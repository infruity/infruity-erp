<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffAccountNameLinkMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'staff_account_name_link_test', 'database.connections.staff_account_name_link_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id_user');
            $table->string('nm_user');
            $table->boolean('is_aktif')->default(true);
            $table->unsignedBigInteger('staff_id')->nullable()->unique();
        });
        Schema::create('staff', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('status')->default('aktif');
            $table->unsignedBigInteger('user_id')->nullable()->unique();
        });
    }

    public function test_it_backfills_only_unique_active_name_matches_and_preserves_explicit_links(): void
    {
        DB::table('users')->insert([
            ['id_user' => 1, 'nm_user' => "\u{2060}ALICE", 'is_aktif' => true, 'staff_id' => null],
            ['id_user' => 2, 'nm_user' => 'Alice', 'is_aktif' => false, 'staff_id' => null],
            ['id_user' => 3, 'nm_user' => 'Bob', 'is_aktif' => true, 'staff_id' => null],
            ['id_user' => 4, 'nm_user' => 'Bob', 'is_aktif' => true, 'staff_id' => null],
            ['id_user' => 5, 'nm_user' => 'No Staff Row', 'is_aktif' => true, 'staff_id' => null],
            ['id_user' => 6, 'nm_user' => 'Dana', 'is_aktif' => true, 'staff_id' => 13],
        ]);
        DB::table('staff')->insert([
            ['id' => 10, 'name' => 'Alice', 'status' => 'aktif', 'user_id' => null],
            ['id' => 11, 'name' => 'Bob', 'status' => 'aktif', 'user_id' => null],
            ['id' => 12, 'name' => 'Dana', 'status' => 'aktif', 'user_id' => null],
            ['id' => 13, 'name' => 'Previously Linked', 'status' => 'aktif', 'user_id' => 6],
            ['id' => 14, 'name' => 'Staff Only', 'status' => 'aktif', 'user_id' => null],
        ]);

        $migration = require database_path('migrations/2026_10_27_030000_link_active_accounts_to_staff_by_unique_name.php');
        $migration->up();

        $this->assertDatabaseHas('users', ['id_user' => 1, 'staff_id' => 10]);
        $this->assertDatabaseHas('staff', ['id' => 10, 'user_id' => 1]);
        $this->assertDatabaseHas('users', ['id_user' => 2, 'staff_id' => null]);
        $this->assertDatabaseHas('users', ['id_user' => 3, 'staff_id' => null]);
        $this->assertDatabaseHas('users', ['id_user' => 4, 'staff_id' => null]);
        $this->assertDatabaseHas('staff', ['id' => 11, 'user_id' => null]);
        $this->assertDatabaseHas('users', ['id_user' => 6, 'staff_id' => 13]);
        $this->assertDatabaseHas('staff', ['id' => 13, 'user_id' => 6]);
        $this->assertDatabaseHas('staff', ['id' => 14, 'user_id' => null]);
    }
}
