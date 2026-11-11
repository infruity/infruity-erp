<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ErpFilterSheetFrameTest extends TestCase
{
    public function test_shared_frame_renders_header_body_footer_and_custom_slots(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-erp.filter-sheet-frame title="Filter Demo" title-id="filter-demo-title" subtitle-id="filter-demo-description" subtitle="Sesuaikan filter">
                <x-slot:close><button type="button" aria-label="Tutup filter">X</button></x-slot:close>
                <x-slot:body><fieldset><legend>Cabang</legend><span>Semua Cabang</span></fieldset></x-slot:body>
                <x-slot:footer><button type="button">Reset</button><button type="button">Terapkan</button></x-slot:footer>
            </x-erp.filter-sheet-frame>
            BLADE);

        $this->assertStringContainsString('<header class="erp-filter-sheet__header">', $html);
        $this->assertStringContainsString('id="filter-demo-title"', $html);
        $this->assertStringContainsString('id="filter-demo-description"', $html);
        $this->assertStringContainsString('Sesuaikan filter', $html);
        $this->assertMatchesRegularExpression('/<div[^>]*class="[^"]*erp-filter-sheet__body/', $html);
        $this->assertMatchesRegularExpression('/<footer[^>]*class="[^"]*erp-filter-sheet__footer/', $html);
        $this->assertStringContainsString('aria-label="Tutup filter"', $html);
        $this->assertStringContainsString('Semua Cabang', $html);
        $this->assertStringContainsString('Terapkan', $html);
    }

    public function test_filter_views_compile_after_shared_frame_migration(): void
    {
        $views = [
            'Modules/Pos/Resources/views/arus-kas/index.blade.php',
            'Modules/Pos/Resources/views/delivery-order/index.blade.php',
            'Modules/Pos/Resources/views/order-book/index.blade.php',
            'Modules/Pos/Resources/views/pos/index2.blade.php',
            'Modules/Transaction/Resources/views/stock-opname/partials/reference-filters.blade.php',
            'Modules/Transaction/Resources/views/wholesale/index.blade.php',
            'Modules/Transaction/Resources/views/sortir/index2.blade.php',
            'Modules/Transaction/Resources/views/transfer/index2.blade.php',
            'Modules/Report/Resources/views/partials/mobile-detail.blade.php',
            'Modules/Master/Resources/views/products/stock.blade.php',
            'Modules/Master/Resources/views/products/stock-show.blade.php',
        ];

        foreach ($views as $view) {
            $compiled = Blade::compileString(file_get_contents(base_path($view)));
            $this->assertIsString($compiled, "Blade compilation failed for {$view}");
            $this->assertNotSame('', $compiled, "Blade output is empty for {$view}");
        }
    }

    public function test_filter_interactions_remain_owned_by_the_existing_views(): void
    {
        $contracts = [
            'Modules/Pos/Resources/views/order-book/index.blade.php' => ['applyFilters(', '<x-erp.filter-sheet'],
            'Modules/Pos/Resources/views/delivery-order/index.blade.php' => ['applySheetFilters(', '<x-erp.filter-sheet'],
            'Modules/Pos/Resources/views/pos/index2.blade.php' => ['applyFilters(', '<x-erp.filter-sheet'],
            'Modules/Pos/Resources/views/arus-kas/index.blade.php' => ['applyMobileFilters(', '<x-erp.filter-sheet'],
            'Modules/Transaction/Resources/views/wholesale/index.blade.php' => ['applyWholesaleMobileFilters(', 'data-wholesale-filter-reset', '<x-erp.filter-sheet'],
            'Modules/Transaction/Resources/views/sortir/index2.blade.php' => ['applyFilters(', '<x-erp.filter-sheet'],
            'Modules/Transaction/Resources/views/transfer/index2.blade.php' => ['applyFilters(', '<x-erp.filter-sheet'],
            'Modules/Report/Resources/views/partials/mobile-detail.blade.php' => ['applyDraftLive()', 'calendarOpen'],
            'Modules/Master/Resources/views/products/stock.blade.php' => ['id="stock-filter-apply"', 'setFilterOpen(open)'],
            'Modules/Master/Resources/views/products/stock-show.blade.php' => ['id="stock-detail-filter-apply"', 'data-stock-date-preset'],
        ];

        foreach ($contracts as $view => $tokens) {
            $source = file_get_contents(base_path($view));
            foreach ($tokens as $token) {
                $this->assertStringContainsString($token, $source, "Filter behavior contract missing from {$view}: {$token}");
            }
        }
    }

    public function test_stock_opname_and_similar_sheet_filters_use_the_shared_frame(): void
    {
        $views = [
            'resources/views/components/erp/filter-sheet.blade.php',
            'resources/views/components/erp/filter-sheet.blade.php',
            'Modules/Report/Resources/views/partials/mobile-detail.blade.php',
            'Modules/Master/Resources/views/products/stock.blade.php',
            'Modules/Master/Resources/views/products/stock-show.blade.php',
        ];

        foreach ($views as $view) {
            $path = base_path($view);
            $this->assertFileExists($path, "Missing filter view: {$view}");
            $this->assertStringContainsString('<x-erp.filter-sheet-frame', file_get_contents($path), "Filter sheet not using the shared frame: {$view}");
        }
    }

    public function test_sheet_can_drop_the_period_section_for_branch_and_status_only_lists(): void
    {
        $with = Blade::render('<x-erp.filter-sheet id="a" title="Filter" :statuses="$statuses" />', ['statuses' => ['all' => 'Semua']]);
        $without = Blade::render('<x-erp.filter-sheet id="a" title="Filter" :statuses="$statuses" :with-dates="false" />', ['statuses' => ['all' => 'Semua']]);

        foreach (['Periode Tanggal', 'Pilihan Cepat', 'id="a-mobile-calendar"'] as $dates) {
            $this->assertStringContainsString($dates, $with);
            $this->assertStringNotContainsString($dates, $without);
        }
        $this->assertStringContainsString('Semua', $without);
        $this->assertStringContainsString('Terapkan', $without);
    }

    public function test_sheet_renders_extra_choice_groups_and_quick_ranges(): void
    {
        $html = Blade::render(
            '<x-erp.filter-sheet id="a" title="Filter" :statuses="$statuses" :groups="$groups" :quick-ranges="[\'week\', \'today\', \'month\']" />',
            [
                'statuses' => ['all' => 'Semua'],
                'groups' => [
                    ['key' => 'delivery', 'label' => 'Status Penerimaan', 'options' => ['all' => 'Semua', 'received' => 'Sudah Diterima']],
                    ['key' => 'sort', 'label' => 'Urutkan', 'type' => 'radio', 'default' => 'name', 'options' => ['name' => 'Nama', 'stock' => 'Stok']],
                ],
            ]
        );

        foreach (['Status Penerimaan', 'Sudah Diterima', 'Urutkan', 'role="radiogroup"', 'name="a-sort"', 'Minggu Ini', 'Hari Ini', 'Bulan Ini'] as $text) {
            $this->assertStringContainsString($text, $html, "Hilang: {$text}");
        }
        // Chips set the group value; the group's default decides when its Reset link shows.
        $this->assertStringContainsString("extra['delivery'] = 'received'", $html);
        $this->assertStringContainsString("extra['sort'] !== 'name'", $html);
        $this->assertStringContainsString('\u0022groups\u0022:[{\u0022key\u0022:\u0022delivery\u0022,\u0022default\u0022:\u0022all\u0022}', $html);
    }

    public function test_default_quick_ranges_stay_week_and_today_without_a_month_button(): void
    {
        $html = Blade::render('<x-erp.filter-sheet id="a" title="Filter" :statuses="$statuses" />', ['statuses' => ['all' => 'Semua']]);

        $this->assertStringContainsString("setDateRange('week')", $html);
        $this->assertStringContainsString("setDateRange('today')", $html);
        $this->assertStringNotContainsString('Bulan Ini', $html);
        $this->assertStringNotContainsString('\u0022groups\u0022:[{', $html);
    }
}
