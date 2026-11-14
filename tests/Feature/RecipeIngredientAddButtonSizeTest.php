<?php

namespace Tests\Feature;

use Tests\TestCase;

class RecipeIngredientAddButtonSizeTest extends TestCase
{
    public function test_add_ingredient_button_is_plain_text_like_the_pos_add_button_in_both_recipe_forms(): void
    {
        foreach (['index', 'create-form'] as $file) {
            $view = file_get_contents(base_path("Modules/Transaction/Resources/views/receipt/{$file}.blade.php"));

            $this->assertStringContainsString('text-xs font-bold text-[#0b595b] outline-none transition hover:text-[#08484a]"><span>+ Tambah</span>', $view, $file);
            $this->assertStringNotContainsString('rounded-lg bg-[#0b595b] px-2.5 py-1 text-[11px]', $view, $file);
            $this->assertStringNotContainsString('h-10 shrink-0 items-center gap-1.5 rounded-xl bg-[#0b595b]', $view, $file);
            $this->assertStringContainsString('<h3 class="block text-sm font-extrabold tracking-tight text-gray-900">Daftar Bahan Baku', $view, $file);
        }
    }
}
