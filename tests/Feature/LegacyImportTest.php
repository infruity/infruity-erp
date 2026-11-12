<?php

namespace Tests\Feature;

use App\Support\LegacyImport\Importer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The legacy importer (php artisan legacy:import) and its safety rules. Uses a tiny scratch source database next to
 * the *_test database; it only reads the target (plan / dry run) and never lets a TRUNCATE run.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class LegacyImportTest extends TestCase
{
    private const SOURCE = 'infruity_legacy_src_test';

    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) DB::connection()->getDatabaseName();
        if (DB::connection()->getDriverName() !== 'mysql' || ! str_ends_with($database, '_test') || ! DB::getSchemaBuilder()->hasTable('wholesale')) {
            $this->markTestSkipped('Needs the migrated MySQL *_test database (use phpunit.mysql.xml).');
        }

        DB::statement('DROP DATABASE IF EXISTS ' . self::SOURCE);
        DB::statement('CREATE DATABASE ' . self::SOURCE . ' CHARACTER SET utf8mb4');
        $s = self::SOURCE;

        // Keyless tables like the legacy ones, with the data problems the importer must handle.
        DB::statement("CREATE TABLE {$s}.branch (id BIGINT, name VARCHAR(255), code VARCHAR(255) NULL, address VARCHAR(255))");
        DB::statement("INSERT INTO {$s}.branch VALUES (1, 'Toko A', 'AAA', 'Jl A'), (2, 'Toko B', NULL, 'Jl B'), (3, 'Toko C', '', 'Jl C')");

        DB::statement("CREATE TABLE {$s}.products (id BIGINT, name VARCHAR(255), hpp DOUBLE NULL, status VARCHAR(20), tipe VARCHAR(20))");
        DB::statement("INSERT INTO {$s}.products VALUES (1, 'Apel', 1234.56, 'no-receipt', 'product'), (2, 'Jeruk', 99.4, '', ''), (3, 'Mangga', NULL, 'receipt', 'kemasan')");

        DB::statement("CREATE TABLE {$s}.wholesale (id BIGINT, uuid TEXT, branch_id BIGINT, order_number VARCHAR(20), supplier_id BIGINT, order_date DATE, status VARCHAR(20))");
        DB::statement("INSERT INTO {$s}.wholesale VALUES
            (0, 'u0', 0, 'PO-J1', 0, '2026-01-01', 'temp'), (0, 'u0b', 0, 'PO-J2', 0, '2026-01-01', 'temp'), (7, 'u7', 1, 'PO-7', 0, '2026-01-02', 'posting')");
    }

    protected function tearDown(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('DROP DATABASE IF EXISTS ' . self::SOURCE);
        }
        parent::tearDown();
    }

    private function plan(): array
    {
        return (new Importer(self::SOURCE, DB::connection()->getDatabaseName()))->plan();
    }

    public function test_it_refuses_identical_source_and_target(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Importer('infruity_test', 'infruity_test');
    }

    public function test_it_refuses_unsafe_database_names(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Importer('x`; DROP DATABASE y; --', 'infruity_test');
    }

    public function test_plan_derives_a_missing_branch_code_from_the_name(): void
    {
        $info = $this->plan()['tables']['branch'];

        $this->assertSame([], $info['problems']);
        $this->assertStringContainsString("WHEN 2 THEN 'B'", $info['columns']['code']);
        $this->assertStringContainsString("WHEN 3 THEN 'C'", $info['columns']['code']);
        $this->assertStringContainsString('NULLIF(`code`', $info['columns']['code'], 'an existing code is kept');
        $this->assertTrue((bool) array_filter($info['notes'], fn ($n) => str_contains($n, 'branch 2 "Toko B" had no code -> B')));
    }

    public function test_a_derived_branch_code_that_collides_falls_back_to_the_branch_id(): void
    {
        DB::statement('INSERT INTO ' . self::SOURCE . ".branch VALUES (4, 'Toko AAA', NULL, 'Jl D')");   // would derive AAA, taken by branch 1

        $code = $this->plan()['tables']['branch']['columns']['code'];

        $this->assertStringContainsString("WHEN 4 THEN 'BR4'", $code);
    }

    public function test_plan_reports_a_unique_index_violation_in_the_legacy_data(): void
    {
        DB::statement('INSERT INTO ' . self::SOURCE . ".branch VALUES (5, 'Toko A', 'EEE', 'Jl E')");   // same name as branch 1

        $problems = $this->plan()['tables']['branch']['problems'];

        $this->assertTrue((bool) array_filter($problems, fn ($p) => str_contains($p, 'unique index branch_name_unique')), implode(' | ', $problems));
    }

    #[DataProvider('branchNames')]
    public function test_branch_code_derivation(string $name, string $expected): void
    {
        $this->assertSame($expected, Importer::deriveBranchCode($name));
    }

    public static function branchNames(): array
    {
        return [
            'generic prefix is dropped' => ['Toko GBA', 'GBA'],
            'short single word kept' => ['Cabang Jakarta', 'JAK'],
            'long single word shortened' => ['Cabang Bandung', 'BAN'],
            'several words become initials' => ['Grand Bunder Regency', 'GBR'],
            'only generic words' => ['Toko Cabang', ''],
            'punctuation ignored' => ['Toko  G.B.A', 'GBA'],
        ];
    }

    public function test_plan_turns_empty_enum_values_into_the_column_default_and_copies_hpp_unrounded(): void
    {
        $products = $this->plan()['tables']['products'];

        $this->assertSame([], $products['problems']);
        $this->assertStringContainsString('NULLIF(`status`', $products['columns']['status']);
        $this->assertStringContainsString('NULLIF(`tipe`', $products['columns']['tipe']);
        $this->assertSame('`hpp`', $products['columns']['hpp'], 'the fractional cost is copied as it is, never rounded');
        $this->assertSame([], array_values(array_filter($products['notes'], fn ($n) => str_contains($n, 'hpp'))));
        $this->assertContains('column status: 1 empty enum value -> default no-receipt', $products['notes']);
        $this->assertContains('column tipe: 1 empty enum value -> default product', $products['notes']);
    }

    public function test_plan_skips_the_junk_id_zero_wholesale_drafts_so_the_primary_key_stays_unique(): void
    {
        $wholesale = $this->plan()['tables']['wholesale'];

        $this->assertSame([], $wholesale['problems'], 'the two id-0 rows are filtered out, so no duplicate primary key remains');
        $this->assertTrue((bool) array_filter($wholesale['notes'], fn ($n) => str_starts_with($n, 'skips 2 row(s)')));
    }

    public function test_plan_reports_a_value_the_target_enum_cannot_store(): void
    {
        DB::statement('INSERT INTO ' . self::SOURCE . ".products VALUES (4, 'Aneh', 1, 'bogus', 'product')");

        $problems = $this->plan()['tables']['products']['problems'];

        $this->assertCount(1, $problems);
        $this->assertStringContainsString("value 'bogus'", $problems[0]);
    }

    public function test_plan_reports_a_duplicated_primary_key_that_is_not_filtered(): void
    {
        DB::statement('INSERT INTO ' . self::SOURCE . ".products VALUES (1, 'Apel dobel', 1, 'no-receipt', 'product')");

        $problems = $this->plan()['tables']['products']['problems'];

        $this->assertContains('primary key id: 1 duplicated value(s)', $problems);
    }

    public function test_dry_run_with_problems_fails_and_changes_nothing(): void
    {
        DB::statement('INSERT INTO ' . self::SOURCE . ".products VALUES (4, 'Aneh', 1, 'bogus', 'product')");
        $before = DB::table('branch')->count();

        $exit = Artisan::call('legacy:import', ['source' => self::SOURCE, '--dry-run' => true]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Nothing was changed', Artisan::output());
        $this->assertSame($before, DB::table('branch')->count());
    }

    public function test_a_real_run_without_the_matching_confirmation_is_refused_and_changes_nothing(): void
    {
        $before = DB::table('branch')->count();
        $this->assertGreaterThan(0, $before);

        $exit = Artisan::call('legacy:import', ['source' => self::SOURCE]);
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Refusing to truncate', Artisan::output());

        $exit = Artisan::call('legacy:import', ['source' => self::SOURCE, '--confirm-target' => 'some_other_db']);
        $this->assertSame(1, $exit);

        $this->assertSame($before, DB::table('branch')->count(), 'target must be untouched');
    }
}
