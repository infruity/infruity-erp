<?php

namespace Modules\Master\Http\Controllers;

use App\Support\DataTableCells;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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


class DepartmentController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('department.index')) {
            return $denied;
        }

        return view('master::department.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('department.create')) {
            return $denied;
        }

        return view('master::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('department.store')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Simpan data ke database
        // Step 1: Buat akronim dari nama
        $name = $request->input('name');
        $words = preg_split('/\s+/', trim($name));
        $acronym = '';
        foreach ($words as $word) {
            $acronym .= strtoupper($word[0]);
        }

        // Step 2: Cari kode terakhir dari akronim
        $lastCode = Department::where('code', 'LIKE', $acronym . '%')
            ->orderBy('code', 'desc')
            ->value('code'); // ambil 1 kolom

        // Step 3: Ambil angka terakhir
        $number = 1;
        if ($lastCode) {
            $number = (int) substr($lastCode, strlen($acronym)) + 1;
        }

        // Step 4: Format kode baru
        $newCode = $acronym . str_pad($number, 3, '0', STR_PAD_LEFT);

        try {
            DB::beginTransaction();
            $department = new Department();
            $department->name = $validated['name'];
            $department->code = $newCode;
            $department->description = $validated['description'] ?? null;
            $department->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Department gagal disimpan.',
                'data' => $department
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Department berhasil disimpan.',
            'data' => $department
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
        if ($denied = $this->requireAccess('department.edit')) {
            return $denied;
        }

        $department = Department::findOrFail($id);
        return response()->json($department);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('department.update')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Simpan data ke database
        // Step 1: Buat akronim dari nama
        $name = $request->input('name');
        $words = preg_split('/\s+/', trim($name));
        $acronym = '';
        foreach ($words as $word) {
            $acronym .= strtoupper($word[0]);
        }

        // Step 2: Cari kode terakhir dari akronim
        $lastCode = Department::where('code', 'LIKE', $acronym . '%')
            ->orderBy('code', 'desc')
            ->value('code'); // ambil 1 kolom

        // Step 3: Ambil angka terakhir
        $number = 1;
        if ($lastCode) {
            $number = (int) substr($lastCode, strlen($acronym)) + 1;
        }

        // Step 4: Format kode baru
        $newCode = $acronym . str_pad($number, 3, '0', STR_PAD_LEFT);

        try {
            DB::beginTransaction();
            $department = Department::findOrFail($id);
            $department->name = $validated['name'];
            $department->code = $newCode;
            $department->description = $validated['description'] ?? null;
            $department->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Department gagal disimpan.',
                'data' => $department
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Department berhasil disimpan.',
            'data' => $department
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('department.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $department = Department::findOrFail($id);
            $department->delete();
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
        $data = Department::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', fn ($item) => DataTableCells::title((string) $item->name))
            ->addColumn('action', fn ($row) => DataTableCells::editDeleteMenu(
                (string) $row->name,
                $row->id,
                check_access('category.edit'),
                check_access('category.delete')
            ))
            ->rawColumns(['name', 'action'])
            ->make(true);
    }

    public function getDepartment(Request $request)
    {
        $departments = Department::select('id', 'name')
            ->get();

        return response()->json($departments);
    }
}
