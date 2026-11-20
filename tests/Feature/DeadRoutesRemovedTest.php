<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * These routes answered 500 (missing view, missing method or a table of the old template) and
 * nothing in the UI links to them, so they were removed instead of repaired.
 */
class DeadRoutesRemovedTest extends TestCase
{
    public function test_create_pages_without_a_view_are_not_routable(): void
    {
        foreach (['stock-opname', 'campaign', 'deposito', 'handling', 'point-schedule', 'department', 'location', 'setting-exp', 'setting-nota'] as $resource) {
            $this->assertFalse(Route::has($resource . '.create'), "{$resource}.create must not be registered");

            // The modal flows still need the rest of the resource.
            foreach (['index', 'store', 'edit', 'update', 'destroy'] as $action) {
                $this->assertTrue(Route::has($resource . '.' . $action), "{$resource}.{$action} must stay registered");
            }
        }
    }

    public function test_exports_without_an_action_and_the_legacy_permohonan_page_are_gone(): void
    {
        $this->assertFalse(Route::has('export-department-data'));
        $this->assertFalse(Route::has('export-position-data'));
        $this->assertFalse(Route::has('admin.module'));
        $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->uri());
        $this->assertFalse($uris->contains('list-permohonan'));
        $this->assertFalse($uris->contains('department/export'));
        $this->assertFalse($uris->contains('position/export'));
    }
}
