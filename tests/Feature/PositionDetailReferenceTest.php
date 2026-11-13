<?php

namespace Tests\Feature;

use Tests\TestCase;

class PositionDetailReferenceTest extends TestCase
{
    public function test_position_detail_has_the_reference_rows_and_no_department(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));
        $block = explode("@elseif (\$routeBase === 'position')", $view)[1] ?? '';
        $block = explode("@else\n", $block)[0];

        $this->assertStringContainsString('<i class="ph-duotone ph-briefcase text-lg">', $block);
        $this->assertStringContainsString('Nama Jabatan', $block);
        $this->assertStringContainsString("'Memperbarui pada ' + formatDetailDate(detail.updated_at || detail.created_at)", $block);
        $this->assertStringNotContainsString('Departemen', $block);
    }

    public function test_position_list_sends_the_editor_and_dates_and_store_update_record_the_editor(): void
    {
        $controller = file_get_contents(base_path('Modules/Master/Http/Controllers/PositionController.php'));

        $this->assertStringContainsString("'editor.nm_user as updated_by_name'", $controller);
        $this->assertSame(2, substr_count($controller, '$position->updated_by = Auth::user()?->id_user;'));
        $this->assertSame(1, substr_count($controller, '$position->created_by = '));
    }
}
