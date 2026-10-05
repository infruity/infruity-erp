<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use Response;
use App\Models\Role;
use DB;
use Validator;
use App\Http\Requests\RoleRequest;
use Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Models\RoleMenu;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class P_role extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($denied = $this->requireAccess('role.index')) {
            return $denied;
        }

        $search = trim((string) $request->query('q', ''));
        $data['data'] = Role::withCount('roleMenu')
            ->when($search !== '', fn ($query) => $query->where('nm_role', 'like', '%' . $search . '%'))
            ->orderBy('nm_role')
            ->paginate(30)
            ->withQueryString();
        $data['search'] = $search;

        return view("admin.role.index", $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if ($denied = $this->requireAccess('role.create')) {
            return $denied;
        }

        return redirect()->route('roles.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RoleRequest $request)
    {
        if ($denied = $this->requireAccess('role.store')) {
            return $denied;
        }

        if (strtolower($request->nm_role) === 'administrator') {
            return redirect()->back()->with('error', 'Akses Terbatas');
        }

        try {
            DB::beginTransaction();

            $role = new Role();
            $role->nm_role = $request->nm_role;
            $role->description = $request->description;
            $role->id_creator = Auth::user()->id_user;

            $role->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Data role Gagal Disimpan' . $e->getMessage());
        }

        return redirect()->route('roles.index')->with('success', 'Data role Disimpan');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if ($denied = $this->requireAccess('role.show')) {
            return $denied;
        }

        $role = Role::with('roleMenu')->findOrFail($id);

        // ambil semua nama route
        $permissions = config('constant.permissions');

        // ambil permission role yang aktif
        $rolePermissions = $role->roleMenu->pluck('permission')->toArray();

        return view('admin.role.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('role.edit')) {
            return $denied;
        }

        if (class_exists(\Debugbar::class)) {
            \Debugbar::disable();
        }
        $role = Role::with('roleMenu')->findOrFail($id);
        return response()->json($role);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('role.update')) {
            return $denied;
        }

        try {
            // save to user
            DB::beginTransaction();

            $role = Role::findOrFail($id);
            $role->nm_role = $request->name;
            $role->description = $request->description;

            if (strtolower($role->nm_role) == "administrator") {
                return redirect()->back()->with('error', 'Akses Terbatas');
            }
            $role->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diduplicate.'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Data masih digunakan di table lain' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('role.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $role = Role::findOrFail($id);
            if (strtolower($role->nm_role) == "administrator") {
                return redirect()->back()->with('error', 'Akses Terbatas');
            }
            $role->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Data masih digunakan di table lain' . $e->getMessage(),
            ]);
        }
    }

    public function duplicate($id)
    {
        if ($denied = $this->requireAccess('role.duplicate')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $role = Role::findOrFail($id);
            $newRole = new Role();
            $newRole->nm_role = $role->nm_role . " copy";
            $newRole->description = $role->description;
            $newRole->id_creator = Auth::user()->id_user;
            $newRole->save();

            $roleMenu = RoleMenu::where('id_role', $id)->get();
            foreach ($roleMenu as $key => $value) {
                RoleMenu::create([
                    'id_role' => $newRole->id_role,
                    'permission' => $value->permission,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diduplicate.'
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Data masih digunakan di table lain' . $e->getMessage(),
            ]);
        }

    }


    public function get_data(Request $request)
    {
        if (class_exists(\Debugbar::class)) {
            \Debugbar::disable();
        }

        $data = Role::query();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                            <li>
                                <a class="dropdown-item" href="' . route('roles.show', $row->id_role) . '">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="editProduct(' . $row->id_role . ')">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="duplicateProduct(' . $row->id_role . ')">
                                    <i class="fa fa-paste"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $row->id_role . ')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </li>
                        </ul>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
