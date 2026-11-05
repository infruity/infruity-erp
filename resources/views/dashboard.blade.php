@extends('layouts.erp')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-breadcrumb', 'Ringkasan operasional')

@section('extra-style')
    <style>
        .dashboard-saldo {
            min-height: 330px;
            border-radius: 22px;
            background: linear-gradient(135deg, #06281e 0%, #0b595b 54%, #041a13 100%);
            box-shadow: 0 14px 34px -12px rgba(11, 89, 91, .48);
            isolation: isolate;
        }

        .dashboard-saldo::before {
            content: '';
            position: absolute;
            z-index: -1;
            width: 190px;
            height: 190px;
            right: -72px;
            top: -84px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(209, 243, 110, .28), rgba(242, 180, 0, .1) 48%, transparent 72%);
        }

        @media (min-width: 768px) {
            .dashboard-saldo { min-height: 360px; }
        }
    </style>
@endsection

@section('content')
<div class="w-full flex-1 min-h-0" x-data="{ activeMetric: 'pendapatan' }">
    <section class="md:hidden pb-4">
        <div class="pt-5 pb-4 flex items-end justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[23px] leading-tight font-bold tracking-[-0.03em] text-gray-900">Ringkasan</h1>
                <p class="mt-1 text-[13px] leading-5 text-gray-500">Pantau kondisi operasional Infruity.</p>
            </div>
            <div class="shrink-0 flex items-center gap-1.5 rounded-full bg-white border border-gray-200 px-3 py-2 text-[11px] font-semibold text-gray-600">
                <i class="ph ph-map-pin text-[15px] text-[#0b595b]"></i>
                Semua cabang
            </div>
        </div>

        <article class="dashboard-saldo relative overflow-hidden p-5 text-white">
            <div class="relative z-10 flex h-full min-h-[290px] flex-col justify-between">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[13px] font-semibold tracking-wide text-white">Semua cabang</p>
                        <p class="mt-1 text-[11px] text-white/70">Periode berjalan · {{ now()->translatedFormat('d F Y') }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1.5 text-[10px] font-medium text-white/85">
                        <i class="ph ph-calendar-blank text-[13px]"></i> Minggu ini
                    </span>
                </div>

                <div class="pt-7">
                    <p class="text-[11px] font-medium tracking-wide text-white/70" x-text="activeMetric === 'pendapatan' ? 'Pendapatan' : (activeMetric === 'pengeluaran' ? 'Pengeluaran' : 'Laba bersih')">Pendapatan</p>
                    <p class="mt-1 text-[32px] leading-tight font-bold tracking-[-0.035em] tabular-nums" aria-live="polite">—</p>
                    <p class="mt-1 text-xs text-white/65">Data transaksi akan tampil di sini.</p>
                </div>

                <div class="mt-6 border-t border-white/15 pt-4">
                    <div class="mb-3 flex items-center justify-between text-[11px]">
                        <span class="text-white/70">Arus kas</span>
                        <span class="text-white/55">Ringkasan periode</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2" role="group" aria-label="Pilih metrik utama">
                        <button type="button" @click="activeMetric = 'pendapatan'" :aria-pressed="activeMetric === 'pendapatan'"
                            :class="activeMetric === 'pendapatan' ? 'bg-white/15 text-white' : 'text-white/65 hover:bg-white/10'"
                            class="min-h-11 rounded-xl px-2 text-[11px] font-semibold transition-colors">Pendapatan</button>
                        <button type="button" @click="activeMetric = 'pengeluaran'" :aria-pressed="activeMetric === 'pengeluaran'"
                            :class="activeMetric === 'pengeluaran' ? 'bg-white/15 text-white' : 'text-white/65 hover:bg-white/10'"
                            class="min-h-11 rounded-xl px-2 text-[11px] font-semibold transition-colors">Pengeluaran</button>
                        <button type="button" @click="activeMetric = 'laba'" :aria-pressed="activeMetric === 'laba'"
                            :class="activeMetric === 'laba' ? 'bg-white/15 text-white' : 'text-white/65 hover:bg-white/10'"
                            class="min-h-11 rounded-xl px-2 text-[11px] font-semibold transition-colors">Laba bersih</button>
                    </div>
                </div>
            </div>
        </article>

        <section class="mt-6" aria-labelledby="weekly-summary-heading">
            <div class="mb-3 flex items-end justify-between gap-3">
                <h2 id="weekly-summary-heading" class="text-[16px] font-bold tracking-tight text-gray-900">Ringkasan minggu ini</h2>
                <span class="text-[11px] text-gray-500">{{ now()->translatedFormat('d M Y') }}</span>
            </div>
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white divide-y divide-gray-100">
                <div class="flex items-center gap-3 px-4 py-3.5">
                    <span class="w-10 h-10 rounded-xl bg-emerald-50 text-[#0b595b] flex items-center justify-center"><i class="ph ph-receipt text-[20px]"></i></span>
                    <div class="min-w-0 flex-1"><p class="text-[13px] font-semibold text-gray-800">Transaksi</p><p class="mt-0.5 text-[11px] text-gray-500">Jumlah transaksi pada periode ini</p></div>
                    <span class="text-sm font-bold text-gray-500 tabular-nums">—</span>
                </div>
                <div class="flex items-center gap-3 px-4 py-3.5">
                    <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center"><i class="ph ph-shopping-basket text-[20px]"></i></span>
                    <div class="min-w-0 flex-1"><p class="text-[13px] font-semibold text-gray-800">Pengadaan</p><p class="mt-0.5 text-[11px] text-gray-500">Pesanan yang perlu ditinjau</p></div>
                    <span class="text-sm font-bold text-gray-500 tabular-nums">—</span>
                </div>
                <div class="flex items-center gap-3 px-4 py-3.5">
                    <span class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center"><i class="ph ph-warning-circle text-[20px]"></i></span>
                    <div class="min-w-0 flex-1"><p class="text-[13px] font-semibold text-gray-800">Stok menipis</p><p class="mt-0.5 text-[11px] text-gray-500">Produk yang perlu diperiksa</p></div>
                    <span class="text-sm font-bold text-gray-500 tabular-nums">—</span>
                </div>
            </div>
        </section>

        <section class="mt-6 mb-4" aria-labelledby="activity-heading">
            <h2 id="activity-heading" class="mb-3 text-[16px] font-bold tracking-tight text-gray-900">Aktivitas terbaru</h2>
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white/70 px-5 py-7 text-center">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-500"><i class="ph ph-clock-counter-clockwise text-[22px]"></i></span>
                <p class="mt-3 text-sm font-semibold text-gray-700">Belum ada aktivitas</p>
                <p class="mt-1 text-xs leading-5 text-gray-500">Aktivitas operasional terbaru akan muncul di sini.</p>
            </div>
        </section>
    </section>

    <section class="hidden md:block py-5 lg:py-2" aria-labelledby="desktop-dashboard-heading">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h1 id="desktop-dashboard-heading" class="text-2xl font-bold tracking-tight text-gray-900">Ringkasan</h1>
                <p class="mt-1 text-sm text-gray-500">Pantau kondisi operasional Infruity.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600"><i class="ph ph-map-pin text-lg text-[#0b595b]"></i> Semua cabang · {{ now()->translatedFormat('d F Y') }}</span>
        </div>
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1.2fr_0.8fr]">
            <article class="dashboard-saldo relative overflow-hidden p-7 text-white">
                <div class="relative z-10 flex h-full min-h-[300px] flex-col justify-between">
                    <div><p class="text-sm font-semibold">Semua cabang</p><p class="mt-1 text-xs text-white/70">Periode berjalan</p></div>
                    <div><p class="text-sm text-white/70">Ringkasan transaksi</p><p class="mt-1 text-4xl font-bold tabular-nums">—</p><p class="mt-2 text-sm text-white/65">Data transaksi akan tampil di sini.</p></div>
                    <div class="grid grid-cols-3 gap-3 border-t border-white/15 pt-4 text-sm"><span>Pendapatan <b class="ml-2">—</b></span><span>Pengeluaran <b class="ml-2">—</b></span><span>Laba bersih <b class="ml-2">—</b></span></div>
                </div>
            </article>
            <section class="rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="desktop-activity-heading">
                <h2 id="desktop-activity-heading" class="text-base font-bold text-gray-900">Aktivitas terbaru</h2>
                <div class="mt-5 rounded-xl border border-dashed border-gray-300 px-5 py-10 text-center">
                    <i class="ph ph-clock-counter-clockwise text-2xl text-gray-400"></i>
                    <p class="mt-2 text-sm font-semibold text-gray-700">Belum ada aktivitas</p>
                    <p class="mt-1 text-xs text-gray-500">Aktivitas terbaru akan muncul di sini.</p>
                </div>
            </section>
        </div>
    </section>
</div>
@endsection
