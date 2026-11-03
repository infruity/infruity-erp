<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffBranchMigrationTest extends TestCase
{
    private string $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDefaultConnection = config('database.default');

        config(['database.connections.staff_branch_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        config(['database.default' => 'staff_branch_test']);
        DB::purge('staff_branch_test');

        Schema::create('staff', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedBigInteger('position_id')->nullable();
            $table->date('date_in')->nullable();
            $table->string('nickname')->nullable();
        });
        Schema::create('branch', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        DB::table('staff')->insert([
            'id' => 7,
            'name' => 'Existing Employee',
            'position_id' => 3,
            'date_in' => '2024-04-12',
            'nickname' => 'Legacy Nickname',
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('staff_branch_test');
        DB::setDefaultConnection($this->originalDefaultConnection);
        config(['database.default' => $this->originalDefaultConnection]);

        parent::tearDown();
    }

    public function test_staff_branch_column_is_added_without_changing_existing_employee_data(): void
    {
        $migration = require database_path('migrations/2026_10_28_020000_add_branch_id_to_staff_table.php');
        $migration->up();

        self::assertTrue(Schema::hasColumn('staff', 'branch_id'));
        self::assertDatabaseHas('staff', [
            'id' => 7,
            'name' => 'Existing Employee',
            'position_id' => 3,
            'date_in' => '2024-04-12',
            'nickname' => 'Legacy Nickname',
            'branch_id' => null,
        ]);

        $migration->down();
        self::assertFalse(Schema::hasColumn('staff', 'branch_id'));
    }
}
