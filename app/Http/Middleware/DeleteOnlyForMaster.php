<?php

namespace App\Http\Middleware;

use App\Models\RoleMenu;
use Closure;
use Illuminate\Http\Request;

/**
 * Every DELETE request (a real DELETE or a form/ajax _method=DELETE) is refused for a signed-in account that is not
 * the master account. The buttons are hidden through RoleMenu::checkAccess; this keeps a hand-made request out too.
 */
class DeleteOnlyForMaster
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('DELETE') && $request->user() && ! RoleMenu::isMasterAccount()) {
            $message = 'Hanya akun master yang dapat menghapus data.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return back()->with('error', $message);
        }

        return $next($request);
    }
}
