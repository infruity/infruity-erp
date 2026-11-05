@extends('layouts.erp')

@section('title', 'Ringkasan Laporan')
@section('page-title', 'Ringkasan Laporan')
@section('dashboard-fullscreen', '1')
@section('page-breadcrumb')
    <span>Laporan</span>
    <i class="ph ph-caret-right text-[0.55rem]" aria-hidden="true"></i>
    <span>Ringkasan Laporan</span>
@endsection

@push('styles')
    <style>
        body:has(.report-hub) main { overflow:hidden; }
        body:has(.report-hub) main > .animate-fade-in-up { padding:0!important; animation:none; }
        .report-hub { display:flex; flex:1; flex-direction:column; align-items:center; min-height:0; overflow-y:auto; overscroll-behavior:contain; border:1px solid #f3f4f6; border-radius:16px; padding:48px 32px 96px; background:#f8fafc; color:#1f2937; scrollbar-width:thin; scrollbar-color:#cbd5e1 transparent; }
        .report-hub *, .report-hub *::before, .report-hub *::after { box-sizing:border-box; }
        .report-hub__card { width:100%; max-width:448px; flex:none; overflow:hidden; border:1px solid #f3f4f6; border-radius:16px; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,.03); }
        .report-hub__intro { padding:20px; border-bottom:1px solid #f3f4f6; }
        .report-hub__intro h2 { margin:0; color:#111827; font-size:17px; font-weight:700; line-height:1.3; }
        .report-hub__intro p { margin:5px 0 0; color:#6b7280; font-size:12px; line-height:1.5; }
        .report-hub__link { display:flex; min-height:52px; align-items:center; justify-content:space-between; gap:16px; padding:16px 20px; border-bottom:1px solid #f3f4f6; color:#1f2937; font-size:14px; font-weight:600; line-height:1.5; text-decoration:none; transition:background .15s,color .15s; -webkit-tap-highlight-color:transparent; }
        .report-hub__link:last-child { border-bottom:0; }
        .report-hub__link:hover { background:#f9fafb; color:#0b595b; }
        .report-hub__link:focus-visible { position:relative; outline:2px solid #0b595b; outline-offset:-3px; background:#f0fdf8; color:#0b595b; }
        .report-hub__link i { flex:none; color:#6b7280; font-size:12px; }
        @media (max-width:767.98px) {
            body:has(.report-hub) main { padding:0!important; }
            .report-hub { border:0; border-radius:0; padding:24px 16px calc(112px + env(safe-area-inset-bottom)); }
        }
        @media (max-width:639.98px) {
            .report-hub__card { max-width:384px; }
            .report-hub__intro { padding:16px; }
            .report-hub__intro h2 { font-size:16px; }
            .report-hub__link { min-height:48px; padding:14px 16px; }
        }
        @media (prefers-reduced-motion:reduce) { .report-hub__link { transition:none; } }
    </style>
@endpush

@section('content')
    <section class="report-hub" aria-labelledby="report-hub-title">
        <div class="report-hub__card">
            <div class="report-hub__intro"><h2 id="report-hub-title">Pusat Laporan</h2><p>Pilih modul analitik &amp; ringkasan bisnis</p></div>
            <nav aria-label="Daftar laporan">
                @foreach ($reports as $report)
                    <a class="report-hub__link" href="{{ route($report['route']) }}"><span>{{ $report['label'] }}</span><i class="ph-bold ph-caret-right" aria-hidden="true"></i></a>
                @endforeach
            </nav>
        </div>
    </section>
@endsection
