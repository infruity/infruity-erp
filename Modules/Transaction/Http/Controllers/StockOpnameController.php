<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Transaction\Entities\StockOpname;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;
use Exception;

class StockOpnameController extends Controller
{
    use \App\Traits\HasAccessControl;

    private function allowedOpname($id): StockOpname
    {
        return StockOpname::whereIn('branch_id', UserBranch::getUserBranch())->findOrFail($id);
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('stock-opname.index')) {
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

        return view('transaction::stock-opname.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('stock-opname.create')) {
            return $denied;
        }

        return view('transaction::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('stock-opname.store')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branch,id',
            'date' => 'required|date',
            'real_stock' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'details' => 'nullable|array',
            'details.*.name' => 'required|string',
            'details.*.qty' => 'required|numeric|min:0',
        ]);

        abort_unless(in_array((int) $validated['branch_id'], array_map('intval', UserBranch::getUserBranch()), true), 403);

        $existing = StockOpname::where('product_id', $validated['product_id'])
            ->where('branch_id', $validated['branch_id'])
            ->whereDate('date', $validated['date'])
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Produk ini sudah di-stock opname pada tanggal yang dipilih di cabang ini. Harap selesaikan stock opname sebelumnya sebelum membuat yang baru.',
            ], 422);
        }

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $productStock = DB::table('product_stock')
                ->where('id', $validated['product_id'])
                ->where('branch_id', $validated['branch_id'])
                ->first();
            if (!$productStock) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Stok produk tidak ditemukan.',
                ], 404);
            }
            $avg_price = $productStock->avg_hpp;
            $stockAvailable = $productStock->stock_available;
            $stock = new StockOpname();
            $stock->code = StockOpname::getOrderNumber();
            $stock->product_id = $validated['product_id'];
            $stock->date = $validated['date'];
            $stock->branch_id = $validated['branch_id'];
            $stock->avg_price = $avg_price ?? 0;
            $stock->stock = $stockAvailable;
            $stock->real_stock = $validated['real_stock'];
            $stock->difference =  (Double)$validated['real_stock'] - (Double)$stockAvailable;
            $stock->details = isset($validated['details']) ? json_encode($validated['details']) : null;
            $stock->status = 'pending';
            $stock->created_by = Auth::user()->id_user;
            $stock->save();

            $noteText = $request->input('note') ?: 'Input Stock Fisik Awal';
            DB::table('stock_opname_history')->insert([
                'stock_opname_id' => $stock->id,
                'action' => 'INITIAL',
                'note' => $noteText,
                'real_stock' => $stock->real_stock,
                'difference' => $stock->difference,
                'details' => $stock->details,
                'created_by' => Auth::user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json([
                'message' => 'Transaksi gagal disimpan.',
                'data' => $stock
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Transaksi berhasil disimpan.',
            'data' => $stock
        ], 201);
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
        if ($denied = $this->requireAccess('stock-opname.edit')) {
            return $denied;
        }

        $stock = StockOpname::with('product.unit')->whereIn('branch_id', UserBranch::getUserBranch())->findOrFail($id);
        $stock->name = $stock->product->name;

        // Fetch latest history note
        $latestHistory = DB::table('stock_opname_history')
            ->where('stock_opname_id', $id)
            ->orderBy('created_at', 'desc')
            ->first();
        $stock->note = $latestHistory ? $latestHistory->note : '';

        return response()->json($stock);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('stock-opname.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branch,id',
            'date' => 'required|date',
            'real_stock' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'details' => 'nullable|array',
            'details.*.name' => 'required|string',
            'details.*.qty' => 'required|numeric|min:0',
        ]);

        abort_unless(in_array((int) $validated['branch_id'], array_map('intval', UserBranch::getUserBranch()), true), 403);

        try {
            DB::beginTransaction();
            $stock = StockOpname::whereIn('branch_id', UserBranch::getUserBranch())->lockForUpdate()->findOrFail($id);
            abort_if($stock->status === 'selesai', 422, 'Stock opname yang sudah selesai tidak dapat diubah.');
            if ((int) $stock->product_id !== (int) $validated['product_id']
                || (int) $stock->branch_id !== (int) $validated['branch_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['product_id' => 'Produk dan cabang stock opname tidak dapat diubah.']);
            }

            $stock->product_id = $validated['product_id'];
            $stock->date = $validated['date'];
            $stock->branch_id = $validated['branch_id'];
            $stock->real_stock = $validated['real_stock'];
            $stock->difference = (Double)$validated['real_stock'] - (Double)$stock->stock;
            $stock->details = isset($validated['details']) ? json_encode($validated['details']) : null;
            $stock->updated_by = Auth::user()->id_user;
            $stock->save();

            $noteText = $request->input('note') ?: 'Penyesuaian Stok Fisik';
            DB::table('stock_opname_history')->insert([
                'stock_opname_id' => $stock->id,
                'action' => 'UPDATE',
                'note' => $noteText,
                'real_stock' => $stock->real_stock,
                'difference' => $stock->difference,
                'details' => $stock->details,
                'created_by' => Auth::user()->id_user,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json([
                'message' => 'Transaksi gagal diperbarui.',
            ], 500);
        }

        return response()->json([
            'message' => 'Transaksi berhasil diperbarui.',
            'data' => $stock
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('stock-opname.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $stock = StockOpname::whereIn('branch_id', UserBranch::getUserBranch())->lockForUpdate()->findOrFail($id);
            abort_if($stock->status === 'selesai', 422, 'Stock opname yang sudah selesai tidak dapat dihapus.');
            $stock->delete();
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

    public function export(Request $request)
    {
        $this->authorizeAccess('stock-opname.index');
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StockOpnameExport($request), 'stock-opname-'.date('YmdHis').'.xlsx');
    }

    public function preview(Request $request)
    {
        $this->authorizeAccess('stock-opname.index');
        $userBranches = UserBranch::getUserBranch();
        $query = StockOpname::with('product.unit')
            ->whereIn('stock_opname.branch_id', $userBranches);

        $branchStr = 'SEMUA CABANG';
        if ($request->has('cabang_filter') && $request->cabang_filter !== 'all') {
            $query->where('stock_opname.branch_id', $request->cabang_filter);
            $branch = Branch::find($request->cabang_filter);
            if ($branch) {
                $branchStr = $branch->name;
            }
        }

        $dateStr = '';
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('stock_opname.date', '>=', $request->start_date);
            $start = \Carbon\Carbon::parse($request->start_date);
            $dateStr .= $start->translatedFormat('d F Y');
        }

        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('stock_opname.date', '<=', $request->end_date);
            $end = \Carbon\Carbon::parse($request->end_date);
            if ($request->start_date != $request->end_date) {
                $dateStr .= ' - ' . $end->translatedFormat('d F Y');
            }
        }

        if ($dateStr == '') {
            $dateStr = 'SEMUA TANGGAL';
        }

        $data = $query->orderBy('stock_opname.created_at', 'desc')->get();

        return view('transaction::stock-opname.exports.preview', [
            'data' => $data,
            'branchStr' => $branchStr,
            'dateStr' => $dateStr
        ]);
    }

    public function get_data(Request $request)
    {
        $this->authorizeAccess('stock-opname.index');
        $userBranches = UserBranch::getUserBranch();
        $query = StockOpname::with('product.unit')
            ->leftJoin('users', 'stock_opname.created_by', '=', 'users.id_user')
            ->leftJoin('branch', 'stock_opname.branch_id', '=', 'branch.id')
            ->leftJoin('products', 'stock_opname.product_id', '=', 'products.id')
            ->leftJoin('product_stock', function ($j) {
                $j->on('product_stock.id', '=', 'stock_opname.product_id')
                  ->on('product_stock.branch_id', '=', 'stock_opname.branch_id');
            })
            ->select(
                'stock_opname.*',
                'users.nm_user as creator_name',
                'branch.name as branch_name',
                'products.name as product_name',
                'product_stock.avg_hpp as avg_hpp_calc',
                'product_stock.stock_available as live_stock',
                DB::raw('(SELECT COUNT(*) FROM stock_opname_discussions WHERE stock_opname_discussions.stock_opname_id = stock_opname.id) as discussions_count'),
                DB::raw('(SELECT COUNT(*) FROM stock_opname_history WHERE stock_opname_history.stock_opname_id = stock_opname.id) as history_count')
            )
            ->whereIn('stock_opname.branch_id', $userBranches);

        if ($request->has('cabang_filter') && $request->cabang_filter !== 'all') {
            $query->where('stock_opname.branch_id', $request->cabang_filter);
        }

        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('stock_opname.date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('stock_opname.date', '<=', $request->end_date);
        }

        $query->orderBy('stock_opname.created_at', 'desc');

        return DataTables::of($query)
            ->filter(function ($q) use ($request) {
                if ($request->has('search') && !empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $q->where(function ($sub) use ($search) {
                        $sub->where('stock_opname.code', 'like', "%{$search}%")
                            ->orWhere('products.name', 'like', "%{$search}%")
                            ->orWhere('users.nm_user', 'like', "%{$search}%")
                            ->orWhere('branch.name', 'like', "%{$search}%");
                    });
                }
            })
            ->addIndexColumn()
            ->make(true);
    }

    public function get_history($id)
    {
        $this->authorizeAccess('stock-opname.index');
        $stockOpname = StockOpname::with('product.unit')->whereIn('branch_id', UserBranch::getUserBranch())->findOrFail($id);
        
        $history = DB::table('stock_opname_history')
            ->leftJoin('users', 'stock_opname_history.created_by', '=', 'users.id_user')
            ->select('stock_opname_history.*', 'users.nm_user as creator_name')
            ->where('stock_opname_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $history->transform(function ($item) {
            $item->details = $item->details ? json_decode($item->details) : null;
            return $item;
        });
            
        if ($history->isEmpty()) {
            $creator = DB::table('users')->where('id_user', $stockOpname->created_by)->first();
            $history = collect([[
                'action' => 'INITIAL',
                'note' => 'Input Stock Fisik Awal',
                'real_stock' => $stockOpname->real_stock,
                'difference' => $stockOpname->difference,
                'details' => $stockOpname->details ? json_decode($stockOpname->details) : null,
                'created_by' => $stockOpname->created_by,
                'creator_name' => $creator ? $creator->nm_user : 'System',
                'created_at' => $stockOpname->created_at->toDateTimeString(),
            ]]);
        }
        
        return response()->json([
            'code' => $stockOpname->code,
            'unit' => $stockOpname->product->unit->abbreviation ?? 'Kg',
            'history' => $history
        ]);
    }

    public function get_discussion($id)
    {
        $this->authorizeAccess('stock-opname.index');
        $stockOpname = StockOpname::with('product')->whereIn('branch_id', UserBranch::getUserBranch())->findOrFail($id);
        
        $discussions = DB::table('stock_opname_discussions')
            ->leftJoin('users', 'stock_opname_discussions.created_by', '=', 'users.id_user')
            ->select('stock_opname_discussions.*', 'users.nm_user as creator_name')
            ->where('stock_opname_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();
            
        return response()->json([
            'code' => $stockOpname->code,
            'product_name' => $stockOpname->product->name ?? '-',
            'discussions' => $discussions
        ]);
    }

    public function post_discussion(Request $request, $id)
    {
        $this->authorizeAccess('stock-opname.index');
        $stockOpname = $this->allowedOpname($id);
        
        $validated = $request->validate([
            'message' => 'required|string|max:1000'
        ]);
        
        $insertedId = DB::table('stock_opname_discussions')->insertGetId([
            'stock_opname_id' => $id,
            'message' => $validated['message'],
            'created_by' => Auth::user()->id_user,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $newDiscussion = DB::table('stock_opname_discussions')
            ->leftJoin('users', 'stock_opname_discussions.created_by', '=', 'users.id_user')
            ->select('stock_opname_discussions.*', 'users.nm_user as creator_name')
            ->where('stock_opname_discussions.id', $insertedId)
            ->first();
            
        return response()->json([
            'message' => 'Pesan berhasil dikirim.',
            'discussion' => $newDiscussion
        ], 201);
    }

    public function adjust(Request $request, $id)
    {
        if ($denied = $this->requireAccess('stock-opname.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'qty' => 'required|numeric|min:0.01',
            'type' => 'required|in:up,down',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $stock = StockOpname::whereIn('branch_id', UserBranch::getUserBranch())->lockForUpdate()->findOrFail($id);
            abort_if($stock->status === 'selesai', 422, 'Stock opname yang sudah selesai tidak dapat diubah.');

            $oldRealStock = (float)$stock->real_stock;
            $adjustQty = (float)$validated['qty'];

            if ($validated['type'] === 'up') {
                $newRealStock = $oldRealStock + $adjustQty;
            } else {
                $newRealStock = $oldRealStock - $adjustQty;
            }

            if ($newRealStock < 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Stok fisik setelah penyesuaian tidak boleh kurang dari 0.'
                ], 422);
            }

            $stock->real_stock = $newRealStock;
            $stock->difference = $newRealStock - (float)$stock->stock;
            $stock->updated_by = Auth::user()->id_user;
            $stock->save();

            DB::table('stock_opname_history')->insert([
                'stock_opname_id' => $stock->id,
                'action' => 'UPDATE',
                'note' => $validated['reason'],
                'real_stock' => $stock->real_stock,
                'difference' => $stock->difference,
                'details' => $stock->details,
                'created_by' => Auth::user()->id_user,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json([
                'message' => 'Gagal melakukan penyesuaian stok: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Penyesuaian stok berhasil disimpan.',
            'data' => $stock
        ]);
    }

    public function finalize($id)
    {
        if ($denied = $this->requireAccess('stock-opname.update')) {
            return $denied;
        }

        try {
            DB::beginTransaction();

            $stockOpname = StockOpname::whereIn('branch_id', UserBranch::getUserBranch())
                ->lockForUpdate()->findOrFail($id);

            if ($stockOpname->status === 'selesai') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stock Opname sudah difinalisasi.'
                ], 422);
            }

            \Modules\Master\Entities\Product::whereKey($stockOpname->product_id)->lockForUpdate()->firstOrFail();
            $productStock = DB::table('product_stock')
                ->where('id', $stockOpname->product_id)
                ->where('branch_id', $stockOpname->branch_id)
                ->first();

            if (!$productStock) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stok produk tidak ditemukan.'
                ], 404);
            }

            $currentStock = $productStock->stock_available;
            $avg_price = $productStock->avg_hpp;
            
            $difference = (Double)$stockOpname->real_stock - (Double)$currentStock;

            $stockOpname->stock = $currentStock;
            $stockOpname->difference = $difference;
            $stockOpname->status = 'selesai';
            $stockOpname->updated_by = Auth::user()->id_user;
            $stockOpname->save();

            // (removed invalid insert into transaction_stock view)



            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock Opname berhasil difinalisasi.',
                'data' => $stockOpname
            ]);
        } catch (Exception $e) {
            DB::rollback();
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan finalisasi: ' . $e->getMessage()
            ], 500);
        }
    }
}
