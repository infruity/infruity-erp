<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Hides pages that are switched off in config/features.php: the routes stay registered (and the code and
 * data stay in place) but answer 404 until the flag is turned on again.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        abort_unless(config("features.{$feature}", false), 404);

        return $next($request);
    }
}
