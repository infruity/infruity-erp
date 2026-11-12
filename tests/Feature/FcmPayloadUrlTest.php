<?php

namespace Tests\Feature;

use App\Services\FcmService;
use ReflectionClass;
use Tests\TestCase;

/** A push notification opens the site that sent it (APP_URL), so moving the app to another address needs no code change. */
final class FcmPayloadUrlTest extends TestCase
{
    public function test_the_notification_links_follow_app_url(): void
    {
        config(['app.url' => 'https://new.infruity.com/']);

        $service = (new ReflectionClass(FcmService::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(FcmService::class, 'payload');
        $payload = $method->invoke($service, 'tok', 'Judul', 'Isi', ['k' => 'v']);

        $webpush = $payload['message']['webpush'];
        $this->assertSame('https://new.infruity.com', $webpush['notification']['click_action']);
        $this->assertSame('https://new.infruity.com', $webpush['fcm_options']['link']);
        $this->assertSame('https://new.infruity.com/icon.png', $webpush['notification']['icon']);
    }
}
