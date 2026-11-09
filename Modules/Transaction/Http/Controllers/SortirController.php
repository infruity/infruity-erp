<?php

namespace Modules\Transaction\Http\Controllers;

use App\Support\Like;
use Exception;
use App\Models\RoleMenu;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Product;
use Modules\Master\Entities\UserBranch;
use Modules\Transaction\Entities\Sortir;
use Modules\Transaction\Entities\SortirDetail;
use Modules\Transaction\Entities\StockIn;
use Modules\Transaction\Entities\StockOut;
use Yajra\DataTables\Facades\DataTables;

class SortirController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('sortir.index')) {
            return $denied;
        }

        $userBranches = UserBranch::getUserBranch();
        $data['branches'] = Branch::whereIn('id', $userBranches)->get();

        // The sortir page has no negative-stock warning, so it does not query the
        // product_stock view (~100 ms per page load).

        return view('transaction::sortir.index2', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('sortir.create')) {
            return $denied;
        }

        $data['alpinejs']       = true;
        $data['data']           = null;
        $data['detail']         = null;
        $data['invoice_number'] = Sortir::getOrderNumber();
        $data['branches'] = $this->branches();
        return view('transaction::sortir.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('sortir.store')) {
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
        if ($denied = $this->requireAccess('sortir.show')) {
            return $denied;
        }

        $data['data']           = $this->accessibleSortir($id);
        $data['detail']         = SortirDetail::with('product', 'product.unit')->where('sortir_id', $id)->get();
        $data['invoice_number'] = $data['data']->invoice_number;
        return view('transaction::sortir.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('sortir.edit')) {
            return $denied;
        }

        $data['alpinejs']       = true;
        $data['data']           = $this->accessibleSortir($id);
        abort_unless($this->canEdit($data['data']), 409, 'Transaksi final sudah tidak dapat diubah.');
        $data['detail']         = SortirDetail::with('product', 'product.unit')->where('sortir_id', $id)->get();
        $data['invoice_number'] = $data['data']->invoice_number;
        $data['branches'] = $this->branches();
        return view('transaction::sortir.create', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('sortir.update')) {
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
        if ($denied = $this->requireAccess('sortir.destroy')) {
            return $denied;
        }

        return DB::transaction(function () use ($id) {
            $pos = $this->accessibleSortir($id, true);
            abort_unless($this->canDelete($pos), 403, 'Transaksi final hanya dapat dihapus oleh admin.');
            SortirDetail::where('sortir_id', $id)->delete();
            $pos->delete();

            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus.']);
        });
    }

    public function saveTransaction(Request $request)
    {
        $this->authorizeAccess('sortir.save-transaction');
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branch,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'sortir_id' => ['nullable', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:9999999999999.99', 'decimal:0,2'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99', 'decimal:0,2'],
            'status' => ['required', 'in:draft,paid'],
        ]);
        $this->authorizeBranch($data['branch_id']);

        return DB::transaction(function () use ($data) {
            if (empty($data['sortir_id'])) {
                // Use one common row to serialize monthly numbering across branches.
                Branch::withoutGlobalScope('userBranch')->orderBy('id')->lockForUpdate()->firstOrFail();
            }
            // Serialize finalizations for this branch, then lock an existing draft.
            Branch::withoutGlobalScope('userBranch')->whereKey($data['branch_id'])->lockForUpdate()->firstOrFail();
            $pos = ! empty($data['sortir_id'])
                ? $this->accessibleSortir($data['sortir_id'], true)
                : null;
            if ($pos) {
                $this->authorizeBranch($pos->branch_id);
                abort_unless($this->hasAccess('sortir.edit') && $this->canEdit($pos), 409,
                    'Transaksi ini sudah tidak dapat diubah.');
                abort_if(in_array($pos->status, ['paid', 'debt']) && $data['status'] !== 'paid', 409,
                    'Transaksi final tidak dapat dikembalikan menjadi draf.');
            } else {
                $this->authorizeAccess('sortir.create');
                $pos = new Sortir();
                $pos->uuid = (string) Str::uuid();
                $pos->invoice_number = Sortir::getOrderNumber();
                $pos->created_by = Auth::id();
            }

            $oldQuantities = $pos->exists && in_array($pos->status, ['paid', 'debt'])
                && (int) $pos->branch_id === (int) $data['branch_id']
                ? $pos->detail()->pluck('quantity', 'product_id') : collect();
            $productIds = array_column($data['items'], 'id');
            // Product row locks also serialize overlapping products in this flow.
            Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();
            $stock = DB::table('product_stock')->where('branch_id', $data['branch_id'])
                ->whereIn('id', $productIds)->get()->keyBy('id');
            $total = 0;
            $items = [];
            foreach ($data['items'] as $index => $item) {
                $quantity = (float) $item['qty'];
                $gross = round($quantity * (float) $item['price'], 2);
                $discount = (float) ($item['discount'] ?? 0);
                if ($discount > $gross) {
                    throw ValidationException::withMessages(["items.$index.discount" => 'Diskon melebihi nilai item.']);
                }
                $available = (float) ($stock->get($item['id'])->stock_available ?? 0)
                    + (float) ($oldQuantities[$item['id']] ?? 0);
                if ($data['status'] === 'paid' && $quantity > $available + 0.00001) {
                    throw ValidationException::withMessages(["items.$index.qty" =>
                        'Jumlah sortir melebihi stok tersedia (' . $available . ').']);
                }
                $subtotal = round($gross - $discount, 2);
                $total += $subtotal;
                $items[] = ['product_id' => $item['id'], 'quantity' => $quantity,
                    'price' => $item['price'], 'discount' => $discount, 'subtotal' => $subtotal];
            }
            if ($total > 9999999999999.99) {
                throw ValidationException::withMessages(['items' => 'Total nilai sortir melebihi batas.']);
            }
            $pos->fill(['branch_id' => $data['branch_id'], 'date' => $data['date'],
                'total' => round($total, 2), 'status' => $data['status']]);
            $pos->save();
            foreach ($items as $item) {
                SortirDetail::updateOrCreate(['sortir_id' => $pos->id, 'product_id' => $item['product_id']],
                    $item + ['created_by' => $pos->created_by]);
            }
            $pos->detail()->whereNotIn('product_id', $productIds)->delete();

            return response()->json(['success' => true, 'message' => 'Sortir berhasil disimpan.',
                'transaksi_id' => $pos->id, 'invoice_number' => $pos->invoice_number,
                'status' => $pos->status]);
        });
    }

    public function products(Request $request)
    {
        abort_unless($this->hasAccess('sortir.create') || $this->hasAccess('sortir.edit'), 403);
        $data = $request->validate(['branch_id' => ['required', 'integer', 'exists:branch,id'],
            'term' => ['nullable', 'string', 'max:200'], 'sortir_id' => ['nullable', 'integer']]);
        $this->authorizeBranch($data['branch_id']);
        $ownQuantities = collect();
        if (! empty($data['sortir_id'])) {
            $sortir = $this->accessibleSortir($data['sortir_id']);
            if (in_array($sortir->status, ['paid', 'debt']) && (int) $sortir->branch_id === (int) $data['branch_id']) {
                $ownQuantities = $sortir->detail()->pluck('quantity', 'product_id');
            }
        }
        $products = Product::with('unit')->where('tipe', '!=', 'parcel')->whereNull('is_variant')
            ->when(! empty($data['term']), fn ($q) => $q->where('name', 'like', Like::contains((string) $data['term'])))
            ->orderBy('name')->get();
        $stock = DB::table('product_stock')->where('branch_id', $data['branch_id'])
            ->whereIn('id', $products->pluck('id'))->get()->keyBy('id');

        return response()->json($products->map(function ($product) use ($stock, $ownQuantities) {
            $productStock = $stock->get($product->id);
            return ['id' => $product->id, 'name' => $product->name,
                'unit' => ['abbreviation' => $product->unit->abbreviation ?? ''],
                'hpp' => (float) ($productStock->avg_hpp ?? $product->hpp ?? 0),
                'stock_available' => (float) ($productStock->stock_available ?? 0)
                    + (float) ($ownQuantities[$product->id] ?? 0)];
        })->values());
    }

    private function branches()
    {
        return Branch::withoutGlobalScope('userBranch')->whereIn('id', UserBranch::getUserBranch())->get(['id', 'name']);
    }

    private function authorizeBranch($branchId): void
    {
        abort_unless(in_array((int) $branchId, array_map('intval', UserBranch::getUserBranch()), true), 403);
    }

    private function accessibleSortir($id, bool $lock = false): Sortir
    {
        $query = Sortir::with(['createdBy', 'branch' => fn ($q) => $q->withoutGlobalScope('userBranch')])
            ->whereIn('branch_id', UserBranch::getUserBranch());
        return ($lock ? $query->lockForUpdate() : $query)->findOrFail($id);
    }

    private function canEdit(Sortir $sortir): bool
    {
        return in_array($sortir->status, ['temp', 'draft', 'pending'])
            || ($sortir->updated_at && $sortir->updated_at->gte(now()->subMinutes(30)));
    }

    private function canDelete(Sortir $sortir): bool
    {
        return ! in_array($sortir->status, ['paid', 'debt']) || RoleMenu::currentRoleId() === 1;
    }

    public function save_stock(Request $request)
    {
        if ($denied = $this->requireAccess('sortir.save-stock')) {
            return $denied;
        }

        // dd($request->all());
        Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'nullable|array',
        ])->validate();

        try {
            DB::beginTransaction();
            $product  = DB::table('product_stock')->where('id', $request->product_id)->first();
            $quantity = $product->stock_available;
            $hpp      = $product->hpp ?? 0;

            if (isset($request->quantity)) {
                foreach ($request->quantity as $key => $value) {
                    $stockIn               = new StockIn();
                    $stockIn->code         = 'sortir';
                    $stockIn->reference_id = $request->product_id;
                    $stockIn->date         = date('Y-m-d');
                    $stockIn->product_id   = $key;
                    $stockIn->quantity     = $value ?? 0;
                    $stockIn->avg_price    = $hpp;
                    $stockIn->created_by   = Auth::user()->id_user;

                    Product::where("id", $key)->update([
                        'hpp' => $hpp,
                    ]);

                    // $variant = ProductStock::where('id', $key)->first();
                    // $variantStock = (float) $variant->stock_available ?? 0;
                    // $variantHpp = $variant->hpp ?? 0;
                    // if ($variantStock <= 0) {
                    //     Product::where("id", $key)->update([
                    //         'hpp' => $hpp,
                    //     ]);
                    // } else {
                    //     $newHpp = collect([$hpp, $variantHpp])->avg();
                    //     Product::where("id", $key)->update([
                    //         'hpp' => $newHpp,
                    //     ]);
                    // }

                    $stockIn->save();
                }

                $stockOut               = new StockOut();
                $stockOut->code         = 'sortir';
                $stockOut->reference_id = $request->product_id;
                $stockOut->date         = date('Y-m-d');
                $stockOut->product_id   = $request->product_id;
                $stockOut->quantity     = array_sum($request->quantity) ?? 0;
                $stockOut->avg_price    = $hpp;
                $stockOut->created_by   = Auth::user()->id_user;
                // dd($stockOut);
                $stockOut->save();
            }

            if (isset($request->buang) && $request->buang > 0) {
                $buang               = new StockOut();
                $buang->code         = 'buang';
                $buang->reference_id = $request->product_id;
                $buang->date         = date('Y-m-d');
                $buang->product_id   = $request->product_id;
                $buang->quantity     = $request->buang ?? 0;
                $buang->avg_price    = $hpp;
                $buang->created_by   = Auth::user()->id_user;
                $buang->save();
            }

            if (isset($request->product_transfer_id) && $request->value_transfer > 0) {
                // dd('masuk', $hpp);
                $transfer               = new StockOut();
                $transfer->code         = 'transfer';
                $transfer->reference_id = $request->product_id;
                $transfer->date         = date('Y-m-d');
                $transfer->product_id   = $request->product_id;
                $transfer->quantity     = $request->value_transfer ?? 0;
                $transfer->avg_price    = $hpp;
                $transfer->created_by   = Auth::user()->id_user;
                $transfer->save();

                $stockIn               = new StockIn();
                $stockIn->code         = 'transfer';
                $stockIn->reference_id = $request->product_id;
                $stockIn->date         = date('Y-m-d');
                $stockIn->product_id   = $request->product_transfer_id;
                $stockIn->quantity     = $request->value_transfer ?? 0;
                $stockIn->avg_price    = $hpp;
                $stockIn->created_by   = Auth::user()->id_user;
                $stockIn->save();
            }
            // dd($stockIn, $stockOut);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->withInput($request->all())
                ->with('error', 'Sortir gagal' . $e->getMessage());
        }

        return redirect('sortir')->with('success', 'Sortir berhasil');
    }

    public function get_data_old(Request $request)
    {
        $data = DB::table('sortir_view')->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $html = '
                    <div class="d-flex align-items-center">';
                $html .= '<div class="ms-5">
                            <a href="' . url('/products', $item->product_id) . '" class="text-gray-800 text-hover-primary fs-5 fw-bold"
                               data-kt-ecommerce-product-filter="product_name">
                                ' . e($item->name) . '
                            </a>
                        </div>
                    </div>
                ';
                return $html;
            })
            ->addColumn('quantity', function ($item) {
                $class = 'badge badge-light-primary';
                if ($item->stock_available <= 0) {
                    $class = 'badge badge-light-danger';
                }
                return '<span class="' . $class . '">' . toNumber($item->stock_available) . ' ' . $item->satuan . '</span>';
            })
            ->addColumn('action', function ($item) {
                return '
                    <a href="' . route('sortir.show', $item->id) . '" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="tooltip" title="View">
                        <i class="fa fa-pencil"></i>
                    </a>
                ';
            })
            ->rawColumns(['name', 'action', 'quantity'])
            ->make(true);
    }

    public function get_data(Request $request)
    {
        $this->authorizeAccess('sortir.index');
        $request->validate([
            'status_filter' => ['nullable', 'in:all,draft,temp,pending,paid,debt,canceled'],
            'cabang_filter' => ['nullable', 'regex:/^(all|[0-9]+)$/'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d',
                ...($request->filled('start_date') ? ['after_or_equal:start_date'] : [])],
        ]);
        $query = Sortir::with(['createdBy', 'detail.product.unit',
            'branch' => fn ($q) => $q->withoutGlobalScope('userBranch')])
            ->whereIn('branch_id', UserBranch::getUserBranch());
        $recordsTotal = (clone $query)->count();
        if ($request->filled('status_filter') && $request->status_filter !== 'all') {
            $statuses = ['draft' => ['draft', 'temp', 'pending'], 'paid' => ['paid', 'debt']];
            $query->whereIn('status', $statuses[$request->status_filter] ?? [$request->status_filter]);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('cabang_filter') && $request->cabang_filter !== 'all') {
            $query->where('branch_id', $request->cabang_filter);
        }
        $search = trim((string) (is_array($request->input('search'))
            ? $request->input('search.value', '') : $request->input('search', '')));
        if ($search !== '') {
            $like = Like::contains($search);
            $query->where(function ($q) use ($like) {
                $q->where('invoice_number', 'like', $like)
                    ->orWhereHas('createdBy', fn ($sub) => $sub->where('nm_user', 'like', $like))
                    ->orWhereHas('branch', fn ($sub) => $sub->where('name', 'like', $like))
                    ->orWhereHas('detail.product', fn ($sub) => $sub->where('name', 'like', $like));
            });
        }
        $query->orderByDesc('id');
        if ($request->boolean('mobile')) {
            $recordsFiltered = (clone $query)->count();
            $canEdit = $this->hasAccess('sortir.edit');
            $canDelete = $this->hasAccess('sortir.destroy');
            $canShow = $this->hasAccess('sortir.show');
            $data = $query->skip(max(0, (int) $request->input('start', 0)))
                ->take(max(1, min(100, (int) $request->input('length', 20))))->get()
                ->map(function ($item) use ($canEdit, $canDelete, $canShow) {
                    return ['id' => $item->id, 'invoice_number' => $item->invoice_number,
                        'date' => $item->date, 'created_at' => $item->created_at?->toIso8601String(),
                        'status' => $item->status, 'total' => (float) $item->total,
                        'branch_name' => $item->branch->name ?? '-',
                        'staff_name' => $item->createdBy->nm_user ?? '-',
                        'items' => $item->detail->map(fn ($detail) => [
                            'product_id' => $detail->product_id, 'name' => $detail->product->name ?? 'Produk dihapus',
                            'unit' => $detail->product->unit->abbreviation ?? '',
                            'quantity' => (float) $detail->quantity, 'price' => (float) $detail->price,
                            'subtotal' => (float) $detail->subtotal])->values(),
                        'can_edit' => $canEdit && $this->canEdit($item),
                        'can_delete' => $canDelete && $this->canDelete($item),
                        'can_show' => $canShow,
                        'show_url' => route('sortir.show', $item->id),
                        'edit_url' => route('sortir.edit', $item->id),
                        'delete_url' => route('sortir.destroy', $item->id)];
                });
            return response()->json(compact('data', 'recordsTotal', 'recordsFiltered'));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                return '<a href="' . route('sortir.show', $item->id) . '" class="text-gray-800 fw-bold">'
                    . e($item->invoice_number) . '</a><br><span class="text-muted">Hpp: Rp'
                    . tonumberround($item->total) . '</span><br><span class="badge badge-light-danger">'
                    . e($item->createdBy->nm_user ?? '-') . '</span>';
            })
            ->addColumn('date', function ($item) {
                return '<span class="text-muted">' . e($item->created_at?->format('d M Y H:i'))
                    . '</span><br><span class="badge badge-light-'
                    . (in_array($item->status, ['paid', 'debt']) ? 'success' : 'warning') . '">'
                    . (in_array($item->status, ['paid', 'debt']) ? 'Final' : 'Draf') . '</span>';
            })
            ->addColumn('action', function ($item) {
                $html = '<a href="' . route('sortir.show', $item->id) . '" class="btn btn-sm btn-light">Detail</a>';
                if ($this->hasAccess('sortir.edit') && $this->canEdit($item)) {
                    $html .= '<a href="' . route('sortir.edit', $item->id) . '" class="btn btn-sm btn-light">Edit</a>';
                }
                if ($this->hasAccess('sortir.destroy') && $this->canDelete($item)) {
                    $html .= '<button type="button" class="btn btn-sm btn-light-danger" onclick="deleteProduct('
                        . $item->id . ')">Hapus</button>';
                }
                return $html;
            })
            ->rawColumns(['name', 'action', 'date'])->make(true);
    }
}
