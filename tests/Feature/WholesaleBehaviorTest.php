<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Pre-deploy QA, phase 1 — wholesale (pengadaan / penerimaan barang): posting a purchase is what
 * adds stock and sets the product cost. Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class WholesaleBehaviorTest extends TestCase
{
    use QaFixtures;

    private int $branch;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // saveTransaction() calls DB::disconnect(), so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();

        $this->branch = $this->makeBranch('PENGADAAN');
        $this->productId = $this->makeProduct('Mangga', 25000);

        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function stock(): float
    {
        return InventorySnapshot::stockAvailable([$this->productId], $this->branch)[$this->productId];
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'branch_id'      => $this->branch,
            'date'           => date('Y-m-d'),
            'invoice_number' => null,
            'items'          => [['id' => $this->productId, 'price' => 10000, 'qty' => 10, 'total_input' => 100000]],
            'subtotal'       => 100000,
            'total'          => 100000,
            'status'         => 'posting',
        ], $override);
    }

    private function save(array $override = [])
    {
        return $this->postJson('/wholesale/save-transaction', $this->payload($override));
    }

    // ---- stock and cost ----------------------------------------------------------------------

    public function test_posting_adds_the_received_quantity_to_the_branch_stock(): void
    {
        $this->save()->assertOk()->assertJson(['success' => true, 'status' => 'posting']);

        $this->assertSame(10.0, $this->stock());
        $this->assertDatabaseHas('wholesale_product', ['product_id' => $this->productId, 'quantity' => 10, 'price' => 10000]);
    }

    public function test_draft_and_temp_do_not_add_stock(): void
    {
        $this->save(['status' => 'draft'])->assertOk();
        $this->assertSame(0.0, $this->stock(), 'draft is not received yet');

        $this->save(['status' => 'temp'])->assertOk();
        $this->assertSame(0.0, $this->stock(), 'temp autosave is not received');
    }

    public function test_stock_goes_to_the_selected_branch_only(): void
    {
        $other = $this->makeBranch('LAIN');

        $this->save()->assertOk();

        $this->assertSame(10.0, $this->stock());
        $this->assertSame(0.0, InventorySnapshot::stockAvailable([$this->productId], $other)[$this->productId]);
    }

    public function test_two_postings_accumulate_stock(): void
    {
        $this->save()->assertOk();
        $this->save(['items' => [['id' => $this->productId, 'price' => 20000, 'qty' => 5, 'total_input' => 100000]]])->assertOk();

        $this->assertSame(15.0, $this->stock());
    }

    public function test_posting_moves_the_product_cost_to_the_moving_average(): void
    {
        $this->save()->assertOk();   // 10 @ 10.000
        $this->assertEquals(10000, DB::table('products')->where('id', $this->productId)->value('hpp'));

        $this->save(['items' => [['id' => $this->productId, 'price' => 20000, 'qty' => 10, 'total_input' => 200000]], 'subtotal' => 200000, 'total' => 200000])->assertOk();

        $this->assertEquals(15000, DB::table('products')->where('id', $this->productId)->value('hpp'), '(10x10.000 + 10x20.000) / 20');
    }

    public function test_selling_price_for_the_branch_is_saved_when_given(): void
    {
        $this->save(['items' => [['id' => $this->productId, 'price' => 10000, 'qty' => 1, 'total_input' => 10000, 'sell' => 18000]]])->assertOk();

        $this->assertDatabaseHas('product_branch', ['product_id' => $this->productId, 'branch_id' => $this->branch, 'price' => 18000]);
    }

    private function sellFromStock(float $qty): void
    {
        $posId = DB::table('pos_transaction')->insertGetId([
            'uuid' => (string) Str::uuid(), 'invoice_number' => 'INVWS' . strtoupper(Str::random(6)), 'date' => date('Y-m-d'),
            'total' => $qty * 25000, 'discount' => 0, 'paid' => $qty * 25000, 'status' => 'paid', 'branch_id' => $this->branch,
            'created_by' => 1, 'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute(),
        ]);
        DB::table('pos_transaction_detail')->insert([
            'pos_id' => $posId, 'product_id' => $this->productId, 'quantity' => $qty, 'price' => 25000, 'discount' => 0,
            'subtotal' => $qty * 25000, 'exp' => 0, 'exp_value' => 0, 'type' => 'product', 'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute(),
        ]);
    }

    // ---- never-negative stock when a posted purchase changes -------------------------------------

    public function test_editing_a_posted_purchase_is_allowed_while_stock_stays_non_negative(): void
    {
        $invoice = $this->save()->assertOk()->json('invoice_number');   // +10
        $this->sellFromStock(8);                                         // 2 left

        $this->save(['invoice_number' => $invoice, 'items' => [['id' => $this->productId, 'price' => 10000, 'qty' => 9, 'total_input' => 90000]], 'subtotal' => 90000, 'total' => 90000])
            ->assertOk();

        $this->assertSame(1.0, $this->stock(), '9 received - 8 sold');
    }

    public function test_editing_a_posted_purchase_below_what_was_sold_is_refused_and_leaves_it_unchanged(): void
    {
        $invoice = $this->save()->assertOk()->json('invoice_number');
        $this->sellFromStock(8);

        $response = $this->save(['invoice_number' => $invoice, 'items' => [['id' => $this->productId, 'price' => 10000, 'qty' => 3, 'total_input' => 30000]], 'subtotal' => 30000, 'total' => 30000]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertEquals(10, DB::table('wholesale_product')->join('wholesale', 'wholesale.id', '=', 'wholesale_product.wholesale_id')->where('wholesale.order_number', $invoice)->value('wholesale_product.quantity'), 'rolled back');
        $this->assertSame(2.0, $this->stock());
    }

    public function test_turning_a_sold_from_posted_purchase_back_into_a_draft_is_refused(): void
    {
        $invoice = $this->save()->assertOk()->json('invoice_number');
        $this->sellFromStock(8);

        $this->save(['invoice_number' => $invoice, 'status' => 'draft'])->assertStatus(422);

        $this->assertDatabaseHas('wholesale', ['order_number' => $invoice, 'status' => 'posting']);
        $this->assertSame(2.0, $this->stock());
    }

    public function test_deleting_a_posted_purchase_that_was_already_sold_from_is_refused(): void
    {
        $wholesaleId = $this->save()->assertOk()->json('transaksi_id');
        $this->sellFromStock(8);

        $this->deleteJson("/wholesale/{$wholesaleId}")->assertStatus(422)->assertJson(['success' => false]);

        $this->assertDatabaseHas('wholesale', ['id' => $wholesaleId]);
        $this->assertSame(2.0, $this->stock());
    }

    public function test_deleting_an_unsold_posted_purchase_is_allowed(): void
    {
        $wholesaleId = $this->save()->assertOk()->json('transaksi_id');

        $this->deleteJson("/wholesale/{$wholesaleId}")->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('wholesale', ['id' => $wholesaleId]);
        $this->assertSame(0.0, $this->stock());
    }

    // ---- retired legacy "receive" endpoints (finding W3) ---------------------------------------

    public function test_legacy_receive_endpoints_can_no_longer_wipe_a_posted_purchase_from_stock(): void
    {
        $wholesaleId = $this->save()->assertOk()->json('transaksi_id');
        $this->assertSame(10.0, $this->stock());

        // These used to write status 'complete' (not in the enum -> stored as ''), erasing the stock-in.
        $this->postJson("/wholesale/set-selesai/{$wholesaleId}")->assertStatus(410);
        $this->postJson("/wholesale/receive/{$wholesaleId}")->assertStatus(410);

        $this->assertSame('posting', DB::table('wholesale')->where('id', $wholesaleId)->value('status'));
        $this->assertSame(10.0, $this->stock());
    }

    public function test_legacy_save_receive_form_is_refused_and_changes_nothing(): void
    {
        $wholesaleId = $this->save()->assertOk()->json('transaksi_id');
        $detailId = DB::table('wholesale_product')->where('wholesale_id', $wholesaleId)->value('id');

        $this->post('/wholesale/save-receive', [
            'wholesale_id' => $wholesaleId,
            'products'     => [$detailId => ['quantity' => 1, 'price' => 1]],
        ])->assertRedirect('wholesale')->assertSessionHas('error');

        $this->assertSame('posting', DB::table('wholesale')->where('id', $wholesaleId)->value('status'));
        $this->assertEquals(10, DB::table('wholesale_product')->where('id', $detailId)->value('quantity'));
        $this->assertSame(10.0, $this->stock());
    }

    // ---- destructive reset endpoint (finding W4) ----------------------------------------------

    public function test_transaction_reset_is_refused_outside_local_and_testing_even_for_super_admin(): void
    {
        $this->save()->assertOk();
        $before = DB::table('wholesale')->count();
        $this->assertGreaterThan(0, $before);

        // CSRF is only skipped while the app thinks it is under test; skip it explicitly so the guard is what answers.
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        foreach (['production', 'staging'] as $environment) {
            $this->app['env'] = $environment;

            $this->postJson('/wholesale/reset')->assertForbidden()->assertJson(['success' => false]);
        }

        $this->assertSame($before, DB::table('wholesale')->count(), 'no transaction table may be truncated');
        $this->assertSame(10.0, $this->stock());
    }

    // ---- the real form flow: opening the form creates the draft row first ------------------------

    private function adminDraftInvoice(): ?string
    {
        return DB::table('wholesale')->where('status', 'temp')->orderByDesc('id')->value('order_number');
    }

    public function test_form_flow_opening_the_form_then_posting_with_the_drafts_invoice_number_works(): void
    {
        // GET /wholesale/create saves a temp draft BEFORE any branch is chosen (branch_id column is NOT NULL,
        // non-strict MySQL stores 0). The form then posts with that draft's invoice number.
        $this->get('/wholesale/create')->assertOk();
        $invoice = $this->adminDraftInvoice();
        $this->assertNotNull($invoice);
        $this->assertNull(DB::table('wholesale')->where('order_number', $invoice)->value('branch_id'), 'a draft without a branch is NULL, not 0');

        $this->save(['invoice_number' => $invoice])->assertOk()->assertJson(['success' => true, 'status' => 'posting']);

        $this->assertSame(10.0, $this->stock());
        $this->assertDatabaseHas('wholesale', ['order_number' => $invoice, 'branch_id' => $this->branch, 'status' => 'posting']);
    }

    public function test_unused_wholesale_columns_are_nullable_in_the_schema(): void
    {
        foreach ([['wholesale', 'branch_id'], ['wholesale', 'supplier_id'], ['wholesale', 'uuid'], ['wholesale_product', 'category_id']] as [$table, $column]) {
            $nullable = DB::selectOne('SELECT is_nullable AS n FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $column])->n;
            $this->assertSame('YES', $nullable, "{$table}.{$column}");
        }
    }

    public function test_a_posted_purchase_stores_no_fake_zero_for_the_columns_the_app_does_not_fill(): void
    {
        $id = $this->save()->assertOk()->json('transaksi_id');

        $row = DB::table('wholesale')->where('id', $id)->first();
        $this->assertNull($row->supplier_id);
        $this->assertNull($row->uuid);
        $this->assertNull(DB::table('wholesale_product')->where('wholesale_id', $id)->value('category_id'));
    }

    public function test_form_flow_autosave_with_the_drafts_invoice_number_works(): void
    {
        $this->get('/wholesale/create')->assertOk();
        $invoice = $this->adminDraftInvoice();

        $this->save(['invoice_number' => $invoice, 'status' => 'temp'])->assertOk()->assertJson(['success' => true, 'status' => 'temp']);

        $this->assertDatabaseHas('wholesale', ['order_number' => $invoice, 'branch_id' => $this->branch, 'status' => 'temp']);
    }

    public function test_form_flow_reopening_the_form_reuses_the_same_draft(): void
    {
        $this->get('/wholesale/create')->assertOk();
        $first = $this->adminDraftInvoice();
        $this->get('/wholesale/create')->assertOk();
        $this->get('/wholesale/create')->assertOk();

        $this->assertSame($first, $this->adminDraftInvoice());
        $this->assertSame(1, DB::table('wholesale')->where('status', 'temp')->where('order_number', $first)->count());
        $this->assertSame(1, DB::table('wholesale')->where('status', 'temp')->where('created_by', DB::table('wholesale')->where('order_number', $first)->value('created_by'))->count(), 'no pile of orphan drafts');
    }

    public function test_form_flow_someone_elses_branchless_draft_cannot_be_taken_over(): void
    {
        $this->get('/wholesale/create')->assertOk();
        $invoice = $this->adminDraftInvoice();

        $other = $this->makeUser($this->makeRole('Gudang'), [], [$this->branch]);
        DB::table('user_permissions')->insert(['id_user' => $other->id_user, 'permission' => 'wholesale.save-transaction', 'is_allowed' => 1]);
        $this->actAsRole($other, (int) DB::table('role_user')->where('id_user', $other->id_user)->value('id_role'));

        $this->save(['invoice_number' => $invoice])->assertForbidden();
        $this->assertDatabaseHas('wholesale', ['order_number' => $invoice, 'status' => 'temp']);
    }

    // ---- input guards ------------------------------------------------------------------------
    // Rule (decided with the HPP work): stock never goes negative, and purchase lines must be real and positive.
    // The one test tagged #[Group('known-gap')] is still open: the header total is not checked against the items.
    // Run it with: php vendor/bin/phpunit -c phpunit.mysql.xml --group known-gap

    public function test_extra_cart_fields_survive_validation_and_are_saved(): void
    {
        $this->save(['items' => [['id' => $this->productId, 'price' => 10000, 'qty' => 2, 'total_input' => 18000, 'discount' => 2000, 'sell' => 21000]], 'subtotal' => 18000, 'total' => 18000])->assertOk();

        $this->assertDatabaseHas('wholesale_product', ['product_id' => $this->productId, 'quantity' => 2, 'total_price' => 18000]);
        $this->assertDatabaseHas('product_branch', ['product_id' => $this->productId, 'branch_id' => $this->branch, 'price' => 21000]);
    }

    public function test_autosave_temp_stays_lenient_while_the_user_is_typing(): void
    {
        // A half-typed row (empty qty, no price yet) must not be rejected by the autosave.
        $this->save(['status' => 'temp', 'items' => [['id' => $this->productId, 'price' => '', 'qty' => '', 'total_input' => '']]])
            ->assertOk()->assertJson(['success' => true, 'status' => 'temp']);
    }

    public function test_a_non_draft_save_needs_at_least_one_item(): void
    {
        $this->save(['items' => []])->assertStatus(422)->assertJsonValidationErrors('items');
    }

    public function test_negative_quantity_cannot_be_posted_to_take_stock_away(): void
    {
        $this->save()->assertOk();

        $response = $this->save(['items' => [['id' => $this->productId, 'price' => 10000, 'qty' => -8, 'total_input' => -80000]]]);

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode(), 'a purchase with negative quantity must be refused');
        $this->assertSame(10.0, $this->stock());
    }

    public function test_negative_price_or_zero_quantity_is_refused(): void
    {
        foreach ([
            ['price' => -5000, 'qty' => 1, 'total_input' => -5000],
            ['price' => 10000, 'qty' => 0, 'total_input' => 0],
        ] as $line) {
            $response = $this->save(['items' => [['id' => $this->productId] + $line]]);
            $this->assertGreaterThanOrEqual(400, $response->getStatusCode(), json_encode($line) . ' must be refused');
        }
        $this->assertSame(0.0, $this->stock());
    }

    public function test_unknown_product_id_is_refused_without_a_half_saved_purchase(): void
    {
        $invoice = 'PO-QA-' . strtoupper(Str::random(6));

        $response = $this->save(['invoice_number' => $invoice, 'items' => [['id' => 999999999, 'price' => 1000, 'qty' => 1, 'total_input' => 1000]]]);

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertDatabaseMissing('wholesale', ['order_number' => $invoice]);
    }

    #[Group('known-gap')]
    public function test_total_must_match_the_items(): void
    {
        $this->save(['total' => 1, 'subtotal' => 1])->assertStatus(422);
        $this->assertSame(0.0, $this->stock());
    }

    // ---- access ------------------------------------------------------------------------------

    public function test_user_cannot_post_into_a_branch_they_are_not_assigned_to(): void
    {
        $roleId = $this->makeRole('Gudang');
        $gudang = $this->makeUser($roleId, [], [$this->makeBranch('LAIN')]);
        DB::table('user_permissions')->insert(['id_user' => $gudang->id_user, 'permission' => 'wholesale.save-transaction', 'is_allowed' => 1]);
        $this->actAsRole($gudang, $roleId);

        $this->save()->assertForbidden();
        $this->assertSame(0.0, $this->stock());
    }

    public function test_user_cannot_overwrite_another_branchs_purchase_by_invoice_number(): void
    {
        $created = $this->save(['status' => 'draft'])->assertOk();
        $invoice = $created->json('invoice_number');

        $roleId = $this->makeRole('Gudang');
        $mine = $this->makeBranch('MILIK');
        $gudang = $this->makeUser($roleId, [], [$mine]);
        DB::table('user_permissions')->insert(['id_user' => $gudang->id_user, 'permission' => 'wholesale.save-transaction', 'is_allowed' => 1]);
        $this->actAsRole($gudang, $roleId);

        $this->save(['branch_id' => $mine, 'invoice_number' => $invoice])->assertForbidden();
        $this->assertDatabaseHas('wholesale', ['order_number' => $invoice, 'branch_id' => $this->branch, 'status' => 'draft']);
    }

    public function test_user_without_permission_cannot_save(): void
    {
        $roleId = $this->makeRole('Sales');
        $sales = $this->makeUser($roleId, [], [$this->branch]);
        $this->actAsRole($sales, $roleId);

        $this->save()->assertRedirect();
        $this->assertSame(0.0, $this->stock());
    }

    // ---- after posting -----------------------------------------------------------------------

    public function test_a_posted_purchase_that_was_already_sold_from_cannot_be_reduced_below_what_was_sold(): void
    {
        $created = $this->save()->assertOk();          // +10
        $invoice = $created->json('invoice_number');

        // 8 of the 10 are sold (paid sale).
        $posId = DB::table('pos_transaction')->insertGetId([
            'uuid' => (string) Str::uuid(), 'invoice_number' => 'INVWS' . strtoupper(Str::random(6)), 'date' => date('Y-m-d'),
            'total' => 200000, 'discount' => 0, 'paid' => 200000, 'status' => 'paid', 'branch_id' => $this->branch,
            'created_by' => 1, 'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute(),
        ]);
        DB::table('pos_transaction_detail')->insert([
            'pos_id' => $posId, 'product_id' => $this->productId, 'quantity' => 8, 'price' => 25000, 'discount' => 0,
            'subtotal' => 200000, 'exp' => 0, 'exp_value' => 0, 'type' => 'product', 'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute(),
        ]);
        $this->assertSame(2.0, $this->stock());

        // Edit the posted purchase down to 3 pieces: stock would become 3 - 8 = -5.
        $this->save(['invoice_number' => $invoice, 'items' => [['id' => $this->productId, 'price' => 10000, 'qty' => 3, 'total_input' => 30000]], 'subtotal' => 30000, 'total' => 30000]);

        $this->assertGreaterThanOrEqual(0.0, $this->stock(), 'editing a posted purchase must never push stock negative');
    }
}
