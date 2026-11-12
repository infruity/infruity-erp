<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Brute-force protection for the login form. Only FAILED logins count; a successful login clears the counters
 * of that account, so a cashier changing shift is never held back.
 *
 *  - per account + IP : 5 failures per minute          -> locked for the rest of that minute
 *  - per account      : 15 failures per 15 minutes     -> locked for the rest of those 15 minutes, from any IP
 *  - per IP           : 30 failures per minute         -> deliberately loose: one shop (one IP) has many cashiers
 *
 * While a lock is active even the correct password is refused (otherwise the lock would not stop a guesser).
 * Counters live in the cache store (CACHE_DRIVER); behind a reverse proxy set TRUSTED_PROXIES so that the IP
 * seen here is the visitor's, not the proxy's (config/trustedproxy.php).
 */
final class LoginThrottle
{
    public const ACCOUNT_IP = ['max' => 5, 'seconds' => 60];
    public const ACCOUNT = ['max' => 15, 'seconds' => 900];
    public const IP = ['max' => 30, 'seconds' => 60];

    /** Seconds until the visitor may try again, or null when they may try now. */
    public static function lockedFor(string $identifier, string $ip): ?int
    {
        $wait = 0;
        foreach (self::buckets($identifier, $ip) as [$key, $limit]) {
            if (RateLimiter::tooManyAttempts($key, $limit['max'])) {
                $wait = max($wait, RateLimiter::availableIn($key));
            }
        }

        return $wait > 0 ? $wait : null;
    }

    public static function recordFailure(string $identifier, string $ip): void
    {
        foreach (self::buckets($identifier, $ip) as [$key, $limit]) {
            RateLimiter::hit($key, $limit['seconds']);
        }
    }

    /** After a successful login: forget this account's failures. The per-IP counter is left alone. */
    public static function recordSuccess(string $identifier, string $ip): void
    {
        [$accountIp, $account] = self::buckets($identifier, $ip);
        RateLimiter::clear($accountIp[0]);
        RateLimiter::clear($account[0]);
    }

    /** @return array<int, array{0: string, 1: array{max:int, seconds:int}}> */
    private static function buckets(string $identifier, string $ip): array
    {
        $account = md5(mb_strtolower(trim($identifier)));

        return [
            ["login:acct-ip:{$account}|{$ip}", self::ACCOUNT_IP],
            ["login:acct:{$account}", self::ACCOUNT],
            ["login:ip:{$ip}", self::IP],
        ];
    }
}
