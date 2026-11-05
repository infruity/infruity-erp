<?php

namespace Modules\Master\Http\Controllers;

use App\Support\DataTableCells;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Master\Entities\Location;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Auth;
use Exception;

class LocationController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('location.index')) {
            return $denied;
        }

        return view('master::location.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('location.create')) {
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
        if ($denied = $this->requireAccess('location.store')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $location = new Location();
            $location->name = $validated['name'];
            $location->address = $validated['address'] ?? null;
            $location->description = $validated['description'] ?? null;
            $location->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Stok Lokasi gagal disimpan.',
                'data' => $location
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Kategori berhasil disimpan.',
            'data' => $location
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
        if ($denied = $this->requireAccess('location.edit')) {
            return $denied;
        }

        $location = Location::findOrFail($id);
        return response()->json($location);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('location.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'string|max:255',
            'description' => 'string|max:255',
        ]);

        try {
            DB::beginTransaction();
            $location = Location::findOrFail($id);
            $location->name = $validated['name'];
            $location->address = $validated['address'] ?? null;
            $location->description = $validated['description'] ?? null;
            $location->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Stock Location updated failed']);
        }

        return response()->json(['message' => 'Stock Location updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('location.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $location = Location::findOrFail($id);
            $location->delete();
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
        $data = Location::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', fn ($item) => DataTableCells::title((string) $item->name))
            ->addColumn('action', fn ($row) => DataTableCells::editDeleteMenu(
                (string) $row->name,
                $row->id,
                check_access('location.edit'),
                check_access('location.delete')
            ))
            ->rawColumns(['name', 'action'])
            ->make(true);
    }
}
