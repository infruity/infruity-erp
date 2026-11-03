<?php

namespace Modules\Crm\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Master\Entities\Customer;
use Modules\Crm\Entities\Tier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Modules\Master\Entities\UserBranch;
use Modules\Transaction\Entities\StockOpname;

class DashboardController extends Controller
{
    use \App\Traits\HasAccessControl;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        if ($denied = $this->requireAccess('crm-dashboard.index')) {
            return $denied;
        }

        // Auto update stock-opname status (kemarin dan seterusnya)
        $pendingStockOpnames = StockOpname::where('date', '<', date('Y-m-d'))
            ->where('status', '!=', 'selesai')
            ->get();

        foreach ($pendingStockOpnames as $stockOpname) {
            $productStock = DB::table('product_stock')
                ->where('id', $stockOpname->product_id)
                ->where('branch_id', $stockOpname->branch_id)
                ->first();

            if ($productStock) {
                $currentStock = $productStock->stock_available;
                $difference = (Double)$stockOpname->real_stock - (Double)$currentStock;

                $stockOpname->stock = $currentStock;
                $stockOpname->difference = $difference;
                $stockOpname->status = 'selesai';
                $stockOpname->updated_by = Auth::check() ? Auth::user()->id_user : null;
                $stockOpname->save();
            }
        }

        $period = in_array($request->query('period'), ['week', 'month', 'year'], true)
            ? $request->query('period') : 'week';
        $offset = max(-120, min(0, (int) $request->query('offset', 0)));
        $today = Carbon::today();

        if ($period === 'month') {
            $start = $today->copy()->startOfMonth()->addMonths($offset);
            $end = $start->copy()->endOfMonth();
        } elseif ($period === 'year') {
            $start = $today->copy()->startOfYear()->addYears($offset);
            $end = $start->copy()->endOfYear();
        } else {
            // The dashboard's business week runs Saturday through Friday.
            $start = $today->copy()->startOfWeek(Carbon::SATURDAY)->addWeeks($offset);
            $end = $start->copy()->endOfWeek(Carbon::FRIDAY);
        }

        $allowedBranchIds = UserBranch::getUserBranch();
        $branches = DB::table('branch')->whereIn('id', $allowedBranchIds)->orderBy('name')->get(['id', 'name']);
        $requestedBranch = $request->query('branch_id');
        $branchId = in_array((string) $requestedBranch, array_map('strval', $allowedBranchIds), true)
            ? (int) $requestedBranch : null;

        $salesQuery = DB::table('pos_transaction')
            ->whereNull('deleted_at')
            ->whereIn('status', ['paid', 'debt'])
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        $expenseQuery = DB::table('expenditure')
            ->where('expenditure.type', 'pengeluaran')
            ->whereIn('expenditure.status', ['paid', 'debt'])
            ->whereIn('expenditure.branch_id', $allowedBranchIds)
            ->whereBetween('expenditure.date', [$start->toDateString(), $end->toDateString()]);

        if ($branchId !== null) {
            $salesQuery->where('branch_id', $branchId);
            $expenseQuery->where('expenditure.branch_id', $branchId);
        }

        $salesByDate = (clone $salesQuery)->select('date', DB::raw('SUM(COALESCE(total, 0)) AS amount'))
            ->groupBy('date')->pluck('amount', 'date');
        $expensesByDate = (clone $expenseQuery)->select('date', DB::raw('SUM(COALESCE(total, 0)) AS amount'))
            ->groupBy('date')->pluck('amount', 'date');

        $hppQuery = DB::table('pos_transaction_detail as detail')
            ->join('pos_transaction as sale', 'sale.id', '=', 'detail.pos_id')
            ->whereNull('detail.deleted_at')
            ->whereNull('sale.deleted_at')
            ->whereIn('sale.status', ['paid', 'debt'])
            ->whereIn('sale.branch_id', $allowedBranchIds)
            ->whereBetween('sale.date', [$start->toDateString(), $end->toDateString()]);
        if ($branchId !== null) {
            $hppQuery->where('sale.branch_id', $branchId);
        }
        $hppByDate = (clone $hppQuery)->select('sale.date', DB::raw('SUM(COALESCE(detail.subtotal_hpp, 0)) AS amount'))
            ->groupBy('sale.date')->pluck('amount', 'date');

