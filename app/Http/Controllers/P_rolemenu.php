<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Response;
use App\Models\RoleMenu;
use DB;
use Validator;
use App\Http\Requests\RoleMenuRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\Perusahaan;
use Auth;
use Session;
use App\Models\Module;

class P_rolemenu extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($denied = $this->requireAccess('role-menu.index')) {
            return $denied;
        }

        $data["data"] = RoleMenu::with('role')->paginate(10);
        return view("role-menu", $data);
    }

    public function create()
    {
        if ($denied = $this->requireAccess('role-menu.create')) {
            return $denied;
        }


    }

    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('role-menu.store')) {
            return $denied;
        }

        // User creation belongs to UserController. This legacy role-menu action
        // must never create accounts or assign roles from an unvalidated form.
        abort(404);
    }

    public function show($id)
    {
        if ($denied = $this->requireAccess('role-menu.show')) {
            return $denied;
        }

        abort(404);
    }

    public function update(Request $request, $id_role)
    {
        if ($denied = $this->requireAccess('role-menu.update')) {
            return $denied;
        }

        try {
            // save to user
            DB::beginTransaction();
            $role = Role::findOrFail($id_role);

            // hapus semua izin lama
            RoleMenu::where('id_role', $id_role)->delete();

            // simpan yang baru
            if ($request->permissions) {
                foreach ($request->permissions as $perm) {
                    RoleMenu::create([
                        'id_role' => $id_role,
                        'permission' => $perm,
                    ]);
                }
            }
            DB::commit();
            return redirect()->route('roles.index')->with('success', 'Akses role berhasil diperbarui');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Data User Gagal Disimpan' . $e->getMessage());
        }

        return redirect('user')->with('success', 'Data User Disimpan');
    }

    public function destroy($id)
    {
        if ($denied = $this->requireAccess('role-menu.destroy')) {
            return $denied;
        }

        DB::beginTransaction();

        try {
            // $user = User::findOrFail($id);
            // $user->delete();
            RoleUser::where('id_user', $id)->delete();
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Data masih digunakan di table lain');
        }

        return redirect()->back()->with('success', 'Data User Dihapus');
    }

    public function logout($id = null)
    {
        Auth::logout();

        if (isset($id) && $id != null) {
            return redirect('auth/login')->with('error', $id);
        } else {
            return redirect('auth/login')->with('error', 'Anda keluar Aplikasi');
        }
    }
}
