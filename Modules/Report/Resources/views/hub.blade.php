@extends('template.root')

@section('title', 'Ringkasan Laporan')
@section('page-title', 'Ringkasan Laporan')
@section('page-breadcrumb')
    <span>Laporan</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Ringkasan Laporan</span>
@endsection

@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
<style>
    .report-hub { min-height:calc(100dvh - 72px); padding:32px 20px 32px; background:#f8fafc; font-family:'Plus Jakarta Sans',sans-serif; color:#1f2937; }
    .report-hub__card { width:100%; max-width:384px; margin:0 auto; overflow:hidden; border:1px solid #f1f3f5; border-radius:18px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.03); }
    .report-hub__intro { padding:16px; border-bottom:1px solid #f1f3f5; }
    .report-hub__intro h1 { margin:0; color:#111827; font-size:16px; font-weight:700; line-height:1.35; }
    .report-hub__intro p { margin:5px 0 0; color:#9ca3af; font-size:12px; }
    .report-hub__link { display:flex; min-height:48px; align-items:center; justify-content:space-between; gap:16px; padding:14px 16px; border-bottom:1px solid #f1f3f5; color:#1f2937; font-size:14px; font-weight:600; text-decoration:none; transition:background .15s,color .15s; }
    .report-hub__link:last-child { border-bottom:0; }
    .report-hub__link:hover, .report-hub__link:focus-visible { background:#f8fafc; color:#0b595b; }
    .report-hub__link i { flex:none; color:#9ca3af; font-size:12px; }
    @media (min-width:640px) {
        .report-hub__card { max-width:448px; }
        .report-hub__intro { padding:20px; }
        .report-hub__intro h1 { font-size:17px; }
        .report-hub__link { min-height:52px; padding:16px 20px; }
    }
    @media (max-width:767.98px) {
        #kt_content { padding:0 !important; }
        #kt_content_container { width:100%; max-width:none; padding:0; }
        .report-hub { min-height:calc(100dvh - 60px); padding:24px 16px 32px; }
    }
</style>
<main class="report-hub">
    <div class="report-hub__card">
        <div class="report-hub__intro"><h1>Pusat Laporan</h1><p>Pilih modul analitik &amp; ringkasan bisnis</p></div>
        @foreach ([
            ['route' => 'report-product-sales.index', 'label' => 'Pendapatan Penjualan'],
            ['route' => 'report-profit-revenue.index', 'label' => 'Keuntungan Penjualan'],
            ['route' => 'report-profit-adjusted.index', 'label' => 'Keuntungan Setelah Penyesuaian'],
            ['route' => 'report-total-aset', 'label' => 'Total Aset'],
            ['route' => 'report-shipping-cost', 'label' => 'Ongkos Kirim'],
            ['route' => 'report-product-buang', 'label' => 'Kerugian Barang Buang'],
        ] as $report)
            <a class="report-hub__link" href="{{ route($report['route']) }}"><span>{{ $report['label'] }}</span><i class="ph-bold ph-caret-right" aria-hidden="true"></i></a>
        @endforeach
    </div>
</main>
@endsection
