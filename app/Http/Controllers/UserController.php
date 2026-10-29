<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\UserPermission;
use Illuminate\Validation\ValidationException;
use Modules\Master\Entities\Staff;
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

        $data['role'] = Role::orderBy('nm_role')->get();
        $data['staffOptions'] = Staff::with(['account:id_user,staff_id', 'pendingRoles:id_role'])
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id'])
            ->map(fn ($staff) => [
                'id' => $staff->id,
                'name' => $staff->name,
                'linked_user_id' => $staff->account?->id_user,
                'branch_id' => $staff->branch_id,
                'role_ids' => $staff->pendingRoles->pluck('id_role')->map(fn ($id) => (int) $id)->all(),
            ]);
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
        $selectedRoleId = $request->query('role_id');
        $moduleDefinitions = config('constant.permissions', []);
        $data['users'] = User::withoutGlobalScope('is_aktif')->with('roleAssignments.role.roleMenu', 'branches.branch', 'permissionOverrides', 'staff')
            ->when($selectedRoleId, fn ($query) => $query->whereHas('roleAssignments', fn ($assignment) => $assignment->where('id_role', $selectedRoleId)))
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = '%' . $request->input('q') . '%';
                $query->where(function ($users) use ($search) {
                    $users->where('nm_user', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->orderByDesc('is_aktif')
            ->orderBy('nm_user')
            ->paginate(50)
            ->withQueryString();
        $data['users']->getCollection()->transform(function ($user) use ($moduleDefinitions) {
            $roles = $user->roleAssignments->pluck('role')->filter()->unique('id_role')->values();
            $permissions = $roles->flatMap(fn ($role) => $role->roleMenu->pluck('permission'))->unique()->values()->all();
            $grantedModules = collect($moduleDefinitions)->filter(function ($module, $moduleKey) use ($permissions) {
                return collect($module['actions'] ?? [])->keys()->contains(
                    fn ($action) => in_array($moduleKey . '.' . $action, $permissions, true)
                );
            })->count();

            $user->setAttribute('role_ids', $roles->pluck('id_role')->map(fn ($id) => (int) $id)->all());
            $user->setAttribute('role_names', $roles->pluck('nm_role')->all());
            $user->setAttribute('granted_modules_count', $grantedModules);
            return $user;
        });
        $data['modulesTotal'] = count($moduleDefinitions);
        $data['selectedRoleId'] = $selectedRoleId;
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
        $user = User::with('roleAssignments.role', 'branches.branch', 'staff')->findOrFail($id);
        $user->setAttribute('role_ids', $user->roleAssignments->pluck('id_role')->map(fn ($id) => (int) $id)->all());
        return response()->json($user);
    }

    public function store(UserRequest $request)
    {
        if ($denied = $this->requireAccess('user.store')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $staff = Staff::query()->lockForUpdate()->findOrFail($request->staff_id);
            $user = new User();
            $user->username = $request->email;
            $user->email = $request->email;
            $user->nm_user = $staff->name;
            $user->password = Hash::make($request->password);
            $user->save();
            $this->syncStaffLink($user, $staff->id);
            DB::commit();
        } catch (ValidationException $e) {
            DB::rollback();
            throw $e;
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Data User Gagal Disimpan' . $e->getMessage())->withInput($request->except(['password']));
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

        DB::transaction(function () use ($id, $request) {
            $user = User::query()->lockForUpdate()->findOrFail($id);
            $this->syncStaffLink($user, $request->input('staff_id'));
        });

        return redirect('user')->with('success', 'Tautan karyawan akun diperbarui');
    }

    private function syncStaffLink(User $user, $staffId): void
    {
        $staffId = $staffId ? (int) $staffId : null;
        $userId = (int) $user->id_user;
        $currentStaffId = $user->staff_id !== null ? (int) $user->staff_id : null;
        $newStaff = null;
        $roleIds = [];
        $linkChanged = $currentStaffId !== $staffId;

        if ($staffId !== null) {
            $newStaff = Staff::query()->lockForUpdate()->findOrFail($staffId);
            if ($newStaff->user_id !== null && (int) $newStaff->user_id !== $userId) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Karyawan ini sudah tertaut ke akun lain.',
                ]);
            }

            if ($linkChanged) {
                $roleIds = DB::table('staff_role')
                    ->where('staff_id', $staffId)
                    ->pluck('id_role')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $hasBranch = $newStaff->branch_id
                    && DB::table('branch')->where('id', $newStaff->branch_id)->exists();

                if (!$hasBranch || $roleIds === []) {
                    throw ValidationException::withMessages([
                        'staff_id' => 'Lengkapi cabang dan Hak Akses karyawan sebelum menautkan akun.',
                    ]);
                }
            }
        }

        if ($currentStaffId !== null && $currentStaffId !== $staffId) {
            DB::table('staff')
                ->where('id', $currentStaffId)
                ->where('user_id', $userId)
                ->update(['user_id' => null]);
        }

        if ($newStaff) {
            DB::table('staff')
                ->where('id', $staffId)
                ->update(['user_id' => $userId]);
        }

        if ($newStaff && $linkChanged) {
            $user->nm_user = $newStaff->name;
        }
        $user->staff_id = $staffId;
        $user->save();

        if ($newStaff && $linkChanged) {
            $this->syncRoleAssignments($user, $roleIds);
            $this->syncUserBranches($userId, [(int) $newStaff->branch_id]);
        }
    }

    private function syncUserBranches(int $userId, array $branchIds): void
    {
        DB::table('user_branch')->where('user_id', $userId)->delete();
        $branchIds = array_values(array_unique(array_map('intval', $branchIds)));
        if ($branchIds === []) {
            return;
        }

        DB::table('user_branch')->insert(array_map(fn ($branchId) => [
            'user_id' => $userId,
            'branch_id' => $branchId,
        ], $branchIds));
    }

    private function syncRoleAssignments(User $user, array $roleIds): void
    {
        RoleUser::where('id_user', $user->id_user)->delete();

        foreach (array_values(array_unique(array_map('intval', $roleIds))) as $roleId) {
            $assignment = new RoleUser();
            $assignment->id_user = $user->id_user;
            $assignment->id_role = $roleId;
            $assignment->save();
        }
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

        $data = User::with('roleAssignments.role', 'branches.branch')->get();

        return DataTables::of($data)
            ->addIndexColumn()
            // ->addColumn('role', function ($row) {
            //     return $row->RoleUser->map(function ($ru) {
            //         return $ru->role->nm_role ?? '-';
            //     })->implode(', ');
            // })
            ->addColumn('role', function ($row) {
                return $row->roleAssignments->map(fn ($assignment) => $assignment->role?->nm_role)
                    ->filter()
                    ->map(fn ($name) => '<span class="badge badge-light-success fw-bold me-1">' . e($name) . '</span>')
                    ->implode(' ');
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
