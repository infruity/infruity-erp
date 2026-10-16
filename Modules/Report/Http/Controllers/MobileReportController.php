<?php

namespace Modules\Report\Http\Controllers;

use App\Traits\HasAccessControl;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\UserBranch;

class MobileReportController extends Controller
{
    use HasAccessControl;

    private function scopeBranch($query, string $column, string $requested): void
    {
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

    private const PERMISSIONS = [
        'sales' => 'report.product.sales',
        'profit' => 'report.profit.revenue',
        'adjusted' => 'report.profit.adjusted',
        'shipping' => 'report.shipping.cost',
        'loss' => 'report.product.buang',
    ];

    public function dates(Request $request, string $type)
    {
        $this->authorizeType($type);
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|string',
            'time_group' => 'nullable|in:daily,weekly',
        ]);
        $events = $this->events($request, $type);
        $period = $request->input('time_group') === 'weekly'
            ? 'DATE_SUB(events.date, INTERVAL (DAYOFWEEK(events.date) % 7) DAY)'
            : 'events.date';

        $groups = DB::query()->fromSub($events, 'events')
            ->leftJoin('branch', 'events.branch_id', '=', 'branch.id')
            ->selectRaw("{$period} as period_start, events.branch_id, COALESCE(branch.name, 'Pusat') as branch_name")
            ->selectRaw('SUM(events.amount) as amount, SUM(events.revenue) as revenue, SUM(events.hpp) as hpp, SUM(events.correction) as correction, SUM(events.quantity) as quantity, SUM(events.item_count) as item_count')
            ->groupByRaw("{$period}, events.branch_id, branch.name");

