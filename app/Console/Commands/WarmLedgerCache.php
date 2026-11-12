<?php

namespace App\Console\Commands;

use App\Support\LedgerCache;
use App\Support\NegativeStockWarning;
use App\Support\TotalAsetLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Recomputes the ledger-derived results that LedgerCache keeps, as soon as the ledger has changed, so a user does not
 * wait for them: the latest HPP and stock of the total-aset report (all branches) and the negative-stock banner of
 * every branch set in use. Run every minute from cron; when the ledger has not changed it costs one fingerprint (~45 ms).
 *
 * It only calls the same readers the pages call; it never decides a number itself.
 */
class WarmLedgerCache extends Command
{
    protected $signature = 'ledger:warm';

    protected $description = 'Warm the ledger caches when the stock ledger changed since the last run';

    public function handle(): int
    {
        $fingerprint = LedgerCache::fingerprint();
        if ($fingerprint === null) {
            $this->line('No MySQL/MariaDB ledger here; nothing to warm.');

            return self::SUCCESS;
        }

        if (Cache::get('ledger_warm:fingerprint') === $fingerprint) {
            return self::SUCCESS;
        }

        $lock = Cache::lock('ledger_warm', 300);
        if (! $lock->get()) {
            return self::SUCCESS; // another run is already doing it
        }

        try {
            $started = microtime(true);

            TotalAsetLedger::latestHpp();
            TotalAsetLedger::stockRows(TotalAsetLedger::stockQuery());

            foreach ($this->branchSets() as $set) {
                NegativeStockWarning::forBranches($set);
            }

            // The fingerprint taken BEFORE computing: a change during the run is picked up by the next one.
            Cache::forever('ledger_warm:fingerprint', $fingerprint);
            $this->info(sprintf('Warmed in %d ms.', (microtime(true) - $started) * 1000));
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }

    /** @return list<list<int>> every branch set a page can ask for: all branches (administrators) and each user's own. */
    private function branchSets(): array
    {
        $sets = [DB::table('branch')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all()];

        $perUser = DB::table('user_branch')
            ->join('users', 'users.id_user', '=', 'user_branch.user_id')
            ->where('users.is_aktif', 1)
            ->orderBy('user_branch.branch_id')
            ->get(['user_branch.user_id', 'user_branch.branch_id'])
            ->groupBy('user_id');

        foreach ($perUser as $rows) {
            $sets[] = $rows->pluck('branch_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
        }

        return array_values(array_unique(array_filter($sets), SORT_REGULAR));
    }
}
