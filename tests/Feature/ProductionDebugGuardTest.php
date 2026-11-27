<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

/** Debugging stays off on the live site whatever its .env says (finding P-05). */
class ProductionDebugGuardTest extends TestCase
{
    private function registerIn(string $environment): void
    {
        config(['app.debug' => true, 'debugbar.enabled' => true]);
        $this->app['env'] = $environment;
        (new AppServiceProvider($this->app))->register();
    }

    public function test_production_never_runs_with_debugging_on(): void
    {
        $this->registerIn('production');

        $this->assertFalse(config('app.debug'));
        $this->assertFalse(config('debugbar.enabled'));
    }

    public function test_other_environments_keep_their_own_setting(): void
    {
        $this->registerIn('local');

        $this->assertTrue(config('app.debug'));
    }
}
