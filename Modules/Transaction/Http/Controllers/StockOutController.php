<?php

namespace Modules\Transaction\Http\Controllers;

use App\Support\DataTableCells;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Transaction\Entities\StockOutModel;
use Modules\Transaction\Entities\StockOutType;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class StockOutController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('stock-out.index')) {
            return $denied;
        }

        $data['type'] = StockOutType::all();
        return view('transaction::stock-out.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('stock-out.create')) {
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
        if ($denied = $this->requireAccess('stock-out.store')) {
            return $denied;
        }

        // Validasi input
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'date' => 'required|date',
            'quantity' => 'required|numeric|gt:0',
            'type' => 'required|exists:stock_out_type,id',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            \Modules\Master\Entities\Product::whereKey($validated['product_id'])->lockForUpdate()->firstOrFail();
            $productStock = DB::table('product_stock')
                ->where('id', $validated['product_id'])
                ->where('branch_id', 0)
                ->first();
            if (!$productStock) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Stok produk tidak ditemukan.',
                ], 404);
            }
            if ($validated['quantity'] > $productStock->stock_available) {
                DB::rollBack();
                return response()->json([
                   'message' => 'Jumlah stok tidak mencukupi.',
                ], 404);
            }
            $avg_price = $productStock->hpp;
            $stock = new StockOutModel();
            $stock->code = StockOutModel::getOrderNumber();
            $stock->product_id = $validated['product_id'];
            $stock->date = $validated['date'];
            $stock->type_id = $validated['type'];
            $stock->avg_price = $avg_price;
            $stock->quantity = $validated['quantity'];
            $stock->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
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
        if ($denied = $this->requireAccess('stock-out.edit')) {
            return $denied;
        }

        $stock = StockOutModel::with('product')->findOrFail($id);
        $stock->name = $stock->product->name;
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
        if ($denied = $this->requireAccess('stock-out.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'date' => 'required|date',
            'quantity' => 'required|numeric|gt:0',
            'type' => 'required|exists:stock_out_type,id',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $stock = StockOutModel::lockForUpdate()->findOrFail($id);
            \Modules\Master\Entities\Product::whereKey($validated['product_id'])->lockForUpdate()->firstOrFail();
            $productStock = DB::table('product_stock')
                ->where('id', $validated['product_id'])
                ->where('branch_id', 0)
                ->first();
            if (!$productStock) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Stok produk tidak ditemukan.',
                ], 404);
            }
            $additionalQuantity = (float) $validated['quantity']
                - ((int) $stock->product_id === (int) $validated['product_id'] ? (float) $stock->quantity : 0);
            if ($additionalQuantity > (float) $productStock->stock_available) {
                DB::rollBack();
                return response()->json([
                   'message' => 'Jumlah stok tidak mencukupi.',
                ], 404);
            }
            $avg_price = $productStock->hpp;
            $stock->product_id = $validated['product_id'];
            $stock->date = $validated['date'];
            $stock->type_id = $validated['type'];
            $stock->avg_price = $avg_price;
            $stock->quantity = $validated['quantity'];
            $stock->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
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
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('stock-out.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $stock = StockOutModel::findOrFail($id);
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

    public function get_data(Request $request)
    {
        $this->authorizeAccess('stock-out.index');
        $query = StockOutModel::with('product', 'type');
        if ($request->has('stock_filter')) {
            if ($request->stock_filter != 'all') {
                $query->where('type_id', $request->stock_filter);
            }
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('date', [$request->start_date, $request->end_date]);
            }
        }
        $data = $query->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', fn ($item) => DataTableCells::title(
                '#'.$item->code,
                $item->product->name.' · '.$item->type->name.' · '.dateindo($item->date),
                url('wholesale/'.$item->id.'/show')
            ))
            ->addColumn('quantity', fn ($item) => DataTableCells::badge(toNumber($item->quantity).' '.$item->product->unit->abbreviation, 'primary'))
            ->addColumn('action', fn ($row) => DataTableCells::editDeleteMenu((string) $row->product->name, $row->id, true, true))
            ->rawColumns(['name', 'quantity', 'action'])
            ->make(true);
    }
}
