<?php

namespace Tests\Feature;

use App\Exports\StockOpnameExport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * The opname export writes its cells directly (it used to render and re-parse a 2 MB HTML table, ~6 s). The sheet must
 * keep the old layout: title rows, green header, one row per opname with the difference coloured.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
final class StockOpnameExportTest extends TestCase
{
    use DatabaseTransactions;
    use QaFixtures;

    private function opname(int $product, int $branch, string $status, float $stock, float $real, string $createdAt): void
    {
        DB::table('stock_opname')->insert([
            'code' => 'SO-X-' . uniqid(), 'date' => '2026-10-27', 'branch_id' => $branch, 'status' => $status, 'product_id' => $product,
            'stock' => $stock, 'real_stock' => $real, 'difference' => $real - $stock, 'avg_price' => 1000,
            'created_by' => 1, 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }

    private function sheet(array $query = [])
    {
        $request = Request::create('/stock-opname-export', 'GET', $query);
        $this->app->instance('request', $request);
        $path = tempnam(sys_get_temp_dir(), 'xo') . '.xlsx';
        file_put_contents($path, Excel::raw(new StockOpnameExport($request), ExcelFormat::XLSX));
        $sheet = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        return $sheet;
    }

    public function test_the_sheet_keeps_the_layout_texts_and_colours(): void
    {
        $this->requireMysqlTestDatabase();
        $branch = $this->makeBranch('XLS');
        $this->actAsRole($this->makeUser($this->superAdminRoleId(), [], [$branch]), $this->superAdminRoleId());

        $short = $this->makeProduct('Apel Kurang');
        $over = $this->makeProduct('Jeruk Lebih');
        $same = $this->makeProduct('Mangga Pas');
        $open = $this->makeProduct('Pir Belum');
        $this->opname($short, $branch, 'selesai', 10, 7, '2026-10-27 10:04:00');
        $this->opname($over, $branch, 'selesai', 4, 6.5, '2026-10-27 10:03:00');
        $this->opname($same, $branch, 'selesai', 5, 5, '2026-10-27 10:02:00');
        $this->opname($open, $branch, 'pending', 0, 3, '2026-10-27 10:01:00');

        $sheet = $this->sheet(['cabang_filter' => (string) $branch]);

        $this->assertSame('LAPORAN STOK OPNAME SEMUA TANGGAL', $sheet->getCell('A1')->getValue());
        $this->assertStringStartsWith('CABANG ', $sheet->getCell('A2')->getValue());
        $this->assertEqualsCanonicalizing(['A1:E1', 'A2:E2', 'A3:E3'], array_values($sheet->getMergeCells()));
        $this->assertSame(['No', 'Nama Item', 'Stok Sistem', 'Stok Fisik', 'Selisih'], array_map(fn ($c) => $sheet->getCell($c . '4')->getValue(), ['A', 'B', 'C', 'D', 'E']));
        $this->assertSame('34A853', $sheet->getStyle('B4')->getFill()->getStartColor()->getRGB());
        $this->assertSame(8, $sheet->getHighestRow(), '4 title/header rows + 4 opnames');

        // Newest first, texts as the old table wrote them (decimal comma, unit after the number).
        $this->assertStringContainsString('Apel Kurang', $sheet->getCell('B5')->getValue());
        $this->assertMatchesRegularExpression('/^10 \S+$/', $sheet->getCell('C5')->getValue());
        $this->assertMatchesRegularExpression('/^7 \S+$/', $sheet->getCell('D5')->getValue());
        $this->assertMatchesRegularExpression('/^-3 \S+$/', $sheet->getCell('E5')->getValue());
        $this->assertMatchesRegularExpression('/^2,5 \S+$/', $sheet->getCell('E6')->getValue());
        $this->assertMatchesRegularExpression('/^0 \S+$/', $sheet->getCell('E7')->getValue());

        $this->assertSame('F4CCCC', $sheet->getStyle('E5')->getFill()->getStartColor()->getRGB(), 'shortage is red');
        $this->assertSame('D9EAD3', $sheet->getStyle('E6')->getFill()->getStartColor()->getRGB(), 'surplus is green');
        $this->assertSame('none', $sheet->getStyle('E7')->getFill()->getFillType(), 'no difference, no colour');

        // A pending opname: marked, system stock shown as "Sistem", row tinted, difference colour still wins.
        $this->assertStringContainsString('(Belum Selesai)', $sheet->getCell('B8')->getValue());
        $this->assertSame('Sistem', $sheet->getCell('C8')->getValue());
        $this->assertTrue($sheet->getStyle('C8')->getFont()->getItalic());
        $this->assertSame('FFF5F8', $sheet->getStyle('A8')->getFill()->getStartColor()->getRGB());
        $this->assertSame('D9EAD3', $sheet->getStyle('E8')->getFill()->getStartColor()->getRGB());
    }

    public function test_an_empty_result_still_writes_the_title_and_header(): void
    {
        $this->requireMysqlTestDatabase();
        $branch = $this->makeBranch('XLS0');
        $this->actAsRole($this->makeUser($this->superAdminRoleId(), [], [$branch]), $this->superAdminRoleId());

        $sheet = $this->sheet(['cabang_filter' => (string) $branch, 'start_date' => '2026-01-01', 'end_date' => '2026-01-31']);

        $this->assertSame('LAPORAN STOK OPNAME 01 JANUARI 2026 - 31 JANUARI 2026', $sheet->getCell('A1')->getValue());
        $this->assertSame('Selisih', $sheet->getCell('E4')->getValue());
        $this->assertSame(4, $sheet->getHighestRow());
    }
}
