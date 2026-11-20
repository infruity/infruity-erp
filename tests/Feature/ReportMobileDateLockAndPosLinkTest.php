<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\ReportController;
use Tests\TestCase;

class ReportMobileDateLockAndPosLinkTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(base_path('Modules/Report/Http/Controllers/MobileReportController.php'));
    }

    public function test_per_date_endpoints_respect_the_report_lock(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('abort_if(ReportController::isLocked(self::INDEX_ROUTES[$type]), 403);', $source);
        // The lock check runs before the permission check, so a locked report answers 403 even to admins.
        $this->assertLessThan(
            strpos($source, '$this->authorizeAccess(self::PERMISSIONS[$type]);'),
            strpos($source, 'abort_if(ReportController::isLocked(')
        );
        $this->assertTrue(ReportController::isLocked('report-profit-adjusted.index'));
    }

    public function test_every_report_type_maps_to_an_existing_page_route(): void
    {
        $reflection = new \ReflectionClass(\Modules\Report\Http\Controllers\MobileReportController::class);
        $routes = $reflection->getConstant('INDEX_ROUTES');
        $permissions = $reflection->getConstant('PERMISSIONS');

        $this->assertSame(array_keys($permissions), array_keys($routes));
        foreach ($routes as $type => $name) {
            $this->assertTrue(Route::has($name), "Missing page route for {$type}: {$name}");
        }
        $this->assertSame('report-profit-adjusted.index', $routes['adjusted']);
    }

    public function test_per_date_history_sends_the_sale_id_for_the_receipt_link(): void
    {
        $this->assertStringContainsString("'pos_id' => \$transaction?->id,", $this->source());
    }
}
