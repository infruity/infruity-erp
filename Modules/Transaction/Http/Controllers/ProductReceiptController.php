<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Transaction\Entities\ProductReceipt;
use Modules\Transaction\Entities\Receipt;
use Modules\Master\Entities\Product;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ProductReceiptController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        if ($denied = $this->requireAccess('product-receipt.index')) {
            return $denied;
        }

        $search = trim((string) $request->query('q', ''));
        $type = in_array($request->query('type'), ['product', 'kemasan'], true)
            ? $request->query('type')
            : 'all';
        $receipts = Receipt::with([
            'products.category',
            'products.unit',
            'recipeIngredients.ingredients.unit',
        ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($matches) use ($search) {
                    $matches->where('code', 'like', '%' . $search . '%')
                        ->orWhereHas('products', fn ($product) => $product->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->when($type !== 'all', fn ($query) => $query->whereHas('products', fn ($product) => $product->where('tipe', $type)))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        if ($request->boolean('partial')) {
            return response()->json([
                'html' => view('transaction::receipt.partials.items', compact('receipts'))->render(),
                'count' => $receipts->count(),
                'hasMore' => $receipts->hasMorePages(),
                'nextPageUrl' => $receipts->nextPageUrl(),
            ]);
        }

        return view('transaction::receipt.index', compact('receipts', 'search', 'type'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('product-receipt.create')) {
            return $denied;
        }

        return view('transaction::receipt.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('product-receipt.store')) {
            return $denied;
        }

        // dd($request->all());
        $messages = [
            'product_id.required' => 'ID produk wajib diisi.',
            'product_id.exists' => 'Produk yang dipilih tidak ditemukan.',
            'product_id.unique' => 'Produk ini sudah ditambahkan ke dalam resep sebelumnya.',
            'product_receipt_id.required' => 'ID tanda terima produk wajib diisi.',
            'product_receipt_id.array' => 'ID tanda terima produk harus berupa array.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'description.max' => 'Deskripsi tidak boleh lebih dari 255 karakter.',
        ];

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id|unique:receipt,product_id,NULL,id',
            'product_receipt_id' => 'required|array',
            'description' => 'nullable|string|max:255',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $receipt = new Receipt();
            $receipt->code = Receipt::getOrderNumber();
            $receipt->product_id = $request->product_id;
            $receipt->description = $request->description;
            $receipt->created_by = Auth::user()->id_user;
            $receipt->save();

            $receiptId = $receipt->id;
            foreach ($request->product_receipt_id as $key => $product) {
                $productReceipt = new ProductReceipt();
                $productReceipt->receipt_id = $receiptId;
                $productReceipt->product_id = $request->product_id;
                $productReceipt->product_receipt_id = $product;
                $productReceipt->quantity = $request->ingredients_quantity[$key];
                $productReceipt->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Produksi gagal' . $e->getMessage());
        }

        return redirect('receipt')->with('success', 'Produksi berhasil');
    }

    public function save_additional_ingredient(Request $request)
    {
        if ($denied = $this->requireAccess('receipt.save-ajax')) {
            return $denied;
        }

        // dd($request->all());
        try {
            DB::beginTransaction();
            $productReceipt = new ProductReceipt();
            $productReceipt->receipt_id = $request->receipt_id;
            $productReceipt->product_id = $request->id;
            $productReceipt->product_receipt_id = $request->product_id;
            $productReceipt->quantity = $request->qty;
            $productReceipt->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Product gagal disimpan.' . $e->getMessage(),
                'data' => $productReceipt
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Product berhasil disimpan.',
            'data' => $productReceipt
        ], 201);
    }

    public function delete_additional_ingredient(Request $request, $id)
    {
        if ($denied = $this->requireAccess('receipt.delete-ajax')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $deteilProduct = ProductReceipt::findOrFail($id);
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

    public function edit_additional_ingredient(Request $request, $id)
    {
        if ($denied = $this->requireAccess('receipt.edit-ajax')) {
            return $denied;
        }

        $data = ProductReceipt::with('ingredients')->findOrFail($id);
        return response()->json([
            'data' => $data
        ], 201);
    }

    public function update_additional_ingredient(Request $request, $id)
    {
        if ($denied = $this->requireAccess('receipt.edit-ajax')) {
            return $denied;
        }

        // dd($request->all());
        $validated = $request->validate([
            'qty' => 'required|numeric',
            'sell_price' => 'nullable',
        ]);

        try {
            DB::beginTransaction();
            $detailProduct = ProductReceipt::findOrFail($id);
            $detailProduct->quantity = $validated['qty'];
            $detailProduct->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Product updated failed']);
        }

        return response()->json(['message' => 'Product updated successfully']);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('transaction::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('product-receipt.edit')) {
            return $denied;
        }

        $data['data'] = Receipt::find($id);
        $data['production_detail'] = ProductReceipt::with('product', 'ingredients')->where('receipt_id', $id)->get();
        $data['selectedProduct'] = Product::find($data['data']->product_id);
        // dd($data);
        return view('transaction::receipt.create', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('product-receipt.update')) {
            return $denied;
        }

        // Custom validation messages in Bahasa Indonesia
        $messages = [
            'product_id.required' => 'ID produk wajib diisi.',
            'product_id.exists' => 'Produk yang dipilih tidak ditemukan.',
            'product_id.unique' => 'Produk ini sudah ditambahkan ke dalam tanda terima.',
            'product_receipt_id.required' => 'ID tanda terima produk wajib diisi.',
            'product_receipt_id.array' => 'ID tanda terima produk harus berupa array.',
            'description.string' => 'Deskripsi harus berupa teks.',
            'description.max' => 'Deskripsi tidak boleh lebih dari 255 karakter.',
        ];

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id|unique:receipt,product_id,' . $id . ',id',
            'product_receipt_id' => 'required|array',
            'description' => 'nullable|string|max:255',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $receipt = Receipt::findOrFail($id);
            $receipt->product_id = $request->product_id;
            $receipt->description = $request->description;
            $receipt->updated_by = Auth::user()->id_user;
            $receipt->save();

            $receiptId = $receipt->id;
            ProductReceipt::where('receipt_id', $receiptId)->delete();

            foreach ($request->product_receipt_id as $key => $product) {
                $productReceipt = new ProductReceipt();
                $productReceipt->receipt_id = $receiptId;
                $productReceipt->product_id = $request->product_id;
                $productReceipt->product_receipt_id = $product;
                $productReceipt->quantity = $request->ingredients_quantity[$key];
                $productReceipt->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Receipt gagal: ' . $e->getMessage());
        }

        return redirect('receipt')->with('success', 'Receipt berhasil');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('product-receipt.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $production = Receipt::findOrFail($id);
            $production->delete();
            ProductReceipt::where('receipt_id', $id)->delete();
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

    public function getReceipt(Request $request)
    {
        if ($denied = $this->requireAccess('products.get-receipt')) {
            return $denied;
        }

        $search = $request->input('search', '');
        $data = Receipt::with('products')
            ->when($search, function ($query) use ($search) {
                return $query->whereHas('products', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
            })
            ->get();
        return response()->json($data);
    }

    public function ProductReceipt(Request $request)
    {
        $data = ProductReceipt::with('product')->with('ingredients')->where('product_id', $request->product_id)
            ->select('*')
            ->get();
        return response()->json($data);
    }

    public function get_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('production.get-receipt')) {
            return $denied;
        }

        // dd($request->all());
        $data = ProductReceipt::with('product')->with('ingredients')->where('receipt_id', $id)->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $ingredientName = $item->ingredients?->name ?? 'Produk tidak ditemukan';
                $unitName = $item->ingredients?->unit?->abbreviation ?? '-';
                $price = $item->ingredients?->price ?? 0;

                $html = '';
                $html .= $ingredientName . '<br> Jumlah : ' . $item->quantity . ' ' . $unitName . '<br> Harga : ' . toNumber($price) . '';
                return $html;
            })
            ->addColumn('hpp', function ($item) {
                $hpp = $item->ingredients?->hpp ?? 0;

                return 'Rp' . toNumber($hpp * $item->quantity);
            })
            ->addColumn('harga_jual', function ($item) {
                $price = $item->ingredients?->price ?? 0;

                return toNumber($price * $item->quantity);
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

    /**
     * Get recipe data for production auto-load
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecipeData($id)
    {
        if ($denied = $this->requireAccess('production.get-recipe-data')) {
            return $denied;
        }

        try {
            // Find the receipt by receipt ID
            $receipt = Receipt::with('products')->find($id);
            
            if (!$receipt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipe not found',
                    'ingredients' => []
                ], 404);
            }

            // Get recipe ingredients with proper relationships
            $ingredients = ProductReceipt::with(['ingredients' => function($query) {
                $query->with('unit');
            }])
            ->where('receipt_id', $id)
            ->get()
            ->map(function($item) {
                // Ambil data dari product receipt dan product
                $product = $item->ingredients; // ingredients adalah relasi ke product
                $quantity = (float) $item->quantity; // qty dari detail receipt
                $hpp = (float) ($product->hpp ?? 0); // hpp dari product
                $total = $quantity * $hpp; // total = qty * hpp
                
                return [
                    'id' => $item->product_receipt_id,
                    'product_id' => $item->product_receipt_id,
                    'name' => $product->name ?? 'Unknown',
                    'quantity' => $quantity,
                    'hpp' => $hpp,
                    'unit' => $product->unit->abbreviation ?? 'pcs',
                    'total' => $total,
                    'product' => [
                        'id' => $item->product_receipt_id,
                        'name' => $product->name ?? 'Unknown',
                        'hpp' => $hpp,
                        'unit' => [
                            'abbreviation' => $product->unit->abbreviation ?? 'pcs'
                        ]
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'id' => $receipt->id,
                'product_id' => $receipt->product_id,
                'product_name' => $receipt->products?->name ?? 'Unknown',
                'yield_quantity' => 1, // Default yield quantity
                'ingredients' => $ingredients
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading recipe: ' . $e->getMessage(),
                'ingredients' => []
            ], 500);
        }
    }

    public function get_data(Request $request)
    {
        $data = Receipt::with('products')->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $productName = $item->products?->name ?? 'Produk tidak ditemukan';

                return '<div class="d-flex align-items-center">
                            <div class="ms-5">
                                <a href="' . route('receipt.edit', $item->id) . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">#' . $item->code . '</a>
                                <br>
                                <span class="text-muted fw-bold">' . e($productName) . '</span>
                            </div>
                        </div>';
            })
            ->addColumn('action', function ($item) {
                $html = '';
                $html .= '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">                        
                            <li>
                                <a class="dropdown-item" href="' . route('receipt.edit', $item->id) . '">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="' . route('receipt.edit', $item->id) . '">
                                    <i class="bi bi-pencil"></i>
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
            ->addColumn('receipt_id', function ($item) {
                return $item->id;
            })
            ->rawColumns(['name', 'action'])
            ->make(true);
    }
}
