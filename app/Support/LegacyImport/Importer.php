<?php

namespace App\Support\LegacyImport;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Copies the data of a legacy Infruity database into a freshly migrated one (same MySQL server).
 *
 * The legacy database has the same tables and column names as the migrated schema, but it has no primary keys,
 * no auto-increment and no foreign keys, a few narrower/wider column types, and a few columns that became NOT NULL.
 * So the copy is column-by-column by name, in STRICT mode (a value that does not fit stops the import instead of
 * being silently truncated), after a preflight that finds every such value first.
 *
 * Destructive for the TARGET: every copied table is truncated first. See docs/qa/phase2-import-rehearsal.md.
 */
final class Importer
{
    /** Target tables that are never touched (migrations bookkeeping, regional reference data loaded by migrations). */
    /**
     * Tables that only exist in the new schema but hold ids of tables the import replaces (staff, users). The
     * migrations backfill them from the seeded accounts; left alone they would attach those roles to unrelated legacy
     * staff. They are emptied by a real run; the legacy data has nothing for them.
     */
    public const RESET_TARGET_TABLES = ['staff_role', 'user_permissions'];

    public const KEEP_TARGET_TABLES = ['migrations', 'reg_provinces', 'reg_regencies', 'reg_districts', 'reg_villages'];

    /**
     * Legacy rows that must not be copied, with the reason.
     * wholesale id 0: temp drafts saved while the legacy table had no auto-increment (every one got id 0, no items).
     */
    public const ROW_FILTERS = [
        'wholesale' => ['sql' => '`id` <> 0', 'reason' => 'id 0 = junk temp drafts from the keyless legacy table (no items)'],
    ];

    /** Per-column source expressions that replace a plain copy. Keys: table => column => SQL over source columns. */
    public const EXPRESSIONS = [
        // "No value" is NULL in the new schema (migration make_unused_wholesale_columns_nullable); the legacy table forced 0 / ''.
        'wholesale' => [
            'branch_id' => 'NULLIF(`branch_id`, 0)',
            'supplier_id' => 'NULLIF(`supplier_id`, 0)',
            'uuid' => "NULLIF(`uuid`, '')",
        ],
        'wholesale_product' => ['category_id' => 'NULLIF(`category_id`, 0)'],
    ];

    /** Words that only say "this is a branch" and carry no identity; dropped when a branch code is derived from the name. */
    private const GENERIC_BRANCH_WORDS = ['toko', 'cabang', 'outlet', 'kantor', 'gerai', 'warung', 'store', 'branch', 'pusat'];

    private const NUMERIC_TYPES = ['decimal', 'double', 'float', 'int', 'bigint', 'smallint', 'mediumint', 'tinyint'];

    private ?bool $mariaDb = null;

    public function __construct(public readonly string $source, public readonly string $target)
    {
        foreach ([$source, $target] as $name) {
            if (! preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                throw new InvalidArgumentException("Invalid database name: {$name}");
            }
        }
        if ($source === $target) {
            throw new InvalidArgumentException('Source and target database must differ.');
        }
    }

    /**
     * A short code from a branch name, for legacy branches that have none (branch.code became NOT NULL + UNIQUE).
     * "Toko GBA" -> GBA, "Cabang Bandung" -> BAN, "Grand Bunder Regency" -> GBR. Returns '' when nothing usable is left.
     */
    public static function deriveBranchCode(string $name): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_values(array_filter($words, fn ($w) => ! in_array(strtolower($w), self::GENERIC_BRANCH_WORDS, true)));

        if ($words === []) {
            return '';
        }
        if (count($words) === 1) {
            return strtoupper(strlen($words[0]) <= 6 ? $words[0] : substr($words[0], 0, 3));
        }

