<?php

namespace Tests\Feature;

use Tests\TestCase;

class UnitDetailReferenceTest extends TestCase
{
    public function test_unit_detail_has_the_reference_tiles_and_the_updated_by_row(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/unit/index.blade.php'));

        $this->assertStringNotContainsString('detail.description', $view, 'no description block in the detail dialog');
        $this->assertSame(3, substr_count($view, 'w-10 h-10 rounded-xl bg-emerald-50 text-[#0b595b] border border-emerald-200/80'), 'three 40px tiles');
        foreach (['ph-tag', 'ph-text-aa', 'ph-clock'] as $icon) {
            $this->assertStringContainsString('<i class="ph-duotone '.$icon.' text-lg">', $view);
        }
        $this->assertStringContainsString("detail.updatedBy || 'Administrator'", $view);
        $this->assertStringContainsString("'Memperbarui pada ' + formatDate(detail.updatedAt || detail.createdAt)", $view);
    }

    public function test_the_controller_sends_who_and_when_and_records_the_editor(): void
    {
        $controller = file_get_contents(base_path('Modules/Master/Http/Controllers/ProductUnitController.php'));

        $this->assertStringContainsString("'updatedBy' => \$unit->updater?->name", $controller);
        $this->assertSame(2, substr_count($controller, '$unit->updated_by = Auth::user()?->id_user;'), 'store and update');
    }
}
