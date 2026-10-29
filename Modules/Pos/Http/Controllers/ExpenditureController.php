<?php

namespace Modules\Pos\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pos\Entities\Expenditure;
use Modules\Pos\Entities\ExpenditureDetail;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Pos\Entities\ExpenditurePayment;
use Yajra\DataTables\Facades\DataTables;

class ExpenditureController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('expenditure.index')) {
            return $denied;
        }

        $branches = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        return view('pos::expenditure.index', compact('branches'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('expenditure.create')) {
            return $denied;
        }

        $data['alpinejs'] = true;
        $data['data'] = null;
        $data['detail'] = null;
        $data['invoice_number'] = Expenditure::getOrderNumber();
        return view('pos::expenditure.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('expenditure.store')) {
            return $denied;
        }

        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if ($denied = $this->requireAccess('expenditure.show')) {
            return $denied;
        }

        return view('pos::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('expenditure.edit')) {
            return $denied;
        }

        $data['alpinejs'] = true;
        $data['data'] = Expenditure::with('branch', 'payment', 'paymentMethod')->findOrFail($id);
        $data['detail'] = ExpenditureDetail::with('product', 'product.unit')->where('expenditure_id', $id)->get();
        $data['invoice_number'] = $data['data']->invoice_number;
        return view('pos::expenditure.create', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('expenditure.update')) {
            return $denied;
        }

        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('expenditure.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $pos = Expenditure::findOrFail($id);
            $pos->delete();
            ExpenditureDetail::where('expenditure_id', $id)->delete();
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

    public function saveTransaction(Request $request)
    {
        if ($denied = $this->requireAccess('expenditure.save-transaction')) {
            return $denied;
        }

        // dd($request->all());
        $data = $request->validate([
            // 'customer_id' => 'nullable|exists:customer,id',
            'branch_id' => 'nullable',
            'date' => 'required|date',
            'invoice_number' => 'nullable',
            'items' => 'required|array',
            'total' => 'required|numeric',
            'subtotal' => 'required|numeric',
            'status' => 'nullable|in:draft,paid,debt,temp,pending',
            'payment' => 'nullable|numeric',
            'payment_method' => 'nullable|numeric',
            'type' => 'required|in:pengeluaran,pemasukan',
        ]);

        try {
            $userId = Auth::id();
            DB::beginTransaction();
            $cek = Expenditure::where('invoice_number', $data['invoice_number'])->first();
            if ($cek) {
                $pos = Expenditure::find($cek->id);
                $posDetail = ExpenditureDetail::where('expenditure_id', $cek->id);
                ExpenditureDetail::where('expenditure_id', $cek->id)->delete();
                $pos->delete();
            }
            // Simpan ke tabel transaksi (buat dulu kalau belum ada)
            $pos = new Expenditure([
                'uuid' => Str::uuid(),
                'branch_id' => $data['branch_id'],
                'date' => $data['date'],
                'invoice_number' => Expenditure::getOrderNumber(),
                'subtotal' => $data['subtotal'],
                'total' => $data['total'],
                'paid' => $data['payment'] ?? 0,
                'payment_method' => $data['payment_method'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'type' => $data['type'],
                'created_by' => $userId,
            ]);
            $pos->created_at = now();
            $pos->save();

            // Simpan item transaksi
            $transaksiId = $pos->id;
            foreach ($data['items'] as $item) {
                ExpenditureDetail::insert([
                    'expenditure_id' => $transaksiId,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'product_unit' => $item['unit'],
                    'price' => $item['price'],
                    'quantity' => $item['qty'],
                    'discount' => $item['discount'] ?? 0,
                    'subtotal' => $item['total_input'],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'type' => 'product',
                    'created_by' => $userId,
                ]);
            }
            // dd($request->all());

            $payment = new ExpenditurePayment([
                'uuid' => Str::uuid(),
                'date' => $data['date'],
                'nota_number' => date('YmdHis'),
                'expenditure_id' => $pos->id,
                'branch_id' => $data['branch_id'],
                // 'account_id' => $data['account_id'],
                'payment_method_id' => $data['payment_method'],
                'total' => $data['payment'],
                'created_by' => Auth::user()->id_user,
            ]);
            // dd($payment);
            $payment->save();
            DB::commit();
            DB::disconnect();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'transaksi_id' => $transaksiId
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            DB::disconnect();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function get_data(Request $request)
    {
        $query = Expenditure::with('branch', 'payment')->whereIn('branch_id', UserBranch::getUserBranch());
        if ($request->has('status_filter') && $request->status_filter !== 'all') {
            $query = $query->where('status', $request->status_filter);
        }
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        if ($request->has('cabang_filter') && $request->cabang_filter !== 'all') {
            $query->where('branch_id', $request->cabang_filter);
        }
        $data = $query->orderBy('id', 'DESC')->get();
        // dd($data);
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $html = '<div class="d-flex align-items-center">';
                $html .= '<div class="ms-5">';
                if (isset($item->branch->name)) {
                    $html .= '<a href="' . url('pos') . '/show' . '/' . $item->id . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . $item->branch->name . '</a>';
                } else {
                    $html .= '<a href="' . url('pos') . '/show' . '/' . $item->id . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">Pelanggan Umum</a>';
                }
                $html .= '<br><span class="text-muted d-block fs-7">Total Rp' . tonumberround($item->total) . '</span>';
                $html .= '<span class="text-muted d-block fs-7">Sisa Rp' . tonumberround($item->total - $item->paid) . '</span>';
                return $html;
            })
            ->addColumn('date', function ($item) {
                $html = '<span class="text-muted d-block fs-8">' . date('d M Y H:i', strtotime($item->created_at)) . '</span>';
                if ($item->status == 'paid') {
                    $html .= '<span class="badge badge-light-success">Final</span>';
                } else if ($item->status == 'draft') {
                    $html .= '<span class="badge badge-light-danger">Draft</span>';
                } else {
                    $html .= '<span class="badge badge-light-warning">' . $item->status . '</span>';
                }
                return $html;
            })
            ->addColumn('action', function ($item) {
                $html = '';
                $html .= '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                $isRecent = false;
                $timeRef = isset($item->updated_at) ? $item->updated_at : (isset($item->created_at) ? $item->created_at : null);
                if ($timeRef && \Carbon\Carbon::parse($timeRef)->diffInMinutes(now()) <= 30) {
                    $isRecent = true;
                }

                if (in_array($item->status, ['temp', 'draft']) || $isRecent) {
                    $html .= '
                            <li>
                                <a class="dropdown-item" href="' . route('expenditure.edit', $item->id) . '">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </li>';
                }
                if (!in_array($item->status, ['paid', 'debt'])) {
                    $html .= '
                            <li>
                                <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $item->id . ')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </li>';
                }
                $html .= '
                        </ul>
                    </div>
                    ';
                return $html;
            })
            ->rawColumns(['name', 'action', 'date'])
            ->make(true);
    }
}
