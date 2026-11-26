<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** A page visit must never push a notification to every registered device. */
class NoBroadcastTestRouteTest extends TestCase
{
    public function test_the_fcm_test_broadcast_route_is_gone(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map->uri();

        $this->assertNotContains('test-fcm', $uris->all());
        $this->get('/test-fcm')->assertNotFound();
        // Registering a device token stays.
        $this->assertContains('api/save-fcm-token', $uris->all());
    }
}
