<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Leftover test pages, the receipt logo upload, cross-branch order writes, legacy endpoints and unescaped table cells.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class SecurityHardeningTest extends TestCase
{
    use QaFixtures;

    private const XSS = '<img src=x onerror=alert(1)>';

    private int $mine;
    private int $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // Some endpoints disconnect the database, so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();

        $this->mine = $this->makeBranch('MILIK');
        $this->other = $this->makeBranch('LAIN');
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function actAsAdmin(): void
    {
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    private function actAsCrew(array $permissions): void
    {
        $role = $this->makeRole('Crew');
        foreach ($permissions as $permission) {
            DB::table('role_menu')->insert(['id_role' => $role, 'permission' => $permission]);
        }
        $this->actAsRole($this->makeUser($role, [], [$this->mine]), $role);
    }

    public function test_leftover_public_test_pages_are_gone(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map->uri()->all();

        foreach (['qrcode', 'srrd'] as $uri) {
            $this->assertNotContains($uri, $uris);
            $this->get('/' . $uri)->assertNotFound();
        }
    }

    public function test_production_endpoints_nobody_calls_are_gone(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map->uri()->all();

        $this->assertNotContains('production/delete-detail/{id}', $uris);
        $this->assertNotContains('production/update-product-id/{id}', $uris);
    }

    /** A real file on disk: Laravel's fake files guess the type from the name, not from the content. */
    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'logo');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    public function test_receipt_logo_accepts_only_small_png_or_jpg(): void
    {
        Storage::fake('public');
        $this->actAsAdmin();

        $rejected = [
            $this->realUpload('logo.html', '<html><script>alert(1)</script></html>'),
            $this->realUpload('logo.png', '<html><script>alert(1)</script></html>'),
            $this->realUpload('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
            UploadedFile::fake()->image('big.png')->size(3000),
        ];

        foreach ($rejected as $file) {
            session()->forget('errors');
            $this->post('/setting-nota', ['branch_id' => $this->mine, 'avatar' => $file])->assertSessionHasErrors('avatar');
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseMissing('setting_nota', ['branch_id' => $this->mine]);

        session()->forget('errors');
        $this->post('/setting-nota', ['branch_id' => $this->mine, 'avatar' => UploadedFile::fake()->image('logo.png')])
            ->assertSessionHasNoErrors();
        $this->assertCount(1, Storage::disk('public')->allFiles('logo'));

        $id = DB::table('setting_nota')->where('branch_id', $this->mine)->value('id');
        session()->forget('errors');
        $this->put('/setting-nota/' . $id, ['branch_id' => $this->mine, 'avatar' => $rejected[0]])->assertSessionHasErrors('avatar');
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    private function saveOrder(string $number, int $branch, string $note = 'pesanan baru', array $extra = [])
    {
        return $this->postJson('/order-book/save-transaction', $extra + [
            'branch_id' => $branch, 'invoice_number' => $number, 'date' => date('Y-m-d'),
            'status' => 'posting', 'note' => $note,
        ]);
    }

    private function seedOrder(int $branch): string
    {
        $number = 'ORDQA' . Str::upper(Str::random(6));
        DB::table('order_book')->insert([
            'branch_id' => $branch, 'invoice_number' => $number, 'date' => date('Y-m-d'),
            'status' => 'posting', 'note' => 'pesanan asli', 'created_by' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $number;
    }

    public function test_order_cannot_be_written_into_or_taken_from_another_branch(): void
    {
        $foreign = $this->seedOrder($this->other);
        $this->actAsCrew(['order-book.save-transaction']);

        $this->saveOrder('ORDQA-BARU', $this->other)->assertStatus(403);
        // Re-using a foreign order number with my own branch would delete the other branch's order.
        $this->saveOrder($foreign, $this->mine)->assertStatus(403);

        $this->assertDatabaseHas('order_book', ['invoice_number' => $foreign, 'branch_id' => $this->other, 'note' => 'pesanan asli']);
        $this->assertDatabaseMissing('order_book', ['invoice_number' => 'ORDQA-BARU']);
    }

    public function test_order_in_my_own_branch_can_still_be_created_and_rewritten(): void
    {
        $own = $this->seedOrder($this->mine);
        $this->actAsCrew(['order-book.save-transaction']);

        // The edit form sends the id of the order being edited.
        $this->saveOrder($own, $this->mine, 'diubah', ['order_book_id' => DB::table('order_book')->where('invoice_number', $own)->value('id')])->assertOk();

        $this->assertSame(['diubah'], DB::table('order_book')->where('invoice_number', $own)->pluck('note')->all());
    }

    public function test_finishing_a_parcel_twice_creates_only_one_product(): void
    {
        $this->actAsAdmin();
        $parcel = DB::table('production_parcel')->insertGetId([
            'production_number' => 'PRCQA' . random_int(100, 999), 'production_date' => date('Y-m-d'),
            'status' => 'posting', 'budget' => 50000, 'quantity' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $filling = $this->makeProduct('Isi');
        $worker = DB::table('staff')->insertGetId(['name' => 'QA Parcel', 'date_in' => date('Y-m-d'), 'branch_id' => $this->mine,
            'status' => 'aktif', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('production_parcel')->where('id', $parcel)->update(['staff_id' => $worker]);
        $this->giveStock($filling, $this->mine, 5);
        DB::table('production_parcel_detail')->insert([
            'production_id' => $parcel, 'product_id' => $filling, 'quantity' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $products = DB::table('products')->count();

        $this->postJson("/parcel/set-selesai/{$parcel}")->assertCreated();
        $this->postJson("/parcel/set-selesai/{$parcel}")->assertStatus(409);

        $this->assertSame($products + 1, DB::table('products')->count());
    }

    public function test_retired_purchase_receive_step_writes_nothing(): void
    {
        $this->actAsAdmin();

        $this->postJson('/wholesale/update-receive-product/999999')->assertStatus(410);
    }

    private function assertEscaped(string $url): void
    {
        $response = $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $cells = json_encode($response->json('data'), JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('onerror=alert(1)', $cells, "fixture row missing from {$url}");
        $this->assertStringNotContainsString('<img src=x', $cells, "unescaped cell in {$url}");
    }

    public function test_supplier_table_escapes_typed_text(): void
    {
        $this->actAsAdmin();
        DB::table('supplier')->insert([
            'name' => self::XSS, 'pic_name' => self::XSS, 'pic_whatsapp' => self::XSS,
            'address' => self::XSS, 'email' => self::XSS, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertEscaped('/supplier/data');
    }

    public function test_branch_and_customer_names_are_escaped_in_transaction_tables(): void
    {
        $this->actAsAdmin();
        DB::table('branch')->where('id', $this->mine)->update(['name' => self::XSS]);

        DB::table('transfer')->insert([
            'uuid' => (string) Str::uuid(), 'invoice_number' => 'TRFQA' . random_int(100, 999),
            'branch_id' => $this->mine, 'branch_destination_id' => $this->other, 'date' => date('Y-m-d'),
            'status' => 'pending', 'created_by' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertEscaped('/transfer/data?url=transfer-pengirim');
    }
}
