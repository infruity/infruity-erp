<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class StaffIdRepairMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'staff_id_repair_test', 'database.connections.staff_id_repair_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);

        Schema::create('staff', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->string('name');
            $table->string('status')->default('aktif');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id_user');
            $table->string('nm_user');
            $table->unsignedBigInteger('staff_id')->nullable();
        });

        DB::table('staff')->insert([
            ['id' => 4, 'name' => 'Existing Staff', 'status' => 'aktif'],
            ['id' => 0, 'name' => 'Bob', 'status' => 'aktif'],
            ['id' => 0, 'name' => 'Carol', 'status' => 'aktif'],
        ]);
        DB::table('users')->insert([
            ['id_user' => 1, 'nm_user' => 'Bob', 'staff_id' => 0],
            ['id_user' => 2, 'nm_user' => 'BOB', 'staff_id' => 0],
            ['id_user' => 3, 'nm_user' => 'Carol', 'staff_id' => 0],
            ['id_user' => 4, 'nm_user' => 'Existing Staff', 'staff_id' => 4],
        ]);
    }

    public function test_duplicate_zero_staff_ids_are_repaired_and_account_links_follow_their_names(): void
    {
        $migration = require database_path('migrations/2026_10_27_060000_repair_staff_ids_after_account_sync.php');
        $migration->up();

        self::assertSame(0, DB::table('staff')->where('id', 0)->count());
        self::assertSame(3, DB::table('staff')->distinct()->count('id'));
        self::assertSame(5, (int) DB::table('users')->where('id_user', 1)->value('staff_id'));
        self::assertSame(5, (int) DB::table('users')->where('id_user', 2)->value('staff_id'));
        self::assertSame(6, (int) DB::table('users')->where('id_user', 3)->value('staff_id'));
        self::assertSame(4, (int) DB::table('users')->where('id_user', 4)->value('staff_id'));
    }
}
