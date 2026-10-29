<?php

namespace Modules\Pos\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pos\Entities\PosModel;
use Modules\Master\Entities\Staff;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Pos\Entities\Payment;
use Illuminate\Support\Str;

class DeliveryOrderController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if ($denied = $this->requireAccess('delivery-order.index')) {
            return $denied;
        }

        $branches = Branch::whereIn('id', UserBranch::getUserBranch())->get();
        return view('pos::delivery-order.index', compact('branches'));
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

    public function setSelesai(Request $request, $id)
    {
        if ($denied = $this->requireAccess('delivery-order.set-selesai')) {
            return $denied;
        }

        try {
            DB::beginTransaction();
            $pos = PosModel::find($id);
            $pos->ongkir_status = 'delivered';

            if ($request->has('nominal') && $request->nominal > 0) {
                $payment = new Payment([
                    'uuid' => Str::uuid(),
                    'date' => date('Y-m-d'),
                    'nota_number' => date('YmdHis'),
                    'pos_id' => $id,
                    'branch_id' => 1,
                    // 'account_id' => $data['account_id'],
                    'payment_method' => json_encode(['cash']),
                    'payment_method_id' => json_encode([1]),
                    'payment_amount' => json_encode([preg_replace('/[^0-9]/', '', $request->nominal)]),
                    'total' => preg_replace('/[^0-9]/', '', $request->nominal),
                    'created_by' => Auth::user()->id_user,
                ]);
                $payment->save();
                $totalPayment = Payment::where('pos_id', $id)->sum('total');
                $pos = PosModel::findOrFail($id);
                $total = $pos->total - $pos->voucher;
                $pos->paid = $totalPayment;
                if ($totalPayment > $total) {
                    $lastPayment = Payment::findOrFail($payment->id);
                    $lastPayment->return = ($totalPayment - $total);
                    $lastPayment->save();
                } else {
                    $lastPayment = Payment::findOrFail($payment->id);
                    $lastPayment->remaining = ($total - $totalPayment);
                    $lastPayment->save();
                }
                $status = 'debt';
                if ($totalPayment >= $total) {
                    $status = 'paid';
                }
                // $pos->ongkir_status = 'delivered';
                $pos->status = $status;
            }

            $pos->save();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => false, 'message' => $th->getMessage()]);
        }
    }

    public function getCourier(Request $request)
    {
        if ($denied = $this->requireAccess('delivery-order.get-courier')) {
            return $denied;
        }

        // Get ONLY staff that are registered in the kurir table (internal type)
        $staffs = Staff::join('kurir', 'staff.id', '=', 'kurir.staff_id')
            ->where('kurir.type', 'internal')
            ->where('staff.is_kurir', 1)
            ->select('staff.*')
            ->get();

        return view('pos::delivery-order.list-kurir', compact('staffs'));
    }

    public function updateCourier(Request $request)
    {
        if ($denied = $this->requireAccess('delivery-order.update-courier')) {
            return $denied;
        }

        $kurirIds = $request->input('kurir_ids', []);
        $staffs = Staff::all();
        foreach ($staffs as $staff) {
            $staff->is_kurir = in_array($staff->id, $kurirIds);
            $staff->save();
        }
        return response()->json(['success' => true]);
    }

    public function get_data(Request $request)
    {
        $query = PosModel::with('customer', 'paymentDetails', 'details', 'branch', 'courier')
        // ->whereIn('branch_id', UserBranch::getUserBranch())
        ->where('ongkir', '>', 0);
        if ($request->has('status_filter') && $request->status_filter !== 'all') {
            $query = $query->where('ongkir_status', $request->status_filter);
        }
        if ($request->has('cabang_filter') && $request->cabang_filter !== 'all') {
            $query = $query->where('branch_id', $request->cabang_filter);
        }
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('ongkir_date', [$request->start_date, $request->end_date]);
        }
        $data = $query->orderBy('ongkir_status', 'asc')
        ->orderBy('created_at', 'desc');
        // dd($data);
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($item) {
                $html = '<div class="d-flex align-items-center">';
                $html .= '<div class="ms-5">';
                if (isset($item->customer->name)) {
                    $html .= '<a href="' . route('pos.show', $item->id) . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . $item->customer->name ?? '-' . '</a>';
                    $html .= '<br><span class="text-muted d-block fs-7">' . ($item->ongkir_address) . '</span>';
                } else {
                    $html .= '<a href="' . route('pos.show', $item->id) . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">Pelanggan Umum</a>';
                    $html .= '<br><span class="text-muted d-block fs-7">' . ($item->ongkir_address) . '</span>';
                }
                $html .= '</div>';
                $html .= '</div>';
                return $html;
            })
            ->addColumn('courier', function ($item) {
                $courierName = '-';
                $courierType = null;
                $courierId = $item->courier_id;

                if (!$courierId) {
                    $courierName = '-';
                } else {
                    // Cek di tabel kurir untuk mendapatkan type (external atau internal)
                    $kurir = \Modules\Master\Entities\Kurir::find($courierId);
                    if ($kurir) {
                        $courierType = $kurir->type;
                        // Jika internal, ambil nama dari staff
                        if ($courierType === 'internal' && $kurir->staff_id) {
                            $staff = \Modules\Master\Entities\Staff::find($kurir->staff_id);
                            $courierName = $staff ? $staff->name : 'Not found';
                        } else {
                            // External atau internal tanpa staff_id (edge case)
                            $courierName = $kurir->name;
                        }
                    } else {
                        // Fallback: cek di tabel staff (untuk legacy data yang tidak ada di kurir)
                        $staff = \Modules\Master\Entities\Staff::find($courierId);
                        $courierName = $staff ? $staff->name : 'Not found';
                        $courierType = $staff && $staff->is_kurir ? 'internal' : 'unknown';
                    }
                }

                $typeLabel = match($courierType) {
                    'external' => 'External',
                    'internal' => 'Internal',
                    default => $courierType ? ucfirst($courierType) : 'Unknown'
                };
                $typeClass = match($courierType) {
                    'external' => 'badge-warning',
                    'internal' => 'badge-info',
                    default => 'badge-secondary'
                };

                $html = '';
                $html .= '<span class="text-muted d-block fs-5">' . ($courierName) . '</span>';
                $html .= '<span class="badge ' . $typeClass . ' fs-8">' . $typeLabel . '</span>';
                $html .= '<span class="text-muted d-block fs-8">Rp. ' . number_format($item->ongkir, 0, ',', '.') . '</span>';
                return $html;
            })
            ->editColumn('ongkir', function ($item) {
                $html = '';
                $html .= '<span class="text-muted d-block fs-8">Rp. ' . number_format($item->ongkir, 0, ',', '.') . '</span>';
                if ($item->ongkri_status == 'delivered' && $item->payment_status == 'paid') {
                    $html .= '<span class="badge badge-light-success">Selesai</span>';
                } else {
                    $html .= '<span class="badge badge-light-danger">Process</span>';
                }
                return $html;
            })
            ->addColumn('total_price', function ($item) {
                return 'Rp. ' . number_format($item->total_price, 0, ',', '.');
            })
            ->addColumn('total_quantity', function ($item) {
                return $item->total_quantity;
            })
            ->addColumn('date', function ($item) {
                $fallbackDateTime = now();
                $deliveryDate = !empty($item->ongkir_date)
                    ? date('Y-m-d', strtotime($item->ongkir_date))
                    : $fallbackDateTime->format('Y-m-d');
                $deliveryTime = !empty($item->ongkir_time)
                    ? date('H:i', strtotime($item->ongkir_time))
                    : $fallbackDateTime->format('H:i');

                $html = '<span class="text-muted d-block fs-8">' . $deliveryDate . '</span>';
                $html .= '<span class="text-muted d-block fs-8">' . $deliveryTime . '</span>';
                if ($item->branch) {
                    $html .= '<span class="badge badge-light-primary">' . e($item->branch->name) . '</span>';
                }
                return $html;
            })
            ->addColumn('status', function ($item) {
                $html = '';
                if ($item->ongkir_status == 'delivered') {
                    $html .= '<span class="badge badge-light-success">Diterima</span>';
                } else if ($item->ongkir_status == 'draft') {
                    $html .= '<span class="badge badge-light-danger">Belum Dikirm</span>';
                } else {
                    $html .= '<span class="badge badge-light-warning">' . $item->ongkir_status . '</span>';
                }
                $html .= '<div class="d-flex"></div>';
                if ($item->status == 'paid') {
                    $html .= '<span class="badge badge-light-success">Lunas</span>';
                } else if ($item->status == 'draft') {
                    $html .= '<span class="badge badge-light-danger">Belum Lunas</span>';
                } else {
                    $html .= '<span class="badge badge-light-warning">' . $item->status . '</span>';
                }
                return $html;
            })
            ->addColumn('action', function ($item) {
                $html = '';
                $html .= '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">';
                $html .= '
                            <li>
                                <a class="dropdown-item" href="' . route('pos.show', $item->id) . '">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>';
                if (!in_array($item->status, ['paid'])) {
                    $html .= '
                            <li>
                                <a class="dropdown-item" href="' . route('pos.payment', $item->id) . '">
                                    <i class="bi bi-cash-stack"></i>
                                </a>
                            </li>';
                }
                if (!in_array($item->ongkir_status, ['delivered'])) {
                    if (!in_array($item->status, ['paid'])) {
                        $html .= '
                            <li>
                                <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="setBayar(' . $item->id . ')">
                                    <i class="bi bi-check2-circle"></i>
                                </a>
                            </li>';
                    } else {
                        $html .= '
                            <li>
                                <a class="dropdown-item text-primary d-flex justify-content-center" href="javascript:void(0)" onclick="setSelesai(' . $item->id . ')">
                                    <i class="bi bi-check2-circle"></i>
                                </a>
                            </li>';
                    }
                }

                $customerWa = $item->customer->whatsapp ?? '';

                if (!empty($customerWa)) {
                    $phone = $customerWa;
                    $waUrl = "https://wa.me/{$phone}";

                    $html .= '
                            <li>
                                <a class="dropdown-item text-success d-flex justify-content-center" href="' . $waUrl . '" target="_blank" title="Kirim WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                            </li>';
                }

                $html .= '
                        </ul>
                    </div>
                    ';
                return $html;
            })
            ->rawColumns(['name', 'action', 'date', 'ongkir', 'courier', 'status'])
            ->make(true);
    }
}