        $count = DB::query()->fromSub(clone $groups, 'report_groups')->count();
        $totals = DB::query()->fromSub(clone $groups, 'report_groups')
            ->selectRaw('COALESCE(SUM(amount), 0) as amount, COALESCE(SUM(revenue), 0) as revenue, COALESCE(SUM(hpp), 0) as hpp, COALESCE(SUM(correction), 0) as correction')
            ->first();
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 50)));
        $data = $groups->orderByDesc('period_start')->orderBy('branch_name')
            ->offset($start)->limit($length)->get();

        $response = [
            'data' => $data,
            'recordsTotal' => $count,
            'recordsFiltered' => $count,
        ];

        if ($start === 0) {
            $response['grand_total'] = $this->rupiah($totals->amount);
            $response['grand_total_pendapatan'] = $this->rupiah($totals->revenue);
            $response['grand_total_hpp'] = '- '.$this->rupiah($totals->hpp);
            $response['grand_total_laba'] = $this->rupiah($totals->amount);
            $response['grand_total_laba_kotor'] = $this->rupiah($totals->revenue - $totals->hpp);
            $response['grand_total_koreksi_stock'] = $this->rupiah(-$totals->correction);
            $response['grand_total_laba_disesuaikan'] = $this->rupiah($totals->amount);
        }

        return response()->json($response);
    }

    public function dateHistory(Request $request, string $type)
    {
        $this->authorizeType($type);
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $query = DB::query()->fromSub($this->events($request, $type), 'events')
            ->leftJoin('branch', 'events.branch_id', '=', 'branch.id')
            ->select('events.*', 'branch.name as branch_name')
            ->orderByDesc('events.tx_date');

        $events = $query->get();
        $posInvoices = $events->whereIn('type', ['Penjualan', 'Ongkos Kirim'])
            ->pluck('invoice')->filter()->unique()->values();
        $transactions = $posInvoices->isEmpty()
            ? collect()
            : \Modules\Pos\Entities\PosModel::with(['paymentDetails', 'customer'])
                ->whereIn('invoice_number', $posInvoices)
                ->whereIn('branch_id', UserBranch::getUserBranch())
                ->get()
                ->keyBy('invoice_number');

        $data = $events->map(function ($event) use ($transactions, $type) {
            $date = Carbon::parse($event->date)->locale('id');
            $time = Carbon::parse($event->tx_date);
            $transaction = $transactions->get($event->invoice);
            $discount = (float) ($event->product_discount ?? 0);
            $prorataDiscount = (float) ($event->prorata_discount ?? 0);
            $totalLabel = match ($type) {
                'profit' => 'Laba Kotor',
                'adjusted' => $event->type === 'Penjualan' ? 'Laba Kotor' : 'Koreksi Stok',
                'shipping' => 'Total Bersih',
                'loss' => 'Kerugian',
                default => 'Total',
            };

            return [
                'invoice' => $event->invoice ?: $event->name,
                'date_formatted' => $date->isoFormat('dddd, D MMMM Y'),
                'date_raw' => $event->date,
                'time_formatted' => 'Jam '.$time->format('H:i').' WIB',
                'branch_name' => $event->branch_name ?: 'Pusat',
                'type' => $event->type,
                'product_name' => $event->name,
                'courier_id' => $event->courier_id,
                'courier_name' => $type === 'shipping' ? $event->name : null,
                'quantity' => $event->quantity,
                'qty' => $event->quantity === null ? null : (float) $event->quantity,
                'payment' => $transaction ? $this->paymentLabel($transaction) : null,
                'payment_methods' => $transaction ? $this->paymentMethods($transaction) : [],
                'customer_name' => $transaction?->customer?->name ?: 'Pelanggan Umum',
                'address' => $transaction?->ongkir_address ?: null,
                'ongkir' => $type === 'shipping' ? $this->rupiah($event->gross_amount ?? 0) : null,
                'ongkir_discount' => $type === 'shipping' && $prorataDiscount > 0 ? '- '.$this->rupiah($prorataDiscount) : null,
                'total_bersih' => $type === 'shipping' ? $this->rupiah((float) ($event->gross_amount ?? 0) - $prorataDiscount) : null,
                'subtotal' => $this->rupiah($event->gross_amount ?? $event->amount),
                'product_discount' => $discount > 0 ? '- '.$this->rupiah($discount) : null,
                'prorata_discount' => $prorataDiscount > 0 ? '- '.$this->rupiah($prorataDiscount) : null,
                'harga_satuan' => isset($event->unit_price) ? $this->rupiah($event->unit_price) : null,
                'penjualan_kotor' => $this->rupiah($event->gross_amount ?? 0),
                'pendapatan_bersih' => $this->rupiah($event->revenue ?? 0),
                'hpp_satuan' => isset($event->unit_hpp) ? $this->rupiah($event->unit_hpp) : null,
                'total_hpp' => $this->rupiah($event->hpp ?? 0),
                'laba_kotor' => $this->rupiah(($event->revenue ?? 0) - ($event->hpp ?? 0)),
                'koreksi_stock' => $this->rupiah(-($event->correction ?? 0)),
                'amount_raw' => (float) ($event->amount ?? 0),
                'revenue_raw' => (float) ($event->revenue ?? 0),
                'hpp_raw' => (float) ($event->hpp ?? 0),
                'correction_raw' => (float) ($event->correction ?? 0),
                'total_label' => $totalLabel,
                'total' => $this->rupiah($event->amount),
            ];
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    private function paymentLabel($transaction): string
    {
        return collect($this->paymentMethods($transaction))->unique()->implode(', ');
    }

    private function paymentMethods($transaction): array
    {
        $methods = [];

        foreach ($transaction->paymentDetails ?? [] as $payment) {
            $methodValue = $payment->payment_method;
            $amountValue = $payment->payment_amount;
            $decodedMethods = is_string($methodValue) ? json_decode($methodValue, true) : null;
            $decodedAmounts = is_string($amountValue) ? json_decode($amountValue, true) : null;
            $paymentMethods = is_array($decodedMethods)
                ? $decodedMethods
                : (is_string($decodedMethods) ? [$decodedMethods] : [$methodValue]);
            $paymentAmounts = is_array($decodedAmounts)
                ? $decodedAmounts
                : (is_numeric($decodedAmounts) ? [$decodedAmounts] : [$amountValue]);

            foreach ($paymentMethods as $index => $method) {
                $amount = $paymentAmounts[$index] ?? null;
                if (is_numeric($amount) && (float) $amount <= 0) continue;

                $method = trim((string) $method);
                if ($method === '') $method = 'Tunai';
                $decodedMethod = json_decode($method, true);
                if (is_array($decodedMethod)) {
                    foreach ($decodedMethod as $nestedMethod) {
                        $nestedMethod = trim((string) $nestedMethod);
                        if ($nestedMethod !== '') $methods[] = $nestedMethod;
                    }
                } else {
                    $methods[] = $method;
                }
            }
        }

        return $methods ?: ['Tunai'];

    }

    private function authorizeType(string $type): void
    {
        abort_unless(isset(self::PERMISSIONS[$type]), 404);
        $this->authorizeAccess(self::PERMISSIONS[$type]);
    }

    private function applySearch($query, string $search, array $nameColumns, ?string $dateColumn = null): void
    {
        if ($search === '') return;

        $like = '%' . $search . '%';
        $dateLabelSql = $dateColumn ? $this->indonesianDateLabelSql($dateColumn) : null;
        $query->where(function ($searchQuery) use ($nameColumns, $like, $dateLabelSql) {
            foreach ($nameColumns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $searchQuery->{$method}($column, 'like', $like);
            }
            if ($dateLabelSql) $searchQuery->orWhereRaw($dateLabelSql . ' LIKE ?', [$like]);
        });
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

    private function events(Request $request, string $type)
    {
        $startDate = $request->input('start_date', '1900-01-01');
        $endDate = $request->input('end_date', '2100-12-31');
        $branchId = (string) ($request->input('branch_id') ?: 'all');
        $search = trim((string) data_get($request->input('search'), 'value', $request->input('search_text', '')));
        $net = '(d.subtotal - COALESCE(d.diskon_global, 0))';
        $hpp = 'COALESCE(d.subtotal_hpp, 0)';

        if (in_array($type, ['sales', 'profit', 'adjusted'], true)) {
            $pos = DB::table('pos_transaction_detail as d')
                ->join('pos_transaction as t', 'd.pos_id', '=', 't.id')
                ->join('products as p', 'd.product_id', '=', 'p.id')
                ->whereBetween('t.date', [$startDate, $endDate])
                ->whereNull('d.deleted_at')
                ->whereNull('t.deleted_at')
                ->whereIn('t.status', ['paid', 'debt']);
            if ($type === 'adjusted') {
                $pos->leftJoin('product_child as pc', 'd.product_id', '=', 'pc.product_id')
                    ->join('products as root', 'root.id', '=', DB::raw('COALESCE(pc.parent_id, d.product_id)'));
            }
            if ($branchId === 'null') $pos->whereNull('t.branch_id');
            elseif ($branchId !== 'all') $pos->where('t.branch_id', $branchId);
            $this->scopeBranch($pos, 't.branch_id', (string) $branchId);
            $this->applySearch($pos, $search, [$type === 'adjusted' ? 'root.name' : 'p.name'], 't.date');
            $amount = $type === 'sales' ? $net : "({$net} - {$hpp})";
            $name = $type === 'adjusted' ? 'root.name' : 'p.name';
            $pos->selectRaw("t.date as date, t.branch_id, t.created_at as tx_date, t.invoice_number as invoice, {$name} as name, NULL as courier_id, 'Penjualan' as type")
                ->selectRaw("{$amount} as amount, {$net} as revenue, {$hpp} as hpp, 0 as correction, d.quantity as quantity, 1 as item_count, (COALESCE(d.price, 0) * COALESCE(d.quantity, 0)) as gross_amount, COALESCE(d.discount, 0) as product_discount, COALESCE(d.diskon_global, 0) as prorata_discount, d.price as unit_price, COALESCE(d.subtotal_hpp / NULLIF(d.quantity, 0), 0) as unit_hpp");

            if ($type !== 'adjusted') return $pos;

            $sort = DB::table('sortir_transaction_detail as d')
                ->join('sortir_transaction as t', 'd.sortir_id', '=', 't.id')
                ->leftJoin('product_child as pc', 'd.product_id', '=', 'pc.product_id')
                ->join('products as root', 'root.id', '=', DB::raw('COALESCE(pc.parent_id, d.product_id)'))
                ->whereBetween('t.date', [$startDate, $endDate]);
            if ($branchId === 'null') $sort->whereNull('t.branch_id');
            elseif ($branchId !== 'all') $sort->where('t.branch_id', $branchId);
            $this->scopeBranch($sort, 't.branch_id', (string) $branchId);
            $this->applySearch($sort, $search, ['root.name'], 't.date');
            $sort->selectRaw("t.date as date, t.branch_id, t.created_at as tx_date, t.invoice_number as invoice, root.name as name, NULL as courier_id, 'Sortir Barang Buang' as type")
                ->selectRaw('-COALESCE(d.subtotal, 0) as amount, 0 as revenue, 0 as hpp, COALESCE(d.subtotal, 0) as correction, d.quantity as quantity, 1 as item_count, COALESCE(d.subtotal, 0) as gross_amount, 0 as product_discount, 0 as prorata_discount, NULL as unit_price, 0 as unit_hpp');

            $opnameValue = '(o.difference * COALESCE(o.hpp, o.avg_price, 0))';
            $opname = DB::table('stock_opname as o')
                ->leftJoin('product_child as pc', 'o.product_id', '=', 'pc.product_id')
                ->join('products as root', 'root.id', '=', DB::raw('COALESCE(pc.parent_id, o.product_id)'))
                ->whereBetween('o.date', [$startDate, $endDate])
                ->where('o.status', 'selesai');
            if ($branchId === 'null') $opname->whereNull('o.branch_id');
            elseif ($branchId !== 'all') $opname->where('o.branch_id', $branchId);
            $this->scopeBranch($opname, 'o.branch_id', (string) $branchId);
            $this->applySearch($opname, $search, ['root.name'], 'o.date');
            $opname->selectRaw("o.date as date, o.branch_id, o.created_at as tx_date, o.code as invoice, root.name as name, NULL as courier_id, 'Stock Opname' as type")
                ->selectRaw("{$opnameValue} as amount, 0 as revenue, 0 as hpp, -{$opnameValue} as correction, o.difference as quantity, 1 as item_count, 0 as gross_amount, 0 as product_discount, 0 as prorata_discount, NULL as unit_price, 0 as unit_hpp");

            return $pos->unionAll($sort)->unionAll($opname);
        }

        if ($type === 'shipping') {
            $shippingDiscount = 'CASE WHEN COALESCE(t.ongkir_discount, 0) BETWEEN 1 AND 100 THEN COALESCE(t.ongkir, 0) * t.ongkir_discount / 100 ELSE COALESCE(t.ongkir_discount, 0) END';
            $query = DB::table('pos_transaction as t')
                ->leftJoin('kurir as k', 't.courier_id', '=', 'k.id')
                ->leftJoin('branch', 't.branch_id', '=', 'branch.id')
                ->whereBetween('t.date', [$startDate, $endDate])
                ->whereNull('t.deleted_at')
                ->where('t.ongkir', '>', 0)
                ->whereIn('t.status', ['paid', 'debt']);
            if ($branchId === 'null') $query->whereNull('t.branch_id');
            elseif ($branchId !== 'all') $query->where('t.branch_id', $branchId);
            $this->scopeBranch($query, 't.branch_id', (string) $branchId);
            if ($search !== '') {
                if ($request->input('group_by') === 'date') {
                    $this->applySearch($query, $search, ['branch.name'], 't.date');
                } else {
                    $this->applySearch($query, $search, ['k.name', 'branch.name']);
                }
            }
            if ($request->filled('courier_id') && $request->courier_id !== 'all') {
                if ($request->courier_id === 'null') $query->whereNull('t.courier_id');
                else $query->where('t.courier_id', $request->courier_id);
            }
            return $query->selectRaw("t.date as date, t.branch_id, t.created_at as tx_date, t.invoice_number as invoice, COALESCE(k.name, 'Tanpa Kurir / Lainnya') as name, t.courier_id as courier_id, 'Ongkos Kirim' as type")
                ->selectRaw("COALESCE(t.ongkir, 0) - ({$shippingDiscount}) as amount, 0 as revenue, 0 as hpp, 0 as correction, 0 as quantity, 1 as item_count, COALESCE(t.ongkir, 0) as gross_amount, 0 as product_discount, ({$shippingDiscount}) as prorata_discount, NULL as unit_price, 0 as unit_hpp");
        }

        $query = DB::table('sortir_transaction_detail as d')
            ->join('sortir_transaction as t', 'd.sortir_id', '=', 't.id')
            ->join('products as p', 'd.product_id', '=', 'p.id')
            ->whereBetween('t.date', [$startDate, $endDate]);
        if ($branchId === 'null') $query->whereNull('t.branch_id');
        elseif ($branchId !== 'all') $query->where('t.branch_id', $branchId);
        $this->applySearch($query, $search, ['p.name'], 't.date');
        return $query->selectRaw("t.date as date, t.branch_id, t.created_at as tx_date, t.invoice_number as invoice, p.name as name, NULL as courier_id, 'Barang Buang' as type")
            ->selectRaw('COALESCE(d.subtotal, 0) as amount, 0 as revenue, 0 as hpp, 0 as correction, d.quantity as quantity, 1 as item_count, (COALESCE(d.price, 0) * COALESCE(d.quantity, 0)) as gross_amount, COALESCE(d.discount, 0) as product_discount, 0 as prorata_discount, NULL as unit_price, 0 as unit_hpp');
    }

    private function rupiah($value): string
    {
        $number = (float) $value;
        return ($number < 0 ? '- ' : '').'Rp '.number_format(abs($number), 0, ',', '.');
    }
}
