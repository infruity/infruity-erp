<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\RoleUser;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;
use App\User;
use Auth;
use DB;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    use \App\Traits\HasAccessControl;

    public function index(Request $request)
    {
        if ($denied = $this->requireAccess('user.index')) {
            return $denied;
        }

        $data['role'] = Role::all();
        $data['branch'] = Branch::all();
        $data['users'] = User::with('RoleUser.role', 'branches.branch')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = '%' . $request->input('q') . '%';
                $query->where(function ($users) use ($search) {
                    $users->where('nm_user', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->orderBy('nm_user')
            ->paginate(50)
            ->withQueryString();
        return view("admin.user.index", $data);
    }

    public function create()
    {
        if ($denied = $this->requireAccess('user.create')) {
            return $denied;
        }

        return redirect()->route('user.index', ['create' => 1]);
    }

    public function edit($id)
    {
        if ($denied = $this->requireAccess('user.edit')) {
            return $denied;
        }

        if (class_exists(\Debugbar::class)) {
            \Debugbar::disable();
        }
        $user = User::with('RoleUser.role', 'branches.branch')->findOrFail($id);
        return response()->json($user);
    }

    public function store(UserRequest $request)
    {
        if ($denied = $this->requireAccess('user.store')) {
            return $denied;
        }

        // dd($request->all());
        try {
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
            // dd($role);
            $userBranch = [];
            foreach ($request->id_branch as $branch) {
                $userBranch[] = [
                    'user_id' => $role->id_user,
                    'branch_id' => $branch,
                ];
            }
            UserBranch::insert($userBranch);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Data User Gagal Disimpan' . $e->getMessage())->withInput($request->all());
        }

        return redirect('user')->with('success', 'Data User Disimpan');
    }

    public function show($id)
    {
        abort(404);
    }

    public function update(UserRequest $request, $id)
    {
        if ($denied = $this->requireAccess('user.update')) {
            return $denied;
        }

        try {
            // save to user
            DB::beginTransaction();
            $user = User::findOrFail($id);
            $user->username = $request->email;
            $user->email = $request->email;
            $user->nm_user = $request->full_name;
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();
            // add roles
            RoleUser::where('id_user', $id)->delete();
            $role = new RoleUser();
            $role->id_user = $id;
            $role->id_role = $request->id_role ?? 99;
            $role->save();

            // add branches
            UserBranch::where('user_id', $id)->delete();
            $userBranch = [];
            foreach ($request->id_branch as $branch) {
                $userBranch[] = [
                    'user_id' => $id,
                    'branch_id' => $branch,
                ];
            }
            UserBranch::insert($userBranch);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Data User Gagal Disimpan');
        }

        return redirect('user')->with('success', 'Data User Disimpan');
    }

    public function destroy($id)
    {
        if ($denied = $this->requireAccess('user.destroy')) {
            return $denied;
        }

        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            $user->is_aktif = false;
            $user->save();

            RoleUser::where('id_user', $id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Data masih digunakan di table lain'
            ]);
        }
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

    public function impersonate($id)
    {
        if (Auth::id() != 1) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::findOrFail($id);
        Auth::login($user);

        return redirect('/')->with('success', 'Berhasil login sebagai ' . $user->nm_user);
    }

    public function get_data(Request $request)
    {
        if (class_exists(\Debugbar::class)) {
            \Debugbar::disable();
        }

        $data = User::with('RoleUser.role', 'branches.branch')->get();

        return DataTables::of($data)
            ->addIndexColumn()
            // ->addColumn('role', function ($row) {
            //     return $row->RoleUser->map(function ($ru) {
            //         return $ru->role->nm_role ?? '-';
            //     })->implode(', ');
            // })
            ->addColumn('role', function ($row) {
                return '<span class="badge badge-light-success fw-bold me-1">' . e($row->RoleUser->role->nm_role ?? '-') . '</span>';
            })
            ->addColumn('branch', function ($row) {
                return $row->branches->map(function ($ub) {
                    $name = $ub->branch->name ?? '-';
                    return '<span class="badge badge-light-primary fw-bold me-1">' . e($name) . '</span>';
                })->implode(' ');
            })
            ->addColumn('nm_user', function ($row) {
                return $row->nm_user . '<br>' . $row->username;
            })
            ->addColumn('action', function ($row) {
                $impersonateBtn = '';
                if(Auth::id() == 1 && $row->id_user != 1) {
                    $impersonateBtn = '
                        <li>
                            <a class="dropdown-item text-success d-flex" href="'.route('user.impersonate', $row->id_user).'" title="Login sebagai user ini">
                                <i class="bi bi-person-bounding-box text-success me-2"></i> Login
                            </a>
                        </li>
                    ';
                }

                return '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                            ' . $impersonateBtn . '
                            <li>
                                <a class="dropdown-item d-flex" href="javascript:void(0)" onclick="editProduct(' . $row->id_user . ')">
                                    <i class="bi bi-pencil-square me-2"></i> Edit
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger d-flex" href="javascript:void(0)" onclick="deleteProduct(' . $row->id_user . ')">
                                    <i class="bi bi-trash text-danger me-2"></i> Delete
                                </a>
                            </li>
                        </ul>
                    </div>';
            })
            ->rawColumns(['nm_user', 'action', 'branch', 'role'])
            ->make(true);
    }
}
