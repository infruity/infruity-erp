<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class PositionUniqueNameTest extends TestCase
{
    public function test_position_names_are_unique_case_insensitively_with_the_reference_message(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2) . '/Modules/Master/Http/Controllers/PositionController.php');

        self::assertStringContainsString('uniquePositionName()', $controller);
        self::assertStringContainsString('uniquePositionName((int) $id)', $controller);
        self::assertStringContainsString('LOWER(name) = ?', $controller);
        self::assertStringContainsString("'Jabatan \"' . \$name . '\" sudah ada!'", $controller);
        // Renaming a record to its own current name never trips the rule, so older duplicates stay editable.
        self::assertStringContainsString('whereKeyNot($ignoreId)', $controller);
    }
}
