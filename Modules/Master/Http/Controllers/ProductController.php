<?php
namespace Modules\Master\Http\Controllers;

use App\Support\Like;
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
use Modules\Master\Support\ProductIcon;
use App\Support\LedgerCache;
use App\Support\LedgerSql;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    use \App\Traits\HasAccessControl;

    /** The five product types of the reference (no custom categories): what the list filter and the add-product drawer offer. */
    private function referenceTypeOptions()
    {
        $names = ['Bahan Baku', 'Perlu Resep', 'Produk Olahan', 'Kemasan', 'Jasa / Layanan'];

        return $this->productTypeOptions()->filter(fn ($option) => in_array($option->name, $names, true))->values();
    }

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
        $data['productTypeOptions'] = $this->referenceTypeOptions();
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
                        DB::rollBack();
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
                        throw new \InvalidArgumentException('Varian produk baru harus dibuat dengan nama baru.');
                    } else {
                        if ($value == $product->name) {
                            DB::rollBack();
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
        if ($denied = $this->requireAccess('product-stock.show')) {
            return $denied;
        }

        $data['product'] = Product::findOrFail($id);
        $data['report'] = DB::table('report_total_belanja')->where('product_id', $id)->first()
            ?? (object) [
                'total_belanja' => 0, 'total_masuk_lain' => 0, 'total_cogs' => 0, 'total_kerugian' => 0,
                'total_bahan_produksi' => 0, 'total_opname' => 0, 'total_transfer' => 0,
                'last_asset' => 0, 'selisih_control' => 0,
            ];
        return view('master::products.show-transaction', $data);
    }

    public function get_data_transaction(Request $request)
    {
        $this->authorizeAccess('product-stock.show');
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'stock_direction' => ['nullable', 'in:all,in,out'],
            'reference' => ['nullable', 'in:all,stock-opname,sortir,pos,production,wholesale,transfer'],
        ]);

        $query = LedgerSql::hpp([$request->product_id])
            ->orderBy('seq', 'asc');

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);

        if ($request->filled('reference') && $request->reference !== 'all') {
            $referenceTypes = [
                'stock-opname' => ['opname'],
                'sortir' => ['barang_buang'],
                'pos' => ['penjualan'],
                'production' => ['bahan_produksi', 'produksi'],
                'wholesale' => ['pengadaan'],
                'transfer' => ['transfer'],
            ];
            $query->whereIn('trx_type', $referenceTypes[$request->reference] ?? [$request->reference]);
        }

        if ($request->filled('branch') && $request->branch !== 'all') {
            $branchId = (int) $request->branch;
            abort_unless(in_array($branchId, UserBranch::getUserBranch(), true), 403);
            $branchSources = [
                'pengadaan' => ['wholesale', 'id'],
                'barang_buang' => ['sortir_transaction', 'id'],
                'penjualan' => ['pos_transaction', 'id'],
                'bahan_produksi' => ['production', 'id'],
                'produksi' => ['production', 'id'],
                'opname' => ['stock_opname', 'id'],
            ];
            $query->where(function ($branchQuery) use ($branchId, $branchSources) {
                foreach ($branchSources as $type => [$table, $idColumn]) {
                    $branchQuery->orWhere(function ($sourceQuery) use ($branchId, $type, $table, $idColumn) {
                        $sourceQuery->where('trx_type', $type)->whereExists(function ($exists) use ($branchId, $table, $idColumn) {
                            $exists->selectRaw('1')->from($table . ' as source_branch')
                                ->whereColumn('source_branch.' . $idColumn, 'product_hpp.trx_id')
                                ->where('source_branch.branch_id', $branchId);
                        });
                    });
                }
            });
        }
        if ($request->input('stock_direction') === 'in') {
            $query->where('type', '+');
        } elseif ($request->input('stock_direction') === 'out') {
            $query->where('type', '-');
        }

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
    public function edit(Request $request, $id)
    {
        if ($denied = $this->requireAccess('products.edit')) {
            return $denied;
        }

        $product = Product::with(['childProducts.product.productBranches', 'productBranches'])->findOrFail($id);
        if ($request->wantsJson()) {
            $selectedBranchId = (int) $request->input('branch_id', 0);
            $allowedBranchIds = UserBranch::getUserBranch();
            if ($selectedBranchId && ! in_array($selectedBranchId, $allowedBranchIds, true)) {
                abort(403);
            }
            return response()->json([
                'id' => $product->id,
                'name' => $product->name,
                'categoryId' => $product->category_id,
                'tipe' => $product->tipe,
                'status' => $product->status,
                'unitId' => $product->product_unit,
                'price' => $product->price,
                'description' => $product->description,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'limit' => $product->limit,
                'handling' => $product->handling,
                'imageUrl' => $product->image ? asset('storage/' . $product->image) : null,
                'variants' => $product->childProducts->map(fn ($child) => [
                    'id' => $child->product?->id,
                    'name' => $child->product?->name,
                    'price' => $child->product?->productBranches?->firstWhere('branch_id', $selectedBranchId)?->price
                        ?? $child->product?->price,
                ])->filter(fn ($child) => $child['id'])->values(),
                'branchPrices' => $product->productBranches->whereIn('branch_id', $allowedBranchIds)->map(fn ($branch) => [
                    'branchId' => $branch->branch_id,
                    'price' => $branch->price,
                ])->values(),
                'updateUrl' => route('products.update', $product->id),
            ]);
        }
        $data    = [
            'data'          => $product,
            'product_units' => ProductUnit::all(),
            'productTypeOptions' => $this->productTypeOptions(),
            'branch' => Branch::whereIn('id', UserBranch::getUserBranch())->get(),
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
            'selected_branch_id' => 'nullable|integer|exists:branch,id',
            // 'category_id'     => 'nullable|exists:products_category,id',
            'avatar'          => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description'     => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $allowedBranchIds = UserBranch::getUserBranch();
        $selectedBranchId = (int) $request->input('selected_branch_id', 0);
        if ($selectedBranchId && ! in_array($selectedBranchId, $allowedBranchIds, true)) {
            abort(403);
        }
        foreach ((array) ($request->input('branch.id') ?? []) as $branchId) {
            if (! in_array((int) $branchId, $allowedBranchIds, true)) {
                abort(403);
            }
        }

        try {
            DB::beginTransaction();

            $category = [];
            if (isset($request->category_id)) {
                if (is_numeric($request->category_id)) {
                    $category = ProductCategory::find($request->category_id);
                    if (! $category) {
                        DB::rollBack();
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
            $product->updated_by   = Auth::user()->id_user;

            // Validate every existing variant before replacing relations.
            $variantIds = array_values(array_filter(
                (array) $request->input('variant.id', []),
                fn ($value) => is_numeric($value)
            ));
            if (count($variantIds) !== count(array_unique($variantIds)) ||
                ProductChild::where('parent_id', $id)->whereIn('product_id', $variantIds)->distinct()->count('product_id') !== count($variantIds)) {
                throw new \InvalidArgumentException('Daftar varian berisi produk asing atau duplikat.');
            }

            // Hapus semua varian yang ada
            ProductChild::where('parent_id', $id)->delete();
            $productId = $id;
            // Simpan varian baru
            if (isset($request->variant)) {
                foreach ($request->variant['id'] as $key => $value) {
                    if (is_numeric($value)) {
                        $variant = Product::whereKey($value)
                            ->where('parent_id', $id)
                            ->first();
                        if (! $variant) {
                            throw new \InvalidArgumentException('Varian tidak terhubung dengan produk ini.');
                        }
                        $variant->is_variant = 1;
                        $variantPrice = $request->variant['price'][$key] ?? 0;
                        if ($selectedBranchId) {
                            ProductBranch::updateOrCreate(
                                ['product_id' => $variant->id, 'branch_id' => $selectedBranchId],
                                ['price' => $variantPrice]
                            );
                        } else {
                            $variant->price = $variantPrice;
                        }
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
                // Hapus semua branch yang ada
                ProductBranch::where('product_id', $id)->whereIn('branch_id', $allowedBranchIds)->delete();
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
        if ($denied = $this->requireAccess('products.update')) {
            return $denied;
        }

        $validator = Validator::make($request->all(), [
            'price'     => 'required|numeric|min:0',
            'branch_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($request->branch_id != 0 && ! in_array((int) $request->branch_id, UserBranch::getUserBranch(), true)) {
            abort(403);
        }

        try {
            DB::beginTransaction();
            if ($request->has('branch_id') && $request->branch_id != 0) {
                Product::findOrFail($id);
                ProductBranch::updateOrCreate(
                    ['product_id' => $id, 'branch_id' => $request->branch_id],
                    ['price' => preg_replace('/[^0-9]/', '', $request->price)]
                );
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
                'price'        => 'required|numeric|min:0',
                'parent_id'    => 'required|integer|exists:products,id',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }
            DB::beginTransaction();
            $variant = Product::whereKey($id)
                ->where('parent_id', $request->parent_id)
                ->whereHas('parentProduct', fn ($query) => $query->where('parent_id', $request->parent_id))
                ->firstOrFail();
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

    public function destroyVariant(Request $request, $id)
    {
        if ($denied = $this->requireAccess('products.destroy-variant')) {
            return $denied;
        }

        $request->validate(['parent_id' => 'required|integer|exists:products,id']);

        try {
            DB::beginTransaction();
            $variant = Product::whereKey($id)
                ->where('parent_id', $request->parent_id)
                ->whereHas('parentProduct', fn ($query) => $query->where('parent_id', $request->parent_id))
                ->firstOrFail();
            ProductChild::where('parent_id', $request->parent_id)->where('product_id', $variant->id)->delete();
            $variant->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
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
        $query = Product::with(['unit', 'category'])
            ->where('status', 'receipt')
            ->where('name', 'like', Like::contains($search))
            ->select('id', 'name', 'hpp', 'product_unit', 'category_id')
            ->get()
            ->map(function ($product) {
                [$emoji, $iconClass] = ProductIcon::resolve($product);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'hpp' => (float) ($product->hpp ?? 0),
                    'unit' => $product->unit?->abbreviation ?? $product->unit?->name ?? '',
                    'material_emoji' => $emoji,
                    'material_icon_class' => $iconClass,
                ];
            });

        return response()->json($query);
    }

    public function getProduct(Request $request)
    {
        $search = $request->input('search', '');
        $products = Product::with(['unit', 'category'])
            ->select('id', 'name', 'sku as code', 'hpp', 'price', 'stock', 'product_unit', 'category_id')
            ->where('name', 'like', Like::contains($search))
            ->where('tipe', '!=', 'parcel'); // Exclude parcel type

        if ($request->boolean('raw_materials')) {
            $products->where('status', 'no-receipt')
                ->whereNull('is_variant')
                ->orderBy('name')
                ->limit(60);
        }

        $query = $products->get()->map(function ($item) use ($request) {
            // Use hpp if available, otherwise use price
            $item->hpp = $item->hpp ?: 0;
            if ($request->boolean('raw_materials')) {
                [$item->material_emoji, $item->material_icon_class] = \Modules\Master\Support\ProductIcon::resolve($item);
            }
            return $item;
        });

        return response()->json($query);
    }

    public function listProduct(Request $request)
    {
        $search = $request->input('term', '');
        // The stock of the products and of their ingredients is loaded below in one go (see attachStock): eager loading
        // it as two relations evaluated the product_stock view twice (~340 ms each).
        $with   = ['category', 'unit', 'productReceipt', 'productReceipt.ingredients', 'parentProduct'];

        $query  = Product::with($with)
            ->where('tipe', '!=', 'parcel')->where('name', 'like', Like::contains($search));

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
        $this->attachStock($query, $request->has('branch') && ! empty($request->branch) ? $request->branch : null);

        // Last purchase per product, its branch, and that branch's sell price:
        // three queries for the whole list instead of three per product.
        $productIds = $query->pluck('id')->unique()->values();
        $lastWholesales = \Modules\Transaction\Entities\WholesaleProduct::with('wholesale')
            ->whereIn('product_id', $productIds)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');
        $branchPrices = \Modules\Master\Entities\ProductBranch::whereIn('product_id', $lastWholesales->keys())
            ->get()
            ->groupBy('product_id');

        $query->map(function ($product) use ($lastWholesales, $branchPrices) {
            $lastWholesale = $lastWholesales->get($product->id);
            $product->last_price = $lastWholesale ? (float) $lastWholesale->price : null;

            // Ambil last_sell_price dari ProductBranch berdasarkan branch_id di transaksi pengadaan terakhir
            $branchId = $lastWholesale?->wholesale?->branch_id;
            if ($branchId) {
                $productBranch = $branchPrices->get($product->id)?->firstWhere('branch_id', $branchId);
                $product->last_sell_price = $productBranch ? (float) $productBranch->price : null;
            } else {
                $product->last_sell_price = null;
            }

            return $product;
        });

        return response()->json($query);
    }

    /**
     * Sets the get_stock relation of the products and of their recipe ingredients from ONE product_stock read, with the
     * same result as eager loading it (a belongsTo: the last stock row of the product wins, none gives null).
     */
    private function attachStock($products, $branchId): void
    {
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product->id;
            foreach ($product->productReceipt as $receipt) {
                if ($receipt->ingredients) {
                    $ids[] = $receipt->ingredients->id;
                }
            }
        }

        $ids = array_values(array_unique($ids));
        $load = fn () => LedgerSql::stockFor($ids)
            ->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        // A long list reads the whole stock ledger (~340 ms); keep it until the ledger changes. Short lists are cheap
        // and varied (one per search term), so they are not stored.
        $stock = (count($ids) > LedgerSql::FILTER_LIMIT
            ? LedgerCache::remember('list_product_stock:' . md5(implode(',', $ids) . '|' . $branchId), $load)
            : $load()
        )->keyBy('id');

        foreach ($products as $product) {
            $product->setRelation('get_stock', $stock->get($product->id));
            foreach ($product->productReceipt as $receipt) {
                $receipt->ingredients?->setRelation('get_stock', $stock->get($receipt->ingredients->id));
            }
        }
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
        $this->authorizeAccess('products.index');
        $branchFilter = $request->input('branch_filter');
        if ($branchFilter && $branchFilter != 0
            && ! in_array((int) $branchFilter, UserBranch::getUserBranch(), true)) {
            abort(403);
        }
        $priceOrderExpression = 'products.price';

        $childProductRelations = ['category', 'childProducts.product', 'creator:id_user,nm_user', 'updater:id_user,nm_user'];
        if ($branchFilter && $branchFilter != 0) {
            $childProductRelations['childProducts.product.productBranches'] = fn($branches) => $branches->where('branch_id', $branchFilter);
        } else {
            // Child prices read productBranches; load them with the page instead of per product.
            $childProductRelations[] = 'childProducts.product.productBranches:id,product_id,branch_id,price';
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
                                if ($option->name === 'Bahan Baku') {
                                    $optionQuery->whereNull('products.category_id')
                                        ->where(function ($types) {
                                            $types->whereNull('products.tipe')
                                                ->orWhere('products.tipe', '')
                                                ->orWhere('products.tipe', 'product');
                                        })
                                        ->where(function ($statuses) {
                                            $statuses->whereNull('products.status')
                                                ->orWhere('products.status', '')
                                                ->orWhere('products.status', 'no-receipt');
                                        });
                                } elseif ($option->name === 'Perlu Resep') {
                                    $optionQuery->where('products.status', 'receipt');
                                } elseif ($option->name === 'Kemasan') {
                                    $optionQuery->where('products.tipe', 'kemasan');
                                } elseif (is_numeric($option->category_id)) {
                                    $optionQuery->where('products.category_id', $option->category_id);
                                } else {
                                    $optionQuery->whereRaw('1 = 0');
                                }
                            });
                        }
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        $canUpdatePrice = check_access('products.update');
        $data = $query;
        return DataTables::of($data)
            ->filter(function ($q) use ($request) {
                $search = trim((string) $request->input('search.value', ''));
                if ($search !== '') {
                    $terms = preg_split('/\\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($terms as $term) {
                        $pattern = Like::contains($term);
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
            ->editColumn('name', function ($product) use ($branchFilter, $canUpdatePrice) {
                if (isset($product->image)) {
                    $url = asset('storage/' . $product->image);
                    $avatar = '<div class="w-9 h-9 md:w-12 md:h-12 rounded-xl overflow-hidden shrink-0 shadow-sm border border-gray-100 flex items-center justify-center text-lg md:text-xl bg-gray-100">
                        <img src="' . $url . '" alt="Product Image" class="w-full h-full object-cover">
                    </div>';
                } else {
                    [$emoji, $bgClass] = ProductIcon::resolve($product);
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

                $unit = mb_strtolower($product->unit_abbreviation ?? 'kg');
                $detail = e(json_encode([
                    'id' => $product->id,
                    'name' => $product->name,
                    'type' => $categoryName,
                    'price' => $hasChildren ? $mobilePriceLabel : $mobilePriceLabel . ' / ' . $unit,
                    'updatedBy' => $product->updater?->nm_user ?? $product->creator?->nm_user ?? 'Administrator',
                    'updatedAt' => ($product->updated_at ?? $product->created_at)?->locale('id')->translatedFormat('d M Y, H:i') ?? '-',
                    'editUrl' => route('products.edit', $product->id),
                ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG));

                $parentRow = '<div class="flex items-center gap-3 md:gap-4 px-5 py-3.5 min-h-[64px] md:min-h-[76px]">' . $avatar . '
                    <div class="flex-1 min-w-0">
                        <button type="button" class="product-detail-trigger block w-full truncate text-left text-[15px] md:text-base font-semibold text-gray-900 hover:text-emerald-700" data-product-detail="' . $detail . '" data-kt-ecommerce-product-filter="product_name">' . e($product->name) . '</button>' . $subtitle . '
                    </div>
                    <span class="md:hidden shrink-0 whitespace-nowrap text-[13px] font-semibold text-emerald-800 ' . ($hasChildren ? 'product-detail-trigger cursor-pointer' : ($canUpdatePrice ? 'editable-price cursor-pointer' : '')) . '" ' . ($hasChildren ? 'role="button" tabindex="0" data-product-detail="' . $detail . '"' : ($canUpdatePrice ? 'role="button" tabindex="0" data-id="' . $product->id . '" data-value="' . $mobilePrice . '"' : '')) . '>' . $mobilePriceLabel . ($hasChildren ? '' : '<span class="font-normal text-gray-400">/' . e($unit) . '</span>') . '</span>
                </div>';

                if (!$hasChildren) {
                    return '<div class="product-detail-row cursor-pointer" data-product-detail="' . $detail . '">' . $parentRow . '</div>';
                }

                $childrenHtml = '<div class="border-t border-emerald-100/60 bg-emerald-50/20">
                    <div class="relative ml-[34px] md:ml-[44px]">
                        <span aria-hidden="true" class="absolute left-0 top-0 bottom-0 w-[2px] bg-emerald-100"></span>';
                foreach ($product->childProducts as $child) {
                    $childName = $child->product->name ?? '';
                    $childPrice = (float) ($child->product?->productBranches?->first()?->price ?? $child->product?->price ?? 0);
                    $unit = mb_strtolower($product->unit_abbreviation ?? 'kg');
                    $childrenHtml .= '<div class="product-detail-row relative min-h-[40px] md:min-h-[44px] flex items-center pr-3 text-[13px] text-gray-500 font-medium cursor-pointer" data-product-detail="' . $detail . '">
                        <span aria-hidden="true" class="absolute left-0 top-1/2 -translate-y-1/2 w-[26px] md:w-8 h-px bg-emerald-100"></span>
                        <span class="flex-1 min-w-0 truncate pl-[34px] md:pl-10 text-[14px] md:text-[13px]">' . e($childName) . '</span>
                        <span class="md:hidden shrink-0 whitespace-nowrap text-[13px] font-semibold text-gray-700 ' . ($canUpdatePrice ? 'editable-price cursor-pointer' : '') . '" ' . ($canUpdatePrice ? 'role="button" tabindex="0" data-id="' . e($child->product?->id) . '" data-value="' . $childPrice . '"' : '') . '>Rp ' . number_format($childPrice, 0, ',', '.') . '<span class="font-normal text-gray-400">/' . e($unit) . '</span></span>
                    </div>';
                }
                $childrenHtml .= '</div></div>';

                return '<div class="border-b border-emerald-50"><div class="product-detail-row cursor-pointer" data-product-detail="' . $detail . '">' . $parentRow . '</div>' . $childrenHtml . '</div>';
            })
            ->editColumn('price', function ($product) use ($branchFilter, $canUpdatePrice) {
                $hasChildren = $product->childProducts && $product->childProducts->count() > 0;

                if ($hasChildren) {
                    $childPrices = $product->childProducts
                        ->map(fn($child) => (float) ($child->product?->productBranches?->first()?->price ?? $child->product?->price ?? 0));
                    $min = $childPrices->min() ?? 0;
                    $max = $childPrices->max() ?? 0;
                    $rangeLabel = 'Rp ' . number_format($min, 0, ',', '.');
                    if ($min !== $max) {
                        $rangeLabel .= ' – ' . number_format($max, 0, ',', '.');
                    }
                    $range = '<div role="button" tabindex="0" class="product-price-range flex min-h-[64px] md:min-h-[76px] cursor-pointer items-center justify-end px-1 pr-5 md:pr-6 text-sm font-semibold text-emerald-800 whitespace-nowrap">' . $rangeLabel . '</div>';

                    $variantPrices = '<div class="border-t border-emerald-100/60 bg-emerald-50/20 pr-5 md:pr-6">';
                    foreach ($product->childProducts as $child) {
                        $childPrice = (float) ($child->product?->productBranches?->first()?->price ?? $child->product?->price ?? 0);
                        $unit = mb_strtolower($product->unit_abbreviation ?? 'kg');
                        $variantPriceLabel = 'Rp ' . number_format($childPrice, 0, ',', '.') . '<span class="text-xs text-gray-400 font-normal">/' . e($unit) . '</span>';
                        $variantPrices .= '<div class="min-h-[40px] md:min-h-[44px] flex items-center justify-end px-2 text-[13px] font-semibold text-gray-700 whitespace-nowrap">'
                            . ($canUpdatePrice
                                ? '<button type="button" title="Klik untuk ubah harga" class="editable-price rounded-lg px-2 py-1 hover:bg-emerald-50 hover:text-emerald-600" data-id="' . e($child->product?->id) . '" data-value="' . $childPrice . '">' . $variantPriceLabel . '</button>'
                                : $variantPriceLabel)
                            . '</div>';
                    }
                    $variantPrices .= '</div>';

                    return '<div>' . $range . $variantPrices . '</div>';
                }

                $price = (float) ($product->display_price ?? $product->price ?? 0);

                return '<div class="min-h-[72px] flex items-center justify-end px-5"><span class="text-sm font-semibold text-emerald-800 whitespace-nowrap ' . ($canUpdatePrice ? 'editable-price cursor-pointer' : '') . '" ' . ($canUpdatePrice ? 'role="button" tabindex="0" data-id="' . $product->id . '" data-value="' . $price . '"' : '') . '>Rp ' . number_format($price, 0, ',', '.') . '</span></div>';
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
        $this->authorizeAccess('product-stock.index');
        $allowedBranchIds = array_map('intval', UserBranch::getUserBranch());
        $branch = $request->input('branch', 'all');
        if ($branch !== 'all' && ! in_array((int) $branch, $allowedBranchIds, true)) {
            abort(403);
        }

        $stock = DB::table('product_stock')
            ->whereIn('branch_id', $allowedBranchIds);

        if ($branch !== 'all') {
            $stock->where('branch_id', (int) $branch);
        }

        // Apply stock filters after aggregation, so "all branches" means the combined balance.
        $totals = $stock->select(
            'id',
            DB::raw('SUM(parent_stock) AS parent_stock'),
            DB::raw('SUM(child_consumed) AS child_consumed'),
            DB::raw('SUM(stock_available) AS stock_available')
        )->groupBy('id');

        // The totals come from the product_stock view (the whole ledger, ~0.3 s) and every count/page query of the
        // list used to rebuild it. Read once, kept until a ledger row changes, then joined as a small literal table.
        $totalRows = LedgerCache::remember(
            'stock_list_totals:' . md5(implode(',', $allowedBranchIds) . '|' . $branch),
            fn () => $totals->get()->map(fn ($r) => [(int) $r->id, (string) $r->parent_stock, (string) $r->child_consumed, (string) $r->stock_available])->all()
        );
        $query = DB::table('products as p')
            ->leftJoinSub($this->stockTotalsTable($totalRows), 'stock_totals', 'stock_totals.id', '=', 'p.id')
            ->leftJoin('product_units as u', 'u.id', '=', 'p.product_unit')
            ->whereNull('p.is_variant')
            ->where('p.tipe', '!=', 'parcel')
            ->select(
                'p.id', 'p.name', 'p.sku', 'p.limit', 'p.hpp', 'p.price',
                'p.tipe', 'p.category_id', 'p.product_unit',
                'u.abbreviation as unit',
                DB::raw('COALESCE(stock_totals.parent_stock, 0) AS parent_stock'),
                DB::raw('COALESCE(stock_totals.child_consumed, 0) AS child_consumed'),
                DB::raw('COALESCE(stock_totals.stock_available, 0) AS stock_available')
            );

        if ($allowedBranchIds === []) {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('stock_filter')) {
            if ($request->stock_filter === 'ada') {
                $query->whereRaw('COALESCE(stock_totals.stock_available, 0) > 0');
            } elseif ($request->stock_filter === 'kosong') {
                $query->whereRaw('COALESCE(stock_totals.stock_available, 0) <= 0');
            }
        }

        if ($request->filled('stock_kategori') && $request->stock_kategori !== 'all') {
            $query->where('p.category_id', $request->stock_kategori);
        }

        $sortColumns = [0 => 'p.name', 1 => 'p.hpp', 2 => 'stock_available'];
        $sortIndex = (int) $request->input('order.0.column', 2);
        $sortDirection = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        $table = DataTables::of($query);

        // Without a stock filter the left join to the stock totals never changes how many products there are, but each
        // count query would rebuild the whole stock ledger for it (~400 ms each). Count the products alone.
        if (! $request->filled('stock_filter')) {
            $countProducts = function (?string $search = null) use ($request, $allowedBranchIds) {
                $count = DB::table('products as p')->whereNull('p.is_variant')->where('p.tipe', '!=', 'parcel');
                if ($allowedBranchIds === []) {
                    $count->whereRaw('1 = 0');
                }
                if ($request->filled('stock_kategori') && $request->stock_kategori !== 'all') {
                    $count->where('p.category_id', $request->stock_kategori);
                }
                if ($search !== null && $search !== '') {
                    $count->where(fn ($q) => $q->where('p.name', 'like', Like::contains($search))
                        ->orWhere('p.sku', 'like', Like::contains($search)));
                }

                return $count->count();
            };

            $table->setTotalRecords($countProducts())
                ->setFilteredRecords($countProducts(trim((string) $request->input('search.value'))));
        }

        return $table
            ->order(function ($query) use ($sortColumns, $sortIndex, $sortDirection) {
                $query->orderBy($sortColumns[$sortIndex] ?? 'stock_available', $sortDirection)
                    ->orderBy('p.id');
            })
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value'));

                if ($search === '') {
                    return;
                }

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('p.name', 'like', Like::contains($search))
                        ->orWhere('p.sku', 'like', Like::contains($search));
                });
            }, false)
            ->addIndexColumn()
            ->addColumn('stock_raw', fn ($product) => (float) $product->stock_available)
            ->addColumn('product_name', fn ($product) => $product->name)
            ->addColumn('hpp_raw', fn ($product) => (float) $product->hpp)
            ->addColumn('detail_url', fn ($product) => route('product-stock.show', $product->id))
            ->addColumn('icon', fn ($product) => ProductIcon::resolve($product)[0])
            ->addColumn('icon_class', fn ($product) => ProductIcon::resolve($product)[1])
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

    /**
     * The (id, parent_stock, child_consumed, stock_available) totals as an inline SELECT. Every value was read from the
     * database as a number and is re-checked here, so nothing user-controlled reaches the SQL.
     *
     * @param  list<array{0:int,1:string,2:string,3:string}>  $rows
     */
    private function stockTotalsTable(array $rows): string
    {
        $number = fn (string $v) => preg_match('/^-?\d+(\.\d+)?$/', $v) ? $v : '0';

        if ($rows === []) {
            return 'SELECT NULL AS id, NULL AS parent_stock, NULL AS child_consumed, NULL AS stock_available FROM (SELECT 1) AS empty_totals WHERE 1 = 0';
        }

        $selects = [];
        foreach ($rows as $i => [$id, $parent, $consumed, $available]) {
            $selects[] = $i === 0
                ? sprintf('SELECT %d AS id, %s AS parent_stock, %s AS child_consumed, %s AS stock_available', $id, $number($parent), $number($consumed), $number($available))
                : sprintf('SELECT %d, %s, %s, %s', $id, $number($parent), $number($consumed), $number($available));
        }

        return implode(' UNION ALL ', $selects);
    }

    public function get_data_available(Request $request)
    {
        abort_unless(\App\Models\RoleMenu::checkAccess('product-stock.index')
            || \App\Models\RoleMenu::checkAccess('stock-out.index')
            || \App\Models\RoleMenu::checkAccess('stock-opname.index'), 403);
        $allowedBranches = UserBranch::getUserBranch();
        if (\App\Models\RoleMenu::checkAccess('stock-out.index')) {
            $allowedBranches[] = 0; // Central stock used by stock-out.
        }
        if ($request->has('branch_id') && ! $request->filled('branch_id')) {
            return collect();
        }
        if ($request->filled('branch_id') && ! in_array((int) $request->branch_id, $allowedBranches, true)) {
            abort(403);
        }

        // The first 20 matches. Reading the whole product_stock view for them cost ~400 ms per keystroke, so the matching
        // products are looked up first (cheap) and only their stock is computed, a few at a time.
        $branchIds = $request->filled('branch_id') ? [(int) $request->branch_id] : $allowedBranches;
        $candidates = DB::table('products')
            ->whereNull('is_variant')
            ->where('tipe', '!=', 'parcel')
            ->where('name', 'like', Like::contains((string) $request->search))
            ->orderBy('id')
            ->pluck('id');

        $rows = collect();
        foreach ($candidates->chunk(LedgerSql::FILTER_LIMIT) as $chunk) {
            $rows = $rows->concat(
                LedgerSql::stockFor($chunk)
                    ->whereIn('branch_id', $branchIds)
                    ->select('id', 'name', 'stock_available', 'avg_hpp as hpp')
                    ->get()
            );
            if ($rows->count() >= 20) {
                break;
            }
        }

        return $rows->take(20)->values();
    }

    public function get_data_stock_show(Request $request)
    {
        $this->authorizeAccess('product-stock.show');
        $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $productId = is_array($request->product_id) ? ($request->product_id[0] ?? $request->product_id) : $request->product_id;

        $query = DB::table('transaction_stock_show AS transaction_stock')
            ->whereIn('transaction_stock.branch_id', UserBranch::getUserBranch())
            ->join('products', 'transaction_stock.product_id', '=', 'products.id')
            ->join('product_units', 'products.product_unit', '=', 'product_units.id')
            ->select('transaction_stock.*', 'products.name', 'product_units.abbreviation as unit');

        if ($request->filled('branch') && $request->branch != 'all') {
            abort_unless(in_array((int) $request->branch, UserBranch::getUserBranch(), true), 403);
            $query->where('transaction_stock.branch_id', $request->branch);
        }

        if ($request->filled('reference') && $request->reference != 'all') {
            $query->whereRaw("transaction_stock.url COLLATE utf8mb4_unicode_ci = ?", [$request->reference]);
        }

        if ($request->filled('stock_direction') && $request->stock_direction !== 'all') {
            $request->validate(['stock_direction' => ['in:in,out']]);
        }

        $query->where(function($q) use ($productId) {
            $q->where('transaction_stock.product_id', $productId)
              ->orWhere('transaction_stock.stock_parent_id', $productId);
        });

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if ($startDate) $query->whereDate('transaction_stock.date', '>=', $startDate);
        if ($endDate) $query->whereDate('transaction_stock.date', '<=', $endDate);

        if ($request->input('stock_direction') === 'in') {
            $query->where('transaction_stock.quantity', '>', 0);
        } elseif ($request->input('stock_direction') === 'out') {
            $query->where('transaction_stock.quantity', '<', 0);
        }

        if ($request->filled('search')) {
            $search = is_array($request->search) ? ($request->search['value'] ?? '') : $request->search;
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('products.name', 'like', Like::contains($search))
                      ->orWhere('transaction_stock.reff', 'like', Like::contains($search));
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
