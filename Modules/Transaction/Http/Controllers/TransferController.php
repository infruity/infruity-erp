<?php
namespace Modules\Transaction\Http\Controllers;

use App\Support\InventorySnapshot;
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

    private function authorizeTransferPage(string $action): void
    {
        $side = request()->segment(1);
        abort_unless(in_array($side, ['transfer-pengirim', 'transfer-penerima'], true), 403);
        $this->authorizeAccess($side . '.' . $action);
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $this->authorizeTransferPage('index');

        $data['branches'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        return view('transaction::transfer.index2', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $this->authorizeAccess('transfer-pengirim.create');
        if (request()->segment(1) === 'transfer-penerima') {
            return redirect()->route('transfer-pengirim.create');
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
        $data['destinations'] = Branch::withoutGlobalScope('userBranch')->select('id', 'name')->orderBy('name')->get();

        return view('transaction::transfer.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $this->authorizeAccess('transfer-pengirim.store');

        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $this->authorizeTransferPage('show');

        $transfer = Transfer::with('branch', 'branchDestination', 'createdBy', 'picPenerima')
            ->whereIn(request()->segment(1) === 'transfer-penerima' ? 'branch_destination_id' : 'branch_id', UserBranch::getUserBranch())
            ->findOrFail($id);
        $type = request()->segment(1);

        $data['alpinejs']       = true;
        $data['data']           = $transfer;
        $data['detail']         = TransferDetail::with(['product', 'product.unit', 'corrections.user'])->where('transfer_id', $id)->get();
        $data['invoice_number'] = $transfer->invoice_number;
        $data['is_view']        = true;
        $data['type']           = $type;
        $data['branches'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        $data['destinations'] = Branch::withoutGlobalScope('userBranch')->select('id', 'name')->orderBy('name')->get();
        return view('transaction::transfer.create', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->authorizeAccess('transfer-pengirim.edit');

        $data['alpinejs']       = true;
        $data['data']           = Transfer::with('branch', 'branchDestination', 'createdBy')
            ->where('created_by', Auth::id())
            ->whereIn('branch_id', UserBranch::getUserBranch())
            ->whereIn('status', ['temp', 'draft', 'pending'])->findOrFail($id);
        $data['detail']         = TransferDetail::with(['product', 'product.unit', 'corrections.user'])->where('transfer_id', $id)->get();
        $data['invoice_number'] = $data['data']->invoice_number;
        $data['type']           = request()->segment(1);
        $data['branches'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        $data['destinations'] = Branch::withoutGlobalScope('userBranch')->select('id', 'name')->orderBy('name')->get();
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
        $this->authorizeAccess('transfer-pengirim.update');

        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $this->authorizeAccess('transfer-pengirim.destroy');

        try {
            DB::beginTransaction();
            $pos = Transfer::where('created_by', Auth::id())
                ->whereIn('branch_id', UserBranch::getUserBranch())
                ->whereIn('status', ['temp', 'draft'])->findOrFail($id);
            $pos->delete();
            TransferDetail::where('transfer_id', $id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
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
            'items'                 => 'required|array|min:1',
            'items.*.id'            => 'required|integer|distinct|exists:products,id',
            'items.*.qty'           => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'items.*.price'         => 'required|numeric|min:0',
            'items.*.discount'      => 'nullable|numeric|min:0',
            'items.*.total_input'   => 'required|numeric|min:0',
            'total'                 => 'required|numeric|min:0',
            'subtotal'              => 'required|numeric|min:0',
            'discount'              => 'nullable|numeric|min:0',
            'ongkir'                => 'nullable|numeric|min:0',
            'discount_ongkir'       => 'nullable|numeric|min:0',
            'status'                => 'nullable|in:temp,draft,pending',
        ]);

        abort_unless(in_array((int) $data['branch_id'], array_map('intval', UserBranch::getUserBranch()), true), 403);
        if ((int) $data['branch_id'] === (int) $data['branch_destination_id']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['branch_destination_id' => 'Cabang tujuan harus berbeda dari cabang asal.']);
        }
        $calculatedSubtotal = 0;
        foreach ($data['items'] as $item) {
            $itemSubtotal = round((float) $item['qty'] * (float) $item['price'] - (float) ($item['discount'] ?? 0), 2);
            if ($itemSubtotal < 0 || abs($itemSubtotal - (float) $item['total_input']) > 0.01) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Subtotal produk tidak sesuai kuantitas dan harga.']);
            }
            $calculatedSubtotal += $itemSubtotal;
        }
        if (abs($calculatedSubtotal - (float) $data['subtotal']) > 0.01) {
            throw \Illuminate\Validation\ValidationException::withMessages(['subtotal' => 'Subtotal transfer tidak sesuai dengan rincian.']);
        }
        $discount = (float) ($data['discount'] ?? 0);
        $shipping = (float) ($data['ongkir'] ?? 0);
        $shippingDiscount = (float) ($data['discount_ongkir'] ?? 0);
        $discountAmount = $discount > 0 && $discount <= 100 ? $calculatedSubtotal * $discount / 100 : $discount;
        $shippingDiscountAmount = $shippingDiscount > 0 && $shippingDiscount <= 100 ? $shipping * $shippingDiscount / 100 : $shippingDiscount;
        $calculatedTotal = max(0, $calculatedSubtotal - $discountAmount + $shipping - $shippingDiscountAmount);
        if ($discountAmount > $calculatedSubtotal || $shippingDiscountAmount > $shipping
            || abs($calculatedTotal - (float) $data['total']) > 0.01) {
            throw \Illuminate\Validation\ValidationException::withMessages(['total' => 'Total transfer tidak sesuai dengan diskon dan ongkir.']);
        }
        $data['total'] = $calculatedTotal;

        try {
            $userId        = Auth::id();
            $invoiceNumber = $data['invoice_number'] ?? null;
            $transferId    = $data['transfer_id'] ?? null;

            DB::beginTransaction();

            $pos = null;
            if (!empty($transferId)) {
                $pos = Transfer::where('id', $transferId)
                    ->where('created_by', $userId)
                    ->where(function ($query) {
                        $query->whereIn('branch_id', UserBranch::getUserBranch())
                            ->orWhere(function ($draft) {
                                $draft->where('status', 'temp')->whereNull('branch_id');
                            });
                    })
                    ->lockForUpdate()->firstOrFail();
            }

            if (!$pos && !empty($invoiceNumber)) {
                $pos = Transfer::where('invoice_number', $invoiceNumber)
                    ->where('created_by', $userId)
                    ->where(function ($query) {
                        $query->whereIn('branch_id', UserBranch::getUserBranch())
                            ->orWhere(function ($draft) {
                                $draft->where('status', 'temp')->whereNull('branch_id');
                            });
                    })
                    ->lockForUpdate()
                    ->first();
            }

            if (($data['status'] ?? 'draft') === 'pending') {
                $sortedItems = collect($data['items'])->sortBy('id');
                foreach ($sortedItems as $item) {
                    \Modules\Master\Entities\Product::whereKey($item['id'])->lockForUpdate()->firstOrFail();
                }
                // One stock query for all items (the view rebuilds the whole ledger per call).
                $stock = InventorySnapshot::stockAvailable($sortedItems->pluck('id'), $data['branch_id']);
                // A pending transfer already took its items out of the origin stock;
                // when it is edited, give its own quantities back before checking.
                if ($pos && in_array($pos->status, ['pending', 'proses'], true)
                    && (int) $pos->branch_id === (int) $data['branch_id']) {
                    $ownQty = TransferDetail::where('transfer_id', $pos->id)
                        ->whereNotNull('sent_quantity')
                        ->groupBy('product_id')
                        ->selectRaw('product_id, SUM(sent_quantity) AS qty')
                        ->pluck('qty', 'product_id');
                    foreach ($ownQty as $productId => $qty) {
                        if (isset($stock[(int) $productId])) {
                            $stock[(int) $productId] += (float) $qty;
                        }
                    }
                }
                foreach ($sortedItems as $item) {
                    $available = $stock[(int) $item['id']] ?? 0.0;
                    if ($available < (float) $item['qty']) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Kuantitas pengiriman melebihi stok cabang asal.']);
                    }
                }
            }
            if ($pos) {
                // Update transaksi transfer yang sudah ada
                if (! in_array($pos->status, ['temp', 'draft', 'pending'], true) || $pos->corrections()->exists()) {
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
                        // Leaves the origin stock from now on (see transaction_stock.sql).
                        'sent_quantity' => ($data['status'] ?? 'draft') === 'pending' ? $item['qty'] : null,
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
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
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
            'quantity'  => 'required|numeric|gt:0',
            'note'      => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $transferId = TransferDetail::whereKey($request->detail_id)->value('transfer_id');
            $transfer = Transfer::lockForUpdate()->findOrFail($transferId);
            $detail = TransferDetail::with('corrections')->lockForUpdate()->findOrFail($request->detail_id);

            abort_unless(in_array((int) $transfer->branch_destination_id, array_map('intval', UserBranch::getUserBranch()), true), 403);

            if (! in_array($transfer->status, ['pending', 'proses'], true)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengoreksi transaksi yang sudah selesai.',
                ], 422);
            }

            if ($detail->sent_quantity !== null && (float) $request->quantity > (float) $detail->sent_quantity) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah diterima tidak boleh melebihi jumlah dikirim.',
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
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengoreksi quantity: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function set_selesai(Request $request, $id)
    {
        $this->authorizeAccess('transfer-penerima.set_selesai');
        $input = $request->validate([
            'items' => 'sometimes|required|array|min:1',
            'items.*.detail_id' => 'required|integer|distinct',
            'items.*.quantity' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'items.*.note' => 'nullable|string|max:1000',
            'items.*.expected_quantity' => 'required|numeric|min:0',
        ]);
        return DB::transaction(function () use ($id, $input) {
            $transfer = Transfer::whereIn('branch_destination_id', UserBranch::getUserBranch())
                ->lockForUpdate()->findOrFail($id);
            if (!in_array($transfer->status, ['pending', 'proses'], true)) {
                return response()->json(['success' => false, 'message' => 'Transfer ini sudah diterima atau belum dikirim.'], 422);
            }
            $details = $transfer->detail()->with('corrections')->lockForUpdate()->get();
            if ($details->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Transfer belum memiliki rincian barang.'], 422);
            }
            if (isset($input['items'])) {
                $items = collect($input['items'])->keyBy('detail_id');
                if ($items->count() !== $details->count() || $details->pluck('id')->diff($items->keys())->isNotEmpty()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Rincian penerimaan harus sesuai seluruh barang yang dikirim.']);
                }
                foreach ($details as $detail) {
                    $item = $items->get($detail->id);
                    if (abs((float) $item['expected_quantity'] - (float) $detail->quantity) > 0.001) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Jumlah barang telah diperbarui. Muat ulang halaman sebelum menerima.']);
                    }
                    $sent = (float) ($detail->sent_quantity ?? $detail->corrections->last()?->old_quantity ?? $detail->quantity);
                    if ((float) $item['quantity'] > $sent) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Jumlah diterima tidak boleh melebihi jumlah dikirim.']);
                    }
                    if (abs((float) $item['quantity'] - (float) $detail->quantity) > 0.001 || trim($item['note'] ?? '') !== ($detail->corrections->first()?->note ?? '')) {
                        TransferDetailCorrection::create([
                            'transfer_detail_id' => $detail->id, 'old_quantity' => $detail->quantity,
                            'new_quantity' => $item['quantity'], 'note' => $item['note'] ?? null, 'created_by' => Auth::id(),
                        ]);
                        $detail->quantity = $item['quantity'];
                        $detail->subtotal = round($detail->price * $item['quantity'], 2);
                        $detail->save();
                    }
                }
            }
            // Items with sent_quantity already left the origin stock when the transfer
            // was sent; a shortfall on receipt is shrinkage. Older items (no
            // sent_quantity) still leave the origin now, so check their stock.
            $groups = $details->whereNull('sent_quantity')->groupBy('product_id')->sortKeys();
            foreach ($groups as $productId => $group) {
                \Modules\Master\Entities\Product::whereKey($productId)->lockForUpdate()->firstOrFail();
            }
            // One stock query for all products (the view rebuilds the whole ledger per call).
            $stock = InventorySnapshot::stockAvailable($groups->keys(), $transfer->branch_id);
            foreach ($groups as $productId => $group) {
                $available = $stock[(int) $productId] ?? 0.0;
                if ($available < (float) $group->sum('quantity')) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Stok cabang asal tidak mencukupi. Hubungi pengirim untuk memeriksa stok.']);
                }
            }
            $transfer->update([
                'status' => 'selesai', 'total' => $details->sum('subtotal'),
                'pic_penerima' => Auth::id(), 'tanggal_diterima' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'Barang berhasil diterima.']);
        });
    }

    public function products(Request $request)
    {
        abort_unless($this->hasAccess('transfer-pengirim.create') || $this->hasAccess('transfer-pengirim.edit'), 403);
        $request->validate(['branch_id' => 'required|integer', 'term' => 'nullable|string|max:150', 'transfer_id' => 'nullable|integer']);
        abort_unless(in_array((int) $request->branch_id, array_map('intval', UserBranch::getUserBranch()), true), 403);

        // Editing a pending transfer: its items are already out of the origin stock,
        // so show them as available again for this form.
        $ownQty = collect();
        if ($request->filled('transfer_id')) {
            $own = Transfer::whereKey($request->transfer_id)->where('created_by', Auth::id())
                ->where('branch_id', $request->branch_id)->whereIn('status', ['pending', 'proses'])->exists();
            if ($own) {
                $ownQty = TransferDetail::where('transfer_id', $request->transfer_id)->whereNotNull('sent_quantity')
                    ->groupBy('product_id')->selectRaw('product_id, SUM(sent_quantity) AS qty')->pluck('qty', 'product_id');
            }
        }

        $products = \Modules\Master\Entities\Product::with('unit')->select('products.*', 'product_stock.stock_available', 'product_stock.avg_hpp')
            ->join('product_stock', 'products.id', '=', 'product_stock.id')
            ->where('product_stock.branch_id', $request->branch_id)
            ->where(fn ($q) => $q->where('product_stock.stock_available', '>', 0)->orWhereIn('products.id', $ownQty->keys()->all()))
            ->where('products.tipe', '!=', 'parcel')->whereNull('products.is_variant')
            ->where('products.name', 'like', '%' . $request->input('term', '') . '%')->orderBy('products.name')->limit(200)->get();
        return response()->json($products->map(fn ($product) => [
            'id' => $product->id, 'name' => $product->name, 'unit' => $product->unit?->abbreviation ?? '',
            'price' => round((float) ($product->avg_hpp ?? $product->hpp ?? 0), 2),
            'stock' => (float) $product->stock_available + (float) ($ownQty[$product->id] ?? 0),
        ]));
    }

    private function mobileData(Request $request, $query)
    {
        $request->validate([
            'start' => 'nullable|integer|min:0', 'length' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:150', 'start_date' => 'nullable|date', 'end_date' => 'nullable|date',
        ]);
        if ($request->filled('start_date') && $request->filled('end_date') && $request->end_date < $request->start_date) {
            throw \Illuminate\Validation\ValidationException::withMessages(['end_date' => 'Tanggal akhir harus sama atau setelah tanggal mulai.']);
        }
        $side = $request->input('url');
        $total = (clone $query)->count();
        if ($request->filled('status_filter') && $request->status_filter !== 'all') $query->where('status', $request->status_filter);
        if ($request->filled('cabang_filter') && $request->cabang_filter !== 'all') $query->where($side === 'transfer-penerima' ? 'branch_destination_id' : 'branch_id', $request->cabang_filter);
        if ($request->filled('start_date')) $query->whereDate('date', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('date', '<=', $request->end_date);
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('branchDestination', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('detail.product', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }
        $filtered = (clone $query)->count();
        $permissions = ['show' => $this->hasAccess($side . '.show'), 'receive' => $this->hasAccess('transfer-penerima.set_selesai'), 'edit' => $this->hasAccess('transfer-pengirim.edit'), 'delete' => $this->hasAccess('transfer-pengirim.destroy')];
        $rows = $query->with(['detail.product.unit', 'detail.corrections.user'])->orderByDesc('date')->orderByDesc('id')
            ->skip((int) $request->input('start', 0))->take((int) $request->input('length', 20))->get()->map(function ($item) use ($side, $permissions) {
                $owner = (int) $item->created_by === (int) Auth::id();
                $editable = $side === 'transfer-pengirim' && $owner && in_array($item->status, ['temp', 'draft', 'pending']) && !$item->corrections_count;
                return [
                    'id' => $item->id, 'invoice_number' => $item->invoice_number, 'date' => $item->date, 'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                    'origin' => $item->branch?->name ?? 'Tanpa cabang', 'destination' => $item->branchDestination?->name ?? 'Tanpa tujuan',
                    'staff_name' => $item->createdBy?->nm_user ?? '', 'receiver_name' => $item->picPenerima?->nm_user ?? '',
                    'received_at' => $item->tanggal_diterima, 'status' => $item->status,
                    'items' => $item->detail->map(fn ($detail) => [
                        'id' => $detail->id, 'name' => $detail->product?->name ?? 'Produk dihapus', 'unit' => $detail->product?->unit?->abbreviation ?? '',
                        'quantity' => (float) $detail->quantity, 'sent' => (float) ($detail->sent_quantity ?? $detail->corrections->last()?->old_quantity ?? $detail->quantity),
                        'note' => $detail->corrections->first()?->note ?? '',
                    ]),
                    'can_show' => $permissions['show'],
                    'can_receive' => $side === 'transfer-penerima' && in_array($item->status, ['pending', 'proses']) && $permissions['receive'] && $permissions['show'],
                    'can_edit' => $editable && $permissions['edit'],
                    'can_delete' => $side === 'transfer-pengirim' && $owner && in_array($item->status, ['temp', 'draft']) && $permissions['delete'],
                    'show_url' => route($side . '.show', $item->id), 'edit_url' => route('transfer-pengirim.edit', $item->id),
                    'delete_url' => route('transfer-pengirim.destroy', $item->id),
                ];
            });
        return response()->json(['data' => $rows, 'recordsTotal' => $total, 'recordsFiltered' => $filtered]);
    }

    public function get_data(Request $request)
    {
        $side = (string) $request->input('url');
        abort_unless(in_array($side, ['transfer-pengirim', 'transfer-penerima'], true), 403);
        $this->authorizeAccess($side . '.index');
        $userBranches = UserBranch::getUserBranch();
        $query        = Transfer::with(['createdBy', 'picPenerima', 'branch', 'branchDestination'])
            ->withCount('corrections')->withSum('corrections as total_old', 'old_quantity')
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

        if ($side === 'transfer-penerima') $query->whereIn('status', ['pending', 'proses', 'selesai']);
        if ($request->boolean('mobile')) return $this->mobileData($request, $query);

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
