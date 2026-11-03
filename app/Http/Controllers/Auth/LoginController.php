<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RoleMenu;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Auth;
// use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Redirect;
use Session;
use Illuminate\Support\Facades\Validator;
use Modules\Permohonan\Entities\Permohonan;

class LoginController extends Controller
{
    // use AuthenticatesUsers;
    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
            // 'captcha' => 'required|captcha',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        // dd($request->all());
        $loginIdentifier = $request->input('email');
        $loginField = filter_var($loginIdentifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt(array(
            $loginField => $loginIdentifier,
            'password' => $request->password,
        ), $request->boolean('remember'))) {
            $request->session()->regenerate();
            $role = RoleMenu::currentRole();
            if ($role === null) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('auth/login')->with('error', 'User tidak terdaftar di role apa pun');
            }
            $request->session()->put('role', $role->toArray());
            return redirect('dashboard')->with('success', 'Welcome Bro ' . Auth::user()->nm_user);
        } else {
            return redirect()->back()->with('error', 'Username atau Password tidak terdaftar');
        }
    }
}