        // Use the same sorting transactions as the Barang Buang report.
        $lossQuery = DB::table('sortir_transaction_detail as detail')
            ->join('sortir_transaction as sorting', 'sorting.id', '=', 'detail.sortir_id')
            ->whereIn('sorting.status', ['paid', 'debt'])
            ->whereIn('sorting.branch_id', $allowedBranchIds)
            ->whereBetween('sorting.date', [$start->toDateString(), $end->toDateString()]);
        if ($branchId !== null) {
            $lossQuery->where('sorting.branch_id', $branchId);
        }
        $lossByDate = (clone $lossQuery)->select('sorting.date', DB::raw('SUM(COALESCE(detail.subtotal, 0)) AS amount'))
            ->groupBy('sorting.date')->pluck('amount', 'date');

        $daily = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $revenue = (float) ($salesByDate[$key] ?? 0);
            $operatingExpense = (float) ($expensesByDate[$key] ?? 0);
            $hpp = (float) ($hppByDate[$key] ?? 0);
            $expense = $operatingExpense + $hpp;
            $daily[] = [
                'date' => $key,
                'label' => $period === 'week' ? $date->locale('id')->isoFormat('ddd') : $date->format('d'),
                'day' => $date->format('d'),
                'revenue' => $revenue,
                'expense' => $expense,
                'profit' => max(0, $revenue - $expense),
                'loss' => (float) ($lossByDate[$key] ?? 0),
            ];
        }

        $chart = [];
        foreach ($daily as $day) {
            $date = Carbon::parse($day['date']);
            if ($period === 'year') {
                $bucket = $date->format('m');
                $label = $date->locale('id')->isoFormat('MMM');
            } elseif ($period === 'month') {
                $bucket = (string) ceil($date->day / 7);
                $label = (string) (((int) $bucket - 1) * 7 + 1) . '–' . min((int) $bucket * 7, $end->day);
            } else {
                $bucket = $day['date'];
                $label = $day['label'];
            }
            if (!isset($chart[$bucket])) {
                $chart[$bucket] = ['label' => $label, 'day' => $period === 'week' ? $day['day'] : '', 'date' => $period === 'week' ? $day['date'] : null, 'revenue' => 0, 'expense' => 0, 'profit' => 0, 'loss' => 0];
            }
            foreach (['revenue', 'expense', 'profit', 'loss'] as $metric) {
                $chart[$bucket][$metric] += $day[$metric];
            }
        }

        $totalRevenue = (float) $salesByDate->sum();
        $totalHpp = (float) $hppByDate->sum();
        $totalExpense = (float) $expensesByDate->sum() + $totalHpp;
        $totalLoss = (float) $lossByDate->sum();
        $net = $totalRevenue - $totalExpense;

        $previousEnd = $start->copy()->subDay();
        $previousStart = match ($period) {
            'month' => $start->copy()->subMonth()->startOfMonth(),
            'year' => $start->copy()->subYear()->startOfYear(),
            default => $start->copy()->subWeek(),
        };
        $previousRevenue = (float) DB::table('pos_transaction')
            ->whereNull('deleted_at')
            ->whereIn('status', ['paid', 'debt'])
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereBetween('date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->when($branchId !== null, static fn ($query) => $query->where('branch_id', $branchId))
            ->sum(DB::raw('COALESCE(total, 0)'));
        $previousExpense = (float) DB::table('expenditure')
            ->where('type', 'pengeluaran')
            ->whereIn('status', ['paid', 'debt'])
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereBetween('date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->when($branchId !== null, static fn ($query) => $query->where('branch_id', $branchId))
            ->sum(DB::raw('COALESCE(total, 0)'));
        $previousHpp = (float) DB::table('pos_transaction_detail as detail')
            ->join('pos_transaction as sale', 'sale.id', '=', 'detail.pos_id')
            ->whereNull('detail.deleted_at')
            ->whereNull('sale.deleted_at')
            ->whereIn('sale.status', ['paid', 'debt'])
            ->whereIn('sale.branch_id', $allowedBranchIds)
            ->whereBetween('sale.date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->when($branchId !== null, static fn ($query) => $query->where('sale.branch_id', $branchId))
            ->sum(DB::raw('COALESCE(detail.subtotal_hpp, 0)'));
        $previousExpense += $previousHpp;
        $previousNet = $previousRevenue - $previousExpense;
        $previousLoss = (float) DB::table('sortir_transaction_detail as detail')
            ->join('sortir_transaction as sorting', 'sorting.id', '=', 'detail.sortir_id')
            ->whereIn('sorting.status', ['paid', 'debt'])
            ->whereIn('sorting.branch_id', $allowedBranchIds)
            ->whereBetween('sorting.date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->when($branchId !== null, static fn ($query) => $query->where('sorting.branch_id', $branchId))
            ->sum(DB::raw('COALESCE(detail.subtotal, 0)'));

        $topProducts = (clone $hppQuery)
            ->join('products', 'products.id', '=', 'detail.product_id')
            ->select('products.name', 'products.image', DB::raw('SUM(COALESCE(detail.subtotal, 0) - COALESCE(detail.diskon_global, 0)) AS amount'))
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('amount')->limit(3)->get();
        $bottomProducts = (clone $hppQuery)
            ->join('products', 'products.id', '=', 'detail.product_id')
            ->select('products.name', 'products.image', DB::raw('SUM(COALESCE(detail.subtotal, 0) - COALESCE(detail.diskon_global, 0)) AS amount'))
            ->groupBy('products.id', 'products.name', 'products.image')
            ->having('amount', '>', 0)
            ->orderBy('amount')->limit(3)->get();

        $productMargin = (clone $hppQuery)
            ->join('products', 'products.id', '=', 'detail.product_id')
            ->select('products.name', 'products.image',
                DB::raw('SUM(COALESCE(detail.subtotal, 0) - COALESCE(detail.diskon_global, 0)) AS sales'),
                DB::raw('SUM(COALESCE(detail.subtotal, 0) - COALESCE(detail.diskon_global, 0) - COALESCE(detail.subtotal_hpp, 0)) AS amount'))
            ->groupBy('products.id', 'products.name', 'products.image')
            ->having('amount', '>', 0);
        $expenseItems = (clone $expenseQuery)
            ->join('expenditure_detail as detail', 'detail.expenditure_id', '=', 'expenditure.id')
            ->leftJoin('products', 'products.id', '=', 'detail.product_id')
            ->select(DB::raw("COALESCE(NULLIF(detail.product_name, ''), products.name, 'Pengeluaran lainnya') AS name"),
                'products.image', DB::raw('SUM(COALESCE(detail.subtotal, 0)) AS amount'))
            ->groupBy('name', 'products.image')
            ->having('amount', '>', 0);
        $productCosts = (clone $hppQuery)
            ->join('products', 'products.id', '=', 'detail.product_id')
            ->select('products.name', 'products.image', DB::raw('SUM(COALESCE(detail.subtotal_hpp, 0)) AS amount'))
            ->groupBy('products.id', 'products.name', 'products.image')
            ->having('amount', '>', 0);
        $costItems = $expenseItems->get()->concat($productCosts->get());
        $lossItems = (clone $lossQuery)
            ->join('products', 'products.id', '=', 'detail.product_id')
            ->select('products.name', 'products.image', DB::raw('SUM(COALESCE(detail.subtotal, 0)) AS amount'))
            ->groupBy('products.id', 'products.name', 'products.image')
            ->having('amount', '>', 0);

        $contributors = [
            'revenue' => ['highest' => $topProducts, 'lowest' => $bottomProducts],
            'expense' => ['highest' => $costItems->sortByDesc('amount')->take(3)->values(), 'lowest' => $costItems->sortBy('amount')->take(3)->values()],
            'profit' => ['highest' => (clone $productMargin)->orderByDesc('amount')->limit(3)->get(), 'lowest' => (clone $productMargin)->orderBy('amount')->limit(3)->get()],
            'loss' => ['highest' => (clone $lossItems)->orderByDesc('amount')->limit(3)->get(), 'lowest' => (clone $lossItems)->orderBy('amount')->limit(3)->get()],
        ];

        return view('crm::dashboard.index', [
            'period' => $period,
            'offset' => $offset,
            'start' => $start,
            'end' => $end,
            'branchId' => $branchId,
            'branches' => $branches,
            'daily' => $daily,
            'chart' => array_values($chart),
            'metrics' => [
                'revenue' => $totalRevenue,
                'expense' => $totalExpense,
                'profit' => max(0, $net),
                'loss' => $totalLoss,
            ],
            'previousMetrics' => [
                'revenue' => $previousRevenue,
                'expense' => $previousExpense,
                'profit' => max(0, $previousNet),
                'loss' => $previousLoss,
            ],
            'topProducts' => $topProducts,
            'bottomProducts' => $bottomProducts,
            'contributors' => $contributors,
        ]);
    }

    public function topDistribution(Request $request)
    {
        if ($denied = $this->requireAccess('crm-dashboard.top-distribution')) {
            return $denied;
        }

        // Query 5 teratas
        $top5 = DB::table('customer')
            ->leftJoin('reg_districts as B', 'customer.district', '=', 'B.id')
            ->select([
                'customer.district',
                'B.name',
                DB::raw('COUNT(*) as total')
            ])
            ->groupBy('customer.district', 'B.name')
            ->orderByDesc('total')
            ->limit(5);

        // Query total lainnya
        $others = DB::table(DB::raw('(
            SELECT `customer`.`district`, COUNT(*) as total
            FROM `customer`
            GROUP BY `customer`.`district`
            ORDER BY total DESC
            LIMIT 18446744073709551615 OFFSET 5
        ) as sub'))
            ->select([
                DB::raw("'Other' as district"),
                DB::raw("'Other' as name"),
                DB::raw('SUM(total) as total')
            ]);

        // Gabungkan dengan UNION ALL
        $totalCustomer = $top5->unionAll($others)->get();
        return view('crm::dashboard.top_distribution', compact('totalCustomer'));
    }

    public function genderDistribution()
    {
        if ($denied = $this->requireAccess('crm-dashboard.gender-distribution')) {
            return $denied;
        }

        $data = Customer::select('gender', DB::raw('COUNT(*) as total'))
            ->groupBy('gender')
            ->get();
            
        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function topTier()
    {
        if ($denied = $this->requireAccess('crm-dashboard.top-tier')) {
            return $denied;
        }

        $data = Customer::query()
            ->leftJoin('vw_customer_tier', 'customer.id', '=', 'vw_customer_tier.customer_id')
            ->leftJoin('crm_tier as C', 'vw_customer_tier.tier_id', '=', 'C.id')
            ->select('customer.id', 'customer.name', 'vw_customer_tier.*', 'C.name as tier_name')
            ->limit(6)
            ->orderBy('C.level', 'desc')
            ->orderBy('vw_customer_tier.customer_exp', 'desc')
            ->get();
        return view('crm::dashboard.top_tier', compact('data'));
    }

    public function tierGraphic(Request $request)
    {
        if ($denied = $this->requireAccess('crm-dashboard.tier-graphic')) {
            return $denied;
        }

        $tiers = Tier::query()->leftJoin('vw_customer_tier as B', 'crm_tier.id', '=', 'B.tier_id')
            ->select([
                'crm_tier.id',
                'crm_tier.name',
                DB::raw('COUNT(B.customer_id) as total'),
            ])
            ->groupBy('crm_tier.id', 'crm_tier.name')
            ->orderBy('crm_tier.level', 'asc')
            ->get();
        return response()->json([
            'status' => true,
            'data' => $tiers,
        ]);
    }

    public function customerGraphic(Request $request)
    {
        if ($denied = $this->requireAccess('crm-dashboard.customer-distribution')) {
            return $denied;
        }

        $customer = Customer::getCustomerGraph();
        return response()->json([
            'status' => true,
            'data' => $customer,
        ]);
    }
    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('crm::create');
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
        return view('crm::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('crm::edit');
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
}
