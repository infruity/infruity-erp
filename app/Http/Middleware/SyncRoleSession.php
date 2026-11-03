<?php

namespace App\Http\Middleware;

use App\Models\RoleMenu;
use Closure;
use Illuminate\Http\Request;

class SyncRoleSession
{
    public function handle(Request $request, Closure $next)
    {
        $role = RoleMenu::currentRole();

        if ($role) {
            $request->session()->put('role', $role->toArray());
        } else {
            $request->session()->forget('role');
        }

        return $next($request);
    }
}
