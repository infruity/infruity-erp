<?php

namespace Modules\Pos\Http\Controllers;

use App\Models\RoleMenu;
use Modules\Master\Entities\UserBranch;
use App\Support\PosDiscount;
use App\Traits\HasAccessControl;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pos\Entities\PosBalancing;
use Modules\Pos\Entities\PosModel;

/**
 * POS balancing changes revenue only. The sale, its lines, payments, stock and HPP are never touched.
 * API for the frontend: docs/api/pos-balancing.md
 */
class PosBalancingController extends Controller
{
    use HasAccessControl;

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAccess('pos.receipt');

        $data = $request->validate([
            'pos_id' => 'required|integer',
            'amount' => 'required|numeric|decimal:0,2|not_in:0|min:-2000000000|max:2000000000',
            'note' => 'required|string|max:1000',
            'idempotency_key' => 'required|string|max:64',
        ]);

        return $this->record((int) $data['pos_id'], (float) $data['amount'], $data['note'], $data['idempotency_key']);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $this->authorizeAccess('pos.receipt');

        $data = $request->validate([
            'note' => 'required|string|max:1000',
            'idempotency_key' => 'required|string|max:64',
        ]);

        $original = PosBalancing::findOrFail($id);
        abort_if($original->reversal_of_id !== null, 422, 'Baris pembatalan tidak dapat dibatalkan.');
        abort_unless($original->created_by === (int) Auth::id() || RoleMenu::checkAccess('pos-balancing.backdate'), 403,
            'Hanya pembuatnya atau atasan yang dapat membatalkan balancing ini.');

