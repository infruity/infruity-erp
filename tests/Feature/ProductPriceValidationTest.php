<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductPriceValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'product_price_test', 'database.connections.product_price_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::purge('product_price_test');
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user')->primary();
            $table->string('nm_user');
            $table->boolean('is_aktif')->default(true);
        });
        Schema::create('role', function (Blueprint $table) {
            $table->integer('id_role')->primary();
            $table->string('nm_role');
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id_ru');
            $table->integer('id_user');
            $table->integer('id_role');
        });
        Schema::create('role_menu', function (Blueprint $table) {
            $table->increments('id_rm');
            $table->integer('id_role');
            $table->string('permission');
        });
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->integer('id_user');
            $table->string('permission');
            $table->boolean('is_allowed');
        });
        Schema::create('branch', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('user_branch', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('branch_id');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price')->default(0);
            $table->timestamps();
        });
        Schema::create('product_branch', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('branch_id');
            $table->integer('price')->default(0);
            $table->timestamps();
        });
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Price Operator']);
        DB::table('role')->insert(['id_role' => 2, 'nm_role' => 'Operator']);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'products.update']);
        DB::table('branch')->insert([['id' => 1, 'name' => 'Allowed'], ['id' => 2, 'name' => 'Foreign']]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('products')->insert(['id' => 10, 'name' => 'Product', 'price' => 10000]);
        DB::table('product_branch')->insert([
            ['product_id' => 10, 'branch_id' => 1, 'price' => 11000],
            ['product_id' => 10, 'branch_id' => 2, 'price' => 12000],
        ]);
        $this->actingAs(User::findOrFail(7));
    }

    protected function tearDown(): void
    {
        DB::purge('product_price_test');
        parent::tearDown();
    }

    public static function invalidPrices(): array
    {
        $cases = [];
        foreach (['12.50', '12.0', '1e3', '1E+3', '-1', '2147483648', '999999999999999999999999999999', '12,500', '12.500', '+12', 'abc', '', null, [], true] as $index => $price) {
            foreach ([0, 1] as $branch) {
                $cases['value '.$index.' branch '.$branch] = [$price, $branch];
            }
        }
        return $cases;
    }

    #[DataProvider('invalidPrices')]
    public function test_invalid_price_is_rejected_without_changing_base_or_any_branch(mixed $price, int $branch): void
    {
        $before = $this->prices();
        $this->putJson('/products/10/update-price', ['price' => $price, 'branch_id' => $branch])
            ->assertUnprocessable()->assertJsonPath('success', false);
        $this->assertSame($before, $this->prices());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_json_decimal_and_scientific_number_tokens_are_rejected_even_when_they_decode_to_whole_numbers(): void
    {
        $before = $this->prices();
        foreach (['12.0', '1e3', '1E+3', '1.25e3'] as $price) {
            foreach ([0, 1] as $branch) {
                $this->call('PUT', '/products/10/update-price', [], [], [], [
                    'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
                ], '{"price":'.$price.',"branch_id":'.$branch.'}')->assertUnprocessable()->assertJsonPath('success', false);
            }
        }
        $this->assertSame($before, $this->prices());
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function validPrices(): array
    {
        return ['zero string' => ['0'], 'integer' => [12500], 'string' => ['12500'], 'signed INT maximum' => ['2147483647']];
    }

    #[DataProvider('validPrices')]
    public function test_valid_whole_rupiah_updates_only_the_requested_price(int|string $price): void
    {
        $this->putJson('/products/10/update-price', ['price' => $price, 'branch_id' => 0])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('products', ['id' => 10, 'price' => (int) $price]);
        $this->assertDatabaseHas('product_branch', ['product_id' => 10, 'branch_id' => 1, 'price' => 11000]);
        $this->putJson('/products/10/update-price', ['price' => $price, 'branch_id' => 1])->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('product_branch', ['product_id' => 10, 'branch_id' => 1, 'price' => (int) $price]);
        $this->assertDatabaseHas('product_branch', ['product_id' => 10, 'branch_id' => 2, 'price' => 12000]);
        $this->assertDatabaseCount('product_branch', 2);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_allowed_branch_price_can_be_created_without_changing_base_or_foreign_branch(): void
    {
        DB::table('product_branch')->where('branch_id', 1)->delete();
        $this->putJson('/products/10/update-price', ['price' => '13500', 'branch_id' => 1])->assertOk();
        $this->assertDatabaseHas('products', ['id' => 10, 'price' => 10000]);
        $this->assertDatabaseHas('product_branch', ['product_id' => 10, 'branch_id' => 1, 'price' => 13500]);
        $this->assertDatabaseHas('product_branch', ['product_id' => 10, 'branch_id' => 2, 'price' => 12000]);
        $this->assertDatabaseCount('product_branch', 2);
    }

    public function test_branch_scope_and_existing_update_permission_are_preserved(): void
    {
        $before = $this->prices();
        $this->putJson('/products/10/update-price', ['price' => 999, 'branch_id' => 2])->assertForbidden();
        $this->putJson('/products/10/update-price', ['price' => 999, 'branch_id' => 999])->assertForbidden();
        foreach (['1e0', '1.5', '-1', 'abc', null, []] as $branch) {
            $this->putJson('/products/10/update-price', ['price' => 999, 'branch_id' => $branch])->assertUnprocessable();
        }
        DB::table('role_menu')->delete();
        foreach ([0, 1] as $branch) {
            $this->putJson('/products/10/update-price', ['price' => 999, 'branch_id' => $branch])->assertRedirect()->assertSessionHas('error');
        }
        auth()->logout();
        $this->putJson('/products/10/update-price', ['price' => 999, 'branch_id' => 0])->assertUnauthorized();
        $this->assertSame($before, $this->prices());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_failed_branch_price_write_rolls_back_without_changing_any_price(): void
    {
        DB::statement("CREATE TRIGGER reject_price BEFORE UPDATE ON product_branch BEGIN SELECT RAISE(ABORT, 'fixture price failure'); END");
        $before = $this->prices();
        $this->putJson('/products/10/update-price', ['price' => 999, 'branch_id' => 1])->assertStatus(500)->assertJsonPath('success', false);
        $this->assertSame($before, $this->prices());
        $this->assertSame(0, DB::transactionLevel());
    }

    private function prices(): array
    {
        return [DB::table('products')->orderBy('id')->get()->toJson(), DB::table('product_branch')->orderBy('id')->get()->toJson()];
    }
}
