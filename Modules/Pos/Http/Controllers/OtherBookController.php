<?php

namespace Modules\Pos\Http\Controllers;

use App\Support\DataTableCells;
use DB;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pos\Entities\PosModel;
use Yajra\DataTables\Facades\DataTables;

class OtherBookController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('other-book.index')) {
            return $denied;
        }

        return view('pos::otherbook.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('pos::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('pos::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('pos::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function setSelesai($id)
    {
        if ($denied = $this->requireAccess('other-book.set-selesai')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $pos = PosModel::find($id);
            $pos->process_status = 'done';
            $pos->process_date = date('Y-m-d H:i:s');
            $pos->save();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function get_data(Request $request)
    {
        $query = PosModel::with('customer', 'payment')->where('process_status', '!=', 'none');
        if ($request->has('status_filter') && $request->status_filter !== 'all') {
            $query = $query->where('process_status', $request->status_filter);
        }
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        $data = $query->get();
        // dd($data);
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', fn ($item) => DataTableCells::title(
                $item->customer->name ?? 'Pelanggan Umum',
                $item->customer->address ?? null,
                url('pos') . '/' . $item->id . '/show'
            ))
            ->editColumn('ongkir', function ($item) {
                return 'Rp. ' . number_format($item->ongkir, 0, ',', '.');
            })
            ->editColumn('process_status', function ($item) {
                return match ($item->process_status) {
                    'pending' => DataTableCells::badge('Proses', 'danger'),
                    'done' => DataTableCells::badge('Selesai', 'success'),
                    default => DataTableCells::badge((string) $item->process_status, 'warning'),
                };
            })
            ->addColumn('date', function ($item) {
                return '<span class="block text-xs text-gray-500">' . e(date('d M Y', strtotime($item->process_date))) . '</span>'
                    . '<span class="block text-xs text-gray-500">' . e(date('H:i', strtotime($item->process_date))) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $pending = $item->process_status === 'pending';

                return DataTableCells::rowActions([
                    ['label' => 'Lihat', 'icon' => 'eye', 'href' => route('pos.show', $item->id), 'can' => check_access('pos.show')],
                    ['label' => 'Ubah', 'icon' => 'pencil-simple', 'href' => route('pos.edit', $item->id), 'can' => $pending && check_access('pos.edit')],
                    ['label' => 'Selesaikan', 'icon' => 'check-circle', 'onclick' => 'setSelesai(' . (int) $item->id . ')', 'can' => $pending],
                ], 'Aksi pemasukan');
            })
            ->rawColumns(['name', 'action', 'date', 'process_status'])
            ->make(true);
    }
}
