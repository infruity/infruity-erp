<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * The customer list shows 50 rows and "Muat Pelanggan Lagi" appends the next 50, like the product list (it used to end
 * in a previous/next pager). The next page comes from the same index route as JSON.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class CustomersLoadMoreTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), $this->superAdminRoleId());
        DB::table('customer')->delete();
    }

    /** The list state is handed to Alpine as JSON.parse('{\u0022total\u0022:...}') inside an HTML attribute. */
    private function scriptState(string $html): string
    {
        return str_replace(' ', '', str_replace('\\u0022', '"', html_entity_decode($html)));
    }

    private function customers(int $count, string $prefix = 'Pelanggan'): void
    {
        for ($i = 1; $i <= $count; $i++) {
            DB::table('customer')->insert(['name' => sprintf('%s %03d', $prefix, $i), 'code' => sprintf('C%05d', $i), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function test_the_first_page_has_the_load_more_button_and_no_pager(): void
    {
        $this->customers(120);

        $html = $this->get('/customers')->assertOk()->getContent();

        $this->assertStringContainsString('Muat Pelanggan Lagi', $html);
        $this->assertStringContainsString('border-dashed', $html);
        $this->assertStringNotContainsString('Selanjutnya', $html);
        $this->assertStringNotContainsString('Navigasi halaman', $html);
        $state = $this->scriptState($html);
        $this->assertStringContainsString('"total":120,"nextPage":2', $state);
    }

    public function test_the_next_pages_come_as_json_until_the_last_one(): void
    {
        $this->customers(120);

        $second = $this->getJson('/customers?page=2')->assertOk()->json();
        $this->assertCount(50, $second['data']);
        $this->assertSame(120, $second['total']);
        $this->assertSame(3, $second['next_page']);
        $this->assertSame(['id', 'name', 'code', 'whatsapp', 'email', 'address', 'gender', 'birth_of_date'], array_keys($second['data'][0]));

        $third = $this->getJson('/customers?page=3')->assertOk()->json();
        $this->assertCount(20, $third['data']);
        $this->assertNull($third['next_page']);

        $ids = array_merge(
            array_column($this->getJson('/customers?page=1')->json('data'), 'id'),
            array_column($second['data'], 'id'),
            array_column($third['data'], 'id')
        );
        $this->assertCount(120, array_unique($ids), 'no row appears twice across the pages');
        $this->assertSame($ids, collect($ids)->sortDesc()->values()->all(), 'newest first, as on the first page');
    }

    public function test_the_search_applies_to_every_page_and_a_short_list_has_no_button_target(): void
    {
        $this->customers(60, 'Budi');
        $this->customers(30, 'Sari');

        $page = $this->getJson('/customers?q=Budi&page=2')->assertOk()->json();
        $this->assertCount(10, $page['data']);
        $this->assertSame(60, $page['total']);
        $this->assertNull($page['next_page']);

        $short = $this->getJson('/customers?q=Sari')->assertOk()->json();
        $this->assertCount(30, $short['data']);
        $this->assertNull($short['next_page']);

        $this->assertStringContainsString('"total":30,"nextPage":null', $this->scriptState($this->get('/customers?q=Sari')->getContent()));
    }
}
