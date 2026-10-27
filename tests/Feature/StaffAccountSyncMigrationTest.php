<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class StaffAccountSyncMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'staff_account_sync_test', 'database.connections.staff_account_sync_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id_user');
            $table->string('nm_user');
            $table->boolean('is_aktif')->default(true);
            $table->unsignedBigInteger('staff_id')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('staff', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('status')->default('aktif');
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->timestamps();
        });

        DB::table('users')->insert([
            ['id_user' => 1, 'nm_user' => 'Alice', 'is_aktif' => true, 'staff_id' => 10],
            ['id_user' => 2, 'nm_user' => "\u{2060}ALICE", 'is_aktif' => true, 'staff_id' => null],
            ['id_user' => 3, 'nm_user' => 'Alice', 'is_aktif' => false, 'staff_id' => null],
            ['id_user' => 4, 'nm_user' => 'Bob', 'is_aktif' => true, 'staff_id' => null],
            ['id_user' => 5, 'nm_user' => "\u{2060}BOB", 'is_aktif' => false, 'staff_id' => null],
            ['id_user' => 6, 'nm_user' => 'Name Changed Since Link', 'is_aktif' => true, 'staff_id' => 11],
            ['id_user' => 7, 'nm_user' => 'Dana', 'is_aktif' => true, 'staff_id' => null],
            ['id_user' => 8, 'nm_user' => 'Dana', 'is_aktif' => false, 'staff_id' => null],
            ['id_user' => 9, 'nm_user' => '  Carol   Person  ', 'is_aktif' => true, 'staff_id' => null],
            ['id_user' => 10, 'nm_user' => 'Carol Person', 'is_aktif' => false, 'staff_id' => null],
        ]);
        DB::table('staff')->insert([
            ['id' => 10, 'name' => 'Alice', 'status' => 'aktif', 'user_id' => 1],
            ['id' => 11, 'name' => 'Former Staff Name', 'status' => 'aktif', 'user_id' => 6],
            ['id' => 12, 'name' => 'Dana', 'status' => 'aktif', 'user_id' => null],
            ['id' => 13, 'name' => "\u{2060}DANA", 'status' => 'aktif', 'user_id' => null],
        ]);
    }

    public function test_accounts_share_staff_by_unique_name_and_missing_people_get_one_staff_row(): void
    {
        $schemaMigration = require database_path('migrations/2026_10_27_040000_allow_multiple_accounts_per_staff.php');
        $schemaMigration->up();
        $dataMigration = require database_path('migrations/2026_10_27_050000_sync_user_accounts_to_staff_records.php');
        $dataMigration->up();
        $staffIdMigration = require database_path('migrations/2026_10_27_060000_repair_staff_ids_after_account_sync.php');
        $staffIdMigration->up();

        self::assertFalse(Schema::hasColumn('staff', 'user_id'));
        self::assertSame(10, (int) DB::table('users')->where('id_user', 1)->value('staff_id'));
        self::assertSame(10, (int) DB::table('users')->where('id_user', 2)->value('staff_id'));
        self::assertSame(10, (int) DB::table('users')->where('id_user', 3)->value('staff_id'));

        $bobStaffId = (int) DB::table('staff')->where('name', 'Bob')->value('id');
        self::assertGreaterThan(0, $bobStaffId);
        self::assertSame($bobStaffId, (int) DB::table('users')->where('id_user', 4)->value('staff_id'));
        self::assertSame($bobStaffId, (int) DB::table('users')->where('id_user', 5)->value('staff_id'));

        self::assertSame(11, (int) DB::table('users')->where('id_user', 6)->value('staff_id'));
        self::assertNull(DB::table('users')->where('id_user', 7)->value('staff_id'));
        self::assertNull(DB::table('users')->where('id_user', 8)->value('staff_id'));
        self::assertSame(6, DB::table('staff')->count(), 'Ambiguous names are skipped; each unique unmatched name gets one row.');

        $carolStaffId = (int) DB::table('staff')->where('name', 'Carol Person')->value('id');
        self::assertGreaterThan(0, $carolStaffId);
        self::assertSame($carolStaffId, (int) DB::table('users')->where('id_user', 9)->value('staff_id'));
        self::assertSame($carolStaffId, (int) DB::table('users')->where('id_user', 10)->value('staff_id'));
    }
}
