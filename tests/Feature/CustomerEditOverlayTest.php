<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class CustomerEditOverlayTest extends TestCase
{
    public function test_customer_create_entry_renders_the_list_with_the_add_drawer_open(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/Modules/Master/Http/Controllers/CustomerController.php');
        self::assertStringContainsString("->with('customerCreateOpen', true)", $controller);

        $sharedView = file_get_contents($root . '/Modules/Master/Resources/views/partials/mobile-index.blade.php');
        $createOverlay = file_get_contents($root . '/Modules/Master/Resources/views/customer/create-overlay.blade.php');
        self::assertStringContainsString("@include('master::customer.create-overlay')", $sharedView);
        self::assertStringContainsString("route('customers.store')", $createOverlay);
        self::assertStringContainsString('customerCreateOpen', $createOverlay);
        self::assertStringContainsString('history.pushState', $sharedView);
        self::assertStringContainsString('regionEndpoints, createIndexUrl, createPageUrl', $sharedView);
        self::assertStringContainsString('@mobile-add-toggle.window="openCreate()"', $sharedView);
    }

    public function test_customer_edit_from_list_uses_an_in_place_record_seeded_drawer(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/Modules/Master/Http/Controllers/CustomerController.php');
        $sharedView = file_get_contents($root . '/Modules/Master/Resources/views/partials/mobile-index.blade.php');
        $drawer = file_get_contents($root . '/Modules/Master/Resources/views/customer/edit-overlay.blade.php');
        $formScript = file_get_contents($root . '/Modules/Master/Resources/views/customer/js-create.blade.php');

        self::assertStringContainsString('async openCustomerEdit(record)', $sharedView);
        self::assertStringContainsString("if (\$request->boolean('overlay'))", $controller);
        self::assertStringContainsString('customerEditOpen', $drawer);
        self::assertStringContainsString('window.initCustomerForms', $formScript);
        self::assertStringContainsString('window.history.pushState', $sharedView);
    }

    public function test_edit_route_renders_the_customer_list_with_a_persisted_edit_drawer(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/Modules/Master/Http/Controllers/CustomerController.php');
        $index = file_get_contents($root . '/Modules/Master/Resources/views/customer/index.blade.php');
        $sharedView = file_get_contents($root . '/Modules/Master/Resources/views/partials/mobile-index.blade.php');
        $drawer = file_get_contents($root . '/Modules/Master/Resources/views/customer/edit-overlay.blade.php');
        $fields = file_get_contents($root . '/Modules/Master/Resources/views/customer/form-fields.blade.php');
        $create = file_get_contents($root . '/Modules/Master/Resources/views/customer/create.blade.php');
        $migration = file_get_contents($root . '/database/migrations/2026_10_26_214233_add_birth_place_to_customer_table.php');

        self::assertStringContainsString("view('master::customer.index'", $controller);
        self::assertStringContainsString('editingCustomer', $controller);
        self::assertStringContainsString("@include('master::customer.edit-overlay',", $sharedView);
        self::assertStringContainsString('data-erp-modal', $drawer);
        self::assertStringContainsString("route('customers.update', \$data->id)", $drawer);
        self::assertStringContainsString('@method(\'PUT\')', $drawer);
        self::assertStringContainsString("@include('master::customer.form-fields')", $drawer);
        self::assertStringContainsString("@include('master::customer.form-fields')", $create);
        self::assertStringContainsString('name="birth_place"', $fields);
        self::assertStringContainsString("'birth_place' => 'nullable|string|max:255'", $controller);
        self::assertStringContainsString('$customer->birth_place = $request->birth_place;', $controller);
        self::assertSame(2, substr_count($controller, '$customer->birth_place = $request->birth_place;'));
        self::assertStringContainsString("\$table->string('birth_place')->nullable()", $migration);
        self::assertStringContainsString("\$table->dropColumn('birth_place')", $migration);
    }
}
