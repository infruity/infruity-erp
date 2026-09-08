<?php
namespace Modules\Transaction\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;
use Modules\Transaction\Entities\Transfer;
use Modules\Transaction\Entities\TransferDetail;
use Modules\Transaction\Entities\TransferDetailCorrection;
use Yajra\DataTables\Facades\DataTables;

class TransferController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('transfer.index')) {
            return $denied;
        }

        $data['branches'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        return view('transaction::transfer.index2', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('transfer.create')) {
            return $denied;
        }

        $data['alpinejs'] = true;
        $userId           = Auth::id();

        // Cek apakah ada temp transaksi untuk user ini (seperti pada PosController)
        $draft = Transfer::with([
            'branch',
            'branchDestination',
            'createdBy',
            'detail',
            'detail.product',
            'detail.product.unit',
            'detail.corrections.user',
        ])
            ->where('created_by', $userId)
            ->where('status', 'temp')
            ->orderBy('id', 'desc')
            ->first();

        if ($draft) {
            $data['data']           = $draft;
            $data['detail']         = $draft->detail;
            $data['invoice_number'] = $draft->invoice_number;
        } else {
            $invoiceNumber = Transfer::getOrderNumber();

            $draft = new Transfer();
            $draft->uuid           = Str::uuid();
            $draft->invoice_number = $invoiceNumber;
            $draft->created_by     = $userId;
            $draft->status         = 'temp';
            $draft->date           = date('Y-m-d');
            $draft->total          = 0;
            $draft->save();

            $data['data']           = $draft;
            $data['detail']         = [];
            $data['invoice_number'] = $invoiceNumber;
        }

        $data['type']     = request()->segment(1);
        $data['branches'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();

        return view('transaction::transfer.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('transfer.store')) {
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
        if ($denied = $this->requireAccess('transfer.show')) {
            return $denied;
        }

        $transfer = Transfer::with('branch', 'branchDestination', 'createdBy')->findOrFail($id);
        $type = request()->segment(1);

        // Ubah status ke proses jika status sebelumnya pending dan bukan pengirim yang melihat
        if ($transfer->status == 'pending' && $type != 'transfer-pengirim') {
            $transfer->update(['status' => 'proses']);
            $transfer->refresh();
        }

        $data['alpinejs']       = true;
        $data['data']           = $transfer;
        $data['detail']         = TransferDetail::with(['product', 'product.unit', 'corrections.user'])->where('transfer_id', $id)->get();
        $data['invoice_number'] = $transfer->invoice_number;
        $data['is_view']        = true;
        $data['type']           = $type;
        return view('transaction::transfer.create', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('transfer.edit')) {
            return $denied;
        }

        $data['alpinejs']       = true;
        $data['data']           = Transfer::with('branch', 'branchDestination', 'createdBy')->findOrFail($id);
        $data['detail']         = TransferDetail::with(['product', 'product.unit', 'corrections.user'])->where('transfer_id', $id)->get();
        $data['invoice_number'] = $data['data']->invoice_number;
        $data['type']           = request()->segment(1);
        return view('transaction::transfer.create', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('transfer.update')) {
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
        if ($denied = $this->requireAccess('transfer.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $pos = Transfer::findOrFail($id);
            $pos->delete();
            TransferDetail::where('transfer_id', $id)->delete();
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

    public function saveTransaction(Request $request)
    {
        if ($denied = $this->requireAccess('transfer.save-transaction')) {
            return $denied;
        }

        // dd($request->all());
        $data = $request->validate([
            'transfer_id'           => 'nullable|exists:transfer,id',
            'branch_id'             => 'required|exists:branch,id',
            'branch_destination_id' => 'required|exists:branch,id',
            'date'                  => 'required|date',
            'invoice_number'        => 'nullable',
            'items'                 => 'required|array',
            'total'                 => 'required|numeric',
            'subtotal'              => 'required|numeric',
            'status'                => 'nullable|in:temp,draft,pending,proses,selesai',
        ]);

        try {
            $userId        = Auth::id();
            $invoiceNumber = $data['invoice_number'] ?? null;
            $transferId    = $data['transfer_id'] ?? null;

            DB::beginTransaction();

            $pos = null;
            if (!empty($transferId)) {
                $pos = Transfer::where('id', $transferId)
                    ->where(function ($q) use ($userId) {
                        $q->where('created_by', $userId)
                          ->orWhere('status', '!=', 'temp');
                    })
                    ->first();
            }

            if (!$pos && !empty($invoiceNumber)) {
                $pos = Transfer::where('invoice_number', $invoiceNumber)
                    ->where('created_by', $userId)
                    ->first();
            }

            if ($pos) {
                // Update transaksi transfer yang sudah ada
                if ($pos->status === 'selesai') {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaksi yang sudah selesai tidak dapat diubah.',
                    ], 422);
                }

                $pos->update([
                    'branch_id'             => $data['branch_id'],
                    'branch_destination_id' => $data['branch_destination_id'],
                    'date'                  => $data['date'],
                    'total'                 => $data['total'],
                    'status'                => $data['status'] ?? 'draft',
                    'updated_by'            => $userId,
                ]);

                TransferDetail::where('transfer_id', $pos->id)->delete();
            } else {
                // Pastikan invoice_number unik dan belum pernah dipakai transaksi lain
                $newInvoice = $invoiceNumber;
                if (empty($newInvoice) || Transfer::where('invoice_number', $newInvoice)->exists()) {
                    $newInvoice = Transfer::getOrderNumber();
                }

                $pos = new Transfer([
                    'uuid'                  => Str::uuid(),
                    'branch_id'             => $data['branch_id'],
                    'branch_destination_id' => $data['branch_destination_id'],
                    'date'                  => $data['date'],
                    'invoice_number'        => $newInvoice,
                    'total'                 => $data['total'],
                    'status'                => $data['status'] ?? 'draft',
                    'created_by'            => $userId,
                ]);
                $pos->created_at = now();
                $pos->save();
            }

            // Hapus temp draft lain milik user ini jika ada (seperti deleteDuplicatePosDrafts)
            Transfer::where('created_by', $userId)
                ->where('status', 'temp')
                ->where('id', '!=', $pos->id)
                ->delete();

            // Simpan item transaksi
            $transaksiId = $pos->id;
            foreach ($data['items'] as $item) {
                if (is_numeric($item['id'])) {
                    TransferDetail::insert([
                        'transfer_id' => $transaksiId,
                        'product_id'  => $item['id'],
                        'price'       => $item['price'],
                        'quantity'    => $item['qty'],
                        'discount'    => $item['discount'] ?? 0,
                        'subtotal'    => $item['total_input'],
                        'created_at'  => now(),
                        'created_by'  => $userId,
                    ]);
                }
            }

            DB::commit();

            $type = request('type');
            $redirectUrl = '/transfer';
            if ($type === 'transfer-penerima') {
                $redirectUrl = '/transfer-penerima';
            } elseif ($type === 'transfer-pengirim') {
                $redirectUrl = '/transfer-pengirim';
            }

            return response()->json([
                'success'      => true,
                'message'      => 'Transaksi berhasil disimpan',
                'transaksi_id' => $transaksiId,
                'redirect_url' => $redirectUrl,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function saveCorrection(Request $request)
    {
        if ($denied = $this->requireAccess('transfer.save-correction')) {
            return $denied;
        }

        $request->validate([
            'detail_id' => 'required|exists:transfer_detail,id',
            'quantity'  => 'required|numeric|min:0',
            'note'      => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $detail   = TransferDetail::findOrFail($request->detail_id);
            $transfer = Transfer::findOrFail($detail->transfer_id);

            if ($transfer->status === 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengoreksi transaksi yang sudah selesai.',
                ], 422);
            }

            $oldQty = $detail->quantity;

            // Record correction history
            TransferDetailCorrection::create([
                'transfer_detail_id' => $detail->id,
                'old_quantity'       => $oldQty,
                'new_quantity'       => $request->quantity,
                'note'               => $request->note,
                'created_by'         => Auth::id(),
            ]);

            // Update detail
            $detail->quantity = $request->quantity;
            $detail->subtotal = $detail->price * $request->quantity;
            $detail->save();

            // Update total transfer
            $transfer        = Transfer::find($detail->transfer_id);
            $total           = TransferDetail::where('transfer_id', $transfer->id)->sum('subtotal');
            $transfer->total = $total;
            $transfer->save();

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => 'Quantity berhasil dikoreksi',
                'new_subtotal' => $detail->subtotal,
                'new_total'    => $transfer->total,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengoreksi quantity: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function set_selesai($id)
    {
        if ($denied = $this->requireAccess('transfer.set_selesai')) {
            return $denied;
        }

        try {
            $transfer = Transfer::findOrFail($id);
            $transfer->update([
                'status' => 'selesai',
                'pic_penerima' => \Illuminate\Support\Facades\Auth::id(),
                'tanggal_diterima' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diubah menjadi selesai',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function get_data(Request $request)
    {
        $userBranches = UserBranch::getUserBranch();
        $query        = Transfer::with(['createdBy', 'picPenerima', 'branch', 'branchDestination'])
            ->withSum('corrections as total_old', 'old_quantity')
            ->withSum('corrections as total_new', 'new_quantity');

        if ($request->url == 'transfer-pengirim') {
            $query->whereIn('branch_id', $userBranches);
        } elseif ($request->url == 'transfer-penerima') {
            $query->whereIn('branch_destination_id', $userBranches);
        } else {
            $query->where(function ($q) use ($userBranches) {
                $q->whereIn('branch_id', $userBranches)
                    ->orWhereIn('branch_destination_id', $userBranches);
            });
        }

        if ($request->has('status_filter') && $request->status_filter !== 'all') {
            $query = $query->where('status', $request->status_filter);
        }
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        if ($request->has('cabang_filter') && $request->cabang_filter !== 'all') {
            if ($request->url == 'transfer-penerima') {
                $query->where('branch_destination_id', $request->cabang_filter);
            } else {
                $query->where('branch_id', $request->cabang_filter);
            }
        }
        $data = $query->orderBy('date', 'DESC')->orderBy('id', 'DESC');
        // dd($data);
        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                $search = trim($request->input('search.value'));

                if (! empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('invoice_number', 'LIKE', "%{$search}%")
                            ->orWhereHas('createdBy', function ($sub) use ($search) {
                                $sub->where('nm_user', 'LIKE', "%{$search}%");
                            });
                    });
                }
            }, true)
            ->addColumn('name', function ($item) use ($request) {
                $html  = '<div class="d-flex align-items-center">';
                $html .= '<div class="ms-5">';
                $html .= '<a href="' . route($request->url . '.show', $item->id) . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . $item->invoice_number . '</a>';
                $html .= '<br><span class="text-muted d-block fs-7">' . ($item->branch->name ?? '-') . ' <i class="bi bi-arrow-right"></i> ' . ($item->branchDestination->name ?? '-') . '</span>';
                $html .= '<span class="badge badge-light-danger">' . ucwords(strtolower($item->createdBy->nm_user ?? $item->createdBy->name ?? 'unknown')) . '</span>';
                return $html;
            })
            ->addColumn('date', function ($item) {
                $date = date('d M Y', strtotime($item->date));
                $time = date('H:i', strtotime($item->created_at));
                $html = '<span class="text-muted d-block fs-8">' . $date . ' ' . $time . '</span>';

                $statusBadges = [
                    'temp'    => '<span class="badge badge-light-danger">Draft</span>',
                    'draft'   => '<span class="badge badge-light-danger">Draft</span>',
                    'pending' => '<span class="badge badge-light-secondary">Pending</span>',
                    'proses'  => '<span class="badge badge-light-warning">Proses</span>',
                    'selesai' => '<span class="badge badge-light-success">Selesai</span>',
                ];

                $html .= $statusBadges[$item->status] ?? '<span class="badge badge-light-dark">' . $item->status . '</span>';
                if ($item->picPenerima) {
                    $html .= '<br><span class="badge badge-light-success mt-1">' . ucwords(strtolower($item->picPenerima->nm_user ?? $item->picPenerima->name ?? 'unknown')) . '</span>';
                }
                return $html;
            })
            ->addColumn('correction', function ($item) {
                $diff = ($item->total_old ?? 0) - ($item->total_new ?? 0);
                if ($diff > 0) {
                    return '<span class="badge badge-light-danger">-' . $diff . '</span>';
                } elseif ($diff < 0) {
                    return '<span class="badge badge-light-success">+' . abs($diff) . '</span>';
                }
                return '-';
            })
            ->addColumn('action', function ($item) use ($request) {
                $html  = '';
                $html .= '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                $html .= '
                            <li>
                                <a class="dropdown-item" href="' . route($request->url . '.show', $item->id) . '">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>';
                $isRecent = false;
                $timeRef = isset($item->updated_at) ? $item->updated_at : (isset($item->created_at) ? $item->created_at : null);
                if ($timeRef && \Carbon\Carbon::parse($timeRef)->diffInMinutes(now()) <= 30) {
                    $isRecent = true;
                }

                if ($request->url == 'transfer-pengirim' && (in_array($item->status, ['temp', 'draft']) || $isRecent)) {
                    $html .= '
                            <li>
                                <a class="dropdown-item" href="' . route($request->url . '.edit', $item->id) . '">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </li>';
                }
                if (! in_array($item->status, ['diterima']) && $request->url == 'transfer-pengirim') {
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
            ->rawColumns(['name', 'action', 'date', 'correction'])
            ->make(true);
    }
}
