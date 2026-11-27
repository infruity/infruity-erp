<?php
namespace Modules\Master\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Master\Entities\PaymentMethod;
use Yajra\DataTables\Facades\DataTables;

class PaymentMethodController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('payment-method.index')) {
            return $denied;
        }

        $records = PaymentMethod::query()
            ->leftJoin('users as editor', 'editor.id_user', '=', 'payment_method.updated_by')
            ->select('payment_method.id', 'payment_method.name', 'payment_method.code', 'payment_method.created_at', 'payment_method.updated_at', 'editor.nm_user as updated_by_name')
            ->orderByDesc('payment_method.id')
            ->get();

        return view('master::payment-method.index', ['records' => $records]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('payment-method.create')) {
            return $denied;
        }

        return redirect()->route('payment-method.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('payment-method.store')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:payment_method,name',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $paymentMethod       = new PaymentMethod();
            $paymentMethod->name = $validated['name'] ?? null;
            $paymentMethod->created_by = \Illuminate\Support\Facades\Auth::user()?->id_user;
            $paymentMethod->updated_by = \Illuminate\Support\Facades\Auth::user()?->id_user;
            $paymentMethod->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Payment Method gagal disimpan.',
                'data'    => $paymentMethod,
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Payment Method berhasil disimpan.',
            'data'    => $paymentMethod,
        ], 201);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('master::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if ($denied = $this->requireAccess('payment-method.edit')) {
            return $denied;
        }

        $paymentMethod = PaymentMethod::findOrFail($id);
        return response()->json($paymentMethod);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('payment-method.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:payment_method,name,' . $id,
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $paymentMethod       = PaymentMethod::findOrFail($id);
            $paymentMethod->name = $validated['name'] ?? null;
            $paymentMethod->updated_by = \Illuminate\Support\Facades\Auth::user()?->id_user;
            $paymentMethod->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Payment Method gagal diupdate.',
                'data'    => $paymentMethod,
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Payment Method berhasil diupdate.',
            'data'    => $paymentMethod,
        ], 201);
    }

    public function getPaymentMethod(Request $request)
    {
        $search = $request->get('search');

        $data = PaymentMethod::where('name', 'like', '%' . $search . '%')
            ->select('id', 'name')
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('payment-method.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $paymentMethod = PaymentMethod::findOrFail($id);
            if (\App\Support\MasterInUse::check('payment_method', [$paymentMethod->getKey()])) {
                DB::rollback();

                return response()->json([
                    'success' => false,
                    'message' => 'Metode pembayaran ini sudah dipakai di transaksi, jadi tidak dapat dihapus. Riwayat dan laporan masih memakainya.',
                ], 422);
            }
            $paymentMethod->delete();
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

    public function get_data(Request $request)
    {
        $this->authorizeAccess('payment-method.index');

        $data = PaymentMethod::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $name = e($row->name);

                return '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi ' . $name . '">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="editProduct(' . $row->id . ')">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $row->id . ')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns(['name', 'quantity', 'action'])
            ->make(true);
    }
}
