<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * The Jabatan form only asks for the position name (like the HTML reference). Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class PositionNameOnlyFormTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $departmentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->departmentId = (int) DB::table('department')->insertGetId(['name' => 'Tim Uji Satu', 'created_at' => now(), 'updated_at' => now()]);
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    public function test_a_position_can_be_created_with_the_name_only_and_gets_a_department_and_code(): void
    {
        $this->postJson('/position', ['name' => 'Kru Toko Uji'])->assertSuccessful();

        $position = DB::table('position')->where('name', 'Kru Toko Uji')->first();
        $this->assertNotNull($position);
        $this->assertNotNull($position->department_id);
        $this->assertMatchesRegularExpression('/^[A-Z]+\d{3}$/', $position->code);
        $this->assertNotNull($position->updated_by);
    }

    public function test_renaming_with_the_name_only_keeps_the_department_and_the_code(): void
    {
        $id = DB::table('position')->insertGetId(['name' => 'Lama', 'code' => 'TUS001', 'department_id' => $this->departmentId, 'created_at' => now(), 'updated_at' => now()]);

        $this->putJson('/position/' . $id, ['name' => 'Baru'])->assertSuccessful();

        $position = DB::table('position')->find($id);
        $this->assertSame('Baru', $position->name);
        $this->assertSame($this->departmentId, (int) $position->department_id);
        $this->assertSame('TUS001', $position->code);
        $this->assertNotNull($position->updated_by);
    }

    public function test_a_department_can_still_be_sent_and_changes_the_code_prefix(): void
    {
        $other = (int) DB::table('department')->insertGetId(['name' => 'Bagian Lain', 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('position')->insertGetId(['name' => 'Pindah', 'code' => 'TUS001', 'department_id' => $this->departmentId, 'created_at' => now(), 'updated_at' => now()]);

        $this->putJson('/position/' . $id, ['name' => 'Pindah', 'department_id' => $other])->assertSuccessful();

        $this->assertStringStartsWith('BL', DB::table('position')->find($id)->code);
    }
}
