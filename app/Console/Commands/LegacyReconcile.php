<?php

namespace App\Console\Commands;

use App\Support\LegacyImport\Importer;
use App\Support\LegacyImport\Reconciler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyReconcile extends Command
{
    protected $signature = 'legacy:reconcile
        {source : name of the legacy database}
        {--all : also list the checks that passed}';

    protected $description = 'Compare the legacy database with this one (counts, checksums, POS revenue, stock, HPP, orphans, logins). Read only.';

    public function handle(): int
    {
        $target = (string) DB::connection()->getDatabaseName();
        $import = new Importer((string) $this->argument('source'), $target);

        $this->line("legacy: <info>{$import->source}</info>   imported: <info>{$target}</info>");
        $started = microtime(true);
        $rec = new Reconciler($import);
        $rows = $rec->run();

        $counts = ['OK' => 0, 'WARN' => 0, 'FAIL' => 0];
        foreach ($rows as $r) {
            $counts[$r['status']] = ($counts[$r['status']] ?? 0) + 1;
        }

        $show = $this->option('all') ? $rows : array_filter($rows, fn ($r) => $r['status'] !== 'OK');
        if ($show) {
            $this->table(['group', 'check', 'legacy', 'imported', 'status', 'note'], array_map(fn ($r) => array_values($r), $show));
        }

        $this->line(sprintf('%d check(s): %d OK, %d WARN, %d FAIL  (%.1fs)', count($rows), $counts['OK'], $counts['WARN'], $counts['FAIL'], microtime(true) - $started));

        return $counts['FAIL'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
