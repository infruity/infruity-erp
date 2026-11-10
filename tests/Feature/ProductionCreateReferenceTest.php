<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

final class ProductionCreateReferenceTest extends TestCase
{
    private function renderForm(string $routeName, bool $includeIngredientModal = false): string
    {
        $route = (new Route('GET', 'production/form', fn () => null))->name($routeName);
        request()->setRouteResolver(fn () => $route);
        $source = file_get_contents(base_path('Modules/Transaction/Resources/views/production/create.blade.php'));
        // Isolate presentation: no application shell, scripts, HTTP calls, or business handlers.
        $source = str_replace(["@extends('template.root')", "@section('title', 'Produksi')", "@section('content')", '@endsection'], '', $source);
        $partials = ['js-create', 'segment.modal-recipe'];
        if (!$includeIngredientModal) {
            $partials[] = 'segment.modal-ingredient';
        }
        foreach ($partials as $partial) {
            $source = str_replace("@include('transaction::production.$partial')", '', $source);
        }
        $__env = app('view');
        $production_number = 'TEST-PRESENTATION';
        ob_start();
        try {
            eval('?>'.app('blade.compiler')->compileString($source));
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function test_create_layout_has_scoped_controls_empty_card_and_mobile_footer_clearance(): void
    {
        $create = $this->renderForm('production.create');
        $this->assertStringContainsString('production-form-reference.css', $create);
        $this->assertStringNotContainsString('production-form-reference.css', $this->renderForm('production.edit'));
        $css = file_get_contents(public_path('assets/css/production-form-reference.css'));
        foreach (['.production-create-reference {', 'width:100%', 'gap:24px',
            '[aria-labelledby="production-ingredients-title"]', 'min-height:260px',
            '.select2-selection--single', 'height:44px', '.erp-cart-empty', 'min-height:180px',
            'border-radius:8px', '.production-form-footer', 'position:fixed',
            'padding-bottom:calc(160px + env(safe-area-inset-bottom))',
            '#kt_content:has(.production-create-reference)', 'padding:0 !important', '.production-form .form-label { line-height:16px; }',
            'min-height:calc(142px + env(safe-area-inset-bottom))', '.select2-selection__arrow b',
            '.select2-container { display:block;'] as $rule) {
            $this->assertStringContainsString($rule, $css);
        }
        $shell = file_get_contents(resource_path('views/template/root.blade.php'));
        $this->assertMatchesRegularExpression('/production\\.create.*erp-hide-dock/s', $shell);
    }

    public function test_mobile_back_link_is_production_create_only(): void
    {
        $render = function (string $routeName): string {
            $route = (new Route('GET', 'production/form', fn () => null))->name($routeName);
            request()->setRouteResolver(fn () => $route);
            $source = file_get_contents(resource_path('views/components/erp/topbar.blade.php'));
            $source = explode('@unless($mobileOnly)', $source)[0];
            $source = str_replace("@props(['mobileOnly' => false])", '', $source);
            $__env = app('view');
            ob_start();
            try {
                eval('?>'.app('blade.compiler')->compileString($source));
                return ob_get_contents();
            } finally {
                ob_end_clean();
            }
        };
        $create = $render('production.create');
        $this->assertStringContainsString('href="'.route('production.index').'"', $create);
        $this->assertStringContainsString('aria-label="Kembali ke Riwayat Produksi"', $create);
        $this->assertStringNotContainsString('data-erp-sidebar-toggle', $create);
        $this->assertStringContainsString('data-erp-sidebar-toggle', $render('production.edit'));
        $this->assertStringContainsString('href="'.route('wholesale.index').'"', $render('wholesale.create'));
    }

    public function test_create_summary_uses_honest_cost_labels_and_keeps_existing_save_actions(): void
    {
        $create = $this->renderForm('production.create');
        $this->assertStringContainsString("totalServiceCost > 0 ? 'Total Biaya Produksi' : 'Total Biaya Bahan'", $create);
        $this->assertStringContainsString('Simpan sebagai Draf', $create);
        $this->assertStringContainsString('Selesaikan Produksi', $create);
        foreach (["saveProduction('temp', () => loading = false)", "saveProduction('posting', () => loading = false)", 'formatCurrency(totalHpp)', 'formatCurrency(hppPerUnit)'] as $binding) {
            $this->assertStringContainsString($binding, $create);
        }
        $edit = $this->renderForm('production.edit');
        $this->assertStringContainsString('<span>Total Biaya</span>', $edit);
        $this->assertStringContainsString('<span>Simpan Draft</span>', $edit);
        $this->assertStringContainsString('<span>Selesai</span>', $edit);
        $this->assertStringNotContainsString('Total Biaya Bahan', $edit);
    }

    public function test_create_product_picker_matches_reference_without_changing_edit_selector(): void
    {
        $create = $this->renderForm('production.create');
        $edit = $this->renderForm('production.edit');

        $this->assertStringContainsString('production-product-picker-field', $create);
        $this->assertStringContainsString('data-placeholder="Pilih produk olahan..."', $create);
        $this->assertStringContainsString('<i class="ph ph-bag"></i>', $create);
        $this->assertStringContainsString('production-product-picker-action', $create);
        $this->assertStringContainsString('openReferenceProductPicker()', $create);
        $this->assertStringContainsString('reference-product-picker', $create);
        $this->assertStringContainsString('x-text="referenceProductEmoji(product.text)"', $create);
        $this->assertStringNotContainsString('<i class="ph ph-bag" aria-hidden="true"></i>', $create);
        $this->assertStringNotContainsString('production-product-picker-field', $edit);
        $this->assertStringNotContainsString('reference-product-picker', $edit);
        $this->assertStringNotContainsString('data-placeholder="Pilih produk olahan..."', $edit);

        $script = file_get_contents(base_path('Modules/Transaction/Resources/views/production/js-create.blade.php'));
        $this->assertStringContainsString("placeholder: $('#product_id').data('placeholder') || 'Pilih Produk'", $script);
        $this->assertStringContainsString('async loadReferenceProducts()', $script);
        $this->assertStringContainsString('selectReferenceProduct(product)', $script);
        $this->assertStringContainsString("url.searchParams.set('search', query)", $script);

        $css = file_get_contents(public_path('assets/css/production-form-reference.css'));
        $this->assertStringContainsString('.production-create-reference #reference-product-picker .production-reference-picker__search input:focus { outline:none; border-color:#10b981; box-shadow:none; }', $css);
        $this->assertStringNotContainsString('.production-create-reference .production-reference-picker__search input:focus { border-color:#10b981; box-shadow:0 0 0 1px #10b981; }', $css);
        foreach (['.production-product-picker-trigger',
            '.production-product-picker-field > .select2-container { display:none !important; }',
            '.production-reference-picker__panel', '.production-reference-picker__option'] as $rule) {
            $this->assertStringContainsString($rule, $css);
        }
    }

    public function test_create_ingredient_picker_matches_reference_without_changing_edit_flow(): void
    {
        $create = $this->renderForm('production.create', true);
        $edit = $this->renderForm('production.edit', true);

        $this->assertStringContainsString('production-reference-ingredient-modal', $create);
        $this->assertStringContainsString('Pilih Bahan Baku', $create);
        $this->assertStringContainsString('Pilih buah atau bahan pelengkap produksi', $create);
        $this->assertStringContainsString('Cari bahan (misal: Stroberi, Susu)...', $create);
        $this->assertStringContainsString('production-reference-ingredient-option', $create);
        $this->assertStringContainsString('selectIngredient(ingredient)', $create);
        $this->assertStringContainsString('x-show="filteredIngredients.length === 0"', $create);
        $this->assertStringNotContainsString('Memuat bahan…', $create);
        $this->assertStringContainsString('id="modal-input-quantity"', $create);
        $this->assertStringNotContainsString('production-reference-ingredient-modal', $edit);
        $this->assertStringContainsString('Tambah Bahan Baku', $edit);
    }

    public function test_create_target_and_hpp_use_reference_unit_fallback_without_changing_edit(): void
    {
        $create = $this->renderForm('production.create');
        $edit = $this->renderForm('production.edit');

        $this->assertStringContainsString('<span class="production-quantity-unit" aria-hidden="true">PORSI</span>', $create);
        $this->assertStringContainsString("Estimasi HPP: ' + formatCurrency(hppPerUnit) + ' / porsi", $create);
        $this->assertStringContainsString("Estimasi HPP: ' + formatCurrency(hppPerUnit) + ' / unit", $edit);
        $this->assertStringNotContainsString('production-quantity-unit', $edit);
    }

    public function test_create_ingredient_rows_use_reference_inline_quantity_and_cost_presentation(): void
    {
        $create = $this->renderForm('production.create');
        $edit = $this->renderForm('production.edit');

        $this->assertStringContainsString('ph-duotone ph-basket', $create);
        $this->assertStringContainsString('Biaya Modal:', $create);
        $this->assertStringContainsString('production-inline-quantity', $create);
        $this->assertStringContainsString(':value="inlineQuantityDrafts[ingredient.id] ?? ingredient.quantity"', $create);
        $this->assertStringContainsString('production-reference-cart__controls', $create);
        $this->assertStringContainsString('updateIngredientInline(index, $event.target.value)', $create);
        $this->assertStringContainsString('removeIngredient(index)', $create);
        $this->assertStringContainsString('openEditIngredientModal(ingredient,index)', $edit);
        $this->assertStringNotContainsString('production-inline-quantity', $edit);
    }

    public function test_create_reference_spacing_and_branch_dropdown_match_source_breakpoints(): void
    {
        $css = file_get_contents(public_path('assets/css/production-form-reference.css'));
        foreach ([
            '@media (min-width:640px)',
            'max-width:896px;',
            '.production-reference-ingredient-modal .modal-dialog { width:calc(100% - 32px); max-width:448px; margin:16px auto; padding:0; }',
            '.production-reference-cart__controls {',
            'font-size:12px; font-weight:700;',
            'width:64px; font-size:14px;',
            'padding:24px',
            '@media (max-width:639px)',
            'background-color:rgba(249,250,251,.7) !important',
            'font-weight:600',
            '.select2-results__option--highlighted',
            'max-height:224px',
        ] as $rule) {
            $this->assertStringContainsString($rule, $css);
        }
    }

    public function test_create_first_card_keeps_extra_payload_fields_after_ingredients(): void
    {
        $html = $this->renderForm('production.create');
        $this->assertStringContainsString('production-create-reference', $html);
        preg_match('/<section[^>]+aria-labelledby="production-info-title"[^>]*>(.*?)<\/section>/s', $html, $card);
        preg_match_all('/(?:input|select)[^>]+\bid="([^"]+)"/', $card[1], $controls);
        $this->assertSame(['branch_id', 'product_id', 'quantity', 'production_notes'], $controls[1]);
        $this->assertStringContainsString('Target Hasil Produksi', $card[1]);
        $this->assertStringContainsString('x-model="productionQuantity"', $card[1]);
        $ingredients = strpos($html, 'aria-labelledby="production-ingredients-title"');
        foreach (['production_date', 'sell_price', 'service_cost', 'production_number', 'production_id'] as $id) {
            $this->assertSame(1, substr_count($html, 'id="'.$id.'"'));
            $this->assertGreaterThan($ingredients, strpos($html, 'id="'.$id.'"'));
        }
        $this->assertStringNotContainsString('<details', $html);
        $this->assertStringContainsString('openAddIngredientModal()', $html);
        $this->assertStringNotContainsString('openEditIngredientModal(ingredient,index)', $html);
        $this->assertStringContainsString('Pilih produk di Card 1 untuk muat resep otomatis, atau klik tombol', $html);
        $edit = $this->renderForm('production.edit');
        $this->assertStringNotContainsString('production-create-reference', $edit);
        preg_match_all('/(?:input|select)[^>]+\bid="([^"]+)"/', $edit, $editControls);
        $this->assertSame(['branch_id', 'production_date', 'product_id', 'quantity', 'sell_price', 'production_number', 'production_id', 'service_cost', 'production_notes'], $editControls[1]);
        $this->assertStringContainsString('Jumlah Produksi', $edit);
    }

    public function test_create_removes_extra_information_card_but_preserves_payload_fields(): void
    {
        $create = $this->renderForm('production.create');

        $this->assertStringNotContainsString('production-extra-fields', $create);
        $this->assertStringNotContainsString('Informasi Tambahan Produksi', $create);
        $this->assertStringContainsString('<div class="production-payload-fields" hidden aria-hidden="true">', $create);
        preg_match('~<div class="production-payload-fields" hidden aria-hidden="true">(.*?)</div>~s', $create, $payload);
        $this->assertNotEmpty($payload[1] ?? null);
        foreach (['production_date', 'sell_price', 'service_cost', 'production_number', 'production_id'] as $id) {
            $this->assertSame(1, substr_count($payload[1], 'id="'.$id.'"'));
            $this->assertSame(1, substr_count($create, 'id="'.$id.'"'));
        }
        $this->assertStringContainsString('name="production_date"', $payload[1]);
        $this->assertStringContainsString('name="sell_price"', $payload[1]);
        $this->assertStringContainsString('name="service_cost"', $payload[1]);
        $this->assertStringContainsString('x-model="sellPrice"', $payload[1]);
        $this->assertStringContainsString('x-model="serviceCost"', $payload[1]);

        $edit = $this->renderForm('production.edit');
        $this->assertStringNotContainsString('production-payload-fields', $edit);
        $this->assertStringContainsString('id="production_date" name="production_date"', $edit);
        $this->assertStringContainsString('id="sell_price" class="form-control format-number"', $edit);
        $this->assertStringContainsString('id="service_cost" class="form-control format-number"', $edit);
    }

    public function test_create_branch_dropdown_matches_reference_states_and_quantity_keeps_active_stroke(): void
    {
        $script = file_get_contents(base_path('Modules/Transaction/Resources/views/production/js-create.blade.php'));
        $this->assertSame(2, substr_count($script, 'allowClear: true'));

        $css = file_get_contents(public_path('assets/css/production-form-reference.css'));
        foreach ([
            'body.erp-ui .production-create-reference #branch_id + .select2-container .select2-selection--single',
            'body.erp-ui .production-create-reference #branch_id + .select2-container--open .select2-selection--single',
            'background-color:rgba(249,250,251,.7) !important',
            'font:14px/1.5 var(--erp-font)',
            'border-color:#e5e7eb !important',
            'font-weight:600',
            'border-color:#10b981 !important',
            'box-shadow:0 0 0 1px #10b981 !important',
            'body.erp-ui .production-create-reference #quantity:focus',
            'outline:none !important',
        ] as $rule) {
            $this->assertStringContainsString($rule, $css);
        }
        $this->assertStringContainsString('body.erp-ui .production-create-reference #branch_id + .select2-container .select2-selection__clear', $css);

        $theme = file_get_contents(public_path('assets/css/erp-theme.css'));
        $this->assertStringContainsString('.erp-ui .select2-dropdown { border-color: var(--erp-border); border-radius: 12px;', $theme);
    }
}
