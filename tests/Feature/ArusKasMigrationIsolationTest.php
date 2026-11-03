<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArusKasMigrationIsolationTest extends TestCase
{
    private string $testDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDatabase = tempnam(getenv('TMPDIR') ?: storage_path('framework/testing'), 'arus-kas-migration-');
        config(['database.default' => 'arus_kas_migration_test', 'database.connections.arus_kas_migration_test' => [
            'driver' => 'sqlite', 'database' => $this->testDatabase, 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        Schema::create('expenditure', function (Blueprint $table) {
            $table->increments('id');
            $table->string('legacy_marker');
        });
        DB::table('expenditure')->insert(['legacy_marker' => 'keep-existing-row']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('arus_kas_migration_test');
        if (isset($this->testDatabase) && file_exists($this->testDatabase)) {
            unlink($this->testDatabase);
        }
        parent::tearDown();
    }

    public function test_feature_migration_adds_new_tables_without_changing_legacy_finance_data(): void
    {
        $migration = require base_path('database/migrations/2026_11_01_010000_create_arus_kas_tables.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('arus_kas_transactions'));
        $this->assertTrue(Schema::hasTable('arus_kas_items'));
        $this->assertSame('keep-existing-row', DB::table('expenditure')->value('legacy_marker'));
        $this->assertSame(1, DB::table('expenditure')->count());
    }
}
