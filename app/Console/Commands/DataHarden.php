<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unique document numbers (finding D1) and header/line foreign keys (D2), added only where the data already obeys them.
 *
 * Not a migration on purpose: the final sync from the old app truncates and reloads these tables, and the old data
 * holds duplicate numbers, which a unique index rejects even with foreign key checks off. Run this after that sync.
 *
 * Without options it only reads. It also reports who holds the risky permissions and whether debugging is on.
 */
class DataHarden extends Command
{
    protected $signature = 'data:harden {--apply : add the constraints whose data is clean} {--drop : remove the constraints this command added}';

    protected $description = 'Check data and add unique document numbers and header/line foreign keys';

    /**
     * The sale number a row holds, by the rule of PosModel::invoiceNumberTaken(): a deleted temporary draft gives its
     * number back. ORD numbers follow their order book and are left alone.
     */
    private const HELD_INVOICE = "CASE WHEN invoice_number LIKE 'INV%' AND NOT (status <=> 'temp' AND deleted_at IS NOT NULL) THEN invoice_number END";

    private const INVOICE_COLUMN = 'hd_active_invoice';

    private const INVOICE_INDEX = 'hd_uq_pos_transaction_invoice';

    /** table => column. pos_transaction is handled by invoice(): there a number may be used again. */
    private const UNIQUE = [
        'wholesale' => 'order_number',
        'stock_opname' => 'code',
        'production' => 'production_number',
        'transfer' => 'invoice_number',
    ];

    /** [line table, column, header table]. Deleting a header takes its lines with it, as the screens already do. */
    private const FOREIGN = [
        ['pos_transaction_detail', 'pos_id', 'pos_transaction'],
        ['pos_payment', 'pos_id', 'pos_transaction'],
        ['wholesale_product', 'wholesale_id', 'wholesale'],
        ['transfer_detail', 'transfer_id', 'transfer'],
        ['production_detail', 'production_id', 'production'],
        ['sortir_transaction_detail', 'sortir_id', 'sortir_transaction'],
        ['order_book_detail', 'order_book_id', 'order_book'],
        ['arus_kas_items', 'arus_kas_transaction_id', 'arus_kas_transactions'],
        ['stock_opname_history', 'stock_opname_id', 'stock_opname'],
        ['expenditure_detail', 'expenditure_id', 'expenditure'],
        ['product_receipt', 'receipt_id', 'receipt'],
        ['customer_address', 'customer_id', 'customer'],
    ];

    private const RISKY_PERMISSIONS = ['staff.store', 'staff.update', 'user.store', 'user.update'];

    public function handle(): int
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->error('Perintah ini hanya untuk MySQL / MariaDB.');

