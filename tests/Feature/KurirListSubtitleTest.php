<?php

namespace Tests\Feature;

use Tests\TestCase;

class KurirListSubtitleTest extends TestCase
{
    public function test_kurir_rows_show_the_type_only_not_the_description(): void
    {
        $view = file_get_contents(base_path('Modules/Master/Resources/views/kurir/index.blade.php'));

        $this->assertStringContainsString("\$subtitleFields = ['type'];", $view);
    }
}
