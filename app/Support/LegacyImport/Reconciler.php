<?php

namespace App\Support\LegacyImport;

use Illuminate\Support\Facades\DB;

/**
 * Compares the legacy database with the imported one. Every check is an independent query on both sides:
 * row counts, a checksum (SUM) of every numeric column, POS revenue per branch and month, the stock and HPP
 * ledgers, orphans, and the login data. FAIL = the import changed something it should not have.
 */
final class Reconciler
{
    /** Relations that exist in the app but not as foreign keys: [child table, child column, parent table, parent column]. */
    private const RELATIONS = [
        ['pos_transaction_detail', 'pos_id', 'pos_transaction', 'id'],
        ['pos_transaction_detail', 'product_id', 'products', 'id'],
        ['pos_payment', 'pos_id', 'pos_transaction', 'id'],
        ['pos_transaction', 'branch_id', 'branch', 'id'],
        ['pos_transaction', 'customer_id', 'customer', 'id'],
        ['wholesale_product', 'wholesale_id', 'wholesale', 'id'],
        ['wholesale_product', 'product_id', 'products', 'id'],
        ['wholesale', 'branch_id', 'branch', 'id'],
        ['transfer_detail', 'transfer_id', 'transfer', 'id'],
        ['transfer_detail', 'product_id', 'products', 'id'],
        ['transfer', 'branch_id', 'branch', 'id'],
        ['transfer', 'branch_destination_id', 'branch', 'id'],
        ['stock_opname', 'product_id', 'products', 'id'],
        ['stock_opname', 'branch_id', 'branch', 'id'],
        ['production_detail', 'production_id', 'production', 'id'],
        ['production', 'product_id', 'products', 'id'],
        ['sortir_transaction_detail', 'sortir_id', 'sortir_transaction', 'id'],
        ['order_book_detail', 'order_book_id', 'order_book', 'id'],
        ['product_branch', 'product_id', 'products', 'id'],
        ['product_branch', 'branch_id', 'branch', 'id'],
        ['customer_address', 'customer_id', 'customer', 'id'],
        ['role_user', 'id_user', 'users', 'id_user'],
        ['role_user', 'id_role', 'role', 'id_role'],
        ['role_menu', 'id_role', 'role', 'id_role'],
        ['user_branch', 'user_id', 'users', 'id_user'],
        ['user_branch', 'branch_id', 'branch', 'id'],
        ['staff_role', 'staff_id', 'staff', 'id'],
        ['user_permissions', 'id_user', 'users', 'id_user'],
    ];

    /** Business numbers that must be unique per transaction type. */
    private const BUSINESS_KEYS = [
        ['pos_transaction', 'invoice_number'],
        ['wholesale', 'order_number'],
        ['transfer', 'invoice_number'],
        ['stock_opname', 'code'],
    ];

    /** @var list<array{group:string, check:string, source:string, target:string, status:string, note:string}> */
    private array $rows = [];

    public function __construct(private Importer $im)
    {
    }

    /** @return list<array{group:string, check:string, source:string, target:string, status:string, note:string}> */
    public function run(): array
    {
        $this->rows = [];
        DB::statement('SET SESSION ' . config('database.cte_recursion_variable', 'cte_max_recursion_depth') . ' = 10000000');

        $tables = array_values(array_intersect($this->im->baseTables($this->im->target), $this->im->baseTables($this->im->source)));
        $tables = array_values(array_diff($tables, Importer::KEEP_TARGET_TABLES));

        $this->rowCounts($tables);
        $this->checksums($tables);
        $this->posByBranchAndMonth();
        $this->stockLedger();
        $this->hppLedger();
        $this->orphans();
        $this->businessKeys();
        $this->logins();

        return $this->rows;
    }

    public function failures(): int
    {
        return count(array_filter($this->rows, fn ($r) => $r['status'] === 'FAIL'));
    }

