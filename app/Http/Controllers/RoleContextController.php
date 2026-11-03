<?php

namespace App\Http\Controllers;

use App\Models\RoleUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleContextController extends Controller
{
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:role,id_role'],
        ]);

        $assignment = RoleUser::with('role')
            ->where('id_user', Auth::id())
            ->where('id_role', $validated['role_id'])
            ->first();

        abort_unless($assignment?->role, 403, 'Role ini tidak ditugaskan ke akun Anda.');

        $request->session()->put('role', $assignment->role->toArray());

        return redirect()->back();
    }
}
