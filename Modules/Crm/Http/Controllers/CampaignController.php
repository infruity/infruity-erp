<?php

namespace Modules\Crm\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Modules\Crm\Entities\Campaign;
use Illuminate\Routing\Controller;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class CampaignController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('campaign.index')) {
            return $denied;
        }

        return view('crm::campaign.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('campaign.create')) {
            return $denied;
        }

        return view('crm::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('campaign.store')) {
            return $denied;
        }

        // dd($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type_promo' => 'required|string',
            'value_promo' => 'required'
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $campaign = new Campaign();
            $campaign->name = $validated['name'];
            $campaign->start_date = $validated['start_date'];
            $campaign->end_date = $validated['end_date'];
            $campaign->type_promo = $validated['type_promo'];
            $campaign->value = preg_replace('/[^0-9]/', '', $validated['value_promo']);
            $campaign->status = 'active'; // Set default status
            $campaign->created_by = Auth::id();
            $campaign->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Campaign gagal disimpan.' . $e->getMessage(),
                'data' => $campaign
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Campaign berhasil disimpan.',
            'data' => $campaign
        ], 201);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('crm::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('campaign.edit')) {
            return $denied;
        }

        $campaign = Campaign::findOrFail($id);
        return response()->json($campaign);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('campaign.update')) {
            return $denied;
        }

        // dd($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type_promo' => 'required|string',
            'value_promo' => 'required'
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $campaign = Campaign::findOrFail($id);
            $campaign->name = $validated['name'];
            $campaign->start_date = $validated['start_date'];
            $campaign->end_date = $validated['end_date'];
            $campaign->type_promo = $validated['type_promo'];
            $campaign->value = preg_replace('/[^0-9]/', '', $validated['value_promo']);
            $campaign->status = 'active'; // Set default status
            $campaign->created_by = Auth::id();
            $campaign->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Campaign gagal disimpan.',
                'data' => $campaign
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Campaign berhasil disimpan.',
            'data' => $campaign
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('campaign.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $campaign = Campaign::findOrFail($id);
            $campaign->delete();
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

    public function get_near_campaign()
    {
        if ($denied = $this->requireAccess('campaign.get-near-event')) {
            return $denied;
        }

        $today = date('Y-m-d');
        $campaign = Campaign::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', 'active')
            ->orderBy('end_date', 'asc')
            ->first();

        if ($campaign) {
            return response()->json([
                'success' => true,
                'data' => $campaign
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada campaign yang sedang berlangsung.'
            ], 404);
        }
    }

    public function get_data(Request $request)
    {
        $this->authorizeAccess('campaign.index');

        $data = Campaign::where('start_date', '>=', date('Y-m-d'))->orWhere('end_date', '>=', date('Y-m-d'))->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('value', function ($row) {
                if ($row->type_promo == 'discount') {
                    return $row->value . ' %';
                } else {
                    return 'Rp' . toNumber($row->value);
                }
            })
            ->addColumn('start_date', function ($row) {
                return \Carbon\Carbon::parse($row->start_date)->locale('id')->translatedFormat('d M Y');
            })
            ->addColumn('end_date', function ($row) {
                return \Carbon\Carbon::parse($row->end_date)->locale('id')->translatedFormat('d M Y');
            })
            ->addColumn('type_promo', function ($row) {
                if ($row->type_promo == 'discount') {
                    return ucwords($row->type_promo) . ' <span class="badge badge-light-primary">%</span>';
                } else {
                    return ucwords($row->type_promo) . ' <span class="badge badge-light-success">Rp</span>';
                }
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
            ->addColumn('exp', function ($row) {
                return toNumber($row->exp);
            })
            ->rawColumns(['name', 'type_promo', 'action'])
            ->make(true);
    }
}
