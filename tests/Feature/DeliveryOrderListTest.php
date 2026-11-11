<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Modules\Pos\Http\Controllers\DeliveryOrderController;
use Tests\TestCase;

class DeliveryOrderListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
        $this->seedOrders();
    }

    public function test_delivery_and_payment_filters_can_be_combined(): void
    {
        $result = $this->requestRows([
            'delivery_status_filter' => 'unreceived',
            'payment_status_filter' => 'unpaid',
        ]);

        $this->assertCount(1, $result['data']);
        $this->assertStringContainsString(route('pos.show', 1), $result['data'][0]['name']);
    }

    public function test_unpaid_filter_includes_debt_for_received_orders(): void
    {
        $result = $this->requestRows([
            'delivery_status_filter' => 'received',
            'payment_status_filter' => 'unpaid',
        ]);

        $this->assertCount(1, $result['data']);
        $this->assertStringContainsString(route('pos.show', 3), $result['data'][0]['name']);
    }

    public function test_last_four_phone_search_returns_mobile_card_metadata(): void
    {
        $result = $this->requestRows([], '8219');

        $this->assertCount(1, $result['data']);
        $this->assertStringContainsString('data-delivery-order-phone="081234568219"', $result['data'][0]['name']);
        $this->assertStringContainsString('data-delivery-order-total="52000"', $result['data'][0]['courier']);
        $this->assertStringContainsString('data-delivery-order-delivery-status="unreceived"', $result['data'][0]['status']);
        $this->assertStringContainsString('data-delivery-order-payment-status="unpaid"', $result['data'][0]['status']);
    }

    public function test_delivery_rows_expose_escaped_detail_modal_metadata(): void
    {
        $result = $this->requestRows([], '8219');
        $name = $result['data'][0]['name'];
        $courier = $result['data'][0]['courier'];

        $this->assertStringContainsString('data-delivery-order-id="1"', $name);
        $this->assertStringContainsString('data-delivery-order-address="Alamat 1"', $name);
        $this->assertStringContainsString('data-delivery-order-shipping-fee="10000"', $courier);
        $this->assertStringContainsString('data-delivery-order-courier-type="Internal"', $courier);
    }

    public function test_eye_action_opens_detail_modal_and_keeps_pos_route_fallback(): void
    {
        $result = $this->requestRows([], '8219');
        $action = $result['data'][0]['action'];

        $this->assertStringContainsString('data-delivery-order-detail-trigger', $action);
        $this->assertStringContainsString('href="' . route('pos.show', 1) . '"', $action);
    }

    public function test_full_phone_search_accepts_local_and_international_prefixes(): void
    {
        $localNumber = $this->requestRows([], '081234568219');
        $internationalNumber = $this->requestRows([], '6281234568219');

        $this->assertCount(1, $localNumber['data']);
        $this->assertCount(1, $internationalNumber['data']);
    }

    public function test_customer_and_detail_metadata_escapes_quotes_markup_and_unicode(): void
    {
        $name = 'Dewi <img src=x onerror=alert(1)> "Cafe & Tea" 🌱';
        $phone = '+62 (812) "8219" &';
        $address = 'Blok "A" <img src=x onerror=alert(2)> & Garden';
        DB::table('customer')->where('id', 1)->update([
            'name' => $name,
            'whatsapp' => $phone,
        ]);
        DB::table('pos_transaction')->where('id', 1)->update(['ongkir_address' => $address]);

        $result = $this->requestRows([], 'Cafe');
        $html = $result['data'][0]['name'];

        $this->assertCount(1, $result['data']);
        $this->assertStringContainsString('data-delivery-order-phone="' . e($phone) . '"', $html);
        $this->assertStringContainsString('data-delivery-order-address="' . e($address) . '"', $html);
        $this->assertStringContainsString(e($name), $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_search_finds_internal_courier_name(): void
    {
        $result = $this->requestRows([], 'Muhammad Chamim');

        $this->assertCount(3, $result['data']);
    }

    public function test_index_renders_reference_filters_and_pos_navigation(): void
    {
        $this->app->instance('request', Request::create('/delivery-order', 'GET'));
        $html = view('pos::delivery-order.index', [
            'branches' => collect([(object) ['id' => 1, 'name' => 'Toko GKB']]),
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('id="delivery-order-page"', $html);
        $this->assertStringContainsString('Cari nama pelanggan, kurir, atau 4 digit HP', $html);
        $this->assertStringContainsString('placeholder="Cari..."', $html);
        $this->assertStringContainsString('data-delivery-order-filter-trigger', $html);
        $this->assertStringContainsString('erp-mobile-search-shell', $html);
        $this->assertStringContainsString('class="erp-mobile-search__input"', $html);
        $this->assertStringContainsString('data-delivery-order-mobile-search-clear', $html);
        $this->assertStringContainsString('class="erp-mobile-search__filter"', $html);
        // The phone filter is the shared sheet: status penerimaan is its status group, pembayaran an extra group.
        $this->assertStringContainsString('id="delivery-order-filter-sheet"', $html);
        $this->assertStringContainsString('role="dialog" aria-modal="true"', $html);
        $this->assertStringContainsString('bg-black/70', $html);
        $this->assertStringContainsString('placeholder="Cari cabang..."', $html);
        $this->assertStringContainsString('Terapkan', $html);
        $this->assertStringContainsString('Belum Lunas', $html);
        $this->assertStringContainsString('Minggu Ini', $html);
        $this->assertStringContainsString('Hari Ini', $html);
        // A single-branch user has no "Semua Cabang" row and keeps their branch as the default.
        $this->assertStringNotContainsString('data-delivery-order-mobile-branch="all"', $html);
        $this->assertStringContainsString('\\u0022showBranchAll\\u0022:false', $html);
        $this->assertStringContainsString('\\u0022defaultBranch\\u0022:\\u00221\\u0022', $html);
        $this->assertStringContainsString("new CustomEvent('erp-filter-open', { detail: { id: 'delivery-order'", $html);
        $this->assertStringContainsString("window.addEventListener('erp-filter-apply'", $html);
        $this->assertStringContainsString('searchDebounce.cancel();', $html);
        // The page's own sheet, calendar and viewport juggling are gone.
        foreach (['filterSheetClosing', 'bindDesktopDismissal', 'data-delivery-date="start"', 'delivery-order-mobile-filter-sheet', 'delivery-order-filter-sheet__'] as $gone) {
            $this->assertStringNotContainsString($gone, $html, "Sisa implementasi lama: {$gone}");
        }
        $this->assertStringNotContainsString('mobileSalesFlatpickr', $html);
        $this->assertStringContainsString('id="kt_ecommerce_sales_flatpickr"', $html);
        $this->assertStringContainsString('#delivery-order-page .erp-workspace-heading { display:none; }', $html);
        $this->assertStringContainsString('#delivery-order-page .erp-record-controls { display:none; }', $html);
        $this->assertStringContainsString('Status Penerimaan', $html);
        $this->assertStringContainsString('Status Pembayaran', $html);
        $this->assertStringContainsString('href="' . route('pos.index') . '"', $html);
        $this->assertStringContainsString('assets/js/delivery-order-mobile.js', $html);
    }

    public function test_index_renders_delivery_detail_modal_shell(): void
    {
        $this->app->instance('request', Request::create('/delivery-order', 'GET'));
        $html = view('pos::delivery-order.index', [
            'branches' => collect([(object) ['id' => 1, 'name' => 'Toko GKB']]),
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('id="delivery-order-detail-modal"', $html);
        $this->assertStringContainsString('role="dialog" aria-modal="true"', $html);
        $this->assertStringContainsString('.delivery-order-detail-modal__icon { display:flex; width:40px; height:40px; flex:0 0 40px; align-items:center; justify-content:center; border:1px solid rgba(167,243,208,.8); border-radius:12px; background:#ecfdf5; color:#0b595b; font-size:18px; box-shadow:0 1px 2px rgba(0,0,0,.05); }', $html);
        foreach (['user', 'map-pin', 'bag', 'moped', 'receipt'] as $icon) {
            $this->assertStringContainsString('<i class="ph-duotone ph-' . $icon . ' text-lg"></i>', $html);
        }
        $this->assertStringContainsString('data-delivery-order-detail-close', $html);
        $this->assertStringContainsString('data-delivery-order-detail-action', $html);
        $this->assertStringContainsString('delivery-order-detail-modal__link--wrap', $html);
        $this->assertStringContainsString('delivery-order-detail-modal__link--phone', $html);
        $this->assertStringContainsString('.delivery-order-detail-modal__title { margin:0; color:#24364f; font:700 18px/1.4 var(--erp-font); }', $html);
        $this->assertStringContainsString('.delivery-order-detail-modal__body { display:flex; min-height:0; flex:1 1 auto; flex-direction:column; gap:14px; overflow-y:auto; padding:4px 20px 16px; overscroll-behavior:contain; }', $html);
        $this->assertStringContainsString('.delivery-order-detail-modal__link--phone { color:#24364f; border-bottom-color:#d1d5db; font:600 12px/1.35 var(--erp-font); }', $html);
        $this->assertStringContainsString('body:has(#delivery-order-page) #kt_content { padding:0!important; scroll-padding-bottom:0; }', $html);
        $this->assertStringContainsString('body:has(#delivery-order-page) #kt_content_container { display:flex; min-height:0; flex:1; flex-direction:column; padding:0!important; }', $html);
        $this->assertStringContainsString('#delivery-order-page { min-height:0; flex:1; border-radius:0; }', $html);
        foreach (['customer', 'phone', 'address', 'subtotal', 'shipping-fee', 'grand-total'] as $field) {
            $this->assertStringContainsString('data-delivery-order-detail-field="' . $field . '"', $html);
        }
    }

    private function requestRows(array $filters = [], string $search = ''): array
    {
        $columns = array_map(static fn (string $name, int $index) => [
            'data' => $name,
            'name' => $name,
            'searchable' => $index < 4 ? 'true' : 'false',
            'orderable' => $index < 4 ? 'true' : 'false',
            'search' => ['value' => '', 'regex' => 'false'],
        ], ['name', 'date', 'courier', 'status', 'action'], array_keys(['name', 'date', 'courier', 'status', 'action']));

        $request = Request::create('/delivery-order/data', 'GET', array_merge([
            'draw' => '1',
            'start' => '0',
            'length' => '10',
            'search' => ['value' => $search, 'regex' => 'false'],
            'columns' => $columns,
        ], $filters));
        $this->app->instance('request', $request);

        return app(DeliveryOrderController::class)->get_data($request)->getData(true);
    }

    private function createTables(): void
    {
        foreach (['pos_transaction_detail', 'pos_payment', 'pos_transaction', 'customer', 'kurir', 'staff', 'branch'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('customer', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('whatsapp')->nullable();
            $table->timestamps();
        });
        Schema::create('branch', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('staff', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->boolean('is_kurir')->default(false);
            $table->timestamps();
        });
        Schema::create('kurir', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('staff_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('pos_transaction', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('customer_id')->nullable();
            $table->date('date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('ongkir', 12, 2)->default(0);
            $table->decimal('ongkir_discount', 12, 2)->default(0);
            $table->string('ongkir_status')->nullable();
            $table->date('ongkir_date')->nullable();
            $table->time('ongkir_time')->nullable();
            $table->string('ongkir_address')->nullable();
            $table->unsignedInteger('courier_id')->nullable();
            $table->string('courier_type')->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('pos_transaction_detail', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('pos_id');
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('pos_payment', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('pos_id');
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedOrders(): void
    {
        DB::table('branch')->insert(['id' => 1, 'name' => 'Toko GKB']);
        DB::table('staff')->insert(['id' => 1, 'name' => 'Muhammad Chamim', 'is_kurir' => true]);
        DB::table('kurir')->insert(['id' => 1, 'name' => 'Chamim', 'type' => 'internal', 'staff_id' => 1]);

        $orders = [
            [1, 'Kak Alvi', '081234568219', 'draft', 'draft', 52000],
            [2, 'Rina Wijaya', '081234560001', 'paid', 'delivered', 33000],
            [3, 'Agus Hadi', '081234560002', 'debt', 'delivered', 28000],
        ];

        foreach ($orders as [$id, $name, $phone, $payment, $delivery, $total]) {
            DB::table('customer')->insert(['id' => $id, 'name' => $name, 'whatsapp' => $phone]);
            DB::table('pos_transaction')->insert([
                'id' => $id,
                'customer_id' => $id,
                'date' => '2026-10-31',
                'invoice_number' => 'DO-' . $id,
                'total' => $total,
                'ongkir' => 10000,
                'ongkir_status' => $delivery,
                'ongkir_date' => '2026-10-31',
                'ongkir_time' => '00:29:00',
                'ongkir_address' => 'Alamat ' . $id,
                'courier_id' => 1,
                'courier_type' => 'internal',
                'status' => $payment,
                'branch_id' => 1,
                'created_at' => '2026-10-31 00:29:00',
                'updated_at' => '2026-10-31 00:29:00',
            ]);
        }
    }
}
