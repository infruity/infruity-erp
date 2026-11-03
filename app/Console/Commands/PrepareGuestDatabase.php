<?php

namespace App\Console\Commands;

use App\Http\Middleware\UseGuestDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PrepareGuestDatabase extends Command
{
    protected $signature = 'guest:prepare';

    protected $description = 'Create an isolated Guest schema and a demo-only account without copying business data';

    public function handle(): int
    {
        if (! UseGuestDatabase::isIsolated()) {
            $this->error('GUEST_DB_DATABASE must be configured and distinct from DB_DATABASE.');
            return 1;
        }

        $source = config('database.connections.mysql.database');
        $target = config('database.connections.guest.database');
        if (! preg_match('/^[A-Za-z0-9_]+$/', $target)) {
            $this->error('The Guest database name may contain only letters, numbers, and underscores.');
            return 1;
        }

        $primary = DB::connection('mysql');
        $primary->statement("CREATE DATABASE IF NOT EXISTS `{$target}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $guest = DB::connection('guest');
        $existingObjects = $primary->table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $target)->count();
        if ($existingObjects && ! $this->objectExists($target, 'guest_installation')) {
            $this->error('The target database already contains objects and is not marked as a Guest schema.');
            return 1;
        }
        if ($existingObjects) {
            $marker = $guest->table('guest_installation')->where('id', 1)->value('database_name');
            if ($marker !== $target) {
                $this->error('The target database has an invalid Guest marker.');
                return 1;
            }
        }
        $guest->statement('CREATE TABLE IF NOT EXISTS guest_installation (id TINYINT UNSIGNED PRIMARY KEY, database_name VARCHAR(128) NOT NULL)');
        $guest->table('guest_installation')->updateOrInsert(['id' => 1], ['database_name' => $target]);

        $tables = $primary->select(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME",
            [$source]
        );
        $guest->statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($tables as $table) {
                $name = $table->TABLE_NAME;
                if ($this->objectExists($target, $name)) {
                    continue;
                }
                $create = $primary->select("SHOW CREATE TABLE `{$name}`")[0]->{'Create Table'};
                $create = preg_replace('/\sAUTO_INCREMENT=\d+\b/', '', $create);
                $guest->statement($create);
            }
        } finally {
            $guest->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $viewFiles = [
            'transaction_stock' => 'sql/transaction_stock.sql',
            'transaction_stock_show' => 'sql/transaction_stock_show.sql',
            'product_hpp' => 'sql/product_hpp.sql',
            'product_stock' => 'sql/product_stock.sql',
        ];
        $pending = array_keys($viewFiles);
        $errors = [];
        for ($pass = 0; $pass < count($pending) + 1 && $pending; $pass++) {
            $remaining = [];
            foreach ($pending as $name) {
                if ($this->objectExists($target, $name)) {
                    continue;
                }
                try {
                    $sql = file_get_contents(database_path($viewFiles[$name]));
                    if ($sql === false) {
                        throw new \RuntimeException("Missing view definition: {$name}");
                    }
                    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                        $guest->unprepared($statement);
                    }
                    unset($errors[$name]);
                } catch (\Throwable $exception) {
                    $remaining[] = $name;
                    $errors[$name] = get_class($exception) . ' (' . $exception->getCode() . ')';
                }
            }
            if (count($remaining) === count($pending)) {
                break;
            }
            $pending = $remaining;
        }
        if ($pending) {
            foreach ($pending as $name) {
                $this->error("Unable to prepare Guest view {$name}: {$errors[$name]}");
            }
            return 1;
        }

        if (! $guest->table('users')->where('username', 'guest')->exists()) {
            $guest->table('users')->insert([
                'id_user' => 1,
                'nm_user' => 'Guest Demo',
                'username' => 'guest',
                'email' => 'guest@demo.invalid',
                'password' => Hash::make(Str::random(64)),
                'is_aktif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $guest->table('role')->updateOrInsert(['id_role' => 1], [
            'nm_role' => 'Administrator Demo',
            'id_creator' => 1,
        ]);
        $guest->table('role_user')->updateOrInsert(['id_user' => 1, 'id_role' => 1], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $guest->table('branch')->updateOrInsert(['id' => 1], [
            'name' => 'Cabang Demo',
            'address' => 'Data contoh Guest',
        ]);
        $guest->table('user_branch')->updateOrInsert(['user_id' => 1, 'branch_id' => 1], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Guest schema prepared. No users, customers, products, or transactions were copied from the primary database.');
        return 0;
    }

    private function objectExists(string $schema, string $name): bool
    {
        return DB::connection('mysql')->table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $name)
            ->exists();
    }
}
