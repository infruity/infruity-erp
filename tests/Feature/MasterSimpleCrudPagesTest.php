<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Lokasi, Handling, Departemen: layout baru + dialog `<dialog>`, tanpa modal Bootstrap/Metronic. */
class MasterSimpleCrudPagesTest extends TestCase
{
    private string $testDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDatabase = tempnam(sys_get_temp_dir(), 'master-crud-');
        config(['database.default' => 'master_crud_test', 'database.connections.master_crud_test' => [
            'driver' => 'sqlite', 'database' => $this->testDatabase, 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        Schema::create('role', fn (Blueprint $table) => $table->integer('id_role')->primary());
        Schema::create('role_user', function (Blueprint $table) { $table->increments('id_ru'); $table->integer('id_user'); $table->integer('id_role'); $table->timestamps(); });
        Schema::create('role_menu', function (Blueprint $table) { $table->increments('id_rm'); $table->integer('id_role'); $table->string('permission'); });
        Schema::create('user_permissions', function (Blueprint $table) { $table->increments('id'); $table->integer('id_user'); $table->string('permission', 160); $table->boolean('is_allowed'); $table->timestamps(); $table->unique(['id_user', 'permission']); });
        Schema::create('users', function (Blueprint $table) { $table->integer('id_user')->primary(); $table->string('nm_user'); });
        DB::table('role')->insert([['id_role' => 1]]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 1]);
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Admin']);
        $user = new User(); $user->id_user = 7; $user->nm_user = 'Admin';
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        DB::disconnect('master_crud_test');
        if (isset($this->testDatabase)) unlink($this->testDatabase);
        parent::tearDown();
    }

    public static function pages(): array
    {
        return [
            'location' => ['/location', 'location', 'Tambah Lokasi'],
            'handling' => ['/handling', 'handling', 'Tambah Handling'],
            'department' => ['/department', 'department', 'Tambah Departemen'],
        ];
    }

    /** @dataProvider pages */
    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_page_uses_the_erp_layout_and_a_native_dialog(string $url, string $resource, string $addLabel): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString('<dialog id="'.$resource.'-dialog"', $html);
        $this->assertMatchesRegularExpression('/<dialog id="'.$resource.'-dialog"[^>]*data-erp-create/', $html, 'tombol + di navigasi mobile membuka dialog ini');
        $this->assertStringContainsString('data-erp-open="#'.$resource.'-dialog"', $html);
        $this->assertStringContainsString($addLabel, $html);
        $this->assertStringContainsString('id="'.$resource.'-table"', $html);
        $this->assertStringContainsString('data-erp-collection="'.$resource.'-table"', $html);
        $this->assertStringContainsString('class="erp-ui', $html, 'memakai layouts.erp');

        foreach (['modal fade', 'modal-dialog', 'data-bs-toggle', 'data-bs-target', 'form-control', 'btn btn-', 'data-kt-'] as $legacy) {
            $this->assertStringNotContainsString($legacy, $html, $legacy.' tidak boleh kembali');
        }
    }
}
