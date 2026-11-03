<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class SupplierRegionFieldsTest extends TestCase
{
    public function test_supplier_drawer_uses_live_cascading_region_fields_and_persists_their_ids(): void
    {
        $root = dirname(__DIR__, 2);
        $supplierView = file_get_contents($root . '/Modules/Master/Resources/views/supllier/index.blade.php');
        $sharedView = file_get_contents($root . '/Modules/Master/Resources/views/partials/mobile-index.blade.php');
        $controller = file_get_contents($root . '/Modules/Master/Http/Controllers/SupplierController.php');
        $migration = file_get_contents($root . '/database/migrations/2026_10_26_205407_add_supplier_region_fields.php');

        foreach (['province', 'city', 'district', 'village'] as $field) {
            self::assertStringContainsString("'key'=>'{$field}'", $supplierView);
            self::assertStringContainsString("'{$field}'", $controller);
            self::assertStringContainsString("\$table->char('{$field}'", $migration);
            self::assertStringContainsString("\$supplier->{$field} = \$validated['{$field}'] ?? null;", $controller);
        }

        self::assertStringContainsString("field.type === 'region'", $sharedView);
        self::assertStringContainsString('searchRegionOptions', $sharedView);
        self::assertStringContainsString('selectRegionOption', $sharedView);
        self::assertStringContainsString("route('ajax.province')", $sharedView);
        self::assertStringContainsString("route('ajax.city')", $sharedView);
        self::assertStringContainsString("route('ajax.district')", $sharedView);
        self::assertStringContainsString("route('ajax.village')", $sharedView);
        self::assertStringContainsString("params.set('province_id', parentId)", $sharedView);
        self::assertStringContainsString("params.set('city_id', parentId)", $sharedView);
        self::assertStringContainsString("params.set('district_id', parentId)", $sharedView);
    }
}
