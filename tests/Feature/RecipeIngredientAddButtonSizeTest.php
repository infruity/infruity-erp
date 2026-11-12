<?php

namespace Tests\Feature;

use Tests\TestCase;

class RecipeIngredientAddButtonSizeTest extends TestCase
{
    public function test_add_ingredient_button_has_the_reference_size_in_both_recipe_forms(): void
    {
        foreach (['index', 'create-form'] as $file) {
            $view = file_get_contents(base_path("Modules/Transaction/Resources/views/receipt/{$file}.blade.php"));

            $this->assertStringContainsString('class="flex items-center gap-1 rounded-lg bg-[#0b595b] px-2.5 py-1 text-[11px] font-semibold text-white shadow-2xs', $view, $file);
            $this->assertStringContainsString('<i class="ph-bold ph-plus text-[10px]"></i><span>Tambah</span>', $view, $file);
            $this->assertStringNotContainsString('h-10 shrink-0 items-center gap-1.5 rounded-xl bg-[#0b595b]', $view, $file);
            $this->assertStringContainsString('<h3 class="block text-xs font-bold text-gray-800">Daftar Bahan Baku', $view, $file);
        }
    }
}
