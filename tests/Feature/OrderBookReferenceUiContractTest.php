<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
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

    public function test_order_book_filter_sheet_uses_the_shared_component_and_existing_request_fields(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));

        foreach ([
            '@click="openFilter()"',
            'aria-controls="order-book-filter-sheet"',
            '<x-erp.filter-sheet',
            'id="order-book"',
            'title="Filter Pesanan"',
            'branch-label="Cabang Tujuan"',
            'status-label="Status Pesanan"',
            "'draft' => 'Pending'",
            "'process' => 'Proses'",
            "'done' => 'Selesai'",
            '<option value="draft">Pending</option>',
            ':timezone="config(\'app.timezone\')"',
            'week-start="monday"',
            'desktop="modal"',
            'reset-applies',
            'reset-label="Reset Filter"',
            'apply-label="Terapkan Filter"',
            "this.\$dispatch('erp-filter-open', { id: 'order-book'",
            '@erp-filter-apply.window',
            'document.dispatchEvent(new CustomEvent(\'order-book:filters-applied\'))',
            'document.addEventListener(\'order-book:filters-applied\'',
            'd.status_filter =',
            'd.cabang_filter =',
            'd.start_date =',
            'd.end_date =',
        ] as $filterContract) {
            $this->assertStringContainsString($filterContract, $view, "Missing order-book filter sheet contract: {$filterContract}");
        }

        // The sheet markup, calendar and draft state live in the component now.
        foreach (['draftBranch', 'draftStartDate', 'calOpen', 'orderBookFilterUi', 'x-teleport'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, "Sisa implementasi lama: {$gone}");
        }
        $this->assertStringNotContainsString('data-kt-menu-trigger="click"', $view);
    }

    public function test_process_filter_includes_posting_orders_displayed_as_proses(): void
    {
        $controller = file_get_contents(base_path('Modules/Pos/Http/Controllers/OrderBookController.php'));

        $this->assertStringContainsString("if (\$request->status_filter === 'process')", $controller);
        $this->assertStringContainsString("whereIn('status', ['process', 'posting'])", $controller);
    }

    private function orderBookSheet(): string
    {
        return Blade::render(
            '<x-erp.filter-sheet id="order-book" title="Filter Pesanan" :branches="$branches" :statuses="$statuses" desktop="modal" week-start="monday" reset-applies reset-label="Reset Filter" apply-label="Terapkan Filter" />',
            [
                'branches' => [(object) ['id' => 1, 'name' => 'toko gkb']],
                'statuses' => ['all' => 'Semua Status', 'draft' => 'Pending', 'process' => 'Proses', 'done' => 'Selesai'],
            ]
        );
    }

    public function test_filter_sheet_keeps_reference_sizing_without_forcing_empty_viewport_height(): void
    {
        $sheet = $this->orderBookSheet();

        foreach ([
            'id="order-book-filter-sheet"',
            'h-auto',
            'max-h-[88dvh]',
            'max-w-[512px]',
            'min-h-0 flex-1',
            'overflow-y-auto',
            'space-y-[16px]',
            'text-[12px]',
            'sm:items-center',
            'Reset Filter',
            'Terapkan Filter',
        ] as $filterSheetContract) {
            $this->assertStringContainsString($filterSheetContract, $sheet, "Missing reference filter sizing: {$filterSheetContract}");
        }

        // Footer action height (44px) is owned by the shared filter sheet stylesheet.
        $this->assertStringContainsString('--erp-filter-action-height:44px;', file_get_contents(base_path('public/assets/css/erp-filters.css')));
        $this->assertDoesNotMatchRegularExpression('/(?<!max-)h-\[\d+(?:d)?vh\]/', $sheet);
        $this->assertStringContainsString('#order-book-filter-sheet :is(h2, h3, h4, p) { margin-bottom:0; }', file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php')));
    }

    public function test_filter_sizes_do_not_depend_on_the_page_root_font_size(): void
    {
        $component = file_get_contents(base_path('resources/views/components/erp/filter-sheet.blade.php'));
        $markup = substr($component, strpos($component, '--}}') + 4);

        // Pages with a 12px root (this one) would shrink rem based Tailwind sizes.
        $this->assertDoesNotMatchRegularExpression('/(?<![\w\[-])(?:text-(?:xs|sm|base)|(?:p[xytblr]?|m[xytblr]?|gap|h|w)-(?:[1-9]\d*(?:\.\d+)?|0\.\d+))(?![\w\[%-])/', $markup);
    }

    public function test_filter_controls_do_not_inherit_bootstrap_border_and_text_utilities(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));
        $sheet = $this->orderBookSheet();

        $this->assertStringContainsString(':where(#order-book-filter-sheet, #order-book-filter-sheet *) { border-width:0; border-style:solid; border-color:#e5e7eb; }', $view);
        $this->assertStringContainsString('border-[1px] border-solid', $sheet);
        $this->assertStringContainsString('border-[2px] border-solid', $sheet);
        // The primary action's colour comes from the shared sheet frame, not inline utilities.
        $this->assertStringContainsString('class="erp-filter-sheet__primary"', $sheet);
        $this->assertStringContainsString('shadow-[0_1px_2px_rgba(0,0,0,0.05)]', $sheet);
        $this->assertDoesNotMatchRegularExpression('/(?:class="|\s)border(?:\s|")/', $sheet);
    }
}
