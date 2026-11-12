<?php

namespace Tests\Feature;

use Tests\TestCase;

class UnitBadgeColoursTest extends TestCase
{
    public function test_unit_badges_use_the_ten_reference_tints_instead_of_one_green(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/unit/index.blade.php'));

        $this->assertStringContainsString(':class="theme(unit)"', $view);
        $this->assertStringNotContainsString('rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700', $view);
        foreach (['emerald', 'blue', 'purple', 'amber', 'rose', 'indigo', 'sky', 'fuchsia', 'teal', 'orange'] as $colour) {
            $this->assertStringContainsString("'bg-{$colour}-50 text-{$colour}-600 border-{$colour}-100'", $view, $colour);
        }
    }
}
