<?php

namespace Modules\Master\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Master\Entities\Account;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class AccountController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('account.index')) {
            return $denied;
        }

        return view('master::account.index', ['records' => Account::query()->select('id', 'name', 'code')->orderByDesc('id')->get()]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if ($denied = $this->requireAccess('account.create')) {
            return $denied;
        }

        return redirect()->route('account.index', ['create' => 1]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($denied = $this->requireAccess('account.store')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:account,name',
            'code' => 'required|string|max:255|unique:account,code',
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $account = new Account();
            $account->name = $validated['name'];
            $account->code = $validated['code'];
            $account->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Account gagal disimpan.',
                'data' => $account
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Account berhasil disimpan.',
            'data' => $account
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
        if ($denied = $this->requireAccess('account.edit')) {
            return $denied;
        }

        $account = Account::findOrFail($id);
        return response()->json($account);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if ($denied = $this->requireAccess('account.update')) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:account,name,' . $id,
            'code' => 'required|string|max:255|unique:account,code,' . $id,
        ]);

        // Simpan data ke database
        try {
            DB::beginTransaction();
            $account = Account::findOrFail($id);
            $account->name = $validated['name'];
            $account->code = $validated['code'];
            $account->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Account gagal disimpan.',
                'data' => $account
            ], 404);
        }

        // Kirim response JSON
        return response()->json([
            'message' => 'Account berhasil disimpan.',
            'data' => $account
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if ($denied = $this->requireAccess('account.destroy')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $account = Account::findOrFail($id);
            $account->delete();
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
        $data = Account::all();
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
