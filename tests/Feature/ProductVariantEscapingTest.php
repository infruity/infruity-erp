<?php

namespace Tests\Feature;

use App\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductVariantEscapingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'product_variant_escaping_test', 'database.connections.product_variant_escaping_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::purge('product_variant_escaping_test');
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
        Schema::create('products', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('parent_id')->nullable();
            $table->string('name');
            $table->integer('price');
        });
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Variant Operator']);
        DB::table('role')->insert(['id_role' => 2, 'nm_role' => 'Operator']);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('products')->insert([
            ['id' => 10, 'parent_id' => null, 'name' => 'Parent A', 'price' => 0],
            ['id' => 20, 'parent_id' => 10, 'name' => 'Varian A', 'price' => 12500],
            ['id' => 30, 'parent_id' => null, 'name' => 'Parent B', 'price' => 0],
            ['id' => 40, 'parent_id' => 30, 'name' => 'Varian B', 'price' => 15000],
        ]);
        $this->actingAs(User::findOrFail(7));
    }

    protected function tearDown(): void
    {
        DB::purge('product_variant_escaping_test');
        parent::tearDown();
    }

    public static function endpoints(): array
    {
        return ['legacy ajax' => ['/ajax/getVariant'], 'product detail' => ['/products/variants/get']];
    }

    public static function names(): array
    {
        $names = [
            'plain' => 'Varian Manis',
            'quotes and markup' => 'Apel "Fuji" & O\'Reilly <Premium>',
            'unicode' => 'Jus 🍊 – Crème fraîche \'pagi\'',
            'attribute breakout' => '" onmouseover="window.__variantInjected=1',
            'element breakout' => '"><img src=x onerror="window.__variantInjected=1"><button data-name="',
            'script breakout' => '"></button><script>window.__variantInjected=1</script><button data-name="',
            'literal entities' => '&quot; &#34; &amp; &lt;img&gt;',
            'json-like' => '{"name":"Manis"}',
        ];
        $cases = [];
        foreach (self::endpoints() as $endpoint => [$url]) {
            foreach ($names as $label => $name) {
                $cases[$endpoint.' / '.$label] = [$url, $name];
            }
        }
        return $cases;
    }

    #[DataProvider('names')]
    public function test_stored_name_remains_data_without_new_html_attributes_or_elements(string $url, string $name): void
    {
        DB::table('products')->where('id', 20)->update(['name' => $name]);
        $response = $this->getJson($url.'?product_id=10')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 20)
            ->assertJsonPath('data.0.name', e($name));

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadHTML(
            '<!doctype html><html><head><meta charset="UTF-8"></head><body>'.$response->json('data.0.action').'</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        ));
        $xpath = new DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//body//*')->length, 'Only the existing two buttons may be rendered.');
        $edit = $xpath->query('//button[contains(concat(" ", normalize-space(@class), " "), " edit-variant ")]');
        $delete = $xpath->query('//button[contains(concat(" ", normalize-space(@class), " "), " delete-variant ")]');
        $this->assertSame(1, $edit->length);
        $this->assertSame(1, $delete->length);
        $this->assertButton($edit->item(0), ['class', 'type', 'data-id', 'data-name', 'data-price']);
        $this->assertButton($delete->item(0), ['class', 'type', 'data-id']);
        $this->assertSame($name, $edit->item(0)->getAttribute('data-name'), 'DOM decoding must recover the original name exactly.');
        $this->assertSame('12500', $edit->item(0)->getAttribute('data-price'));
        $this->assertSame('20', $delete->item(0)->getAttribute('data-id'));
        $this->assertSame($name, DB::table('products')->where('id', 20)->value('name'));
    }

    private function assertButton(DOMElement $button, array $expectedAttributes): void
    {
        $attributes = [];
        foreach ($button->attributes as $attribute) {
            $attributes[] = $attribute->name;
        }
        sort($attributes);
        sort($expectedAttributes);
        $this->assertSame($expectedAttributes, $attributes, 'No injected event or other extra attributes are allowed.');
        $this->assertSame('button', $button->getAttribute('type'));
        $this->assertSame('20', $button->getAttribute('data-id'));
    }

    #[DataProvider('endpoints')]
    public function test_parent_filter_and_datatable_contract_remain_intact(string $url): void
    {
        foreach ([10 => 20, 30 => 40, 999 => null] as $parent => $variant) {
            $count = $variant === null ? 0 : 1;
            $response = $this->getJson($url.'?'.http_build_query(['product_id' => $parent, 'draw' => 3]))
                ->assertOk()->assertJsonPath('draw', 3)->assertJsonCount($count, 'data')
                ->assertJsonPath('recordsTotal', $count)->assertJsonPath('recordsFiltered', $count);
            if ($variant !== null) {
                $response->assertJsonPath('data.0.id', $variant);
            }
        }
    }

    #[DataProvider('endpoints')]
    public function test_guest_remains_unauthorized(string $url): void
    {
        auth()->logout();
        $this->getJson($url.'?product_id=10')->assertUnauthorized();
    }
}
