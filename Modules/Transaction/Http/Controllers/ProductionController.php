<?php
namespace Modules\Transaction\Http\Controllers;

use App\Support\Like;
use App\Support\InventorySnapshot;
use App\Support\ProductionCost;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Product;
use Modules\Master\Entities\ProductBranch;
use Modules\Master\Entities\UserBranch;
use Modules\Transaction\Entities\Production;
use Modules\Transaction\Entities\ProductionDetail;
use Modules\Transaction\Entities\ProductReceipt;
use Modules\Transaction\Entities\ProductStock;
use Modules\Transaction\Entities\Receipt;
use Yajra\DataTables\Facades\DataTables;

class ProductionController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('production.index')) {
            return $denied;
        }

        $data['branches'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        return view('transaction::production.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('production.create')) {
            return $denied;
        }

        $data['alpinejs'] = true;

        // Cek apakah ada temp production untuk user ini
        $draft = Production::with(['products'])
            ->where('created_by', Auth::user()->id_user)
            ->where('status', 'temp')
            ->orderBy('id', 'desc')
            ->first();

        if ($draft) {
            // Jika ada draft, load data draft
            $data['data']              = $draft;
            $data['production_detail'] = ProductionDetail::with('products')->where('production_id', $draft->id)->get();
            $data['production_number'] = $draft->production_number;
            // Restore the draft's product too, so resuming shows the whole draft instead of only its ingredients.
            if ($draft->product_id && $draft->products) {
                $data['edit_product_id']   = $draft->product_id;
                $data['edit_product_name'] = $draft->products->name;
            }
            $data['edit_sell_price']   = $draft->sell_price;
            $data['edit_service_cost'] = $draft->service_cost;
        } else {
            // Jika tidak ada draft, siapkan data kosong
            $data['data']              = null;
            $data['production_detail'] = collect(); // Empty collection
            $data['production_number'] = Production::getOrderNumber();
        }

        return view('transaction::production.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('production.store')) {
            return $denied;
        }

        // dd($request->all());
        $validator = Validator::make($request->all(), [

            'product_id'             => 'required|exists:products,id',
            'production_number'      => 'required|string',
            'submit_type'            => 'required|in:draft,posting,temp',
            'production_date'        => 'required|date',
            'ingredients'            => 'required|array|min:1',
            'ingredients.*.id'       => 'required|exists:products,id',
            'ingredients.*.quantity' => 'required|numeric|min:0',
            'ingredients.*.hpp'      => 'required|numeric|min:0',
            'quantity'               => 'required|numeric|min:1',
            'branch_id'              => 'nullable|exists:branch,id',
            'sell_price'             => 'nullable',
            'notes'                  => 'nullable|string',
            'service_cost'           => 'nullable',
        ]);

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            return response()->json([
                'success' => false,
                'message' => 'Produksi gagal: ' . implode('. ', $errorMessages),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // Validasi stok bahan baku (seperti PosController saveTransaction)
            if (in_array($request->submit_type, ['draft', 'posting'])) {
                // One stock query for all ingredients (the view rebuilds the whole ledger per call).
                $stock = InventorySnapshot::stockAvailable(array_column((array) $request->ingredients, 'id'), $request->branch_id);
                foreach ($request->ingredients as $ingredient) {
                    $requiredQty = (float)$ingredient['quantity'];
                    $currentStock = $stock[(int) $ingredient['id']] ?? 0.0;

                    if ($currentStock < $requiredQty) {
                        $productName = Product::where('id', $ingredient['id'])->value('name') ?? 'Bahan #' . $ingredient['id'];
                        throw new \Exception("Stok bahan baku \"{$productName}\" tidak mencukupi. Stok tersedia: {$currentStock}, dibutuhkan: {$requiredQty}");
                    }
                }
            }

            DB::beginTransaction();

            // Check if this is an update (id is provided) or a new record
            $production = null;
            if ($request->id) {
                $production = Production::find($request->id);
            }

            // If no production found with ID, check for existing temp production
            if (! $production) {
                $production = Production::where('created_by', Auth::user()->id_user)
                    ->where('status', 'temp')
                    ->where('production_number', $request->production_number)
                    ->first();
            }

            // If still no production, create new one
            if (! $production) {
                $production                    = new Production();
                $production->production_number = $request->production_number;
                $production->created_by        = Auth::user()->id_user;
            }

            // Update production data
            $production->product_id      = $request->product_id;
            $production->production_date = $request->production_date;
            $production->status          = $request->submit_type;
            $production->quantity        = $request->quantity;
            $production->staff_id        = $request->staff_id;
            $production->description     = $request->notes;
            $production->branch_id       = $request->branch_id;
            $production->updated_by      = Auth::user()->id_user;
            $production->service_cost    = preg_replace('/[^0-9]/', '', $request->service_cost);
            if ($request->sell_price) {
                $production->sell_price = preg_replace('/[^0-9]/', '', $request->sell_price);
            }
            $production->created_by      = Auth::user()->id_user;
            $production->save();

            // Clear existing production details
            ProductionDetail::where('production_id', $production->id)->delete();

            // Add new production details from ingredients array
            foreach ($request->ingredients as $ingredient) {
                $productionDetail                = new ProductionDetail();
                $productionDetail->production_id = $production->id;
                $productionDetail->product_id    = $ingredient['id'];
                $productionDetail->quantity      = $ingredient['quantity'];
                $productionDetail->created_by    = Auth::user()->id_user;
                $productionDetail->save();
            }

            // Handle different submit types like PosController
            if ($request->submit_type == 'temp') {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Produksi berhasil disimpan',
                ], 200);
            } elseif ($request->submit_type == 'draft') {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Produksi berhasil disimpan',
                ], 200);
            } else {
                // posting - freeze HPP from the ingredients' moving HPP (server side)
                ProductionCost::freeze($production, (float) $production->service_cost);

                $branch = ProductBranch::where('product_id', $request->product_id)
                    ->where('branch_id', $request->branch_id)
                    ->first();
                if ($branch) {
                    $branch->price = $request->sell_price;
                    $branch->save();
                } else {
                    $branch             = new ProductBranch();
                    $branch->product_id = $request->product_id;
                    $branch->branch_id  = $request->branch_id;
                    $branch->price      = $request->sell_price;
                    $branch->save();
                }
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Produksi berhasil disimpan',
                ], 200);
            }

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Produksi gagal: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show(Request $request, $id)
    {
        if ($denied = $this->requireAccess('production.detail')) {
            return $denied;
        }

        try {
            $data['data']              = Production::with(['products', 'products.unit', 'staff', 'creator', 'branch', 'pos.customer'])->findOrFail($id);
            $data['production_detail'] = ProductionDetail::with(['products', 'products.unit', 'products.category'])->where('production_id', $id)->get();

            // Calculate totals with null safety
            $data['total_hpp'] = $data['production_detail']->sum(function ($item) {
                return ($item->products->hpp ?? 0) * $item->quantity;
            });

            $data['hpp_per_unit'] = $data['data']->quantity > 0 ? $data['total_hpp'] / $data['data']->quantity : 0;

            // Posted runs show the HPP frozen at posting, not today's ingredient prices.
            if ($data['data']->hpp_per_unit !== null) {
                $data['hpp_per_unit'] = (float) $data['data']->hpp_per_unit;
                $data['total_hpp']    = $data['hpp_per_unit'] * (float) $data['data']->quantity;
            }

            // The list's popup asks for the compact summary of the reference; a direct visit keeps the full page.
            if ($request->ajax()) {
                [$data['emoji'], $data['icon_class']] = \Modules\Master\Support\ProductIcon::resolve($data['data']->products);

                return view('transaction::production.show-modal', $data);
            }

            return view('transaction::production.show', $data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('production.index')->with('error', 'Data produksi tidak ditemukan.');
        } catch (\Exception $e) {
            return redirect()->route('production.index')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('production.edit')) {
            return $denied;
        }

        $data['alpinejs']          = true;
        $data['data']              = Production::with(['branch', 'products'])->find($id);
        $data['production_detail'] = ProductionDetail::with('products')->where('production_id', $id)->get();
        $data['receipt']           = Receipt::with('products')->where('product_id', $data['data']->product_id)->first();
        $data['production_number'] = $data['data']->production_number;
        $data['id']                = $id;
        $data['edit_quantity']     = $data['data']->quantity;
        $data['edit_product_id']   = $data['data']->product_id;
        $data['edit_product_name'] = $data['data']->products ? $data['data']->products->name : '';
        $data['edit_sell_price']  = $data['data']->sell_price;
        $data['edit_service_cost'] = $data['data']->service_cost;
        return view('transaction::production.create', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('production.update')) {
            return $denied;
        }

        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'product_id'      => 'required|exists:receipt,id',
            'submit_type'     => 'required|in:draft,posting,temp',
            'production_date' => 'required|date',
            'quantity'        => 'required',
            'staff_id'        => 'nullable|exists:staff,id',
            'branch_id'       => 'nullable|exists:branch,id',
            'sell_price'      => 'nullable',
            'service_cost'    => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $receipt   = Receipt::find($request->product_id);
            $productId = $receipt->product_id;
            DB::beginTransaction();
            $production                  = Production::findOrFail($id);
            $production->product_id      = $productId;
            $production->production_date = $request->production_date;
            $production->status          = $request->submit_type;
            $production->created_by      = Auth::user()->id_user;
            $production->quantity        = $request->quantity;
            $production->staff_id        = $request->staff_id;
            $production->branch_id       = $request->branch_id;
            $production->service_cost    = $request->service_cost ?? 0;
            if ($request->sell_price) {
                $production->sell_price = preg_replace('/[^0-9]/', '', $request->sell_price);
            }
            if ($request->submit_type == 'posting') {
                $this->assertIngredientStock($production);
            }
            $production->save();

            // Jika status temp, redirect ke halaman yang sama untuk continue editing
            if ($request->submit_type == 'temp') {
                DB::commit();
                return redirect()->back()->with('success', 'Data berhasil disimpan sebagai draft');
            }

            // Jika status draft, redirect ke payment
            if ($request->submit_type == 'draft') {
                DB::commit();
                return redirect()->route('production.payment', $id);
            }

            if ($request->submit_type == 'posting') {
                ProductionCost::freeze($production, (float) $production->service_cost);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Produksi gagal' . $e->getMessage());
        }

        return redirect('production')->with('success', 'Produksi berhasil');
    }

    /**
     * Stops a posting when an ingredient has less stock than the run uses, so
     * stock never goes below zero. Runs already posted are skipped: their usage
     * is already in the stock ledger.
     */
    private function assertIngredientStock(Production $production): void
    {
        if (in_array($production->getOriginal('status'), ['posting', 'complete'], true)) {
            return;
        }

        $needed = [];
        foreach (ProductionDetail::where('production_id', $production->id)->get(['product_id', 'quantity']) as $detail) {
            $needed[(int) $detail->product_id] = ($needed[(int) $detail->product_id] ?? 0) + (float) $detail->quantity;
        }

        $stock = InventorySnapshot::stockAvailable(array_keys($needed), $production->branch_id);
        foreach ($needed as $productId => $requiredQty) {
            $currentStock = $stock[$productId] ?? 0.0;
            if ($currentStock < $requiredQty) {
                $productName = Product::where('id', $productId)->value('name') ?? 'Bahan #' . $productId;
                throw new \Exception("Stok bahan baku \"{$productName}\" tidak mencukupi. Stok tersedia: {$currentStock}, dibutuhkan: {$requiredQty}");
            }
        }
    }

    /**
     * Show payment page (similar to POS)
     * @param int $id
     * @return Renderable
     */
    public function payment($id)
    {
        if ($denied = $this->requireAccess('production.payment')) {
            return $denied;
        }

        $data['data']              = Production::with('products')->findOrFail($id);
        $data['production_detail'] = ProductionDetail::with('products')->where('production_id', $id)->get();
        return view('transaction::production.payment', $data);
    }

    /**
     * Save production completion (similar to POS savePayment)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveCompletion(Request $request)
    {
        if ($denied = $this->requireAccess('production.save-completion')) {
            return $denied;
        }

        $data = $request->validate([
            'production_id'   => 'required|exists:production,id',
            'completion_date' => 'required|date',
            'notes'           => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $production                  = Production::findOrFail($data['production_id']);
            $this->assertIngredientStock($production);
            $production->status          = 'posting';
            $production->completion_date = $data['completion_date'];
            $production->notes           = $data['notes'] ?? null;
            $production->save();

            ProductionCost::freeze($production, (float) $production->service_cost);

            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => 'Produksi berhasil diselesaikan',
                'production_id' => $data['production_id'],
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan produksi',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show completion notification (similar to POS paymentNotification)
     * @param int $id
     * @return Renderable
     */
    public function completionNotification($id)
    {
        if ($denied = $this->requireAccess('production.completion-notification')) {
            return $denied;
        }

        $data['data']              = Production::with('products')->findOrFail($id);
        $data['production_detail'] = ProductionDetail::with('products')->where('production_id', $id)->get();
        return view('transaction::production.completion-success', $data);
    }

    /**
     * Print production report (similar to POS printNota)
     * @param int $id
     * @return Renderable
     */
    public function printProduction($id)
    {
        if ($denied = $this->requireAccess('production.print')) {
            return $denied;
        }

        $data['data']              = Production::with('products')->findOrFail($id);
        $data['production_detail'] = ProductionDetail::with('products')->where('production_id', $id)->get();
        return view('transaction::production.print', $data);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('production.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $production = Production::findOrFail($id);
            $production->delete();
            ProductionDetail::where('production_id', $id)->delete();
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

    public function delete_detail(Request $request, $id)
    {
        if ($denied = $this->requireAccess('production.delete_detail')) {
            return $denied;
        }

        try {
            $userId = Auth::user()->id_user;
            DB::beginTransaction();
            $receipt   = Receipt::with('products')->find($request->receipt_id);
            $productId = $receipt->product_id;
            Production::where('id', $id)->update([
                'product_id' => $productId,
                'status'     => 'draft',
            ]);
            ProductionDetail::where('production_id', $id)->delete();
            $newProduct       = ProductReceipt::where('receipt_id', $request->receipt_id)->get();
            $productionDetail = [];
            foreach ($newProduct as $key => $product) {
                $productionDetail[] = [
                    'production_id' => $id,
                    'product_id'    => $product->product_receipt_id,
                    'quantity'      => $product->quantity,
                    'created_by'    => $userId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
            if (! empty($productionDetail)) {
                ProductionDetail::insert($productionDetail);
            }
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

    public function update_product_id(Request $request, $id)
    {
        if ($denied = $this->requireAccess('production.update_product_id')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $receipt   = Receipt::with('products')->find($request->receipt_id);
            $productId = $receipt->product_id;
            Production::where('id', $id)->update([
                'product_id' => $productId,
                'status'     => 'draft',
            ]);
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

    public function get_detail_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('production.get-receipt')) {
            return $denied;
        }

        // dd($request->all());
        $data = ProductionDetail::with('products')->where('production_id', $id)->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $html  = '';
                $html .= $item->products->name . '<br> Jumlah : ' . $item->quantity . ' ' . $item->products->unit->abbreviation . '<br> Harga : ' . toNumber($item->products->price) . '';
                return $html;
            })
            ->addColumn('hpp', function ($item) {
                return 'Rp' . tonumberround($item->products->hpp * $item->quantity);
            })
            ->addColumn('harga_jual', function ($item) {
                return toNumber($item->products->price * $item->quantity);
            })
            ->addColumn('action', function ($item) use ($request) {
                $html = '';

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
                return $html;
            })
            ->rawColumns(['name', 'action', 'harga_jual'])
            ->make(true);
    }

    public function edit_additional_ingredient(Request $request, $id)
    {
        if ($denied = $this->requireAccess('production.edit-ajax')) {
            return $denied;
        }

        $data = ProductionDetail::with('products')->findOrFail($id);
        return response()->json([
            'data' => $data,
        ], 201);
    }

    public function save_additional_ingredient(Request $request)
    {
        if ($denied = $this->requireAccess('production.save-ajax')) {
            return $denied;
        }

        // dd($request->all());
        try {
            DB::beginTransaction();
            $productionDetail                = new ProductionDetail();
            $productionDetail->production_id = $request->production_id;
            $productionDetail->product_id    = $request->product_id;
            $productionDetail->quantity      = $request->qty;
            $productionDetail->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Product gagal disimpan.' . $e->getMessage(),
                'data'    => $productionDetail,
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Product berhasil disimpan.',
            'data'    => $productionDetail,
        ], 201);
    }

    public function update_additional_ingredient(Request $request, $id)
    {
        if ($denied = $this->requireAccess('production.edit-ajax')) {
            return $denied;
        }

        // dd($request->all());
        $validated = $request->validate([
            'qty'        => 'required|numeric',
            'sell_price' => 'nullable',
        ]);

        try {
            DB::beginTransaction();
            $detailProduct           = ProductionDetail::findOrFail($id);
            $detailProduct->quantity = $validated['qty'];
            $detailProduct->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Product updated failed']);
        }

        return response()->json(['message' => 'Product updated successfully']);
    }

    public function delete_additional_ingredient(Request $request, $id)
    {
        if ($denied = $this->requireAccess('production.delete-ajax')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $deteilProduct = ProductionDetail::findOrFail($id);
            $deteilProduct->delete();
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

    public function get_data(Request $request)
    {
        try {
            $query = Production::with(['products', 'products.category', 'products.unit', 'branch', 'staff', 'productionDetails', 'productionDetails.products', 'pos.customer'])
                ->whereNotNull('product_id');

            if ($request->has('status_filter') && $request->status_filter !== 'all' && ! empty($request->status_filter)) {
                $query->where('status', $request->status_filter);
            } else {
                $statusColumnSearch = trim((string) data_get($request->input('columns', []), '1.search.value', ''));
                if ($statusColumnSearch !== '') {
                    $query->where('status', $statusColumnSearch);
                }
            }

            if ($request->has('cabang_filter') && $request->cabang_filter !== 'all' && ! empty($request->cabang_filter)) {
                $query->where('branch_id', $request->cabang_filter);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('production_date', [$request->start_date, $request->end_date]);
            }

            $data = $query->orderBy('id', 'DESC');

            return DataTables::of($data)
                ->filter(function ($q) use ($request) {
                    $search = trim((string) $request->input('search.value'));

                    if ($search === '') {
                        return;
                    }

                    $q->where(function ($sub) use ($search) {
                        $sub->where('production_number', 'LIKE', Like::contains($search))
                            ->orWhere('status', 'LIKE', Like::contains($search))
                            ->orWhereHas('products', function ($productQuery) use ($search) {
                                $productQuery->where('name', 'LIKE', Like::contains($search));
                            })
                            ->orWhereHas('branch', function ($branchQuery) use ($search) {
                                $branchQuery->where('name', 'LIKE', Like::contains($search));
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

                        foreach ($possibleDates as $dateStr) {
                            $sub->orWhereDate('production_date', $dateStr);
                        }
                    });
                }, true)
                ->addColumn('name', function ($item) {
                    $productName     = $item->products ? e($item->products->name) : 'N/A';
                    $number          = e($item->production_number ?? 'N/A');
                    $createdAt       = $item->created_at ? date('d/m/Y H:i', strtotime($item->created_at)) : 'N/A';
                    $ingredientCount = $item->productionDetails ? $item->productionDetails->count() : 0;
                    $ingredientLine  = $ingredientCount > 0
                        ? ' · ' . $ingredientCount . ' bahan'
                        : '';

                    [$emoji, $iconClass] = \Modules\Master\Support\ProductIcon::resolve($item->products);

                    return '<div class="production-row">
                                <span class="production-avatar ' . e($iconClass) . '" aria-hidden="true">' . e($emoji) . '</span>
                                <a href="' . route('production.detail', $item->id) . '" class="production-copy">
                                    <span class="production-product">' . $productName . '</span>
                                    <span class="production-sub">' . $number . ' · ' . $createdAt . $ingredientLine . '</span>
                                </a>
                            </div>';
                })
                ->addColumn('production_date', function ($item) {
                    $date = $item->created_at ? date('d/m/Y H:i', strtotime($item->created_at)) : 'N/A';

                    $result = '<span class="text-muted d-block fs-8">' . $date . '</span>';

                    if ($item->branch) {
                        $result .= '<span class="badge badge-light-primary">' . e($item->branch->name) . '</span>';
                    }

                    if ($item->staff) {
                        $result .= '<br><small class="text-muted">' . e($item->staff->name) . '</small>';
                    }

                    return $result;
                })
                ->addColumn('status', function ($item) {
                    $posBadge = '';
                    if ($item->pos_id) {
                        $customerName = $item->pos && $item->pos->customer
                            ? e($item->pos->customer->name)
                            : 'Pelanggan Umum';
                        $posBadge = '<span class="production-pos">POS · ' . $customerName . '</span>';
                    }

                    $labels = [
                        'posting'  => ['Completed', 'is-posting'],
                        'complete' => ['Complete', 'is-complete'],
                        'draft'    => ['Draft', 'is-draft'],
                        'temp'     => ['Temp', 'is-temp'],
                    ];
                    [$label, $class] = $labels[$item->status] ?? [ucfirst($item->status ?? 'Unknown'), 'is-muted'];
                    $quantity        = number_format($item->quantity ?? 0, 0, ',', '.');
                    $unit            = e($item->products->unit->abbreviation ?? '');

                    return '<div class="production-status-cell">
                                <span class="production-status ' . $class . '">' . $label . '</span>
                                <span class="production-qty" data-unit="' . $unit . '">Qty: ' . $quantity . ($unit !== '' ? ' ' . $unit : '') . '</span>
                                ' . $posBadge . '
                            </div>';
                })
                ->addColumn('action', function ($row) {
                    $editUrl   = route('production.edit', $row->id);
                    $deleteUrl = route('production.destroy', $row->id);
                    $name      = e($row->production_number ?? 'Produksi');

                    $html  = '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi ' . $name . '">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                    
                    if (! $row->pos_id) {
                        $html .= '
                            <li>
                                <a class="dropdown-item text-primary d-flex justify-content-center" href="' . $editUrl . '" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </li>';
                        $html .= '
                            <li>
                                <a class="dropdown-item text-danger d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $row->id . ')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </li>';
                    } else {
                        $html .= '
                            <li>
                                <span class="dropdown-item text-muted d-flex justify-content-center" title="Terkunci (Terhubung ke POS)" style="cursor: not-allowed;">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                            </li>';
                    }
                    
                    $html .= '
                    </ul>
                </div>';
                    return $html;
                })
                ->rawColumns(['name', 'action', 'status', 'production_date'])
                // staff is eager-loaded only for the name label; keep it out of the JSON as before.
                ->removeColumn('staff')
                ->make(true);

        } catch (\Exception $e) {
            \Log::error('Production DataTable Error: ' . $e->getMessage());
            return response()->json([
                'draw'            => intval($request->input('draw', 1)),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => $e->getMessage(),
            ]);
        }
    }
}
