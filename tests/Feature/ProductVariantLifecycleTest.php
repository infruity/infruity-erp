<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductVariantLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'product_lifecycle_test', 'database.connections.product_lifecycle_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::purge('product_lifecycle_test');
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
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('abbreviation');
        });
        Schema::create('products_category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price')->default(0);
            $table->integer('parent_id')->nullable();
            $table->integer('is_variant')->nullable();
            $table->integer('category_id')->nullable();
            $table->integer('product_unit')->default(1);
            $table->decimal('stock', 10, 2)->default(0);
            $table->integer('limit')->default(1);
            $table->text('handling')->nullable();
            $table->text('description')->nullable();
            $table->text('sku')->nullable();
            $table->text('barcode')->nullable();
            $table->text('image')->nullable();
            $table->string('status')->default('no-receipt');
            $table->string('tipe')->default('product');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
        foreach (['product_child', 'product_branch'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger($name === 'product_child' ? 'parent_id' : 'branch_id');
                if ($name === 'product_branch') {
                    $table->integer('price')->default(0);
                }
                $table->timestamps();
            });
        }
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Product Operator']);
        DB::table('role')->insert(['id_role' => 2, 'nm_role' => 'Operator']);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        foreach (['store', 'update', 'edit', 'store-variant', 'update-variant', 'destroy-variant'] as $permission) {
            DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'products.'.$permission]);
        }
        DB::table('branch')->insert([['id' => 1, 'name' => 'Allowed'], ['id' => 2, 'name' => 'Foreign']]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('product_units')->insert(['id' => 1, 'name' => 'Kilogram', 'abbreviation' => 'kg']);
        DB::table('products')->insert([
            ['id' => 10, 'name' => 'Parent A', 'price' => 10000],
            ['id' => 20, 'name' => 'Parent B', 'price' => 20000],
        ]);
        $this->actingAs(User::findOrFail(7));
    }

    protected function tearDown(): void
    {
        DB::purge('product_lifecycle_test');
        parent::tearDown();
    }

    protected function productPayload(array $overrides = []): array
    {
        return array_replace([
            'product_name' => 'Parent A', 'price' => '10000', 'product_unit_id' => 1,
            'status' => 'no-receipt', 'description' => '<b>Fresh</b>',
        ], $overrides);
    }

    public function test_new_parent_can_select_an_existing_product_without_reparenting_or_changing_it(): void
    {
        DB::table('products')->where('id', 20)->update(['is_variant' => 1]);
        DB::table('product_child')->insert(['product_id' => 20, 'parent_id' => 10]);
        DB::table('product_branch')->insert(['product_id' => 20, 'branch_id' => 2, 'price' => 22000]);
        $productBefore = DB::table('products')->where('id', 20)->first();
        $linkBefore = DB::table('product_child')->first();
        $this->post('/products', $this->productPayload([
            'product_name' => 'New parent', 'variant' => ['id' => [20], 'price' => [999]],
        ]))->assertRedirect('/products')->assertSessionHas('success');
        $parentId = DB::table('products')->where('name', 'New parent')->value('id');
        $this->assertEquals($productBefore, DB::table('products')->where('id', 20)->first());
        $this->assertEquals($linkBefore, DB::table('product_child')->where('id', $linkBefore->id)->first());
        $this->assertDatabaseHas('product_child', ['product_id' => 20, 'parent_id' => $parentId]);
        $this->assertDatabaseHas('product_branch', ['product_id' => 20, 'branch_id' => 2, 'price' => 22000]);
        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseCount('product_child', 2);
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function formCreationPaths(): array
    {
        return ['store' => ['store'], 'update' => ['update']];
    }

    #[DataProvider('formCreationPaths')]
    public function test_form_created_variants_have_the_same_lifecycle(string $entry): void
    {
        $payload = $this->productPayload([
            'product_name' => $entry === 'store' ? 'New parent' : 'Parent A',
            'variant' => ['id' => ['Small', 'Large'], 'price' => ['12000', '18000']],
        ]);
        ($entry === 'store' ? $this->post('/products', $payload) : $this->put('/products/10', $payload))
            ->assertRedirect('/products')->assertSessionHas('success');
        $parentId = DB::table('products')->where('name', $payload['product_name'])->value('id');
        foreach (['Small' => 12000, 'Large' => 18000] as $name => $price) {
            $id = DB::table('products')->where('name', $name)->value('id');
            $this->assertDatabaseHas('products', ['id' => $id, 'parent_id' => $parentId, 'is_variant' => 1, 'price' => $price, 'description' => 'Fresh']);
            $this->assertDatabaseHas('product_child', ['product_id' => $id, 'parent_id' => $parentId]);
            $this->assertDatabaseHas('product_branch', ['product_id' => $id, 'branch_id' => 1, 'price' => $price]);
            $this->assertDatabaseMissing('product_branch', ['product_id' => $id, 'branch_id' => 2]);
            $this->getJson('/products/child-data?parent_id='.$parentId)->assertOk()->assertJsonCount(2, 'data');
            $this->putJson('/products/variants/'.$id, ['parent_id' => $parentId, 'product_name' => $name.' updated', 'price' => $price])->assertOk();
        }
        foreach (DB::table('product_child')->where('parent_id', $parentId)->pluck('product_id') as $id) {
            $this->deleteJson('/products/variants/'.$id, ['parent_id' => $parentId])->assertOk();
            $this->assertDatabaseMissing('products', ['id' => $id]);
            $this->assertDatabaseMissing('product_branch', ['product_id' => $id]);
        }
        $this->assertDatabaseMissing('product_child', ['parent_id' => $parentId]);
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function invalidFormBasePrices(): array
    {
        return [
            'fractional' => ['12000.5'],
            'decimal notation' => ['12000.0'],
            'scientific notation' => ['1e3'],
            'negative' => ['-1'],
            'signed int overflow' => ['2147483648'],
        ];
    }

    #[DataProvider('invalidFormBasePrices')]
    public function test_regular_product_forms_reject_non_whole_base_prices(string $price): void
    {
        $before = $this->databaseState();
        $this->post('/products', $this->productPayload([
            'product_name' => 'New product '.$price,
            'price' => $price,
        ]))->assertSessionHasErrors('price');
        $this->assertSame($before, $this->databaseState());

        $this->put('/products/10', $this->productPayload(['price' => $price]))
            ->assertSessionHasErrors('price');
        $this->assertSame($before, $this->databaseState());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_regular_product_forms_reject_non_whole_nested_variant_prices(): void
    {
        foreach (['12000.5', '1e3', '-1', '2147483648'] as $price) {
            foreach (['store', 'update'] as $entry) {
                $before = $this->databaseState();
                $payload = $this->productPayload([
                    'product_name' => $entry === 'store' ? 'New variant parent '.$price : 'Parent A',
                    'variant' => ['id' => ['Small'], 'price' => [$price]],
                ]);
                ($entry === 'store' ? $this->post('/products', $payload) : $this->put('/products/10', $payload))
                    ->assertSessionHasErrors('variant.price.0');
                $this->assertSame($before, $this->databaseState());
                $this->assertSame(0, DB::transactionLevel());
            }
        }
    }

    public function test_regular_product_forms_reject_non_whole_branch_prices(): void
    {
        foreach (['12000.5', '12000.0', '1e3', '-1', '2147483648'] as $price) {
            foreach (['store', 'update'] as $entry) {
                $before = $this->databaseState();
                $payload = $this->productPayload([
                    'product_name' => $entry === 'store' ? 'New branch parent '.$price : 'Parent A',
                    'branch' => ['id' => [1], 'price' => [$price]],
                ]);
                ($entry === 'store' ? $this->post('/products', $payload) : $this->put('/products/10', $payload))
                    ->assertSessionHasErrors('branch.price.0');
                $this->assertSame($before, $this->databaseState());
                $this->assertSame(0, DB::transactionLevel());
            }
        }
    }

    public function test_regular_product_form_rejects_foreign_branch_assignments(): void
    {
        $before = $this->databaseState();
        $payload = $this->productPayload([
            'product_name' => 'Foreign branch product',
            'branch' => ['id' => [2], 'price' => [25000]],
        ]);

        $this->post('/products', $payload)->assertForbidden();
        $this->assertSame($before, $this->databaseState());
        $this->assertSame(0, DB::transactionLevel());

        $this->put('/products/10', $this->productPayload([
            'branch' => ['id' => [2], 'price' => [25000]],
        ]))->assertForbidden();
        $this->assertSame($before, $this->databaseState());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_ajax_variant_creation_creates_branch_prices_only_for_accessible_branches(): void
    {
        $expectedBranchIds = [1];
        $this->postJson('/products/variant/store', [
            'parent_id' => 10, 'product_name' => 'Branch scoped child', 'price' => 12000,
        ])->assertOk();

        $id = DB::table('products')->where('name', 'Branch scoped child')->value('id');
        $createdBranchIds = DB::table('product_branch')->where('product_id', $id)->pluck('branch_id')
            ->map(static fn ($branchId) => (int) $branchId)->all();
        sort($createdBranchIds);
        $this->assertSame($expectedBranchIds, $createdBranchIds);
        $this->assertDatabaseMissing('product_branch', ['product_id' => $id, 'branch_id' => 2]);
    }

    public function test_ajax_variant_update_synchronizes_only_accessible_branch_prices(): void
    {
        $this->postJson('/products/variant/store', [
            'parent_id' => 10, 'product_name' => 'Branch priced child', 'price' => 12000,
        ])->assertOk();
        $id = DB::table('products')->where('name', 'Branch priced child')->value('id');
        DB::table('product_branch')->insert(['product_id' => $id, 'branch_id' => 2, 'price' => 17000]);

        $this->putJson('/products/variants/'.$id, [
            'parent_id' => 10, 'product_name' => 'Branch priced child updated', 'price' => 14500,
        ])->assertOk();

        $this->assertDatabaseHas('products', ['id' => $id, 'price' => 14500]);
        $this->assertDatabaseHas('product_branch', ['product_id' => $id, 'branch_id' => 1, 'price' => 14500]);
        $this->assertDatabaseHas('product_branch', ['product_id' => $id, 'branch_id' => 2, 'price' => 17000]);
    }

    public static function invalidVariantPrices(): array
    {
        return [
            'fractional' => ['12000.5'],
            'decimal notation' => ['12000.0'],
            'scientific notation' => ['1e3'],
            'negative' => ['-1'],
            'signed int overflow' => ['2147483648'],
        ];
    }

    #[DataProvider('invalidVariantPrices')]
    public function test_ajax_variant_endpoints_reject_prices_outside_whole_rupiah_range(string $price): void
    {
        $before = $this->databaseState();
        $this->postJson('/products/variant/store', [
            'parent_id' => 10, 'product_name' => 'Rejected child', 'price' => $price,
        ])->assertUnprocessable();
        $this->assertSame($before, $this->databaseState());

        $this->postJson('/products/variant/store', [
            'parent_id' => 10, 'product_name' => 'Existing child', 'price' => 12000,
        ])->assertOk();
        $id = DB::table('products')->where('name', 'Existing child')->value('id');
        $before = $this->databaseState();
        $this->putJson('/products/variants/'.$id, [
            'parent_id' => 10, 'product_name' => 'Rejected update', 'price' => $price,
        ])->assertUnprocessable();
        $this->assertSame($before, $this->databaseState());
    }

    public static function failedCreationPaths(): array
    {
        $cases = [];
        foreach (['ajax', 'store', 'update'] as $entry) {
            foreach (['product_child', 'product_branch'] as $table) {
                $cases[$entry.' '.$table] = [$entry, $table];
            }
        }
        return $cases;
    }

    #[DataProvider('failedCreationPaths')]
    public function test_variant_creation_rolls_back_every_record_when_a_relation_write_fails(string $entry, string $table): void
    {
        DB::statement("CREATE TRIGGER reject_variant BEFORE INSERT ON {$table} BEGIN SELECT RAISE(ABORT, 'fixture relation failure'); END");
        $before = $this->databaseState();
        if ($entry === 'ajax') {
            $this->postJson('/products/variant/store', ['parent_id' => 10, 'product_name' => 'Child', 'price' => 123])->assertStatus(500)->assertJsonPath('success', false);
        } else {
            $payload = $this->productPayload([
                'product_name' => $entry === 'store' ? 'New parent' : 'Changed parent',
                'category_id' => 'New category', 'variant' => ['id' => ['Child'], 'price' => [123]],
            ]);
            ($entry === 'store' ? $this->post('/products', $payload) : $this->put('/products/10', $payload))
                ->assertRedirect()->assertSessionHas('error');
        }
        $this->assertSame($before, $this->databaseState());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_variant_deletion_rolls_back_links_and_branch_prices_when_delete_fails(): void
    {
        $this->postJson('/products/variant/store', ['parent_id' => 10, 'product_name' => 'Child', 'price' => 123])->assertOk();
        $id = DB::table('products')->where('name', 'Child')->value('id');
        DB::statement("CREATE TRIGGER reject_delete BEFORE DELETE ON products BEGIN SELECT RAISE(ABORT, 'fixture delete failure'); END");
        $before = $this->databaseState();
        $this->deleteJson('/products/variants/'.$id, ['parent_id' => 10])->assertStatus(500);
        $this->assertSame($before, $this->databaseState());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_mutations_keep_existing_permissions_and_guest_authentication(): void
    {
        $requests = [
            ['POST', '/products/variant/store', ['parent_id' => 10, 'product_name' => 'Child', 'price' => 123]],
            ['PUT', '/products/variants/20', ['parent_id' => 10, 'product_name' => 'Child', 'price' => 123]],
            ['DELETE', '/products/variants/20', ['parent_id' => 10]],
            ['POST', '/products', $this->productPayload(['product_name' => 'New'])],
            ['PUT', '/products/10', $this->productPayload()],
        ];
        DB::table('role_menu')->delete();
        $before = $this->databaseState();
        foreach ($requests as [$method, $url, $payload]) {
            $this->json($method, $url, $payload)->assertRedirect()->assertSessionHas('error');
        }
        auth()->logout();
        foreach ($requests as [$method, $url, $payload]) {
            $this->json($method, $url, $payload)->assertUnauthorized();
        }
        $this->assertSame($before, $this->databaseState());
    }

    public function test_parent_edit_rejects_foreign_or_duplicate_existing_variants_atomically(): void
    {
        DB::table('product_child')->insert(['product_id' => 20, 'parent_id' => 10]);
        foreach ([[10], [20, 20]] as $ids) {
            $before = $this->databaseState();
            $this->put('/products/10', $this->productPayload([
                'product_name' => 'Changed', 'category_id' => 'New category',
                'variant' => ['id' => $ids, 'price' => array_fill(0, count($ids), 123)],
            ]))->assertRedirect()->assertSessionHas('error');
            $this->assertSame($before, $this->databaseState());
            $this->assertSame(0, DB::transactionLevel());
        }
    }

    public function test_legacy_relation_only_variant_can_be_edited_and_unlinked_without_deleting_the_product(): void
    {
        DB::table('product_child')->insert(['product_id' => 20, 'parent_id' => 10]);
        $this->putJson('/products/variants/20', ['parent_id' => 10, 'product_name' => 'Selected product', 'price' => 24000])->assertOk();
        $this->deleteJson('/products/variants/20', ['parent_id' => 10])->assertOk();
        $this->assertDatabaseHas('products', ['id' => 20, 'name' => 'Selected product', 'parent_id' => null, 'is_variant' => null, 'price' => 24000]);
        $this->assertDatabaseMissing('product_child', ['product_id' => 20, 'parent_id' => 10]);
    }

    public function test_deleting_an_owned_variant_that_is_shared_only_unlinks_the_requested_parent(): void
    {
        $this->postJson('/products/variant/store', ['parent_id' => 10, 'product_name' => 'Shared child', 'price' => 123])->assertOk();
        $id = DB::table('products')->where('name', 'Shared child')->value('id');
        DB::table('product_child')->insert(['product_id' => $id, 'parent_id' => 20]);
        $this->deleteJson('/products/variants/'.$id, ['parent_id' => 10])->assertOk();
        $this->assertDatabaseHas('products', ['id' => $id, 'parent_id' => null, 'is_variant' => 1]);
        $this->assertDatabaseHas('product_child', ['product_id' => $id, 'parent_id' => 20]);
        $this->assertDatabaseMissing('product_child', ['product_id' => $id, 'parent_id' => 10]);
        $this->assertDatabaseHas('product_branch', ['product_id' => $id, 'branch_id' => 1, 'price' => 123]);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_parent_edit_preserves_existing_shared_product_identity_flags_and_other_parent_links(): void
    {
        DB::table('products')->insert(['id' => 30, 'name' => 'Legacy selected product', 'price' => 30000, 'is_variant' => null]);
        DB::table('products')->where('id', 10)->update(['is_variant' => 1]);
        DB::table('product_child')->insert([
            ['product_id' => 30, 'parent_id' => 10], ['product_id' => 30, 'parent_id' => 20],
        ]);
        DB::table('product_branch')->insert([
            ['product_id' => 30, 'branch_id' => 1, 'price' => 31000],
            ['product_id' => 30, 'branch_id' => 2, 'price' => 32000],
        ]);
        $this->put('/products/10', $this->productPayload([
            'selected_branch_id' => 1, 'variant' => ['id' => [30], 'price' => [33000]],
        ]))->assertRedirect('/products')->assertSessionHas('success');
        $this->assertDatabaseHas('products', ['id' => 30, 'parent_id' => null, 'is_variant' => null, 'price' => 30000]);
        $this->assertDatabaseHas('products', ['id' => 10, 'is_variant' => 1]);
        $this->assertDatabaseHas('product_child', ['product_id' => 30, 'parent_id' => 10]);
        $this->assertDatabaseHas('product_child', ['product_id' => 30, 'parent_id' => 20]);
        $this->assertDatabaseCount('product_child', 2);
        $this->assertDatabaseHas('product_branch', ['product_id' => 30, 'branch_id' => 1, 'price' => 33000]);
        $this->assertDatabaseHas('product_branch', ['product_id' => 30, 'branch_id' => 2, 'price' => 32000]);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_wrong_parent_is_not_found_without_any_mutation(): void
    {
        $this->postJson('/products/variant/store', ['parent_id' => 10, 'product_name' => 'Child', 'price' => 123])->assertOk();
        $id = DB::table('products')->where('name', 'Child')->value('id');
        $before = $this->databaseState();
        $this->putJson('/products/variants/'.$id, ['parent_id' => 20, 'product_name' => 'Forged', 'price' => 999])->assertNotFound();
        $this->deleteJson('/products/variants/'.$id, ['parent_id' => 20])->assertNotFound();
        $this->assertSame($before, $this->databaseState());
        $this->assertSame(0, DB::transactionLevel());
    }

    protected function databaseState(): array
    {
        $state = [];
        foreach (['products', 'product_child', 'product_branch', 'products_category'] as $table) {
            $state[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }
        return $state;
    }

    public function test_new_ajax_variant_is_immediately_listable_editable_and_deletable(): void
    {
        $name = 'Apel "Fuji" & <Premium>';
        $this->postJson('/products/variant/store', [
            'parent_id' => 10, 'product_name' => $name, 'price' => '12500',
        ])->assertOk()->assertJsonPath('success', true);
        $id = DB::table('products')->where('name', $name)->value('id');
        $this->assertDatabaseHas('products', ['id' => $id, 'parent_id' => 10, 'is_variant' => 1, 'price' => 12500]);
        $this->assertDatabaseHas('product_child', ['product_id' => $id, 'parent_id' => 10]);
        $this->assertDatabaseHas('product_branch', ['product_id' => $id, 'branch_id' => 1, 'price' => 12500]);
        $this->assertDatabaseMissing('product_branch', ['product_id' => $id, 'branch_id' => 2]);
        foreach (['/ajax/getVariant?product_id=10', '/products/variants/get?product_id=10', '/products/child-data?parent_id=10', '/products/10/edit'] as $url) {
            $response = $this->getJson($url)->assertOk();
            $this->assertSame($id, $response->json(str_contains($url, '/edit') ? 'variants.0.id' : 'data.0.id'));
        }
        $this->putJson('/products/variants/'.$id, ['parent_id' => 10, 'product_name' => 'Updated', 'price' => 14000])
            ->assertOk()->assertJsonPath('message', 'Updated');
        $this->assertDatabaseHas('products', ['id' => $id, 'name' => 'Updated', 'price' => 14000]);
        $this->deleteJson('/products/variants/'.$id, ['parent_id' => 10])->assertOk()->assertJsonPath('message', 'Deleted');
        $this->assertDatabaseMissing('products', ['id' => $id]);
        $this->assertDatabaseMissing('product_child', ['product_id' => $id]);
        $this->assertDatabaseMissing('product_branch', ['product_id' => $id]);
        $this->assertSame(0, DB::transactionLevel());
    }
}
