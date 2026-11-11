<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrderBookReferenceUiContractTest extends TestCase
{
    public function test_mobile_toolbar_matches_the_order_book_reference_without_dropping_create_access(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));

        foreach ([
            'class="order-book-workspace"',
            'class="order-book-toolbar-controls"',
            'placeholder="Cari..."',
            'order-book-filter-trigger',
            'order-book-create',
            'order-book-workspace .erp-workspace-heading { display:none; }',
            '.order-book-workspace { width:100vw; margin-inline:calc(50% - 50vw); }',
            '.order-book-workspace .order-book-create,',
        ] as $referenceContract) {
            $this->assertStringContainsString($referenceContract, $view, "Missing order-book reference contract: {$referenceContract}");
        }

        $mobileAction = file_get_contents(base_path('app/Support/ErpMobileAction.php'));
        $this->assertStringContainsString("[['order-book.index'], 'order-book.create', 'order-book.create']", $mobileAction);
    }

    public function test_mobile_toolbar_uses_the_existing_mobile_search_component(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));

        foreach ([
            '<x-erp.mobile-search class="order-book-mobile-search erp-mobile-search-shell--fill">',
            'class="erp-mobile-search__input"',
            '<x-slot:filter>',
            'class="erp-mobile-search__filter order-book-filter-trigger"',
            'class="erp-search d-none d-md-flex"',
            'id="search-desktop"',
            '$searchInputs = $(\'[data-kt-ecommerce-product-filter="search"]\');',
            '$searchInputs.on(\'input\', function() {',
            '$searchInputs.not(this).val(this.value);',
            'dataTable.search(this.value).draw();',
        ] as $sharedSearchContract) {
            $this->assertStringContainsString($sharedSearchContract, $view, "Missing shared mobile search contract: {$sharedSearchContract}");
        }
    }

    public function test_mobile_pager_stays_available_for_records_after_the_first_hundred(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));
        $mobileStart = strpos($view, '@media (max-width:767px)');
        $styleEnd = strpos($view, '</style>', $mobileStart);
        $mobileStyles = substr($view, $mobileStart, $styleEnd - $mobileStart);

        $this->assertStringContainsString('pageLength: 100', $view);
        $this->assertStringContainsString('.order-book-workspace .erp-record-controls { display:none; }', $mobileStyles);
        $this->assertStringNotContainsString('.order-book-workspace .erp-record-pager', $mobileStyles);
    }

    public function test_mobile_order_rows_keep_search_filter_and_order_actions_while_showing_reference_metadata(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));

        foreach ([
            'order-book-record__summary',
            'order-book-record__status',
            'order-book-record__invoice',
            'orderBookReferenceUi.initials',
            'orderBookReferenceUi.formatDate',
            'orderBookReferenceUi.status',
            'orderBookReferenceUi.invoice',
            "route('order-book.data')",
            'd.status_filter =',
            'd.cabang_filter =',
            'd.start_date =',
            'd.end_date =',
            'function setSelesai(id)',
            'function deleteProduct(id)',
        ] as $referenceContract) {
            $this->assertStringContainsString($referenceContract, $view, "Missing order-book behavior contract: {$referenceContract}");
        }
    }

    public function test_order_book_filter_sheet_uses_reference_controls_and_existing_request_fields(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));

        foreach ([
            '@click="openFilter()"',
            'order-book-filter-sheet',
            'role="dialog"',
            'aria-modal="true"',
            'Cabang Tujuan',
            'placeholder="Cari cabang..."',
            'x-text="branch.name"',
            ':value="branch.id"',
            "{ value: 'draft', label: 'Pending' }",
            "{ value: 'process', label: 'Proses' }",
            "{ value: 'done', label: 'Selesai' }",
            '<option value="draft">Pending</option>',
            'Periode Tanggal',
            'Minggu Ini',
            'Hari Ini',
            'Reset Filter',
            'Terapkan Filter',
            'document.dispatchEvent(new CustomEvent(\'order-book:filters-applied\'))',
            'document.addEventListener(\'order-book:filters-applied\'',
            'd.status_filter =',
            'd.cabang_filter =',
            'd.start_date =',
            'd.end_date =',
        ] as $filterContract) {
            $this->assertStringContainsString($filterContract, $view, "Missing order-book filter sheet contract: {$filterContract}");
        }

        $this->assertStringNotContainsString('data-kt-menu-trigger="click"', $view);
    }

    public function test_process_filter_includes_posting_orders_displayed_as_proses(): void
    {
        $controller = file_get_contents(base_path('Modules/Pos/Http/Controllers/OrderBookController.php'));

        $this->assertStringContainsString("if (\$request->status_filter === 'process')", $controller);
        $this->assertStringContainsString("whereIn('status', ['process', 'posting'])", $controller);
    }

    public function test_filter_sheet_keeps_reference_sizing_without_forcing_empty_viewport_height(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));
        $sheet = explode('<template x-teleport="body">', $view, 2)[1];
        $sheet = explode('</x-erp.workspace>', $sheet, 2)[0];

        foreach ([
            'h-auto',
            'max-h-[88vh]',
            'max-h-[88dvh]',
            'max-w-[512px]',
            'min-h-0 flex-1',
            'overflow-y-auto',
            'space-y-[16px]',
            'text-[12px] leading-[16px]',
            'text-[16px] leading-[16px]',
            '<x-erp.filter-sheet-frame',
            '<x-slot:footer>',
        ] as $filterSheetContract) {
            $this->assertStringContainsString($filterSheetContract, $sheet, "Missing reference filter sizing: {$filterSheetContract}");
        }

        // Footer action height (44px) is owned by the shared filter sheet stylesheet.
        $this->assertStringContainsString('--erp-filter-action-height:44px;', file_get_contents(base_path('public/assets/css/erp-filters.css')));
        $this->assertDoesNotMatchRegularExpression('/(?<!max-)h-\[\d+(?:d)?vh\]/', $sheet);
        $this->assertStringContainsString('#order-book-filter-sheet :is(h2, h3, h4, p) { margin-bottom:0; }', $view);
    }

    public function test_filter_controls_do_not_inherit_bootstrap_border_and_text_utilities(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));
        $sheet = explode('<template x-teleport="body">', $view, 2)[1];
        $sheet = explode('</x-erp.workspace>', $sheet, 2)[0];

        $this->assertStringContainsString(':where(#order-book-filter-sheet, #order-book-filter-sheet *) { border-width:0; border-style:solid; border-color:#e5e7eb; }', $view);
        $this->assertStringContainsString('border-[1px] border-solid', $sheet);
        $this->assertStringContainsString('border-[2px] border-solid', $sheet);
        // The primary action's colour now comes from the shared Stock Opname sheet frame, not inline utilities.
        $this->assertStringContainsString('class="erp-filter-sheet__primary"', $sheet);
        $this->assertStringContainsString('shadow-[0_1px_2px_rgba(0,0,0,0.05)]', $sheet);
        $this->assertDoesNotMatchRegularExpression('/(?:class="|\s)border(?:\s|")/', $sheet);
    }
}
