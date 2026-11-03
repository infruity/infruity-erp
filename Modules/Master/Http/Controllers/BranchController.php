<?php

namespace Modules\Master\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

        $records = Branch::query()
            ->leftJoin('reg_provinces as provinces', 'branch.province', '=', 'provinces.id')
            ->leftJoin('reg_regencies as cities', 'branch.city', '=', 'cities.id')
            ->leftJoin('reg_districts as districts', 'branch.district', '=', 'districts.id')
            ->leftJoin('reg_villages as villages', 'branch.village', '=', 'villages.id')
            ->select('branch.id', 'branch.name', 'branch.code', 'branch.address', 'branch.province', 'branch.city', 'branch.district', 'branch.village', 'branch.map_link', 'provinces.name as province_name', 'cities.name as city_name', 'districts.name as district_name', 'villages.name as village_name')
            ->orderByDesc('branch.id')
            ->get();

        return view('master::branch.index', ['records' => $records]);
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
            'address' => 'required|string|max:1000',
            'province' => ['required', 'string', 'exists:reg_provinces,id'],
            'city' => ['required', 'string', Rule::exists('reg_regencies', 'id')->where(fn ($query) => $query->where('province_id', $request->input('province')))],
            'district' => ['required', 'string', Rule::exists('reg_districts', 'id')->where(fn ($query) => $query->where('regency_id', $request->input('city')))],
            'village' => ['required', 'string', Rule::exists('reg_villages', 'id')->where(fn ($query) => $query->where('district_id', $request->input('district')))],
            'map_link' => 'nullable|url|max:2048',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $branch = new Branch();
            $identity = $this->generateBranchIdentity($validated);
            $branch->name = $identity['name'];
            $branch->code = $identity['code'];
            $branch->address = $validated['address'];
            $branch->province = $validated['province'];
            $branch->city = $validated['city'];
            $branch->district = $validated['district'];
            $branch->village = $validated['village'];
            $branch->map_link = $validated['map_link'] ?? null;
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
            'address' => 'required|string|max:1000',
            'province' => ['required', 'string', 'exists:reg_provinces,id'],
            'city' => ['required', 'string', Rule::exists('reg_regencies', 'id')->where(fn ($query) => $query->where('province_id', $request->input('province')))],
            'district' => ['required', 'string', Rule::exists('reg_districts', 'id')->where(fn ($query) => $query->where('regency_id', $request->input('city')))],
            'village' => ['required', 'string', Rule::exists('reg_villages', 'id')->where(fn ($query) => $query->where('district_id', $request->input('district')))],
            'map_link' => 'nullable|url|max:2048',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $branch = Branch::findOrFail($id);
            $branch->address = $validated['address'];
            $branch->province = $validated['province'];
            $branch->city = $validated['city'];
            $branch->district = $validated['district'];
            $branch->village = $validated['village'];
            $branch->map_link = $validated['map_link'] ?? null;
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

    private function generateBranchIdentity(array $validated): array
    {
        $cityName = DB::table('reg_regencies')->where('id', $validated['city'])->value('name');
        $districtName = DB::table('reg_districts')->where('id', $validated['district'])->value('name');
        $villageName = DB::table('reg_villages')->where('id', $validated['village'])->value('name');

        $cityName = preg_replace('/^(?:KABUPATEN|KAB\\.?|KOTA)\\s+/iu', '', (string) $cityName);
        $nameBase = collect([$cityName, $districtName, $villageName])
            ->map(fn ($name) => Str::title(mb_strtolower(trim((string) $name))))
            ->implode(' / ');

        $nameSequence = 1;
        do {
            $name = $nameBase . ' - ' . str_pad((string) $nameSequence++, 3, '0', STR_PAD_LEFT);
        } while (DB::table('branch')->where('name', $name)->exists());

        $lastCodeNumber = DB::table('branch')
            ->where('code', 'like', 'CBG-%')
            ->pluck('code')
            ->map(fn ($code) => preg_match('/^CBG-(\\d+)$/i', (string) $code, $matches) ? (int) $matches[1] : 0)
            ->max() ?? 0;

        $codeNumber = $lastCodeNumber + 1;
        do {
            $code = 'CBG-' . str_pad((string) $codeNumber++, 4, '0', STR_PAD_LEFT);
        } while (DB::table('branch')->where('code', $code)->exists());

        return ['name' => $name, 'code' => $code];
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
