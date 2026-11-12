<?php

namespace App\Console\Commands;

use App\Support\LegacyImport\Importer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LegacyImport extends Command
{
    protected $signature = 'legacy:import
        {source : name of the database that holds the legacy data (same MySQL server)}
        {--dry-run : only run the preflight and show the plan}
        {--confirm-target= : required for a real run; must equal the TARGET database name (the current DB_DATABASE)}';

    protected $description = 'Copy a legacy Infruity database into this (freshly migrated) database. DESTRUCTIVE for the target.';

    public function handle(): int
    {
        $target = (string) DB::connection()->getDatabaseName();
        $source = (string) $this->argument('source');

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->error('MySQL only.');

            return self::FAILURE;
        }

        try {
            $import = new Importer($source, $target);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line("source (legacy, read only): <info>{$source}</info>");
        $this->line("target (will be TRUNCATED table by table): <info>{$target}</info>");

        $started = microtime(true);
        $plan = $import->plan();

        $problems = 0;
        foreach ($plan['tables'] as $table => $info) {
            foreach ($info['notes'] as $note) {
                $this->line("  note  {$table}: {$note}");
            }
            foreach ($info['problems'] as $problem) {
                $problems++;
                $this->error("  PROBLEM {$table}: {$problem}");
            }
        }
        if ($plan['source_only']) {
            $this->line('  not imported (only in legacy): ' . implode(', ', $plan['source_only']));
        }
        if ($plan['target_only']) {
            $this->line('  left untouched (only in target): ' . implode(', ', $plan['target_only']));
        }
        $this->line(sprintf('preflight: %d table(s), %d problem(s), %.1fs', count($plan['tables']), $problems, microtime(true) - $started));

        if ($problems > 0) {
            $this->error('Nothing was changed. Fix the problems above (usually: run the migrations on the target) and retry.');

            return self::FAILURE;
        }
        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was changed.');

            return self::SUCCESS;
        }
        if ($this->option('confirm-target') !== $target) {
            $this->error("Refusing to truncate '{$target}'. Re-run with --confirm-target={$target} if that is really the database to overwrite.");

            return self::FAILURE;
        }

        $failed = 0;
        $import->run($plan['tables'], function (string $table, int $expected, int $inserted) use (&$failed) {
            $ok = $expected === $inserted;
            $failed += $ok ? 0 : 1;
            $this->line(sprintf('  %s %-30s expected %6d  inserted %6d', $ok ? '<info>ok</info>  ' : '<error>FAIL</error>', $table, $expected, $inserted));
        });

        $this->line(sprintf('imported in %.1fs', microtime(true) - $started));
        if ($failed > 0) {
            $this->error("{$failed} table(s) differ from the expected row count.");

            return self::FAILURE;
        }

        $this->info('Import finished. Now run: php artisan legacy:reconcile ' . $source);

        return self::SUCCESS;
    }
}
