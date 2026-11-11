<?php

namespace Tests\Feature;

use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

final class StaffFormPickerFilterTest extends TestCase
{
    public function test_position_and_branch_picker_rows_render_a_complete_search_expression(): void
    {
        view()->share('errors', new ViewErrorBag());

        $html = view('master::staff.form-fields', [
            'insideOverlay' => false,
            'positions' => collect([(object) ['id' => 1, 'name' => "Courier Crew"], (object) ['id' => 2, 'name' => "O'Neil <Lead>"]]),
            'branches' => collect([(object) ['id' => 1, 'name' => 'Toko GBA']]),
            'roles' => collect(),
        ])->render();

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $expressions = [];
        foreach ($dom->getElementsByTagName('label') as $label) {
            $show = $label->getAttribute('x-show');
            if (str_contains($show, 'pickerSearch')) {
                $expressions[] = $show;
            }
        }

        $this->assertCount(3, $expressions);
        foreach ($expressions as $expression) {
            // A string from @js() is single-quoted, so it must not sit inside a single-quoted attribute:
            // that cut the expression to "!pickerSearch || " and Alpine threw a syntax error on /staff.
            $this->assertStringContainsString('.includes(pickerSearch.trim().toLocaleLowerCase(', $expression);
            $this->assertStringNotContainsString('||  ', $expression);
        }
    }
}
