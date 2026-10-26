<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class BranchOverlayFieldsTest extends TestCase
{
    public function test_branch_add_and_edit_drawer_use_persisted_region_and_map_fields(): void
    {
        $root = dirname(__DIR__, 2);
        $branchView = file_get_contents($root . '/Modules/Master/Resources/views/branch/index.blade.php');
        $sharedView = file_get_contents($root . '/Modules/Master/Resources/views/partials/mobile-index.blade.php');
        $controller = file_get_contents($root . '/Modules/Master/Http/Controllers/BranchController.php');
        $migrationPath = $root . '/database/migrations/2026_10_26_233900_add_branch_region_and_map_fields.php';

        self::assertFileExists($migrationPath);

        foreach (['province', 'city', 'district', 'village'] as $field) {
            self::assertStringContainsString("'key'=>'{$field}'", $branchView);
            self::assertStringContainsString("\$branch->{$field} = \$validated['{$field}'];", $controller);
            self::assertStringContainsString("'{$field}'", file_get_contents($migrationPath));
        }

        self::assertStringNotContainsString("'key'=>'name'", $branchView);
        self::assertStringNotContainsString("'key'=>'code'", $branchView);
        self::assertStringContainsString('generateBranchIdentity', $controller);
        self::assertStringContainsString('CBG-', $controller);
        self::assertStringContainsString("'key'=>'map_link'", $branchView);
        self::assertStringContainsString("\$branch->map_link = \$validated['map_link'] ?? null;", $controller);
        self::assertStringContainsString('closeDrawer()', $sharedView);
        self::assertStringContainsString("searchParams.delete('create')", $sharedView);
        self::assertStringContainsString("@js(in_array(\$routeBase, ['supplier', 'branch'], true)", $sharedView);
        self::assertStringContainsString("'province' => route('ajax.province')", $sharedView);
        self::assertStringContainsString("'city' => route('ajax.city')", $sharedView);
        self::assertStringContainsString("'district' => route('ajax.district')", $sharedView);
        self::assertStringContainsString("'village' => route('ajax.village')", $sharedView);
        self::assertStringContainsString('province_name', $controller);
        self::assertStringContainsString('city_name', $controller);
        self::assertStringContainsString('district_name', $controller);
        self::assertStringContainsString('village_name', $controller);
    }
}
