<?php

namespace Tests\Feature;

use App\Http\Controllers\FCMController;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Transaction\Http\Controllers\WholesaleController;
use Tests\TestCase;

class RetiredDangerousEndpointsTest extends TestCase
{
    public function test_global_transaction_reset_has_no_web_route_or_controller_handler(): void
    {
        // Assert retirement before issuing any request: never exercise the old reset.
        $this->assertFalse(Route::has('wholesale.reset'));
        $this->assertFalse(method_exists(WholesaleController::class, 'reset_transactions'));
        // The resource GET /wholesale/{wholesale} still matches this path for other verbs.
        $this->postJson('/wholesale/reset')->assertStatus(405);
    }

    public function test_fcm_broadcast_demo_has_no_route_or_controller_handler(): void
    {
        Http::preventStrayRequests();
        $matchingRoutes = collect(Route::getRoutes())->filter(
            fn ($route) => $route->uri() === 'test-fcm'
        );
        // Never call the old broadcast: fail on the registered route first.
        $this->assertCount(0, $matchingRoutes);
        $this->assertFalse(method_exists(FCMController::class, 'testNotification'));
        $this->getJson('/test-fcm')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_authenticated_token_registration_still_persists_without_sending_notifications(): void
    {
        Http::preventStrayRequests();
        config(['database.default' => 'fcm_token_test', 'database.connections.fcm_token_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id_ru');
            $table->integer('id_user');
            $table->integer('id_role');
        });
        Schema::create('user_devices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('fcm_token')->unique();
            $table->string('device_name');
            $table->timestamps();
        });
        $this->actingAs((new User())->forceFill(['id_user' => 77, 'nm_user' => 'Fixture User']));
        $this->postJson('/api/save-fcm-token', ['fcm_token' => 'fixture-token'])
            ->assertOk()->assertJsonPath('message', 'FCM token saved successfully');
        $this->assertDatabaseHas('user_devices', ['user_id' => 77, 'fcm_token' => 'fixture-token']);
        Http::assertNothingSent();
    }

    public function test_fcm_token_registration_remains_an_authenticated_post_endpoint(): void
    {
        $route = Route::getRoutes()->match(Request::create('/api/save-fcm-token', 'POST'));
        $this->assertSame(FCMController::class . '@store', $route->getActionName());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->postJson('/api/save-fcm-token', ['fcm_token' => 'fixture-token'])->assertUnauthorized();
    }
}
