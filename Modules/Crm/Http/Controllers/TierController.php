<?php

namespace Modules\Crm\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Crm\Entities\Tier;
use Modules\Crm\Entities\ReportCustomer;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class TierController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('tier.index')) {
            return $denied;
        }

        return view('crm::tier.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('tier.create')) {
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
        if ($denied = $this->requireAccess('tier.store')) {
            return $denied;
        }

        // dd($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:crm_tier,name',
            'level' => 'required|integer|unique:crm_tier,level',
            'exp' => 'required',
            'style' => 'required|string|max:255',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $tier = new Tier();
            $tier->name = $validated['name'];
            $tier->level = $validated['level'];
            $tier->exp = preg_replace('/[^0-9]/', '', $validated['exp']);
            $tier->style = $validated['style'];
            $tier->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Tier gagal disimpan.',
                'data' => $tier
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Tier berhasil disimpan.',
            'data' => $tier
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
        if ($denied = $this->requireAccess('tier.edit')) {
            return $denied;
        }

        $tier = Tier::findOrFail($id);
        return response()->json($tier);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('tier.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:crm_tier,name,' . $id,
            'level' => 'required|integer|unique:crm_tier,level,' . $id,
            'exp' => 'required',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $tier = Tier::findOrFail($id);
            $tier->name = $validated['name'];
            $tier->level = $validated['level'];
            $tier->exp = preg_replace('/[^0-9]/', '', $validated['exp']);
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('tiers', 'public');
                $tier->icon = $path;
            }
            $tier->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Tier gagal disimpan.' . $e->getMessage(),
                'data' => $tier
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Tier berhasil disimpan.',
            'data' => $tier
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('tier.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $tier = Tier::findOrFail($id);
            $tier->delete();
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

    public function saveDetail(Request $request, $id)
    {
        if ($denied = $this->requireAccess('tier.save_detail')) {
            return $denied;
        }

        // dd($request->all());
        $validated = $request->validate([
            'discount_transaction' => 'nullable|numeric',
            'free_product_id' => 'nullable|array',
            'free_product_id.*' => 'exists:products,id',
            'birthday_gift' => 'nullable|boolean',
            'combo_promo' => 'nullable|boolean',
            'minimal_purchase' => 'nullable',
            'max_claim' => 'nullable|numeric',
            'voucher' => 'nullable',
            'deposito' => 'nullable',
        ]);
        try {
            DB::beginTransaction();

            $tier = Tier::findOrFail($id);
            $tier->discount_transaction = $validated['discount_transaction'];
            $tier->free_product_id = $validated['free_product_id'] ?? []; // simpan sebagai array
            $tier->birthday_gift = $validated['birthday_gift'] ?? null;
            $tier->combo_promo = $validated['combo_promo'] ?? null;
            $tier->minimal_purchase = preg_replace('/[^0-9]/', '', $validated['minimal_purchase'] ?? null);
            $tier->max_claim = $validated['max_claim'] ?? null;
            $tier->voucher = preg_replace('/[^0-9]/', '', $validated['voucher'] ?? null);
            $tier->deposito = preg_replace('/[^0-9]/', '', $validated['deposito'] ?? null);
            $tier->save();

            DB::commit();

            return response()->json([
                'message' => 'Detail berhasil disimpan.',
                'data' => $tier
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Gagal menyimpan detail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function customerReport(Request $request)
    {
        if ($denied = $this->requireAccess('customer-report.index')) {
            return $denied;
        }

        return view('crm::report.customer');
    }

    public function getGift($id)
    {
        if ($denied = $this->requireAccess('tier.get-gift')) {
            return $denied;
        }

        $tier = Tier::findOrFail($id);
        $freeProducts = $tier->freeProducts()->get();

        return response()->json($freeProducts);
    }

    public function listTier(Request $request)
    {
        if ($denied = $this->requireAccess('tier.list-tier')) {
            return $denied;
        }

        $search = $request->get('search');
        $tier = Tier::where('name', 'like', '%' . $search . '%')->get();

        return response()->json($tier);
    }

    public function get_data(Request $request)
    {
        $data = Tier::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('freeProduct', function ($row) {
                if (!empty($row->free_product_id) && is_array($row->free_product_id)) {
                    return json_decode($row->freeProducts()->get());
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
            ->rawColumns(['name', 'exp', 'action'])
            ->make(true);
    }

    public function customerReportData()
    {
        $data = ReportCustomer::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('type_customer', function ($row) {
                $type = strtolower($row->type_customer); // pastikan lowercase
                $options = ['reguler', 'member', 'mitra'];

                // Mapping class badge berbeda per type_customer
                $badgeClassMap = [
                    'reguler' => 'badge bg-secondary',
                    'member' => 'badge bg-primary',
                    'mitra' => 'badge bg-success',
                ];

                $badgeClass = $badgeClassMap[$type] ?? 'badge bg-dark';

                $dropdown = '<div class="dropdown">
        <span class="' . $badgeClass . ' dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
            ' . ucfirst($type) . '
        </span>
        <ul class="dropdown-menu p-0 m-0">';

                foreach ($options as $opt) {
                    $dropdown .= '<li>
            <a class="dropdown-item px-3 py-2 small"
               href="javascript:void(0)"
               onclick="changeTypeCustomer(' . $row->id_customer . ', \'' . $opt . '\')">'
                        . ucfirst($opt) .
                        '</a>
        </li>';
                }

                $dropdown .= '</ul></div>';

                return $dropdown;
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
                            <a class="dropdown-item" href="javascript:void(0)" onclick="editProduct(' . $row->id_customer . ')">
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
            ->addColumn('total_transaksi', function ($row) {
                return toNumber($row->total_transaksi);
            })
            ->rawColumns(['name', 'total_transaksi', 'action', 'type_customer'])
            ->make(true);
    }
}
