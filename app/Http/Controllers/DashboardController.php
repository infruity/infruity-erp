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

    public function list_permohonan(Request $request)
    {
        $firstQuery = DB::table('t_permohonan')
            ->join('m_layanan', 't_permohonan.id_layanan', '=', 'm_layanan.id_layanan')
            ->join('m_status', 't_permohonan.id_status', '=', 'm_status.id_status')
            ->where('t_permohonan.id_user', Auth::user()->id_user)
            ->select(
                't_permohonan.id',
                't_permohonan.no_permohonan',
                't_permohonan.tanggal_pengajuan',
                't_permohonan.id_status',
                't_permohonan.id_surat',
                'm_layanan.nm_layanan',
                'm_status.nama_status',
                'm_status.icon',
                'm_status.class_color',
                DB::raw("'permohonan-sk' AS type"),
                DB::raw("CASE WHEN t_permohonan.id_status < 10 THEN t_permohonan.id_status + 1 ELSE NULL END AS next_status")
            );

        $secondQuery = DB::table('ipt_pengurangan')
            ->join('m_status', 'ipt_pengurangan.id_status', '=', 'm_status.id_status')
            ->where('ipt_pengurangan.id_user', Auth::user()->id_user)
            ->select(
                'ipt_pengurangan.id',
                'ipt_pengurangan.no_permohonan',
                'ipt_pengurangan.tanggal_pengajuan',
                'ipt_pengurangan.id_status',
                'ipt_pengurangan.type AS nm_layanan',
                'ipt_pengurangan.id_surat',
                'm_status.nama_status',
                'm_status.icon',
                'm_status.class_color',
                DB::raw("'ipt-pengurangan' AS type"),
                DB::raw("CASE WHEN ipt_pengurangan.id_status < 10 THEN ipt_pengurangan.id_status + 1 ELSE NULL END AS next_status")
            );

        $combinedResults = DB::query()->fromSub(
            $firstQuery->union($secondQuery),
            'cr'
        )
            ->leftJoin('m_status AS ns', 'cr.next_status', '=', 'ns.id_status')
            ->select('cr.*', 'ns.nama_status AS next_status_name', 'ns.class_color AS next_status_class_color')
            ->orderBy('cr.tanggal_pengajuan', 'DESC')
            ->paginate(5);
        $data['data'] = $combinedResults;
        // dd($combinedResults);
        return view('list_permohonan', $data);
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
