<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Shared fixtures for the pre-deploy behaviour tests (docs/qa/pre-deploy-test-plan.md, phase 1).
 *
 * These tests write real rows, so they only run on a migrated MySQL database whose name ends in
 * _test, and every test rolls back through DatabaseTransactions. Run them with:
 *   php vendor/bin/phpunit -c phpunit.mysql.xml
 */
trait QaFixtures
{
    protected function requireMysqlTestDatabase(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('pos_transaction')) {
            $this->markTestSkipped('Needs a migrated database (use phpunit.mysql.xml).');
        }

        $database = (string) DB::connection()->getDatabaseName();
        if (! str_ends_with($database, '_test')) {
            $this->markTestSkipped("Refusing to run against non-test database '{$database}'.");
        }
    }

    /** @var array<string, array{0:string,1:int}> table => [auto-increment column, max id before the test] */
    private array $committedBaseline = [];

    /**
     * For endpoints that call DB::disconnect() (e.g. WholesaleController::saveTransaction): that closes
     * the connection and drops the transaction DatabaseTransactions wraps the test in, so rows would
     * leak and fixture users would vanish mid-test. Use this instead of DatabaseTransactions: the test
     * commits for real, and cleanCommittedRows() deletes every row added since (tests run serially on
     * a disposable *_test database).
     */
    protected function rememberCommittedBaseline(): void
    {
        $database = DB::connection()->getDatabaseName();
        $columns = DB::select(
            "SELECT c.TABLE_NAME t, c.COLUMN_NAME col FROM information_schema.COLUMNS c
             JOIN information_schema.TABLES tb ON tb.TABLE_SCHEMA = c.TABLE_SCHEMA AND tb.TABLE_NAME = c.TABLE_NAME AND tb.TABLE_TYPE = 'BASE TABLE'
             WHERE c.TABLE_SCHEMA = ? AND c.EXTRA LIKE '%auto_increment%'",
            [$database]
        );

        foreach ($columns as $c) {
            $max = (int) (DB::table($c->t)->max($c->col) ?? 0);
            $this->committedBaseline[$c->t] = [$c->col, $max];
        }
    }

    protected function cleanCommittedRows(): void
    {
        if ($this->committedBaseline === []) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->committedBaseline as $table => [$column, $max]) {
            DB::table($table)->where($column, '>', $max)->delete();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->committedBaseline = [];
    }

    protected function makeRole(string $name, array $permissionKeys = []): int
    {
        $roleId = DB::table('role')->insertGetId([
            'nm_role'    => $name . ' ' . Str::random(6),
            'id_creator' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $roleId;
    }

    protected function superAdminRoleId(): int
    {
        if (! DB::table('role')->where('id_role', 1)->exists()) {
            DB::table('role')->insert(['id_role' => 1, 'nm_role' => 'Super Admin', 'id_creator' => 1]);
        }

        return 1;
    }

    /**
     * Create a login-capable user. $roleId null = no role assignment at all.
     */
    protected function makeUser(?int $roleId, array $attributes = [], array $branchIds = []): User
    {
        $suffix = Str::lower(Str::random(8));

        $id = DB::table('users')->insertGetId(array_merge([
            'nm_user'    => 'QA User ' . $suffix,
            'username'   => 'qa_' . $suffix,
            'email'      => "qa_{$suffix}@example.test",
            'password'   => bcrypt('qa-password'),
            'is_aktif'   => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        if ($roleId !== null) {
            DB::table('role_user')->insert(['id_user' => $id, 'id_role' => $roleId]);
        }

        foreach ($branchIds as $branchId) {
            DB::table('user_branch')->insert(['user_id' => $id, 'branch_id' => $branchId]);
        }

        return User::withoutGlobalScope('is_aktif')->findOrFail($id);
    }

    protected function makeBranch(string $label = 'QA'): int
    {
        $suffix = Str::upper(Str::random(6));

        return DB::table('branch')->insertGetId([
            'name'       => "{$label} Cabang {$suffix}",
            'code'       => "Q{$suffix}",
            'address'    => "Jl. {$label} {$suffix}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeProduct(string $label = 'Apel', int $price = 20000): int
    {
        return DB::table('products')->insertGetId([
            'name'       => 'QA ' . $label . ' ' . Str::random(5),
            'price'      => $price,
            'tipe'       => 'product',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Put stock into a branch's ledger through a finished opname row (same trick as TransferConcurrencyTest).
     */
    protected function giveStock(int $productId, int $branchId, float $qty, int $avgPrice = 10000): void
    {
        DB::table('stock_opname')->insert([
            'code'        => 'SO-QA-' . Str::upper(Str::random(8)),
            'date'        => date('Y-m-d'),
            'branch_id'   => $branchId,
            'status'      => 'selesai',
            'product_id'  => $productId,
            'stock'       => 0,
            'real_stock'  => $qty,
            'difference'  => $qty,
            'avg_price'   => $avgPrice,
            'created_by'  => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    protected function actAsRole(User $user, int $roleId): void
    {
        $this->actingAs($user);
        $name = (string) DB::table('role')->where('id_role', $roleId)->value('nm_role');
        session(['role' => ['id_role' => $roleId, 'nm_role' => $name]]);
    }

    protected function paymentMethodId(string $name): int
    {
        $existing = DB::table('payment_method')->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');

        return $existing ?: DB::table('payment_method')->insertGetId([
            'name'       => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
