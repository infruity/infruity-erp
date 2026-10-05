<?php
namespace Modules\Master\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Master\Entities\Department;
use Modules\Master\Entities\Position;
use Modules\Master\Entities\Staff;
use Yajra\DataTables\Facades\DataTables;

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

        return view('master::staff.index', ['records' => Staff::query()->with(['department:id,name', 'position:id,name'])->select('id', 'name', 'nickname', 'contact', 'email', 'department_id', 'position_id', 'status')->orderByDesc('id')->get()]);
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

        $data['page_plugin_js'] = [
            'assets/plugins/custom/formrepeater/formrepeater.bundle.js',
        ];
        $data['data'] = null;
        return view('master::staff.create', $data);
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

        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'staff_name'  => 'required',
            'nickname'    => 'required',
            'date_in'     => 'nullable|date|date_format:Y-m-d',
            'gender'      => 'nullable|in:male,female',
            'nik'         => 'nullable',
            'contact'     => [
                'nullable',
                'numeric',
                'digits_between:10,15',
                'regex:/^(?:\+62|62|08)[0-9]{8,13}$/',
            ],
            'email'       => 'nullable|email',
            // 'department' => 'required|exists:department,id',
            // 'position' => 'required|exists:position,id',
            'description' => 'nullable|string|max:1000',
            'avatar'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $staff                = new Staff();
            $staff->name          = $request->staff_name;
            $staff->nickname      = $request->nickname;
            $staff->date_in       = $request->date_in;
            $staff->gender        = $request->gender;
            $staff->nik           = $request->nik;
            $staff->contact       = $request->contact;
            $staff->email         = $request->email;
            $staff->department_id = $request->department;
            $staff->position_id   = $request->position;
            $staff->description   = $request->description;
            $staff->status        = $request->status ?? 'aktif';
            $staff->created_by    = Auth::user()->id_user;

            if ($request->hasFile('avatar')) {
                $file         = $request->file('avatar');
                $filename     = time() . '_' . $file->getClientOriginalName();
                $path         = $file->storeAs('staff', $filename, 'public');
                $staff->image = $path;
            }

            $staff->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
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
    public function edit($id)
    {
        if ($denied = $this->requireAccess('staff.edit')) {
            return $denied;
        }

        $data['page_plugin_js'] = [
            'assets/plugins/custom/formrepeater/formrepeater.bundle.js',
        ];
        $data['data']       = Staff::findOrFail($id);
        $data['department'] = Department::where('id', $data['data']->department_id)->first();
        $data['position']   = Position::where('id', $data['data']->position_id)->first();
        return view('master::staff.create', $data);
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

        $validator = Validator::make($request->all(), [
            'staff_name'  => 'required',
            'nickname'    => 'required',
            'date_in'     => 'nullable|date|date_format:Y-m-d',
            'gender'      => 'nullable|in:male,female',
            'nik'         => 'nullable',
            'contact'     => [
                'nullable',
                'numeric',
                'digits_between:10,15',
                'regex:/^(?:\+62|62|08)[0-9]{8,13}$/',
            ],
            'email'       => 'nullable|email',
            // 'department' => 'nullable|exists:department,id',
            // 'position' => 'nullable|exists:position,id',
            'description' => 'nullable|string|max:1000',
            'avatar'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $staff                = Staff::findOrFail($id);
            $staff->name          = $request->staff_name;
            $staff->nickname      = $request->nickname;
            $staff->date_in       = $request->date_in;
            $staff->gender        = $request->gender;
            $staff->nik           = $request->nik;
            $staff->contact       = $request->contact;
            $staff->email         = $request->email;
            $staff->department_id = $request->department;
            $staff->position_id   = $request->position;
            $staff->description   = strip_tags($request->description ?? '');
            $staff->status        = $request->status ?? 'aktif';
            $staff->updated_by    = Auth::user()->id_user;

            if ($request->hasFile('avatar')) {
                // Hapus foto lama jika ada
                if ($staff->image) {
                    Storage::disk('public')->delete($staff->image);
                }
                $file         = $request->file('avatar');
                $filename     = time() . '_' . $file->getClientOriginalName();
                $path         = $file->storeAs('staff', $filename, 'public');
                $staff->image = $path;
            }

            if ($request->avatar_remove == '1') {
                if ($staff->image) {
                    Storage::disk('public')->delete($staff->image);
                }
                $staff->image = null;
            }

            $staff->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
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

    public function get_data(Request $request)
    {
        // eager load relations supaya lebih efisien
        $data = Staff::with(['position', 'department'])
            ->orderBy('name', 'asc');

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $colors      = ['warning', 'success', 'info', 'primary'];
                $color       = $colors[$item->id % count($colors)];
                $initial     = strtoupper(substr($item->name ?? '', 0, 1));
                $displayName = $item->name ?? '-';
                $email       = $item->email ?? '-';
                $contact     = $item->contact ?? '-';

                $avatarInner = '<div class="symbol-label fs-3 bg-light-' . $color . ' text-' . $color . '">' . e($initial) . '</div>';
                if ($item->image) {
                    $imageUrl    = asset('storage/' . $item->image);
                    $avatarInner = '<img src="' . $imageUrl . '" alt="' . e($displayName) . '" style="width: 50px; height: 50px; object-fit: cover;">';
                }

                $linkUrl = $item->image ? asset('storage/' . $item->image) : 'javascript:void(0)';
                $target  = $item->image ? 'target="_blank"' : '';

                return '<div class="d-flex align-items-center">
                        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3 border">
                            <a href="' . $linkUrl . '" ' . $target . ' class="d-block w-100 h-100">
                                ' . $avatarInner . '
                            </a>
                        </div>
                        <div class="ms-5">
                            <a href="javascript:void(0)" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . e($displayName) . '</a><br>
                            <span class="fs-7">' . e($email) . '</span>
                            <span class="fs-7">' . e($contact) . '</span>
                        </div>
                    </div>';
            })
            ->addColumn('date_in', function ($item) {
                return dateindo($item->date_in);
            })
            ->addColumn('position', function ($item) {
                // safe guard: cek relasi sebelum akses ->name
                $positionLabel = '<span class="badge badge-light-danger">Belum ada jabatan</span>';
                if ($item->position && $item->position->name) {
                    $positionLabel = '<span class="badge badge-light-primary">' . e($item->position->name) . '</span>';
                }

                $departmentLabel = '<span class="badge badge-light-danger">Belum ada departemen</span>';
                if ($item->department && $item->department->name) {
                    $departmentLabel = '<span class="badge badge-light-primary">' . e($item->department->name) . '</span>';
                }

                return $positionLabel . ' ' . $departmentLabel;
            })
            ->addColumn('action', function ($item) {
                $html = '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                        <li>
                            <a class="dropdown-item" href="' . route('staff.show', $item->id) . '">
                                <i class="bi bi-eye"></i>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="' . route('staff.edit', $item->id) . '">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $item->id . ')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            ';
                return $html;
            })
            ->rawColumns(['name', 'action', 'position'])
            ->make(true);
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