        return strtoupper(substr(implode('', array_map(fn ($w) => $w[0], $words)), 0, 6));
    }

    /**
     * SQL for branch.code: keep an existing code, derive one from the name otherwise. Codes are made unique here
     * (a collision falls back to BR<id>) because the target enforces it. Returns [expression, notes].
     *
     * @return array{0: string, 1: list<string>}
     */
    private function branchCodeExpression(string $from, string $where): array
    {
        $rows = DB::select("SELECT id, name, code FROM {$from} WHERE ({$where}) ORDER BY id");
        $taken = [];
        foreach ($rows as $r) {
            if (trim((string) $r->code) !== '') {
                $taken[strtolower(trim((string) $r->code))] = true;
            }
        }

        $pdo = DB::getPdo();
        $cases = [];
        $notes = [];
        foreach ($rows as $r) {
            if (trim((string) $r->code) !== '') {
                continue;
            }
            $code = self::deriveBranchCode((string) $r->name);
            $how = 'derived from the name';
            if ($code === '' || isset($taken[strtolower($code)])) {
                $code = 'BR' . $r->id;
                $how = 'name gave no unique code, fell back to the id';
            }
            $taken[strtolower($code)] = true;
            $cases[] = 'WHEN ' . (int) $r->id . ' THEN ' . $pdo->quote($code);
            $notes[] = "branch {$r->id} \"{$r->name}\" had no code -> {$code} ({$how}); rename in Master > Cabang if the real code differs";
        }

        if ($cases === []) {
            return ['`code`', []];
        }

        return ["CASE WHEN NULLIF(`code`, '') IS NOT NULL THEN `code` ELSE CASE `id` " . implode(' ', $cases) . ' END END', $notes];
    }

    public static function q(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /** @return list<string> */
    public function baseTables(string $schema): array
    {
        return array_map(
            fn ($r) => $r->table_name,
            DB::select("SELECT table_name AS table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE' ORDER BY table_name", [$schema])
        );
    }

    /** @return array<string, object> column name => information_schema row */
    public function columns(string $schema, string $table): array
    {
        $rows = DB::select(
            'SELECT column_name AS column_name, data_type AS data_type, column_type AS column_type, is_nullable AS is_nullable,
                    column_default AS column_default, extra AS extra, column_key AS column_key,
                    numeric_precision AS numeric_precision, numeric_scale AS numeric_scale
             FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position',
            [$schema, $table]
        );

        $byName = [];
        foreach ($rows as $row) {
            $byName[$row->column_name] = $this->normalizeDefault($row);
        }

        return $byName;
    }

    /**
     * MySQL reports a column default as the plain value (NULL when there is none, DEFAULT_GENERATED in `extra` for an
     * expression). MariaDB reports the SQL literal instead: strings come quoted ('aktif', ''), a nullable column
     * without default comes as the text NULL, and an expression default is the bare function (current_timestamp()).
     * Rewrite the MariaDB form into the MySQL one so the callers can use the value as it is.
     */
    private function normalizeDefault(object $row): object
    {
        if ($row->column_default === null || ! $this->isMariaDb()) {
            return $row;
        }

        $default = (string) $row->column_default;

        if (strcasecmp($default, 'NULL') === 0) {
            $row->column_default = null;
        } elseif (strlen($default) >= 2 && $default[0] === "'" && str_ends_with($default, "'")) {
            $row->column_default = str_replace("''", "'", substr($default, 1, -1));
        } elseif (str_contains($default, '(')) {
            $row->extra = trim((string) $row->extra . ' DEFAULT_GENERATED');
        }

        return $row;
    }

    private function isMariaDb(): bool
    {
        return $this->mariaDb ??= stripos((string) DB::selectOne('SELECT VERSION() AS v')->v, 'mariadb') !== false;
    }

    public function src(string $table): string
    {
        return self::q($this->source) . '.' . self::q($table);
    }

    public function dst(string $table): string
    {
        return self::q($this->target) . '.' . self::q($table);
    }

    public function filterSql(string $table): string
    {
        return self::ROW_FILTERS[$table]['sql'] ?? '1=1';
    }

    /**
     * @return array{tables: array<string, array{columns: array<string,string>, problems: list<string>, notes: list<string>}>, source_only: list<string>, target_only: list<string>}
     */
    public function plan(): array
    {
        $sourceTables = $this->baseTables($this->source);
        $targetTables = array_values(array_diff($this->baseTables($this->target), self::KEEP_TARGET_TABLES));

        $plan = [
            'tables' => [],
            'source_only' => array_values(array_diff($sourceTables, $targetTables, self::KEEP_TARGET_TABLES)),
            'target_only' => array_values(array_diff($targetTables, $sourceTables)),
        ];

        foreach (array_intersect($targetTables, $sourceTables) as $table) {
            $plan['tables'][$table] = $this->planTable($table);
        }

        return $plan;
    }

    /** @return array{columns: array<string,string>, problems: list<string>, notes: list<string>} */
    private function planTable(string $table): array
    {
        $src = $this->columns($this->source, $table);
        $dst = $this->columns($this->target, $table);
        $from = $this->src($table);
        $where = $this->filterSql($table);
        $pdo = DB::getPdo();

        $columns = [];
        $problems = [];
        $notes = [];

        if (isset(self::ROW_FILTERS[$table])) {
            $skipped = (int) DB::selectOne("SELECT COUNT(*) c FROM {$from} WHERE NOT ({$where})")->c;
            $notes[] = "skips {$skipped} row(s): " . self::ROW_FILTERS[$table]['reason'];
        }

        foreach ($dst as $name => $d) {
            if (! isset($src[$name])) {
                if ($d->is_nullable === 'NO' && $d->column_default === null && ! str_contains((string) $d->extra, 'auto_increment')) {
                    $problems[] = "column {$name} exists only in the target, is NOT NULL and has no default";
                }
                continue;
            }

            $s = $src[$name];
            $col = self::q($name);
            $expr = self::EXPRESSIONS[$table][$name] ?? $col;

            if (isset(self::EXPRESSIONS[$table][$name])) {
                $notes[] = "column {$name}: {$expr}";
            }

            if ($table === 'branch' && $name === 'code' && isset($src['name'], $src['id'])) {
                [$expr, $branchNotes] = $this->branchCodeExpression($from, $where);
                array_push($notes, ...$branchNotes);
                $columns[$name] = $expr;
                continue;
            }

            // NOT NULL in the target, nullable in the source.
            if ($d->is_nullable === 'NO' && $s->is_nullable === 'YES' && ! isset(self::EXPRESSIONS[$table][$name])) {
                $nulls = (int) DB::selectOne("SELECT COUNT(*) c FROM {$from} WHERE ({$where}) AND {$col} IS NULL")->c;
                if ($nulls > 0) {
                    if ($d->column_default !== null && ! str_contains((string) $d->extra, 'DEFAULT_GENERATED')) {
                        $expr = "COALESCE({$col}, " . $pdo->quote((string) $d->column_default) . ')';
                        $notes[] = "column {$name}: {$nulls} NULL -> default {$d->column_default}";
                    } else {
                        $problems[] = "column {$name}: {$nulls} NULL value(s) but the target is NOT NULL without a usable default";
                    }
                }
            }

            // Enum values the target cannot store.
            if ($d->data_type === 'enum') {
                preg_match_all("/'((?:[^']|'')*)'/", $d->column_type, $m);
                $allowed = array_map(fn ($v) => strtolower(str_replace("''", "'", $v)), $m[1]);
                foreach (DB::select("SELECT {$col} v, COUNT(*) c FROM {$from} WHERE ({$where}) AND {$col} IS NOT NULL GROUP BY {$col}") as $row) {
                    if (in_array(strtolower((string) $row->v), $allowed, true)) {
                        continue;
                    }
                    if ((string) $row->v === '' && $d->column_default !== null) {
                        // MySQL stores '' when a non-strict write used a value the enum did not know. The app has
                        // always treated '' like the default, so make that explicit.
                        $expr = "COALESCE(NULLIF({$col}, ''), " . $pdo->quote((string) $d->column_default) . ')';
                        $notes[] = "column {$name}: {$row->c} empty enum value -> default {$d->column_default}";
                        continue;
                    }
                    $problems[] = "column {$name}: {$row->c} row(s) with value '{$row->v}' which the target enum {$d->column_type} cannot store (run the migrations)";
                }
            }

            // Invalid JSON for a json column.
            if ($d->data_type === 'json' && $s->data_type !== 'json') {
                $bad = (int) DB::selectOne("SELECT COUNT(*) c FROM {$from} WHERE ({$where}) AND {$col} IS NOT NULL AND {$col} <> '' AND JSON_VALID({$col}) = 0")->c;
                if ($bad > 0) {
                    $problems[] = "column {$name}: {$bad} value(s) are not valid JSON";
                }
                $empty = (int) DB::selectOne("SELECT COUNT(*) c FROM {$from} WHERE ({$where}) AND {$col} = ''")->c;
                if ($empty > 0) {
                    $expr = "NULLIF({$col}, '')";
                    $notes[] = "column {$name}: {$empty} empty string -> NULL";
                }
            }

            // Numeric value that would not fit a narrower decimal.
            if ($d->data_type === 'decimal' && in_array($s->data_type, self::NUMERIC_TYPES, true)) {
                $limit = 10 ** ((int) $d->numeric_precision - (int) $d->numeric_scale);
                $over = (int) DB::selectOne("SELECT COUNT(*) c FROM {$from} WHERE ({$where}) AND ABS({$col}) >= {$limit}")->c;
                if ($over > 0) {
                    $problems[] = "column {$name}: {$over} value(s) do not fit {$d->column_type}";
                }
            }

            $columns[$name] = $expr;
        }

        // A usable primary key.
        $pk = array_keys(array_filter($dst, fn ($c) => $c->column_key === 'PRI'));
        if (count($pk) === 1 && isset($src[$pk[0]])) {
            $key = self::q($pk[0]);
            $nulls = (int) DB::selectOne("SELECT COUNT(*) c FROM {$from} WHERE ({$where}) AND {$key} IS NULL")->c;
            $dups = (int) DB::selectOne("SELECT COUNT(*) c FROM (SELECT {$key} FROM {$from} WHERE ({$where}) AND {$key} IS NOT NULL GROUP BY {$key} HAVING COUNT(*) > 1) x")->c;
            if ($nulls > 0) {
                $problems[] = "primary key {$pk[0]}: {$nulls} NULL";
            }
            if ($dups > 0) {
                $problems[] = "primary key {$pk[0]}: {$dups} duplicated value(s)";
            }
        }

        // Unique indexes: duplicates in what will be inserted (after the expressions above) would stop the import.
        foreach ($this->uniqueIndexes($table) as $indexName => $cols) {
            if (count($cols) === 1 && $cols[0] === ($pk[0] ?? null)) {
                continue;
            }
            if (array_diff($cols, array_keys($columns))) {
                continue;
            }
            $exprs = array_map(fn ($c) => $columns[$c], $cols);
            $nonNull = implode(' AND ', array_map(fn ($e) => "({$e}) IS NOT NULL", $exprs));
            $list = implode(', ', $exprs);
            $dups = (int) DB::selectOne("SELECT COUNT(*) c FROM (SELECT {$list} FROM {$from} WHERE ({$where}) AND {$nonNull} GROUP BY {$list} HAVING COUNT(*) > 1) x")->c;
            if ($dups > 0) {
                $problems[] = "unique index {$indexName} (" . implode(', ', $cols) . "): {$dups} duplicated value(s) in the legacy data";
            }
        }

        return ['columns' => $columns, 'problems' => $problems, 'notes' => $notes];
    }

    /** @return array<string, list<string>> unique index name => columns, primary key excluded */
    private function uniqueIndexes(string $table): array
    {
        $out = [];
        foreach (DB::select(
            "SELECT index_name AS index_name, column_name AS column_name FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND non_unique = 0 AND index_name <> 'PRIMARY' ORDER BY index_name, seq_in_index",
            [$this->target, $table]
        ) as $row) {
            $out[$row->index_name][] = $row->column_name;
        }

        return $out;
    }

    /**
     * Truncate and copy. Returns table => [expected rows, inserted rows].
     *
     * @param  array<string, array{columns: array<string,string>, problems: list<string>, notes: list<string>}>  $tables
     * @return array<string, array{0:int,1:int}>
     */
    public function run(array $tables, ?callable $progress = null): array
    {
        DB::statement("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        $result = [];
        try {
            foreach (self::RESET_TARGET_TABLES as $table) {
                if (! isset($tables[$table]) && DB::getSchemaBuilder()->hasTable($table)) {
                    DB::statement("TRUNCATE TABLE {$this->dst($table)}");
                }
            }

            foreach ($tables as $table => $info) {
                if ($info['columns'] === []) {
                    continue;
                }

                $where = $this->filterSql($table);
                $expected = (int) DB::selectOne("SELECT COUNT(*) c FROM {$this->src($table)} WHERE {$where}")->c;

                DB::statement("TRUNCATE TABLE {$this->dst($table)}");

                $names = implode(', ', array_map([self::class, 'q'], array_keys($info['columns'])));
                $exprs = implode(', ', array_values($info['columns']));
                $inserted = DB::affectingStatement("INSERT INTO {$this->dst($table)} ({$names}) SELECT {$exprs} FROM {$this->src($table)} WHERE {$where}");

                $result[$table] = [$expected, $inserted];
                if ($progress) {
                    $progress($table, $expected, $inserted);
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        return $result;
    }
}
