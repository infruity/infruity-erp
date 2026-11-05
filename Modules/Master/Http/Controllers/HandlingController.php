<?php

namespace Modules\Master\Http\Controllers;

use App\Support\DataTableCells;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Master\Entities\Handling;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use \Exception;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;

class HandlingController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('handling.index')) {
            return $denied;
        }

        return view('master::handling.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('handling.create')) {
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
        if ($denied = $this->requireAccess('handling.store')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:handling,name',
            'description' => 'nullable|string|max:1000',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $handling = new Handling();
            $handling->name = $validated['name'];
            $handling->description = $validated['description'] ?? null;
            $handling->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Penanganan gagal disimpan.',
                'data' => $handling
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Penanganan berhasil disimpan.',
            'data' => $handling
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
        if ($denied = $this->requireAccess('handling.edit')) {
            return $denied;
        }

        $handling = Handling::findOrFail($id);
        return response()->json($handling);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('handling.update')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:handling,name,' . $id,
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();
            $handling = Handling::findOrFail($id);
            $handling->name = $validated['name'];
            $handling->description = $validated['description'] ?? null;
            $handling->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Handling updated failed']);
        }

        return response()->json(['message' => 'Handling updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('handling.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $handling = Handling::findOrFail($id);
            $handling->delete();
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
        $data = Handling::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', fn ($item) => DataTableCells::title((string) $item->name))
            ->addColumn('action', fn ($row) => DataTableCells::editDeleteMenu(
                (string) $row->name,
                $row->id,
                check_access('handling.edit'),
                check_access('handling.delete')
            ))
            ->rawColumns(['name', 'action'])
            ->make(true);
    }
}
