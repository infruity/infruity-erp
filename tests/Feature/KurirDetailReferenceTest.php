<?php

namespace Tests\Feature;

use Tests\TestCase;

class KurirDetailReferenceTest extends TestCase
{
    public function test_kurir_detail_has_the_reference_rows_without_type_staff_or_description(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));
        $block = explode("@else\n", explode("@elseif (\$routeBase === 'kurir')", $view)[1] ?? '')[0];

        $this->assertStringContainsString('<i class="ph-duotone ph-truck text-lg">', $block);
        $this->assertStringContainsString('Nama Kurir', $block);
        $this->assertStringContainsString("'Memperbarui pada ' + formatDetailDate(detail.updated_at || detail.created_at)", $block);
        foreach (['Tipe Kurir', 'Deskripsi', 'Karyawan'] as $gone) {
            $this->assertStringNotContainsString($gone, $block);
        }
    }

    public function test_kurir_list_sends_the_dates(): void
    {
        $controller = file_get_contents(base_path('Modules/Master/Http/Controllers/KurirController.php'));

        $this->assertStringContainsString("'description', 'created_at', 'updated_at')", $controller);
    }
}
