<?php

namespace Tests\Feature;

use Tests\TestCase;

class RecipeDetailNoCodeTest extends TestCase
{
    public function test_recipe_detail_does_not_show_the_transaction_code(): void
    {
        $view = file_get_contents(base_path('Modules/Transaction/Resources/views/receipt/index.blade.php'));

        $this->assertStringContainsString('title="Detail Resep"', $view);
        $this->assertStringNotContainsString('selectedRecipe?.code', $view);
    }
}
