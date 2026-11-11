<?php

namespace Tests\Feature;

use Tests\TestCase;

final class OrderBookToolbarStickyTest extends TestCase
{
    public function test_mobile_order_book_toolbar_stays_pinned_while_the_main_content_scrolls(): void
    {
        $view = file_get_contents(base_path('Modules/Pos/Resources/views/order-book/index.blade.php'));
        $mobileStart = strpos($view, '@media (max-width:767px) {');
        $styleEnd = strpos($view, '</style>', $mobileStart);
        $mobileStyles = substr($view, $mobileStart, $styleEnd - $mobileStart);

        $this->assertStringContainsString(
            '.order-book-workspace .erp-workspace-toolbar { position:sticky; top:0; z-index:25; background:#fff; }',
            $mobileStyles
        );

        $shell = file_get_contents(base_path('resources/views/template/root.blade.php'));
        $this->assertStringContainsString(
            '<main id="kt_content" class="erp-main flex min-h-0 flex-1 flex-col overflow-y-auto',
            $shell
        );
    }
}
