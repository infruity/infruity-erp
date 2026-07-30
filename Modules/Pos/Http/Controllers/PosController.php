<?php
namespace Modules\Pos\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Crm\Entities\CustomerDeposito;
use Modules\Crm\Entities\CustomerTier;
use Modules\Crm\Entities\Deposito;
use Modules\Crm\Entities\SettingExp;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Product;
use Modules\Master\Entities\UserBranch;
use Modules\Pos\Entities\OrderBook;
use Modules\Pos\Entities\Payment;
use Modules\Pos\Entities\PosDetailModel;
use Modules\Pos\Entities\PosModel;
use Modules\Pos\Entities\SettingNota;
use Modules\Transaction\Entities\ProductHppRunning;
use Modules\Transaction\Entities\Production;
use Modules\Transaction\Entities\ProductionDetail;
use Modules\Transaction\Entities\ProductionParcelDetail;
use Modules\Transaction\Entities\ProductStock;
use Modules\Transaction\Entities\ProductReceipt;
use Yajra\DataTables\Facades\DataTables;

class PosController extends Controller
{
    use \App\Traits\HasAccessControl;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('pos.index')) {
            return $denied;
        }

        $data['alpinejs'] = true;
        $userBranches     = UserBranch::getUserBranch();
        $data['branches'] = Branch::whereIn('id', $userBranches)->get();

        // Cek product_stock yang kosong pada branch yang dimiliki user
        $emptyStockProducts = DB::table('product_stock')
            ->join('branch', 'product_stock.branch_id', '=', 'branch.id')
            ->select('product_stock.*', 'branch.name as branch_name')
            ->whereIn('product_stock.branch_id', $userBranches)
            ->where('stock_available', '<', 0)
            ->whereNull('is_variant')
            ->get();

        $data['emptyStockData'] = $emptyStockProducts;

        return view('pos::pos.index2', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('pos.create')) {
            return $denied;
        }

        $data['alpinejs'] = true;

        // Cek apakah ada temp transaksi untuk user ini
        $draft = PosModel::with([
            'customer',
            'courier',
            'branch',
            'branch_proses',
        ])
            ->where('created_by', Auth::id())
            ->where('status', 'temp')
            ->orderBy('id', 'desc')
            ->first();

        if ($draft) {
            $draft->load([
                'details',
                'details.parcel',
                'details.product',
                'details.product.unit',
                'details.product.productionParcelDetails' => function($q) use ($draft) {
                    $q->where('pos_id', $draft->id);
                },
                'details.product.productionParcelDetails.product',
                'details.product.productionParcelDetails.product.productBranches',
                'details.product.productionParcelDetails.product.get_stock',
            ]);
            $data['data']           = $draft;
            $data['detail']         = $draft->details;
            $data['invoice_number'] = $draft->invoice_number;
        } else {
            $invoiceNumber = PosModel::getOrderNumber();

            $draft = new PosModel();
            $draft->uuid = Str::uuid();
            $draft->invoice_number = $invoiceNumber;
            $draft->created_by = Auth::id();
            $draft->status = 'temp';
            $draft->date = date('Y-m-d');
            $draft->total = 0;
            $draft->save();

            $data['data']           = $draft;
            $data['detail']         = [];
            $data['invoice_number'] = $invoiceNumber;
        }

        return view('pos::pos.create2', $data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('pos.store')) {
            return $denied;
        }

        // dd($request->all());
        $request->validate([
            'customer_id'         => 'nullable|exists:customer,id',
            'items'               => 'required|array',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.qty'         => 'required|numeric|min:1',
            'items.*.price'       => 'required|numeric|min:0',
            'items.*.discount'    => 'nullable|numeric|min:0',
            'items.*.total_input' => 'required|numeric|min:0',
        ]);

        $sum_discount    = array_sum(array_column($request->items, 'discount'));
        $sum_total_input = array_sum(array_column($request->items, 'total_input'));

        DB::beginTransaction();
        try {

            $userId = Auth::id(); // Ambil user sekali
            $pos    = new PosModel([
                'customer_id' => $request->customer_id,
                'date'        => date('Y-m-d'),
                'total'       => $sum_total_input - $sum_discount,
                'created_by'  => $userId,
            ]);
            $pos->save();

            $posDetail = [];
            foreach ($request->items as $item) {
                $posDetail[] = [
                    'pos_id'     => $pos->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['qty'],
                    'price'      => $item['price'],
                    'discount'   => $item['discount'] ?? 0,
                    'subtotal'   => $item['total_input'],
                ];
            }
            PosDetailModel::insert($posDetail);

            DB::commit();

            return response()->json([
                'success' => true,
                'id'      => $pos->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if ($denied = $this->requireAccess('pos.show')) {
            return $denied;
        }

        $data['data']         = PosModel::with('customer')->findOrFail($id);
        $data['detail']       = PosDetailModel::with('product')->where('pos_id', $id)->get();
        $data['parcelDetail'] = ProductionParcelDetail::with('product')->where('pos_id', $id)->get();
        $data['setting']      = SettingNota::first();
        // dd($data);
        return view('pos::pos.receipt', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('pos.edit')) {
            return $denied;
        }

        $data['alpinejs']       = true;
        $data['data']           = PosModel::with('customer', 'customer.customerTier', 'courier', 'branch', 'branch_proses', 'user', 'productions', 'productions.productionDetails', 'productions.productionDetails.products', 'productions.productionDetails.products.get_stock')->findOrFail($id);

        $isRecent = false;
        if ($data['data']->updated_at && $data['data']->updated_at->diffInMinutes(now()) <= 30) {
            $isRecent = true;
        }

        if ($data['data']->status === 'paid') {
            return redirect()->route('pos.index')->with('error', 'Transaksi yang sudah dibayar (paid) tidak dapat diedit.');
        }

        if ($data['data']->status === 'debt' && !$isRecent) {
            return redirect()->route('pos.index')->with('error', 'Tidak bisa diedit, buatlah transaksi baru.');
        }

        $data['detail'] = PosDetailModel::with([
            'product',
            'parcel',
            'product.unit',
            'product.productionParcelDetails' => function($q) use ($id) {
                $q->where('pos_id', $id);
            },
            'product.productionParcelDetails.product',
            'product.productionParcelDetails.product.get_stock',
            'product.productionParcelDetails.product.productBranches',
            'product.productReceipt',
            'product.productReceipt.ingredients',
            'product.productReceipt.ingredients.get_stock'
        ])->where('pos_id', $id)->get();
        $data['invoice_number'] = $data['data']->invoice_number;
        return view('pos::pos.create2', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('pos.update')) {
            return $denied;
        }

        return response()->json(['success' => false, 'message' => 'Method not implemented.'], 501);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('pos.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $pos = PosModel::findOrFail($id);

            // Hapus production parcel details
            ProductionParcelDetail::where('pos_id', $id)->delete();

            // Hapus production details untuk productions yang terkait
            $productionIds = Production::where('pos_id', $id)->pluck('id');
            ProductionDetail::whereIn('production_id', $productionIds)->delete();

            // Hapus productions
            Production::where('pos_id', $id)->delete();

            // Hapus POS details, payments, dan transaksi utama
            PosDetailModel::where('pos_id', $id)->delete();
            Payment::where('pos_id', $id)->delete();
            $pos->delete();

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

    public function savePayment(Request $request)
    {
        if ($denied = $this->requireAccess('pos.savePayment')) {
            return $denied;
        }

        // dd($request->all());
        $data = $request->validate([
            'date'           => 'required|date',
            'transaction_id' => 'required|exists:pos_transaction,id',
            // 'branch_id' => 'required|exists:branch,id',
            // 'account_id' => 'required|exists:account,id',
            // 'payment_id' => 'required|exists:payment_method,id',
            'payments'       => 'required|array',
            'total_payment'  => 'required|numeric|min:0',
            'customer_id'    => 'nullable',
        ]);

        try {
            DB::beginTransaction();

            // Simpan ke tabel pembayaran
            $paymentNames   = collect($data['payments'])->pluck('payment_name')->toArray();
            $paymentIds     = collect($data['payments'])->pluck('payment_id')->toArray();
            $paymentAmounts = collect($data['payments'])->map(function ($item) {
                return (float) preg_replace('/[^\d]/', '', $item['amount']);
            })->toArray();

            $payment = new Payment([
                'uuid'              => Str::uuid(),
                'date'              => $data['date'],
                'nota_number'       => date('YmdHis') . Str::random(4),
                'pos_id'            => $data['transaction_id'],
                // 'branch_id' => $data['branch_id'],
                // 'account_id' => $data['account_id'],
                'payment_method'    => json_encode($paymentNames),
                'payment_method_id' => json_encode($paymentIds),
                'payment_amount'    => json_encode($paymentAmounts),
                'total'             => array_sum($paymentAmounts),
                'created_by'        => Auth::id(),
            ]);
            // dd($payment);
            $payment->save();

            $posForVoucher = PosModel::findOrFail($data['transaction_id']);

            // Pengecekan stok seperti pada saveTransaction (hanya untuk transaksi yang belum terpotong stoknya)
            if (in_array($posForVoucher->status, ['draft', 'temp', 'pending'])) {
                $posDetails = PosDetailModel::where('pos_id', $data['transaction_id'])->get();
                $parcelDetails = ProductionParcelDetail::where('pos_id', $data['transaction_id'])->get();
                $productions = Production::with('productionDetails')->where('pos_id', $data['transaction_id'])->get();

                $allProductIdsToLock = [];
                $requiredStocks = [];

                $addRequiredStock = function($productId, $qty) use (&$requiredStocks, &$allProductIdsToLock) {
                    if (!$productId) return;
                    
                    if (!in_array($productId, $allProductIdsToLock)) {
                        $allProductIdsToLock[] = $productId;
                    }

                    $product = Product::find($productId);
                    $stockProductId = $productId;
                    if ($product) {
                        $parentId = $product->getParentId();
                        $stockProductId = $parentId ?? $product->id;
                        if ($parentId && !in_array($parentId, $allProductIdsToLock)) {
                            $allProductIdsToLock[] = $parentId;
                        }
                    }
                    
                    if (!isset($requiredStocks[$stockProductId])) {
                        $requiredStocks[$stockProductId] = 0;
                    }
                    $requiredStocks[$stockProductId] += (float)$qty;
                };

                foreach ($posDetails as $detail) {
                    if ($detail->type === 'product') {
                        $addRequiredStock($detail->product_id, $detail->quantity);
                    } elseif ($detail->type === 'parcel') {
                        if ($detail->parcel_id) {
                            $addRequiredStock($detail->parcel_id, $detail->quantity);
                        }
                    } elseif ($detail->type === 'jus') {
                        $production = $productions->where('product_id', $detail->product_id)->first();
                        $productionQty = $production ? $production->quantity : 0;
                        $consumeStockQty = $detail->quantity - $productionQty;
                        if ($consumeStockQty > 0) {
                            $addRequiredStock($detail->product_id, $consumeStockQty);
                        }
                    }
                }

                foreach ($parcelDetails as $pDetail) {
                    $addRequiredStock($pDetail->product_id, $pDetail->quantity);
                }

                foreach ($productions as $production) {
                    foreach ($production->productionDetails as $pDetail) {
                        $addRequiredStock($pDetail->product_id, $pDetail->quantity);
                    }
                }

                $allProductIdsToLock = array_unique($allProductIdsToLock);
                sort($allProductIdsToLock);

                // Kunci master produk secara berurutan agar antrean checkout terjaga dan bebas Deadlock
                foreach ($allProductIdsToLock as $lockId) {
                    Product::where('id', $lockId)->lockForUpdate()->first();
                }

                // Validasi stok akumulatif dari semua sumber
                foreach ($requiredStocks as $stockProductId => $requiredQty) {
                    $productStock = ProductStock::where('id', $stockProductId)
                        ->where('branch_id', $posForVoucher->branch_id)
                        ->first();
                    $currentStock = $productStock ? (float)$productStock->stock_available : 0;

                    if ($currentStock < $requiredQty) {
                        $product     = Product::find($stockProductId);
                        $productName = $product ? $product->name : 'Produk #' . $stockProductId;
                        throw new \Exception("Stok \"{$productName}\" tidak mencukupi. Stok tersedia: {$currentStock}, dibutuhkan: {$requiredQty}");
                    }
                }
            }

            if (empty($posForVoucher->deposito_id) && empty($posForVoucher->voucher)) {
                $deposito = Deposito::where('customer_id', $data['customer_id'])->first();
                $voucher  = $deposito?->voucher ?? 0;
                
                if ($deposito && $voucher > 0 && $deposito->voucher_qty > 0) {
                    PosModel::where("id", $data['transaction_id'])->update([
                        'voucher'     => $voucher,
                        'voucher_qty' => 1,
                        'deposito_id' => $deposito->id,
                    ]);
                    $deposito->decrement('voucher_qty');
                }
            }

            $totalPayment = Payment::where('pos_id', $data['transaction_id'])
                ->sum('total');

            $pos       = PosModel::findOrFail($data['transaction_id']);
            $total     = $pos->total - $pos->voucher;
            $pos->paid = $totalPayment;
            if ($totalPayment > $total) {
                $lastPayment         = Payment::findOrFail($payment->id);
                $lastPayment->return = ($totalPayment - $total);
                $lastPayment->save();
            } else {
                $lastPayment            = Payment::findOrFail($payment->id);
                $lastPayment->remaining = ($total - $totalPayment);
                $lastPayment->save();
            }
            $status = 'debt';
            if ($totalPayment >= $total) {
                $status = 'paid';
            }
            // $pos->ongkir_status = 'delivered';
            $pos->status = $status;
            $pos->save();

            if (in_array($status, ['paid', 'debt'])) {
                Production::where('pos_id', $pos->id)
                    ->where('status', 'draft')
                    ->update(['status' => 'complete']);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'payment' => $payment,
                'pos'     => $pos,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function listPayment($id)
    {
        if ($denied = $this->requireAccess('pos.listPayment')) {
            return $denied;
        }

        $payments = Payment::with('paymentMethod')->where('pos_id', $id)
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($payments);
    }

    public function showReceipt($id)
    {
        if ($denied = $this->requireAccess('pos.receipt')) {
            return $denied;
        }

        $data['data']    = PosModel::with('customer')->findOrFail($id);
        $data['detail']  = PosDetailModel::with('product')->where('pos_id', $id)->get();
        $data['setting'] = SettingNota::first();
        // dd($data);
        return view('pos::pos.receipt2', $data);
    }

    public function saveTransaction(Request $request)
    {
        if ($denied = $this->requireAccess('pos.receipt')) {
            return $denied;
        }

        // dd($request->all());
        $data = $request->validate([
            // 'customer_id' => 'nullable|exists:customer,id',
            'customer_id'       => 'nullable',
            'date'              => 'required|date',
            'invoice_number'    => 'nullable',
            'items'             => 'required|array',
            'items.*.id'        => 'nullable',
            'items.*.price'     => 'nullable|numeric|min:0',
            'items.*.qty'       => 'nullable|numeric|min:0.01',
            'items.*.discount'  => 'nullable|numeric|min:0',
            'items.*.total_input' => 'nullable|numeric|min:0',
            'parcel'            => 'nullable|array',
            'jus'               => 'nullable|array',
            'jus.*.productId'   => 'nullable',
            'jus.*.qty'         => 'nullable|numeric|min:0',
            'jus.*.price'       => 'nullable|numeric|min:0',
            'jus.*.hpp'         => 'nullable|numeric|min:0',
            'jus.*.discount'    => 'nullable|numeric|min:0',
            'jus.*.total_input' => 'nullable|numeric|min:0',
            'jus.*.product_receipt_id' => 'nullable|array',
            'jus.*.product_receipt_qty' => 'nullable|array',
            'subtotal'          => 'required|numeric|min:0',
            'discount'          => 'nullable|numeric|min:0',
            'ongkir'            => 'required|numeric|min:0',
            'discount_ongkir'   => 'required|numeric|min:0',
            'total'             => 'required|numeric|min:0',
            'status'            => 'nullable|in:draft,paid,debt,temp,pending',
            'process_status'    => 'nullable|in:none,pending,done',
            'ongkir_date'       => 'nullable|date',
            'ongkir_time'       => 'nullable',
            'note'              => 'nullable',
            'courier_id'        => 'nullable',
            'courier_type'      => 'nullable',
            'ongkir_address'    => 'nullable',
            'kemasan_price'     => 'nullable|numeric|min:0',
            'branch_id'         => 'nullable',
            'branch_process_id' => 'nullable',
        ]);

        try {
            $userId     = Auth::id();
            $isTempSave = ($data['status'] ?? null) === 'temp';

            
            // Kumpulkan semua ID Produk untuk mengurutkan lockForUpdate (Mencegah Deadlock)
            $allProductIdsToLock = [];
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (isset($item['id']) && is_numeric($item['id'])) {
                        $allProductIdsToLock[] = $item['id'];
                    }
                }
            }
            if (!empty($data['parcel'])) {
                foreach ($data['parcel'] as $parcel) {
                    if (!empty($parcel['data'])) {
                        foreach ($parcel['data'] as $item) {
                            $allProductIdsToLock[] = $item['product'];
                        }
                    }
                    $kemasanId = $parcel['kemasanId'] ?? null;
                    if (empty($kemasanId) && !empty($parcel['kemasan'])) {
                        $kemasanProduct = Product::where('name', $parcel['kemasan'])->first();
                        $kemasanId = $kemasanProduct?->id;
                    }
                    if (!empty($kemasanId)) {
                        $allProductIdsToLock[] = $kemasanId;
                    }
                }
            }
            if (!empty($data['jus'])) {
                foreach ($data['jus'] as $jus) {
                    if (!empty($jus['productId'])) {
                        $allProductIdsToLock[] = $jus['productId'];
                    }
                    if (!empty($jus['product_receipt_id'])) {
                        foreach ($jus['product_receipt_id'] as $receiptProductId) {
                            $allProductIdsToLock[] = $receiptProductId;
                        }
                    }
                }
            }
            
            $allProductIdsToLock = array_unique($allProductIdsToLock);

            // Tambahkan parent_id ke lock untuk mencegah deadlock jika validasi stok mengenai parent product
            $parentIdsToLock = [];
            foreach ($allProductIdsToLock as $lockId) {
                $product = Product::find($lockId);
                if ($product) {
                    $parentId = $product->getParentId();
                    if ($parentId) {
                        $parentIdsToLock[] = $parentId;
                    }
                }
            }
            $allProductIdsToLock = array_unique(array_merge($allProductIdsToLock, $parentIdsToLock));
            sort($allProductIdsToLock);

            DB::beginTransaction();

            // Kunci master produk secara berurutan agar antrean checkout terjaga dan bebas Deadlock
            foreach ($allProductIdsToLock as $lockId) {
                Product::where('id', $lockId)->lockForUpdate()->first();
            }

            $requiredStocks = [];

            $addRequiredStock = function($productId, $qty) use (&$requiredStocks) {
                if (!$productId) return;
                $product = Product::find($productId);
                $stockProductId = $productId;
                if ($product) {
                    $parentId = $product->getParentId();
                    $stockProductId = $parentId ?? $product->id;
                }
                if (!isset($requiredStocks[$stockProductId])) {
                    $requiredStocks[$stockProductId] = 0;
                }
                $requiredStocks[$stockProductId] += (float)$qty;
            };

            // Kumpulkan kebutuhan stok buah
            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (isset($item['id']) && is_numeric($item['id'])) {
                        $addRequiredStock($item['id'], $item['qty'] ?? 0);
                    }
                }
            }

            // Kumpulkan kebutuhan stok untuk parcel
            if (isset($data['parcel'])) {
                foreach ($data['parcel'] as $parcel) {
                    foreach ($parcel['data'] as $item) {
                        $requiredQty = ((float)($item['qty'] ?? 0)) * ((float)($parcel['qty'] ?? 0));
                        $addRequiredStock($item['product'], $requiredQty);
                    }

                    // Kumpulkan kebutuhan stok kemasan
                    $kemasanId = $parcel['kemasanId'] ?? null;
                    if (empty($kemasanId) && !empty($parcel['kemasan'])) {
                        $kemasanProduct = Product::where('name', $parcel['kemasan'])->first();
                        $kemasanId = $kemasanProduct?->id;
                    }
                    if (!empty($kemasanId)) {
                        $addRequiredStock($kemasanId, $parcel['qty'] ?? 0);
                    }
                }
            }

            // Kumpulkan kebutuhan stok bahan baku jus
            $remainingStockJusValidation = [];
            if (isset($data['jus'])) {
                foreach ($data['jus'] as $jus) {
                    $productId = $jus['productId'] ?? null;
                    if (!$productId) continue;

                    if (!isset($remainingStockJusValidation[$productId])) {
                        $productStock = ProductStock::where('id', $productId)->where('branch_id', $data['branch_id'])->first();
                        $remainingStockJusValidation[$productId] = $productStock ? (float)$productStock->stock_available : 0;
                    }

                    $currentStock = $remainingStockJusValidation[$productId];
                    $jusQty = (float)($jus['qty'] ?? 0);

                    if ($currentStock <= 0) {
                        $productionQty = $jusQty;
                        $consumeStockQty = 0;
                    } elseif ($currentStock < $jusQty) {
                        $productionQty = $jusQty - $currentStock;
                        $consumeStockQty = $currentStock;
                    } else {
                        $productionQty = 0;
                        $consumeStockQty = $jusQty;
                    }

                    $remainingStockJusValidation[$productId] -= $consumeStockQty;

                    if ($consumeStockQty > 0) {
                        $addRequiredStock($productId, $consumeStockQty);
                    }

                    if ($productionQty > 0 && isset($jus['product_receipt_id']) && is_array($jus['product_receipt_id'])) {
                        foreach ($jus['product_receipt_id'] as $key => $receiptProductId) {
                            $receiptQty  = (float)($jus['product_receipt_qty'][$key] ?? 0);
                            $requiredQty = $receiptQty * $productionQty;
                            $addRequiredStock($receiptProductId, $requiredQty);
                        }
                    }
                }
            }

            // Validasi stok akumulatif dari semua sumber
            foreach ($requiredStocks as $stockProductId => $requiredQty) {
                $productStock = ProductStock::where('id', $stockProductId)
                    ->where('branch_id', $data['branch_id'])
                    ->first();
                $currentStock = $productStock ? (float)$productStock->stock_available : 0;

                if ($currentStock < $requiredQty) {
                    $product     = Product::find($stockProductId);
                    $productName = $product ? $product->name : 'Produk #' . $stockProductId;
                    throw new \Exception("Stok \"{$productName}\" tidak mencukupi. Stok tersedia: {$currentStock}, dibutuhkan: {$requiredQty}");
                }
            }
            
            $invoiceNumber = $data['invoice_number'] ?? null;
            $pos           = null;
            $posId         = null;

            if (! empty($invoiceNumber)) {
                $pos = PosModel::where('invoice_number', $invoiceNumber)
                    ->lockForUpdate()
                    ->first();
            }

            if ($pos) {
                $isRecent = false;
                if ($pos->updated_at && $pos->updated_at->diffInMinutes(now()) <= 30) {
                    $isRecent = true;
                }

                if ($pos->status === 'paid') {
                    throw new \Exception('Transaksi yang sudah dibayar tidak dapat diubah.');
                }
                
                if ($pos->status === 'debt' && !$isRecent) {
                    throw new \Exception('Transaksi piutang yang melebihi 30 menit tidak dapat diubah.');
                }

                $this->clearExistingPosRelations($pos->id);
                $posId          = $pos->id ?? null;
                $originalStatus = $pos->status;

            } else {
                $pos            = new PosModel();
                $pos->uuid      = Str::uuid();
                $pos->created_by = $userId;
                $originalStatus = null;
            }

            $statusToSave = $data['status'] ?? 'draft';
            if ($pos->exists && $originalStatus && $originalStatus !== 'temp' && $statusToSave === 'temp') {
                $statusToSave = $originalStatus;
            }

            $pos->fill([
                'customer_id'       => $data['customer_id'],
                'date'              => $data['date'],
                'invoice_number'    => $invoiceNumber,
                'subtotal'          => $data['subtotal'],
                'total'             => $data['total'],
                'discount'          => $data['discount'],
                'ongkir'            => $data['ongkir'],
                'ongkir_discount'   => $data['discount_ongkir'] ?? 0,
                'ongkir_date'       => $data['ongkir_date'] ?? null,
                'ongkir_time'       => $data['ongkir_time'] ?? null,
                'status'            => $statusToSave,
                'process_status'    => $data['process_status'] ?? 'none',
                'process_date'      => date('Y-m-d H:i:s'),
                'note'              => $data['note'] ?? null,
                'courier_id'        => $data['courier_id'] ?? null,
                'courier_type'      => $data['courier_type'] ?? null,
                'ongkir_address'    => $data['ongkir_address'] ?? null,
                'branch_id'         => $data['branch_id'] ?? null,
                'branch_process_id' => $data['branch_process_id'] ?? null,
            ]);
            $pos->created_at = now();
            $pos->save();
            $this->deleteDuplicatePosDrafts($userId, $invoiceNumber, $pos->id);

            // Simpan item transaksi
            $transaksiId = $pos->id;
            $settingExp  = SettingExp::first();

            // Convert global discount to nominal if it is a percentage (<= 100)
            $globalDiscountInput = $data['discount'] ?? 0;
            $globalDiscountNominal = ($globalDiscountInput > 0 && $globalDiscountInput <= 100) 
                ? ($data['subtotal'] * ($globalDiscountInput / 100)) 
                : $globalDiscountInput;

            foreach ($data['items'] as $item) {
                if (is_numeric($item['id'])) {
                    $itemTotal   = isset($item['total_input']) ? $item['total_input'] : (($item['price'] * $item['qty']) - ($item['discount'] ?? 0));
                    $prosentase  = $data['subtotal'] > 0 ? ($itemTotal / $data['subtotal']) : 0;
                    $posDiscount = $prosentase * $globalDiscountNominal;
                    $product     = Product::find($item['id']);

                    // Ambil parent/child dari product yang dipilih
                    $childProducts = $product->childProducts()->get();
                    $parentId      = $product->getParentId();

                    // Jika product memiliki parent, gunakan parent_id untuk HPP, jika tidak gunakan product itu sendiri
                    $hppProductId = $parentId ?? $product->id;
                    $productHpp   = ProductHppRunning::where('product_id', $hppProductId)
                        ->orderBy('created_at', 'desc')
                        ->orderBy('trx_id', 'desc')
                        ->first();
                    $currentStock = $productHpp ? $productHpp->qty_berjalan : 0;
                    $hppValue     = $productHpp ? ($productHpp->hpp_berjalan ?? 0) : 0;

                    $posDetail = PosDetailModel::create([
                        'pos_id'               => $transaksiId,
                        'product_id'           => $item['id'],
                        'price'                => $item['price'],
                        'quantity'             => $item['qty'],
                        'discount'             => $item['discount'] ?? 0,
                        'diskon_global'        => $posDiscount,
                        'subtotal'             => $itemTotal,
                        'price_after_discount' => $item['price'] - $posDiscount,
                        'exp'                  => $item['price'] - $hppValue,
                        'exp_value'            => ($item['price'] - $hppValue) * $settingExp->value_exp,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                        'type'                 => 'product',
                        'created_by'           => $userId,
                    ]);

                    if ($currentStock <= 0) {
                        $posDetail->update([
                            'hpp'           => 0,
                            'debt_quantity' => 0,
                            'subtotal_hpp'  => 0,
                        ]);
                    } else {
                        if ($currentStock < $posDetail->quantity) {
                            $posDetail->update([
                                'hpp'           => 0,
                                'debt_quantity' => $posDetail->quantity - $currentStock,
                                'subtotal_hpp'  => ($productHpp->hpp_berjalan ?? 0) * $currentStock,
                            ]);
                        } else {
                            $posDetail->update([
                                'hpp'           => $productHpp->hpp_berjalan ?? 0,
                                'debt_quantity' => 0,
                                'subtotal_hpp'  => ($productHpp->hpp_berjalan ?? 0) * $posDetail->quantity,
                            ]);
                        }
                    }
                }
            }

            if (isset($data['parcel'])) {
                foreach ($data['parcel'] as $key => $value) {
                    $parcel         = $value;
                    $kemasanProduct = null;
                    if (! empty($parcel['kemasan'])) {
                        $kemasanProduct = Product::where('name', $parcel['kemasan'])->first();
                    }
                    $productNameBase    = 'Parcel ' . $value['kemasan'] . '-' . formatRibuanToK(preg_replace('/[^0-9]/', '', $parcel['budget']));
                    $productPrice       = preg_replace('/[^0-9]/', '', $parcel['budget']);
                    
                    // Reusable Parcel Product (Anti-Bloat)
                    $product = Product::firstOrCreate(
                        ['name' => $productNameBase, 'tipe' => 'parcel'],
                        [
                            'description'  => $productNameBase,
                            'price'        => $productPrice,
                            'product_unit' => 3,
                            'status'       => 'no-receipt',
                            'hpp'          => preg_replace('/[^0-9]/', '', $parcel['hpp']),
                            'fee'          => preg_replace('/[^0-9]/', '', $parcel['fee']),
                            'created_by'   => $userId,
                        ]
                    );

                    $parcelSubtotal = $product->price;
                    $parcelProrate  = $data['subtotal'] > 0 ? ($parcelSubtotal / $data['subtotal']) : 0;
                    $parcelDiscount = $parcelProrate * $globalDiscountNominal;

                    $parcelHpp = preg_replace('/[^0-9]/', '', $parcel['hpp']);

                    PosDetailModel::insert([
                        'pos_id'        => $transaksiId,
                        'parcel_id'     => ! empty($parcel['kemasanId']) ? $parcel['kemasanId'] : ($kemasanProduct->id ?? null),
                        'product_id'    => $product->id,
                        'price'         => $product->price,
                        'quantity'      => $parcel['qty'],
                        'discount'      => 0,
                        'diskon_global' => $parcelDiscount,
                        'subtotal'      => $parcelSubtotal,
                        'kemasan_price' => isset($parcel['kemasanPrice']) ? preg_replace('/[^0-9]/', '', $parcel['kemasanPrice']) : ($kemasanProduct->price ?? 0),
                        'hpp'           => $parcelHpp,
                        'exp'           => $product->price - $parcelHpp,
                        'exp_value'     => ($product->price - $parcelHpp) * $settingExp->value_exp,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                        'type'          => 'parcel',
                        'created_by'    => $userId,
                    ]);
                    foreach ($value['data'] as $item) {
                        $parcelQtyPerSet = (float) ($item['qty'] ?? 0);
                        $parcelLinePrice = (float) ($item['price'] ?? 0);
                        $parcelBasePrice = (float) ($item['priceAwal'] ?? 0);

                        if ($parcelBasePrice <= 0 && $parcelQtyPerSet > 0) {
                            $parcelBasePrice = $parcelLinePrice / $parcelQtyPerSet;
                        }

                        ProductionParcelDetail::insert([
                            'production_id' => $product->id,
                            'pos_id'        => $transaksiId,
                            'product_id'    => $item['product'],
                            'kemasan_id'    => $parcel['kemasanId'] ?? null,
                            'quantity'      => $parcelQtyPerSet * $value['qty'],
                            'quantity_kemasan'  => $parcel['qty'],
                            'price'         => $parcelLinePrice,
                            'price_awal'    => $parcelBasePrice,
                        ]);
                    }
                }
            }

            if (!empty($data['jus'])) {
                $remainingStockJus = [];
                foreach ($data['jus'] as $index => $value) {
                    $productId = $value['productId'];
                    if (!isset($remainingStockJus[$productId])) {
                        $productStock = ProductStock::where('id', $productId)->where('branch_id', $data['branch_id'])->first();
                        $remainingStockJus[$productId] = $productStock ? (float)$productStock->stock_available : 0;
                    }

                    $currentStock = $remainingStockJus[$productId];
                    $qtyToProcess = (float)$value['qty'];

                    // Tentukan qty produksi berdasarkan ketersediaan stok
                    if ($currentStock <= 0) {
                        $productionQty = $qtyToProcess;
                        $consumeStockQty = 0;
                    } elseif ($currentStock < $qtyToProcess) {
                        $productionQty = $qtyToProcess - $currentStock;
                        $consumeStockQty = $currentStock;
                    } else {
                        $productionQty = 0;
                        $consumeStockQty = $qtyToProcess;
                    }

                    // Update remaining stock
                    $remainingStockJus[$productId] -= $consumeStockQty;

                    if ($productionQty > 0) {
                        $production = new Production([
                            'production_number' => Production::getOrderNumber(),
                            'product_id'        => $value['productId'],
                            'production_date'   => now(),
                            'status'            => $statusToSave === 'paid' || $statusToSave === 'debt' ? 'complete' : 'draft',
                            'created_by'        => Auth::user()->id_user,
                            'quantity'          => $productionQty,
                            'staff_id'          => Auth::user()->id_user,
                            'pos_id'            => $transaksiId,
                            'branch_id'         => $data['branch_id'] ?? null,
                        ]);
                        $production->save();
                        if (isset($value['product_receipt_id'])) {
                            foreach ($value['product_receipt_id'] as $receiptKey => $productReceiptId) {
                                $productionDetail = new ProductionDetail([
                                    'production_id' => $production->id,
                                    'product_id'    => $productReceiptId,
                                    'quantity'      => $value['product_receipt_qty'][$receiptKey] * $productionQty,
                                ]);
                                $productionDetail->save();
                            }
                        }
                    }

                    $jusSubtotal = isset($value['total_input']) ? $value['total_input'] : (($value['price'] * $value['qty']) - ($value['discount'] ?? 0));
                    $jusProrate  = $data['subtotal'] > 0 ? ($jusSubtotal / $data['subtotal']) : 0;
                    $jusDiscount = $jusProrate * $globalDiscountNominal;

                    PosDetailModel::insert([
                        'pos_id'     => $transaksiId,
                        'product_id' => $value['productId'],
                        'price'      => $value['price'],
                        'quantity'   => $value['qty'],
                        'discount'   => $value['discount'],
                        'diskon_global' => $jusDiscount,
                        'subtotal'   => $jusSubtotal,
                        'hpp'        => $value['hpp'],
                        'exp'        => $value['price'] - $value['hpp'],
                        'exp_value'  => ($value['price'] - $value['hpp']) * $settingExp->value_exp,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'type'       => 'jus',
                        'created_by' => $userId,
                    ]);
                }
            }

            if (! $isTempSave && preg_match('/ORD\d+/i', $data['invoice_number'])) {
                $orderBook = OrderBook::where('invoice_number', $data['invoice_number'])->first();
                if ($orderBook) {
                    $orderBook->status = 'done';
                    $orderBook->save();
                }
            }

            // Pastikan pos.created_at lebih baru dari production.created_at agar secara alami tampil di atas saat diurutkan DESC
            $latestProduction = Production::where('pos_id', $transaksiId)->orderBy('created_at', 'desc')->first();
            if ($latestProduction) {
                $pos->created_at = \Carbon\Carbon::parse($latestProduction->created_at)->addSeconds(1);
                $pos->save();
            }

            DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => 'Transaksi berhasil disimpan',
                'transaksi_id'   => $transaksiId,
                'invoice_number' => $pos->invoice_number,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function clearExistingPosRelations(int $posId): void
    {
        ProductionParcelDetail::where('pos_id', $posId)->delete();
        PosDetailModel::where('pos_id', $posId)->forceDelete();

        $productionIds = Production::where('pos_id', $posId)->pluck('id');
        if ($productionIds->isNotEmpty()) {
            ProductionDetail::whereIn('production_id', $productionIds)->delete();
            Production::whereIn('id', $productionIds)->delete();
        }
    }

    private function deleteDuplicatePosDrafts(int $userId, ?string $invoiceNumber, int $keepPosId): void
    {
        if (empty($invoiceNumber)) {
            return;
        }

        $duplicates = PosModel::where('invoice_number', $invoiceNumber)
            ->where('id', '!=', $keepPosId)
            ->get();

        foreach ($duplicates as $duplicate) {
            $this->clearExistingPosRelations($duplicate->id);
            $duplicate->forceDelete();
        }
    }

    public function payment($id)
    {
        if ($denied = $this->requireAccess('pos.payment')) {
            return $denied;
        }

        $data['alpinejs'] = true;
        $data['data']     = PosModel::with('customer')->findOrFail($id);

        if ($data['data']->status === 'paid') {
            return redirect()->route('pos.index')->with('error', 'Transaksi yang sudah dibayar (paid) tidak dapat diakses untuk pembayaran lagi.');
        }

        $data['detail']   = PosDetailModel::with('product')->where('pos_id', $id)->get();
        $data['tier']     = CustomerTier::where('customer_id', $data['data']->customer_id)->first();
        $data['deposito'] = CustomerDeposito::where('customer_id', $data['data']->customer_id)->where('quantity', '>', 0)->first();
        // dd($data);
        return view('pos::pos.payment', $data);
    }

    public function printPayment($id)
    {
        if ($denied = $this->requireAccess('pos.printPayment')) {
            return $denied;
        }

        // $data['data'] = PosModel::with('customer', 'user')->findOrFail($id);
        $data['listPayment'] = Payment::with('paymentMethod', 'pos')->where('pos_id', $id)->get();
        $data['setting']     = SettingNota::first();
        $data['detail']      = PosDetailModel::with('product')->where('pos_id', $id)->get();
        return view('pos::pos.print-list-payment', $data);
    }

    public function printDraftPayment($id)
    {
        if ($denied = $this->requireAccess('pos.printDraftPayment')) {
            return $denied;
        }

        $data['data']        = PosModel::with('customer', 'user')->findOrFail($id);
        $data['listPayment'] = Payment::with('paymentMethod', 'pos')->where('pos_id', $id)->get();
        $data['setting']     = SettingNota::first();
        $data['detail']      = PosDetailModel::with('product')->where('pos_id', $id)->get();
        $data['tier']        = CustomerTier::where('customer_id', $data['data']->customer_id)->first();
        $data['deposito']    = CustomerDeposito::where('customer_id', $data['data']->customer_id)->where('quantity', '>', 0)->first();
        // dd($data);
        return view('pos::pos.print2', $data);
    }

    public function uploadReceipt(Request $request)
    {
        if ($denied = $this->requireAccess('pos.upload-receipt')) {
            return $denied;
        }

        $request->validate([
            'image' => 'required|string',
        ]);

        $image = $request->input('image');

        // Validasi format base64 image
        if (!preg_match('/^data:image\/(png|jpg|jpeg|gif);base64,/', $image)) {
            return response()->json(['error' => 'Format gambar tidak didukung. Gunakan PNG, JPG, atau GIF.'], 400);
        }

        // Pisahkan data:image/png;base64,
        $image_parts  = explode(";base64,", $image);
        if (count($image_parts) < 2 || empty($image_parts[1])) {
            return response()->json(['error' => 'Data gambar tidak valid.'], 400);
        }

        $image_base64 = base64_decode($image_parts[1]);
        if ($image_base64 === false || strlen($image_base64) > 5 * 1024 * 1024) {
            return response()->json(['error' => 'Gambar terlalu besar (maks 5MB) atau tidak valid.'], 400);
        }

        $fileName = 'receipt_' . time() . '_' . Str::random(8) . '.png';

        // Simpan di public/storage/receipts
        $filePath = public_path('storage/receipts/' . $fileName);
        file_put_contents($filePath, $image_base64);

        return response()->json([
            'url' => asset('storage/receipts/' . $fileName),
        ]);
    }

    public function paymentNotification($id)
    {
        if ($denied = $this->requireAccess('pos.paymentNotification')) {
            return $denied;
        }

        $data['data']         = Payment::with('paymentMethod', 'pos')->findOrFail($id);
        $data['totalPayment'] = Payment::where('pos_id', $data['data']->pos_id)->sum('total');
        return view('pos::pos.payment-success2', $data);
    }

    public function printNota($id)
    {
        if ($denied = $this->requireAccess('pos.printNota')) {
            return $denied;
        }

        $data['payment'] = Payment::find($id);
        if (!$data['payment']) {
            return view('pos::pos.deleted-nota');
        }
        
        $data['data']    = PosModel::with('customer', 'user')->find($data['payment']->pos_id);
        if (!$data['data']) {
            return view('pos::pos.deleted-nota');
        }

        $data['setting'] = SettingNota::first();
        $data['detail']  = PosDetailModel::with('product')->where('pos_id', $data['payment']->pos_id)->get();
        $data['tier']    = CustomerTier::where('customer_id', $data['data']->customer_id)->first();
        // dd($data);
        return view('pos::pos.print2', $data);
    }

    public function cekNota($id)
    {
        $data['payment'] = Payment::where('uuid', $id)->first();
        if (isset($data['payment'])) {
            $data['data']    = PosModel::with('customer', 'user')->find($data['payment']->pos_id);
            if (!$data['data']) {
                return view('pos::pos.deleted-nota');
            }
            $data['setting'] = SettingNota::first();
            $data['detail']  = PosDetailModel::with('product')->where('pos_id', $data['payment']->pos_id)->get();
            $data['tier']    = CustomerTier::where('customer_id', $data['data']->customer_id)->first();
            // dd($data);
            return view('pos::pos.print2', $data);
        } else {
            return view('pos::pos.deleted-nota');
        }
    }

    public function cekNotaDraft($id)
    {
        $data['data'] = PosModel::with('customer', 'user')->where('uuid', $id)->first();
        if (! isset($data['data'])) {
            return view('pos::pos.deleted-nota');
        }
        $data['listPayment'] = Payment::with('paymentMethod', 'pos')->where('pos_id', $data['data']->id)->get();
        $data['setting']     = SettingNota::first();
        $data['detail']      = PosDetailModel::with('product')->where('pos_id', $data['data']->id)->get();
        $data['tier']        = CustomerTier::where('customer_id', $data['data']->customer_id)->first();
        // dd($data);
        return view('pos::pos.print2', $data);
    }

    public function get_data(Request $request)
    {
        $userId = auth()->id();
        $query  = PosModel::with('customer', 'paymentDetails', 'details', 'branch', 'user')
            ->whereIn('branch_id', UserBranch::getUserBranch());

        $query->where(function ($q) use ($userId) {
            $q->where('status', '!=', 'temp')
                ->orWhere('created_by', $userId);
        });

        if ($request->has('status_filter') && $request->status_filter !== 'all') {
            $query = $query->where('status', $request->status_filter);
        }
        // if ($request->has('cabang_filter') && $request->cabang_filter !== 'all') {
        //     $query->whereHas('paymentDetails', function ($q) use ($request) {
        //         $q->where('branch_id', $request->cabang_filter);
        //     });
        // }
        if ($request->has('cabang_filter') && $request->cabang_filter !== 'all') {
            $query = $query->where('branch_id', $request->cabang_filter);
        }
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        $data = $query->orderBy('date', 'DESC')->orderBy('created_at', 'DESC');
        // dd($data);
        return DataTables::of($data)
            ->filter(function ($q) use ($request) {
                $search = trim($request->input('search.value'));

                if (! empty($search)) {
                    $q->where(function ($sub) use ($search) {
                        $sub->whereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('whatsapp', 'LIKE', "%{$search}%");
                        });
                        $possibleDates = [];
                        $formats       = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd M Y', 'd F Y', 'd/m/Y H:i', 'd-m-Y H:i'];
                        foreach ($formats as $format) {
                            $date = \DateTime::createFromFormat($format, $search);
                            if ($date) {
                                $possibleDates[] = $date->format('Y-m-d');
                                break;
                            }
                        }
                        if (! empty($possibleDates)) {
                            foreach ($possibleDates as $dateStr) {
                                $sub->orWhereDate('date', $dateStr);
                            }
                        }
                        $sub->orWhere('status', 'LIKE', "%{$search}%");
                    });
                }
            }, true)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $hasParcel  = $item->details->contains(fn($detail) => isset($detail->product) && $detail->type === 'parcel');
                $iconHtml   = $hasParcel ? '<i class="bi bi-box-seam text-success" title="Ada Parcel"></i>' : '';
                $html       = '<div class="d-flex align-items-center">';
                $html      .= '<div class="ms-5">';
                if (isset($item->customer->name)) {
                    $waLast4  = ! empty($item->customer->whatsapp) ? ' (' . substr($item->customer->whatsapp, -4) . ')' : '';
                    $html    .= $iconHtml . ' <a href="' . url('pos') . '/show' . '/' . $item->id . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . $item->customer->name . $waLast4 . '</a> ';
                } else {
                    $html .= $iconHtml . ' <a href="' . url('pos') . '/show' . '/' . $item->id . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">Pelanggan Umum</a> ';
                }
                $html .= '<br><span class="text-muted d-block fs-7">Total Rp' . tonumberround($item->total) . '</span>';
                $html .= '<span class="text-muted d-block fs-7">Sisa Rp' . tonumberround($item->total_due - ($item->voucher ?? 0)) . '</span>';
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            })
            ->addColumn('total_price', function ($item) {
                return 'Rp. ' . number_format($item->total_price, 0, ',', '.');
            })
            ->addColumn('total_quantity', function ($item) {
                return $item->total_quantity;
            })
            ->addColumn('date', function ($item) {
                $date         = date('d M Y', strtotime($item->date)) . ' ' . date('H:i', strtotime($item->created_at));
                $statusLabels = [
                    'paid'     => ['label' => 'Lunas', 'class' => 'success'],
                    'draft'    => ['label' => 'Pending', 'class' => 'secondary'],
                    'debt'     => ['label' => 'Piutang', 'class' => 'danger'],
                    'canceled' => ['label' => 'Dihapus', 'class' => 'dark'],
                    'temp'     => ['label' => 'Pending', 'class' => 'secondary'],
                ];
                $statusInfo  = $statusLabels[$item->status] ?? ['label' => ucfirst($item->status), 'class' => 'warning'];
                $badge       = '<span class="badge badge-light-' . $statusInfo['class'] . '">' . $statusInfo['label'] . '</span>';
                $branchLabel = '';
                if ($item->branch) {
                    $branchLabel = '<span class="badge badge-light-primary ms-1">' . e($item->branch->name) . '</span>';
                }
                $userLabel = '';
                if ($item->user) {
                    $userLabel = '<span class="text-muted d-block fs-8 mt-1"><i class="bi bi-person"></i> ' . e(ucwords(strtolower($item->user->nm_user))) . '</span>';
                }

                return "<span class=\"text-muted d-block fs-8\">{$date}</span>{$badge}{$branchLabel}{$userLabel}";
            })
            ->addColumn('action', function ($item) {
                $html  = '';
                $html .= '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-2 text-center" style="min-width: 40px; z-index: 1050;">';
                $html .= '
                            <li>
                                <a class="dropdown-item px-2 py-2" href="' . route('pos.show', $item->id) . '" title="Lihat">
                                    <i class="bi bi-eye fs-5 text-info"></i>
                                </a>
                            </li>';
                $isRecent = false;
                $timeRef = isset($item->updated_at) ? $item->updated_at : (isset($item->created_at) ? $item->created_at : null);
                if ($timeRef && \Carbon\Carbon::parse($timeRef)->diffInMinutes(now()) <= 30) {
                    $isRecent = true;
                }

                if (in_array($item->status, ['temp', 'draft']) || ($isRecent && $item->status !== 'paid')) {
                    $html .= '
                            <li>
                                <a class="dropdown-item px-2 py-2" href="' . route('pos.edit', $item->id) . '" title="Edit">
                                    <i class="bi bi-pencil fs-5 text-primary"></i>
                                </a>
                            </li>';
                }
                if (! in_array($item->status, ['paid'])) {
                    $html .= '
                            <li>
                                <a class="dropdown-item px-2 py-2" href="' . route('pos.payment', $item->id) . '" title="Bayar">
                                    <i class="bi bi-cash-stack fs-5 text-success"></i>
                                </a>
                            </li>';
                }
                $html .= '
                            <li>
                                <a class="dropdown-item px-2 py-2" href="' . route('pos.printPayment', $item->id) . '" title="Cetak">
                                    <i class="fa fa-receipt fs-5 text-warning"></i>
                                </a>
                            </li>';
                if (! in_array($item->status, ['paid', 'debt']) || Session('role')['id_role'] == 1) {
                    $html .= '
                            <li>
                                <a class="dropdown-item px-2 py-2" href="javascript:void(0)" onclick="deleteProduct(' . $item->id . ')" title="Hapus">
                                    <i class="bi bi-trash fs-5 text-danger"></i>
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

    private function sumTotalPrice(array $items): int
    {
        return collect($items)->sum(function ($item) {
            return $item['total_input'] ?? ($item['price'] * $item['qty']);
        });
    }
}
