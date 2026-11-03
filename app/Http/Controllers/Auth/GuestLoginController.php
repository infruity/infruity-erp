<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\UseGuestDatabase;
use App\Models\RoleMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuestLoginController extends Controller
{
    public function store(Request $request)
    {
        if (! UseGuestDatabase::isIsolated() || config('session.driver') === 'database') {
            return redirect()->route('login')->with('error', 'Database demo Guest belum tersedia.');
        }

        try {
            $marker = DB::connection('guest')->table('guest_installation')->where('id', 1)->value('database_name');
            if ($marker !== config('database.connections.guest.database')) {
                return redirect()->route('login')->with('error', 'Database demo Guest tidak valid.');
            }
            $guestId = DB::connection('guest')->table('users')
                ->where('username', 'guest')
                ->where('is_aktif', 1)
                ->value('id_user');
        } catch (\Throwable $exception) {
            report($exception);
            return redirect()->route('login')->with('error', 'Database demo Guest belum siap.');
        }

        if (! $guestId) {
            return redirect()->route('login')->with('error', 'Akun Guest belum tersedia di database demo.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('guest_mode', true);
        DB::setDefaultConnection('guest');

        if (! Auth::loginUsingId($guestId) || ! RoleMenu::currentRole()) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->with('error', 'Akun Guest tidak memiliki akses demo.');
        }

        $request->session()->regenerate();

        return redirect('/dashboard');
    }
}
