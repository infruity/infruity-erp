<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Laporan Stok Opname</title>
    <style>
        body { font-family: var(--erp-font); padding: 20px; background: var(--erp-canvas); margin: 0; }
        .preview-container { max-width: 1000px; margin: 0 auto; background: #ffffff; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.05); border-radius: var(--erp-radius-surface); }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; font-size: 14px; }
        th, td { border: 1px solid #e4e6ef; padding: 10px 12px; text-align: left; }
        th { background-color: var(--erp-primary); color: white; text-align: center; font-weight: 600; border-color: var(--erp-primary); }
        .text-center { text-align: center; }
        .bg-danger-light { background-color: var(--erp-danger-soft); color: var(--erp-danger); font-weight: 600; }
        .bg-success-light { background-color: var(--erp-success-soft); color: var(--erp-success); font-weight: 600; }
        .header-title { text-align: center; font-weight: bold; font-size: 20px; margin-bottom: 5px; color: #181c32; }
        .header-subtitle { text-align: center; font-size: 15px; margin-bottom: 30px; color: #7e8299; }
        .action-buttons { text-align: right; margin-bottom: 20px; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; font-family: inherit; transition: background-color 0.3s; }
        .btn-primary { background: var(--erp-primary, #0b595b); color: white; }
        .btn-primary:hover { background: var(--erp-primary-hover); }
        .btn-light { background: var(--erp-canvas); color: #5e6278; margin-left: 8px; }
        .btn-light:hover { background: #e4e6ef; color: #3f4254; }
        .preview-table { max-width:100%; overflow-x:auto; }
        .preview-table table { min-width:760px; }
        .preview-table :is(th,td):nth-child(2) { min-width:220px; }
        @media (max-width:767px) { .preview-container { padding:20px; } body { padding:12px; } }
        @media print {
            .preview-table { overflow:visible; }
            .preview-table table { min-width:0; }
            .no-print { display: none; }
            body { padding: 0; background: white; }
            .preview-container { box-shadow: none; padding: 0; margin: 0; max-width: 100%; border-radius: 0; }
            th { background-color: var(--erp-primary) !important; -webkit-print-color-adjust: exact; color: white !important; }
            .bg-danger-light { background-color: #F4CCCC !important; -webkit-print-color-adjust: exact; color: #000 !important; font-weight: normal; }
            .bg-success-light { background-color: #D9EAD3 !important; -webkit-print-color-adjust: exact; color: #000 !important; font-weight: normal; }
        }
    </style>
<link rel="stylesheet" href="{{ asset('assets/css/erp-theme.css') }}?v={{ filemtime(public_path('assets/css/erp-theme.css')) }}">
</head>
<body class="erp-ui">
    <div class="preview-container">
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn erp-button erp-button--primary">
                Cetak Laporan
            </button>
            <button onclick="window.close()" class="btn erp-button">
                Tutup Preview
            </button>
        </div>

        <div class="header-title">LAPORAN STOK OPNAME {{ strtoupper($dateStr) }}</div>
        <div class="header-subtitle">CABANG {{ strtoupper($branchStr) }}</div>

        <div class="preview-table" tabindex="0" role="region" aria-label="Rincian stok opname, geser untuk melihat seluruh kolom">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Item</th>
                    <th style="width: 150px;">Stok Sistem</th>
                    <th style="width: 150px;">Stok Fisik</th>
                    <th style="width: 150px;">Selisih</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $index => $row)
                    @php
                        $isPending = $row->status === 'pending';
                        $selisih = (float)$row->difference;
                        $bgClass = '';
                        if ($selisih < 0) {
                            $bgClass = 'bg-danger-light';
                        } elseif ($selisih > 0) {
                            $bgClass = 'bg-success-light';
                        }
                    @endphp
                    <tr style="{{ $isPending ? 'background-color: #fff5f8;' : '' }}">
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            {{ $row->product->name ?? '-' }}
                            @if($isPending)
                                <span style="color: var(--erp-danger); font-size: 11px; margin-left: 5px; font-weight: bold;">(Belum Selesai)</span>
                            @endif
                        </td>
                        <td class="text-center" style="{{ $isPending ? 'color: var(--erp-danger); font-weight: bold; font-style: italic;' : '' }}">
                            {{ $isPending ? 'Sistem' : str_replace('.', ',', (float)$row->stock) . ' ' . ($row->product->unit->abbreviation ?? $row->product->unit->name ?? '') }}
                        </td>
                        <td class="text-center">{{ str_replace('.', ',', (float)$row->real_stock) . ' ' . ($row->product->unit->abbreviation ?? $row->product->unit->name ?? '') }}</td>
                        <td class="text-center {{ $bgClass }}">{{ str_replace('.', ',', $selisih) . ' ' . ($row->product->unit->abbreviation ?? $row->product->unit->name ?? '') }}</td>
                    </tr>
                @endforeach
                @if(count($data) === 0)
                    <tr>
                        <td colspan="5" class="text-center" style="color: #a1a5b7; padding: 20px;">Tidak ada data stok opname yang ditemukan</td>
                    </tr>
                @endif
            </tbody>
        </table>
        </div>
    </div>
</body>
</html>
