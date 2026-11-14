<?php

namespace Tests\Feature;

use Tests\TestCase;

class PositionListRowReferenceTest extends TestCase
{
    public function test_position_rows_show_only_the_name_like_the_reference(): void
    {
        $position = file_get_contents(base_path('Modules/Master/Resources/views/position/index.blade.php'));
        $shared = file_get_contents(base_path('Modules/Master/Resources/views/partials/mobile-index.blade.php'));

        $this->assertStringContainsString("\$subtitleFields = [];", $position);
        $this->assertStringContainsString('@if (! empty($subtitleFields))<span class="block text-[11px] {{ ! empty($trailingField ?? null) ? \'font-normal\' : \'font-medium\' }} text-gray-400 mt-0.5 truncate{{ in_array($routeBase, [\'branch\', \'kurir\'], true) ? \' leading-4\' : \'\' }}" x-text="subtitle(record)"></span>@endif', $shared);
    }
}
