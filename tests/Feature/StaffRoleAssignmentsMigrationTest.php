<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffRoleAssignmentsMigrationTest extends TestCase
{
    private string $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDefaultConnection = config('database.default');
        config(['database.connections.staff_role_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        config(['database.default' => 'staff_role_test']);
        DB::purge('staff_role_test');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id_user');
            $table->unsignedBigInteger('staff_id')->nullable()->unique();
        });
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('user_id')->nullable()->unique();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id_ru');
            $table->integer('id_user');
            $table->integer('id_role');
        });

        DB::table('users')->insert([
            ['id_user' => 2, 'staff_id' => 10],
            ['id_user' => 3, 'staff_id' => null],
        ]);
        DB::table('staff')->insert([
            ['id' => 10, 'name' => 'Linked Employee', 'user_id' => 2],
            ['id' => 11, 'name' => 'Unlinked Employee', 'user_id' => null],
        ]);
        DB::table('role_user')->insert([
            ['id_user' => 2, 'id_role' => 2],
            ['id_user' => 2, 'id_role' => 3],
            ['id_user' => 3, 'id_role' => 3],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('staff_role_test');
        DB::setDefaultConnection($this->originalDefaultConnection);
        config(['database.default' => $this->originalDefaultConnection]);
        parent::tearDown();
    }

    public function test_migration_backfills_pending_roles_only_through_explicit_staff_account_links(): void
    {
        $migration = require database_path('migrations/2026_10_28_030000_create_staff_role_assignments_table.php');
        $migration->up();
        $migration->up();

        self::assertTrue(Schema::hasTable('staff_role'));
        self::assertEqualsCanonicalizing(
            [2, 3],
            DB::table('staff_role')->where('staff_id', 10)->pluck('id_role')->map(fn ($id) => (int) $id)->all()
        );
        self::assertDatabaseMissing('staff_role', ['staff_id' => 11]);
        self::assertSame(2, DB::table('staff_role')->count());
        self::assertTrue(collect(Schema::getIndexes('staff_role'))->contains(
            fn (array $index) => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['staff_id', 'id_role']
        ));

        $migration->down();
        self::assertFalse(Schema::hasTable('staff_role'));
    }
}
