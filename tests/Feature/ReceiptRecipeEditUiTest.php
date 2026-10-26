<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class ReceiptRecipeEditUiTest extends TestCase
{
    public function test_edit_drawer_uses_recipe_summary_rows_and_the_ingredient_picker(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/Modules/Transaction/Resources/views/receipt/index.blade.php');
        $start = strpos($view, '<form x-data="recipeForm(');
        self::assertNotFalse($start, 'The recipe edit form should be present.');
        $end = strpos($view, '</form>', $start);
        self::assertNotFalse($end, 'The recipe edit form should be closed.');
        $editForm = substr($view, $start, $end - $start);

        self::assertStringContainsString('@click="openIngredientPicker()"', $editForm);
        self::assertStringContainsString('Total Modal', $editForm);
        self::assertStringContainsString('ingredientCost(ingredient)', $editForm);
        self::assertStringContainsString('@input="updateQuantity(ingredient, $event)"', $editForm);
        self::assertStringContainsString('name="product_receipt_id[]"', $editForm);
        self::assertStringContainsString('name="ingredients_quantity[]"', $editForm);
        self::assertStringNotContainsString('x-model="ingredient.search"', $editForm);
    }
}
