<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** What POS balancing adds to (or takes from) revenue, dated by the sale it corrects. */
class PosBalancingRevenue
{
    /** @return Collection<string, string> sale date => amount */
    public static function byDate(array $branchIds, ?int $branchId, string $from, string $to): Collection
    {
        return DB::table('pos_balancing as balancing')
            ->join('pos_transaction as sale', 'sale.id', '=', 'balancing.pos_id')
            ->whereNull('sale.deleted_at')
            ->whereIn('sale.status', ['paid', 'debt'])
            ->whereIn('sale.branch_id', $branchIds)
            ->when($branchId !== null, fn ($query) => $query->where('sale.branch_id', $branchId))
            ->whereBetween('sale.date', [$from, $to])
            ->groupBy('sale.date')
            ->select('sale.date', DB::raw('SUM(balancing.amount) AS amount'))
            ->pluck('amount', 'date');
    }
}
