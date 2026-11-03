<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        return redirect('auth/login')->with('error', 'Pendaftaran mandiri tidak tersedia. Hubungi administrator untuk membuat akun.');
    }

    public function create(Request $request)
    {
        abort(403, 'Pendaftaran mandiri tidak tersedia.');
    }
}
