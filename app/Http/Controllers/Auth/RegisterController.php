<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\RoleUser;
use App\Models\User;
use DB;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Redirect;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    protected function create(UserRequest $request)
    {
        try {
            // save to user
            DB::beginTransaction();
            $user = new User();
            $user->username = $request->email;
            $user->email = $request->email;
            $user->nm_user = $request->full_name;
            $user->password = Hash::make($request->password);
            $user->save();
            // add roles
            $role = new RoleUser();
            $role->id_user = DB::getPdo()->lastInsertId();
            $role->id_role = $request->id_role ?? 99;
            $role->save();

            DB::commit();
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'User Gagal Register');
        }

        return redirect('auth/login')->with('success', 'User Sukses Register');
    }
}
