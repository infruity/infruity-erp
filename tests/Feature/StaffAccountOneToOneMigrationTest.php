<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffAccountOneToOneMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.staff_one_to_one_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        config(['database.default' => 'staff_one_to_one_test']);
        DB::purge('staff_one_to_one_test');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id_user');
            $table->string('nm_user');
            $table->boolean('is_aktif')->default(true);
            $table->unsignedBigInteger('staff_id')->nullable();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        DB::table('staff')->insert([
            ['id' => 10, 'name' => 'Shared Staff'],
            ['id' => 20, 'name' => 'Inactive Only'],
        ]);
        DB::table('users')->insert([
            ['id_user' => 1, 'nm_user' => 'Active First', 'is_aktif' => true, 'staff_id' => 10],
            ['id_user' => 2, 'nm_user' => 'Active Second', 'is_aktif' => true, 'staff_id' => 10],
            ['id_user' => 3, 'nm_user' => 'Inactive Third', 'is_aktif' => false, 'staff_id' => 10],
            ['id_user' => 4, 'nm_user' => 'Inactive Keeper', 'is_aktif' => false, 'staff_id' => 20],
            ['id_user' => 5, 'nm_user' => 'Dangling Link', 'is_aktif' => true, 'staff_id' => 99],
            ['id_user' => 6, 'nm_user' => 'Unlinked', 'is_aktif' => true, 'staff_id' => null],
        ]);
    }

    public function test_duplicate_links_are_unlinked_without_deleting_accounts_and_both_sides_are_unique(): void
    {
        $migration = require database_path('migrations/2026_10_28_000000_enforce_one_to_one_user_staff_links.php');
        $migration->up();

        self::assertSame(10, (int) DB::table('users')->where('id_user', 1)->value('staff_id'));
        self::assertNull(DB::table('users')->where('id_user', 2)->value('staff_id'));
        self::assertNull(DB::table('users')->where('id_user', 3)->value('staff_id'));
        self::assertSame(20, (int) DB::table('users')->where('id_user', 4)->value('staff_id'));
        self::assertNull(DB::table('users')->where('id_user', 5)->value('staff_id'));
        self::assertSame(6, DB::table('users')->count());
        self::assertSame(1, (int) DB::table('staff')->where('id', 10)->value('user_id'));
        self::assertSame(4, (int) DB::table('staff')->where('id', 20)->value('user_id'));

        $userIndexes = Schema::getIndexes('users');
        $staffIndexes = Schema::getIndexes('staff');
        self::assertTrue(collect($userIndexes)->contains(fn ($index) => $index['unique'] && $index['columns'] === ['staff_id']));
        self::assertTrue(collect($staffIndexes)->contains(fn ($index) => $index['unique'] && $index['columns'] === ['user_id']));
    }
}
