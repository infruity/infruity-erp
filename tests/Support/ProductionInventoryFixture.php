<?php

namespace Tests\Support;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait ProductionInventoryFixture
{
    protected function createProductionInventorySchema(): void
    {
        // This helper uses the selected connection; MySQL callers select their isolated DB first.
        foreach (['role' => 'id_role', 'role_user' => 'id_ru', 'role_menu' => 'id_rm', 'user_permissions' => 'id'] as $name => $key) {
            Schema::create($name, function (Blueprint $table) use ($name, $key) {
                $table->increments($key);
                if ($name === 'role') {
                    $table->string('nm_role');
                } else {
                    if ($name !== 'role_menu') $table->integer('id_user');
                    if ($name !== 'user_permissions') $table->integer('id_role');
                    if (in_array($name, ['role_menu', 'user_permissions'])) $table->string('permission');
                    if ($name === 'user_permissions') $table->boolean('is_allowed');
                }
            });
        }
        foreach (['branch', 'staff'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });
            DB::table($name)->insert(['id' => 1, 'name' => 'Inventory fixture']);
        }
        foreach ([
            '2025_04_29_090218_create_products_table.php',
            '2025_05_25_105216_create_production_table.php',
            '2025_05_26_040951_create_production_detail_table.php',
            '2025_05_26_212658_create_production_parcel_table.php',
            '2025_05_26_223813_create_production_parcel_detail_table.php',
            '2025_05_26_152320_create_receipt_table.php',
            '2025_05_11_215838_create_product_receipt_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('product_child', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('parent_id');
            $table->timestamps();
        });
        Schema::create('inventory_opening', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('branch_id');
            $table->decimal('quantity', 10, 2);
        });
        // Parent and variant rows expose one shared balance; consumption keeps its entered product ID.
        DB::statement("CREATE VIEW product_stock AS
            SELECT p.id, movements.branch_id, SUM(movements.quantity) AS stock_available
            FROM products AS p
            LEFT JOIN product_child AS requested_child ON requested_child.product_id = p.id
            JOIN (
                SELECT product_id, branch_id, quantity FROM inventory_opening
                UNION ALL SELECT product_id, branch_id, quantity FROM production WHERE status IN ('posting', 'complete')
                UNION ALL SELECT d.product_id, h.branch_id, -d.quantity FROM production_detail d
                    JOIN production h ON h.id = d.production_id WHERE h.status IN ('posting', 'complete')
            ) AS movements ON 1 = 1
            LEFT JOIN product_child AS source_child ON source_child.product_id = movements.product_id
            WHERE COALESCE(source_child.parent_id, movements.product_id) = COALESCE(requested_child.parent_id, p.id)
            GROUP BY p.id, movements.branch_id");
        DB::table('inventory_opening')->insert(['product_id' => 1, 'branch_id' => 1, 'quantity' => 10]);
    }

    protected function actingAsInventoryOperator(array $permissions = ['production.store', 'parcel.set_selesai']): void
    {
        DB::table('role')->insert(['id_role' => 2, 'nm_role' => 'Inventory operator']);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        foreach ($permissions as $permission) {
            DB::table('role_menu')->insert(['id_role' => 2, 'permission' => $permission]);
        }
        $user = new User();
        $user->id_user = 7;
        $this->actingAs($user);
        session(['role' => ['id_role' => 2]]);
    }

    protected function useSqliteInventoryFixture(): void
    {
        config(['database.default' => 'production_integrity_test', 'database.connections.production_integrity_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        // Translate only the legacy numbering query's MySQL RIGHT syntax; execute
        // its real SQL against SQLite rather than mocking Receipt or its writes.
        DB::connection()->setQueryGrammar(new class(DB::connection()) extends \Illuminate\Database\Query\Grammars\SQLiteGrammar {
            public function compileSelect(\Illuminate\Database\Query\Builder $query)
            {
                return preg_replace('/RIGHT\\((\\w+),\\s*(\\d+)\\)/i', 'substr($1, -$2)', parent::compileSelect($query));
            }
        });
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('NOW', fn () => date('Y-m-d H:i:s'));
        $pdo->sqliteCreateFunction('MONTH', fn ($value) => (int) date('m', strtotime($value ?? '1970-01-01')));
        $pdo->sqliteCreateFunction('YEAR', fn ($value) => (int) date('Y', strtotime($value ?? '1970-01-01')));
        $this->createProductionInventorySchema();
        $this->actingAsInventoryOperator();
    }

    protected function productionPayload(array $changes = []): array
    {
        return array_replace([
            'product_id' => 9, 'production_number' => 'PRO202610001', 'production_date' => '2026-10-29',
            'submit_type' => 'posting', 'quantity' => 1, 'branch_id' => 1, 'service_cost' => 0, 'sell_price' => 10000,
            'ingredients' => [['id' => 1, 'quantity' => 6, 'hpp' => 100]],
        ], $changes);
    }

    protected function seedParcel(string $status = 'posting'): int
    {
        $id = DB::table('production_parcel')->insertGetId([
            'production_number' => 'PRC202610001', 'production_date' => '2026-10-29',
            'status' => $status, 'budget' => 100000, 'quantity' => 1, 'staff_id' => 1,
        ]);
        DB::table('production_parcel_detail')->insert([
            'production_id' => $id, 'product_id' => 1, 'quantity' => 2, 'quantity_kemasan' => 0,
        ]);
        return $id;
    }
}
