<?php

namespace Modules\Master\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Master\Entities\ProductUnit;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Auth;
use Exception;

class ProductUnitController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('unit.index')) {
            return $denied;
        }

        $units = ProductUnit::query()
            ->select(['id', 'name', 'abbreviation', 'description', 'created_at', 'updated_at'])
            ->orderBy('name')
            ->get();

        return view('master::unit.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('unit.create')) {
            return $denied;
        }

        return redirect()->route('unit.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('unit.store')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_units,name',
            'abbreviation' => 'required|string|max:255|unique:product_units,abbreviation',
            'description' => 'nullable|string|max:1000',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $unit = new ProductUnit();
            $unit->name = $validated['name'];
            $unit->abbreviation = $validated['abbreviation'];
            $unit->description = $validated['description'] ?? null;
            $unit->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Satuan gagal disimpan.',
                'data' => $unit
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Satuan berhasil disimpan.',
            'data' => $unit
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
        if ($denied = $this->requireAccess('unit.edit')) {
            return $denied;
        }

        $unit = ProductUnit::findOrFail($id);
        return response()->json($unit);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('unit.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();
            $unit = ProductUnit::findOrFail($id);
            $unit->name = $validated['name'];
            $unit->abbreviation = $validated['abbreviation'];
            $unit->description = $validated['description'] ?? null;
            $unit->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Satuan gagal diperbarui.'], 500);
        }

        return response()->json(['message' => 'Unit updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('unit.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $unit = ProductUnit::findOrFail($id);
            $unit->delete();
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
        $data = ProductUnit::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $colors = ['warning', 'success', 'info', 'primary'];
                $color = $colors[$item->id % count($colors)];

                return '<div class="d-flex align-items-center">
                            <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                <a href="javascript:void(0)">
                                    <div class="symbol-label fs-3 bg-light-' . $color . ' text-' . $color . '">' . $item->abbreviation . '</div>
                                </a>
                            </div>
                            <div class="ms-5">
                                <a href="javascript:void(0)" class="text-gray-800 text-hover-primary fs-5 fw-bold d-flex">' . $item->name . '</a>
                                <span class="text-muted fw-semibold d-block">' . $item->description . '</span>
                            </div>
                        </div>';
            })
            ->addColumn('action', function ($row) {
                $name = e($row->name);

                $html = '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi ' . $name . '">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                if (check_access('unit.edit')) {
                    $html .= '
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="editProduct(' . $row->id . ')">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </li> ';
                }
                if (check_access('unit.delete')) {
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
}
