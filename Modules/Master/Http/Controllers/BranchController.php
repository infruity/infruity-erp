<?php

namespace Modules\Master\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('branch.index')) {
            return $denied;
        }

        return view('master::branch.index', ['records' => Branch::query()->select('id', 'name', 'code', 'address')->orderByDesc('id')->get()]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('branch.create')) {
            return $denied;
        }

        return redirect()->route('branch.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('branch.store')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branch,name',
            'code' => 'required|string|max:255|unique:branch,code',
            'address' => 'nullable|string|max:1000',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $branch = new Branch();
            $branch->name = $validated['name'];
            $branch->code = $validated['code'];
            $branch->address = $validated['address'] ?? null;
            $branch->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Branch gagal disimpan. ' . $e->getMessage(),
                'data' => $branch,
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Branch berhasil disimpan.',
            'data' => $branch,
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
        if ($denied = $this->requireAccess('branch.edit')) {
            return $denied;
        }

        $branch = Branch::findOrFail($id);
        return response()->json($branch);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('branch.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:branch,name,' . $id,
            'code' => 'required|string|max:255|unique:branch,code,' . $id,
            'address' => 'nullable|string|max:1000',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $branch = Branch::findOrFail($id);
            $branch->name = $validated['name'];
            $branch->code = $validated['code'];
            $branch->address = $validated['address'] ?? null;
            $branch->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Branch gagal disimpan.',
                'data' => $branch,
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Branch berhasil disimpan.',
            'data' => $branch,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('branch.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $branch = Branch::findOrFail($id);
            $branch->delete();
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

    public function getBranch(Request $request)
    {
        $search = $request->get('search', $request->get('term', ''));
        $showAll = $request->boolean('show_all');

        $query = Branch::query()
            ->where('name', 'like', '%' . $search . '%')
            ->select('id', 'name');

        if (! $showAll) {
            $query->whereIn('id', UserBranch::getUserBranch());
        }

        $data = $query
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    public function get_data(Request $request)
    {
        $data = Branch::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $name = e($row->name);

                return '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi ' . $name . '">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="editProduct(' . $row->id . ')">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $row->id . ')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns(['name', 'quantity', 'action'])
            ->make(true);
    }
}
