<?php

/*
 * Reverse proxies whose X-Forwarded-* headers are believed (read by Laravel's TrustProxies middleware).
 * Empty (default): trust none, so $request->ip() is the address that connected to PHP. Behind nginx / Cloudflare
 * on the same server that is the proxy itself, which makes per-IP features (the login throttle) treat every
 * visitor as one. Set TRUSTED_PROXIES to the proxy address(es), comma separated (e.g. 127.0.0.1), or to '*' to
 * trust whatever connects (only when PHP is reachable exclusively through the proxy).
 */
$proxies = env('TRUSTED_PROXIES');

return [
    'proxies' => ($proxies === null || $proxies === '')
        ? null
        : (in_array($proxies, ['*', '**'], true) ? $proxies : array_values(array_filter(array_map('trim', explode(',', $proxies))))),
];