    private function add(string $group, string $check, mixed $source, mixed $target, string $status, string $note = ''): void
    {
        $this->rows[] = ['group' => $group, 'check' => $check, 'source' => (string) $source, 'target' => (string) $target, 'status' => $status, 'note' => $note];
    }

    private function scalar(string $sql): mixed
    {
        $row = DB::selectOne($sql);

        return $row ? array_values((array) $row)[0] : null;
    }

    private function tableExists(string $db, string $table): bool
    {
        return (int) DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [$db, $table]
        )->c > 0;
    }

    private function same(mixed $a, mixed $b): bool
    {
        return abs((float) $a - (float) $b) <= max(0.005, abs((float) $a) * 1e-9);
    }

    /** @param list<string> $tables */
    private function rowCounts(array $tables): void
    {
        foreach ($tables as $t) {
            $where = $this->im->filterSql($t);
            $src = (int) $this->scalar("SELECT COUNT(*) FROM {$this->im->src($t)} WHERE {$where}");
            $all = (int) $this->scalar("SELECT COUNT(*) FROM {$this->im->src($t)}");
            $dst = (int) $this->scalar("SELECT COUNT(*) FROM {$this->im->dst($t)}");
            if ($src === 0 && $dst === 0) {
                continue;
            }
            $note = $all !== $src ? ($all - $src) . ' legacy row(s) intentionally skipped' : '';
            $this->add('rows', $t, $src, $dst, $src === $dst ? 'OK' : 'FAIL', $note);
        }
    }

    /** @param list<string> $tables */
    private function checksums(array $tables): void
    {
        foreach ($tables as $t) {
            $where = $this->im->filterSql($t);
            $dstCols = $this->im->columns($this->im->target, $t);
            $srcCols = $this->im->columns($this->im->source, $t);

            foreach ($dstCols as $name => $c) {
                $numeric = in_array($c->data_type, ['decimal', 'double', 'float', 'int', 'bigint', 'smallint', 'mediumint'], true);
                if (! $numeric || $c->column_key === 'PRI' || $name === 'id' || $name === 'seq' || str_ends_with($name, '_id') || ! isset($srcCols[$name])) {
                    continue;
                }
                $col = Importer::q($name);
                $srcExpr = $col;
                $a = $this->scalar("SELECT COALESCE(SUM({$srcExpr}), 0) FROM {$this->im->src($t)} WHERE {$where}");
                $b = $this->scalar("SELECT COALESCE(SUM({$col}), 0) FROM {$this->im->dst($t)}");
                if ((float) $a == 0.0 && (float) $b == 0.0) {
                    continue;
                }
                $this->add('checksum', "{$t}.{$name}", $a, $b, $this->same($a, $b) ? 'OK' : 'FAIL');
            }
        }
    }

    private function posByBranchAndMonth(): void
    {
        $sql = fn (string $db) => "SELECT COALESCE(branch_id, -1) b, DATE_FORMAT(date, '%Y-%m') m, status s, COUNT(*) n, ROUND(SUM(total), 2) t, ROUND(SUM(paid), 2) p
            FROM " . Importer::q($db) . ".pos_transaction GROUP BY 1, 2, 3 ORDER BY 1, 2, 3";
        $key = fn ($r) => "{$r->b}|{$r->m}|{$r->s}";
        $src = collect(DB::select($sql($this->im->source)))->keyBy($key);
        $dst = collect(DB::select($sql($this->im->target)))->keyBy($key);

        $bad = 0;
        foreach ($src->keys()->merge($dst->keys())->unique() as $k) {
            $a = $src[$k] ?? null;
            $b = $dst[$k] ?? null;
            if (! $a || ! $b || $a->n != $b->n || ! $this->same($a->t, $b->t) || ! $this->same($a->p, $b->p)) {
                $bad++;
            }
        }
        $this->add('pos', 'revenue per branch / month / status (groups)', $src->count(), $dst->count(), $bad === 0 && $src->count() === $dst->count() ? 'OK' : 'FAIL', $bad ? "{$bad} group(s) differ" : '');
        $this->add('pos', 'grand total', $src->sum('t'), $dst->sum('t'), $this->same($src->sum('t'), $dst->sum('t')) ? 'OK' : 'FAIL');
    }

    private function stockLedger(): void
    {
        $s = Importer::q($this->im->source) . '.product_stock';
        $d = Importer::q($this->im->target) . '.product_stock';

        $sRows = (int) $this->scalar("SELECT COUNT(*) FROM {$s}");
        $dRows = (int) $this->scalar("SELECT COUNT(*) FROM {$d}");
        $this->add('stock', 'product_stock rows (product x branch)', $sRows, $dRows, $sRows === $dRows ? 'OK' : 'FAIL');

        $a = $this->scalar("SELECT ROUND(COALESCE(SUM(stock_available), 0), 2) FROM {$s}");
        $b = $this->scalar("SELECT ROUND(COALESCE(SUM(stock_available), 0), 2) FROM {$d}");
        $this->add('stock', 'total stock_available', $a, $b, $this->same($a, $b) ? 'OK' : 'FAIL');

        $mismatch = (int) $this->scalar("SELECT COUNT(*) FROM {$s} a LEFT JOIN {$d} b ON b.id = a.id AND b.branch_id <=> a.branch_id
            WHERE b.id IS NULL OR ABS(a.stock_available - b.stock_available) > 0.005");
        $this->add('stock', 'product x branch rows whose stock differs', 0, $mismatch, $mismatch === 0 ? 'OK' : 'FAIL');

        $neg = (int) $this->scalar("SELECT COUNT(*) FROM {$d} WHERE stock_available < 0");
        $negSrc = (int) $this->scalar("SELECT COUNT(*) FROM {$s} WHERE stock_available < 0");
        $this->add('stock', 'rows with negative stock (rule: never negative)', $negSrc, $neg, $neg === 0 ? 'OK' : ($neg <= $negSrc ? 'WARN' : 'FAIL'), $neg ? 'pre-existing in legacy data, needs a stock opname' : '');
    }

    private function hppLedger(): void
    {
        $latest = fn (string $db) => 'SELECT product_id, hpp_berjalan, qty_berjalan, total_aset_berjalan FROM (
            SELECT h.*, ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY seq DESC) rn FROM ' . Importer::q($db) . '.product_hpp h) x WHERE rn = 1';

        $src = collect(DB::select($latest($this->im->source)))->keyBy('product_id');
        $dst = collect(DB::select($latest($this->im->target)))->keyBy('product_id');

        $bad = 0;
        foreach ($src->keys()->merge($dst->keys())->unique() as $id) {
            $a = $src[$id] ?? null;
            $b = $dst[$id] ?? null;
            if (! $a || ! $b || ! $this->same($a->hpp_berjalan, $b->hpp_berjalan) || ! $this->same($a->qty_berjalan, $b->qty_berjalan) || ! $this->same($a->total_aset_berjalan, $b->total_aset_berjalan)) {
                $bad++;
            }
        }
        $this->add('hpp', 'products with a ledger', $src->count(), $dst->count(), $src->count() === $dst->count() ? 'OK' : 'FAIL');
        $this->add('hpp', 'latest hpp / qty / asset per product that differ', 0, $bad, $bad === 0 ? 'OK' : 'FAIL');
        $a = round($src->sum('total_aset_berjalan'), 2);
        $b = round($dst->sum('total_aset_berjalan'), 2);
        $this->add('hpp', 'total asset value (sum of latest)', $a, $b, $this->same($a, $b) ? 'OK' : 'FAIL');
    }

    private function orphans(): void
    {
        foreach (self::RELATIONS as [$child, $col, $parent, $pcol]) {
            $count = fn (string $db) => (int) $this->scalar(
                'SELECT COUNT(*) FROM ' . Importer::q($db) . '.' . Importer::q($child) . ' c LEFT JOIN ' . Importer::q($db) . '.' . Importer::q($parent)
                . ' p ON p.' . Importer::q($pcol) . ' = c.' . Importer::q($col) . ' WHERE c.' . Importer::q($col) . ' IS NOT NULL AND c.' . Importer::q($col) . ' <> 0 AND p.' . Importer::q($pcol) . ' IS NULL'
            );
            // A table that only exists in the new schema (staff_role, ...) has no legacy rows, so it cannot be orphaned there.
            $both = fn (string $db) => $this->tableExists($db, $child) && $this->tableExists($db, $parent);
            $a = $both($this->im->source) ? $count($this->im->source) : 0;
            $b = $both($this->im->target) ? $count($this->im->target) : 0;
            if ($a === 0 && $b === 0) {
                continue;
            }
            $this->add('orphans', "{$child}.{$col} -> {$parent}.{$pcol}", $a, $b, $b > $a ? 'FAIL' : 'WARN', $b > $a ? 'import introduced orphans' : 'already orphaned in legacy data');
        }
        if (! array_filter($this->rows, fn ($r) => $r['group'] === 'orphans')) {
            $this->add('orphans', 'all checked relations', 0, 0, 'OK');
        }
    }

    private function businessKeys(): void
    {
        foreach (self::BUSINESS_KEYS as [$t, $c]) {
            $dup = fn (string $db) => (int) $this->scalar('SELECT COUNT(*) FROM (SELECT ' . Importer::q($c) . ' FROM ' . Importer::q($db) . '.' . Importer::q($t)
                . ' WHERE ' . Importer::q($c) . " IS NOT NULL AND {$this->im->filterSql($t)} GROUP BY " . Importer::q($c) . ' HAVING COUNT(*) > 1) x');
            $a = $dup($this->im->source);
            $b = $dup($this->im->target);
            $this->add('keys', "duplicate {$t}.{$c}", $a, $b, $b > $a ? 'FAIL' : ($b > 0 ? 'WARN' : 'OK'), $b > 0 ? 'duplicates already in legacy data' : '');
        }
    }

    private function logins(): void
    {
        $same = (int) $this->scalar("SELECT COUNT(*) FROM {$this->im->src('users')} a JOIN {$this->im->dst('users')} b ON b.id_user = a.id_user
            AND b.password <=> a.password AND b.email <=> a.email AND b.username <=> a.username AND b.is_aktif <=> a.is_aktif");
        $all = (int) $this->scalar("SELECT COUNT(*) FROM {$this->im->src('users')}");
        $this->add('auth', 'users with identical id / email / username / password hash / active flag', $all, $same, $all === $same ? 'OK' : 'FAIL');

        $a = (int) $this->scalar("SELECT COUNT(*) FROM {$this->im->src('role_menu')}");
        $b = (int) $this->scalar("SELECT COUNT(*) FROM {$this->im->dst('role_menu')}");
        $this->add('auth', 'role_menu permission rows', $a, $b, $a === $b ? 'OK' : 'FAIL');

        $noRole = (int) $this->scalar("SELECT COUNT(*) FROM {$this->im->dst('users')} u WHERE u.is_aktif = 1 AND NOT EXISTS (SELECT 1 FROM {$this->im->dst('role_user')} r WHERE r.id_user = u.id_user)");
        $noRoleSrc = (int) $this->scalar("SELECT COUNT(*) FROM {$this->im->src('users')} u WHERE u.is_aktif = 1 AND NOT EXISTS (SELECT 1 FROM {$this->im->src('role_user')} r WHERE r.id_user = u.id_user)");
        $this->add('auth', 'active users without any role (they are logged out right after login)', $noRoleSrc, $noRole, $noRole > $noRoleSrc ? 'FAIL' : ($noRole > 0 ? 'WARN' : 'OK'), $noRole ? 'cannot use the app until given a role' : '');
    }
}
