<?php

namespace Modules\Master\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Master\Entities\Position;
use Modules\Master\Entities\Department;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use DB;
use Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;

class PositionController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('position.index')) {
            return $denied;
        }

        $departments = Department::all();
        $data = [
            'departments' => $departments,
            'records' => Position::query()->with('department:id,name')->select('id', 'name', 'code', 'department_id', 'description')->orderByDesc('id')->get(),
        ];
        return view('master::position.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('position.create')) {
            return $denied;
        }

        return redirect()->route('position.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('position.store')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:department,id',
            'description' => 'nullable|string|max:1000',
        ]);

        // Simpan data ke database
        // Step 1: Buat akronim dari nama
        $name = Department::find($validated['department_id'])->name;
        $words = preg_split('/\s+/', trim($name));
        $acronym = '';
        foreach ($words as $word) {
            $acronym .= strtoupper($word[0]);
        }
        // Step 2: Cari kode terakhir dari akronim
        $lastCode = Position::where('code', 'LIKE', $acronym . '%')
            ->orderBy('code', 'desc')
            ->value('code'); // ambil 1 kolom

        // Step 3: Ambil angka terakhir
        $number = 1;
        if ($lastCode) {
            $number = (int) substr($lastCode, strlen($acronym)) + 1;
        }

        // Step 4: Format kode baru
        $newCode = $acronym . str_pad($number, 3, '0', STR_PAD_LEFT);
        // Simpan data ke database
        // dd($newCode);
        try {
            DB::beginTransaction();
            $position = new Position();
            $position->name = $validated['name'];
            $position->department_id = $validated['department_id'];
            $position->description = $validated['description'] ?? null;
            $position->code = $newCode;
            $position->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Position gagal disimpan.',
                'data' => $position
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Position berhasil disimpan.',
            'data' => $position
        ], 201);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('master::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('position.edit')) {
            return $denied;
        }

        $position = Position::findOrFail($id);
        return response()->json($position);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('position.update')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:department,id',
            'description' => 'nullable|string|max:1000',
        ]);

        // Simpan data ke database

        // Step 1: Buat akronim dari nama
        $name = Department::find($validated['department_id'])->name;
        $words = preg_split('/\s+/', trim($name));
        $acronym = '';
        foreach ($words as $word) {
            $acronym .= strtoupper($word[0]);
        }
        // Step 2: Cari kode terakhir dari akronim
        $lastCode = Position::where('code', 'LIKE', $acronym . '%')
            ->orderBy('code', 'desc')
            ->value('code'); // ambil 1 kolom

        // Step 3: Ambil angka terakhir
        $number = 1;
        if ($lastCode) {
            $number = (int) substr($lastCode, strlen($acronym)) + 1;
        }

        // Step 4: Format kode baru
        $newCode = $acronym . str_pad($number, 3, '0', STR_PAD_LEFT);
        // Simpan data ke database
        // dd($newCode);
        try {
            DB::beginTransaction();
            $position = Position::findOrFail($id);
            $position->name = $validated['name'];
            $position->department_id = $validated['department_id'];
            $position->description = $validated['description'] ?? null;
            if ($position->getOriginal('department_id') != $validated['department_id']) {
                $position->code = $newCode;
            }
            $position->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Position gagal disimpan.',
                'data' => $position
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Position berhasil disimpan.',
            'data' => $position
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('position.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $position = Position::findOrFail($id);
            $position->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function get_data(Request $request)
    {
        $data = Position::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('department_name', function ($position) {
                return $position->department->name ?? '-';
            })
            ->addColumn('name', function ($position) {
                $colors = ['warning', 'success', 'info', 'primary'];
                $color = $colors[$position->id % count($colors)];

                $html = '';
                $html .= '<div class="d-flex align-items-center">';
                // $html .= '
                //             <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                //                 <a href="javascript:void(0)">
                //                     <div class="symbol-label fs-3 bg-light-' . $color . ' text-' . $color . '">' . strtoupper(substr($position->name, 0, 1)) . '</div>
                //                 </a>
                //             </div>';
                $html .= '<div class="ms-5">
                                <a href="javascript:void(0)" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . $position->name . '</a>
                            </div>';
                $html .= '</div>';
                return $html;
            })
            ->addColumn('code', function ($position) {
                return 'J-' . $position->code;
            })
            ->addColumn('action', function ($row) {
                $name = e($row->name);

                $html = '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi ' . $name . '">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                if (check_access('position.edit')) {
                    $html .= '
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="editProduct(' . $row->id . ')">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </li>';
                }

                if (check_access('position.delete')) {
                    $html .= '
                        <li>
                            <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $row->id . ')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </li>';
                }
                $html .= '
                    </ul>
                </div>';
                return $html;
            })
            ->rawColumns(['name', 'action'])
            ->make(true);
    }

    public function getPosition(Request $request)
    {
        $departmentId = $request->get('department_id');

        $positions = Position::where('department_id', $departmentId)
            ->select('id', 'name')
            ->get();

        return response()->json($positions);
    }
}
