<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Transaction\Entities\ProductionParcel;
use Modules\Transaction\Entities\ProductReceipt;
use Modules\Transaction\Entities\Receipt;
use Modules\Transaction\Entities\ProductionParcelDetail;
use Modules\Transaction\Entities\Production;
use Modules\Transaction\Entities\ProductionDetail;
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

class ProductionParcelController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('parcel.index')) {
            return $denied;
        }

        return view('transaction::production.parcel');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('parcel.create')) {
            return $denied;
        }

        return view('transaction::production.create-parcel');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('parcel.store')) {
            return $denied;
        }

        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'quantity' => 'nullable|numeric|min:1',
            'budget' => 'required|numeric|min:1',
            'production_date' => 'required|date',
            'submit_type' => 'required',
            'staff_id' => 'required|exists:staff,id',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        try {
            DB::beginTransaction();

            $production = new ProductionParcel();
            $production->production_number = ProductionParcel::getOrderNumber();
            $production->budget = $request->budget;
            $production->quantity = $request->quantity;
            $production->production_date = date('Y-m-d', strtotime($request->production_date));
            $production->status = $request->submit_type;
            $production->staff_id = $request->staff_id;
            $production->fee = $request->fee;
            $production->save();

            DB::commit();
            return redirect()->route('parcel.index')->with('success', 'Production parcel created successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to create production parcel: ' . $e->getMessage()]);
        }
    }

    public function process($id)
    {
        if ($denied = $this->requireAccess('parcel.process')) {
            return $denied;
        }

        $data['data'] = ProductionParcel::with('staff')->findOrFail($id);
        return view('transaction::production.process-parcel', $data);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if ($denied = $this->requireAccess('parcel.show')) {
            return $denied;
        }

        $data['data'] = ProductionParcel::with('staff')->findOrFail($id);
        $data['production_detail'] = ProductionParcelDetail::with('product')->where('production_id', $id)->get();
        return view('transaction::production.create-parcel', $data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('parcel.edit')) {
            return $denied;
        }

        $data['data'] = ProductionParcel::with('staff')->findOrFail($id);
        $data['production_detail'] = ProductionParcelDetail::with('product')->where('production_id', $id)->get();
        return view('transaction::production.create-parcel', $data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('parcel.update')) {
            return $denied;
        }

        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'quantity' => 'nullable|numeric|min:1',
            'budget' => 'required|numeric|min:1',
            'production_date' => 'required|date',
            'submit_type' => 'required',
            'staff_id' => 'required|exists:staff,id',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        try {
            DB::beginTransaction();

            $production = ProductionParcel::findOrFail($id);
            $production->budget = $request->budget;
            $production->quantity = $request->quantity;
            $production->production_date = date('Y-m-d', strtotime($request->production_date));
            $production->status = $request->submit_type;
            $production->staff_id = $request->staff_id;
            $production->save();

            ProductionParcelDetail::where('production_id', $id)->delete();
            if (empty($request->product_receipt_id)) {
                return redirect()->back()->withErrors(['error' => 'Tambahkan satu produk.']);
            }
            foreach ($request->product_receipt_id as $key => $product) {
                $productionDetail = new ProductionParcelDetail();
                $productionDetail->production_id = $id;
                $productionDetail->product_id = $product;
                $productionDetail->quantity = $request->ingredients_quantity[$key];
                // dd($productionDetail);
                $productionDetail->save();
            }

            DB::commit();
            return redirect()->route('parcel.index')->with('success', 'Production parcel updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to update production parcel: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('parcel.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $parcel = ProductionParcel::findOrFail($id);
            $parcel->delete();
            ProductionParcelDetail::where('production_id', $id)->delete();
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
        $data = ProductionParcel::with('staff')->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                return '<div class="d-flex align-items-center">
                            <div class="ms-5">
                                <a href="' . url('production') . '/' . $item->id . '/show' . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">#' . $item->production_number . '</a>
                                <br>
                                <span class="text-muted fw-bold d-block fs-7">Budget : ' . tonumber($item->budget) . '</span>
                                <span class="text-muted fw-bold d-block fs-7">Pic : ' . (isset($item->staff_id) ? $item->staff->name : '-') . '</span>
                            </div>
                        </div>';
            })
            ->addColumn('production_date', function ($item) {
                return dateindo($item->production_date);
            })
            ->addColumn('status', function ($item) {
                if ($item->status == 'posting') {
                    return '<span class="badge badge-light-primary">Posting</span>';
                } elseif ($item->status == 'complete') {
                    return '<span class="badge badge-light-success">Complete</span>';
                } elseif ($item->status == 'draft') {
                    return '<span class="badge badge-light-warning">Draft</span>';
                } else {
                    return '<span class="badge badge-light-danger">Unknown</span>';
                }
            })
            ->addColumn('status_raw', function ($item) {
                return $item->status;
            })
            ->addColumn('action', function ($item) {
                $html = '<div class="dropstart">
                            <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                                <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';

                if ($item->status != 'complete') {
                    $html .= '  <li>
                                <a class="dropdown-item" href="' . route('parcel.edit', $item->id) . '">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="' . route('parcel.edit', $item->id) . '">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="' . route('parcel.process', $item->id) . '">
                                    <i class="bi bi-box-seam"></i>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $item->id . ')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </li>
                    ';
                } else {
                    $html .= '  <li>
                                    <a class="dropdown-item" href="' . route('parcel.show', $item->id) . '">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </li>
                    ';
                }
                $html .= '</ul>
                    </div>';
                return $html;
            })
            ->addColumn('production_id', function ($item) {
                return $item->id;
            })
            ->rawColumns(['name', 'action', 'status', 'address'])
            ->make(true);
    }

    public function get_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('parcel.get-product')) {
            return $denied;
        }

        // dd($request->all());
        $data = ProductionParcelDetail::where('production_id', $id)->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $html = '';
                $html .= $item->product->name . '<br> Jumlah : ' . $item->quantity . ' ' . $item->product->unit->abbreviation . '<br> Harga : ' . toNumber($item->product->price) . '';
                return $html;
            })
            ->addColumn('hpp', function ($item) {
                return 'Rp' . toNumber($item->product->hpp * $item->quantity);
            })
            ->addColumn('harga_jual', function ($item) {
                return toNumber($item->product->price * $item->quantity);
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

    public function save_product(Request $request)
    {
        if ($denied = $this->requireAccess('parcel.save-product')) {
            return $denied;
        }

        // dd($request->all());
        $validated = $request->validate([
            'production_id' => 'required|exists:production_parcel,id',
            'id' => 'required|exists:products,id',
            'qty' => 'required',
            'sell_price' => 'nullable',
        ]);

        $cek = ProductionParcelDetail::where('production_id', $validated['production_id'])
            ->where('product_id', $validated['id'])
            ->first();
        if ($cek) {
            return response()->json([
                'message' => 'Product sudah ada',
            ], 404);
        }

        try {
            DB::beginTransaction();
            $detailProduct = new ProductionParcelDetail();
            $detailProduct->production_id = $validated['production_id'];
            $detailProduct->product_id = $validated['id'];
            $detailProduct->quantity = $validated['qty'];
            $detailProduct->save();

            if ($validated['sell_price'] != null) {
                $product = Product::findOrFail($validated['id']);
                $product->price = str_replace('.', '', $validated['sell_price']);
                $product->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Product gagal disimpan.' . $e->getMessage(),
                'data' => $detailProduct
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Product berhasil disimpan.',
            'data' => $detailProduct
        ], 201);
    }

    public function edit_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('parcel.edit-product')) {
            return $denied;
        }

        $data = ProductionParcelDetail::findOrFail($id);
        $product = Product::findOrFail($data->product_id);
        $data->price = $product->price;
        $data->nominal = $product->price * $data->quantity;
        return response()->json([
            'data' => $data
        ], 201);
    }

    public function update_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('parcel.update-product')) {
            return $denied;
        }

        $validated = $request->validate([
            'qty' => 'required|numeric',
            'sell_price' => 'nullable',
        ]);

        try {
            DB::beginTransaction();
            $detailProduct = ProductionParcelDetail::findOrFail($id);
            $detailProduct->quantity = $validated['qty'];
            $detailProduct->save();

            if ($validated['sell_price'] != null) {
                $product = Product::findOrFail($detailProduct->product_id);
                $product->price = str_replace('.', '', $validated['sell_price']);
                $product->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Product updated failed']);
        }

        return response()->json(['message' => 'Product updated successfully']);
    }

    public function delete_product(Request $request, $id)
    {
        if ($denied = $this->requireAccess('parcel.delete_product')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $deteilProduct = ProductionParcelDetail::findOrFail($id);
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

    public function set_selesai(Request $request, $id)
    {
        if ($denied = $this->requireAccess('parcel.set_selesai')) {
            return $denied;
        }

        try {
            DB::beginTransaction();

            $userId = Auth::id(); // Ambil user sekali
            $parcel = ProductionParcel::findOrFail($id);
            $parcel->status = 'complete';

            // Create product
            $productNameBase = 'PARCEL ' . formatRibuanToK($parcel->budget);
            $product = new Product([
                'name' => Product::generateProductName($productNameBase),
                'description' => 'Generate parcel ' . $parcel->production_number . ' by System',
                'price' => $parcel->budget ?? '',
                'product_unit' => 3,
                'status' => 'receipt',
                'hpp' => $request->hpp ?? 0,
                'fee' => $parcel->fee ?? 0,
                'created_by' => $userId,
            ]);
            $product->save();

            $receipt = new Receipt([
                'code' => Receipt::getOrderNumber(),
                'product_id' => $product->id,
                'description' => $product->description,
                'created_by' => $userId,
            ]);
            $receipt->save();

            // Ambil detail produk parcel
            $details = ProductionParcelDetail::where('production_id', $parcel->id)->get();

            $parcel->product_id = $product->id;
            $parcel->save();

            $production = new Production([
                'production_number' => Production::getOrderNumber(), // Use Production's method instead of Receipt's
                'product_id' => $product->id,
                'quantity' => $parcel->quantity,
                'production_date' => now(),
                'status' => 'complete',
                'description' => $product->description,
                'staff_id' => $parcel->staff_id,
                'created_by' => $userId,
            ]);
            $production->save();

            // Persiapkan data insert massal
            $productReceipts = [];
            $productionDetail = [];
            foreach ($details as $item) {
                $productReceipts[] = [
                    'receipt_id' => $receipt->id,
                    'product_id' => $product->id,
                    'product_receipt_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $productionDetail[] = [
                    'production_id' => $production->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'created_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            // dd($productReceipts, $productionDetail);
            // Insert massal untuk meningkatkan performa
            ProductReceipt::insert($productReceipts);
            ProductionDetail::insert($productionDetail);

            DB::commit();

            return response()->json([
                'message' => 'Parcel berhasil diterima dan menjadi produk.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Parcel gagal diterima. ' . $e->getMessage(),
            ], 500);
        }
    }
}
