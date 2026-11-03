<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UseGuestDatabase
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->get('guest_mode') === true) {
            abort_unless(self::isIsolated(), 503, 'Database Guest belum dikonfigurasi secara terpisah.');
            abort_if(config('session.driver') === 'database', 503, 'Sesi Guest memerlukan penyimpanan sesi di luar database utama.');

            try {
                $marker = DB::connection('guest')->table('guest_installation')->where('id', 1)->value('database_name');
            } catch (\Throwable $exception) {
                abort(503, 'Database Guest belum siap.');
            }
            abort_unless($marker === config('database.connections.guest.database'), 503, 'Database Guest tidak valid.');

            DB::setDefaultConnection('guest');
        }

        return $next($request);
    }

    public static function isIsolated(): bool
    {
        $primary = config('database.connections.mysql');
        $guest = config('database.connections.guest');

        return ! empty($guest['database'])
            && strtolower((string) $guest['database']) !== strtolower((string) ($primary['database'] ?? ''))
            && (string) ($guest['host'] ?? '') !== ''
            && (string) ($guest['username'] ?? '') !== '';
    }
}
