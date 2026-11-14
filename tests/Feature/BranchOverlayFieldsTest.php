<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class BranchOverlayFieldsTest extends TestCase
{
    public function test_branch_form_asks_for_name_address_and_map_link_only(): void
    {
        $root = dirname(__DIR__, 2);
        $branchView = file_get_contents($root . '/Modules/Master/Resources/views/branch/index.blade.php');
        $controller = file_get_contents($root . '/Modules/Master/Http/Controllers/BranchController.php');

        foreach (['name', 'address', 'map_link'] as $field) {
            self::assertStringContainsString("'key'=>'{$field}'", $branchView);
        }
        foreach (['province', 'city', 'district', 'village', 'code'] as $field) {
            self::assertStringNotContainsString("'key'=>'{$field}'", $branchView);
        }

        // The region columns stay in the table but are no longer asked for or required.
        self::assertStringNotContainsString("'province' => ['required'", $controller);
        self::assertStringNotContainsString('generateBranchIdentity', $controller);
        self::assertStringContainsString('generateBranchCode', $controller);
        self::assertStringContainsString('CBG-', $controller);
        self::assertStringContainsString("\$branch->name = trim(\$validated['name']);", $controller);
        self::assertStringContainsString("\$branch->map_link = \$validated['map_link'] ?? null;", $controller);
    }

    public function test_branch_detail_shows_a_linked_address_on_a_single_line(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/Modules/Master/Resources/views/partials/mobile-index.blade.php');

        self::assertStringContainsString('inline-flex max-w-full items-center gap-1 transition-colors cursor-pointer"><span class="truncate" x-text="detail.address">', $view);
        // Without a link the address may wrap.
        self::assertStringContainsString('text-sm font-bold text-gray-800 leading-snug break-words" x-text="detail.address"', $view);
    }
}
