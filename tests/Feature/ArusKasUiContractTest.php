<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ArusKasUiContractTest extends TestCase
{
    public function test_arus_kas_index_route_is_registered_inside_authentication(): void
    {
        $route = Route::getRoutes()->getByName('arus-kas.index');

        $this->assertNotNull($route);
        $this->assertSame('arus-kas', $route->uri());
        $this->assertContains('auth', $route->gatherMiddleware());
    }

    public function test_view_exposes_all_eight_reference_states_and_their_actions(): void
    {
        $viewPath = base_path('Modules/Pos/Resources/views/arus-kas/index.blade.php');
        $this->assertFileExists($viewPath);
        $view = file_get_contents($viewPath);

        foreach ([
            'Belum ada transaksi arus kas',
            'Filter Arus Kas',
            'Cabang Tujuan',
            'Status Arus Kas',
            'Detail Arus Kas',
            'Cabang Toko',
            'Daftar Kebutuhan Kas',
            'Tambah Arus Kas',
            'Edit Arus Kas',
            'Alur Status Arus Kas',
            'Petugas Arus Kas',
            'Informasi Pemasok',
            'Tujuan &amp; Pembayaran',
            'Rincian Item Kas',
            'Cetak PO',
            'Tambah Item Kas',
            'Kas Masuk',
            'Kas Keluar',
            'Contoh: Bensin',
            'Kuantitas',
            'Biaya / Harga Satuan',
            'Tambah Transaksi',
        ] as $referenceText) {
            $this->assertStringContainsString($referenceText, $view, "Missing reference UI text: {$referenceText}");
        }

        $this->assertStringContainsString('ph-sliders-horizontal', $view);
        $this->assertStringContainsString('x-model.debounce.200ms="searchQuery"', $view);
        $this->assertStringContainsString('openFilterModal()', $view);
        $this->assertStringContainsString('x-show="filterOpen"', $view);
        $this->assertStringContainsString('open="detailModalOpen"', $view);
        $this->assertStringContainsString('x-show="invoiceModalOpen"', $view);
        $this->assertStringContainsString('x-show="itemModalOpen"', $view);
    }

    public function test_mobile_cash_rows_match_reference_spacing_text_scale_and_alignment(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/arus-kas/index.blade.php'));

        $this->assertStringContainsString('.arus-kas-row { padding:12px 16px; transition:background-color .15s ease; }', $view);
        $this->assertStringContainsString('.arus-kas-row__icon { box-shadow:0 1px 2px 0 rgba(0,0,0,.05); }', $view);
        $this->assertStringContainsString('.arus-kas-row__date { font-size:11px; line-height:16px; }', $view);
        $this->assertStringContainsString('.arus-kas-row__status { width:100%; text-align:right; font-size:12px; font-weight:600; line-height:16px; }', $view);
        $this->assertStringContainsString('.arus-kas-row__total { margin-top:2px; font-size:12px; font-weight:700; line-height:16px; }', $view);
    }

    public function test_mobile_detail_dialog_matches_reference_scale(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/arus-kas/index.blade.php'));

        $this->assertStringContainsString('id="arus-kas-detail-dialog"', $view);
        $this->assertStringContainsString('#arus-kas-detail-dialog > section { width:384px; max-width:calc(100vw - 32px); max-height:calc(100dvh - 32px); border-radius:16px; }', $view);
        $this->assertStringContainsString('#arus-kas-detail-dialog > section > div.p-6 { padding:24px; font-size:16px; line-height:24px; }', $view);
        $this->assertStringContainsString('#arus-kas-detail-dialog .space-y-4 { display:flex; flex-direction:column; gap:20px; }', $view);
        $this->assertStringContainsString('#arus-kas-detail-dialog .space-y-4 .text-sm { font-size:14px; line-height:20px; }', $view);
    }

    public function test_detail_dialog_omits_the_legacy_read_only_notice(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/arus-kas/index.blade.php'));

        $this->assertStringNotContainsString('Transaksi lama ditampilkan hanya-baca agar data Pemasukan/Pengeluaran yang sudah ada tetap aman.', $view);
    }

    public function test_item_popup_matches_reference_width_and_body_scale(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/arus-kas/index.blade.php'));

        $this->assertStringContainsString('width:calc(100% - 32px); max-width:512px; max-height:92vh;', $view);
        $this->assertStringContainsString('.arus-kas-item-modal__body { overflow-y:auto; padding:24px; font-size:16px; line-height:24px; }', $view);
        $this->assertStringContainsString('@media (min-width:640px) { .arus-kas-item-modal__body { padding:28px; } }', $view);
        $this->assertStringContainsString('.arus-kas-category { display:flex; height:48px;', $view);
        $this->assertStringContainsString('.arus-kas-item-modal__footer { border-top:1px solid #f3f4f6; padding:20px 24px; }', $view);
        $this->assertStringContainsString('class="arus-kas-item-modal__body space-y-5"', $view);
    }

    public function test_item_popup_uses_reference_sizing_independent_of_local_rem_scale(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/arus-kas/index.blade.php'));

        $this->assertStringContainsString('.arus-kas-item-modal > header.px-6.py-5 { padding:20px 24px; }', $view);
        $this->assertStringContainsString('.arus-kas-item-modal > header h2.text-base { font-size:16px; line-height:20px; }', $view);
        $this->assertStringContainsString('.arus-kas-item-modal > header p.mt-1.text-xs { margin-top:4px; font-size:12px; line-height:16px; }', $view);
        $this->assertStringContainsString('.arus-kas-item-modal__body > :not([hidden]) ~ :not([hidden]) { margin-top:20px; }', $view);
        $this->assertStringContainsString('.arus-kas-item-modal__body fieldset > legend.mb-2.text-xs.font-semibold { margin-bottom:8px; font-size:12px; line-height:16px; }', $view);
        $this->assertStringContainsString('.arus-kas-item-modal__body label.block.text-xs.font-semibold { font-size:12px; line-height:16px; }', $view);
        $this->assertStringContainsString('.arus-kas-item-modal__body label > .arus-kas-field.mt-2 { margin-top:8px; }', $view);
        $this->assertStringContainsString('.arus-kas-category svg.h-4.w-4 { width:16px; height:16px; }', $view);
        $this->assertStringContainsString('.arus-kas-item-modal__footer button.h-12 { height:48px; border-radius:12px; font-size:12px; line-height:16px; }', $view);
    }

    public function test_branch_picker_uses_current_branch_ids_in_a_custom_option_list(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/arus-kas/index.blade.php'));

        $this->assertStringContainsString('<select id="arus-kas-branch" name="branch_id" x-model="form.branchId"', $view);
        $this->assertStringContainsString('x-for="branch in branches"', $view);
        $this->assertStringContainsString('role="listbox"', $view);
        $this->assertStringContainsString('role="option"', $view);
        $this->assertStringContainsString('x-ref="branchOptions"', $view);
        $this->assertStringContainsString('this.$refs.branchOptions.querySelector', $view);
        $this->assertStringContainsString('form.branchId = String(branch.id)', $view);
    }

    public function test_branch_picker_matches_the_reference_dropdown_surface(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/arus-kas/index.blade.php'));

        $this->assertStringContainsString('.arus-kas-branch-options { position:absolute; top:calc(100% + 6px); left:0; z-index:50; width:100%; max-height:224px; overflow-y:auto;', $view);
        $this->assertStringContainsString('.arus-kas-branch-option[aria-selected="true"] { background:#f9fafb; color:#059669; font-weight:600; }', $view);
    }

    public function test_keuangan_menu_and_mobile_dock_point_to_the_new_flow_only(): void
    {
        $sidebar = file_get_contents(resource_path('views/components/erp/sidebar.blade.php'));
        $mobileAction = file_get_contents(app_path('Support/ErpMobileAction.php'));

        $this->assertStringContainsString("route('arus-kas.index')", $sidebar);
        $this->assertStringContainsString('arus-kas.index', $mobileAction);
        $this->assertStringContainsString('arus-kas.create', $mobileAction);
    }
}
