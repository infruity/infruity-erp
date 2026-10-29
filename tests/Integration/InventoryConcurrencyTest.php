<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use PDO;
use Symfony\Component\Process\Process;
use Tests\Support\InventoryMySqlDatabase;
use Tests\TestCase;

/** Opt-in real MySQL tests, deliberately outside the fast Unit/Feature suites. */
class InventoryConcurrencyTest extends TestCase
{
    private ?PDO $administrator = null;
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $connection = InventoryMySqlDatabase::connection();
        $this->administrator = InventoryMySqlDatabase::administrator($connection);
        $this->database = 'infruity_test_inventory_'.bin2hex(random_bytes(8));
        $this->administrator->exec('CREATE DATABASE `'.$this->database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        config([
            'database.default' => 'inventory_mysql_integration',
            'database.connections.inventory_mysql_integration' => $connection + ['database' => $this->database],
        ]);
        $this->createFixture();
    }

    protected function tearDown(): void
    {
        DB::purge('inventory_mysql_integration');
        if ($this->administrator && $this->database) {
            $this->administrator->exec('DROP DATABASE `'.$this->database.'`');
        }
        $this->administrator = null;
        parent::tearDown();
    }

    public function test_competing_productions_cannot_both_consume_the_last_ingredient_stock(): void
    {
        $this->seedTemporaryProductions();
        $this->assertEquals(5, DB::table('product_stock')->where('id', 10)->where('branch_id', 1)->value('stock_available'));
        $artifactBase = getenv('INFRUITY_TEST_ARTIFACTS') ?: (getenv('TMPDIR') ?: storage_path('framework/testing'));
        $artifactsBefore = glob(rtrim($artifactBase, '/').'/inventory-production-*') ?: [];
        $results = $this->concurrentRequests('production');
        $artifactsAfter = glob(rtrim($artifactBase, '/').'/inventory-production-*') ?: [];
        sort($artifactsBefore);
        sort($artifactsAfter);
        $this->assertSame($artifactsBefore, $artifactsAfter, 'Worker artifacts must be removed after concurrent production requests.');
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame([200, 422], $statuses, json_encode($results));
        $failure = array_values(array_filter($results, fn ($result) => $result['status'] === 422))[0];
        $this->assertStringContainsStringIgnoringCase('stok', json_encode($failure['body']));
        $this->assertStringNotContainsString('SQLSTATE', json_encode($failure['body']));
        $this->assertSame(2, DB::table('production')->count());
        $this->assertSame(1, DB::table('production')->where('status', 'posting')->count());
        $this->assertSame(1, DB::table('production')->where('status', 'temp')->count());
        $this->assertEquals(4, DB::table('production_detail')->sum('quantity'));
        $this->assertEquals(1, DB::table('product_stock')->where('id', 10)->where('branch_id', 1)->value('stock_available'));
        $this->assertOverlappingLockAttempts($results);
    }

