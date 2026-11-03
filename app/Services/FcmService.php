<?php

namespace App\Services;

use App\Models\UserDevice;
use Google\Client as GoogleClient;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /** Requests sent at the same time. */
    private const CONCURRENCY = 30;

    protected $projectId;
    protected $credentialsPath;

    public function __construct()
    {
        $credentials = env('GOOGLE_APPLICATION_CREDENTIALS');
        $this->credentialsPath = ($credentials && is_file(base_path($credentials))) ? base_path($credentials) : null;
        $this->projectId = env('FCM_PROJECT_ID');
    }

    /**
     * Send after the HTTP response has been returned to the user, so saving a
     * form never waits on Firebase. Under PHP-FPM the browser gets its answer first.
     */
    public function sendAfterResponse(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        app()->terminating(function () use ($tokens, $title, $body, $data) {
            try {
                $this->sendNotification($tokens, $title, $body, $data);
            } catch (\Throwable $e) {
                Log::error('❌ FCM gagal dikirim: ' . $e->getMessage());
            }
        });
    }

    public function sendNotification(array $tokens, string $title, string $body, array $data = []): bool
    {
        if (! $this->credentialsPath) {
            Log::error('FCM credentials are not configured.');
            return false;
        }

        if (empty($tokens)) {
            Log::warning('⚠️ Tidak ada token FCM yang dikirim.');
            return false;
        }

        $accessToken = $this->accessToken();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $invalidTokens = [];

        // Send each chunk concurrently instead of one request after another.
        foreach (array_chunk(array_values($tokens), self::CONCURRENCY) as $batch) {
            $responses = Http::pool(function (Pool $pool) use ($batch, $accessToken, $url, $title, $body, $data) {
                foreach ($batch as $token) {
                    $pool->withToken($accessToken)
                        ->timeout(10)
                        ->asJson()
                        ->post($url, $this->payload($token, $title, $body, $data));
                }
            });

            foreach ($batch as $i => $token) {
                $response = $responses[$i] ?? null;

                if ($response instanceof \Throwable) {
                    Log::error("❌ Gagal kirim ke {$token}: " . $response->getMessage());
                    continue;
                }

                if ($response && $response->failed()) {
                    $error = $response->json();
                    $code = $error['error']['details'][0]['errorCode'] ?? null;

                    if ($code === 'UNREGISTERED') {
                        $invalidTokens[] = $token;
                        Log::warning("🧹 Token invalid ditemukan: {$token}");
                    } else {
                        Log::error('❌ FCM Error: ' . json_encode($error, JSON_UNESCAPED_SLASHES));
                    }
                }
            }
        }

        if (! empty($invalidTokens)) {
            UserDevice::whereIn('fcm_token', $invalidTokens)->delete();
            Log::info('🧹 Token invalid dihapus dari database.', ['count' => count($invalidTokens)]);
        }

        Log::info('🎯 FCM broadcast selesai.', ['total_tokens' => count($tokens)]);
        return true;
    }

    /**
     * Google OAuth tokens live for 1 hour; reuse one instead of signing a new
     * JWT and calling Google on every send.
     */
    protected function accessToken(): string
    {
        $cacheKey = 'fcm_access_token:' . md5($this->credentialsPath);

        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $client = new GoogleClient();
        $client->setAuthConfig($this->credentialsPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $result = $client->fetchAccessTokenWithAssertion();

        $token = $result['access_token'];
        $ttl = max(60, (int) ($result['expires_in'] ?? 3600) - 300);
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    protected function payload(string $token, string $title, string $body, array $data): array
    {
        return [
            'message' => [
                'token' => $token,
                'data' => (object) $data,
                'webpush' => [
                    'notification' => [
                        'title' => (string) $title,
                        'body' => (string) $body,
                        'icon' => 'https://infruity.com/icon.png',
                        'click_action' => 'https://infruity.com',
                    ],
                    'fcm_options' => [
                        'link' => 'https://infruity.com',
                    ],
                ],
            ],
        ];
    }
}
