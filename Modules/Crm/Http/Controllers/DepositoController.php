<?php

namespace Modules\Crm\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Crm\Entities\Deposito;
use Modules\Crm\Entities\PointSchedule;
use Modules\Crm\Entities\Tier;
use Modules\Crm\Entities\CustomerDeposito;
use Modules\Master\Entities\Customer;
use Modules\Crm\Entities\DepositoTransaction;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class DepositoController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('deposito.index')) {
            return $denied;
        }

        return view('crm::deposito.index');
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function customer_deposito()
    {
        if ($denied = $this->requireAccess('customer-deposito.index')) {
            return $denied;
        }

        return view('crm::deposito.customer-deposito');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('deposito.create')) {
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
        if ($denied = $this->requireAccess('deposito.store')) {
            return $denied;
        }

        // dd($request->all());
        $validated = $request->validate([
            'customer_id' => 'required|exists:customer,id',
            'tier_id' => 'required|exists:crm_tier,id',
            'date' => 'required|date',
            'deposito' => 'required',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();

            $tier = Tier::findOrFail($validated['tier_id']);
            $pointSchedule = PointSchedule::first();
            // dd($tier, $pointSchedule);

            $deposito = new Deposito();
            $deposito->customer_id = $validated['customer_id'];
            $deposito->deposito_date = $validated['date'];
            $deposito->tier_id = $validated['tier_id'];
            $deposito->start_period = $pointSchedule->start_date;
            $deposito->end_period = $pointSchedule->end_date;
            $deposito->voucher = $tier->voucher;
            $deposito->voucher_qty = $tier->deposito / $tier->voucher;
            $deposito->exp = $tier->exp;
            $deposito->deposito = preg_replace('/[^0-9]/', '', $validated['deposito']);
            $deposito->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Deposito gagal disimpan.' . $e->getMessage(),
                'data' => $deposito
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Deposito berhasil disimpan.',
            'data' => $deposito
        ], 201);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if ($denied = $this->requireAccess('customer-deposito.show')) {
            return $denied;
        }

        $data['customer'] = Customer::find($id);
        return view('crm::deposito.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('deposito.edit')) {
            return $denied;
        }

        $deposito = Deposito::with('customer')->findOrFail($id);
        return response()->json($deposito);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('deposito.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customer,id',
            'date' => 'required|date',
            'deposito' => 'required',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $deposito = Deposito::findOrFail($id);
            $deposito->customer_id = $validated['customer_id'];
            $deposito->date = $validated['date'];
            $deposito->deposito = preg_replace('/[^0-9]/', '', $validated['deposito']);
            $deposito->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Deposito gagal disimpan.' . $e->getMessage(),
                'data' => $deposito
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Deposito berhasil disimpan.',
            'data' => $deposito
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('deposito.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $deposito = Deposito::findOrFail($id);
            $deposito->delete();
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
        $data = Deposito::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                return $row->customer->name;
            })
            ->addColumn('date', function ($row) {
                return '<span class="badge badge-light-success">' . date('d M Y', strtotime($row->deposito_date)) . '</span>';
            })
            ->addColumn('deposito', function ($row) {
                return 'Rp' . number_format($row->deposito, 0, ',', '.');
            })
            ->addColumn('action', function ($row) {
                // $name = e($row->name);
                return '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
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
            ->rawColumns(['name', 'date', 'action'])
            ->make(true);
    }
    public function customer_deposito_get_data(Request $request)
    {
        $data = CustomerDeposito::with('customer')->select(
            'customer_id',
            DB::raw('SUM(deposito) as deposito'),
            DB::raw('SUM(voucher_qty) as voucher_qty'),
            DB::raw('SUM(total_used_voucher) as voucher_used'),
            DB::raw('SUM(nominal_using_voucher) as nominal_used'),
            DB::raw('SUM(quantity) as voucher_remaining')
        )
            ->groupBy('customer_id')
            ->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                return $row->customer->name;
            })
            ->addColumn('deposito', function ($row) {
                $html = '<span class="badge badge-light-success">';
                $html .= 'Rp' . number_format($row->deposito, 0, ',', '.');
                $html .= '</span><br>';
                $html .= '<span class="badge badge-light-danger">';
                $html .= 'Rp' . number_format($row->nominal_used, 0, ',', '.');
                $html .= '</span><br>';
                return $html;
            })
            ->addColumn('voucher_qty', function ($row) {
                $html = '<span class="badge badge-light-success">';
                $html .= $row->voucher_qty;
                $html .= '</span><br>';
                $html .= '<span class="badge badge-light-danger">';
                $html .= $row->voucher_used;
                $html .= '</span><br>';
                return $html;
            })
            ->addColumn('action', function ($item) {
                return '
                    <a href="' . url('customer-deposito/show') . '/' . $item->customer_id . '" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="tooltip" title="View">
                        <i class="fa fa-eye"></i>
                    </a>
                ';
            })
            ->rawColumns(['name', 'deposito', 'voucher_qty', 'action'])
            ->make(true);
    }

    public function customer_deposito_transaction_get_data(Request $request)
    {
        $query = DepositoTransaction::with(['customer', 'deposito'])
            ->where('customer_id', $request->customer_id);
        $data = $query->get();
        $totalVoucher = $query->sum('voucher_qty');
        $totalRemaining = $query->sum('deposito');
        return DataTables::of($data)
            ->with([
                'total_voucher' => $totalVoucher,
                'total_remaining' => $totalRemaining,
            ])
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $html = '
                    <div class="d-flex align-items-center">';
                $html .= '<div class="ms-5">
                            <a href="#" class="text-gray-800 text-hover-primary fs-5 fw-bold" 
                               data-kt-ecommerce-product-filter="product_name">
                                ' . e($item->customer->name) . '
                            </a>
                        </div>
                    </div>
                ';
                return $html;
            })
            ->addColumn('nominal', function ($item) {
                if ($item->deposito > 0) {
                    return '<span class="badge badge-light-success">' . tonumber($item->deposito) . '</span>';
                } else {
                    return '<span class="badge badge-light-danger">' . tonumber($item->deposito) . '</span>';
                }
            })
            ->addColumn('voucher_qty', function ($item) {
                if ($item->voucher > 0) {
                    return '<span class="badge badge-light-success">' . $item->voucher_qty . '</span>';
                } else {
                    return '<span class="badge badge-light-danger">' . $item->voucher_qty . '</span>';
                }
            })
            ->addColumn('date', function ($item) {
                return dateEnglish($item->deposito_date);
            })
            ->rawColumns(['name', 'nominal', 'voucher_qty', 'date'])
            ->make(true);
    }
}
