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

    public function test_recipe_detail_shows_the_product_cost_in_whole_rupiah(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/receipt/index.blade.php'));

        // toLocaleString defaults to three decimals, which rendered "Rp 9.365,301".
        $this->assertStringContainsString("Math.round(Number(selectedRecipe?.product?.hpp || 0)).toLocaleString('id-ID')", $view);
        $this->assertStringNotContainsString("Number(selectedRecipe?.product?.hpp || 0).toLocaleString('id-ID')", $view);
    }
}
