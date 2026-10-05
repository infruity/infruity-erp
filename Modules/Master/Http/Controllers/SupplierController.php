<?php

namespace Modules\Master\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Master\Entities\Supplier;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Auth;
use Exception;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('supplier.index')) {
            return $denied;
        }

        return view('master::supllier.index', [
            'records' => Supplier::query()
                ->leftJoin('users as updater', 'supplier.updated_by', '=', 'updater.id_user')
                ->select('supplier.id', 'supplier.name', 'supplier.pic_name', 'supplier.pic_whatsapp', 'supplier.address', 'supplier.email', 'supplier.updated_at', 'updater.nm_user as updated_by_name')
                ->orderByDesc('supplier.id')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('supplier.create')) {
            return $denied;
        }

        return redirect()->route('supplier.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('supplier.store')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:supplier,name',
            'pic_name' => 'required|string|max:255',
            'pic_whatsapp' => [
                'required',
                'numeric',
                'digits_between:10,15',
                'regex:/^(?:\+62|62|08)[0-9]{8,13}$/'
            ],
            'address' => 'nullable|string|max:1000',
            'email' => 'nullable|email|unique:supplier,email',
        ]);

        try {
            DB::beginTransaction();
            $supplier = new Supplier();
            $supplier->name = $validated['name'];
            $supplier->pic_name = $validated['pic_name'];
            $supplier->pic_whatsapp = $validated['pic_whatsapp'];
            $supplier->address = $validated['address'];
            $supplier->email = $validated['email'];
            $supplier->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Supplier gagal disimpan.',
                'data' => $supplier
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Supplier berhasil disimpan.',
            'data' => $supplier
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
        if ($denied = $this->requireAccess('supplier.edit')) {
            return $denied;
        }

        $supplier = Supplier::findOrFail($id);
        return response()->json($supplier);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('supplier.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:supplier,name,' . $id,
            'pic_name' => 'required|string|max:255',
            'pic_whatsapp' => [
                'required',
                'numeric',
                'digits_between:10,15',
                'regex:/^(?:\+62|62|08)[0-9]{8,13}$/'
            ],
            'address' => 'nullable|string|max:1000',
            'email' => 'nullable|email|unique:supplier,email,' . $id,
        ]);

        try {
            DB::beginTransaction();
            $supplier = Supplier::findOrFail($id);
            $supplier->name = $validated['name'];
            $supplier->pic_name = $validated['pic_name'];
            $supplier->pic_whatsapp = $validated['pic_whatsapp'];
            $supplier->address = $validated['address'];
            $supplier->email = $validated['email'];
            $supplier->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Supplier gagal disimpan.',
                'data' => $supplier
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Supplier berhasil disimpan.',
            'data' => $supplier
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('supplier.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();
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
        $data = Supplier::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                return '<div class="d-flex align-items-center">
                            <div class="ms-5">
                                <a href="javascript:void(0)" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . $item->name . '</a>
                                <br> ' . $item->pic_name . '<br> <div class="badge badge-light-success fw-bold">' . $item->pic_whatsapp . '</div>
                            </div>
                        </div>';
            })
            ->addColumn('pic_name', function ($item) {
                return $item->pic_name . '<br> <div class="badge badge-light-success fw-bold">' . $item->pic_whatsapp . '</div>';
            })
            ->addColumn('address', function ($item) {
                return $item->address . '<br> <div class="badge badge-light fw-bold">' . $item->email . '</div>';
            })
            ->addColumn('action', function ($row) {
                $name = e($row->name);

                $html = '
                <div class="dropstart">
                    <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi ' . $name . '">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                if (check_access('supplier.edit')) {
                    $html .= '
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="editProduct(' . $row->id . ')">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </li>';
                }
                if (check_access('supplier.delete')) {
                    $html .= '
                        <li>
                            <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="deleteProduct(' . $row->id . ')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </li>';
                }
                $html .= '
                    </ul>
                </div>';
                return $html;
            })
            ->rawColumns(['name', 'action', 'pic_name', 'address'])
            ->make(true);
    }
}
