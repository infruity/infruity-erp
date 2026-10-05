<?php

namespace Modules\Master\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\Kurir;
use Modules\Master\Entities\Staff;
use Yajra\DataTables\Facades\DataTables;

class KurirController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('kurir.index')) {
            return $denied;
        }

        return view('master::kurir.index', [
            'records' => Kurir::query()->select('id', 'name', 'type', 'staff_id', 'description')->orderByDesc('id')->get(),
            'staffOptions' => \Modules\Master\Entities\Staff::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('kurir.create')) {
            return $denied;
        }

        return redirect()->route('kurir.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('kurir.store')) {
            return $denied;
        }

        $validated = $request->validate([
            'type' => 'required|in:internal,external',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'staff_id' => 'required_if:type,internal|nullable|integer|exists:staff,id',
        ]);

        try {
            DB::beginTransaction();
            $kurir = new Kurir();
            $kurir->type = $validated['type'];
            $kurir->name = $validated['name'];
            $kurir->description = $validated['description'] ?? null;
            $kurir->staff_id = $request->type === 'internal' ? ($validated['staff_id'] ?? null) : null;
            $kurir->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Kurir gagal disimpan. ' . $e->getMessage(),
                'data' => $kurir,
            ], 404);
        }

        return response()->json([
            'message' => 'Kurir berhasil disimpan.',
            'data' => $kurir,
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
        if ($denied = $this->requireAccess('kurir.edit')) {
            return $denied;
        }

        $kurir = Kurir::findOrFail($id);
        return response()->json([
            'id' => $kurir->id,
            'type' => $kurir->type,
            'name' => $kurir->name,
            'description' => $kurir->description,
            'staff_id' => $kurir->staff_id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('kurir.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'type' => 'required|in:internal,external',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'staff_id' => 'required_if:type,internal|nullable|integer|exists:staff,id',
        ]);

        try {
            DB::beginTransaction();
            $kurir = Kurir::findOrFail($id);
            $kurir->type = $validated['type'];
            $kurir->name = $validated['name'];
            $kurir->description = $validated['description'] ?? null;
            $kurir->staff_id = $request->type === 'internal' ? ($validated['staff_id'] ?? null) : null;
            $kurir->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Kurir gagal disimpan.',
                'data' => $kurir,
            ], 404);
        }

        return response()->json([
            'message' => 'Kurir berhasil disimpan.',
            'data' => $kurir,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('kurir.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $kurir = Kurir::findOrFail($id);
            $kurir->delete();
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

    public function getKurir(Request $request)
    {
        $search = $request->get('search', $request->get('term', ''));

        // External kurir: dari tabel kurir dengan type 'external'
        $data = Kurir::query()
            ->where('name', 'like', '%' . $search . '%')
            ->select('id', 'name', 'type')
            ->get();

        return response()->json($data);
    }

    public function getStaff(Request $request)
    {
        $search = $request->get('search', $request->get('term', ''));
        $query = Staff::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('nickname', 'like', '%' . $search . '%');
            });
        }

        $staff = $query->select('id', 'name', 'nickname')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                $label = $item->nickname ? $item->name . ' (' . $item->nickname . ')' : $item->name;
                return [
                    'id' => $item->id,
                    'text' => $label,
                    'name' => $item->name,
                ];
            });

        return response()->json(['results' => $staff]);
    }

    public function get_data(Request $request)
    {
        $data = Kurir::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('type', function ($row) {
                $badgeClass = $row->type === 'internal' ? 'badge-success' : 'badge-warning';
                $label = $row->type === 'internal' ? 'Internal' : 'External';
                return '<span class="badge ' . $badgeClass . '">' . $label . '</span>';
            })
            ->addColumn('action', function ($row) {
                $name = e($row->name);

                return '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi ' . $name . '">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="editKurir(' . $row->id . ')">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteKurir(' . $row->id . ')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns(['type', 'name', 'action'])
            ->make(true);
    }
}
