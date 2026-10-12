<?php
namespace Modules\Transaction\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Product;
use Modules\Master\Entities\ProductBranch;
use Modules\Master\Entities\ProductChild;
use Modules\Master\Entities\Supplier;
use Modules\Master\Entities\UserBranch;
use Modules\Pos\Entities\PosDetailModel;
use Modules\Transaction\Entities\ProductHppRunning;
use Modules\Transaction\Entities\ProductStock;
use Modules\Transaction\Entities\Wholesale;
use Modules\Transaction\Entities\WholesaleProduct;
use Yajra\DataTables\Facades\DataTables;

class WholesaleController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('wholesale.index')) {
            return $denied;
        }

        $userBranches = UserBranch::getUserBranch();
        $data['branches'] = Branch::whereIn('id', $userBranches)->get();

        $emptyStockProducts = DB::table('product_stock')
            ->join('branch', 'product_stock.branch_id', '=', 'branch.id')
            ->select('product_stock.*', 'branch.name as branch_name')
            ->whereIn('product_stock.branch_id', $userBranches)
            ->where('stock_available', '<', 0)
            ->whereNull('is_variant')
            ->get();

        $data['emptyStockData'] = $emptyStockProducts;

        return view('transaction::wholesale.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    // public function create()
    // {
    //     $wholsale = new Wholesale();
    //     $wholsale->order_date = date('Y-m-d');
    //     $wholsale->status = 'draft';
    //     $wholsale->created_by = Auth::user()->id_user;
    //     $wholsale->save();

    //     $data['products'] = Product::whereNull('parent_id')->get(); // optional: reset index numerik
    //     $data['suppliers'] = Supplier::all();
    //     return redirect('wholesale/' . $wholsale->id . '/edit');
    //     // return view('transaction::wholesale.create', $data);
    // }
    public function create()
    {
        if ($denied = $this->requireAccess('wholesale.create')) {
            return $denied;
        }

        $data['alpinejs'] = true;
        $userBranches = UserBranch::getUserBranch();

        // Cek apakah ada temp transaksi untuk user ini
        $draft = Wholesale::with(['branch', 'products', 'products.product', 'products.product.unit'])
            ->where('created_by', Auth::user()->id_user)
            ->where('status', 'temp')
            ->where(function ($query) use ($userBranches) {
                $query->whereNull('branch_id')->orWhereIn('branch_id', $userBranches);
            })
            ->orderBy('id', 'desc')
            ->first();

        if ($draft) {
            $draft->setRelation(
                'products',
                $this->hydrateWholesaleDetailPricing($draft->products, $draft->branch_id)
            );
            $data['data']           = $draft;
            $data['detail']         = $draft->products;
            $data['invoice_number'] = $draft->order_number;
        } else {
            $invoiceNumber = Wholesale::getOrderNumber();

            $draft = new Wholesale();
            $draft->order_number = $invoiceNumber;
            $draft->order_date   = date('Y-m-d');
            $draft->status       = 'temp';
            $draft->created_by   = Auth::user()->id_user;
            $draft->save();

            $data['data']           = $draft;
            $data['detail']         = [];
            $data['invoice_number'] = $invoiceNumber;
        }

        return view('transaction::wholesale.create2', $data);
    }

    /**
     * Save temp wholesale data for auto-save functionality.
     * @param Request $request
     * @return Renderable
     */
    public function saveTemp(Request $request)
    {
        // dd($request->all());
        $data = $request->validate([
            'order_date'  => 'required|date',
            'description' => 'nullable|string|max:255',
            'products'   => 'required|array',
            'invoice_number' => 'nullable|string',
        ]);

        try {
            $userId = Auth::user()->id_user;
            DB::beginTransaction();

            // Cek apakah sudah ada temp draft untuk user ini
            $existingDraft = Wholesale::where('created_by', $userId)
                ->where('status', 'temp')
                ->where('id', '!=', $request->wholesale_id ?? 0)
                ->first();

            // Jika ada draft lain yang bukan yang sedang diedit, hapus
            if ($existingDraft) {
                WholesaleProduct::where('wholesale_id', $existingDraft->id)->delete();
                $existingDraft->delete();
            }

            // Cek apakah akan update atau create baru
            if (!empty($request->wholesale_id)) {
                $wholesale = Wholesale::find($request->wholesale_id);
                if (!$wholesale) {
                    $wholesale = new Wholesale();
                    $wholesale->order_number = Wholesale::getOrderNumber();
                }
            } else {
                // Cek jika ada draft tanpa ID yang sudah ada
                $draft = Wholesale::where('created_by', $userId)
                    ->where('status', 'temp')
                    ->first();

                if ($draft) {
                    $wholesale = $draft;
                } else {
                    $wholesale = new Wholesale();
                    $wholesale->order_number = Wholesale::getOrderNumber();
                }
            }

            $wholesale->order_date   = $data['order_date'];
            $wholesale->description  = $data['description'] ?? null;
            $wholesale->status       = 'temp';
            $wholesale->created_by   = $userId;
            $wholesale->save();

            $wholesaleId = $wholesale->id;

            // Hapus produk lama dan ganti dengan yang baru
            WholesaleProduct::where('wholesale_id', $wholesaleId)->delete();

            foreach ($data['products'] as $product) {
                $wholesaleDetail               = new WholesaleProduct();
                $wholesaleDetail->wholesale_id = $wholesaleId;
                $wholesaleDetail->quantity     = $product['qty'];
                $wholesaleDetail->price        = $product['price'];
                $wholesaleDetail->total_price  = $product['price'] * $product['qty'];
                if (isset($product['type']) && $product['type'] == 'product') {
                    $wholesaleDetail->product_id = $product['id'];
                } else {
                    $wholesaleDetail->category_id = $product['id'] ?? null;
                }
                $wholesaleDetail->supplier_id = $product['supplier_id'] ?? null;
                $wholesaleDetail->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan sementara',
                'wholesale_id' => $wholesale->id,
                'invoice_number' => $wholesale->order_number,
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('wholesale.store')) {
            return $denied;
        }

        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'order_date'  => 'required',
            'description' => 'nullable|string|max:255',
            'products'    => 'required|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $wholesale               = new Wholesale();
            $wholesale->order_number = Wholesale::getOrderNumber();
            $wholesale->order_date   = $request->order_date;
            $wholesale->description  = $request->description;
            $wholesale->status       = $request->submit_type;
            $wholesale->created_by   = Auth::user()->id_user;
            $wholesale->save();

            $wholesaleId = $wholesale->id;
            foreach ($request->products as $product) {
                $wholesaleDetail               = new WholesaleProduct();
                $wholesaleDetail->wholesale_id = $wholesaleId;
                $wholesaleDetail->quantity     = $product['qty'];
                $wholesaleDetail->price        = $product['price'];
                $wholesaleDetail->total_price  = $product['price'] * $product['qty'];
                if ($product['type'] == 'product') {
                    $wholesaleDetail->product_id = $product['id'];
                } else {
                    $wholesaleDetail->category_id = $product['id'];
                }
                $wholesaleDetail->supplier_id = $product['supplier_id'];
                // dd($wholesaleDetail);
                $wholesaleDetail->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Pembuatan Data Kulak gagal' . $e->getMessage());
        }

        return redirect('wholesale')->with('success', 'Pembuatan Data Kulak berhasil');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if ($denied = $this->requireAccess('wholesale.show')) {
            return $denied;
        }

        // $data['suppliers'] = Supplier::all();
        // $data['data'] = Wholesale::findOrFail($id);
        // return view('transaction::wholesale.show', $data);

        $data['alpinejs']       = true;
        $data['data']           = Wholesale::with('branch')
            ->whereIn('branch_id', UserBranch::getUserBranch())
            ->findOrFail($id);
        $data['detail']         = $this->hydrateWholesaleDetailPricing(
            WholesaleProduct::with('product', 'product.unit')->where('wholesale_id', $id)->get(),
            $data['data']->branch_id
        );
        $data['invoice_number'] = $data['data']->order_number;
        return view('transaction::wholesale.create2', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    // public function edit($id)
    // {
    //     $data['suppliers'] = Supplier::all();
    //     $data['data'] = Wholesale::findOrFail($id);
    //     return view('transaction::wholesale.create', $data);
    // }

    public function edit($id)
    {
        if ($denied = $this->requireAccess('wholesale.edit')) {
            return $denied;
        }

        $data['alpinejs']       = true;
        $data['data']           = Wholesale::with('branch')
            ->whereIn('branch_id', UserBranch::getUserBranch())
            ->findOrFail($id);
        abort_if($data['data']->status === 'complete', 403, 'Pengadaan yang sudah selesai tidak dapat diedit.');
        $data['detail']         = $this->hydrateWholesaleDetailPricing(
            WholesaleProduct::with('product', 'product.unit')->where('wholesale_id', $id)->get(),
            $data['data']->branch_id
        );
        $data['invoice_number'] = $data['data']->order_number;
        return view('transaction::wholesale.create2', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function update_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('wholesale.update-product')) {
            return $denied;
        }

        // Validasi input
        // dd($request->all());
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:supplier,id',
            'price'       => 'required|numeric',
            'qty'         => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();
            $wholesaleProduct              = WholesaleProduct::findOrFail($id);
            $wholesaleProduct->supplier_id = $validated['supplier_id'];
            $wholesaleProduct->price       = $validated['price'];
            $wholesaleProduct->quantity    = $validated['qty'];
            $wholesaleProduct->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Product updated failed']);
        }

        return response()->json(['message' => 'Product updated successfully']);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('wholesale.update')) {
            return $denied;
        }

        // dd($request->all());
        // Validasi input
        $request->validate([
            'order_date'  => 'required|date',
            'submit_type' => 'required|in:draft,posting',
        ]);

        try {
            DB::beginTransaction();
            $wholesale = Wholesale::whereIn('branch_id', UserBranch::getUserBranch())->findOrFail($id);
            if ($wholesale->status === 'complete') {
                DB::rollBack();
                return redirect()->back()->with('error', 'Pengadaan yang sudah selesai tidak dapat diubah.');
            }

            if ($wholesale->order_number == null) {
                // Generate order number if not already set
                $wholesale->order_number = Wholesale::getOrderNumber();
            }
            $wholesale->status      = $request->submit_type;
            $wholesale->created_by  = Auth::user()->id_user;
            $wholesale->order_date  = $request->order_date;
            $wholesale->description = $request->description;

            if ($request->submit_type == 'posting') {
                $wholesaleProduct = WholesaleProduct::with('product', 'productStock')->where('wholesale_id', $id)->get();
                foreach ($wholesaleProduct as $key => $value) {
                    $wholesaleProductChild = ProductChild::with('product', 'productStock')->where('parent_id', $value->product_id);
                    $totalStock            = $wholesaleProductChild->get()->sum(function ($child) {
                        return $child->productStock->stock_available ?? 0;
                    });
                    // dd($totalStock);
                    $stock  = $value->productStock->stock_available ?? 0;
                    $stock += $totalStock;
                    $hpp    = $value->product->hpp ?? 0;

                    // dd($stock);
                    if ($stock == 0) {
                        Product::where("id", $value->product_id)->update([
                            'hpp' => $value->price,
                        ]);
                        Product::whereIn('id', $wholesaleProductChild->pluck('product_id')->toArray())->update([
                            'hpp' => $value->price,
                        ]);
                    } else {
                        $newHpp = collect([$hpp, $value->price])->avg();
                        Product::where("id", $value->product_id)->update([
                            'hpp' => $newHpp,
                        ]);
                        Product::whereIn('id', $wholesaleProductChild->pluck('product_id')->toArray())->update([
                            'hpp' => $newHpp,
                        ]);
                    }
                }
            }

            // dd($wholesaleProduct);
            $wholesale->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Pembuatan Data Kulak gagal' . $e->getMessage());
        }
        return redirect('wholesale')->with('success', 'Update Data Kulak berhasil');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('wholesale.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $wholesale = Wholesale::whereIn('branch_id', UserBranch::getUserBranch())->findOrFail($id);
            $wholesale->delete();
            WholesaleProduct::where('wholesale_id', $id)->delete();
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

    public function receive_productOld($id)
    {
        $data['data']             = Wholesale::findOrFail($id);
        $data['selectedProducts'] = WholesaleProduct::with('product')
            ->where('wholesale_id', $id)
            ->get()
            ->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'name'  => $item->product->name,
                    'sku'   => $item->product->sku,
                    'unit'  => $item->product->unit->abbreviation,
                    'price' => number_format($item->product->price, 0, ',', '.'),
                    'image' => asset('storage/' . $item->product->image),
                    'qty'   => $item->quantity,
                ];
            });
        // dd($data);
        return view('transaction::wholesale.receive_product', $data);
    }

    public function receive_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('wholesale.receive_product')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $wholesale         = Wholesale::findOrFail($id);
            $wholesale->status = 'complete';
            $wholesale->save();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diproses.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function save_receive(Request $request)
    {
        if ($denied = $this->requireAccess('wholesale.save-receive')) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'wholesale_id' => 'required|exists:wholesale,id',
            'products'     => 'required|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $wholesale         = Wholesale::findOrFail($request->wholesale_id);
            $wholesale->status = 'complete';
            $wholesale->save();

            foreach ($request->products as $key => $product) {
                $wholesaleDetail           = WholesaleProduct::findOrFail($key);
                $wholesaleDetail->quantity = $product['quantity'];
                $wholesaleDetail->hpp      = $product['price'];
                $wholesaleDetail->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Pembuatan Data Kulak gagal' . $e->getMessage());
        }

        return redirect('wholesale')->with('success', 'Pembuatan Data Kulak berhasil');
    }

    public function get_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('wholesale.get-product')) {
            return $denied;
        }

        // dd($request->all());
        $data = WholesaleProduct::where('wholesale_id', $id)->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $html = '';
                if ($item->supplier_id != null) {
                    $html .= $item->product->name . '<br>' . $item->supplier->name . '<br> Jumlah : ' . $item->quantity . ' ' . $item->product->unit->abbreviation;;
                } else {
                    $html .= $item->product->name . '<br>' . '<span class="text-danger">Tidak ada supplier</span>' . '<br> Jumlah : ' . $item->quantity . ' ' . $item->product->unit->abbreviation;;
                }
                return $html;
            })
            ->addColumn('supplier', function ($item) {
                if (isset($item->supplier_id) && $item->supplier_id != null) {
                    return $item->supplier->name;
                } else {
                    return '-';
                }
            })
            ->addColumn('price', function ($item) {
                return 'Rp' . toNumber($item->price);
            })
            ->addColumn('total', function ($item) {
                return 'Rp' . toNumber($item->total_price);
            })
            ->addColumn('action', function ($item) use ($request) {
                $html = '';

                if ($request->url == 'wholesale/show') {
                    $html .= '';
                } else {
                    $html .= '
                    <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                        <li>
                            <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="editProduct(' . $item->id . ')" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $item->id . ')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </li>
                    </ul>
                </div>
                    ';
                }
                return $html;
            })
            ->rawColumns(['name', 'action'])
            ->make(true);
    }

    public function save_product(Request $request)
    {
        if ($denied = $this->requireAccess('wholesale.save-product')) {
            return $denied;
        }

        $validated = $request->validate([
            'wholesale_id' => 'required|exists:wholesale,id',
            'id'           => 'required|exists:products,id',
            'supplier_id'  => 'nullable|exists:supplier,id',
            'qty'          => 'required',
            'price'        => 'required',
            'sell_price'   => 'nullable',
        ]);

        $cek = WholesaleProduct::where('wholesale_id', $validated['wholesale_id'])
            ->where('product_id', $validated['id'])
            ->first();
        if ($cek) {
            return response()->json([
                'message' => 'Product sudah ada',
            ], 404);
        }

        if ($validated['qty'] <= 0) {
            return response()->json([
                'message' => 'Qty tidak boleh 0',
            ], 404);
        }
        try {
            DB::beginTransaction();
            $wholesaleProduct               = new WholesaleProduct();
            $wholesaleProduct->wholesale_id = $validated['wholesale_id'];
            $wholesaleProduct->product_id   = $validated['id'];
            $wholesaleProduct->supplier_id  = $validated['supplier_id'];
            $wholesaleProduct->quantity     = $validated['qty'];
            $wholesaleProduct->price        = $validated['price'];
            $wholesaleProduct->total_price  = $validated['price'] * $validated['qty'];
            $wholesaleProduct->save();

            if ($validated['sell_price'] != null) {
                $product        = Product::findOrFail($validated['id']);
                $product->price = $validated['sell_price'];
                $product->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Product gagal disimpan.' . $e->getMessage(),
                'data'    => $wholesaleProduct,
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Product berhasil disimpan.',
            'data'    => $wholesaleProduct,
        ], 201);
    }

    public function delete_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('wholesale.delete_product')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $wholesaleProduct = WholesaleProduct::findOrFail($id);
            $wholesaleProduct->delete();
            DB::commit();
            return response()->json([
                'message' => 'Product berhasil dihapus.',
            ], 201);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Product gagal dihapus.',
            ], 404);
        }
    }

    public function edit_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('wholesale.edit-product')) {
            return $denied;
        }

        $data = WholesaleProduct::findOrFail($id);
        return response()->json([
            'data' => $data,
        ], 201);
    }

    public function receive_process($id)
    {
        if ($denied = $this->requireAccess('wholesale.receive_process')) {
            return $denied;
        }

        $wholesale        = Wholesale::findOrFail($id);
        $wholsaleProduct  = WholesaleProduct::with('product', 'supplier')->where('wholesale_id', $id)->get();
        $data['data']     = $wholesale;
        $data['products'] = $wholsaleProduct;

        return view('transaction::wholesale.process', $data);
    }

    public function update_receive_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('wholesale.update_receive_product')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $wholesaleProduct         = WholesaleProduct::findOrFail($id);
            $wholesaleProduct->status = 'complete';
            $wholesaleProduct->save();
            DB::commit();
            return response()->json([
                'message' => 'Product berhasil diterima.',
            ], 201);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Product gagal diterima.',
            ], 404);
        }
    }

    public function set_selesai(Request $request, $id)
    {
        if ($denied = $this->requireAccess('wholesale.set_selesai')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $wholesale         = Wholesale::findOrFail($id);
            $wholesale->status = 'complete';
            $wholesale->save();
            DB::commit();
            return response()->json([
                'message' => 'Product berhasil diterima.',
            ], 201);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Product gagal diterima.',
            ], 404);
        }
    }

    public function reset_transactions(Request $request)
    {
        if ($denied = $this->requireAccess('wholesale.reset')) {
            return $denied;
        }

        $tables = [
            'wholesale',
            'wholesale_product',
            'wholsale_product',
            'pos_transaction',
            'pos_transaction_detail',
            'pos_payment',
            'sortir_transaction',
            'sortir_transaction_detail',
            'production',
            'production_detail',
            'production_parcel',
            'production_parcel_detail',
            'transfer',
            'transfer_detail',
            'stock_opname',
            'stock_opname_discussions',
            'stock_opname_history',
            'order_book',
            'order_book_detail',
            'expenditure',
            'expenditure_detail',
            'expenditure_payment',
        ];

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $resetTables = [];
            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                DB::table($table)->truncate();
                $resetTables[] = $table;
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return response()->json([
                'success'        => true,
                'message'        => 'Transaksi wholesale berhasil direset.',
                'reset_tables'   => $resetTables,
            ]);
        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset transaksi wholesale.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getProductTableData(Request $request)
    {
        $searchValue = $request->input('searchValue'); // Ambil nilai pencarian
        if (empty($searchValue)) {
            return DataTables::of([])->make(true); // Kembalikan tabel kosong jika tidak ada pencarian
        }
        $query = Product::query()
            ->with('category')
            ->with('get_stock')
            ->where('name', 'like', '%' . $searchValue . '%');
        if ($request->url != 'parcel') {
            $query = $query->whereNull('is_variant');
        }

        // $data = Product::whereNull('parent_id')->get();
        $data = $query->get();
        return DataTables::of($data)
        // ->addColumn('checkbox', function ($row) {
        //     return '<div class="form-check form-check-sm form-check-custom form-check-solid">
        //             <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
        //         </div>';
        // })
            ->addColumn('name', function ($row) {
                return '<a href="javascript:void(0)" class="text-gray-800 text-hover-primary fs-5 fw-bold check-product">'
                . e($row->name) .
                    '</a>';
            })
            ->addColumn('qty_remaining', function ($row) {
                return '<span class="fw-bold ms-3 check-product">' . ($row->get_stock->stock_available) . '</span>';
            })
            ->rawColumns(['checkbox', 'name', 'qty_remaining'])
            ->make(true);
    }

    public function saveTransaction(Request $request)
    {
        if ($denied = $this->requireAccess('wholesale.save-transaction')) {
            return $denied;
        }

        // dd($request->all());
        $data = $request->validate([
            'branch_id'      => 'required|exists:branch,id',
            'date'           => 'required|date',
            'invoice_number' => 'nullable',
            'items'          => 'required|array',
            'total'          => 'required|numeric',
            'subtotal'       => 'required|numeric',
            'status'         => 'nullable|in:temp,draft,posting',
        ]);

        $userBranchIds = collect(UserBranch::getUserBranch())->map(fn ($branchId) => (int) $branchId)->all();
        if (! in_array((int) $data['branch_id'], $userBranchIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke cabang yang dipilih.',
            ], 403);
        }

        $userId = Auth::user()->id_user;
        if (! empty($data['invoice_number'])) {
            $existingOrder = Wholesale::where('order_number', $data['invoice_number'])->first();
            $isAccessibleOrder = $existingOrder && (
                ($existingOrder->branch_id !== null && in_array((int) $existingOrder->branch_id, $userBranchIds, true))
                || ($existingOrder->branch_id === null && (int) $existingOrder->created_by === (int) $userId)
            );

            if ($existingOrder && ! $isAccessibleOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke transaksi ini.',
                ], 403);
            }

            if ($existingOrder && $existingOrder->status === 'complete') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengadaan yang sudah selesai tidak dapat diubah.',
                ], 409);
            }
        }

        try {
            DB::beginTransaction();

            // =========================
            // HEADER (UPDATE / CREATE)
            // =========================

            // Jika status adalah temp, cek apakah ada existing temp draft untuk user ini
            $existingTempDraft = null;
            if (($data['status'] ?? 'draft') === 'temp') {
                $existingTempDraft = Wholesale::where('created_by', $userId)
                    ->where('status', 'temp')
                    ->where(function ($query) use ($userBranchIds) {
                        $query->whereNull('branch_id')->orWhereIn('branch_id', $userBranchIds);
                    })
                    ->when(!empty($data['invoice_number']), function($query) use ($data) {
                        return $query->where('order_number', '!=', $data['invoice_number']);
                    })
                    ->first();

                // Jika ada temp draft lain, hapus
                if ($existingTempDraft) {
                    WholesaleProduct::where('wholesale_id', $existingTempDraft->id)->delete();
                    $existingTempDraft->delete();
                    $existingTempDraft = null;
                }
            }

            $wholesaleData = [
                'branch_id'  => $data['branch_id'],
                'order_date' => $data['date'],
                'status'     => $data['status'] ?? 'draft',
                'created_by' => $userId,
            ];

            // Update atau create berdasarkan invoice_number
            if (!empty($data['invoice_number'])) {
                $wholesale = Wholesale::updateOrCreate(
                    ['order_number' => $data['invoice_number']],
                    $wholesaleData
                );
                $wholesale->created_at = now();
                $wholesale->save();
            } else {
                // Cek jika ada draft existing untuk diupdate
                $draft = Wholesale::where('created_by', $userId)
                    ->where('status', 'temp')
                    ->where(function ($query) use ($userBranchIds) {
                        $query->whereNull('branch_id')->orWhereIn('branch_id', $userBranchIds);
                    })
                    ->first();

                if ($draft) {
                    $wholesale = $draft;
                    $wholesale->update($wholesaleData);
                    $wholesale->created_at = now();
                    $wholesale->save();
                } else {
                    $wholesale = Wholesale::create($wholesaleData);
                    // Generate order number
                    $wholesale->order_number = Wholesale::getOrderNumber();
                    $wholesale->created_at = now();
                    $wholesale->save();
                }
            }

            $transaksiId = $wholesale->id;

            // =========================
            // DETAIL ITEM
            // =========================
            // Hapus semua item lama dan ganti dengan yang baru
            WholesaleProduct::where('wholesale_id', $transaksiId)->delete();

            $existingProductIds = [];

            foreach ($data['items'] as $item) {

                if (! is_numeric($item['id'])) {
                    continue;
                }

                $existingProductIds[] = $item['id'];

                // Calculate total_price if not provided
                $price = $item['price'] ?? 0;
                $qty = $item['qty'] ?? 1;
                $totalInput = $item['total_input'] ?? ($price * $qty);

                // INSERT DETAIL
                WholesaleProduct::create([
                    'wholesale_id' => $transaksiId,
                    'product_id'   => $item['id'],
                    'price'       => $price,
                    'quantity'    => $qty,
                    'total_price' => $totalInput,
                    'created_by'  => $userId,
                ]);

                // =========================
                // UPDATE HARGA JUAL CABANG
                // =========================
                if (! empty($item['sell'])) {
                    ProductBranch::updateOrCreate(
                        [
                            'product_id' => $item['id'],
                            'branch_id'  => $data['branch_id'],
                        ],
                        [
                            'price' => $item['sell'],
                        ]
                    );
                }

                // =========================
                // UPDATE HPP SAAT POSTING
                // =========================
                if (($data['status'] ?? 'draft') === 'posting') {

                    $productHpp = ProductHppRunning::where('product_id', $item['id'])
                        ->latest()
                        ->first();

                    if ($productHpp) {
                        Product::where('id', $item['id'])->update([
                            'hpp' => $productHpp->hpp_berjalan,
                        ]);

                        $childIds = ProductChild::where('parent_id', $item['id'])
                            ->pluck('product_id');

                        Product::whereIn('id', $childIds)->update([
                            'hpp' => $productHpp->hpp_berjalan,
                        ]);

                        if ((float) ($productHpp->qty_berjalan ?? 0) > 0) {
                            $hppBerjalan = (float) $productHpp->hpp_berjalan;
                            PosDetailModel::whereIn('product_id', $childIds)
                                ->where('hpp', 0)
                                ->update([
                                    'hpp'          => $hppBerjalan,
                                    'subtotal_hpp' => DB::raw(
                                        'CASE
                                            WHEN COALESCE(debt_quantity, 0) > 0
                                                THEN COALESCE(subtotal_hpp, 0) + (' . $hppBerjalan . ' * debt_quantity)
                                            ELSE ' . $hppBerjalan . ' * quantity
                                        END'
                                    ),
                                ]);
                        }
                    }
                }
            }

            DB::commit();
            DB::disconnect();

            return response()->json([
                'success'      => true,
                'message'      => 'Transaksi berhasil disimpan',
                'transaksi_id' => $transaksiId,
                'invoice_number' => $wholesale->order_number,
                'status'       => $wholesale->status,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            DB::disconnect();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function get_data(Request $request)
    {
        // dd($request->all());
        $data = Wholesale::getData($request);
        $canViewWholesale = function_exists('check_access') && check_access('wholesale.show');
        $canEditWholesale = function_exists('check_access') && check_access('wholesale.edit');
        $canDeleteWholesale = function_exists('check_access') && check_access('wholesale.destroy');
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('branch', function ($item) use ($canViewWholesale) {
                $detailUrl = route('wholesale.show', $item->id);
                $branchName = (string) ($item->branch_name ?? 'Tanpa cabang');
                $normalizedBranch = mb_strtolower(trim($branchName));
                $branchInitial = match (true) {
                    str_contains($normalizedBranch, 'gresik') => 'GR',
                    str_contains($normalizedBranch, 'surabaya') && str_contains($normalizedBranch, 'rungkut') => 'SR',
                    str_contains($normalizedBranch, 'surabaya') && str_contains($normalizedBranch, 'barat') => 'SB',
                    str_contains($normalizedBranch, 'sidoarjo') => 'SD',
                    str_contains($normalizedBranch, 'malang') => 'ML',
                    default => mb_strtoupper(mb_substr(preg_replace('/[^\pL\pN]/u', '', $branchName) ?: '—', 0, 2)),
                };
                $avatarClass = match ($branchInitial) {
                    'GR' => 'is-branch-gr',
                    'SR' => 'is-branch-sr',
                    'SB' => 'is-branch-sb',
                    'SD' => 'is-branch-sd',
                    'ML' => 'is-branch-ml',
                    default => 'is-branch-default',
                };
                $dateTime = \Carbon\Carbon::parse($item->order_date ?? $item->created_at);
                if (! empty($item->created_at)) {
                    $dateTime->setTimeFrom(\Carbon\Carbon::parse($item->created_at));
                }
                $mobileDate = $dateTime
                    ->locale('id')
                    ->translatedFormat('d M Y, H:i') . ' WIB';
                $detailLabel = '<span class="wholesale-order-number">' . e($branchName) . '</span>'
                    . '<span class="wholesale-order-subtitle">' . e($mobileDate) . '</span>';
                $detailLink = $canViewWholesale
                    ? '<a href="' . $detailUrl . '" class="wholesale-order-copy wholesale-open-detail" aria-label="Lihat pengadaan ' . e($item->order_number) . '">' . $detailLabel . '</a>'
                    : '<span class="wholesale-order-copy">' . $detailLabel . '</span>';

                return '<div class="wholesale-order-cell">
                            <span class="wholesale-order-avatar ' . $avatarClass . '" aria-hidden="true">' . e($branchInitial ?: '—') . '</span>
                            ' . $detailLink . '
                        </div>';
            })
            ->addColumn('po', function ($item) use ($canViewWholesale) {
                $poNumber = '#' . e($item->order_number);
                if (! $canViewWholesale) {
                    return '<span class="wholesale-po-link">' . $poNumber . '</span>';
                }

                return '<a href="' . route('wholesale.show', $item->id) . '" class="wholesale-po-link wholesale-open-detail">' . $poNumber . '</a>';
            })
            ->addColumn('staff', function ($item) {
                return '<span class="wholesale-staff-cell"><i class="ph ph-user" aria-hidden="true"></i>'
                    . e(ucwords(strtolower((string) ($item->created_by ?? '—'))))
                    . '</span>';
            })
            ->addColumn('items', function ($item) {
                $itemNames = trim((string) ($item->item_names ?? ''));
                $itemSummary = $itemNames !== '' ? e($itemNames) : 'Belum ada rincian bahan';

                return '<span class="wholesale-items-cell"><span class="wholesale-items-count">'
                    . e($item->total_product ?? 0) . ' jenis bahan</span><span class="wholesale-items-volume" title="' . $itemSummary . '">'
                    . $itemSummary . '</span></span>';
            })
            ->addColumn('order_date', function ($item) {
                $dateTime = \Carbon\Carbon::parse($item->order_date ?? $item->created_at);
                if (! empty($item->created_at)) {
                    $dateTime->setTimeFrom(\Carbon\Carbon::parse($item->created_at));
                }

                return $dateTime
                    ->locale('id')
                    ->translatedFormat('d F Y H:i');
            })
            ->addColumn('status', function ($item) {
                $status = (string) ($item->status ?? '');
                [$label, $class] = match ($status) {
                    'temp' => ['Draft', 'is-muted'],
                    'draft' => ['Pending', 'is-draft'],
                    'posting' => ['Dalam Pengiriman', 'is-posting'],
                    'complete' => ['Selesai', 'is-complete'],
                    default => [ucfirst($status ?: 'Tidak diketahui'), 'is-muted'],
                };

                return '<div class="wholesale-status-cell">'
                    . '<span class="wholesale-status-badge ' . $class . '">' . e($label) . '</span>'
                    . '<span class="wholesale-status-total">Rp ' . number_format((float) ($item->total_amount ?? 0), 0, ',', '.') . '</span>'
                    . '</div>';
            })
            ->addColumn('status_raw', function ($item) {
                return $item->status;
            })
            ->addColumn('action', function ($item) use ($canViewWholesale, $canEditWholesale, $canDeleteWholesale) {
                $html = '<div class="wholesale-row-actions">';
                if ($canViewWholesale) {
                    $html .= '<button class="wholesale-action-button wholesale-open-detail" type="button" aria-label="Lihat pengadaan ' . e($item->order_number) . '"><i class="ph ph-eye" aria-hidden="true"></i></button>';
                }
                if ($item->status !== 'complete' && $canEditWholesale) {
                    $html .= '<a class="wholesale-action-button" href="' . route('wholesale.edit', $item->id) . '" aria-label="Edit pengadaan ' . e($item->order_number) . '"><i class="ph ph-pencil-simple" aria-hidden="true"></i></a>';
                }
                if ($item->status !== 'complete' && $canDeleteWholesale) {
                    $html .= '<button class="wholesale-action-button is-danger" type="button" onclick="deleteProduct(' . (int) $item->id . ')" aria-label="Hapus pengadaan ' . e($item->order_number) . '"><i class="ph ph-trash" aria-hidden="true"></i></button>';
                }
                return $html . '</div>';
            })
            ->addColumn('wholesale_id', function ($item) {
                return $item->id;
            })
            ->addColumn('supplier_search', function ($item) {
                return (string) ($item->supplier_names ?? '');
            })
            ->addColumn('detail_payload', function ($item) use ($canViewWholesale, $canEditWholesale, $canDeleteWholesale) {
                if (! $canViewWholesale) {
                    return null;
                }

                $canEdit = $item->status !== 'complete' && $canEditWholesale;

                return [
                    'id' => (int) $item->id,
                    'order_number' => (string) ($item->order_number ?? '—'),
                    'branch_name' => ucwords((string) ($item->branch_name ?? 'Tanpa cabang')),
                    'total_label' => 'Rp ' . number_format((float) ($item->total_amount ?? 0), 0, ',', '.'),
                    'item_count_label' => (int) ($item->total_product ?? 0) . ' Bahan Baku',
                    'creator_name' => ucwords(strtolower((string) ($item->created_by ?? '—'))),
                    'created_at_label' => \Carbon\Carbon::parse($item->created_at ?? $item->order_date)
                        ->locale('id')
                        ->translatedFormat('d M Y, H:i') . ' WIB',
                    'edit_url' => $canEdit ? route('wholesale.edit', $item->id) : null,
                    'can_edit' => (bool) $canEdit,
                    'can_delete' => $item->status !== 'complete' && $canDeleteWholesale,
                ];
            })
            ->rawColumns(['branch', 'po', 'staff', 'items', 'action', 'status', 'address'])
            ->make(true);
    }

    private function hydrateWholesaleDetailPricing($detail, $branchId = null)
    {
        if (! $detail || $detail->isEmpty()) {
            return $detail;
        }

        $branchPrices = collect();

        if (! empty($branchId)) {
            $branchPrices = ProductBranch::where('branch_id', $branchId)
                ->whereIn('product_id', $detail->pluck('product_id')->filter()->all())
                ->pluck('price', 'product_id');
        }

        return $detail->map(function ($item) use ($branchPrices) {
            $fallbackSellPrice = optional($item->product)->price ?? 0;
            $sellPrice = $branchPrices->get($item->product_id, $fallbackSellPrice);

            $item->setAttribute('sell_price', (float) $sellPrice);
            $item->setAttribute('subtotal', (float) ($item->total_price ?? 0));

            return $item;
        });
    }
}
