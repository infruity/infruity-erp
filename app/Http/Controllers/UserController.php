<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\UserPermission;
use Illuminate\Validation\ValidationException;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Staff;
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
        $data['staffOptions'] = Staff::query()->orderBy('name')->get(['id', 'name']);
        $data['linkedStaffUsers'] = DB::table('staff')->whereNotNull('user_id')->pluck('user_id', 'id')->all() + DB::table('users')->whereNotNull('staff_id')->pluck('id_user', 'staff_id')->all();
        $usedOperatorNumbers = DB::table('users')
            ->whereRaw('UPPER(email) LIKE ?', ['OPR%@INFRUITY.COM'])
            ->pluck('email')
            ->map(function ($email) {
                return preg_match('/^OPR(\d+)@INFRUITY\.COM$/i', (string) $email, $matches) === 1
                    ? (int) $matches[1]
                    : null;
            })
            ->filter(fn ($number) => $number !== null)
            ->all();
        $nextOperatorNumber = 1;
        while (in_array($nextOperatorNumber, $usedOperatorNumbers, true)) {
            $nextOperatorNumber++;
        }
        $data['nextAutoEmail'] = sprintf('OPR%03d@INFRUITY.COM', $nextOperatorNumber);
        $data['users'] = User::with('RoleUser.role', 'branches.branch', 'permissionOverrides', 'staff')
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
        $user = User::with('RoleUser.role', 'branches.branch', 'staff')->findOrFail($id);
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
            $this->syncStaffLink($user, $request->input('staff_id'));
            // add roles
            $role = new RoleUser();
            $role->id_user = $user->id_user;
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

        if ($request->boolean('link_only')) {
            DB::transaction(function () use ($id, $request) {
                $user = User::query()->lockForUpdate()->findOrFail($id);
                $this->syncStaffLink($user, $request->input('staff_id'));
            });

            return redirect('user')->with('success', 'Tautan karyawan akun diperbarui');
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
            if ($request->has('staff_id')) {
                $this->syncStaffLink($user, $request->input('staff_id'));
            } else {
                $user->save();
            }
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

    private function syncStaffLink(User $user, $staffId): void
    {
        $staffId = $staffId ? (int) $staffId : null;

        Staff::query()->where('user_id', $user->id_user)->update(['user_id' => null]);

        if ($staffId !== null) {
            $staff = Staff::query()->whereKey($staffId)->lockForUpdate()->firstOrFail();
            if ($staff->user_id !== null && (int) $staff->user_id !== (int) $user->id_user) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Karyawan tersebut sudah tertaut ke akun lain.',
                ]);
            }

            $staff->user_id = $user->id_user;
            $staff->save();
        }

        $user->staff_id = $staffId;
        $user->save();
    }

    public function updatePermissions(Request $request, $id)
    {
        $this->authorizeAccess('user.update');

        $user = User::findOrFail($id);
        abort_if((int) $user->id_user === (int) Auth::id(), 403, 'Hak akses akun sendiri tidak dapat diubah.');

        $validated = $request->validate([
            'overrides' => ['required', 'array', 'min:1'],
            'overrides.*' => ['required', 'boolean'],
        ]);

        $knownPermissions = collect(config('constant.permissions', []))
            ->flatMap(function ($module, $moduleKey) {
                return collect(array_keys($module['actions'] ?? []))
                    ->map(fn ($action) => $moduleKey . '.' . $action);
            })
            ->push('kurir.index')
            ->unique()
            ->values();
        $overrides = $validated['overrides'];
        $unknownPermissions = array_diff(array_keys($overrides), $knownPermissions->all());

        if ($unknownPermissions !== []) {
            throw ValidationException::withMessages([
                'overrides' => 'Satu atau lebih hak akses tidak dikenal.',
            ]);
        }

        DB::transaction(function () use ($user, $overrides) {
            foreach ($overrides as $permission => $allowed) {
                UserPermission::updateOrCreate(
                    ['id_user' => $user->id_user, 'permission' => $permission],
                    ['is_allowed' => (bool) $allowed]
                );
            }
        });

        return response()->json([
            'success' => true,
            'overrides' => collect($overrides)->map(fn ($allowed) => (bool) $allowed)->all(),
        ]);
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
