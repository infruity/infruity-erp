<?php
namespace Modules\Master\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Product;
use Modules\Master\Entities\ProductBranch;
use Modules\Master\Entities\ProductCategory;
use Modules\Master\Entities\ProductChild;
use Modules\Master\Entities\ProductUnit;
use Modules\Master\Entities\UserBranch;
use Modules\Transaction\Entities\ProductHppRunning;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    use \App\Traits\HasAccessControl;

    private function productTypeOptions()
    {
        $categories = ProductCategory::orderBy('name')->get();
        $presets = collect([
            ['name' => 'Bahan Baku', 'tipe' => 'product', 'status' => 'no-receipt'],
            ['name' => 'Perlu Resep', 'tipe' => 'product', 'status' => 'receipt'],
            ['name' => 'Produk Olahan', 'tipe' => 'product', 'status' => 'no-receipt'],
            ['name' => 'Kemasan', 'tipe' => 'kemasan', 'status' => 'no-receipt'],
            ['name' => 'Jasa / Layanan', 'tipe' => 'product', 'status' => 'no-receipt'],
        ]);

        $defaultOptions = $presets->map(function ($preset) use ($categories) {
            $category = $categories->firstWhere('name', $preset['name']);
            $value = $category?->id ?? $preset['name'];

            return (object) array_merge($preset, [
                'value' => $value,
                'category_id' => $value,
            ]);
        });

        $customOptions = $categories
            ->reject(fn ($category) => $presets->contains('name', $category->name))
            ->map(fn ($category) => (object) [
                'name' => $category->name,
                'value' => $category->id,
                'category_id' => $category->id,
                'tipe' => 'product',
                'status' => 'no-receipt',
            ]);

        return $defaultOptions->concat($customOptions)->values();
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('products.index')) {
            return $denied;
        }

        $data['branch'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        $data['product_units'] = ProductUnit::all();
        $data['productCategories'] = ProductCategory::orderBy('name')->get();
        $data['productTypeOptions'] = $this->productTypeOptions();
        $data['tipe'] = ['product' => 'Product', 'kemasan' => 'Kemasan'];
        $data['data'] = null;

        $listableProducts = Product::whereDoesntHave('parentProduct')->where('tipe', '!=', 'parcel');

        $data['categories'] = ProductCategory::whereIn('id', (clone $listableProducts)->whereNotNull('category_id')->distinct()->pluck('category_id'))
            ->orderBy('name')
            ->get();

        $data['productTypes'] = $data['productTypeOptions']->map(fn ($option) => [
            'value' => (string) $option->category_id,
            'name' => $option->name,
        ])->values();

        $data['canCreateProduct'] = check_access('products.create');

        return view('master::products.index', $data);
    }

    public function get_stock()
    {
        if ($denied = $this->requireAccess('product-stock.index')) {
            return $denied;
        }

        $data['category'] = ProductCategory::all();
        $data['branch']   = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        return view('master::products.stock', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('products.create')) {
            return $denied;
        }

        $data['product_units'] = ProductUnit::all();
        $data['branch'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        $data['productCategories'] = ProductCategory::orderBy('name')->get();
        $data['productTypeOptions'] = $this->productTypeOptions();
        $data['tipe']          = ['product' => 'Product', 'kemasan' => 'Kemasan'];
        $data['data']          = null;
        return view('master::products.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('products.store')) {
            return $denied;
        }

        // dd($request->all(), $request->file('avatar'));
        $validator = Validator::make($request->all(), [
            'product_name'    => 'required|unique:products,name',
            'price'           => 'required',
            'product_unit_id' => 'required|exists:product_units,id',
            'status'          => 'required',
            // 'category_id' => 'required|exists:products_category,id',
            'avatar'          => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description'     => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $category = [];
            if (isset($request->category_id)) {
                if (is_numeric($request->category_id)) {
                    $category = ProductCategory::find($request->category_id);
                    if (! $category) {
                        return redirect()->back()
                            ->withErrors('Kategori tidak ditemukan')
                            ->withInput();
                    }
                } else {
                    $category       = new ProductCategory();
                    $category->name = $request->category_id;
                    $category->save();
                }
            }
            $branches              = Branch::all();
            $product               = new Product();
            $product->name         = $request->product_name;
            $product->category_id  = $category->id ?? null;
            $product->description  = strip_tags($request->description ?? '');
            $product->price        = $request->price ?? '';
            $product->product_unit = $request->product_unit_id ?? '';
            $product->stock        = $request->stock ?? 0;
            $product->limit        = $request->limit ?? 0;
            $product->handling     = $request->handling ?? '';
            $product->sku          = $request->sku ?? '';
            $product->barcode      = $request->barcode ?? '';
            $product->status       = $request->status ?? '';
            $product->tipe         = $request->tipe ?? 'product';
            $product->created_by   = Auth::user()->id_user;

            if ($request->hasFile('avatar')) {
                $path           = $request->file('avatar')->store('products', 'public');
                $product->image = $path;
                // $product->save();
            }

            $product->save();

            $productId = $product->id;
            if (isset($request->variant)) {
                foreach ($request->variant['id'] as $key => $value) {
                    if (is_numeric($value)) {
                        $variant             = Product::find($value);
                        $variant->is_variant = 1;
                        $variant->save();
                    } else {
                        if ($value == $product->name) {
                            return redirect()->back()
                                ->withErrors('Nama variant tidak boleh sama dengan nama produk utama')
                                ->withInput();
                        }
                        $variant               = new Product();
                        $variant->parent_id    = $productId;
                        $variant->name         = $value;
                        $variant->price        = $request->variant['price'][$key];
                        $variant->category_id  = $product->category_id;
                        $variant->product_unit = $product->product_unit;
                        $variant->stock        = $product->stock;
                        $variant->limit        = $product->limit;
                        $variant->handling     = $product->handling;
                        $variant->is_variant   = 1;
                        $variant->created_by   = Auth::user()->id_user;
                        $variant->description  = strip_tags($request->description ?? '');
                        $variant->save();

                        foreach ($branches as $key => $value) {
                            $branch             = new ProductBranch();
                            $branch->product_id = $variant->id;
                            $branch->branch_id  = $value->id;
                            $branch->price      = $variant->price;
                            $branch->save();
                        }
                    }

                    $child             = new ProductChild();
                    $child->product_id = $variant->id;
                    $child->parent_id  = $productId;
                    $child->save();
                }
            }

            if (isset($request->branch)) {
                foreach ($request->branch['id'] as $key => $value) {
                    if (is_numeric($value)) {
                        $branch = ProductBranch::where('product_id', $productId)
                            ->where('branch_id', $value)
                            ->first();
                        if ($branch) {
                            $branch->price = $request->branch['price'][$key] ?? 0;
                            $branch->save();
                        } else {
                            $branch             = new ProductBranch();
                            $branch->product_id = $productId;
                            $branch->branch_id  = $value;
                            $branch->price      = $request->branch['price'][$key] ?? 0;
                            $branch->save();
                        }
                    } else {
                        $branch             = new ProductBranch();
                        $branch->product_id = $productId;
                        $branch->branch_id  = $value;
                        $branch->price      = $request->branch['price'][$key] ?? 0;
                        $branch->save();
                    }
                }
            } else {
                foreach ($branches as $key => $value) {
                    $branch             = new ProductBranch();
                    $branch->product_id = $productId;
                    $branch->branch_id  = $value->id;
                    $branch->price      = $product->price;
                    $branch->save();
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Pembuatan Product gagal' . $e->getMessage());
        }

        return redirect('products')->with('success', 'Pembuatan Product berhasil');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if ($denied = $this->requireAccess('products.show')) {
            return $denied;
        }

        $product = Product::findOrFail($id);
        $data    = [
            'data'          => $product,
            'product_units' => ProductUnit::all(),
            'productTypeOptions' => $this->productTypeOptions(),
        ];
        // dd($data);
        return view('master::products.create', $data);
    }

    public function show_stock($id)
    {
        if ($denied = $this->requireAccess('product-stock.show')) {
            return $denied;
        }

        $data['data'] = Product::findOrFail($id);
        $data['branch'] = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        return view('master::products.stock-show', $data);
    }

    public function show_transaction($id)
    {
        $data['product'] = Product::findOrFail($id);
        $data['report']  = DB::table('report_total_belanja')->where('product_id', $id)->first();
        return view('master::products.show-transaction', $data);
    }

    public function get_data_transaction(Request $request)
    {
        $query = ProductHppRunning::where('product_id', $request->product_id)
            ->orderBy('created_at', 'asc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('type', function ($item) {
                $html = '';
                switch ($item->type) {
                    case '+':
                        $html .= '<span class="badge badge-light-success">' . $item->type . '</span>';
                        break;

                    case '-':
                        $html .= '<span class="badge badge-light-danger">' . $item->type . '</span>';
                        break;

                    default:
                        $html .= '<span class="badge badge-light-primary">' . $item->type . '</span>';
                        break;
                }

                return $html;
            })
            ->addColumn('remarks', function ($item) {
                return $item->remarks ?? '-';
            })
            ->addColumn('qty', function ($item) {
                return number_format($item->qty, 0, ',', '.');
            })
            ->addColumn('qty_berjalan_raw', function ($item) {
                return number_format($item->qty_berjalan_raw, 0, ',', '.');
            })
            ->addColumn('qty_berjalan', function ($item) {
                return number_format($item->qty_berjalan, 0, ',', '.');
            })
            ->addColumn('harga_satuan', function ($item) {
                return $item->harga_satuan ? 'Rp ' . number_format($item->harga_satuan, 0, ',', '.') : '-';
            })
            ->addColumn('total_belanja', function ($item) {
                return 'Rp ' . number_format($item->total_belanja, 0, ',', '.');
            })
            ->addColumn('total_non_belanja', function ($item) {
                $value = $item->total_non_belanja;
                if ($value < 0) {
                    return '<span class="text-danger">Rp ' . number_format($value, 0, ',', '.') . '</span>';
                }
                return 'Rp ' . number_format($value, 0, ',', '.');
            })
            ->addColumn('hpp_berjalan', function ($item) {
                return 'Rp ' . number_format($item->hpp_berjalan, 0, ',', '.');
            })
            ->addColumn('total_aset_berjalan', function ($item) {
                return 'Rp ' . number_format($item->total_aset_berjalan, 0, ',', '.');
            })
            ->addColumn('qty_x_hpp', function ($item) {
                return 'Rp ' . number_format($item->qty_x_hpp, 0, ',', '.');
            })
            ->editColumn('cogs', function ($item) {
                return 'Rp ' . number_format($item->cogs, 0, ',', '.');
            })
            ->editColumn('recovered_cogs', function ($item) {
                return 'Rp ' . number_format($item->recovered_cogs, 0, ',', '.');
            })
            ->addColumn('created_at', function ($item) {
                return \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i:s');
            })
            ->rawColumns(['type', 'total_non_belanja'])
            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('products.edit')) {
            return $denied;
        }

        $product = Product::findOrFail($id);
        $data    = [
            'data'          => $product,
            'product_units' => ProductUnit::all(),
        ];
        $data['tipe'] = ['product' => 'Product', 'kemasan' => 'Kemasan'];
        if (isset($product->category_id)) {
            $data['category'] = ProductCategory::findOrFail($product->category_id);
        } else {
            $data['category'] = null;
        }
        $data['variant']        = ProductChild::with('product')->where('parent_id', $id)->get();
        $data['product_branch'] = ProductBranch::with('product', 'branch')->where('product_id', $id)->get();
        return view('master::products.create', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('products.update')) {
            return $denied;
        }

        // dd($request->all(), $request->file('avatar'));
        $validator = Validator::make($request->all(), [
            'product_name'    => 'required|unique:products,name,' . $id,
            'price'           => 'required',
            'product_unit_id' => 'required|exists:product_units,id',
            'status'          => 'required',
            // 'category_id'     => 'nullable|exists:products_category,id',
            'avatar'          => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description'     => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $category = [];
            if (isset($request->category_id)) {
                if (is_numeric($request->category_id)) {
                    $category = ProductCategory::find($request->category_id);
                    if (! $category) {
                        return redirect()->back()
                            ->withErrors('Kategori tidak ditemukan')
                            ->withInput();
                    }
                } else {
                    $category       = new ProductCategory();
                    $category->name = $request->category_id;
                    $category->save();
                }
            }

            $product = Product::findOrFail($id);
            if ($request->hasFile('avatar')) {
                // Hapus gambar lama jika ada
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $path           = $request->file('avatar')->store('products', 'public');
                $product->image = $path;
            }
            $product->name         = $request->product_name;
            $product->category_id  = $category->id ?? null;
            $product->description  = strip_tags($request->description ?? '');
            $product->price        = $request->price ?? '';
            $product->product_unit = $request->product_unit_id ?? '';
            $product->stock        = $request->stock ?? 0;
            $product->limit        = $request->limit ?? 0;
            $product->handling     = $request->handling ?? '';
            $product->sku          = $request->sku ?? '';
            $product->barcode      = $request->barcode ?? '';
            $product->status       = $request->status ?? '';
            $product->tipe         = $request->tipe ?? 'product';
            $product->created_by   = Auth::user()->id_user;

            if ($request->hasFile('avatar')) {
                $path           = $request->file('avatar')->store('products', 'public');
                $product->image = $path;
                $product->save();
            }

            // Hapus semua varian yang ada
            ProductChild::where('parent_id', $id)->delete();
            $productId = $id;
            // Simpan varian baru
            if (isset($request->variant)) {
                foreach ($request->variant['id'] as $key => $value) {
                    if (is_numeric($value)) {
                        $variant             = Product::find($value);
                        $variant->is_variant = 1;
                        $variant->price      = $request->variant['price'][$key] ?? 0;
                        $variant->save();
                    } else {
                        $variant               = new Product();
                        $variant->parent_id    = $productId;
                        $variant->name         = $value;
                        $variant->price        = $request->variant['price'][$key] ?? 0;
                        $variant->category_id  = $product->category_id;
                        $variant->product_unit = $product->product_unit;
                        $variant->stock        = $product->stock;
                        $variant->limit        = $product->limit;
                        $variant->handling     = $product->handling;
                        $variant->is_variant   = 1;
                        $variant->created_by   = Auth::user()->id_user;
                        $variant->description  = strip_tags($request->description ?? '');
                        $variant->save();
                        $variantId = $variant->id;

                        $listBranch = Branch::all();
                        foreach ($listBranch as $key => $item) {
                            $branch             = new ProductBranch();
                            $branch->product_id = $variantId;
                            $branch->branch_id  = $item->id;
                            $branch->price      = $request->variant['price'][$key] ?? 0;
                            $branch->save();
                        }

                    }

                    $child             = new ProductChild();
                    $child->product_id = $variant->id;
                    $child->parent_id  = $productId;
                    $child->save();
                }
            }

            if (isset($request->branch)) {
                // Hapus semua branch yang ada
                ProductBranch::where('product_id', $id)->delete();
                foreach ($request->branch['id'] as $key => $value) {
                    if (is_numeric($value)) {
                        $branch = ProductBranch::where('product_id', $id)
                            ->where('branch_id', $value)
                            ->first();
                        if ($branch) {
                            $branch->price = $request->branch['price'][$key] ?? 0;
                            $branch->save();
                        } else {
                            $branch             = new ProductBranch();
                            $branch->product_id = $productId;
                            $branch->branch_id  = $value;
                            $branch->price      = $request->branch['price'][$key] ?? 0;
                            $branch->save();
                        }
                    } else {
                        $branch             = new ProductBranch();
                        $branch->product_id = $productId;
                        $branch->branch_id  = $value;
                        $branch->price      = $request->branch['price'][$key] ?? 0;
                        $branch->save();
                    }
                }
            }

            $product->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Update Product gagal' . $e->getMessage());
        }

        return redirect('products')->with('success', 'Update Product berhasil');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('products.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $product = Product::findOrFail($id);
            $child   = Product::where('parent_id', $id)->get();
            if ($child->count() > 0) {
                foreach ($child as $item) {
                    $item->delete();
                }
            }
            $product->delete();
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

    public function updatePrice(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'price'     => 'required|numeric',
            'branch_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            if ($request->has('branch_id') && $request->branch_id != 0) {
                $productBranch = ProductBranch::where('product_id', $id)->where('branch_id', $request->branch_id)->first();
                if ($productBranch) {
                    $productBranch->price = preg_replace('/[^0-9]/', '', $request->price);
                    $productBranch->save();
                }
            } else {
                $product        = Product::findOrFail($id);
                $product->price = preg_replace('/[^0-9]/', '', $request->price);
                $product->save();
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Harga berhasil diperbarui.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui harga: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getVariant(Request $request)
    {
        $productId = $request->product_id;

        $variants = Product::where('parent_id', $productId);

        return DataTables::of($variants)
            ->addColumn('action', function ($row) {
                return '
                <button class="btn btn-sm btn-light-primary edit-variant variant" type="button"
                        data-id="' . $row->id . '"
                        data-name="' . $row->name . '"
                        data-price="' . $row->price . '">
                    Edit
                </button>
                <button class="btn btn-sm btn-light-danger delete-variant variant" type="button"
                        data-id="' . $row->id . '">
                    Hapus
                </button>
            ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function storeVariant(Request $request)
    {
        if ($denied = $this->requireAccess('products.store-variant')) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'parent_id'    => 'required|exists:products,id',
            'product_name' => 'required|string|max:255|unique:products,name',
            'price'        => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $parentProduct = Product::findOrFail($request->parent_id);
            DB::beginTransaction();
            $product               = new Product();
            $product->parent_id    = $request->parent_id;
            $product->name         = $request->product_name;
            $product->price        = $request->price;
            $product->category_id  = $parentProduct->category_id;
            $product->product_unit = $parentProduct->product_unit;
            $product->stock        = $parentProduct->stock;
            $product->limit        = $parentProduct->limit;
            $product->handling     = $parentProduct->handling;
            $product->created_by   = Auth::user()->id_user;
            $product->description  = $parentProduct->description;
            $product->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Variant berhasil disimpan.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan variant: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateVariant(Request $request, $id)
    {
        if ($denied = $this->requireAccess('products.update-variant')) {
            return $denied;
        }

        try {
            $validator = Validator::make($request->all(), [
                'product_name' => 'required|string|max:255',
                'price'        => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            DB::beginTransaction();
            $variant        = Product::findOrFail($id);
            $variant->name  = $request->product_name;
            $variant->price = $request->price;
            $variant->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Gagal memperbarui variant: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Updated']);
    }

    public function destroyVariant($id)
    {
        if ($denied = $this->requireAccess('products.destroy-variant')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $variant = Product::findOrFail($id);
            $variant->delete();
            DB::commit();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus variant: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Deleted']);
    }

    public function getProductReceipt(Request $request)
    {
        if ($denied = $this->requireAccess('products.get-product-receipt')) {
            return $denied;
        }

        $search = $request->input('search', '');
        $query  = Product::where('status', 'receipt')
            ->where('name', 'like', '%' . $search . '%')
            ->where('status', 'receipt')
            ->select('id', 'name')
            ->get();

        return response()->json($query);
    }

    public function getProduct(Request $request)
    {
        $search = $request->input('search', '');
        $query  = Product::with(['unit', 'category'])
            ->select('id', 'name', 'sku as code', 'hpp', 'price', 'stock', 'product_unit', 'category_id')
            ->where('name', 'like', '%' . $search . '%')
            ->where('tipe', '!=', 'parcel') // Exclude parcel type
            ->get()
            ->map(function ($item) {
                // Use hpp if available, otherwise use price
                $item->hpp = $item->hpp ?: 0;
                return $item;
            });

        return response()->json($query);
    }

    public function listProduct(Request $request)
    {
        $search = $request->input('term', '');
        $with   = ['category', 'unit', 'productReceipt', 'productReceipt.ingredients', 'parentProduct'];

        if ($request->has('branch') && ! empty($request->branch)) {
            $with['get_stock'] = function ($q) use ($request) {
                $q->where('branch_id', $request->branch);
            };
            $with['productReceipt.ingredients.get_stock'] = function ($q) use ($request) {
                $q->where('branch_id', $request->branch);
            };
        } else {
            $with[] = 'get_stock';
            $with[] = 'productReceipt.ingredients.get_stock';
        }

        $query  = Product::with($with)
            ->where('tipe', '!=', 'parcel')->where('name', 'like', '%' . $search . '%');

        if ($request->has('type') && ! empty($request->type)) {
            $query = $query->where('tipe', $request->type);
        }

        if ($request->has('branch') && ! empty($request->branch)) {
            $query = $query->join('product_branch', 'products.id', '=', 'product_branch.product_id')
                ->where('product_branch.branch_id', $request->branch)
                ->select('products.*', 'product_branch.price as price');
            
            if ($request->has('has_stock') && $request->has_stock == 1) {
                $query = $query->whereExists(function ($q) use ($request) {
                    $q->select(DB::raw(1))
                        ->from('product_stock')
                        ->whereColumn('product_stock.id', 'products.id')
                        ->where('product_stock.branch_id', $request->branch)
                        ->where('product_stock.stock_available', '>', 0);
                });
            }
        }

        if ($request->has('jenis')) {
            $query = $query->where('status', $request->jenis);
        }

        if ($request->has('variant')) {
            if ($request->variant == "0") {
                $query = $query->whereNull('is_variant');
            } else {
                $query = $query->where('is_variant', 1);
            }
        }

        $query = $query->get();

        $query->map(function ($product) {
            $lastWholesale = \Modules\Transaction\Entities\WholesaleProduct::where('product_id', $product->id)
                ->orderBy('created_at', 'desc')
                ->first();
            $product->last_price = $lastWholesale ? (float) $lastWholesale->price : null;
            
            // Ambil last_sell_price dari ProductBranch berdasarkan branch_id di transaksi pengadaan terakhir
            if ($lastWholesale) {
                $lastWholesaleFull = \Modules\Transaction\Entities\WholesaleProduct::with('wholesale')
                    ->where('product_id', $product->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $branchId = $lastWholesaleFull?->wholesale?->branch_id;
                if ($branchId) {
                    $productBranch = \Modules\Master\Entities\ProductBranch::where('product_id', $product->id)
                        ->where('branch_id', $branchId)
                        ->first();
                    $product->last_sell_price = $productBranch ? (float) $productBranch->price : null;
                } else {
                    $product->last_sell_price = null;
                }
            } else {
                $product->last_sell_price = null;
            }
            
            return $product;
        });

        return response()->json($query);
    }

    public function generateBranchPrice(Request $request)
    {
        if ($denied = $this->requireAccess('products.generate-branch-price')) {
            return $denied;
        }

        try {
            DB::beginTransaction();

            $products  = Product::whereNotIn('tipe', ['parcel'])->get();
            $branchIds = Branch::pluck('id');

            // Ambil semua kombinasi product-branch yang sudah ada
            $existingCombinations = ProductBranch::select('product_id', 'branch_id')->get()
                ->map(fn($item) => $item->product_id . '_' . $item->branch_id)
                ->toArray();

            $productBranch = [];

            foreach ($products as $product) {
                foreach ($branchIds as $branchId) {
                    // Cek jika kombinasi product-branch sudah ada, skip jika sudah ada
                    $combinationKey = $product->id . '_' . $branchId;
                    if (in_array($combinationKey, $existingCombinations)) {
                        continue;
                    }

                    $productBranch[] = [
                        'product_id' => $product->id,
                        'branch_id'  => $branchId,
                        'price'      => $product->price,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Insert hanya data yang belum ada
            if (!empty($productBranch)) {
                ProductBranch::insert($productBranch);
            }

            DB::commit();

            $count = count($productBranch);
            return response()->json(['message' => "Berhasil menggenerate harga cabang. $count data ditambahkan."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menggenerate harga cabang: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get data for DataTables
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function get_data(Request $request)
    {
        $branchFilter = $request->input('branch_filter');
        $priceOrderExpression = 'products.price';

        $childProductRelations = ['category', 'childProducts.product'];
        if ($branchFilter && $branchFilter != 0) {
            $childProductRelations['childProducts.product.productBranches'] = fn($branches) => $branches->where('branch_id', $branchFilter);
        }

        $query = Product::query()
            ->select('products.*', 'product_units.abbreviation as unit_abbreviation')
            ->with($childProductRelations)
            ->whereDoesntHave('parentProduct')
            ->leftJoin('product_units', 'products.product_unit', '=', 'product_units.id')
            ->where('tipe', '!=', 'parcel');

        if ($request->filled('branch_filter') && $request->branch_filter != 0) {
            $query = $query->leftJoin('product_branch', function ($join) use ($branchFilter) {
                $join->on('products.id', '=', 'product_branch.product_id')
                    ->where('product_branch.branch_id', '=', $branchFilter);
            })->addSelect(DB::raw('COALESCE(product_branch.price, products.price) as display_price'));

            $priceOrderExpression = 'COALESCE(product_branch.price, products.price)';
        } else {
            $query->addSelect(DB::raw('products.price as display_price'));
        }

        if ($request->filled('category_filter') && $request->category_filter !== 'all') {
            $query->where('products.category_id', $request->category_filter);
        }

        if ($request->filled('type_filter') && $request->type_filter !== 'all') {
            $selectedTypes = is_array($request->type_filter)
                ? array_map('strval', $request->type_filter)
                : [(string) $request->type_filter];

            if (collect($selectedTypes)->every(fn ($type) => in_array($type, ['product', 'kemasan', 'parcel'], true))) {
                $query->whereIn('products.tipe', $selectedTypes);
            } else {
                $options = $this->productTypeOptions()->keyBy(fn ($option) => (string) $option->category_id);
                $selectedOptions = collect($selectedTypes)
                    ->map(fn ($value) => $options->get($value))
                    ->filter();

                if ($selectedOptions->isNotEmpty()) {
                    $query->where(function ($typeQuery) use ($selectedOptions) {
                        foreach ($selectedOptions as $option) {
                            $typeQuery->orWhere(function ($optionQuery) use ($option) {
                                if (is_numeric($option->category_id)) {
                                    $optionQuery->where('products.category_id', $option->category_id);
                                } else {
                                    $optionQuery->whereRaw('1 = 0');
                                }

                                if ($option->name === 'Perlu Resep') {
                                    $optionQuery->orWhere('products.status', 'receipt');
                                } elseif ($option->name === 'Kemasan') {
                                    $optionQuery->orWhere('products.tipe', 'kemasan');
                                }
                            });
                        }
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        $data = $query;
        return DataTables::of($data)
            ->filter(function ($q) use ($request) {
                $search = trim((string) $request->input('search.value', ''));
                if ($search !== '') {
                    $terms = preg_split('/\\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($terms as $term) {
                        $pattern = '%' . $term . '%';
                        $q->where(function ($sub) use ($pattern) {
                            $sub->where('products.name', 'LIKE', $pattern)
                                ->orWhereHas('category', fn($category) => $category->where('name', 'LIKE', $pattern))
                                ->orWhereHas('childProducts.product', fn($variant) => $variant->where('name', 'LIKE', $pattern));
                        });
                    }
                }
            })
            ->order(function ($query) use ($request, $priceOrderExpression) {
                $order = $request->input('order', []);

                if (empty($order)) {
                    $query->reorder()
                        ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM product_child WHERE product_child.parent_id = products.id) THEN 0 ELSE 1 END ASC')
                        ->orderBy('products.id', 'asc')
                        ->whereNull('products.parent_id');
                    return;
                }

                $columnIndex = (int) ($order[0]['column'] ?? 0);
                $direction = strtolower($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

                $query->reorder();

                if ($columnIndex === 0) {
                    $query->orderBy('products.name', $direction);
                    return;
                }

                if ($columnIndex === 1) {
                    $query->orderByRaw($priceOrderExpression . ' ' . $direction);
                    return;
                }

                if ($columnIndex === 2) {
                    $query->orderBy('product_units.abbreviation', $direction);
                    return;
                }

                $query->orderBy('products.id', 'desc');
            })
            ->addIndexColumn()
            ->editColumn('name', function ($product) use ($branchFilter) {
                if (isset($product->image)) {
                    $url = asset('storage/' . $product->image);
                    $avatar = '<div class="w-9 h-9 md:w-12 md:h-12 rounded-xl overflow-hidden shrink-0 shadow-sm border border-gray-100 flex items-center justify-center text-lg md:text-xl bg-gray-100">
                        <img src="' . $url . '" alt="Product Image" class="w-full h-full object-cover">
                    </div>';
                } else {
                    [$emoji, $bgClass] = self::resolveProductIcon($product);
                    $avatar = '<div class="w-9 h-9 md:w-12 md:h-12 rounded-xl overflow-hidden shrink-0 shadow-sm border border-gray-100 flex items-center justify-center text-lg md:text-xl ' . $bgClass . '">
                        ' . $emoji . '
                    </div>';
                }

                // Check if this product is a child (has parent_id) or has children
                $hasChildren = $product->childProducts && $product->childProducts->count() > 0;

                $categoryName = trim((string) ($product->category?->name ?? ''));
                if ($categoryName === '') {
                    $categoryName = match (true) {
                        $product->tipe === 'kemasan' => 'Kemasan',
                        $product->status === 'receipt' => 'Perlu Resep',
                        default => 'Bahan Baku',
                    };
                }
                $subtitle = '<div class="mt-0.5 truncate text-[11px] text-gray-400 md:text-xs">' . e($categoryName) . '</div>';

                $mobilePrice = (float) ($product->display_price ?? $product->price ?? 0);
                $mobilePriceLabel = 'Rp ' . number_format($mobilePrice, 0, ',', '.');
                if ($hasChildren) {
                    $childPrices = $product->childProducts
                        ->map(fn($child) => (float) ($child->product?->productBranches?->first()?->price ?? $child->product?->price ?? 0));
                    $minimum = $childPrices->min() ?? 0;
                    $maximum = $childPrices->max() ?? 0;
                    $mobilePriceLabel = 'Rp ' . number_format($minimum, 0, ',', '.');
                    if ($minimum !== $maximum) {
                        $mobilePriceLabel .= ' – ' . number_format($maximum, 0, ',', '.');
                    }
                }

                $parentRow = '<div class="flex items-center gap-3 md:gap-4 px-5 py-3.5 min-h-[64px] md:min-h-[76px]">' . $avatar . '
                    <div class="flex-1 min-w-0">
                        <a href="' . url('products') . '/' . $product->id . '/show" class="block truncate text-[15px] md:text-base font-semibold text-gray-900 hover:text-emerald-700" data-kt-ecommerce-product-filter="product_name">' . e($product->name) . '</a>' . $subtitle . '
                    </div>
                    <span class="md:hidden shrink-0 whitespace-nowrap text-[13px] font-semibold text-emerald-800">' . $mobilePriceLabel . '</span>
                </div>';

                if (!$hasChildren) {
                    return $parentRow;
                }

                $childrenHtml = '<div class="border-t border-emerald-100/60 bg-emerald-50/20">
                    <div class="relative ml-[34px] md:ml-[44px]">
                        <span aria-hidden="true" class="absolute left-0 top-0 bottom-0 w-[2px] bg-emerald-100"></span>';
                foreach ($product->childProducts as $child) {
                    $childName = $child->product->name ?? '';
                    $childPrice = (float) ($child->product?->productBranches?->first()?->price ?? $child->product?->price ?? 0);
                    $unit = mb_strtolower($product->unit_abbreviation ?? 'kg');
                    $childrenHtml .= '<div class="relative min-h-[40px] md:min-h-[44px] flex items-center pr-3 text-[13px] text-gray-500 font-medium">
                        <span aria-hidden="true" class="absolute left-0 top-1/2 -translate-y-1/2 w-[26px] md:w-8 h-px bg-emerald-100"></span>
                        <span class="flex-1 min-w-0 truncate pl-[34px] md:pl-10 text-[14px] md:text-[13px]">' . e($childName) . '</span>
                        <span class="md:hidden shrink-0 whitespace-nowrap text-[13px] font-semibold text-gray-700">Rp ' . number_format($childPrice, 0, ',', '.') . '<span class="font-normal text-gray-400">/' . e($unit) . '</span></span>
                    </div>';
                }
                $childrenHtml .= '</div></div>';

                return '<div class="border-b border-emerald-50">' . $parentRow . $childrenHtml . '</div>';
            })
            ->editColumn('price', function ($product) use ($branchFilter) {
                $hasChildren = $product->childProducts && $product->childProducts->count() > 0;

                if ($hasChildren) {
                    $childPrices = $product->childProducts
                        ->map(fn($child) => (float) ($child->product?->productBranches?->first()?->price ?? $child->product?->price ?? 0));
                    $min = $childPrices->min() ?? 0;
                    $max = $childPrices->max() ?? 0;
                    $range = '<div class="flex min-h-[64px] md:min-h-[76px] items-center justify-end px-1 pr-5 md:pr-6 text-sm font-semibold text-emerald-800 whitespace-nowrap">Rp ' . number_format($min, 0, ',', '.') . ' – ' . number_format($max, 0, ',', '.') . '</div>';

                    $variantPrices = '<div class="border-t border-emerald-100/60 bg-emerald-50/20 pr-5 md:pr-6">';
                    foreach ($product->childProducts as $child) {
                        $childPrice = (float) ($child->product?->productBranches?->first()?->price ?? $child->product?->price ?? 0);
                        $unit = mb_strtolower($product->unit_abbreviation ?? 'kg');
                        $variantPrices .= '<div class="min-h-[40px] md:min-h-[44px] flex items-center justify-end px-2 text-[13px] font-semibold text-gray-700 whitespace-nowrap">Rp ' . number_format($childPrice, 0, ',', '.') . '<span class="text-xs text-gray-400 font-normal">/' . e($unit) . '</span></div>';
                    }
                    $variantPrices .= '</div>';

                    return '<div>' . $range . $variantPrices . '</div>';
                }

                $price = (float) ($product->display_price ?? $product->price ?? 0);

                return '<div class="min-h-[72px] flex items-center justify-end px-5"><span class="editable-price text-sm font-semibold text-emerald-800 whitespace-nowrap cursor-pointer" data-id="' . $product->id . '" data-value="' . $price . '">Rp ' . number_format($price, 0, ',', '.') . '</span></div>';
            })
            ->addColumn('category', function ($product) {
                return $product->category->name ?? '-';
            })
            ->editColumn('unit_abbreviation', function ($product) {
                return $product->unit_abbreviation ?? '-';
            })
            ->addColumn('status', function ($product) {
                $html = '';
                if ($product->status == 'receipt') {
                    $html .= '<span class="badge badge-light-success">Menggunakan Resep</span>';
                } elseif ($product->status == 'no-receipt') {
                    $html .= '<span class="badge badge-light-info">Tanpa Resep</span>';
                } else {
                    $html .= '<span class="badge badge-light-warning">Unknown</span>';
                }
                return $html;
            })
        // ->addColumn('action', function ($product) {
        //     return '
        //         <div class="dropdown text-end">
        //             <button class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary dropdown-toggle"
        //                 type="button"
        //                 id="dropdownMenuButton' . $product->id . '"
        //                 data-bs-toggle="dropdown"
        //                 aria-expanded="false">
        //                 <i class="ki-outline ki-gear fs-5 ms-1"></i>
        //             </button>

        //             <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton' . $product->id . '">
        //                 <li>
        //                     <a class="dropdown-item" href="' . route('products.edit', $product->id) . '">
        //                         Edit
        //                     </a>
        //                 </li>
        //                 <li>
        //                     <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteProduct(' . $product->id . ')">
        //                         Delete
        //                     </a>
        //                 </li>
        //             </ul>
        //         </div>
        //     ';
        // })
            ->addColumn('action', function ($row) {
                $editUrl = route('products.edit', $row->id);
                $name    = e($row->name);

                $html = '<div class="flex items-center justify-end gap-1" title="Aksi ' . $name . '">';
                if (check_access('products.edit')) {
                    $html .= '
                        <a href="' . $editUrl . '" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Edit">
                            <i class="ph ph-pencil-simple text-base"></i>
                        </a>';
                }
                if (check_access('products.destroy')) {
                    $html .= '
                        <button type="button" onclick="deleteProduct(' . $row->id . ')" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Hapus">
                            <i class="ph ph-trash text-base"></i>
                        </button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['name', 'action', 'price', 'status'])
            ->make(true);
    }

    /**
     * Map a product/category name to a presentational emoji + Tailwind bg class.
     * @return array{0: string, 1: string}
     */
    private static function resolveProductIcon($product): array
    {
        $needle = mb_strtolower(($product->name ?? '') . ' ' . ($product->category->name ?? ''));

        $map = [
            'stroberi' => ['🍓', 'bg-pink-100'],
            'strawberry' => ['🍓', 'bg-pink-100'],
            'mangga' => ['🥭', 'bg-amber-50'],
            'apel' => ['🍎', 'bg-red-50'],
            'pisang' => ['🍌', 'bg-yellow-50'],
            'jeruk' => ['🍊', 'bg-orange-50'],
            'anggur' => ['🍇', 'bg-purple-50'],
            'nanas' => ['🍍', 'bg-yellow-50'],
            'semangka' => ['🍉', 'bg-red-50'],
            'melon' => ['🍈', 'bg-lime-50'],
            'alpukat' => ['🥑', 'bg-green-50'],
            'nangka' => ['🟡', 'bg-yellow-50'],
            'durian' => ['🟢', 'bg-green-50'],
            'sirsak' => ['🟢', 'bg-green-50'],
            'kelapa' => ['🥥', 'bg-stone-50'],
        ];

        foreach ($map as $keyword => $icon) {
            if (str_contains($needle, $keyword)) {
                return $icon;
            }
        }

        return ['📦', 'bg-gray-100'];
    }

    /**
     * Get child products (variants) for a parent product
     */
    public function get_child_data(Request $request)
    {
        $parentId = $request->parent_id;

        if (!$parentId) {
            return response()->json(['data' => []]);
        }

        // Get children from product_child relationship
        $children = ProductChild::with(['product.unit'])
            ->where('parent_id', $parentId)
            ->get();

        $data = [];
        foreach ($children as $child) {
            if ($child->product) {
                $data[] = [
                    'id' => $child->product->id,
                    'name' => $child->product->name,
                    'price' => $child->product->price,
                    'unit' => $child->product->unit
                ];
            }
        }

        return response()->json(['data' => $data]);
    }

    public function get_data_stock(Request $request)
    {
        $branch = $request->branch;

        // Handle sorting from DataTables
        $orderColumnIndex = 2; // default stock_available
        $orderDirection = 'desc';
        if ($request->has('order') && !empty($request->order)) {
            $orderColumnIndex = $request->order[0]['column'];
            $orderDirection = $request->order[0]['dir'];
        }

        $columns = ['name', 'hpp', 'stock_available', 'category_id'];
        $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'stock_available';

        if ($branch && $branch != 'all') {
            // Single branch query
            $query = DB::table('product_stock')
                ->where('branch_id', $branch)
                ->whereNull('is_variant')
                ->where('tipe', '!=', 'parcel')
                ->orderBy($orderColumn, $orderDirection);
        } else {
            // All branches with aggregation
            $query = DB::table('product_stock')
                ->where('tipe', '!=', 'parcel')
                ->whereNull('is_variant')
                ->select(
                    'id',
                    'name',
                    'sku',
                    'limit',
                    'hpp',
                    'price',
                    'tipe',
                    'category_id',
                    'product_unit',
                    'unit',
                    DB::raw('SUM(parent_stock) as parent_stock'),
                    DB::raw('SUM(child_consumed) as child_consumed'),
                    DB::raw('SUM(stock_available) as stock_available'),
                    DB::raw('AVG(avg_hpp) as avg_hpp'),
                    DB::raw('GROUP_CONCAT(DISTINCT child) as child'),
                    'stock_status'
                )
                ->groupBy(
                    'id',
                    'name',
                    'sku',
                    'limit',
                    'hpp',
                    'price',
                    'tipe',
                    'category_id',
                    'product_unit',
                    'unit'
                )
                ->orderBy($orderColumn, $orderDirection);
        }

        if ($request->has('stock_filter')) {
            if ($request->stock_filter === 'ada') {
                $query->where('stock_available', '>', 0);
            } elseif ($request->stock_filter === 'kosong') {
                $query->where('stock_available', '=', 0);
            }
        }

        if ($request->filled('stock_kategori') && $request->stock_kategori !== 'all') {
            $query->where('category_id', $request->stock_kategori);
        }

        $data = $query;
        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value'));

                if ($search === '') {
                    return;
                }

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            }, true)
            ->addIndexColumn()
            ->addColumn('name', function ($product) {
                $html = '
                    <div class="d-flex align-items-center">';

                // kalau mau pakai image, bisa aktifkan lagi
                // if ($product->image) {
                //     $url = asset('storage/' . $product->image);
                //     $html .= '<img src="' . $url . '" alt="Product Image" width="50">';
                // } else {
                //     $html .= '<a href="javascript:void(0)" class="symbol symbol-50px">
                //                 <span class="symbol-label" style="background-image:url(assets/media/svg/files/blank-image.svg);"></span>
                //             </a>';
                // }

                $html .= '<div class="ms-5">
                <a href="' . url('products/' . $product->id . '/show') . '"
                   class="text-gray-800 text-hover-primary fs-5 fw-bold"
                   data-kt-ecommerce-product-filter="product_name">'
                . e($product->name) . '
                </a>
                <br>';

                // if (!empty($product->child)) {
                //     $childs = explode(',', $product->child);
                //     $html .= '<span>' . implode('<br>', array_map('trim', $childs)) . '</span>';
                // }

                $html .= '</div></div>';

                return $html;
            })
            ->addColumn('price', function ($product) {
                return '<span class="badge badge-light-primary editable-price" data-id="' . $product->id . '" data-value="' . toNumber($product->price) . '">Rp.' . $product->price . '</span>';
            })
            ->addColumn('hpp', function ($product) {
                return '<span class="badge badge-light-primary" data-id="' . $product->id . '" data-value="' . $product->hpp . '">Rp' . tonumberround($product->hpp) . '</span>';
            })
            ->addColumn('stock_available', function ($product) {
                $stockValue = (float) $product->stock_available;

                if ($stockValue < 0) {
                    $badgeClass = 'danger';
                } elseif ($stockValue == 0.0) {
                    $badgeClass = 'secondary';
                } elseif ($stockValue <= (float) $product->limit) {
                    $badgeClass = 'warning';
                } else {
                    $badgeClass = 'success';
                }

                return '<span class="badge badge-light-' . $badgeClass . '">' . $product->stock_available . ' ' . $product->unit . '</span>';
            })
            ->addColumn('category', function ($item) {
                return $item->category_id;
            })
            ->addColumn('action', function ($item) {
                return '
                    <a href="' . url('product-stock') . '/' . $item->id . '/show' . '" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="tooltip" title="View">
                        <i class="fa fa-eye"></i>
                    </a>
                    <a href="' . url('product-transaction') . '/' . $item->id . '/show' . '" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="tooltip" title="View">
                        <i class="fa fa-print"></i>
                    </a>
                ';
            })
            ->rawColumns(['name', 'action', 'price', 'stock_available', 'hpp', 'category'])
            ->make(true);
    }

    public function get_data_available(Request $request)
    {
        if ($request->has('branch_id') && ! $request->filled('branch_id')) {
            return collect();
        }

        $query = DB::table('product_stock')
            ->whereNull('is_variant')
            // ->where('stock_available', '>', 0)
            ->where('name', 'like', '%' . $request->search . '%')
            ->select('id', 'name', 'stock_available', 'avg_hpp as hpp');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return $query->limit(20)->get();
    }

    public function get_data_stock_show(Request $request)
    {
        $productId = is_array($request->product_id) ? ($request->product_id[0] ?? $request->product_id) : $request->product_id;

        $query = DB::table('transaction_stock_show AS transaction_stock')
            ->join('products', 'transaction_stock.product_id', '=', 'products.id')
            ->join('product_units', 'products.product_unit', '=', 'product_units.id')
            ->select('transaction_stock.*', 'products.name', 'product_units.abbreviation as unit');

        if ($request->filled('branch') && $request->branch != 'all') {
            $query->where('transaction_stock.branch_id', $request->branch);
        }

        if ($request->filled('reference') && $request->reference != 'all') {
            $query->whereRaw("transaction_stock.url COLLATE utf8mb4_unicode_ci = ?", [$request->reference]);
        }

        $query->where(function($q) use ($productId) {
            $q->where('transaction_stock.product_id', $productId)
              ->orWhere('transaction_stock.stock_parent_id', $productId);
        });

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = is_array($request->start_date) ? ($request->start_date[0] ?? $request->start_date) : $request->start_date;
            $endDate = is_array($request->end_date) ? ($request->end_date[0] ?? $request->end_date) : $request->end_date;
            $query->whereDate('transaction_stock.date', '>=', $startDate)
                  ->whereDate('transaction_stock.date', '<=', $endDate);
        }

        if ($request->filled('search')) {
            $search = is_array($request->search) ? ($request->search['value'] ?? '') : $request->search;
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('products.name', 'like', "%{$search}%")
                      ->orWhere('transaction_stock.reff', 'like', "%{$search}%");
                });
            }
        }

        $query->orderBy('transaction_stock.date', 'desc')
              ->orderBy('transaction_stock.source_table', 'desc')
              ->orderBy('transaction_stock.id', 'desc')
              ->orderBy('transaction_stock.detail_id', 'desc');

        $data = $query->get();
        return DataTables::of($data)
            ->addIndexColumn()
->addColumn('name', function ($product) {
                $targetUrl = $this->getTargetUrlFromTransaction($product);

                $html  = '<div class="d-flex align-items-center">';
                $html .= '<div class="ms-5">
                            <a href="' . $targetUrl . '" class="text-gray-800 text-hover-primary fs-5 fw-bold"
                               data-kt-ecommerce-product-filter="product_name">
                                ' . e($product->name) . '
                            </a>
                        </div>
                    </div>';
                return $html;
            })
            ->addColumn('quantity', function ($product) {
                if ($product->quantity > 0) {
                    return '<span class="badge badge-light-success">' . $product->quantity . ' ' . $product->unit . '</span>';
                } else {
                    return '<span class="badge badge-light-danger">' . $product->quantity . ' ' . $product->unit . '</span>';
                }
            })
        // ->addColumn('date', function ($item) {
        //     return \Carbon\Carbon::parse($item->date)->format('d M y H:i:s');
        // })
            ->rawColumns(['name', 'quantity'])
            ->make(true);
    }

    private function getTargetUrlFromTransaction(object $transaction): string
    {
        $baseUrl = $transaction->url;
        $id = $transaction->id;

        $showRoutes = ['wholesale', 'pos', 'sortir', 'transfer'];
        $detailRoutes = ['production'];

        if (in_array($baseUrl, $showRoutes)) {
            return url($baseUrl . '/show/' . $id);
        }

        if (in_array($baseUrl, $detailRoutes)) {
            return url($baseUrl . '/' . $id . '/detail');
        }

        return url($baseUrl . '/' . $id . '/edit');
    }

    }