            return self::FAILURE;
        }
        if ($this->option('drop')) {
            return $this->drop();
        }

        $apply = (bool) $this->option('apply');
        $rows = [];
        foreach (self::UNIQUE as $table => $column) {
            $rows[] = [$table . '.' . $column, 'unique', $this->unique($table, $column, $apply)];
        }
        $rows[] = ['pos_transaction.invoice_number', 'unique', $this->invoice($apply)];
        foreach (self::FOREIGN as [$table, $column, $parent]) {
            $rows[] = ["{$table}.{$column} -> {$parent}", 'foreign key', $this->foreign($table, $column, $parent, $apply)];
        }
        $this->table(['Kolom', 'Jenis', 'Hasil'], $rows);

        $this->environmentReport();
        $this->permissionReport();

        if (! $apply) {
            $this->line('Tidak ada yang diubah. Jalankan dengan --apply setelah backup (dan setelah sinkron data final).');
        }

        return self::SUCCESS;
    }

    private function unique(string $table, string $column, bool $apply): string
    {
        $name = "hd_uq_{$table}_{$column}";
        if (! Schema::hasColumn($table, $column)) {
            return 'dilewati: kolom tidak ada';
        }
        if ($this->hasIndex($table, $name)) {
            return 'sudah terpasang';
        }
        $duplicates = DB::table($table)->select($column)->whereNotNull($column)->groupBy($column)->havingRaw('COUNT(*) > 1')->get();
        if ($duplicates->isNotEmpty()) {
            return 'DITAHAN: ' . $duplicates->count() . ' nomor kembar, mis. ' . $duplicates->take(3)->pluck($column)->implode(', ');
        }
        if (! $apply) {
            return 'siap dipasang';
        }
        DB::statement("ALTER TABLE `{$table}` ADD UNIQUE KEY `{$name}` (`{$column}`)");

        return 'dipasang';
    }

    private function invoice(bool $apply): string
    {
        if (! Schema::hasColumns('pos_transaction', ['invoice_number', 'status', 'deleted_at'])) {
            return 'dilewati: kolom tidak ada';
        }
        if ($this->hasIndex('pos_transaction', self::INVOICE_INDEX)) {
            return 'sudah terpasang';
        }
        $held = DB::table('pos_transaction')->selectRaw(self::HELD_INVOICE . ' AS number');
        $duplicates = DB::query()->fromSub($held, 'held')->select('number')->whereNotNull('number')
            ->groupBy('number')->havingRaw('COUNT(*) > 1')->get();
        if ($duplicates->isNotEmpty()) {
            return 'DITAHAN: ' . $duplicates->count() . ' nomor kembar, mis. ' . $duplicates->take(3)->pluck('number')->implode(', ');
        }
        if (! $apply) {
            return 'siap dipasang';
        }
        // A column the database computes itself, because neither MySQL nor MariaDB can index only some rows.
        if (! Schema::hasColumn('pos_transaction', self::INVOICE_COLUMN)) {
            DB::statement('ALTER TABLE `pos_transaction` ADD COLUMN `' . self::INVOICE_COLUMN . '` VARCHAR(191) AS (' . self::HELD_INVOICE . ') VIRTUAL');
        }
        DB::statement('ALTER TABLE `pos_transaction` ADD UNIQUE KEY `' . self::INVOICE_INDEX . '` (`' . self::INVOICE_COLUMN . '`)');

        return 'dipasang';
    }

    private function foreign(string $table, string $column, string $parent, bool $apply): string
    {
        $name = substr("hd_fk_{$table}_{$column}", 0, 64);
        if (! Schema::hasColumn($table, $column) || ! Schema::hasColumn($parent, 'id')) {
            return 'dilewati: kolom tidak ada';
        }
        if ($this->hasForeignOn($table, $column)) {
            return 'sudah terpasang';
        }
        if ($this->columnType($table, $column) !== $this->columnType($parent, 'id')) {
            return 'dilewati: tipe kolom berbeda (' . $this->columnType($table, $column) . ' vs ' . $this->columnType($parent, 'id') . ')';
        }
        $orphans = DB::table($table . ' as line')->whereNotNull("line.{$column}")
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from($parent . ' as header')->whereColumn('header.id', "line.{$column}"))
            ->count();
        if ($orphans > 0) {
            return "DITAHAN: {$orphans} baris tanpa induk";
        }
        if (! $apply) {
            return 'siap dipasang';
        }
        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$column}`) REFERENCES `{$parent}` (`id`) ON DELETE CASCADE");

        return 'dipasang';
    }

    private function drop(): int
    {
        foreach (self::FOREIGN as [$table, $column]) {
            $name = substr("hd_fk_{$table}_{$column}", 0, 64);
            if ($this->hasForeign($table, $name)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
                $this->line("dilepas: {$name}");
            }
            // MySQL keeps the index it made for the key.
            if ($this->hasIndex($table, $name) && ! $this->hasForeignOn($table, $column)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
            }
        }
        foreach (self::UNIQUE as $table => $column) {
            $name = "hd_uq_{$table}_{$column}";
            if ($this->hasIndex($table, $name)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
                $this->line("dilepas: {$name}");
            }
        }

        if ($this->hasIndex('pos_transaction', self::INVOICE_INDEX)) {
            DB::statement('ALTER TABLE `pos_transaction` DROP INDEX `' . self::INVOICE_INDEX . '`');
            $this->line('dilepas: ' . self::INVOICE_INDEX);
        }
        if (Schema::hasColumn('pos_transaction', self::INVOICE_COLUMN)) {
            DB::statement('ALTER TABLE `pos_transaction` DROP COLUMN `' . self::INVOICE_COLUMN . '`');
        }

        return self::SUCCESS;
    }

    private function environmentReport(): void
    {
        $this->newLine();
        $this->line('Konfigurasi: APP_ENV=' . app()->environment() . ', APP_DEBUG=' . (config('app.debug') ? 'true' : 'false')
            . ', debugbar ' . (class_exists(\Barryvdh\Debugbar\ServiceProvider::class) ? 'TERPASANG' : 'tidak terpasang'));
    }

    private function permissionReport(): void
    {
        $byRole = DB::table('role_menu as menu')->join('role', 'role.id_role', '=', 'menu.id_role')
            ->where('menu.id_role', '<>', 1)->whereIn('menu.permission', self::RISKY_PERMISSIONS)
            ->when(Schema::hasColumn('role_menu', 'deleted_at'), fn ($query) => $query->whereNull('menu.deleted_at'))
            ->get(['role.nm_role as holder', 'menu.permission']);
        $byAccount = DB::table('user_permissions as allowed')->join('users', 'users.id_user', '=', 'allowed.id_user')
            ->where('allowed.is_allowed', 1)->whereIn('allowed.permission', self::RISKY_PERMISSIONS)
            ->get(['users.nm_user as holder', 'allowed.permission']);

        $this->line('Pemegang izin kelola karyawan/akun selain Administrator:');
        $holders = $byRole->map(fn ($row) => ['role', $row->holder, $row->permission])
            ->concat($byAccount->map(fn ($row) => ['akun', $row->holder, $row->permission]));
        $holders->isEmpty() ? $this->line('  (tidak ada)') : $this->table(['Lewat', 'Nama', 'Izin'], $holders->all());
    }

    private function hasIndex(string $table, string $name): bool
    {
        return Schema::hasTable($table) && collect(Schema::getIndexes($table))->contains('name', $name);
    }

    private function hasForeign(string $table, string $name): bool
    {
        return Schema::hasTable($table) && collect(Schema::getForeignKeys($table))->contains('name', $name);
    }

    /** Some of these columns already have a key from their own migration, under another name. */
    private function hasForeignOn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && collect(Schema::getForeignKeys($table))->contains(fn ($key) => $key['columns'] === [$column]);
    }

    private function columnType(string $table, string $column): string
    {
        return (string) DB::table('information_schema.COLUMNS')->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)->where('COLUMN_NAME', $column)->value('COLUMN_TYPE');
    }
}
