<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Permohonan\Entities\Layanan;
use Modules\Permohonan\Entities\Permohonan;
use Modules\Permohonan\Entities\PenguranganIpt;
use Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        return view('dashboard');
    }

    public function landing()
    {
        return view('landing');
    }

    public function change_password()
    {
        return view('change-password');
    }

    public function save_change_password(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                'regex:/[A-Z]/', // Harus ada huruf besar
                'regex:/[a-z]/', // Harus ada huruf kecil
                'regex:/[0-9]/', // Harus ada angka
                'regex:/[!@#$%^&*()_+\-]/', // Harus ada karakter spesial
            ],
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('old_password', 'password', 'password_confirmation'));
        }

        if (
            Auth::attempt(array(
                'username' => Auth::user()->username,
                'password' => $request->old_password,
            ))
        ) {
            try {
                DB::beginTransaction();
                $user = User::findOrFail(Auth::user()->id_user);
                $user->password = Hash::make($request->password);
                $user->save();
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                return redirect()->back()->with('error', 'Password tidak bisa diganti, Hubungi Administrator')->withInput();
            }
            return redirect('dashboard')->with('success', 'Password Berhasil di Ganti oleh ' . Auth::user()->nm_user);
        } else {
            return redirect()->back()->with('error', 'Password Salah')->withInput();
        }
    }

    public function forgot_password()
    {
        return redirect('auth/login')->with('error', 'Pemulihan kata sandi mandiri sementara tidak tersedia. Hubungi administrator untuk bantuan.');
    }

    public function forgot_password_check(Request $request)
    {
        return $this->forgot_password();
    }

    public function generateQrCode()
    {
        return view('qrcode');
    }
}
