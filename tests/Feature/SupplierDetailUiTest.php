<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class SupplierDetailUiTest extends TestCase
{
    public function test_supplier_detail_uses_the_reference_icon_rows_and_link_treatments(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/Modules/Master/Resources/views/partials/mobile-index.blade.php');

        self::assertStringContainsString('@if ($routeBase === \'supplier\')', $view);
        self::assertStringContainsString('border border-emerald-200/80 flex items-center justify-center shrink-0 shadow-2xs"><i class="ph-duotone ph-storefront text-lg"></i>', $view);
        self::assertStringContainsString('Alamat Pemasok (Klik untuk Buka Google Maps)', $view);
        self::assertStringContainsString('text-sm font-bold text-blue-600 hover:text-blue-700', $view);
        self::assertStringContainsString('ph-arrow-up-right', $view);
        self::assertStringContainsString('https://wa.me/', $view);
        self::assertStringContainsString('https://www.google.com/maps/search/', $view);
    }
}
