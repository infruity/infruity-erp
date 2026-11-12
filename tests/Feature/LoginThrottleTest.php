<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * Brute-force protection on POST /auth/login (App\Support\LoginThrottle): 5 failures per minute per account+IP,
 * 15 per 15 minutes per account (any IP), 30 per minute per IP. Only failures count. Run: phpunit.mysql.xml
 */
final class LoginThrottleTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private object $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        $this->user = $this->makeUser($this->makeRole('Kasir'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function attempt(?string $identifier = null, string $password = 'qa-password', string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/auth/login', ['email' => $identifier ?? $this->user->username, 'password' => $password]);
    }

    private function wrongPassword(int $times, ?string $identifier = null, string $ip = '10.0.0.1'): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->attempt($identifier, 'salah', $ip)->assertSessionHas('error', 'Username atau Password tidak terdaftar');
        }
    }

    private function assertLocked($response): void
    {
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('error'));
        $this->assertGuest();
    }

    private function logout(): void
    {
        Auth::logout();
        $this->flushSession();
    }

    public function test_the_sixth_failed_attempt_in_a_minute_locks_the_account_even_for_the_right_password(): void
    {
        $this->wrongPassword(5);

        $this->assertLocked($this->attempt());
    }

    public function test_five_failures_still_allow_the_right_password(): void
    {
        $this->wrongPassword(4);

        $this->attempt()->assertRedirect('dashboard');
        $this->assertAuthenticated();
    }

    public function test_the_message_says_how_long_to_wait(): void
    {
        $this->wrongPassword(5);
        $this->attempt();

        $this->assertMatchesRegularExpression('/dalam (\d+) detik/', session('error'));
        preg_match('/dalam (\d+) detik/', session('error'), $m);
        $this->assertGreaterThan(0, (int) $m[1]);
        $this->assertLessThanOrEqual(60, (int) $m[1]);
    }

    public function test_the_lock_ends_after_a_minute(): void
    {
        $this->wrongPassword(5);
        $this->assertLocked($this->attempt());

        Carbon::setTestNow(now()->addSeconds(61));

        $this->attempt()->assertRedirect('dashboard');
    }

    public function test_another_account_on_the_same_ip_is_not_locked(): void
    {
        $other = $this->makeUser($this->makeRole('Gudang'));
        $this->wrongPassword(5);

        $this->attempt($other->username)->assertRedirect('dashboard');
    }

    public function test_the_same_account_from_another_ip_is_not_locked_by_the_short_limit(): void
    {
        $this->wrongPassword(5, null, '10.0.0.1');

        $this->attempt(null, 'qa-password', '10.0.0.2')->assertRedirect('dashboard');
    }

    public function test_a_successful_login_resets_the_counters_of_that_account(): void
    {
        $this->wrongPassword(4);
        $this->attempt()->assertRedirect('dashboard');
        $this->logout();

        $this->wrongPassword(4);
        $this->attempt()->assertRedirect('dashboard');
    }

    public function test_the_account_is_limited_across_many_ips(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->wrongPassword(1, null, "10.1.0.{$i}");
        }

        $this->assertLocked($this->attempt(null, 'qa-password', '10.1.0.200'));
    }

    public function test_thirty_failures_from_one_ip_lock_that_ip_but_twenty_nine_do_not(): void
    {
        for ($i = 1; $i <= 29; $i++) {
            $this->wrongPassword(1, "nobody{$i}", '10.2.0.1');
        }
        $this->attempt(null, 'qa-password', '10.2.0.1')->assertRedirect('dashboard');   // 29 failures: still open
        $this->logout();

        // A successful login never clears the per-IP counter, so one more failure makes it 30.
        $this->wrongPassword(1, 'nobody30', '10.2.0.1');

        $this->assertLocked($this->attempt(null, 'qa-password', '10.2.0.1'));
    }

    public function test_case_and_spaces_in_the_username_do_not_dodge_the_limit(): void
    {
        $this->wrongPassword(2, strtoupper($this->user->username));
        $this->wrongPassword(3, ' ' . $this->user->username . ' ');

        $this->assertLocked($this->attempt());
    }

    public function test_empty_submissions_do_not_count(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->post('/auth/login', [])->assertSessionHasErrors(['email', 'password']);
        }

        $this->attempt()->assertRedirect('dashboard');
    }

    public function test_a_forged_forwarded_for_header_cannot_dodge_the_limit_when_no_proxy_is_trusted(): void
    {
        $this->assertNull(config('trustedproxy.proxies'), 'default: no proxy is trusted');
        $this->wrongPassword(5);

        $forged = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->withHeader('X-Forwarded-For', '203.0.113.77')
            ->post('/auth/login', ['email' => $this->user->username, 'password' => 'qa-password']);

        $this->assertLocked($forged);
    }

    public function test_behind_a_trusted_proxy_each_visitor_is_counted_by_the_forwarded_address(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.1']);   // the proxy that connects to PHP
        $post = fn (string $visitor, string $password) => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeader('X-Forwarded-For', $visitor)
            ->post('/auth/login', ['email' => $this->user->username, 'password' => $password]);

        for ($i = 0; $i < 5; $i++) {
            $post('203.0.113.10', 'salah');
        }

        $this->assertLocked($post('203.0.113.10', 'qa-password'));
        $post('203.0.113.99', 'qa-password')->assertRedirect('dashboard');
    }
}
