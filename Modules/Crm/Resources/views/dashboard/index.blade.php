@extends('layouts.erp-tailwind')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-breadcrumb', 'Dashboard')
@section('dashboard-fullscreen', '1')

@section('extra-style')
    .erp-dashboard { --dashboard-accent: 211 236 90; --dashboard-dark: #0f5c45; }
    .erp-dashboard[data-mode="expense"] { --dashboard-accent: 249 115 22; --dashboard-dark: #5c2009; }
    .erp-dashboard[data-mode="profit"] { --dashboard-accent: 56 189 248; --dashboard-dark: #1e3a8a; }
    .erp-dashboard[data-mode="loss"] { --dashboard-accent: 239 68 68; --dashboard-dark: #7f1d1d; }
    .erp-dashboard .mobile-bg-layer { position: absolute; inset: 0; pointer-events: none; transition: opacity .4s ease; }
    .erp-dashboard .mobile-bg-income { background: linear-gradient(to bottom, #0f5c45 0%, #0f5c45 60%, #ebf2ef 90%, #ebf2ef 100%); }
    .erp-dashboard .mobile-bg-expense { background: linear-gradient(to bottom, #5c2009 0%, #5c2009 60%, #fff7ed 90%, #fff7ed 100%); }
    .erp-dashboard .mobile-bg-profit { background: linear-gradient(to bottom, #1e3a8a 0%, #1e3a8a 60%, #eff6ff 90%, #eff6ff 100%); }
    .erp-dashboard .mobile-bg-loss { background: linear-gradient(to bottom, #7f1d1d 0%, #7f1d1d 60%, #fef2f2 90%, #fef2f2 100%); }
    .erp-dashboard .mobile-bg-layer { opacity: 0; }
    .erp-dashboard[data-mode="revenue"] .mobile-bg-income,
    .erp-dashboard[data-mode="expense"] .mobile-bg-expense,
    .erp-dashboard[data-mode="profit"] .mobile-bg-profit,
    .erp-dashboard[data-mode="loss"] .mobile-bg-loss { opacity: 1; }
    .erp-dashboard .dashboard-scroll { scrollbar-width: none; }
    .erp-dashboard .dashboard-scroll::-webkit-scrollbar { display: none; }
    .erp-dashboard .dashboard-bar { background: rgb(var(--dashboard-accent)); }
    .erp-dashboard .dashboard-bar.active { box-shadow: 0 0 15px rgb(var(--dashboard-accent) / .4); }
    @media (max-width: 767px) {
        .erp-dashboard { height: 100dvh; max-height: 100dvh; margin-top: 0; margin-bottom: 0; }
    }
@endsection

@section('content')
@php
    $periodTitle = $period === 'year'
        ? $start->format('Y')
        : ($period === 'month' ? $start->locale('id')->isoFormat('MMMM Y') : $start->locale('id')->isoFormat('D MMM') . ' - ' . $end->locale('id')->isoFormat('D MMM Y'));
    $branchName = $branchId ? optional($branches->firstWhere('id', $branchId))->name : 'Semua Cabang';
    $currency = static function ($amount) { return 'Rp ' . number_format((float) $amount, 0, ',', '.'); };
    $nextUrl = route('crm.dashboard', array_filter(['period' => $period, 'offset' => min(0, $offset + 1), 'branch_id' => $branchId], static function ($value) { return $value !== null; }));
    $prevUrl = route('crm.dashboard', array_filter(['period' => $period, 'offset' => max(-120, $offset - 1), 'branch_id' => $branchId], static function ($value) { return $value !== null; }));
@endphp
<div class="erp-dashboard flex flex-col min-h-0 w-full" x-data="erpDashboard()" :data-mode="mode">
    {{-- The source dashboard has a separate edge-to-edge mobile canvas. --}}
    <section class="md:hidden relative flex flex-col text-white overflow-hidden bg-[#ebf2ef] h-full">
        <div class="mobile-bg-layer mobile-bg-income"></div>
        <div class="mobile-bg-layer mobile-bg-expense"></div>
        <div class="mobile-bg-layer mobile-bg-profit"></div>
        <div class="mobile-bg-layer mobile-bg-loss"></div>
        <div class="absolute -top-32 -left-40 w-[500px] h-[500px] rounded-full blur-[100px] pointer-events-none z-0 transition-colors duration-500" style="background:rgb(var(--dashboard-accent) / .4)"></div>
        <div class="absolute -top-16 -left-16 w-[250px] h-[250px] bg-white/25 rounded-full blur-[70px] pointer-events-none z-0 mix-blend-overlay"></div>

        <div class="flex-1 overflow-y-auto dashboard-scroll pb-[115px] px-5 flex flex-col relative z-10">
            <div class="mt-8 px-1 relative z-10 flex flex-col items-center text-center">
                <div class="flex items-center justify-between w-full max-w-xs">
                    <a href="{{ $prevUrl }}" aria-label="Periode sebelumnya" class="text-white/60 hover:text-white p-1"><i class="ph-bold ph-caret-left text-[16px]"></i></a>
                    <button type="button" @click="filterOpen = true" class="flex flex-col items-center gap-1 hover:opacity-80 transition-opacity">
                        <span class="text-[14px] font-bold text-white tracking-wide leading-none">{{ $branchName }}</span>
                        <span class="text-[11px] font-medium text-white/60 tracking-wide">{{ $periodTitle }}</span>
                    </button>
                    <a href="{{ $nextUrl }}" aria-label="Periode berikutnya" class="text-white/60 hover:text-white p-1 {{ $offset >= 0 ? 'opacity-30 pointer-events-none' : '' }}"><i class="ph-bold ph-caret-right text-[16px]"></i></a>
                </div>
                <div class="mt-5 mb-5 flex flex-col items-center">
                    <p class="text-[14px] text-white/80 font-medium mb-1" x-text="modeTitle"></p>
                    <h1 class="text-[34px] font-bold text-white tracking-tight leading-none mt-[4px] mb-[6px]" x-text="rupiah(total)"></h1>
                    <div class="flex items-center justify-center gap-1 text-[10px] mt-1 text-white/70">
                        <i class="ph-fill ph-chart-bar text-[10px]" style="color:rgb(var(--dashboard-accent))"></i>
                        <span>Data transaksi {{ $periodTitle }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-auto mb-0 px-1 relative z-10 pt-7">
                <div class="flex items-end justify-between h-[160px] relative px-1 w-full gap-1">
                    <template x-for="(point, index) in chart" :key="index">
                        <button type="button" @click="selectedIndex = index" class="flex-1 min-w-0 flex flex-col items-center justify-end gap-2.5 z-10 h-full group" :aria-label="point.label + ': ' + rupiah(point[mode])">
                            <div class="w-full max-w-8 dashboard-bar rounded-t-[6px] rounded-b-[2px] transition-all cursor-pointer relative min-h-[4px]" :class="selectedIndex === index ? 'active' : 'opacity-30'" :style="'height:' + barHeight(point) + '%'">
                                <span class="absolute -top-7 left-1/2 -translate-x-1/2 text-[9px] tracking-tighter font-bold px-1.5 py-0.5 rounded shadow-lg whitespace-nowrap" :class="selectedIndex === index ? 'text-[#06261c]' : 'text-white opacity-0'" :style="selectedIndex === index ? 'background:rgb(var(--dashboard-accent))' : 'background:rgb(255 255 255 / .2)'" x-text="rupiah(point[mode])"></span>
                            </div>
                            <div class="flex flex-col items-center relative">
                                <span class="text-[10px] truncate max-w-[38px]" :class="selectedIndex === index ? 'font-bold text-white' : 'font-medium text-white/40'" x-text="point.label"></span>
                                <span class="text-[9px] absolute top-full mt-0.5" :class="selectedIndex === index ? 'font-bold' : 'text-white/40'" :style="selectedIndex === index ? 'color:rgb(var(--dashboard-accent))' : ''" x-text="point.day"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            <div class="mt-[33px] mb-4 bg-white rounded-[20px] p-3 px-4 shadow-[0_8px_30px_rgba(0,0,0,0.04)]">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-[13px] font-bold text-[#0a3d2e] tracking-tight" x-text="showBottom ? 'Produk Pendapatan Terendah' : 'Produk Pendapatan Tertinggi'"></h4>
                    <button type="button" @click="showBottom = !showBottom" class="w-7 h-7 shrink-0 rounded-full bg-[#d3ec5a]/30 flex items-center justify-center text-[#06261c] cursor-pointer active:scale-90 transition-all duration-300" :aria-label="showBottom ? 'Lihat produk tertinggi' : 'Lihat produk terendah'">
                        <i class="ph ph-arrows-down-up text-[14px]"></i>
                    </button>
                </div>
                <div class="flex flex-col gap-3" x-show="!showBottom">
                    @forelse ($topProducts as $product)
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('assets/media/svg/files/blank-image.svg') }}" alt="{{ $product->name }}" class="w-8 h-8 rounded-[8px] object-cover shadow-sm border border-gray-100 shrink-0">
                                <div class="min-w-0"><h5 class="text-[13px] font-medium text-[#334155] leading-tight truncate">{{ $product->name }}</h5><p class="text-[12px] font-bold text-[#0F5C45] mt-0.5">{{ $currency($product->amount) }}</p></div>
                            </div>
                            <span class="text-[13px] font-bold text-[#10b981]">{{ $metrics['revenue'] > 0 ? number_format($product->amount / $metrics['revenue'] * 100, 0, ',', '.') : 0 }}%</span>
                        </div>
                    @empty
                        <p class="text-[12px] text-gray-500 py-2">Belum ada penjualan pada periode ini.</p>
                    @endforelse
                </div>
                <div class="flex flex-col gap-3" x-show="showBottom" x-cloak>
                    @forelse ($bottomProducts as $product)
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('assets/media/svg/files/blank-image.svg') }}" alt="{{ $product->name }}" class="w-8 h-8 rounded-[8px] object-cover shadow-sm border border-gray-100 shrink-0">
                                <div class="min-w-0"><h5 class="text-[13px] font-medium text-[#334155] leading-tight truncate">{{ $product->name }}</h5><p class="text-[12px] font-bold text-[#0F5C45] mt-0.5">{{ $currency($product->amount) }}</p></div>
                            </div>
                            <span class="text-[13px] font-bold text-[#10b981]">{{ $metrics['revenue'] > 0 ? number_format($product->amount / $metrics['revenue'] * 100, 0, ',', '.') : 0 }}%</span>
                        </div>
                    @empty
                        <p class="text-[12px] text-gray-500 py-2">Belum ada penjualan pada periode ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <nav class="absolute bottom-6 left-1/2 bg-white/10 backdrop-blur-md border border-white/20 rounded-[32px] px-4 py-3 shadow-[0_8px_32px_rgba(0,0,0,0.12)] z-20 w-[90%]" style="transform:translateX(-50%); -webkit-backdrop-filter:blur(12px)" aria-label="Metrik dashboard">
            <div class="relative flex items-center justify-between w-full h-[48px]" x-init="$nextTick(() => { $refs.activePill.style.left = $el.querySelector('button').offsetLeft + 'px' })">
                <div x-ref="activePill" class="absolute top-0 left-0 h-[48px] w-[48px] bg-[#d3ec5a] rounded-full transition-all duration-300 ease-in-out z-0 shadow-sm"></div>
                <template x-for="item in modes" :key="item.key">
                    <button type="button" @click="if (item.key === 'menu') { sidebarOpen = true } else { mode = item.key; $refs.activePill.style.left = $el.offsetLeft + 'px' }" class="relative z-10 flex items-center justify-center w-[48px] h-full rounded-full transition-all duration-300" :class="mode === item.key ? 'text-[#022c22]' : 'text-[#0f5132]/40 hover:text-[#0f5132]/80'" :aria-label="item.label">
                        <i class="ph text-[26px]" :class="item.icon"></i>
                    </button>
                </template>
            </div>
        </nav>

        <div x-show="filterOpen" x-cloak x-transition.opacity @click.self="filterOpen = false" class="absolute inset-0 bg-black/50 z-50 flex items-end justify-center backdrop-blur-[2px]">
            <form method="GET" action="{{ route('crm.dashboard') }}" class="bg-white rounded-t-[24px] w-full p-6 max-h-[85%] overflow-y-auto dashboard-scroll text-black shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-[18px] font-bold text-[#0a3d2e] tracking-tight">Filter Data</h3>
                    <button type="button" @click="filterOpen = false" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500"><i class="ph-bold ph-x text-[16px]"></i></button>
                </div>
                <div class="flex flex-col gap-6">
                    <label class="flex flex-col gap-3"><span class="text-[13px] font-bold text-gray-700">Pilih Cabang</span>
                        <select name="branch_id" class="w-full appearance-none bg-gray-50 border border-gray-200 text-gray-700 text-[14px] font-medium rounded-2xl px-4 py-3.5">
                            <option value="">Semua Cabang</option>
                            @foreach ($branches as $branch)<option value="{{ $branch->id }}" {{ $branchId === (int) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="flex flex-col gap-3"><span class="text-[13px] font-bold text-gray-700">Periode Berdasarkan</span>
                        <select name="period" class="w-full appearance-none bg-gray-50 border border-gray-200 text-gray-700 text-[14px] font-medium rounded-2xl px-4 py-3.5">
                            <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Hari dalam Minggu</option>
                            <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulan dalam Tahun</option>
                            <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Tahunan</option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-3"><span class="text-[13px] font-bold text-gray-700">Geser Periode</span>
                        <select name="offset" class="w-full appearance-none bg-gray-50 border border-gray-200 text-gray-700 text-[14px] font-medium rounded-2xl px-4 py-3.5">
                            @for ($i = 0; $i >= -12; $i--)<option value="{{ $i }}" {{ $offset === $i ? 'selected' : '' }}>{{ $i === 0 ? 'Periode ini' : abs($i) . ' periode lalu' }}</option>@endfor
                        </select>
                    </label>
                </div>
                <div class="flex gap-3 mt-8">
                    <a href="{{ route('crm.dashboard') }}" class="px-5 bg-[#0F5C45]/10 text-[#0F5C45] border border-[#0F5C45]/20 rounded-full py-3.5 text-[13px] font-bold whitespace-nowrap">Minggu Ini</a>
                    <button type="submit" class="flex-1 bg-[#0F5C45] text-white rounded-full py-3.5 text-[14px] font-bold shadow-[0_8px_20px_rgba(15,92,69,0.3)]">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </section>

    {{-- Desktop cards use the same data and visual hierarchy as the HTML reference. --}}
    <section class="hidden md:flex flex-col gap-4 xl:gap-5 min-h-0 pb-5">
        <div class="flex items-center justify-between gap-4">
            <div><p class="text-[11px] font-bold uppercase tracking-[.18em] text-[#0F5C45]">Ringkasan Bisnis</p><h2 class="font-serif text-[24px] font-semibold text-gray-800 mt-1">{{ $periodTitle }}</h2></div>
            <form method="GET" action="{{ route('crm.dashboard') }}" class="flex items-center gap-2">
                <select name="branch_id" class="bg-white border border-gray-200 rounded-full px-4 py-2 text-[12px] text-gray-700 font-semibold" onchange="this.form.submit()"><option value="">Semua Cabang</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" {{ $branchId === (int) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>@endforeach</select>
                <select name="period" class="bg-white border border-gray-200 rounded-full px-4 py-2 text-[12px] text-gray-700 font-semibold" onchange="this.form.submit()"><option value="week" {{ $period === 'week' ? 'selected' : '' }}>Mingguan</option><option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulanan</option><option value="year" {{ $period === 'year' ? 'selected' : '' }}>Tahunan</option></select>
                <input type="hidden" name="offset" value="{{ $offset }}">
                <a href="{{ $prevUrl }}" class="bg-white border border-gray-200 rounded-full w-9 h-9 flex items-center justify-center"><i class="ph-bold ph-caret-left"></i></a>
                <a href="{{ $nextUrl }}" class="bg-white border border-gray-200 rounded-full w-9 h-9 flex items-center justify-center {{ $offset >= 0 ? 'opacity-30 pointer-events-none' : '' }}"><i class="ph-bold ph-caret-right"></i></a>
            </form>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 xl:gap-5">
            <div class="rounded-[14px] p-5 text-white flex flex-col justify-between min-h-[190px]" style="background-color:#0b595b"><div class="flex justify-between items-start"><h3 class="text-[14px] font-medium">Total Pendapatan</h3><i class="ph-bold ph-dots-three-vertical"></i></div><div><p class="text-[24px] font-bold tracking-tight">{{ $currency($metrics['revenue']) }}</p><span class="text-[11px] text-white/70">Penjualan terbayar & piutang</span></div><div class="flex justify-between text-[10px] text-white/70"><span>{{ $branchName }}</span><span>{{ $periodTitle }}</span></div></div>
            <div class="rounded-[14px] p-5 text-gray-800 flex flex-col justify-between min-h-[190px] bg-white border border-gray-100 shadow-sm"><div class="flex justify-between items-start"><h3 class="text-[14px] font-medium">Total Pengeluaran</h3><i class="ph-bold ph-dots-three-vertical"></i></div><div><p class="text-[24px] font-bold tracking-tight">{{ $currency($metrics['expense']) }}</p><span class="text-[11px] text-gray-500">Pengeluaran terbayar & utang</span></div><div class="flex justify-between text-[10px] text-gray-400"><span>{{ $branchName }}</span><span>{{ $periodTitle }}</span></div></div>
            <div class="rounded-[14px] p-5 text-gray-800 flex flex-col justify-between min-h-[190px] bg-white border border-gray-100 shadow-sm"><div class="flex justify-between items-start"><h3 class="text-[14px] font-medium">Total Keuntungan</h3><i class="ph ph-plant text-xl text-emerald-500"></i></div><div><p class="text-[24px] font-bold tracking-tight text-[#0F5C45]">{{ $currency($metrics['profit']) }}</p><span class="text-[11px] text-gray-500">Setelah HPP dan pengeluaran</span></div><div class="flex justify-between text-[10px] text-gray-400"><span>{{ $branchName }}</span><span>{{ $periodTitle }}</span></div></div>
            <div class="rounded-[14px] p-5 text-gray-800 flex flex-col justify-between min-h-[190px] bg-white border border-gray-100 shadow-sm"><div class="flex justify-between items-start"><h3 class="text-[14px] font-medium">Total Kerugian</h3><i class="ph ph-fire text-xl text-red-500"></i></div><div><p class="text-[24px] font-bold tracking-tight text-red-600">{{ $currency($metrics['loss']) }}</p><span class="text-[11px] text-gray-500">Saat biaya melebihi pendapatan</span></div><div class="flex justify-between text-[10px] text-gray-400"><span>{{ $branchName }}</span><span>{{ $periodTitle }}</span></div></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.7fr)_minmax(280px,1fr)] gap-4 xl:gap-5">
            <div class="rounded-[14px] bg-white border border-gray-100 shadow-sm p-5 min-h-[270px]"><div class="flex items-center justify-between mb-7"><div><h3 class="text-[15px] font-bold text-gray-800">Tren Pendapatan</h3><p class="text-[11px] text-gray-400 mt-1">{{ $periodTitle }}</p></div><span class="w-2 h-2 rounded-full bg-[#0b595b]"></span></div><div class="flex items-end justify-between h-[160px] gap-2">@php $maxRevenue = max(1, max(array_column($chart, 'revenue') ?: [0])); @endphp @foreach ($chart as $point)<div class="flex-1 flex flex-col items-center justify-end h-full gap-2" title="{{ $point['label'] }}: {{ $currency($point['revenue']) }}"><div class="w-full max-w-[34px] bg-[#0b595b] rounded-t-[5px]" style="height:{{ max(2, $point['revenue'] / $maxRevenue * 100) }}%"></div><span class="text-[10px] text-gray-500 truncate max-w-full">{{ $point['label'] }}</span></div>@endforeach</div></div>
            <div class="rounded-[14px] bg-white border border-gray-100 shadow-sm p-5"><div class="flex items-center justify-between mb-6"><div><h3 class="text-[15px] font-bold text-gray-800">Produk Pendapatan Tertinggi</h3><p class="text-[11px] text-gray-400 mt-1">{{ $periodTitle }}</p></div><i class="ph ph-chart-bar text-xl text-[#0b595b]"></i></div><div class="flex flex-col gap-4">@forelse ($topProducts as $product)<div class="flex items-center justify-between gap-3"><div class="flex items-center gap-3 min-w-0"><img src="{{ $product->image ? asset('storage/' . $product->image) : asset('assets/media/svg/files/blank-image.svg') }}" alt="{{ $product->name }}" class="w-9 h-9 rounded-lg object-cover border border-gray-100"><span class="text-[12px] font-semibold text-gray-700 truncate">{{ $product->name }}</span></div><span class="text-[12px] font-bold text-[#0F5C45] whitespace-nowrap">{{ $currency($product->amount) }}</span></div>@empty<p class="text-[12px] text-gray-500">Belum ada penjualan pada periode ini.</p>@endforelse</div></div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    function erpDashboard() {
        const chart = @json($chart);
        const metrics = @json($metrics);
        return {
            chart,
            metrics,
            mode: 'revenue',
            selectedIndex: Math.max(0, chart.length - 1),
            filterOpen: false,
            showBottom: false,
            modes: [
                { key: 'revenue', label: 'Pendapatan', icon: 'ph-cardholder' },
                { key: 'expense', label: 'Pengeluaran', icon: 'ph-shopping-bag' },
                { key: 'menu', label: 'Buka menu', icon: 'ph-list' },
                { key: 'profit', label: 'Keuntungan', icon: 'ph-plant' },
                { key: 'loss', label: 'Kerugian', icon: 'ph-fire' },
            ],
            get modeTitle() { return {revenue: 'Total Pendapatan', expense: 'Total Pengeluaran', profit: 'Total Keuntungan', loss: 'Total Kerugian'}[this.mode]; },
            get total() { return Number(this.metrics[this.mode] || 0); },
            rupiah(value) { return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0)); },
            barHeight(point) {
                const highest = Math.max(1, ...this.chart.map(item => Number(item[this.mode] || 0)));
                return Math.max(3, Number(point[this.mode] || 0) / highest * 100);
            },
        };
    }
</script>
@endpush
