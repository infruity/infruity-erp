<?php
namespace Modules\Report\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\Branch;
use Modules\Pos\Entities\PosDetailModel;
use Modules\Report\Entities\BranchProduct;
use Modules\Report\Entities\BranchTransaction;
use Modules\Report\Entities\CustomerProduct;
use Modules\Report\Entities\CustomerTransaction;
use Modules\Transaction\Entities\SortirDetail;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        if ($denied = $this->requireAccess('report.transaction')) {
            return $denied;
        }

        return view('report::customer-transaction');
    }

    public function customer_transaction(Request $request)
    {
        if ($denied = $this->requireAccess('report.customer.transaction')) {
            return $denied;
        }

        return view('report::customer-transaction-rep');
    }

    public function branch_transaction(Request $request)
    {
        if ($denied = $this->requireAccess('report.branch.transaction')) {
            return $denied;
        }

        return view('report::branch-transaction-rep');
    }

    public function branch_product(Request $request)
    {
        if ($denied = $this->requireAccess('report.branch.product')) {
            return $denied;
        }

        return view('report::branch-product-rep');
    }
    public function customer_product(Request $request)
    {
        if ($denied = $this->requireAccess('report.customer.product')) {
            return $denied;
        }

        return view('report::product-customer-transaction-rep');
    }
    public function product_buang(Request $request)
    {
        if ($denied = $this->requireAccess('report.product.buang')) {
            return $denied;
        }

        $data['branches'] = Branch::all();
        return view('report::product-buang', $data);
    }
    public function product_sales(Request $request)
    {
        if ($denied = $this->requireAccess('report.product.sales')) {
            return $denied;
        }

        $data['branches']    = Branch::all();
        $data['defaultDate'] = date('Y-m-d');
        return view('report::product-sales', $data);
    }
    public function total_aset(Request $request)
    {
        if ($denied = $this->requireAccess('report.total.aset')) {
            return $denied;
        }

        $data['branches'] = Branch::all();
        return view('report::total-aset', $data);
    }

    public function get_data_transaction(Request $request)
    {
        $data = CustomerTransaction::query();

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('name', function ($row) {
                return $row->name ?? 'Pelanggan Umum';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('d F y : H:i:s');
            })
            ->editColumn('total', function ($row) {
                return number_format($row->total, 0, ',', '.');
            })
            ->editColumn('gender', function ($row) {
                if ($row->gender == 'male') {
                    return '<span class="badge badge-light-primary">Laki-laki</span>';
                } else if ($row->gender == 'female') {
                    return '<span class="badge badge-light-success">Perempuan</span>';
                } else {
                    return '<span class="badge badge-light-danger">-</span>';
                }
            })
            ->editColumn('branch_name', function ($row) {
                switch ($row->branch_id) {
                    case 1:
                        return '<span class="badge badge-light-primary">' . $row->branch_name . '</span>';
                        break;
                    case 2:
                        return '<span class="badge badge-light-success">' . $row->branch_name . '</span>';
                        break;
                    case 3:
                        return '<span class="badge badge-light-warning">' . $row->branch_name . '</span>';
                        break;
                    case 4:
                        return '<span class="badge badge-light-info">' . $row->branch_name . '</span>';
                        break;
                    default:
                        return '<span class="badge badge-light-danger">Other</span>';
                }
            })
            ->editColumn('profit', function ($row) {
                return number_format($row->profit, 0, ',', '.');
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                            <li>
                                <a class="dropdown-item" href="' . route('pos.show', $row->pos_id) . '">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                        </ul>
                    </div>';
            })
            ->rawColumns(['action', 'gender', 'branch_name'])
            ->make(true);
    }

    public function get_data_customer_transaction(Request $request)
    {
        $dr_tgl = date('Y-01-01');
        $sp_tgl = date('Y-12-31');
        $data   = CustomerTransaction::getAllCustomerTransaction($dr_tgl, $sp_tgl);

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('total_omset', function ($row) {
                return number_format($row->total_omset, 0, ',', '.');
            })
            ->editColumn('profit', function ($row) {
                return number_format($row->profit, 0, ',', '.');
            })
            ->editColumn('prosentase_omset', function ($row) {
                return number_format($row->prosentase_omset, 0, ',', '.') . ' %';
            })
            ->editColumn('prosentase_profit', function ($row) {
                return number_format($row->prosentase_profit, 0, ',', '.') . ' %';
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                        </ul>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function get_data_branch_transaction(Request $request)
    {
        $dr_tgl = $request->dr_tgl ?? date('Y-01-01');
        $sp_tgl = $request->sp_tgl ?? date('Y-12-31');
        $data   = BranchTransaction::getAllBranchTransaction($dr_tgl, $sp_tgl);
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('total_omset', function ($row) {
                return number_format($row->total_omset, 0, ',', '.');
            })
            ->editColumn('profit', function ($row) {
                return number_format($row->profit, 0, ',', '.');
            })
            ->editColumn('prosentase_omset', function ($row) {
                return number_format($row->prosentase_omset, 0, ',', '.') . ' %';
            })
            ->editColumn('prosentase_profit', function ($row) {
                return number_format($row->prosentase_profit, 0, ',', '.') . ' %';
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                        </ul>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function get_data_branch_product(Request $request)
    {
        $dr_tgl = $request->dr_tgl ?? date('Y-01-01');
        $sp_tgl = $request->sp_tgl ?? date('Y-12-31');
        $data   = BranchProduct::getAllBranchProduct($dr_tgl, $sp_tgl);
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('branch', function ($row) {
                switch ($row->branch_id) {
                    case 1:
                        return '<span class="badge badge-light-primary">' . $row->branch . '</span>';
                        break;
                    case 2:
                        return '<span class="badge badge-light-success">' . $row->branch . '</span>';
                        break;
                    case 3:
                        return '<span class="badge badge-light-warning">' . $row->branch . '</span>';
                        break;
                    case 4:
                        return '<span class="badge badge-light-info">' . $row->branch . '</span>';
                        break;
                    default:
                        return '<span class="badge badge-light-danger">Belum Terdaftar</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                        </ul>
                    </div>';
            })
            ->rawColumns(['action', 'branch'])
            ->make(true);
    }

    public function get_data_customer_product(Request $request)
    {
        $dr_tgl = $request->dr_tgl ?? date('Y-01-01');
        $sp_tgl = $request->sp_tgl ?? date('Y-12-31');
        $data   = CustomerProduct::getAllCustomerProduct($dr_tgl, $sp_tgl);
        return DataTables::of($data)
            ->editColumn('nama', function ($row) {
                return $row->nama ?? 'Pelanggan Umum';
            })
            ->editColumn('branch', function ($row) {
                switch ($row->branch_id) {
                    case 1:
                        return '<span class="badge badge-light-primary">' . $row->branch . '</span>';
                        break;
                    case 2:
                        return '<span class="badge badge-light-success">' . $row->branch . '</span>';
                        break;
                    case 3:
                        return '<span class="badge badge-light-warning">' . $row->branch . '</span>';
                        break;
                    case 4:
                        return '<span class="badge badge-light-info">' . $row->branch . '</span>';
                        break;
                    default:
                        return '<span class="badge badge-light-danger">Belum Terdaftar</span>';
                }
            })
            ->editColumn('gender', function ($row) {
                if ($row->gender == 'male') {
                    return '<span class="badge badge-light-primary">Laki-laki</span>';
                } else if ($row->gender == 'female') {
                    return '<span class="badge badge-light-success">Perempuan</span>';
                } else {
                    return '<span class="badge badge-light-danger">-</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="dropstart">
                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Aksi">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu p-1" style="min-width: 40px; z-index: 1050;">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </li>
                        </ul>
                    </div>';
            })
            ->rawColumns(['action', 'branch', 'gender'])
            ->make(true);
    }

    public function get_data_barang_buang(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-01-01');
        $endDate   = $request->end_date ?? date('Y-12-31');

        $query = SortirDetail::query()
            ->join('sortir_transaction as B', 'sortir_transaction_detail.sortir_id', '=', 'B.id')
            ->join('products as C', 'sortir_transaction_detail.product_id', '=', 'C.id')
            ->join('product_units as D', 'C.product_unit', '=', 'D.id')
            ->select(
                'sortir_transaction_detail.product_id',
                'B.branch_id',
                DB::raw('SUM(sortir_transaction_detail.quantity) as quantity'),
                'C.name',
                'D.id as unit_id',
                'D.abbreviation as satuan',
                'D.name as product_unit',
                DB::raw('AVG(sortir_transaction_detail.price) as hpp'),
                DB::raw('SUM(sortir_transaction_detail.subtotal) as total_hpp')
            )->whereBetween('B.date', [$startDate, $endDate]);

        if ($request->has('branch_id') && $request->branch_id !== 'all') {
            $query->where('branch_id', $request->branch_id);
        }

        $grandTotalQuery = clone $query;
        $grandTotal      = $grandTotalQuery->sum('sortir_transaction_detail.subtotal');

        $query = $query->groupBy('sortir_transaction_detail.product_id')->orderByDesc('total_hpp');

        return DataTables::of($query)
            ->editColumn('satuan', function ($row) {
                switch ($row->unit_id) {
                    case 1:
                        return '<span class="badge badge-light-primary">' . $row->satuan . '</span>';
                        break;
                    case 2:
                        return '<span class="badge badge-light-success">' . $row->satuan . '</span>';
                        break;
                    case 3:
                        return '<span class="badge badge-light-warning">' . $row->satuan . '</span>';
                        break;
                    case 4:
                        return '<span class="badge badge-light-info">' . $row->satuan . '</span>';
                        break;
                    default:
                        return '<span class="badge badge-light-danger">' . $row->satuan . '</span>';
                }
            })
            ->editColumn('quantity', function ($row) {
                return number_format($row->quantity, 2);
            })
            ->editColumn('hpp', function ($row) {
                return number_format($row->hpp, 2);
            })
            ->editColumn('total_hpp', function ($row) {
                return number_format($row->quantity * $row->hpp, 2);
            })
            ->rawColumns(['satuan'])
            ->with([
                'grand_total' => 'Rp. ' . number_format($grandTotal, 0, ',', '.'),
            ])
            ->make(true);
    }

    public function get_data_product_sales(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-m-d');
        $endDate   = $request->end_date ?? date('Y-m-d');

        // Query utama
        $data = PosDetailModel::select(
            'pos_transaction_detail.product_id',
            'products.name',
            'product_units.abbreviation as unit',
            DB::raw('COUNT(pos_transaction_detail.product_id) AS total_beli'),
            DB::raw('SUM(pos_transaction_detail.quantity) AS quantity'),
            DB::raw('SUM(pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0)) AS total'),
            DB::raw("
            ROUND(
                (SUM(pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0)) * 100.0) /
                SUM(SUM(pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0))) OVER (),
                2
            ) AS persentase_penjualan
        ")
        )
            ->join('products', 'pos_transaction_detail.product_id', '=', 'products.id')
            ->leftJoin('product_units', 'products.product_unit', '=', 'product_units.id')
            ->join('pos_transaction', 'pos_transaction_detail.pos_id', '=', 'pos_transaction.id')
            ->whereBetween('pos_transaction.date', [$startDate, $endDate])
            ->whereNull('pos_transaction_detail.deleted_at')  // hanya yang belum dihapus
            ->where('pos_transaction.status', '!=', 'draft'); // status bukan draft

        if ($request->has('branch_id') && $request->branch_id != 'all') {
            $data = $data->where('pos_transaction.branch_id', $request->branch_id);
        }

        $this->applyProductSalesSearch($data, $request);

        // Clone query untuk menghitung grand total
        $grandTotalQuery = clone $data;
        $grandTotal      = $grandTotalQuery->sum(DB::raw('pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0)'));

        // Grouping dan urutan data
        $data = $data->groupBy('pos_transaction_detail.product_id', 'products.name', 'product_units.abbreviation')
            ->orderByDesc('total');

        return DataTables::of($data)
            ->filter(function ($queryInstance) use ($request) {
                $this->applyProductSalesSearch($queryInstance, $request);
            })
            ->editColumn('price', function ($row) {
                $qty = (float)$row->quantity;
                $price = $qty > 0 ? $row->total / $qty : 0;
                return 'Rp ' . number_format($price, 0, ',', '.');
            })
            ->editColumn('qty_formatted', function ($row) {
                // Return integer if no decimal part, otherwise up to 2 decimals
                return round($row->quantity, 2);
            })
            ->editColumn('total_formatted', function ($row) {
                return 'Rp ' . number_format($row->total, 0, ',', '.');
            })
            ->editColumn('total', function ($row) {
                return 'Rp. ' . number_format($row->total, 0, ',', '.');
            })
            ->editColumn('persentase_penjualan', function ($row) {
                return number_format($row->persentase_penjualan, 2, ',', '.') . ' %';
            })
            ->with([
                'grand_total' => 'Rp. ' . number_format($grandTotal, 0, ',', '.'),
            ])
            ->make(true);
    }

    private function applyProductSalesSearch($query, Request $request): void
    {
        $searchValue = trim((string) data_get($request->input('search'), 'value', ''));

        if ($searchValue !== '') {
            $query->where('products.name', 'like', '%' . $searchValue . '%');
        }
    }

    public function get_data_total_aset(Request $request)
    {
        $startDate = $request->start_date ?? date('Y-01-01');
        $endDate   = $request->end_date ?? date('Y-12-31');

        $lastHpp = DB::table('product_hpp as ph')
            ->select('ph.*')
            ->join(
                DB::raw('(
                    SELECT product_id, MAX(created_at) AS last_created
                    FROM product_hpp
                    GROUP BY product_id
                ) as last'),
                function ($join) {
                    $join->on('ph.product_id', '=', 'last.product_id')
                        ->on('ph.created_at', '=', 'last.last_created');
                }
            );
        // Query utama
        $query = DB::table('transaction_stock as A')
            ->select([
                DB::raw('COALESCE(pc.parent_id, A.product_id) as product_id'),
                'PARENT.name as name',
                'C.abbreviation',
                'PARENT.hpp',
                DB::raw('SUM(A.quantity) as total_stock'),
                // DB::raw('(SUM(A.quantity) * PARENT.hpp) as total_hpp'),

                DB::raw('COALESCE(hpp_last.total_aset_berjalan, 0) as total_hpp'),
            ])
            ->join('products as CHILD', 'A.product_id', '=', 'CHILD.id')
            ->leftJoin('product_child as pc', 'CHILD.id', '=', 'pc.product_id')
            ->join(
                DB::raw('products as PARENT'),
                DB::raw('PARENT.id'),
                '=',
                DB::raw('COALESCE(pc.parent_id, CHILD.id)')
            )
            ->join('product_units as C', 'PARENT.product_unit', '=', 'C.id')

            ->leftJoinSub($lastHpp, 'hpp_last', function ($join) {
                $join->on(
                    DB::raw('hpp_last.product_id'),
                    '=',
                    DB::raw('COALESCE(pc.parent_id, A.product_id)')
                );
            })
            ->where('PARENT.tipe', '!=', 'parcel');

        if ($request->has('branch_id') && $request->branch_id != 'all') {
            $query->where('A.branch_id', $request->branch_id);
        }

        $query->groupBy(
            DB::raw('COALESCE(pc.parent_id, A.product_id)'),
            'PARENT.name',
            'C.abbreviation',
            'PARENT.hpp'
        );
            // ->having('total_stock', '>', 0);

        // GRAND TOTAL HPP (semua baris yang tampil)
        $grandTotal = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->sum('total_hpp');

        return DataTables::of($query)
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('PARENT.name', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('abbreviation', function ($query, $keyword) {
                $query->where('C.abbreviation', 'like', '%' . $keyword . '%');
            })
            ->filterColumn('hpp', function ($query, $keyword) {
                $normalizedKeyword = str_replace(',', '.', $keyword);
                $query->whereRaw('CAST(PARENT.hpp AS CHAR) LIKE ?', ['%' . $normalizedKeyword . '%']);
            })
            ->filterColumn('total_stock', function ($query, $keyword) {
                $normalizedKeyword = preg_replace('/[^0-9.,-]/', '', $keyword);
                $query->havingRaw('CAST(SUM(A.quantity) AS CHAR) LIKE ?', ['%' . $normalizedKeyword . '%']);
            })
            ->filterColumn('total_hpp', function ($query, $keyword) {
                $normalizedKeyword = str_replace(',', '.', $keyword);
                $query->whereRaw('CAST(COALESCE(hpp_last.total_aset_berjalan, 0) AS CHAR) LIKE ?', ['%' . $normalizedKeyword . '%']);
            })
            ->editColumn('total_hpp', function ($row) {
                return 'Rp' . number_format($row->total_hpp, 0, ',', '.');
            })
            ->editColumn('hpp', function ($row) {
                return 'Rp' . number_format($row->hpp, 0, ',', '.');
            })
            ->editColumn('total_stock', function ($row) {
                return number_format($row->total_stock, 0, ',', '.');
            })
            ->addColumn('action', function ($item) {
                return '
                    <a href="' . url('product-stock') . '/' . $item->product_id . '/show' . '" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="tooltip" title="View">
                        <i class="fa fa-eye"></i>
                    </a>
                ';
            })
            ->with([
                'grand_total' => number_format($grandTotal, 0, ',', '.'),
            ])
            ->make(true);
    }

    public function get_product_sales_history(Request $request)
    {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $branch    = $request->branch ?: 'all';
        $productId = $request->product_id;

        $query = \Modules\Pos\Entities\PosDetailModel::select(
            'pos_transaction_detail.*',
            'pos_transaction.id as pos_id',
            'pos_transaction.invoice_number as invoice',
            'pos_transaction.created_at as tx_date',
            'pos_transaction.date as pos_date',
            'branch.name as branch_name',
            'products.name as product_name',
            'product_units.abbreviation as unit'
        )
            ->join('pos_transaction', 'pos_transaction_detail.pos_id', '=', 'pos_transaction.id')
            ->join('branch', 'pos_transaction.branch_id', '=', 'branch.id')
            ->join('products', 'pos_transaction_detail.product_id', '=', 'products.id')
            ->leftJoin('product_units', 'products.product_unit', '=', 'product_units.id')
            ->where('pos_transaction_detail.product_id', $productId)
            ->whereNull('pos_transaction_detail.deleted_at')
            ->whereNull('pos_transaction.deleted_at')
            ->with(['pos.paymentDetails.paymentMethod']); // to get payment methods

        if ($startDate && $endDate) {
            $query->whereBetween('pos_transaction.date', [$startDate, $endDate]);
        }

        if ($branch !== 'all') {
            $query->where('pos_transaction.branch_id', $branch);
        }

        $history = $query->orderBy('pos_transaction.created_at', 'desc')->get();

        // Map data to the format needed by the drawer
        $formattedData = $history->map(function ($detail) {
            $paymentMethods = collect();
            if ($detail->pos && $detail->pos->paymentDetails) {
                $paymentMethods = $detail->pos->paymentDetails->map(function($payment) {
                    return $payment->paymentMethod ? $payment->paymentMethod->name : 'Tunai';
                })->filter()->unique()->values();
            }
            
            if ($paymentMethods->count() > 1) {
                $paymentStr = 'Split (' . $paymentMethods->implode(' & ') . ')';
            } elseif ($paymentMethods->count() == 1) {
                $paymentStr = $paymentMethods[0];
            } else {
                $paymentStr = '-';
            }

            $txDate = \Carbon\Carbon::parse($detail->tx_date);
            $subtotal = $detail->price * $detail->quantity;
            $discount = $detail->discount ?? 0;
            $diskon_global = $detail->diskon_global ?? 0;
            $total_discount = $discount + $diskon_global;
            $total = $detail->subtotal - $diskon_global;
            
            $unit = $detail->unit ? $detail->unit : 'pcs';
            $qty_formatted = round($detail->quantity, 2) . ' ' . $unit;

            return [
                'invoice' => $detail->invoice,
                'date_formatted' => $txDate->locale('id')->isoFormat('dddd, D MMMM Y'),
                'time_formatted' => 'Jam ' . $txDate->format('H:i') . ' WIB',
                'branch_name' => $detail->branch_name,
                'payment' => $paymentStr,
                'qty' => $qty_formatted,
                'subtotal' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
                'product_discount' => $discount > 0 ? '- Rp ' . number_format($discount, 0, ',', '.') : null,
                'prorata_discount' => $diskon_global > 0 ? '- Rp ' . number_format($diskon_global, 0, ',', '.') : null,
                'total' => 'Rp ' . number_format($total, 0, ',', '.'),
                'pos_id' => $detail->pos_id,
                'pos_date' => $detail->pos_date,
                'tx_date' => $detail->tx_date,
            ];
        });

        return response()->json([
            'status' => 'success',
            'product_name' => $history->first() ? $history->first()->product_name : '',
            'data' => $formattedData
        ]);
    }
}
