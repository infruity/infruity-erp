<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * products.hpp is fractional in the database (backend math uses the fraction). The screens must show whole rupiah.
 * Found in the legacy import rehearsal: production cost columns used toNumber() (2 decimals) and one JS cell used
 * Intl.NumberFormat without a fraction limit.
 */
final class HppDisplayRoundingTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    public function test_the_whole_rupiah_helper_rounds_a_fractional_cost(): void
    {
        $this->assertSame('5.083', tonumberround(5083.3928718449));
        $this->assertSame('244.003', tonumberround(244002.86));
    }

    public function test_production_list_cost_column_is_displayed_as_whole_rupiah(): void
    {
        $this->assertStringContainsString("'Rp' . tonumberround(\$item->products->hpp * \$item->quantity)", $this->source('Modules/Transaction/Http/Controllers/ProductionController.php'));
        $this->assertStringNotContainsString("'Rp' . toNumber(\$item->products->hpp", $this->source('Modules/Transaction/Http/Controllers/ProductionController.php'));
    }

    public function test_parcel_production_list_cost_column_is_displayed_as_whole_rupiah(): void
    {
        $this->assertStringContainsString("'Rp' . tonumberround(\$item->product->hpp * \$item->quantity)", $this->source('Modules/Transaction/Http/Controllers/ProductionParcelController.php'));
    }

    public function test_production_ingredient_cost_cell_limits_fraction_digits(): void
    {
        $js = $this->source('Modules/Transaction/Resources/views/production/js-create.blade.php');

        $this->assertStringContainsString("new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(ingredient.hpp || 0)", $js);
        $this->assertStringNotContainsString("new Intl.NumberFormat('id-ID').format(ingredient.hpp)", $js);
    }

    public function test_the_value_sent_to_the_backend_stays_unrounded(): void
    {
        $js = $this->source('Modules/Transaction/Resources/views/production/js-create.blade.php');

        $this->assertStringContainsString('name="ingredients[${index}][hpp]" value="${ingredient.hpp}"', $js);
    }
}
