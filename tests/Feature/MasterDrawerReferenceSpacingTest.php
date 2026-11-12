<?php

namespace Tests\Feature;

use Tests\TestCase;

class MasterDrawerReferenceSpacingTest extends TestCase
{
    public function test_master_drawer_body_has_no_stray_gap_under_the_header_and_hides_the_native_scrollbar(): void
    {
        $shared = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));

        // space-y-* counted the <template> before the first field as a sibling: +20px between header and first label.
        $this->assertStringNotContainsString("'px-5 py-5 space-y-5'", $shared);
        $this->assertStringContainsString('overscroll-none scrollbar-hide', $shared);
        $this->assertStringContainsString('bg-gray-400/50', $shared, 'custom scrollbar thumb like the product drawer');
        $this->assertStringContainsString(':placeholder="field.placeholder || \'\'"', $shared, 'textarea placeholders');
    }

    public function test_supplier_placeholders_follow_the_reference(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/supllier/index.blade.php'));

        foreach (['Nama orang yang bisa dihubungi (PIC)', 'Contoh: 6281234567890', 'contoh@email.com', 'Jalan, RT/RW, dsb'] as $placeholder) {
            $this->assertStringContainsString($placeholder, $view);
        }
    }
}
