<?php
namespace Modules\Master\Http\Controllers;

use Exception;
use App\User;
use App\Models\Role;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Position;
use Modules\Master\Entities\Staff;

class StaffController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('staff.index')) {
            return $denied;
        }

        $records = $this->staffListRecords();

        return view('master::staff.index', array_merge(
            $this->staffFormData(),
            [
                'records' => $records,
                'initialCreateOpen' => request()->boolean('create'),
                'initialEditOpen' => false,
                'initialEditReturnUrl' => route('staff.index'),
            ]
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('staff.create')) {
            return $denied;
        }

        return redirect()->route('staff.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('staff.store')) {
            return $denied;
        }

        $rules = [
            'staff_name' => ['required', 'string', 'max:255'],
            'date_in' => ['nullable', 'date_format:Y-m-d'],
            'position' => ['required', 'integer', 'exists:position,id'],
            'branch_id' => ['required', 'integer', Rule::in($this->availableBranchIds())],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', 'distinct', 'exists:role,id_role'],
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('staff.index', ['create' => 1])
                ->withErrors($validator)
                ->withInput($request->except(['account_password', 'password']));
        }

        try {
            DB::beginTransaction();
            $staff = new Staff();
            $staff->name = $request->staff_name;
            $staff->date_in = $request->date_in;
            $staff->position_id = $request->position;
            $staff->branch_id = $request->branch_id;
            $staff->status = 'aktif';
            $staff->created_by = Auth::user()->id_user;
            $staff->save();
            $this->syncPendingRoles($staff->id, $request->input('role_ids', []));

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->route('staff.index', ['create' => 1])
                ->withInput($request->except(['account_password', 'password']))
                ->with('error', 'Pembuatan Staff gagal' . $e->getMessage());
        }

        return redirect('staff')->with('success', 'Pembuatan Staff berhasil');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if ($denied = $this->requireAccess('staff.show')) {
            return $denied;
        }

        $data['data'] = Staff::findOrFail($id);
        return view('master::staff.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit(Request $request, $id)
    {
        if ($denied = $this->requireAccess('staff.edit')) {
            return $denied;
        }

        $editingStaff = Staff::with(['account', 'pendingRoles'])->findOrFail($id);
        $formData = array_merge(['data' => $editingStaff], $this->staffFormData());

        if ($request->boolean('overlay')) {
            return view('master::staff.edit-form', $formData);
        }

        $records = check_access('staff.index')
            ? $this->staffListRecords()
            : $this->staffListRecords((int) $editingStaff->id);

        return view('master::staff.index', array_merge($this->staffFormData(), [
            'records' => $records,
            'initialCreateOpen' => false,
            'initialEditOpen' => true,
            'initialEditReturnUrl' => route('staff.index'),
            'editingStaff' => $editingStaff,
        ]));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('staff.update')) {
            return $denied;
        }

        $rules = [
            'staff_name' => ['required', 'string', 'max:255'],
            'date_in' => ['nullable', 'date_format:Y-m-d'],
            'position' => ['required', 'integer', 'exists:position,id'],
            'branch_id' => ['required', 'integer', Rule::in($this->availableBranchIds())],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', 'distinct', 'exists:role,id_role'],
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except(['account_password', 'password']));
        }

        try {
            DB::beginTransaction();
            $staff = Staff::findOrFail($id);
            $staff->name = $request->staff_name;
            $staff->date_in = $request->date_in;
            $staff->position_id = $request->position;
            $staff->branch_id = $request->branch_id;
            $staff->updated_by = Auth::user()->id_user;
            $staff->save();
            $this->syncPendingRoles($staff->id, $request->input('role_ids', []));

            $accountId = User::withoutGlobalScope('is_aktif')
                ->where('staff_id', $staff->id)
                ->value('id_user');
            if ($accountId) {
                User::withoutGlobalScope('is_aktif')
                    ->where('id_user', $accountId)
                    ->update(['nm_user' => $staff->name]);
                $this->syncAccountRoles((int) $accountId, $request->input('role_ids', []));
                $this->syncAccountBranch((int) $accountId, (int) $staff->branch_id);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->except(['account_password', 'password']))
                ->with('error', 'Update Staff gagal' . $e->getMessage());
        }

        return redirect('staff')->with('success', 'Update Staff berhasil');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('staff.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $staff = Staff::findOrFail($id);
            if ($staff->image) {
                Storage::disk('public')->delete($staff->image);
            }
            $staff->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function syncPendingRoles(int $staffId, array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
        DB::table('staff_role')->where('staff_id', $staffId)->delete();

        $now = now();
        DB::table('staff_role')->insert(array_map(fn ($roleId) => [
            'staff_id' => $staffId,
            'id_role' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $roleIds));
    }

    private function syncAccountRoles(int $userId, array $roleIds): void
    {
        $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
        DB::table('role_user')->where('id_user', $userId)->delete();

        $now = now();
        DB::table('role_user')->insert(array_map(fn ($roleId) => [
            'id_user' => $userId,
            'id_role' => $roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $roleIds));
    }

    private function syncAccountBranch(int $userId, int $branchId): void
    {
        DB::table('user_branch')->where('user_id', $userId)->delete();
        DB::table('user_branch')->insert(['user_id' => $userId, 'branch_id' => $branchId]);
    }

    private function staffListRecords(?int $onlyId = null)
    {
        $query = Staff::query()
            ->with(['department:id,name', 'position:id,name', 'branch:id,name'])
            ->select('id', 'name', 'department_id', 'position_id', 'branch_id', 'status', 'date_in')
            ->orderByDesc('id');

        if ($onlyId !== null) {
            $query->whereKey($onlyId);
        }

        return $query->get()->each(function ($staff) {
            $staff->position_label = $staff->position?->name ?: 'Belum diatur';
            $staff->branch_label = $staff->branch?->name ?: 'Belum diatur';

            if (!$staff->date_in) {
                $staff->tenure_label = 'Belum diatur';
                return;
            }

            $tenure = Carbon::parse($staff->date_in)->diff(Carbon::now());
            $parts = [];
            if ($tenure->y > 0) $parts[] = $tenure->y . ' Thn';
            if ($tenure->m > 0) $parts[] = $tenure->m . ' Bln';
            $staff->tenure_label = $parts ? implode(' ', $parts) : 'Baru bergabung';
        });
    }

    private function staffFormData(): array
    {
        return [
            'positions' => Position::orderBy('name')->get(['id', 'name']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'roles' => Role::orderBy('nm_role')->get(['id_role', 'nm_role']),
            'initialCreateOpen' => false,
        ];
    }

    private function availableBranchIds(): array
    {
        return Branch::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function getStaff(Request $request)
    {
        $search = $request->get('search');

        $data = Staff::where('name', 'like', '%' . $search . '%')
            ->select('id', 'name');

        if ($request->has('type') && $request->type == 'kurir') {
            $data = $data->where('is_kurir', 1);
        }

        $data = $data->limit(10)->get();

        return response()->json($data);
    }
}
