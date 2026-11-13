<?php

namespace Tests\Feature;

use Tests\TestCase;

class BranchDetailReferenceTest extends TestCase
{
    public function test_branch_detail_has_the_reference_rows(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));

        $this->assertStringContainsString("@elseif (\$routeBase === 'branch')", $view);
        foreach (['ph-storefront', 'ph-map-pin', 'ph-clock'] as $icon) {
            $this->assertStringContainsString('<i class="ph-duotone '.$icon.' text-lg">', $view, $icon);
        }
        foreach (['Nama Lengkap Cabang', 'Alamat Cabang (Klik untuk Buka Google Maps)', "'Memperbarui pada ' + formatDetailDate(detail.updated_at || detail.created_at)"] as $text) {
            $this->assertStringContainsString($text, $view, $text);
        }
    }

    public function test_branch_list_sends_the_editor_and_dates_and_store_update_record_the_editor(): void
    {
        $controller = file_get_contents(base_path('Modules/Master/Http/Controllers/BranchController.php'));

        $this->assertStringContainsString("'editor.nm_user as updated_by_name'", $controller);
        $this->assertStringContainsString("'branch.updated_at'", $controller);
        $this->assertSame(2, substr_count($controller, '$branch->updated_by = \Illuminate\Support\Facades\Auth::user()?->id_user;'));
        $this->assertSame(1, substr_count($controller, '$branch->created_by = '));
    }
}
