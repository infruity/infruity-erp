<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;
use Modules\Transaction\Entities\StockOpname;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Stock opname list as an .xlsx.
 *
 * The cells are written directly. It used to render a 2 MB HTML table (one inline-styled <td> per cell) and let
 * PhpSpreadsheet parse it back: ~5.9 s for 4,451 rows, ~1 s now. Same columns, texts and colours as that table.
 */
class StockOpnameExport implements FromArray, WithColumnWidths, WithEvents
{
    private const HEADER_ROW = 4;

    private const FIRST_DATA_ROW = 5;

    /** @var list<int> */
    private array $negativeRows = [];

    /** @var list<int> */
    private array $positiveRows = [];

    /** @var list<int> */
    private array $pendingRows = [];

    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function array(): array
    {
        $userBranches = UserBranch::getUserBranch();
        $query = StockOpname::with('product.unit')
            ->whereIn('stock_opname.branch_id', $userBranches);

        $branchStr = 'SEMUA CABANG';
        if ($this->request->has('cabang_filter') && $this->request->cabang_filter !== 'all') {
            $query->where('stock_opname.branch_id', $this->request->cabang_filter);
            $branch = Branch::find($this->request->cabang_filter);
            if ($branch) {
                $branchStr = $branch->name;
            }
        }

        $dateStr = '';
        if ($this->request->has('start_date') && $this->request->start_date != '') {
            $query->whereDate('stock_opname.date', '>=', $this->request->start_date);
            $start = Carbon::parse($this->request->start_date);
            $dateStr .= $start->translatedFormat('d F Y');
        }

        if ($this->request->has('end_date') && $this->request->end_date != '') {
            $query->whereDate('stock_opname.date', '<=', $this->request->end_date);
            $end = Carbon::parse($this->request->end_date);
            if ($this->request->start_date != $this->request->end_date) {
                $dateStr .= ' - ' . $end->translatedFormat('d F Y');
            }
        }

        if ($dateStr == '') {
            $dateStr = 'SEMUA TANGGAL';
        }

        $rows = [
            ['LAPORAN STOK OPNAME ' . strtoupper($dateStr), null, null, null, null],
            ['CABANG ' . strtoupper($branchStr), null, null, null, null],
            [null, null, null, null, null],
            ['No', 'Nama Item', 'Stok Sistem', 'Stok Fisik', 'Selisih'],
        ];

        $this->negativeRows = $this->positiveRows = $this->pendingRows = [];
        $line = self::FIRST_DATA_ROW;

        foreach ($query->orderBy('stock_opname.created_at', 'desc')->get() as $index => $row) {
            $isPending = $row->status === 'pending';
            $selisih = (float) $row->difference;
            $unit = $row->product?->unit?->abbreviation ?? $row->product?->unit?->name ?? '';

            if ($selisih < 0) {
                $this->negativeRows[] = $line;
            } elseif ($selisih > 0) {
                $this->positiveRows[] = $line;
            }
            if ($isPending) {
                $this->pendingRows[] = $line;
            }

            $rows[] = [
                $index + 1,
                ($row->product->name ?? '-') . ($isPending ? ' (Belum Selesai)' : ''),
                $isPending ? 'Sistem' : str_replace('.', ',', (float) $row->stock) . ' ' . $unit,
                str_replace('.', ',', (float) $row->real_stock) . ' ' . $unit,
                str_replace('.', ',', $selisih) . ' ' . $unit,
            ];
            $line++;
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 11, 'B' => 43, 'C' => 21, 'D' => 21, 'E' => 21];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = $sheet->getHighestRow();
                $center = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];

                $sheet->mergeCells('A1:E1')->mergeCells('A2:E2')->mergeCells('A3:E3');
                $sheet->getStyle('A1')->applyFromArray($center + ['font' => ['bold' => true, 'size' => 16]]);
                $sheet->getStyle('A2')->applyFromArray($center + ['font' => ['size' => 14]]);

                $sheet->getStyle('A' . self::HEADER_ROW . ':E' . self::HEADER_ROW)->applyFromArray($center + [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34A853']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);

                if ($last < self::FIRST_DATA_ROW) {
                    return;
                }

                $data = 'A' . self::FIRST_DATA_ROW . ':E' . $last;
                $sheet->getStyle($data)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
                ]);
                $sheet->getStyle('A' . self::FIRST_DATA_ROW . ':A' . $last)->applyFromArray($center);
                $sheet->getStyle('C' . self::FIRST_DATA_ROW . ':E' . $last)->applyFromArray($center);

                $fill = fn (string $rgb) => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]]];

                foreach ($this->pendingRows as $line) {
                    $sheet->getStyle("A{$line}:E{$line}")->applyFromArray($fill('FFF5F8'));
                    $sheet->getStyle("C{$line}")->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => 'F1416C']]]);
                }
                // The difference colour wins over the pending tint, as in the old table.
                foreach ($this->negativeRows as $line) {
                    $sheet->getStyle("E{$line}")->applyFromArray($fill('F4CCCC'));
                }
                foreach ($this->positiveRows as $line) {
                    $sheet->getStyle("E{$line}")->applyFromArray($fill('D9EAD3'));
                }
            },
        ];
    }
}
