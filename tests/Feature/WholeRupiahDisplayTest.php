<?php

namespace Tests\Feature;

use Tests\TestCase;

final class WholeRupiahDisplayTest extends TestCase
{
    public function test_production_summary_shows_the_estimated_cost_in_whole_rupiah(): void
    {
        $js = file_get_contents(base_path('Modules/Transaction/Resources/views/production/js-create.blade.php'));
        $start = strpos($js, 'formatCurrency(amount)');
        $this->assertNotFalse($start);
        $body = substr($js, $start, 260);

        // Intl's IDR default allows two decimals, which rendered "Rp 3.120,8".
        $this->assertStringContainsString("currency: 'IDR'", $body);
        $this->assertStringContainsString('maximumFractionDigits: 0', $body);
    }

    public function test_recipe_detail_no_longer_shows_the_product_cost(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/receipt/index.blade.php'));

        // The recipe screens show no cost any more (it used to be rounded to whole rupiah: "Rp 9.365,301" bug).
        $this->assertStringNotContainsString('selectedRecipe?.product?.hpp', $view);
        $this->assertStringNotContainsString('Harga Pokok Produksi', $view);
    }
}