        return $this->record($original->pos_id, -$original->amount, $data['note'], $data['idempotency_key'], $original->id);
    }

    private function record(int $posId, float $amount, string $note, string $key, ?int $reversalOf = null): JsonResponse
    {
        $branchIds = UserBranch::getUserBranch();
        $userId = (int) Auth::id();
        $mayBackdate = RoleMenu::checkAccess('pos-balancing.backdate');

        return DB::transaction(function () use ($posId, $amount, $note, $key, $reversalOf, $branchIds, $userId, $mayBackdate) {
            // First statement of the transaction: two balancings of one sale run one after the other.
            $sale = PosModel::whereIn('branch_id', $branchIds)->lockForUpdate()->findOrFail($posId);

            $saved = PosBalancing::where('idempotency_key', $key)->lockForUpdate()->first();
            if ($saved) {
                $same = $saved->created_by === $userId && $saved->pos_id === $posId
                    && $saved->reversal_of_id === $reversalOf && abs($saved->amount - $amount) < 0.005;
                if (! $same) {
                    throw ValidationException::withMessages(['idempotency_key' => 'Kunci ini sudah dipakai untuk data lain.']);
                }

                return $this->saved($saved, $sale, 200);
            }

            if (! in_array($sale->status, ['paid', 'debt'], true)) {
                throw ValidationException::withMessages(['pos_id' => 'Balancing hanya untuk nota lunas atau utang.']);
            }
            if (! $mayBackdate && substr((string) $sale->date, 0, 10) !== now()->toDateString()) {
                throw ValidationException::withMessages(['pos_id' => 'Nota ini bukan nota hari ini. Balancing hanya dapat dilakukan oleh atasan.']);
            }
            if ($reversalOf !== null && PosBalancing::where('reversal_of_id', $reversalOf)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['pos_balancing' => 'Balancing ini sudah dibatalkan.']);
            }
            if ($this->revenueAfter($sale->id) + $amount < -0.005) {
                throw ValidationException::withMessages(['amount' => 'Omzet nota tidak boleh kurang dari 0.']);
            }

            $row = PosBalancing::create([
                'pos_id' => $sale->id,
                'amount' => $amount,
                'note' => $note,
                'reversal_of_id' => $reversalOf,
                'created_by' => $userId,
                'idempotency_key' => $key,
            ]);

            return $this->saved($row->refresh(), $sale, 201);
        });
    }

    /** The sale's revenue including every balancing so far. */
    private function revenueAfter(int $posId): float
    {
        $revenue = (float) DB::table('pos_transaction')->where('id', $posId)
            ->value(DB::raw(PosDiscount::revenueSql()));

        return $revenue + (float) PosBalancing::where('pos_id', $posId)->lockForUpdate()->get(['amount'])->sum('amount');
    }

    private function saved(PosBalancing $row, PosModel $sale, int $status): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $row,
            'revenue' => $this->revenueAfter($sale->id),
        ], $status);
    }

    /** The list of events. A cashier may read one sale's rows; the whole list needs the report permission. */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAnyAccess($request->filled('pos_id')
            ? ['pos-balancing.index', 'pos.receipt'] : ['pos-balancing.index']);

        $filters = $this->filters($request);

        $rows = $this->rows($filters)
            ->leftJoin('users as cashier', 'cashier.id_user', '=', 'balancing.created_by')
            ->leftJoin('branch', 'branch.id', '=', 'sale.branch_id')
            ->orderByDesc('balancing.created_at')->orderByDesc('balancing.id')
            ->select([
                'balancing.id', 'balancing.pos_id', 'sale.invoice_number', 'sale.date as sale_date',
                'sale.branch_id', 'branch.name as branch_name', 'balancing.amount', 'balancing.note',
                'balancing.reversal_of_id', 'reversal.id as cancelled_by_id',
                'balancing.created_by', 'cashier.nm_user as created_by_name', 'balancing.created_at',
            ])
            ->paginate($filters['per_page'] ?? 25);

        return response()->json($rows);
    }

    /**
     * How often each cashier balances. A cancellation is not an event of its own; a cancelled event still counts as
     * one that happened (cancelled_count) but adds nothing to the amounts.
     */
    public function summary(Request $request): JsonResponse
    {
        $this->authorizeAccess('pos-balancing.index');

        $filters = $this->filters($request);
        $events = fn () => $this->rows($filters)->whereNull('balancing.reversal_of_id');

        $cashiers = $events()
            ->leftJoin('users as cashier', 'cashier.id_user', '=', 'balancing.created_by')
            ->groupBy('balancing.created_by', 'cashier.nm_user')
            ->selectRaw('balancing.created_by AS user_id, cashier.nm_user AS name, COUNT(*) AS balancing_count,
                SUM(CASE WHEN reversal.id IS NOT NULL THEN 1 ELSE 0 END) AS cancelled_count,
                SUM(CASE WHEN reversal.id IS NULL AND balancing.amount > 0 THEN balancing.amount ELSE 0 END) AS surplus_total,
                SUM(CASE WHEN reversal.id IS NULL AND balancing.amount < 0 THEN balancing.amount ELSE 0 END) AS minus_total,
                COUNT(DISTINCT balancing.pos_id) AS balanced_sale_count')
            ->orderByDesc('balancing_count')
            ->get();

        // Sales of the same period and branches, to compare cashiers who serve different numbers of customers.
        $sales = DB::table('pos_transaction')
            ->whereNull('deleted_at')
            ->whereIn('status', ['paid', 'debt'])
            ->whereIn('branch_id', $this->branchScope($filters))
            ->whereIn('created_by', $cashiers->pluck('user_id'))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('date', '<=', $to))
            ->groupBy('created_by')
            ->selectRaw('created_by, COUNT(*) AS total')
            ->pluck('total', 'created_by');

        $perHour = $events()
            ->groupByRaw('HOUR(balancing.created_at)')
            ->orderByRaw('HOUR(balancing.created_at)')
            ->selectRaw('HOUR(balancing.created_at) AS hour, COUNT(*) AS total')
            ->get();

        $perDate = $events()
            ->groupByRaw('DATE(balancing.created_at)')
            ->orderByRaw('DATE(balancing.created_at)')
            ->selectRaw('DATE(balancing.created_at) AS date, COUNT(*) AS total')
            ->get();

        return response()->json([
            'cashiers' => $cashiers->map(fn ($row) => [
                'user_id' => (int) $row->user_id,
                'name' => $row->name,
                'balancing_count' => (int) $row->balancing_count,
                'cancelled_count' => (int) $row->cancelled_count,
                'surplus_total' => (float) $row->surplus_total,
                'minus_total' => (float) $row->minus_total,
                'net_total' => (float) $row->surplus_total + (float) $row->minus_total,
                'balanced_sale_count' => (int) $row->balanced_sale_count,
                'sale_count' => (int) ($sales[$row->user_id] ?? 0),
            ])->values(),
            'per_hour' => $perHour->map(fn ($row) => ['hour' => (int) $row->hour, 'total' => (int) $row->total]),
            'per_date' => $perDate->map(fn ($row) => ['date' => $row->date, 'total' => (int) $row->total]),
        ]);
    }

    /** from / to are the days the balancing was done, not the days of the sales. */
    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'branch_id' => 'nullable|integer',
            'cashier_id' => 'nullable|integer',
            'pos_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);
    }

    private function branchScope(array $filters): array
    {
        $allowed = array_map('intval', UserBranch::getUserBranch());

        return isset($filters['branch_id']) ? array_values(array_intersect($allowed, [(int) $filters['branch_id']])) : $allowed;
    }

    private function rows(array $filters): Builder
    {
        return DB::table('pos_balancing as balancing')
            ->join('pos_transaction as sale', 'sale.id', '=', 'balancing.pos_id')
            ->leftJoin('pos_balancing as reversal', 'reversal.reversal_of_id', '=', 'balancing.id')
            ->whereNull('sale.deleted_at')
            ->whereIn('sale.branch_id', $this->branchScope($filters))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('balancing.created_at', '>=', $from . ' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('balancing.created_at', '<=', $to . ' 23:59:59'))
            ->when($filters['cashier_id'] ?? null, fn ($query, $id) => $query->where('balancing.created_by', $id))
            ->when($filters['pos_id'] ?? null, fn ($query, $id) => $query->where('balancing.pos_id', $id));
    }
}
