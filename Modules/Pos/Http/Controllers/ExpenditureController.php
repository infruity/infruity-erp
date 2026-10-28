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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
        $data['data'] = Expenditure::with('branch', 'payment', 'paymentMethod')
            ->whereIn('branch_id', UserBranch::getUserBranch())->findOrFail($id);
        abort_unless($this->canEdit($data['data']), 409, 'Transaksi final sudah tidak dapat diubah.');
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
            $pos = Expenditure::whereIn('branch_id', UserBranch::getUserBranch())->lockForUpdate()->findOrFail($id);
            $pos->setRelation('payment', ExpenditurePayment::where('expenditure_id', $pos->id)->lockForUpdate()->get());
            abort_unless($this->canDelete($pos), 409, 'Transaksi final atau transaksi dengan pembayaran tidak dapat dihapus.');
            $pos->delete();
            ExpenditureDetail::where('expenditure_id', $id)->delete();
            ExpenditurePayment::where('expenditure_id', $id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.'
            ]);
        } catch (HttpExceptionInterface | ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
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
        $this->authorizeAccess('expenditure.save-transaction');
        $branchIds = array_map('intval', UserBranch::getUserBranch());
        $data = $request->validate([
            'expenditure_id' => ['present', 'nullable', 'integer', 'min:1'],
            'branch_id' => ['required', 'integer', Rule::in($branchIds)],
            'date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:255'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_input' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:draft,paid,debt,temp,pending'],
            'payment' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'integer', 'exists:payment_method,id', Rule::requiredIf((float) $request->input('payment', 0) > 0)],
            'type' => ['required', 'in:pengeluaran,pemasukan'],
        ]);

        try {
            $transactionId = DB::transaction(function () use ($data, $branchIds) {
                $userId = Auth::id();
                if (! empty($data['expenditure_id'])) {
                    $pos = Expenditure::whereIn('branch_id', $branchIds)
                        ->lockForUpdate()->findOrFail($data['expenditure_id']);
                    abort_if((int) $pos->branch_id !== (int) $data['branch_id'], 422, 'Cabang transaksi yang sudah tersimpan tidak dapat dipindahkan.');
                    abort_unless($this->canEdit($pos), 409, 'Transaksi final sudah tidak dapat diubah.');
                    abort_if(in_array($pos->status, ['paid', 'debt'], true)
                        && ! in_array($data['status'] ?? 'draft', ['paid', 'debt'], true),
                        409, 'Transaksi final tidak dapat dikembalikan menjadi draft.');
                } else {
                    // A preview invoice is never the identity of an existing transaction.
                    $pos = new Expenditure([
                        'uuid' => Str::uuid(),
                        'invoice_number' => Expenditure::getOrderNumber(),
                        'created_by' => $userId,
                    ]);
                }

                $payments = $pos->exists
                    ? ExpenditurePayment::where('expenditure_id', $pos->id)->lockForUpdate()->get()
                    : collect();
                $amount = (float) ($data['payment'] ?? $pos->paid ?? 0);
                $method = $data['payment_method'] ?? $pos->payment_method;
                if ($payments->count() > 1) {
                    $netPaid = (float) $payments->sum(fn ($payment) => (float) $payment->total - (float) $payment->return);
                    abort_if(abs($amount - $netPaid) > 0.005 || (int) $method !== (int) $pos->payment_method,
                        409, 'Pembayaran multi-nota tidak dapat diubah melalui editor transaksi.');
                    $amount = $netPaid;
                }

                $pos->fill([
                    'branch_id' => $data['branch_id'], 'date' => $data['date'], 'total' => $data['total'],
                    'paid' => $amount, 'payment_method' => $method, 'status' => $data['status'] ?? 'draft',
                    'type' => $data['type'],
                ]);
                $pos->updated_by = $userId;
                $pos->save();
                ExpenditureDetail::where('expenditure_id', $pos->id)->delete();
                foreach ($data['items'] as $item) {
                    ExpenditureDetail::insert([
                        'expenditure_id' => $pos->id, 'product_id' => $item['id'] ?? null,
                        'product_name' => $item['name'], 'product_unit' => $item['unit'] ?? null,
                        'price' => $item['price'], 'quantity' => $item['qty'], 'discount' => $item['discount'] ?? 0,
                        'subtotal' => $item['total_input'], 'created_at' => now(), 'updated_at' => now(),
                        'type' => 'product', 'created_by' => $userId,
                    ]);
                }

                if ($payments->count() === 1) {
                    // The editor carries cumulative paid, not a new payment intent.
                    $payment = $payments->first();
                    $payment->fill(['total' => $amount + (float) $payment->return,
                        'payment_method_id' => $method, 'date' => $data['date'], 'branch_id' => $pos->branch_id]);
                    $payment->updated_by = $userId;
                    $payment->save();
                } elseif ($payments->isEmpty() && $amount > 0) {
                    ExpenditurePayment::create([
                        'uuid' => Str::uuid(), 'date' => $data['date'], 'nota_number' => now()->format('YmdHis'),
                        'expenditure_id' => $pos->id, 'branch_id' => $pos->branch_id,
                        'payment_method_id' => $method, 'total' => $amount, 'created_by' => $userId,
                    ]);
                }

                return $pos->id;
            });

            return response()->json(['success' => true, 'message' => 'Transaksi berhasil disimpan', 'transaksi_id' => $transactionId]);
        } catch (HttpExceptionInterface | ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan transaksi'], 500);
        }
    }

    private function canDelete(Expenditure $transaction): bool
    {
        return ! in_array($transaction->status, ['paid', 'debt'], true)
            && (float) $transaction->paid === 0.0
            && ! $transaction->payment->contains(fn ($payment) => (float) $payment->total != 0 || (float) $payment->return != 0);
    }

    private function canEdit(Expenditure $transaction): bool
    {
        return in_array($transaction->status, ['temp', 'draft'], true)
            || (in_array($transaction->status, ['pending', 'paid', 'debt'], true)
                && $transaction->updated_at && $transaction->updated_at->gte(now()->subMinutes(30)));
    }

    public function get_data(Request $request)
    {
        $this->authorizeAccess('expenditure.index');
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
        $canEdit = $this->hasAccess('expenditure.edit');
        $canDelete = $this->hasAccess('expenditure.destroy');
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
            ->addColumn('action', function ($item) use ($canEdit, $canDelete) {
                $html = '';
                $html .= '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                if ($canEdit && $this->canEdit($item)) {
                    $html .= '
                            <li>
                                <a class="dropdown-item" href="' . route('expenditure.edit', $item->id) . '">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </li>';
                }
                if ($canDelete && $this->canDelete($item)) {
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