    public function test_competing_parent_and_variant_productions_share_one_stock_lock(): void
    {
        $this->seedTemporaryProductions();
        DB::table('products')->insert([
            'id' => 13, 'name' => 'Ingredient variant', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('product_child')->insert([
            'product_id' => 13, 'parent_id' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertSame(2, DB::table('product_stock')->whereIn('id', [10, 13])->where('branch_id', 1)->count());
        $this->assertEquals(5, DB::table('product_stock')->where('id', 10)->where('branch_id', 1)->value('stock_available'));
        $this->assertEquals(5, DB::table('product_stock')->where('id', 13)->where('branch_id', 1)->value('stock_available'));

        $results = $this->concurrentRequests('production-variant');
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame([200, 422], $statuses, json_encode($results));
        $this->assertSame(2, DB::table('production')->count());
        $this->assertSame(1, DB::table('production')->where('status', 'posting')->count());
        $this->assertSame(1, DB::table('production')->where('status', 'temp')->count());
        $this->assertEquals(4, DB::table('production_detail')->sum('quantity'));
        $this->assertEquals(1, DB::table('product_stock')->where('id', 10)->where('branch_id', 1)->value('stock_available'));
        $this->assertEquals(1, DB::table('product_stock')->where('id', 13)->where('branch_id', 1)->value('stock_available'));
        $this->assertOverlappingLockAttempts($results);
    }

    public function test_concurrent_parcel_finalizations_produce_one_output_chain(): void
    {
        DB::table('production_parcel')->insert([
            'id' => 50, 'production_number' => 'PRC202610001', 'production_date' => '2026-10-29',
            'status' => 'posting', 'budget' => 50000, 'quantity' => 1, 'staff_id' => null, 'fee' => 0,
        ]);
        DB::table('production_parcel_detail')->insert(['production_id' => 50, 'product_id' => 10, 'quantity' => 1, 'price' => 3]);
        $results = $this->concurrentRequests('parcel');
        foreach ($results as $result) {
            $this->assertContains($result['status'], [200, 201], json_encode($result));
        }
        $this->assertSame(4, DB::table('products')->count(), 'Only one product may be added to the three fixture products.');
        foreach (['receipt', 'product_receipt', 'production', 'production_detail'] as $table) {
            $this->assertSame(1, DB::table($table)->count(), $table);
        }
        $parcel = DB::table('production_parcel')->find(50);
        $this->assertSame('complete', $parcel->status);
        $this->assertEquals($parcel->product_id, DB::table('receipt')->value('product_id'));
        $this->assertEquals($parcel->product_id, DB::table('production')->value('product_id'));
        $this->assertOverlappingLockAttempts($results);
    }

    private function seedTemporaryProductions(): void
    {
        $common = [
            'production_date' => '2026-10-29', 'quantity' => 0, 'status' => 'temp',
            'staff_id' => null, 'description' => 'Concurrency fixture', 'branch_id' => 1,
            'service_cost' => 0, 'sell_price' => 12500, 'updated_by' => null,
            'pos_id' => null, 'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('production')->insert([
            array_merge($common, ['id' => 101, 'production_number' => 'PRO-LOCK-FIXTURE-101', 'product_id' => 11, 'created_by' => 7]),
            array_merge($common, ['id' => 102, 'production_number' => 'PRO-LOCK-FIXTURE-102', 'product_id' => 12, 'created_by' => 8]),
        ]);
        DB::table('production_detail')->insert([
            ['id' => 201, 'production_id' => 101, 'product_id' => 10, 'quantity' => 0, 'created_by' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 202, 'production_id' => 102, 'product_id' => 10, 'quantity' => 0, 'created_by' => 8, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function assertOverlappingLockAttempts(array $results): void
    {
        $attemptKey = array_key_exists('parent_map_attempt', $results[0]['times'])
            ? 'parent_map_attempt'
            : 'attempt';
        foreach ($results as $result) {
            $this->assertArrayHasKey($attemptKey, $result['times']);
            $this->assertArrayHasKey('locked', $result['times']);
        }
        $latestAttempt = max(array_column(array_column($results, 'times'), $attemptKey));
        $firstFinished = min(array_column(array_column($results, 'times'), 'finished'));
        $this->assertLessThan($firstFinished, $latestAttempt, 'Both requests must attempt a shared mapping or product lock before either request finishes.');
    }

    private function concurrentRequests(string $mode): array
    {
        $base = getenv('INFRUITY_TEST_ARTIFACTS') ?: (getenv('TMPDIR') ?: storage_path('framework/testing'));
        $directory = rtrim($base, '/').'/inventory-'.$mode.'-'.bin2hex(random_bytes(6));
        if (!mkdir($directory, 0700, true)) {
            throw new \RuntimeException('Could not create the isolated inventory artifact directory.');
        }
        $environment = [
            'APP_ENV' => 'testing', 'APP_KEY' => (string) config('app.key'),
            'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DATABASE_URL' => '',
            'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'INFRUITY_TEST_MYSQL_DATABASE' => $this->database,
            'INFRUITY_INVENTORY_SOURCE_ROOT' => $directory.'/untrusted-source-root',
        ];
        foreach (['SOCKET', 'HOST', 'PORT', 'USERNAME', 'PASSWORD'] as $key) {
            $environment['INFRUITY_TEST_MYSQL_'.$key] = getenv('INFRUITY_TEST_MYSQL_'.$key) ?: '';
        }
        $workers = [];
        try {
            foreach (['0', '1'] as $id) {
                $workers[$id] = new Process([PHP_BINARY, base_path('tests/Support/mysql-inventory-worker.php'), $directory, $id, $mode], base_path(), $environment, null, 30);
                $workers[$id]->start();
            }
            $deadline = microtime(true) + 15;
            while (!is_file($directory.'/0.ready') || !is_file($directory.'/1.ready')) {
                foreach ($workers as $worker) {
                    if (!$worker->isRunning()) {
                        $this->fail('Worker stopped before ready: '.$worker->getOutput().$worker->getErrorOutput());
                    }
                }
                if (microtime(true) > $deadline) {
                    $this->fail('Workers did not reach the start barrier.');
                }
                usleep(10000);
            }
            touch($directory.'/go');
            $results = [];
            foreach ($workers as $id => $worker) {
                $worker->wait();
                $this->assertSame(0, $worker->getExitCode(), $worker->getOutput().$worker->getErrorOutput());
                $file = $directory.'/'.$id.'.result.json';
                $this->assertFileExists($file);
                $results[] = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            }
            $controllerPath = realpath(base_path($mode === 'parcel'
                ? 'Modules/Transaction/Http/Controllers/ProductionParcelController.php'
                : 'Modules/Transaction/Http/Controllers/ProductionController.php'));
            foreach ($results as $result) {
                $this->assertSame($controllerPath, realpath($result['controller']));
            }
            return $results;
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
            $this->removeArtifactDirectory($directory);
            $this->assertDirectoryDoesNotExist($directory);
        }
    }

    private function removeArtifactDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (new \FilesystemIterator($directory) as $entry) {
            $path = $entry->getPathname();
            if ($entry->isDir() && !$entry->isLink()) {
                $this->removeArtifactDirectory($path);
            } elseif (!unlink($path)) {
                throw new \RuntimeException('Could not remove inventory test artifact: '.basename($path));
            }
        }

        if (!rmdir($directory)) {
            throw new \RuntimeException('Could not remove the inventory test artifact directory.');
        }
    }

    private function createFixture(): void
    {
        // Minimal columns used by the real controllers and the three real SQL views.
        $common = 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, created_at DATETIME NULL, updated_at DATETIME NULL';
        $tables = [
            'users' => 'id_user BIGINT PRIMARY KEY, nm_user VARCHAR(255), is_aktif BOOLEAN DEFAULT 1',
            'role' => 'id_role BIGINT PRIMARY KEY, nm_role VARCHAR(255)',
            'role_user' => 'id_ru BIGINT AUTO_INCREMENT PRIMARY KEY, id_user BIGINT, id_role BIGINT',
            'role_menu' => 'id_rm BIGINT AUTO_INCREMENT PRIMARY KEY, id_role BIGINT, permission VARCHAR(255)',
            'user_permissions' => 'id BIGINT AUTO_INCREMENT PRIMARY KEY, id_user BIGINT, permission VARCHAR(255), is_allowed BOOLEAN',
            'branch' => $common.', name VARCHAR(255)',
            'user_branch' => $common.', user_id BIGINT, branch_id BIGINT',
            'products' => $common.', name VARCHAR(255), description TEXT NULL, sku VARCHAR(255) NULL, `limit` INTEGER DEFAULT 0, hpp INTEGER DEFAULT 3, price INTEGER DEFAULT 0, tipe VARCHAR(32) DEFAULT \'product\', category_id BIGINT NULL, product_unit BIGINT DEFAULT 1, is_variant INTEGER NULL, parent_id BIGINT NULL, status VARCHAR(32) DEFAULT \'no-receipt\', fee INTEGER DEFAULT 0, created_by BIGINT NULL, updated_by BIGINT NULL',
            'product_units' => $common.', abbreviation VARCHAR(32)',
            'product_child' => $common.', parent_id BIGINT, product_id BIGINT',
            'product_branch' => $common.', product_id BIGINT, branch_id BIGINT, price INTEGER DEFAULT 0, created_by BIGINT NULL, updated_by BIGINT NULL',
            'production' => $common.', production_number VARCHAR(64), product_id BIGINT, production_date DATE, quantity DECIMAL(15,2), status VARCHAR(32), staff_id BIGINT NULL, description TEXT NULL, branch_id BIGINT NULL, service_cost INTEGER NULL, sell_price INTEGER NULL, created_by BIGINT NULL, updated_by BIGINT NULL, pos_id BIGINT NULL',
            // Test-only index isolates shared product-pool locking from unrelated detail-table scans/gap locks.
            'production_detail' => $common.', production_id BIGINT, product_id BIGINT, quantity DECIMAL(15,2), created_by BIGINT NULL, INDEX production_detail_production_id (production_id)',
            'production_parcel' => $common.', production_number VARCHAR(64), production_date DATE, status VARCHAR(32), budget INTEGER, quantity INTEGER, fee INTEGER NULL, staff_id BIGINT NULL, product_id BIGINT NULL, created_by BIGINT NULL, updated_by BIGINT NULL',
            'production_parcel_detail' => $common.', production_id BIGINT, product_id BIGINT, quantity DECIMAL(15,2), price INTEGER NULL, pos_id BIGINT NULL',
            'receipt' => $common.', code VARCHAR(64), product_id BIGINT, description TEXT NULL, created_by BIGINT NULL',
            'product_receipt' => $common.', receipt_id BIGINT, product_id BIGINT, product_receipt_id BIGINT, quantity DECIMAL(15,2), created_by BIGINT NULL',
            'wholesale' => $common.', branch_id BIGINT, status VARCHAR(32), created_by BIGINT NULL',
            'wholesale_product' => $common.', wholesale_id BIGINT, product_id BIGINT, quantity DECIMAL(15,2), price INTEGER',
            'stock_opname' => $common.', branch_id BIGINT, product_id BIGINT, difference DECIMAL(15,2), avg_price INTEGER, status VARCHAR(32), created_by BIGINT NULL',
            'pos_transaction' => $common.', branch_id BIGINT, status VARCHAR(32), created_by BIGINT NULL, deleted_at DATETIME NULL',
            'pos_transaction_detail' => $common.', pos_id BIGINT, product_id BIGINT, parcel_id BIGINT NULL, quantity DECIMAL(15,2), price INTEGER, deleted_at DATETIME NULL',
            'sortir_transaction' => $common.', branch_id BIGINT, status VARCHAR(32), created_by BIGINT NULL',
            'sortir_transaction_detail' => $common.', sortir_id BIGINT, product_id BIGINT, quantity DECIMAL(15,2), price INTEGER',
            'transfer' => $common.', branch_id BIGINT, branch_destination_id BIGINT, status VARCHAR(32), created_by BIGINT NULL',
            'transfer_detail' => $common.', transfer_id BIGINT, product_id BIGINT, quantity DECIMAL(15,2), price INTEGER',
        ];
        foreach (['stock_in', 'stock_out', 'stock_out_transaction'] as $table) {
            $tables[$table] = $common.', product_id BIGINT, quantity DECIMAL(15,2), avg_price INTEGER, code VARCHAR(64), created_by BIGINT NULL';
        }
        foreach ($tables as $table => $columns) {
            DB::unprepared('CREATE TABLE `'.$table.'` ('.$columns.') ENGINE=InnoDB');
        }
        DB::table('users')->insert([['id_user' => 7, 'nm_user' => 'Actor A'], ['id_user' => 8, 'nm_user' => 'Actor B']]);
        DB::table('role')->insert(['id_role' => 1, 'nm_role' => 'Administrator']);
        DB::table('role_user')->insert([['id_user' => 7, 'id_role' => 1], ['id_user' => 8, 'id_role' => 1]]);
        DB::table('branch')->insert(['id' => 1, 'name' => 'Fixture branch']);
        DB::table('user_branch')->insert([['user_id' => 7, 'branch_id' => 1], ['user_id' => 8, 'branch_id' => 1]]);
        DB::table('products')->insert([['id' => 10, 'name' => 'Ingredient'], ['id' => 11, 'name' => 'Output A'], ['id' => 12, 'name' => 'Output B']]);
        DB::table('product_units')->insert(['id' => 1, 'abbreviation' => 'pcs']);
        DB::table('wholesale')->insert(['id' => 1, 'branch_id' => 1, 'status' => 'posting', 'created_by' => 7, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('wholesale_product')->insert(['wholesale_id' => 1, 'product_id' => 10, 'quantity' => 5, 'price' => 3]);
        foreach (['transaction_stock', 'transaction_stock_show', 'product_stock'] as $view) {
            DB::unprepared(file_get_contents(base_path('database/sql/'.$view.'.sql')));
        }
    }
}
