<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class SupplierDetailUiTest extends TestCase
{
    public function test_supplier_detail_uses_the_reference_icon_rows_and_link_treatments(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/Modules/Master/Resources/views/partials/mobile-index.blade.php');

        self::assertStringContainsString('@if ($routeBase === \'supplier\')', $view);
        self::assertStringContainsString('ph-duotone ph-storefront text-2xl text-emerald-500 shrink-0', $view);
        self::assertStringContainsString("field.key === 'address' ? 'text-blue-600", $view);
        self::assertStringContainsString('ph-arrow-up-right', $view);
        self::assertStringContainsString('https://wa.me/', $view);
        self::assertStringContainsString('https://www.google.com/maps/search/', $view);
    }
}
