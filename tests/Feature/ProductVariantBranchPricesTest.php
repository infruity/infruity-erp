<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Master\Entities\Product;
use Modules\Master\Entities\ProductBranch;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Variants of a product carry their own price rules per branch (like the HTML reference):
 * variant[branch][<position>][id][] / [price][] next to variant[id][] / [name][] / [price][].
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class ProductVariantBranchPricesTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private int $branchA;
    private int $branchB;
    private int $unitId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();

        $this->branchA = $this->makeBranch('A');
        $this->branchB = $this->makeBranch('B');
        $this->unitId = (int) (DB::table('product_units')->value('id')
            ?? DB::table('product_units')->insertGetId(['name' => 'Kg', 'symbol' => 'kg', 'created_at' => now(), 'updated_at' => now()]));

        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    private function payload(array $variant, array $override = []): array
    {
        return array_merge([
            'product_name'    => 'QA Melon ' . Str::random(6),
            'price'           => 0,
            'product_unit_id' => $this->unitId,
            'status'          => 'no-receipt',
            'tipe'            => 'product',
            'variant'         => $variant,
        ], $override);
    }

    private function variantPrices(Product $variant): array
    {
        return ProductBranch::where('product_id', $variant->id)->orderBy('branch_id')->pluck('price', 'branch_id')
            ->map(fn ($price) => (int) $price)->all();
    }

    public function test_new_variants_get_their_own_price_per_branch(): void
    {
        $payload = $this->payload([
            'id'    => ['Grade A', 'Grade B'],
            'name'  => ['Grade A', 'Grade B'],
            'price' => [1000, 3000],
            'branch' => [
                0 => ['id' => [$this->branchA, $this->branchB], 'price' => [1000, 2000]],
                1 => ['id' => [$this->branchA, $this->branchB], 'price' => [3000, 3000]],
            ],
        ]);

        $this->post('/products', $payload)->assertRedirect('products');

        $parent = Product::where('name', $payload['product_name'])->firstOrFail();
        $variants = Product::where('parent_id', $parent->id)->orderBy('id')->get();
        $this->assertCount(2, $variants);
        $this->assertSame([$this->branchA => 1000, $this->branchB => 2000], $this->variantPrices($variants[0]));
        $this->assertSame([$this->branchA => 3000, $this->branchB => 3000], $this->variantPrices($variants[1]));
        $this->assertSame(1000, (int) $variants[0]->price);
    }

    public function test_a_variant_without_rules_keeps_the_old_behaviour_one_price_in_every_branch(): void
    {
        $payload = $this->payload(['id' => ['Grade A'], 'price' => [5000]]);

        $this->post('/products', $payload)->assertRedirect('products');

        $variant = Product::where('parent_id', Product::where('name', $payload['product_name'])->value('id'))->firstOrFail();
        $prices = $this->variantPrices($variant);
        $this->assertSame(5000, $prices[$this->branchA]);
        $this->assertSame(5000, $prices[$this->branchB]);
    }

    public function test_update_renames_and_reprices_an_existing_variant_and_adds_a_new_one(): void
    {
        $create = $this->payload(['id' => ['Grade A'], 'name' => ['Grade A'], 'price' => [1000],
            'branch' => [0 => ['id' => [$this->branchA], 'price' => [1000]]]]);
        $this->post('/products', $create)->assertRedirect('products');
        $parent = Product::where('name', $create['product_name'])->firstOrFail();
        $existing = Product::where('parent_id', $parent->id)->firstOrFail();

        $update = $this->payload([
            'id'    => [$existing->id, 'Grade C'],
            'name'  => ['Grade Super', 'Grade C'],
            'price' => [4000, 700],
            'branch' => [
                0 => ['id' => [$this->branchA, $this->branchB], 'price' => [4000, 4500]],
                1 => ['id' => [$this->branchB], 'price' => [700]],
            ],
        ], ['product_name' => $parent->name, 'price' => 0, 'branch' => ['id' => [$this->branchA], 'price' => [0]]]);
        $this->put('/products/' . $parent->id, $update)->assertRedirect('products');

        $this->assertSame('Grade Super', $existing->fresh()->name);
        $this->assertSame([$this->branchA => 4000, $this->branchB => 4500], $this->variantPrices($existing));
        $new = Product::where('parent_id', $parent->id)->where('name', 'Grade C')->firstOrFail();
        $this->assertSame([$this->branchB => 700], $this->variantPrices($new));
    }

    public function test_the_edit_endpoint_lists_the_branch_prices_of_every_variant(): void
    {
        $create = $this->payload(['id' => ['Grade A'], 'name' => ['Grade A'], 'price' => [1000],
            'branch' => [0 => ['id' => [$this->branchA, $this->branchB], 'price' => [1000, 2000]]]]);
        $this->post('/products', $create);
        $parent = Product::where('name', $create['product_name'])->firstOrFail();

        $json = $this->getJson('/products/' . $parent->id . '/edit?branch_id=' . $this->branchA)->assertOk()->json();

        $this->assertSame('Grade A', $json['variants'][0]['name']);
        $prices = collect($json['variants'][0]['branchPrices'])->mapWithKeys(fn ($row) => [$row['branchId'] => (int) $row['price']])->all();
        $this->assertSame([$this->branchA => 1000, $this->branchB => 2000], $prices);
    }
}
