<?php
namespace Modules\Report\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Kurir;
use Modules\Master\Entities\UserBranch;
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

    private function scopeReportBranch($query, Request $request, string $column, string $parameter = 'branch_id'): void
    {
        $requested = (string) $request->input($parameter, 'all');
        $allowed = array_map('intval', UserBranch::getUserBranch());
        $isAdmin = \App\Models\RoleMenu::currentRoleId() === 1;

        if ($requested === 'null') {
            abort_unless($isAdmin, 403);
            $query->whereNull($column);
        } elseif ($requested !== 'all' && $requested !== '') {
            abort_unless(ctype_digit($requested) && in_array((int) $requested, $allowed, true), 403);
            $query->where($column, (int) $requested);
        } elseif (! $isAdmin) {
            $query->whereIn($column, $allowed);
        }
    }

    private function authorizeLegacyAggregate(string $permission): void
    {
        $this->authorizeAccess($permission);
        // Stored procedures aggregate all branches before PHP can filter them.
        abort_unless(\App\Models\RoleMenu::currentRoleId() === 1, 403);
    }

    private function applyReportSearch($query, Request $request, array $nameColumns, ?string $dateColumn = null): void
    {
        $searchValue = trim((string) data_get($request->input('search'), 'value', ''));
        if ($searchValue === '') return;

        $like = '%' . $searchValue . '%';
        $dateLabelSql = $dateColumn ? $this->indonesianDateLabelSql($dateColumn) : null;

        $query->where(function ($searchQuery) use ($nameColumns, $like, $dateLabelSql) {
            foreach ($nameColumns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $searchQuery->{$method}($column, 'like', $like);
            }

            if ($dateLabelSql) $searchQuery->orWhereRaw($dateLabelSql . ' LIKE ?', [$like]);
        });
    }

    private function applyDateBounds($query, string $column, Request $request): void
    {
        if ($request->filled('start_date')) $query->whereDate($column, '>=', $request->input('start_date'));
        if ($request->filled('end_date')) $query->whereDate($column, '<=', $request->input('end_date'));
    }

    private function indonesianDateLabelSql(string $column): string
    {
        return "LOWER(CONCAT(
            CASE DAYOFWEEK({$column})
                WHEN 1 THEN 'minggu' WHEN 2 THEN 'senin' WHEN 3 THEN 'selasa'
                WHEN 4 THEN 'rabu' WHEN 5 THEN 'kamis' WHEN 6 THEN 'jumat' WHEN 7 THEN 'sabtu'
            END,
            ', ', DATE_FORMAT({$column}, '%d '),
            CASE MONTH({$column})
                WHEN 1 THEN 'januari' WHEN 2 THEN 'februari' WHEN 3 THEN 'maret'
                WHEN 4 THEN 'april' WHEN 5 THEN 'mei' WHEN 6 THEN 'juni'
                WHEN 7 THEN 'juli' WHEN 8 THEN 'agustus' WHEN 9 THEN 'september'
                WHEN 10 THEN 'oktober' WHEN 11 THEN 'november' WHEN 12 THEN 'desember'
            END,
            ' ', DATE_FORMAT({$column}, '%Y')
        ))";
    }

    public function hub()
    {
        $reportPermissions = [
            'report.total.aset', 'report.product.sales', 'report.profit.revenue',
            'report.profit.adjusted', 'report.shipping.cost', 'report.product.buang',
        ];

        abort_unless(collect($reportPermissions)->contains(fn ($permission) => $this->hasAccess($permission)), 403);

        return view('report::hub');
    }

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
        $this->authorizeLegacyAggregate('report.customer.transaction');

        return view('report::customer-transaction-rep');
    }

    public function branch_transaction(Request $request)
    {
        $this->authorizeLegacyAggregate('report.branch.transaction');

        return view('report::branch-transaction-rep');
    }

    public function branch_product(Request $request)
    {
        $this->authorizeLegacyAggregate('report.branch.product');

        return view('report::branch-product-rep');
    }
    public function customer_product(Request $request)
    {
        $this->authorizeLegacyAggregate('report.customer.product');

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
    public function profit_revenue(Request $request)
    {
        if ($denied = $this->requireAccess('report.profit.revenue')) {
            return $denied;
        }

        $data['branches']    = Branch::all();
        $data['defaultDate'] = date('Y-m-d');
        return view('report::profit-revenue', $data);
    }
    public function profit_adjusted(Request $request)
    {
        if ($denied = $this->requireAccess('report.profit.adjusted')) {
            return $denied;
        }

        $data['branches']    = Branch::all();
        $data['defaultDate'] = date('Y-m-d');
        return view('report::profit-adjusted', $data);
    }
    public function shipping_cost(Request $request)
    {
        if ($denied = $this->requireAccess('report.shipping.cost')) {
            return $denied;
        }

        $data['branches']    = Branch::all();
        $data['couriers']    = Kurir::query()->orderBy('name')->get();
        $data['defaultDate'] = date('Y-m-d');
        return view('report::shipping-cost', $data);
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
        $this->authorizeAccess('report.transaction');
        $data = CustomerTransaction::query();
        $this->scopeReportBranch($data, $request, 'branch_id');

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
        $this->authorizeLegacyAggregate('report.customer.transaction');
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
        $this->authorizeLegacyAggregate('report.branch.transaction');
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
        $this->authorizeLegacyAggregate('report.branch.product');
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
        $this->authorizeLegacyAggregate('report.customer.product');
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
        $this->authorizeAccess('report.product.buang');

        $startDate = $request->start_date ?? date('Y-01-01');
        $endDate   = $request->end_date ?? date('Y-12-31');

        $query = SortirDetail::query()
            ->join('sortir_transaction as B', 'sortir_transaction_detail.sortir_id', '=', 'B.id')
            ->join('products as C', 'sortir_transaction_detail.product_id', '=', 'C.id')
            ->join('product_units as D', 'C.product_unit', '=', 'D.id')
            ->select(
                'sortir_transaction_detail.product_id',
                DB::raw('SUM(sortir_transaction_detail.quantity) as quantity'),
                'C.name',
                'D.id as unit_id',
                'D.abbreviation as satuan',
                'D.name as product_unit',
                DB::raw('sortir_transaction_detail.price as hpp'),
                DB::raw('SUM(sortir_transaction_detail.subtotal) as total_hpp')
            )->whereBetween('B.date', [$startDate, $endDate]);

        if ($request->has('branch_id') && $request->branch_id !== 'all') {
            if ($request->branch_id === 'null') $query->whereNull('B.branch_id');
            else $query->where('B.branch_id', $request->branch_id);
        }
        $this->scopeReportBranch($query, $request, 'B.branch_id');

        $this->applyReportSearch($query, $request, ['C.name'], 'B.date');

        $grandTotalQuery = clone $query;
        $grandTotal      = $grandTotalQuery->sum('sortir_transaction_detail.subtotal');

        $query = $query->groupBy('sortir_transaction_detail.product_id', 'sortir_transaction_detail.price', 'C.name', 'D.id', 'D.abbreviation', 'D.name')->orderByDesc('total_hpp');

        return DataTables::of($query)
            ->filter(function ($queryInstance) {
                // Search is already applied before groupBy
            })
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
                return fmod($row->quantity, 1) == 0 ? number_format($row->quantity, 0, ',', '.') : number_format($row->quantity, 2, ',', '.');
            })
            ->editColumn('hpp', function ($row) {
                return number_format($row->hpp, 0, ',', '.');
            })
            ->editColumn('total_hpp', function ($row) {
                return number_format($row->total_hpp, 0, ',', '.');
            })
            ->rawColumns(['satuan'])
            ->with([
                'grand_total' => 'Rp. ' . number_format($grandTotal, 0, ',', '.'),
            ])
            ->make(true);
    }

    public function get_data_product_sales(Request $request)
    {
        $this->authorizeAccess('report.product.sales');

        $startDate = $request->filled('start_date') ? $request->start_date : date('Y-m-d');
        $endDate   = $request->filled('end_date') ? $request->end_date : date('Y-m-d');

        // Query utama
        $data = PosDetailModel::select(
            'pos_transaction_detail.product_id',
            'pos_transaction_detail.price',
            DB::raw('pos_transaction_detail.price as price_value'),
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
            ->whereNull('pos_transaction.deleted_at')
            ->whereIn('pos_transaction.status', ['paid', 'debt']); // status bukan draft

        if ($request->has('branch_id') && $request->branch_id != 'all') {
            if ($request->branch_id === 'null') $data->whereNull('pos_transaction.branch_id');
            else $data->where('pos_transaction.branch_id', $request->branch_id);
        }
        $this->scopeReportBranch($data, $request, 'pos_transaction.branch_id');

        $this->applyProductSalesSearch($data, $request);

        // Grouping dan urutan data
        $data = $data->groupBy('pos_transaction_detail.product_id', 'pos_transaction_detail.price', 'products.name', 'product_units.abbreviation')
            ->orderByDesc('total');

        // Clone query untuk menghitung grand total dari data yang sudah di-group dan di-filter
        $response = DataTables::of($data)
            ->filter(function ($queryInstance) use ($request) {
                $this->applyProductSalesSearch($queryInstance, $request);
            });

        if ($request->input('start') == 0) {
            $grandTotalQuery = clone $data;
            $grandTotal = DB::table(DB::raw("({$grandTotalQuery->toSql()}) as sub"))
                ->mergeBindings($grandTotalQuery->getQuery())
                ->sum('total');

            $response->with([
                'grand_total' => 'Rp. ' . number_format($grandTotal, 0, ',', '.'),
            ]);
        }

        return $response->editColumn('price', function ($row) {
                return 'Rp ' . number_format((float) $row->price, 0, ',', '.');
            })
            ->editColumn('qty_formatted', function ($row) {
                return (int) $row->quantity == $row->quantity ? number_format($row->quantity, 0, ',', '.') : number_format($row->quantity, 2, ',', '.');
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
            ->make(true);
    }

    public function get_data_profit_revenue(Request $request)
    {
        $this->authorizeAccess('report.profit.revenue');

        $startDate = $request->filled('start_date') ? $request->start_date : date('Y-m-d');
        $endDate   = $request->filled('end_date') ? $request->end_date : date('Y-m-d');

        $data = \Modules\Pos\Entities\PosDetailModel::select(
            'pos_transaction_detail.product_id',
            'pos_transaction_detail.price',
            'products.name',
            'product_units.abbreviation as unit',
            DB::raw('SUM(pos_transaction_detail.quantity) AS quantity'),
            DB::raw('SUM(pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0)) AS total_pendapatan'),
            DB::raw('SUM(COALESCE(pos_transaction_detail.subtotal_hpp, 0)) AS total_hpp'),
            DB::raw('SUM(pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0)) - SUM(COALESCE(pos_transaction_detail.subtotal_hpp, 0)) AS laba_kotor')
        )
            ->join('products', 'pos_transaction_detail.product_id', '=', 'products.id')
            ->leftJoin('product_units', 'products.product_unit', '=', 'product_units.id')
            ->join('pos_transaction', 'pos_transaction_detail.pos_id', '=', 'pos_transaction.id')
            ->whereBetween('pos_transaction.date', [$startDate, $endDate])
            ->whereNull('pos_transaction_detail.deleted_at')
            ->whereNull('pos_transaction.deleted_at')
            ->whereIn('pos_transaction.status', ['paid', 'debt']);

        if ($request->has('branch_id') && $request->branch_id != 'all') {
            if ($request->branch_id === 'null') $data->whereNull('pos_transaction.branch_id');
            else $data->where('pos_transaction.branch_id', $request->branch_id);
        }
        $this->scopeReportBranch($data, $request, 'pos_transaction.branch_id');

        $this->applyReportSearch($data, $request, ['products.name'], 'pos_transaction.date');

        $data = $data->groupBy('pos_transaction_detail.product_id', 'pos_transaction_detail.price', 'products.name', 'product_units.abbreviation')
            ->orderByDesc('laba_kotor');

        $response = DataTables::of($data);

        if ($request->input('start') == 0) {
            $grandTotalQuery = clone $data;
            
            $subQuery = DB::table(DB::raw("({$grandTotalQuery->toSql()}) as sub"))
                ->mergeBindings($grandTotalQuery->getQuery());
                
            $grandTotalPendapatan = $subQuery->sum('total_pendapatan');
            $grandTotalHpp = $subQuery->sum('total_hpp');
            $grandTotalLaba = $subQuery->sum('laba_kotor');
            
            $labaPercentage = $grandTotalPendapatan > 0 ? ($grandTotalLaba / $grandTotalPendapatan) * 100 : 0;

            $response->with([
                'grand_total_pendapatan' => 'Rp ' . number_format($grandTotalPendapatan, 0, ',', '.'),
                'grand_total_hpp' => '- Rp ' . number_format($grandTotalHpp, 0, ',', '.'),
                'grand_total_laba' => 'Rp ' . number_format($grandTotalLaba, 0, ',', '.'),
                'laba_percentage' => number_format($labaPercentage, 1, ',', '.') . '%',
            ]);
        }

        return $response
            ->editColumn('qty_formatted', function ($row) {
                return fmod($row->quantity, 1) == 0 ? number_format($row->quantity, 0, ',', '.') : number_format($row->quantity, 2, ',', '.');
            })
            ->editColumn('pendapatan_formatted', function ($row) {
                return 'Rp ' . number_format($row->total_pendapatan, 0, ',', '.');
            })
            ->editColumn('hpp_formatted', function ($row) {
                return 'Rp ' . number_format($row->total_hpp, 0, ',', '.');
            })
            ->editColumn('laba_formatted', function ($row) {
                return 'Rp ' . number_format($row->laba_kotor, 0, ',', '.');
            })
            ->make(true);
    }

    public function get_data_shipping_cost(Request $request)
    {
        $this->authorizeAccess('report.shipping.cost');

        $startDate = $request->filled('start_date') ? $request->start_date : date('Y-m-d');
        $endDate   = $request->filled('end_date') ? $request->end_date : date('Y-m-d');
        $shippingDiscount = 'CASE WHEN COALESCE(pos_transaction.ongkir_discount, 0) BETWEEN 1 AND 100 THEN COALESCE(pos_transaction.ongkir, 0) * pos_transaction.ongkir_discount / 100 ELSE COALESCE(pos_transaction.ongkir_discount, 0) END';

        // Query
        $data = \Modules\Pos\Entities\PosModel::select(
            'pos_transaction.courier_id',
            'kurir.name as courier_name',
            'pos_transaction.branch_id',
            'branch.name as branch_name',
            DB::raw('COUNT(pos_transaction.id) AS total_transaksi'),
            DB::raw("SUM(COALESCE(pos_transaction.ongkir, 0) - ({$shippingDiscount})) AS total_ongkir")
        )
            ->leftJoin('kurir', 'pos_transaction.courier_id', '=', 'kurir.id')
            ->leftJoin('branch', 'pos_transaction.branch_id', '=', 'branch.id')
            ->whereBetween('pos_transaction.date', [$startDate, $endDate])
            ->whereNull('pos_transaction.deleted_at')
            ->where('pos_transaction.ongkir', '>', 0)
            ->whereIn('pos_transaction.status', ['paid', 'debt']);

        if ($request->has('branch_id') && $request->branch_id != 'all') {
            if ($request->branch_id === 'null') $data->whereNull('pos_transaction.branch_id');
            else $data->where('pos_transaction.branch_id', $request->branch_id);
        }
        $this->scopeReportBranch($data, $request, 'pos_transaction.branch_id');

        if ($request->filled('courier_id') && $request->courier_id !== 'all') {
            if ($request->courier_id === 'null') $data->whereNull('pos_transaction.courier_id');
            else $data->where('pos_transaction.courier_id', $request->courier_id);
        }

        $searchValue = trim((string) data_get($request->input('search'), 'value', ''));
        if ($searchValue !== '') {
            $data->where(function ($query) use ($searchValue) {
                $query->where('kurir.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('branch.name', 'like', '%' . $searchValue . '%');
            });
        }

        // Grouping
        $data = $data->groupBy('pos_transaction.courier_id', 'kurir.name', 'pos_transaction.branch_id', 'branch.name')
            ->orderBy('kurir.name')
            ->orderBy('branch.name');

        $response = DataTables::of($data);

        if ($request->input('start') == 0) {
            $grandTotalQuery = clone $data;
            $grandTotal = DB::table(DB::raw("({$grandTotalQuery->toSql()}) as sub"))
                ->mergeBindings($grandTotalQuery->getQuery())
                ->sum('total_ongkir');

            $response->with([
                'grand_total' => 'Rp. ' . number_format($grandTotal, 0, ',', '.'),
            ]);
        }

        return $response
            ->editColumn('courier_name', function ($row) {
                return $row->courier_name ?? 'Tanpa Kurir / Lainnya';
            })
            ->editColumn('total_transaksi', function ($row) {
                return number_format($row->total_transaksi, 0, ',', '.') . ' Transaksi';
            })
            ->editColumn('total_ongkir_formatted', function ($row) {
                return 'Rp ' . number_format($row->total_ongkir, 0, ',', '.');
            })
            ->make(true);
    }

    public function get_shipping_cost_history(Request $request)
    {
        $this->authorizeAccess('report.shipping.cost');

        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $branch    = $request->branch ?: 'all';
        $courierId = $request->courier_id;

        $query = \Modules\Pos\Entities\PosModel::select(
            'pos_transaction.*',
            'pos_transaction.id as pos_id',
            'pos_transaction.invoice_number as invoice',
            'pos_transaction.created_at as tx_date',
            'pos_transaction.date as pos_date',
            'branch.name as branch_name',
            'kurir.name as courier_name',
            'customer.name as customer_name'
        )
            ->leftJoin('branch', 'pos_transaction.branch_id', '=', 'branch.id')
            ->leftJoin('kurir', 'pos_transaction.courier_id', '=', 'kurir.id')
            ->leftJoin('customer', 'pos_transaction.customer_id', '=', 'customer.id')
            ->whereNull('pos_transaction.deleted_at')
            ->where('pos_transaction.ongkir', '>', 0)
            ->whereIn('pos_transaction.status', ['paid', 'debt']);
            
        if ($request->filled('courier_id') && $courierId !== 'all') {
            if ($courierId === 'null') $query->whereNull('pos_transaction.courier_id');
            else $query->where('pos_transaction.courier_id', $courierId);
        }
            
        $query->with(['paymentDetails.paymentMethod']);

        $this->applyDateBounds($query, 'pos_transaction.date', $request);

        if ($branch !== 'all') {
            if ($branch === 'null') $query->whereNull('pos_transaction.branch_id');
            else $query->where('pos_transaction.branch_id', $branch);
        }
        $this->scopeReportBranch($query, $request, 'pos_transaction.branch_id', 'branch');

        $history = $query->orderBy('pos_transaction.created_at', 'desc')->get();

        $formattedData = $history->map(function ($detail) {
            $paymentMethods = collect();
            if ($detail->paymentDetails) {
                $paymentMethods = $detail->paymentDetails->filter(function($payment) {
                    return $payment->payment_amount > 0;
                })->map(function($payment) {
                    $method = $payment->payment_method;
                    if (empty($method) || strtolower($method) === 'tunai') {
                        return 'Tunai';
                    }
                    if ($method === 'Split') {
                        return 'Split';
                    }
                    $decoded = json_decode($method, true);
                    if (is_array($decoded) && count($decoded) > 0) {
                        return collect($decoded)->implode(', ');
                    }
                    return $method;
                })->filter()->unique()->values();
            }
            
            if ($paymentMethods->isEmpty()) {
                $paymentMethods->push('Tunai');
            }

            if ($paymentMethods->count() > 1) {
                $paymentStr = $paymentMethods->implode(', ');
            } elseif ($paymentMethods->count() == 1) {
                $paymentStr = $paymentMethods[0];
            } else {
                $paymentStr = '-';
            }

            $txDate = \Carbon\Carbon::parse($detail->tx_date);
            $ongkir = $detail->ongkir ?? 0;
            $rawShippingDiscount = (float) ($detail->ongkir_discount ?? 0);
            $ongkirDiscount = $rawShippingDiscount > 0 && $rawShippingDiscount <= 100
                ? $ongkir * $rawShippingDiscount / 100
                : $rawShippingDiscount;
            $customerName = $detail->customer_name ?? 'Pelanggan Umum';
            $address = $detail->ongkir_address ?? '-';
            
            return [
                'invoice' => $detail->invoice,
                'date_formatted' => $txDate->locale('id')->isoFormat('dddd, D MMMM Y'),
                'time_formatted' => 'Jam ' . $txDate->format('H:i') . ' WIB',
                'branch_name' => $detail->branch_name,
                'payment' => $paymentStr,
                'payment_methods' => $this->historyPaymentMethods($detail->paymentDetails),
                'customer_name' => $customerName,
                'address' => $address,
                'ongkir' => 'Rp ' . number_format($ongkir, 0, ',', '.'),
                'ongkir_discount' => $ongkirDiscount > 0 ? '- Rp ' . number_format($ongkirDiscount, 0, ',', '.') : null,
                'total_bersih' => 'Rp ' . number_format($ongkir - $ongkirDiscount, 0, ',', '.'),
                'total' => 'Rp ' . number_format($detail->total, 0, ',', '.'),
                'pos_id' => $detail->pos_id,
                'pos_date' => $detail->pos_date,
                'tx_date' => $detail->tx_date,
            ];
        });

        return response()->json([
            'status' => 'success',
            'courier_name' => $history->first() ? ($history->first()->courier_name ?? 'Tanpa Kurir / Lainnya') : '',
            'data' => $formattedData
        ]);
    }

    private function applyProductSalesSearch($query, Request $request): void
    {
        $this->applyReportSearch($query, $request, ['products.name'], 'pos_transaction.date');
    }

    public function get_data_total_aset(Request $request)
    {
        $this->authorizeAccess('report.total.aset');

        $branchId = (string) $request->input('branch_id', 'all');
        $searchValue = trim((string) data_get($request->input('search'), 'value', ''));

        // Evaluate the moving-HPP view once per request instead of embedding it
        // multiple times in the grouped stock query and DataTables count query.
        $latestHppByProduct = DB::table('product_hpp')
            ->select('product_id', 'total_aset_berjalan', 'hpp_berjalan', 'created_at')
            ->orderByDesc('created_at')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');

        $stockQuery = DB::table('transaction_stock as A')
            ->join('products as CHILD', 'A.product_id', '=', 'CHILD.id')
            ->leftJoin('product_child as pc', 'CHILD.id', '=', 'pc.product_id')
            ->join('products as PARENT', 'PARENT.id', '=', DB::raw('COALESCE(pc.parent_id, CHILD.id)'))
            ->join('product_units as C', 'PARENT.product_unit', '=', 'C.id')
            ->where('PARENT.tipe', '!=', 'parcel')
            ->selectRaw('COALESCE(pc.parent_id, A.product_id) as product_id')
            ->selectRaw('PARENT.name as name, C.abbreviation, PARENT.hpp')
            ->selectRaw('SUM(A.quantity) as total_stock')
            ->groupByRaw('COALESCE(pc.parent_id, A.product_id), PARENT.name, C.abbreviation, PARENT.hpp');

        if ($branchId !== 'all') {
            if ($branchId === 'null') $stockQuery->whereNull('A.branch_id');
            else $stockQuery->where('A.branch_id', $branchId);
        }
        $this->scopeReportBranch($stockQuery, $request, 'A.branch_id');

        $stockRows = $stockQuery->get()->map(function ($row) use ($latestHppByProduct, $branchId) {
            $hppRow = $latestHppByProduct->get($row->product_id);
            $stock = (float) $row->total_stock;
            $unitHpp = (float) ($hppRow->hpp_berjalan ?? $row->hpp ?? 0);
            $assetValue = max(0, $stock * $unitHpp);

            return [
                'product_id' => (int) $row->product_id,
                'name' => $row->name,
                'abbreviation' => $row->abbreviation,
                'total_stock_raw' => $stock,
                'hpp_raw' => (float) $row->hpp,
                'total_hpp_raw' => $assetValue,
            ];
        });

        $recordsTotal = $stockRows->count();
        $filteredRows = $searchValue === ''
            ? $stockRows
            : $stockRows->filter(fn ($row) => mb_stripos($row['name'], $searchValue) !== false)->values();
        $recordsFiltered = $filteredRows->count();
        $grandTotal = $filteredRows->sum('total_hpp_raw');

        $orderColumns = ['name', 'abbreviation', 'total_stock_raw', 'hpp_raw', 'total_hpp_raw'];
        $orderIndex = (int) $request->input('order.0.column', 2);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orderColumn = $orderColumns[$orderIndex] ?? 'total_stock_raw';
        $filteredRows = $orderDirection === 'asc'
            ? $filteredRows->sortBy($orderColumn)->values()
            : $filteredRows->sortByDesc($orderColumn)->values();

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 50);
        $pageRows = $length < 0 ? $filteredRows->slice($start) : $filteredRows->slice($start, min($length, 500));
        $data = $pageRows->map(function ($row) {
            return [
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'abbreviation' => $row['abbreviation'],
                'total_stock' => number_format($row['total_stock_raw'], 2, ',', '.'),
                'hpp' => 'Rp' . number_format($row['hpp_raw'], 0, ',', '.'),
                'total_hpp' => 'Rp' . number_format($row['total_hpp_raw'], 0, ',', '.'),
                'action' => '<a href="' . url('product-stock') . '/' . $row['product_id'] . '/show" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="tooltip" title="View"><i class="fa fa-eye"></i></a>',
            ];
        })->values();

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'grand_total' => 'Rp. ' . number_format($grandTotal, 0, ',', '.'),
            'data' => $data,
        ]);
    }

    public function get_product_sales_history(Request $request)
    {
        $this->authorizeAccess('report.product.sales');

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
            ->leftJoin('branch', 'pos_transaction.branch_id', '=', 'branch.id')
            ->join('products', 'pos_transaction_detail.product_id', '=', 'products.id')
            ->leftJoin('product_units', 'products.product_unit', '=', 'product_units.id')
            ->where('pos_transaction_detail.product_id', $productId)
            ->whereNull('pos_transaction_detail.deleted_at')
            ->whereNull('pos_transaction.deleted_at')
            ->whereIn('pos_transaction.status', ['paid', 'debt'])
            ->with(['pos.paymentDetails.paymentMethod']); // to get payment methods

        $this->applyDateBounds($query, 'pos_transaction.date', $request);

        if ($request->filled('price')) $query->where('pos_transaction_detail.price', $request->input('price'));

        if ($branch !== 'all') {
            if ($branch === 'null') $query->whereNull('pos_transaction.branch_id');
            else $query->where('pos_transaction.branch_id', $branch);
        }
        $this->scopeReportBranch($query, $request, 'pos_transaction.branch_id', 'branch');

        $history = $query->orderBy('pos_transaction.created_at', 'desc')->get();

        // Map data to the format needed by the drawer
        $formattedData = $history->map(function ($detail) {
            $paymentMethods = collect();
            if ($detail->pos && $detail->pos->paymentDetails) {
                $paymentMethods = $detail->pos->paymentDetails->filter(function($payment) {
                    return $payment->payment_amount > 0;
                })->map(function($payment) {
                    $method = $payment->payment_method;
                    if (empty($method) || strtolower($method) === 'tunai') {
                        return 'Tunai';
                    }
                    if ($method === 'Split') {
                        return 'Split';
                    }
                    $decoded = json_decode($method, true);
                    if (is_array($decoded) && count($decoded) > 0) {
                        return collect($decoded)->implode(', ');
                    }
                    return $method;
                })->filter()->unique()->values();
            }
            
            if ($paymentMethods->isEmpty()) {
                $paymentMethods->push('Tunai');
            }

            if ($paymentMethods->count() > 1) {
                $paymentStr = $paymentMethods->implode(', ');
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
                'payment_methods' => $this->historyPaymentMethods($detail->pos?->paymentDetails),
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

    public function get_data_profit_adjusted(Request $request)
    {
        $this->authorizeAccess('report.profit.adjusted');

        $startDate = $request->filled('start_date') ? $request->start_date : date('Y-m-d');
        $endDate   = $request->filled('end_date') ? $request->end_date : date('Y-m-d');
        $branchId  = $request->input('branch_id', 'all');
        $mobileReport = $request->boolean('mobile_report');

        $posQuery = DB::table('pos_transaction_detail')
            ->select(
                DB::raw('COALESCE(product_child.parent_id, pos_transaction_detail.product_id) as product_id'),
                DB::raw('NULL as price'),
                DB::raw('SUM(pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0)) AS total_pendapatan'),
                DB::raw('SUM(pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0)) - SUM(COALESCE(pos_transaction_detail.subtotal_hpp, 0)) AS laba_kotor')
            )
            ->join('pos_transaction', 'pos_transaction_detail.pos_id', '=', 'pos_transaction.id')
            ->leftJoin('product_child', 'pos_transaction_detail.product_id', '=', 'product_child.product_id')
            ->join('products as report_root', 'report_root.id', '=', DB::raw('COALESCE(product_child.parent_id, pos_transaction_detail.product_id)'))
            ->whereBetween('pos_transaction.date', [$startDate, $endDate])
            ->whereNull('pos_transaction_detail.deleted_at')
            ->whereNull('pos_transaction.deleted_at')
            ->whereIn('pos_transaction.status', ['paid', 'debt']);

        if ($branchId !== 'all') {
            if ($branchId === 'null') $posQuery->whereNull('pos_transaction.branch_id');
            else $posQuery->where('pos_transaction.branch_id', $branchId);
        }
        $this->scopeReportBranch($posQuery, $request, 'pos_transaction.branch_id');
        $this->applyReportSearch($posQuery, $request, ['report_root.name'], 'pos_transaction.date');
        $posQuery->groupBy(DB::raw('COALESCE(product_child.parent_id, pos_transaction_detail.product_id)'));

        $sortirQuery = DB::table('sortir_transaction_detail')
            ->select(
                DB::raw('COALESCE(product_child.parent_id, sortir_transaction_detail.product_id) as product_id'),
                DB::raw('SUM(sortir_transaction_detail.subtotal) AS koreksi_stock')
            )
            ->join('sortir_transaction', 'sortir_transaction_detail.sortir_id', '=', 'sortir_transaction.id')
            ->leftJoin('product_child', 'sortir_transaction_detail.product_id', '=', 'product_child.product_id')
            ->whereBetween('sortir_transaction.date', [$startDate, $endDate]);

        if ($branchId !== 'all') {
            if ($branchId === 'null') $sortirQuery->whereNull('sortir_transaction.branch_id');
            else $sortirQuery->where('sortir_transaction.branch_id', $branchId);
        }
        $this->scopeReportBranch($sortirQuery, $request, 'sortir_transaction.branch_id');
        $sortirQuery->groupBy(DB::raw('COALESCE(product_child.parent_id, sortir_transaction_detail.product_id)'));

        $opnameQuery = \Modules\Transaction\Entities\StockOpname::select(
                DB::raw('COALESCE(product_child.parent_id, stock_opname.product_id) as product_id'),
                DB::raw('SUM(stock_opname.difference * COALESCE(stock_opname.hpp, stock_opname.avg_price, 0)) AS opname_value')
            )
            ->leftJoin('product_child', 'stock_opname.product_id', '=', 'product_child.product_id')
            ->whereBetween('stock_opname.date', [$startDate, $endDate])
            ->where('stock_opname.status', 'selesai');
        
        if ($branchId !== 'all') {
            if ($branchId === 'null') $opnameQuery->whereNull('stock_opname.branch_id');
            else $opnameQuery->where('stock_opname.branch_id', $branchId);
        }
        $this->scopeReportBranch($opnameQuery, $request, 'stock_opname.branch_id');
        $opnameQuery->groupBy(DB::raw('COALESCE(product_child.parent_id, stock_opname.product_id)'));

        $data = DB::table('products')
            ->select(
                'products.id as product_id',
                'products.name',
                'product_units.abbreviation as unit',
                'pos.price',
                DB::raw('COALESCE(pos.total_pendapatan, 0) AS total_pendapatan'),
                DB::raw('COALESCE(pos.laba_kotor, 0) AS laba_kotor'),
                DB::raw('COALESCE(srt.koreksi_stock, 0) - COALESCE(opn.opname_value, 0) AS koreksi_stock'),
                DB::raw('COALESCE(pos.laba_kotor, 0) - (COALESCE(srt.koreksi_stock, 0) - COALESCE(opn.opname_value, 0)) AS laba_disesuaikan')
            )
            ->leftJoin('product_units', 'products.product_unit', '=', 'product_units.id')
            ->leftJoin('product_child', 'products.id', '=', 'product_child.product_id')
            ->leftJoinSub($posQuery, 'pos', function ($join) {
                $join->on('products.id', '=', 'pos.product_id');
            })
            ->leftJoinSub($sortirQuery, 'srt', function ($join) {
                $join->on('products.id', '=', 'srt.product_id');
            })
            ->leftJoinSub($opnameQuery, 'opn', function ($join) {
                $join->on('products.id', '=', 'opn.product_id');
            })
            ->whereNull('product_child.product_id') // Exclude products that are children
            ->whereNotNull('pos.product_id');

        $data->orderByDesc('laba_disesuaikan');

        $response = \Yajra\DataTables\Facades\DataTables::of($data);

        if ($request->input('start') == 0) {
            $grandTotalQuery = clone $data;
            
            $subQuery = DB::table(DB::raw("({$grandTotalQuery->toSql()}) as sub"))
                ->mergeBindings($grandTotalQuery);
                
            $grandTotalLabaKotor = $subQuery->sum('laba_kotor');
            $grandTotalPendapatan = $subQuery->sum('total_pendapatan');
            $grandTotalKoreksiStock = $subQuery->sum('koreksi_stock');
            $grandTotalLabaDisesuaikan = $subQuery->sum('laba_disesuaikan');

            $response->with([
                'grand_total_pendapatan' => 'Rp ' . number_format($grandTotalPendapatan, 0, ',', '.'),
                'grand_total_laba_kotor' => 'Rp ' . number_format($grandTotalLabaKotor, 0, ',', '.'),
                'grand_total_koreksi_stock' => ($grandTotalKoreksiStock > 0 ? '- Rp ' : ($grandTotalKoreksiStock < 0 ? '+ Rp ' : 'Rp ')) . number_format(abs($grandTotalKoreksiStock), 0, ',', '.'),
                'grand_total_laba_disesuaikan' => 'Rp ' . number_format($grandTotalLabaDisesuaikan, 0, ',', '.'),
            ]);
        }

        return $response
            ->addColumn('laba_kotor_formatted', function ($row) {
                return 'Rp ' . number_format($row->laba_kotor, 0, ',', '.');
            })
            ->addColumn('koreksi_stock_formatted', function ($row) {
                if ($row->koreksi_stock > 0) {
                    return '- Rp ' . number_format($row->koreksi_stock, 0, ',', '.');
                } elseif ($row->koreksi_stock < 0) {
                    return '+ Rp ' . number_format(abs($row->koreksi_stock), 0, ',', '.');
                }
                return 'Rp 0';
            })
            ->addColumn('laba_disesuaikan_formatted', function ($row) {
                return 'Rp ' . number_format($row->laba_disesuaikan, 0, ',', '.');
            })
            ->make(true);
    }


    public function get_profit_adjusted_history(Request $request)
    {
        $this->authorizeAccess('report.profit.adjusted');

        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $branch    = $request->branch ?: 'all';
        $productId = $request->product_id;

        $productIds = \DB::table('product_child')->where('parent_id', $productId)->pluck('product_id')->toArray();
        $productIds[] = $productId;
        $parentProductName = \DB::table('products')->where('id', $productId)->value('name');

        $posQuery = \Modules\Pos\Entities\PosDetailModel::select(
            'pos_transaction_detail.pos_id as pos_id',
            'pos_transaction.created_at as tx_date',
            'pos_transaction.date as date',
            'pos_transaction.branch_id as branch_id',
            'pos_transaction.invoice_number as invoice',
            \DB::raw('(pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0)) AS pendapatan'),
            \DB::raw('COALESCE(pos_transaction_detail.subtotal_hpp, 0) AS total_hpp'),
            \DB::raw('(pos_transaction_detail.subtotal - COALESCE(pos_transaction_detail.diskon_global, 0)) - COALESCE(pos_transaction_detail.subtotal_hpp, 0) AS laba_kotor'),
            \DB::raw('0 AS koreksi_stock'),
            \DB::raw('"Penjualan" as type')
        )
            ->join('pos_transaction', 'pos_transaction_detail.pos_id', '=', 'pos_transaction.id')
            ->whereIn('pos_transaction_detail.product_id', $productIds)
            ->whereNull('pos_transaction_detail.deleted_at')
            ->whereNull('pos_transaction.deleted_at')
            ->whereIn('pos_transaction.status', ['paid', 'debt'])
            ->with(['pos.paymentDetails']);

        $this->applyDateBounds($posQuery, 'pos_transaction.date', $request);
        if ($request->filled('price')) $posQuery->where('pos_transaction_detail.price', $request->input('price'));
        if ($branch !== 'all') {
            if ($branch === 'null') $posQuery->whereNull('pos_transaction.branch_id');
            else $posQuery->where('pos_transaction.branch_id', $branch);
        }
        $this->scopeReportBranch($posQuery, $request, 'pos_transaction.branch_id', 'branch');
        $posHistory = $posQuery->get();

        $sortirQuery = \Modules\Transaction\Entities\SortirDetail::select(
            'sortir_transaction.created_at as tx_date',
            'sortir_transaction.date as date',
            'sortir_transaction.branch_id as branch_id',
            'sortir_transaction.invoice_number as invoice',
            \DB::raw('0 AS laba_kotor'),
            \DB::raw('sortir_transaction_detail.subtotal AS koreksi_stock'),
            \DB::raw('"Sortir Barang Buang" as type')
        )
            ->join('sortir_transaction', 'sortir_transaction_detail.sortir_id', '=', 'sortir_transaction.id')
            ->whereIn('sortir_transaction_detail.product_id', $productIds);

        $this->applyDateBounds($sortirQuery, 'sortir_transaction.date', $request);
        if ($branch !== 'all') {
            if ($branch === 'null') $sortirQuery->whereNull('sortir_transaction.branch_id');
            else $sortirQuery->where('sortir_transaction.branch_id', $branch);
        }
        $this->scopeReportBranch($sortirQuery, $request, 'sortir_transaction.branch_id', 'branch');
        $sortirHistory = $sortirQuery->get();

        $opnameQuery = \Modules\Transaction\Entities\StockOpname::select(
            'stock_opname.created_at as tx_date',
            'stock_opname.date as date',
            'stock_opname.branch_id as branch_id',
            'stock_opname.code as invoice',
            \DB::raw('0 AS laba_kotor'),
            \DB::raw('-(stock_opname.difference * COALESCE(stock_opname.hpp, stock_opname.avg_price, 0)) AS koreksi_stock'),
            \DB::raw('"Stock Opname" as type')
        )
            ->whereIn('stock_opname.product_id', $productIds)
            ->where('stock_opname.status', 'selesai');

        $this->applyDateBounds($opnameQuery, 'stock_opname.date', $request);
        if ($branch !== 'all') {
            if ($branch === 'null') $opnameQuery->whereNull('stock_opname.branch_id');
            else $opnameQuery->where('stock_opname.branch_id', $branch);
        }
        $this->scopeReportBranch($opnameQuery, $request, 'stock_opname.branch_id', 'branch');
        $opnameHistory = $opnameQuery->get();

        // Combine all histories
        $combined = $posHistory->concat($sortirHistory)->concat($opnameHistory)->sortByDesc('tx_date')->values();

        // Format for output
        $formatted = $combined->map(function($item) use ($parentProductName) {
            $txDate = \Carbon\Carbon::parse($item->tx_date);
            $laba_kotor = $item->laba_kotor;
            $koreksi_stock = $item->koreksi_stock;
            $laba_disesuaikan = $laba_kotor - $koreksi_stock;

            $koreksi_stock_formatted = 'Rp 0';
            if ($koreksi_stock > 0) {
                $koreksi_stock_formatted = '- Rp ' . number_format($koreksi_stock, 0, ',', '.');
            } elseif ($koreksi_stock < 0) {
                $koreksi_stock_formatted = '+ Rp ' . number_format(abs($koreksi_stock), 0, ',', '.');
            }

            return [
                'tx_date_raw' => $item->tx_date,
                'date_raw' => $item->date,
                'date_formatted' => $txDate->locale('id')->isoFormat('dddd, D MMMM Y HH:mm'),
                'type' => $item->type,
                'invoice' => $item->invoice,
                'laba_kotor' => 'Rp ' . number_format($laba_kotor, 0, ',', '.'),
                'koreksi_stock' => $koreksi_stock_formatted,
                'koreksi_stock_raw' => $koreksi_stock,
                'laba_disesuaikan' => 'Rp ' . number_format($laba_disesuaikan, 0, ',', '.'),
                'amount_raw' => (float) $laba_disesuaikan,
                'revenue_raw' => (float) ($item->pendapatan ?? 0),
                'hpp_raw' => (float) ($item->total_hpp ?? 0),
                'correction_raw' => (float) $koreksi_stock,
                'payment_methods' => $item->type === 'Penjualan' ? $this->historyPaymentMethods($item->pos?->paymentDetails) : [],
                'product_name' => $parentProductName
            ];
        });

        return response()->json([
            'status' => 'success',
            'product_name' => $formatted->first() ? $formatted->first()['product_name'] : '',
            'data' => $formatted
        ]);
    }

    public function get_profit_revenue_history(Request $request)
    {
        $this->authorizeAccess('report.profit.revenue');

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
            ->leftJoin('branch', 'pos_transaction.branch_id', '=', 'branch.id')
            ->join('products', 'pos_transaction_detail.product_id', '=', 'products.id')
            ->leftJoin('product_units', 'products.product_unit', '=', 'product_units.id')
            ->where('pos_transaction_detail.product_id', $productId)
            ->whereNull('pos_transaction_detail.deleted_at')
            ->whereNull('pos_transaction.deleted_at')
            ->whereIn('pos_transaction.status', ['paid', 'debt'])
            ->with(['pos.paymentDetails.paymentMethod']);

        $this->applyDateBounds($query, 'pos_transaction.date', $request);
        if ($request->filled('price')) $query->where('pos_transaction_detail.price', $request->input('price'));

        if ($branch !== 'all') {
            if ($branch === 'null') $query->whereNull('pos_transaction.branch_id');
            else $query->where('pos_transaction.branch_id', $branch);
        }
        $this->scopeReportBranch($query, $request, 'pos_transaction.branch_id', 'branch');

        $history = $query->orderBy('pos_transaction.created_at', 'desc')->get();

        $formattedData = $history->map(function ($detail) {
            $paymentMethods = collect();
            if ($detail->pos && $detail->pos->paymentDetails) {
                $paymentMethods = $detail->pos->paymentDetails->filter(function($payment) {
                    return $payment->payment_amount > 0;
                })->map(function($payment) {
                    $method = $payment->payment_method;
                    if (empty($method) || strtolower($method) === 'tunai') {
                        return 'Tunai';
                    }
                    if ($method === 'Split') {
                        return 'Split';
                    }
                    $decoded = json_decode($method, true);
                    if (is_array($decoded) && count($decoded) > 0) {
                        return collect($decoded)->implode(', ');
                    }
                    return $method;
                })->filter()->unique()->values();
            }
            
            if ($paymentMethods->isEmpty()) {
                $paymentMethods->push('Tunai');
            }

            if ($paymentMethods->count() > 1) {
                $paymentStr = $paymentMethods->implode(', ');
            } elseif ($paymentMethods->count() == 1) {
                $paymentStr = $paymentMethods[0];
            } else {
                $paymentStr = '-';
            }

            $txDate = \Carbon\Carbon::parse($detail->tx_date);
            $diskon_global = $detail->diskon_global ?? 0;
            
            $unit = $detail->unit ? $detail->unit : 'pcs';
            $qty_formatted = round($detail->quantity, 2) . ' ' . $unit;

            $harga_satuan = $detail->price;
            $penjualan_kotor = $harga_satuan * $detail->quantity;
            
            // Pendapatan bersih = subtotal (price*qty - diskon item) - diskon global
            $pendapatan_bersih = $detail->subtotal - $diskon_global;
            
            $hpp_satuan = $detail->hpp ?? 0;
            $total_hpp = $hpp_satuan * $detail->quantity;
            
            $laba_kotor = $pendapatan_bersih - $total_hpp;

            return [
                'invoice' => $detail->invoice,
                'date_formatted' => $txDate->locale('id')->isoFormat('dddd, D MMMM Y'),
                'time_formatted' => 'Jam ' . $txDate->format('H:i') . ' WIB',
                'branch_name' => $detail->branch_name,
                'qty' => $qty_formatted,
                'harga_satuan' => 'Rp ' . number_format($harga_satuan, 0, ',', '.'),
                'penjualan_kotor' => 'Rp ' . number_format($penjualan_kotor, 0, ',', '.'),
                'pendapatan_bersih' => 'Rp ' . number_format($pendapatan_bersih, 0, ',', '.'),
                'hpp_satuan' => 'Rp ' . number_format($hpp_satuan, 0, ',', '.'),
                'total_hpp' => '- Rp ' . number_format($total_hpp, 0, ',', '.'),
                'laba_kotor' => 'Rp ' . number_format($laba_kotor, 0, ',', '.'),
                'pos_id' => $detail->pos_id,
                'pos_date' => $detail->pos_date,
                'tx_date' => $detail->tx_date,
                'payment' => $paymentStr,
                'payment_methods' => $this->historyPaymentMethods($detail->pos?->paymentDetails),
            ];
        });

        return response()->json([
            'status' => 'success',
            'product_name' => $history->first() ? $history->first()->product_name : '',
            'data' => $formattedData
        ]);
    }

    public function get_product_buang_history(Request $request)
    {
        $this->authorizeAccess('report.product.buang');

        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $branch    = $request->branch ?: 'all';
        $productId = $request->product_id;

        $query = \Modules\Transaction\Entities\SortirDetail::select(
            'sortir_transaction_detail.*',
            'sortir_transaction.id as sortir_id',
            'sortir_transaction.invoice_number as invoice',
            'sortir_transaction.created_at as tx_date',
            'sortir_transaction.date as sortir_date',
            'branch.name as branch_name',
            'products.name as product_name',
            'product_units.abbreviation as unit'
        )
            ->join('sortir_transaction', 'sortir_transaction_detail.sortir_id', '=', 'sortir_transaction.id')
            ->leftJoin('branch', 'sortir_transaction.branch_id', '=', 'branch.id')
            ->join('products', 'sortir_transaction_detail.product_id', '=', 'products.id')
            ->leftJoin('product_units', 'products.product_unit', '=', 'product_units.id')
            ->where('sortir_transaction_detail.product_id', $productId);

        $this->applyDateBounds($query, 'sortir_transaction.date', $request);

        if ($branch !== 'all') {
            if ($branch === 'null') $query->whereNull('sortir_transaction.branch_id');
            else $query->where('sortir_transaction.branch_id', $branch);
        }
        $this->scopeReportBranch($query, $request, 'sortir_transaction.branch_id', 'branch');

        $history = $query->orderBy('sortir_transaction.created_at', 'desc')->get();

        $formattedData = $history->map(function ($detail) {
            $txDate = \Carbon\Carbon::parse($detail->tx_date);
            $subtotal = $detail->price * $detail->quantity;
            $total = $detail->subtotal;
            
            $unit = $detail->unit ? $detail->unit : 'pcs';
            $qty_formatted = fmod($detail->quantity, 1) == 0 ? number_format($detail->quantity, 0, ',', '.') : number_format($detail->quantity, 2, ',', '.');
            $qty_formatted .= ' ' . $unit;

            return [
                'invoice' => $detail->invoice,
                'date_formatted' => $txDate->locale('id')->isoFormat('dddd, D MMMM Y'),
                'time_formatted' => 'Jam ' . $txDate->format('H:i') . ' WIB',
                'branch_name' => $detail->branch_name ?? 'Pusat',
                'qty' => $qty_formatted,
                'subtotal' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
                'product_discount' => (float) $detail->discount > 0 ? '- Rp ' . number_format((float) $detail->discount, 0, ',', '.') : null,
                'total' => 'Rp ' . number_format($total, 0, ',', '.'),
                'sortir_id' => $detail->sortir_id,
                'sortir_date' => $detail->sortir_date,
                'tx_date' => $detail->tx_date,
            ];
        });

        return response()->json([
            'status' => 'success',
            'product_name' => $history->first() ? $history->first()->product_name : '',
            'data' => $formattedData
        ]);
    }

    private function historyPaymentMethods($payments): array
    {
        $methods = [];

        foreach ($payments ?? [] as $payment) {
            $decodedMethods = is_string($payment->payment_method) ? json_decode($payment->payment_method, true) : null;
            $decodedAmounts = is_string($payment->payment_amount) ? json_decode($payment->payment_amount, true) : null;
            $paymentMethods = is_array($decodedMethods)
                ? $decodedMethods
                : (is_string($decodedMethods) ? [$decodedMethods] : [$payment->payment_method]);
            $paymentAmounts = is_array($decodedAmounts)
                ? $decodedAmounts
                : (is_numeric($decodedAmounts) ? [$decodedAmounts] : [$payment->payment_amount]);

            foreach ($paymentMethods as $index => $method) {
                $amount = $paymentAmounts[$index] ?? null;
                if (is_numeric($amount) && (float) $amount <= 0) continue;

                $method = trim((string) $method);
                if ($method === '') $method = 'Tunai';
                $nestedMethods = json_decode($method, true);
                foreach (is_array($nestedMethods) ? $nestedMethods : [$method] as $nestedMethod) {
                    $nestedMethod = trim((string) $nestedMethod);
                    if ($nestedMethod !== '') $methods[] = $nestedMethod;
                }
            }
        }

        return $methods ?: ['Tunai'];
    }
}
