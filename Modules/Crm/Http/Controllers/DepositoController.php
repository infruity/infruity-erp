<?php

namespace Modules\Crm\Http\Controllers;

use App\Support\DataTableCells;
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
            'deposito' => ['required', 'regex:/^[0-9][0-9.,]*$/'],
        ]);

        $pointSchedule = PointSchedule::first();
        if (! $pointSchedule) {
            return response()->json(['message' => 'Jadwal poin belum diatur.'], 422);
        }

        // Simpan data ke database
        try {
            DB::beginTransaction();

            $tier = Tier::findOrFail($validated['tier_id']);
            // dd($tier, $pointSchedule);

            $deposito = new Deposito();
            $deposito->customer_id = $validated['customer_id'];
            $deposito->deposito_date = $validated['date'];
            $deposito->tier_id = $validated['tier_id'];
            $deposito->start_period = $pointSchedule->start_date;
            $deposito->end_period = $pointSchedule->end_date;
            $deposito->voucher = $tier->voucher;
            $deposito->voucher_qty = (int) $tier->voucher > 0
                ? intdiv((int) $tier->deposito, (int) $tier->voucher)
                : 0;
            $deposito->exp = $tier->exp;
            $deposito->deposito = preg_replace('/[^0-9]/', '', $validated['deposito']);
            $deposito->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Deposito gagal disimpan.' . $e->getMessage(),
                'data' => null
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
            'deposito' => ['required', 'regex:/^[0-9][0-9.,]*$/'],
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $deposito = Deposito::findOrFail($id);
            $deposito->customer_id = $validated['customer_id'];
            $deposito->deposito_date = $validated['date'];
            $deposito->deposito = preg_replace('/[^0-9]/', '', $validated['deposito']);
            $deposito->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Deposito gagal disimpan.' . $e->getMessage(),
                'data' => null
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
        if ($denied = $this->requireAccess('deposito.index')) {
            return $denied;
        }
        $data = Deposito::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                return e($row->customer->name ?? '-');
            })
            ->addColumn('date', function ($row) {
                return DataTableCells::badge(date('d M Y', strtotime($row->deposito_date)), 'success');
            })
            ->addColumn('deposito', function ($row) {
                return 'Rp' . number_format($row->deposito, 0, ',', '.');
            })
            ->addColumn('action', fn ($row) => DataTableCells::editDeleteMenu((string) ($row->customer->name ?? ''), $row->id, true, true))
            ->rawColumns(['name', 'date', 'action'])
            ->make(true);
    }
    public function customer_deposito_get_data(Request $request)
    {
        if ($denied = $this->requireAccess('customer-deposito.index')) {
            return $denied;
        }
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
                return e($row->customer->name ?? '-');
            })
            ->addColumn('deposito', fn ($row) => DataTableCells::badge('Rp' . number_format($row->deposito, 0, ',', '.'), 'success')
                . '<br>' . DataTableCells::badge('Rp' . number_format($row->nominal_used, 0, ',', '.'), 'danger'))
            ->addColumn('voucher_qty', fn ($row) => DataTableCells::badge((string) $row->voucher_qty, 'success')
                . '<br>' . DataTableCells::badge((string) $row->voucher_used, 'danger'))
            ->addColumn('action', fn ($item) => DataTableCells::viewMenu(url('customer-deposito/show/'.$item->customer_id)))
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
            ->addColumn('name', fn ($item) => DataTableCells::title((string) ($item->customer->name ?? '-')))
            ->addColumn('nominal', function ($item) {
                if ($item->deposito > 0) {
                    return DataTableCells::badge((string) tonumber($item->deposito), 'success');
                } else {
                    return DataTableCells::badge((string) tonumber($item->deposito), 'danger');
                }
            })
            ->addColumn('voucher_qty', function ($item) {
                if ($item->voucher > 0) {
                    return DataTableCells::badge((string) $item->voucher_qty, 'success');
                } else {
                    return DataTableCells::badge((string) $item->voucher_qty, 'danger');
                }
            })
            ->addColumn('date', function ($item) {
                return dateEnglish($item->deposito_date);
            })
            ->rawColumns(['name', 'nominal', 'voucher_qty', 'date'])
            ->make(true);
    }
}
