<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleAssignmentsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.role_assignments_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        config(['database.default' => 'role_assignments_test']);
        DB::purge('role_assignments_test');

        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id_ru');
            $table->integer('id_user');
            $table->integer('id_role');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('role_user')->insert([
            ['id_ru' => 2, 'id_user' => 10, 'id_role' => 3, 'deleted_at' => '2026-10-01 00:00:00'],
            ['id_ru' => 5, 'id_user' => 10, 'id_role' => 3, 'deleted_at' => null],
            ['id_ru' => 9, 'id_user' => 10, 'id_role' => 4, 'deleted_at' => null],
        ]);
    }

    public function test_role_assignments_are_unique_per_account_and_role_without_losing_distinct_roles(): void
    {
        $migration = require database_path('migrations/2026_10_28_010000_enforce_account_role_assignments.php');
        $migration->up();

        self::assertSame(2, DB::table('role_user')->count());
        self::assertDatabaseHas('role_user', ['id_ru' => 5, 'id_user' => 10, 'id_role' => 3]);
        self::assertDatabaseMissing('role_user', ['id_ru' => 2]);
        self::assertDatabaseHas('role_user', ['id_ru' => 9, 'id_user' => 10, 'id_role' => 4]);

        $indexes = Schema::getIndexes('role_user');
        self::assertTrue(collect($indexes)->contains(fn ($index) => $index['unique'] && $index['columns'] === ['id_user', 'id_role']));
    }
}
