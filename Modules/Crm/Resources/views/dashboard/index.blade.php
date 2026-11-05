@extends('layouts.erp')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-breadcrumb', 'Dashboard')
@section('dashboard-fullscreen', '1')

@section('extra-style')
    .erp-dashboard { --dashboard-accent: 211 236 90; --dashboard-dark: #0f5c45; }
    .erp-dashboard[data-mode="expense"] { --dashboard-accent: 253 164 175; --dashboard-dark: #741d27; }
    .erp-dashboard[data-mode="profit"] { --dashboard-accent: 52 211 153; --dashboard-dark: #0a5c47; }
    .erp-dashboard[data-mode="loss"] { --dashboard-accent: 244 63 94; --dashboard-dark: #881337; }
    .erp-dashboard .mobile-bg-layer { position: absolute; inset: 0; pointer-events: none; transition: opacity .4s ease; }
    .erp-dashboard .mobile-bg-income,
    .erp-dashboard .mobile-bg-expense,
    .erp-dashboard .mobile-bg-profit,
    .erp-dashboard .mobile-bg-loss { background: linear-gradient(180deg, #f3f7f6 0%, #f8fafc 100%); }
    .erp-dashboard .mobile-bg-layer { opacity: 0; }
    .erp-dashboard[data-mode="revenue"] .mobile-bg-income,
    .erp-dashboard[data-mode="expense"] .mobile-bg-expense,
    .erp-dashboard[data-mode="profit"] .mobile-bg-profit,
    .erp-dashboard[data-mode="loss"] .mobile-bg-loss { opacity: 1; }
    .erp-dashboard .dashboard-scroll { scrollbar-width: none; }
    .erp-dashboard .dashboard-scroll::-webkit-scrollbar { display: none; }
    .erp-dashboard .dashboard-bar { background: rgb(var(--dashboard-accent)); }
    .erp-dashboard .dashboard-bar.active { box-shadow: 0 0 15px rgb(var(--dashboard-accent) / .4); }
    .erp-dashboard .dashboard-hero {
        padding: 22px 20px 20px;
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 22px;
        background: linear-gradient(135deg, #06281e 0%, #0f5c45 50%, #041a13 100%);
        box-shadow: 0 10px 28px -6px rgba(15,92,69,.35), 0 3px 10px rgba(0,0,0,.12);
    }
    .erp-dashboard .dashboard-hero-shine {
        background: radial-gradient(circle, rgba(209,243,110,.22) 0%, rgba(242,180,0,.1) 45%, transparent 70%);
    }
    .erp-dashboard .dashboard-chart-plot { height: 143px; }
    .erp-dashboard[data-mode="expense"] .dashboard-hero { background: linear-gradient(135deg,#2c0b10 0%,#741d27 50%,#1c0609 100%); border-color:rgba(253,164,175,.25); box-shadow:0 10px 28px -6px rgba(116,29,39,.35),0 3px 10px rgba(0,0,0,.12); }
    .erp-dashboard[data-mode="profit"] .dashboard-hero { background: linear-gradient(135deg,#02241d 0%,#0a5c47 50%,#011a15 100%); border-color:rgba(167,243,208,.25); box-shadow:0 10px 28px -6px rgba(10,92,71,.35),0 3px 10px rgba(0,0,0,.12); }
    .erp-dashboard[data-mode="loss"] .dashboard-hero { background: linear-gradient(135deg,#30050c 0%,#881337 50%,#1e0307 100%); border-color:rgba(251,113,133,.28); box-shadow:0 10px 28px -6px rgba(136,19,55,.4),0 3px 10px rgba(0,0,0,.12); }
    .erp-dashboard[data-mode="expense"] .dashboard-hero-shine { background:radial-gradient(circle,rgba(251,113,133,.22) 0%,rgba(116,29,39,.12) 45%,transparent 70%); }
    .erp-dashboard[data-mode="profit"] .dashboard-hero-shine { background:radial-gradient(circle,rgba(52,211,153,.22) 0%,rgba(10,92,71,.12) 45%,transparent 70%); }
    .erp-dashboard[data-mode="loss"] .dashboard-hero-shine { background:radial-gradient(circle,rgba(244,63,94,.24) 0%,rgba(136,19,55,.12) 45%,transparent 70%); }
    .erp-dashboard .icon-rounded { display:inline-block; line-height:1; letter-spacing:normal; text-transform:none; white-space:nowrap; direction:ltr; -webkit-font-feature-settings:'liga'; font-feature-settings:'liga'; -webkit-font-smoothing:antialiased; }
    @media (max-width: 767px) {
        .erp-dashboard { flex: 1 1 auto; height: auto; max-height: none; min-height: 0; margin-top: 0; margin-bottom: 0; }
    }
    @media (max-width: 767px) and (max-height: 850px) {
        .erp-dashboard .dashboard-hero { min-height: clamp(330px, calc(100dvh - 457px), 422px); }
        .erp-dashboard .dashboard-chart-plot { height: 118px; }
    }
@endsection

@section('content')
@php
    $periodTitle = $period === 'year'
        ? $start->format('Y')
        : ($period === 'month' ? $start->locale('id')->isoFormat('MMMM Y') : $start->locale('id')->isoFormat('DD') . ' - ' . $end->locale('id')->isoFormat('DD MMMM YYYY'));
    $branchName = $branchId ? optional($branches->firstWhere('id', $branchId))->name : 'Semua Cabang';
    $currency = static function ($amount) { return 'Rp ' . number_format((float) $amount, 0, ',', '.'); };
    $nextUrl = route('crm.dashboard', array_filter(['period' => $period, 'offset' => min(0, $offset + 1), 'branch_id' => $branchId], static function ($value) { return $value !== null; }));
    $prevUrl = route('crm.dashboard', array_filter(['period' => $period, 'offset' => max(-120, $offset - 1), 'branch_id' => $branchId], static function ($value) { return $value !== null; }));
    $filterBranches = collect([['id' => '', 'name' => 'Semua Cabang', 'note' => 'Konsolidasi seluruh cabang']])->concat($branches->map(static function ($branch) {
        return ['id' => (string) $branch->id, 'name' => $branch->name, 'note' => 'Cabang operasional'];
    }))->values();
    $filterPeriods = collect(range(0, 51))->map(static function ($weeksBack) {
        $weekStart = \Carbon\Carbon::today()->startOfWeek(\Carbon\Carbon::SATURDAY)->subWeeks($weeksBack);
        $weekEnd = $weekStart->copy()->addDays(6);
        $monthName = ucfirst($weekStart->locale('id')->monthName);
        $endMonthName = ucfirst($weekEnd->locale('id')->monthName);
        $range = $weekStart->format('m-Y') === $weekEnd->format('m-Y')
            ? $weekStart->format('d') . ' - ' . $weekEnd->format('d') . ' ' . $monthName . ' ' . $weekStart->format('Y')
            : $weekStart->format('d') . ' ' . $monthName . ' - ' . $weekEnd->format('d') . ' ' . $endMonthName . ' ' . $weekEnd->format('Y');
        $yearStart = $weekStart->copy()->startOfYear();
        while ($yearStart->dayOfWeek !== \Carbon\Carbon::SATURDAY) {
            $yearStart->addDay();
        }
        $weekNumber = $yearStart->diffInWeeks($weekStart) + 1;
        $note = 'Minggu ke-' . $weekNumber . ' · ' . $monthName . ' ' . $weekStart->format('Y');
        if ($weeksBack === 0) {
            $note .= ' (Periode Berjalan)';
        }

        return ['offset' => -$weeksBack, 'name' => $range, 'note' => $note];
    })->values();
    $mobileContributors = collect($contributors)->mapWithKeys(static function ($sorts, $mode) use ($metrics) {
        return [$mode => collect($sorts)->map(static function ($items) use ($mode, $metrics) {
            return $items->map(static function ($item) use ($mode, $metrics) {
                $amount = (float) $item->amount;
                $base = $mode === 'profit' ? (float) ($item->sales ?? 0) : (float) $metrics[$mode];
                return [
                    'name' => $item->name,
                    'amount' => $amount,
                    'percent' => $base > 0 ? round($amount / $base * 100) : 0,
                ];
            })->values();
        })->toArray()];
    })->toArray();
@endphp
<div class="erp-dashboard flex flex-1 flex-col min-h-0 w-full" x-data="erpDashboard()" :data-mode="mode">
    {{-- Mobile dashboard follows the current HTML reference, using CRM data from the controller. --}}
    <section class="md:hidden relative flex flex-col text-white overflow-hidden bg-[#ebf2ef] h-full" @mobile-search-toggle.window="searchOpen = !searchOpen">
        <div class="mobile-bg-layer mobile-bg-income"></div>
        <div class="mobile-bg-layer mobile-bg-expense"></div>
        <div class="mobile-bg-layer mobile-bg-profit"></div>
        <div class="mobile-bg-layer mobile-bg-loss"></div>
        <div class="absolute -top-32 -left-40 w-[500px] h-[500px] rounded-full blur-[100px] pointer-events-none z-0 transition-colors duration-500 opacity-[.08]" style="background:rgb(var(--dashboard-accent))"></div>
        <div class="absolute -top-16 -left-16 w-[250px] h-[250px] bg-white/25 rounded-full blur-[70px] pointer-events-none z-0 mix-blend-overlay"></div>



        <div class="flex-1 overflow-y-auto dashboard-scroll pt-5 pb-[112px] px-4 flex flex-col relative z-10">
            <article class="dashboard-hero relative min-h-[422px] overflow-hidden touch-pan-y select-none" @touchstart.passive="startSwipe($event)" @touchend.passive="endSwipe($event)" @mousedown="startSwipe($event)" @mouseup="endSwipe($event)" @mouseleave="swipeStartX = null" @click.outside="resetSelectedDay()">
                <div class="dashboard-hero-shine pointer-events-none absolute -right-[60px] -top-[60px] h-40 w-40 rounded-full"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0 text-left">
                            <p class="truncate text-[13.5px] font-bold tracking-wide">{{ $branchName }}</p>
                            <p class="mt-0.5 text-[11px] font-medium" :style="'color:' + modePalette.subtext">Periode : {{ $periodTitle }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <button type="button" @click="filterOpen = true" aria-label="Filter dashboard" class="h-9 w-9 rounded-full border border-white/15 bg-white/10 flex items-center justify-center hover:bg-white/20"><i class="ph ph-sliders-horizontal text-[17px]"></i></button>
                        </div>
                    </div>

                    <div class="mt-3 text-left">
                        <p class="text-[11.5px] font-medium" :style="'color:' + modePalette.subtext" x-text="modeTitle + ' (' + modeContext + ')' "></p>
                        <p class="mt-1 flex items-baseline gap-2"><span class="text-[16px] font-medium text-white/90">Rp</span><span class="font-['Poppins'] text-[30px] leading-none font-bold tracking-tight tabular-nums" x-text="number(total)"></span></p>
                        <div class="mt-1 flex flex-wrap items-center gap-x-2 text-[11px] text-white/75">
                            <span class="inline-flex items-center gap-1 font-bold" :style="'color:' + trendColor"><i class="ph-fill" :class="trendIcon"></i><span x-text="trendLabel"></span></span>
                            <span><span x-text="period === 'week' ? 'vs minggu sebelumnya (' : 'vs periode sebelumnya ('"></span><strong x-text="'Rp ' + number(previousTotal)"></strong>)</span>
                        </div>
                    </div>

                    <div class="mt-4 flex-1 border-t border-white/15 pt-3.5 flex flex-col">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <div class="min-w-0 text-left"><p class="text-[12px] font-bold tracking-wide" x-text="chartTitle"></p><p class="mt-0.5 text-[10px]" :style="'color:' + modePalette.subtext" x-text="chartSubtitle"></p></div>
                            <div class="shrink-0 text-right"><p class="text-[13px] font-bold leading-tight" :style="'color:' + modePalette.accent" x-text="selectedPoint ? rupiah(selectedPoint[mode]) : 'Rp 0'"></p><p class="mt-0.5 text-[9px] text-white/70" x-text="selectedPoint ? (selectedPoint.date ? formatChartDate(selectedPoint.date) : selectedPoint.label) : ''"></p></div>
                        </div>
                        <div class="dashboard-chart-plot flex items-end justify-between h-[143px] relative w-full gap-2 mt-auto">
                    <template x-for="(point, index) in chart" :key="index">
                        <button type="button" @click="selectedIndex = index" class="flex-1 min-w-0 flex flex-col items-center justify-end gap-2 z-10 h-full group" :aria-label="point.label + ' ' + point.day + ': ' + rupiah(point[mode])">
                            <div class="w-full max-w-[26px] rounded-t-md transition-all relative min-h-[3px]" :style="'height:' + barHeight(point) + '%;background-color:' + (selectedIndex === index ? modePalette.accent : modePalette.bar) + (selectedIndex === index ? ';box-shadow:0 0 12px ' + modePalette.accent + '88' : '')"></div>
                            <span class="flex flex-col items-center leading-none"><span class="text-[10px] font-bold" :style="selectedIndex === index ? 'color:' + modePalette.accent : 'color:rgba(255,255,255,.75)'" x-text="point.label"></span><span class="mt-1 text-[9px]" :class="selectedIndex === index ? 'text-white font-semibold' : 'text-white/70'" x-text="point.day"></span></span>
                        </button>
                    </template>
                        </div>
                        <div class="mt-3.5 flex items-center justify-center gap-0" role="group" aria-label="Pilih metrik dashboard">
                            <template x-for="item in metricModes" :key="item.key">
                                <button type="button" @click="selectMode(item.key)" :aria-pressed="mode === item.key" :title="item.label" :aria-label="item.label" class="inline-flex w-11 h-11 items-center justify-center rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-white"><span :class="mode === item.key ? 'w-5' : 'w-1.5 bg-white/30'" :style="mode === item.key ? 'background-color:' + modePalette.accent : ''" class="h-1.5 rounded-full" aria-hidden="true"></span></button>
                            </template>
                        </div>
                    </div>
                </div>
            </article>

            <div class="mt-6 mb-4 min-h-[248px] shrink-0 rounded-[24px] border border-white bg-white px-4 py-4 shadow-[0_8px_30px_rgba(0,0,0,.06)] text-gray-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div class="min-w-0"><h2 class="text-[15px] font-bold tracking-tight text-slate-900"><span x-text="contributorTitle"></span> <span :class="contributorAccentClass" x-text="contributorSuffix"></span></h2><p class="mt-1 text-[11px] text-slate-400" x-text="contributorSubtitle"></p></div>
                    <button type="button" @click="showBottom = !showBottom" class="h-10 w-10 shrink-0 rounded-full border flex items-center justify-center active:scale-90 transition" :class="contributorButtonClass" :title="contributorToggleTitle" :aria-label="contributorToggleTitle">
                        <i class="ph-bold text-[17px]" :class="showBottom ? 'ph-arrow-down-right' : 'ph-arrow-up-right'"></i>
                    </button>
                </div>
                <div class="flex flex-col divide-y divide-gray-100">
                    <template x-for="(item, index) in filteredContributors" :key="mode + '-' + showBottom + '-' + index">
                        <div class="flex items-center justify-between gap-2 py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-10 h-10 rounded-xl border flex items-center justify-center text-[22px] leading-none shrink-0" :class="isCostMode ? 'bg-rose-50 border-rose-100' : 'bg-emerald-50 border-emerald-100'" role="img" :aria-label="item.name + ' icon'" x-text="itemIcon(item.name)"></span>
                                <div class="min-w-0"><h3 class="text-[13px] font-semibold text-slate-800 leading-tight truncate" x-text="item.name"></h3><p class="text-[11.5px] font-bold mt-0.5" :class="contributorAmountClass" x-text="rupiah(item.amount)"></p></div>
                            </div>
                            <span class="text-[13px] font-bold shrink-0" :class="contributorPercentClass" x-text="item.percent + '%'"></span>
                        </div>
                    </template>
                    <div x-show="filteredContributors.length === 0" class="flex min-h-[120px] flex-col items-center justify-center py-4 text-center"><i class="ph ph-chart-line-down text-xl text-gray-300"></i><p class="mt-2 text-[12px] font-medium text-gray-600" x-text="searchQuery ? 'Tidak ada hasil pencarian.' : 'Belum ada data pada periode ini.'"></p></div>
                </div>
            </div>
        </div>

        <div x-show="searchOpen" x-cloak x-transition class="absolute bottom-[calc(5.75rem+env(safe-area-inset-bottom))] left-0 right-0 z-30">
            <x-erp.mobile-search class="erp-mobile-search-shell--overlay">
                <x-slot:input><input type="search" x-model="searchQuery" aria-label="Cari produk" placeholder="Cari produk..." class="erp-mobile-search__input"></x-slot:input>
            </x-erp.mobile-search>
        </div>



        <template x-teleport="body">
            <div x-show="filterOpen" x-cloak x-transition.opacity @click.self="filterOpen = false" @keydown.escape.window="filterOpen = false" class="fixed inset-0 z-[100] flex items-end justify-center overflow-hidden bg-black/70 backdrop-blur-[2px]">
                <form method="GET" action="{{ route('crm.dashboard') }}" class="flex h-[85dvh] max-h-[85dvh] w-full flex-col overflow-hidden rounded-t-[32px] bg-white pb-[env(safe-area-inset-bottom)] text-gray-800 shadow-2xl">
                    <input type="hidden" name="period" value="week">
                    <div class="flex shrink-0 justify-center pt-3 pb-1"><div class="h-1.5 w-16 rounded-full bg-gray-300"></div></div>

                    <header class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
                        <div>
                            <h3 class="text-[18px] font-bold tracking-tight text-gray-800">Filter Dashboard</h3>
                            <p class="mt-0.5 text-[11px] text-gray-400">Pilih cabang &amp; rentang periode laporan</p>
                        </div>
                        <button type="button" @click="filterOpen = false" aria-label="Tutup filter" class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200 active:scale-95"><i class="ph-bold ph-x"></i></button>
                    </header>

                    <div class="dashboard-scroll flex-1 space-y-5 overflow-y-auto overscroll-contain p-4">
                        <section class="px-1">
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Cabang Toko</h4>
                            <div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <label class="relative flex h-10 shrink-0 items-center border-b border-gray-100 px-3.5 text-gray-400">
                                    <i class="ph-bold ph-magnifying-glass text-xs"></i>
                                    <input type="search" x-model="branchSearchQuery" placeholder="Cari cabang..." aria-label="Cari cabang" class="h-full w-full border-0 bg-transparent pl-3 pr-7 text-xs text-gray-700 outline-none placeholder:text-gray-400 focus:ring-0">
                                    <button x-show="branchSearchQuery" x-cloak type="button" @click="branchSearchQuery = ''" aria-label="Hapus pencarian cabang" class="absolute right-3 text-gray-400 hover:text-gray-600"><i class="ph-bold ph-x text-xs"></i></button>
                                </label>
                                <div class="scrollbar-hide max-h-[190px] divide-y divide-gray-100 overflow-y-auto overscroll-contain bg-gray-50/30">
                                    <template x-for="branch in filterBranches.filter(item => (item.name + ' ' + item.note).toLocaleLowerCase('id-ID').includes(branchSearchQuery.trim().toLocaleLowerCase('id-ID')))" :key="'branch-' + branch.id">
                                        <label class="flex cursor-pointer items-center justify-between px-3.5 py-3 transition-colors hover:bg-gray-50">
                                            <span class="flex min-w-0 flex-col pr-2 text-left">
                                                <span class="text-xs text-gray-700" :class="String(tempBranchId) === String(branch.id) ? 'font-bold' : 'font-medium'" x-text="branch.name"></span>
                                                <span class="text-[10px] font-normal text-gray-400" x-text="branch.note"></span>
                                            </span>
                                            <span class="relative flex h-4 w-4 shrink-0 items-center justify-center">
                                                <input type="radio" name="branch_id" :value="branch.id" x-model="tempBranchId" class="peer h-4 w-4 cursor-pointer appearance-none rounded-full border-2 border-gray-300 checked:border-emerald-500">
                                                <span class="pointer-events-none absolute h-2 w-2 scale-0 rounded-full bg-emerald-500 transition-transform peer-checked:scale-100"></span>
                                            </span>
                                        </label>
                                    </template>
                                    <p x-show="filterBranches.filter(item => (item.name + ' ' + item.note).toLocaleLowerCase('id-ID').includes(branchSearchQuery.trim().toLocaleLowerCase('id-ID'))).length === 0" class="py-4 text-center text-xs text-gray-400">Cabang tidak ditemukan</p>
                                </div>
                            </div>
                        </section>

                        <section class="px-1">
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Rentang Periode</h4>
                            <div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <label class="relative flex h-10 shrink-0 items-center border-b border-gray-100 px-3.5 text-gray-400">
                                    <i class="ph-bold ph-magnifying-glass text-xs"></i>
                                    <input type="search" x-model="periodSearchQuery" placeholder="Cari periode atau bulan..." aria-label="Cari periode" class="h-full w-full border-0 bg-transparent pl-3 pr-7 text-xs text-gray-700 outline-none placeholder:text-gray-400 focus:ring-0">
                                    <button x-show="periodSearchQuery" x-cloak type="button" @click="periodSearchQuery = ''" aria-label="Hapus pencarian periode" class="absolute right-3 text-gray-400 hover:text-gray-600"><i class="ph-bold ph-x text-xs"></i></button>
                                </label>
                                <div class="scrollbar-hide max-h-[300px] divide-y divide-gray-100 overflow-y-auto overscroll-contain bg-gray-50/30">
                                    <template x-for="item in filterPeriods.filter(periodItem => (periodItem.name + ' ' + periodItem.note).toLocaleLowerCase('id-ID').includes(periodSearchQuery.trim().toLocaleLowerCase('id-ID')))" :key="'period-' + item.offset">
                                        <label class="flex cursor-pointer items-center justify-between px-3.5 py-3 transition-colors hover:bg-gray-50">
                                            <span class="flex min-w-0 flex-col pr-2 text-left">
                                                <span class="text-xs text-gray-700" :class="Number(tempOffset) === Number(item.offset) ? 'font-bold' : 'font-medium'" x-text="item.name"></span>
                                                <span class="text-[10px] font-normal text-gray-400" x-text="item.note"></span>
                                            </span>
                                            <span class="relative flex h-4 w-4 shrink-0 items-center justify-center">
                                                <input type="radio" name="offset" :value="item.offset" x-model="tempOffset" class="peer h-4 w-4 cursor-pointer appearance-none rounded-full border-2 border-gray-300 checked:border-emerald-500">
                                                <span class="pointer-events-none absolute h-2 w-2 scale-0 rounded-full bg-emerald-500 transition-transform peer-checked:scale-100"></span>
                                            </span>
                                        </label>
                                    </template>
                                    <p x-show="filterPeriods.filter(periodItem => (periodItem.name + ' ' + periodItem.note).toLocaleLowerCase('id-ID').includes(periodSearchQuery.trim().toLocaleLowerCase('id-ID'))).length === 0" class="py-4 text-center text-xs text-gray-400">Periode tidak ditemukan</p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <footer class="flex shrink-0 items-center gap-2.5 border-t border-gray-100 bg-white px-4 pt-3.5 pb-[calc(.75rem+env(safe-area-inset-bottom))]">
                        <button type="button" @click="filterOpen = false" class="h-11 flex-1 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50 active:scale-[.98]">Batal</button>
                        <button type="submit" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-xs font-bold text-white shadow-sm transition-colors hover:bg-[#08484a] active:scale-[.98]">Terapkan Filter</button>
                    </footer>
                </form>
            </div>
        </template>
    </section>

    {{-- Desktop cards use the same data and visual hierarchy as the HTML reference. --}}
    <section class="hidden md:flex flex-col gap-4 xl:gap-5 min-h-0 pb-5">
        <div class="flex items-center justify-between gap-4">
            <div><p class="text-[11px] font-bold uppercase tracking-[.18em] text-[#0F5C45]">Ringkasan Bisnis</p><h2 class="font-serif text-[24px] font-semibold text-gray-800 mt-1">{{ $periodTitle }}</h2></div>
            <form method="GET" action="{{ route('crm.dashboard') }}" class="flex items-center gap-2">
                <select name="branch_id" class="bg-white border border-gray-200 rounded-full px-4 py-2 text-[12px] text-gray-700 font-semibold" onchange="this.form.submit()"><option value="">Semua Cabang</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" {{ $branchId === (int) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>@endforeach</select>
                <select name="period" class="bg-white border border-gray-200 rounded-full px-4 py-2 text-[12px] text-gray-700 font-semibold" onchange="this.form.submit()"><option value="week" {{ $period === 'week' ? 'selected' : '' }}>Mingguan</option><option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulanan</option><option value="year" {{ $period === 'year' ? 'selected' : '' }}>Tahunan</option></select>
                <input type="hidden" name="offset" value="{{ $offset }}">
                <a href="{{ $prevUrl }}" class="bg-white border border-gray-200 rounded-full w-9 h-9 flex items-center justify-center"><i class="ph-bold ph-caret-left text-[16px]"></i></a>
                <a href="{{ $nextUrl }}" class="bg-white border border-gray-200 rounded-full w-9 h-9 flex items-center justify-center {{ $offset >= 0 ? 'opacity-30 pointer-events-none' : '' }}"><i class="ph-bold ph-caret-right text-[16px]"></i></a>
            </form>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 xl:gap-5">
            <div class="rounded-[14px] p-5 text-white flex flex-col justify-between min-h-[190px]" style="background-color:#0b595b"><div class="flex justify-between items-start"><h3 class="text-[14px] font-medium">Total Pendapatan</h3><i class="ph-bold ph-dots-three-vertical text-[16px]"></i></div><div class="text-[16px]"><p class="text-[24px] font-bold tracking-tight">{{ $currency($metrics['revenue']) }}</p><span class="text-[11px] text-white/70">Penjualan terbayar & piutang</span></div><div class="flex justify-between text-[10px] text-white/70"><span>{{ $branchName }}</span><span>{{ $periodTitle }}</span></div></div>
            <div class="rounded-[14px] p-5 text-gray-800 flex flex-col justify-between min-h-[190px] bg-white border border-gray-100 shadow-sm"><div class="flex justify-between items-start"><h3 class="text-[14px] font-medium">Total Pengeluaran</h3><i class="ph-bold ph-dots-three-vertical text-[16px]"></i></div><div class="text-[16px]"><p class="text-[24px] font-bold tracking-tight">{{ $currency($metrics['expense']) }}</p><span class="text-[11px] text-gray-500">Pengeluaran terbayar & utang</span></div><div class="flex justify-between text-[10px] text-gray-400"><span>{{ $branchName }}</span><span>{{ $periodTitle }}</span></div></div>
            <div class="rounded-[14px] p-5 text-gray-800 flex flex-col justify-between min-h-[190px] bg-white border border-gray-100 shadow-sm"><div class="flex justify-between items-start"><h3 class="text-[14px] font-medium">Total Keuntungan</h3><i class="ph ph-plant text-xl text-emerald-500"></i></div><div class="text-[16px]"><p class="text-[24px] font-bold tracking-tight text-[#0F5C45]">{{ $currency($metrics['profit']) }}</p><span class="text-[11px] text-gray-500">Setelah HPP dan pengeluaran</span></div><div class="flex justify-between text-[10px] text-gray-400"><span>{{ $branchName }}</span><span>{{ $periodTitle }}</span></div></div>
            <div class="rounded-[14px] p-5 text-gray-800 flex flex-col justify-between min-h-[190px] bg-white border border-gray-100 shadow-sm"><div class="flex justify-between items-start"><h3 class="text-[14px] font-medium">Total Kerugian</h3><i class="ph ph-fire text-xl text-red-500"></i></div><div class="text-[16px]"><p class="text-[24px] font-bold tracking-tight text-red-600">{{ $currency($metrics['loss']) }}</p><span class="text-[11px] text-gray-500">Saat biaya melebihi pendapatan</span></div><div class="flex justify-between text-[10px] text-gray-400"><span>{{ $branchName }}</span><span>{{ $periodTitle }}</span></div></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.7fr)_minmax(280px,1fr)] gap-4 xl:gap-5">
            <div class="rounded-[14px] bg-white border border-gray-100 shadow-sm p-5 min-h-[270px]"><div class="flex items-center justify-between mb-7"><div><h3 class="text-[15px] font-bold text-gray-800">Tren Pendapatan</h3><p class="text-[11px] text-gray-400 mt-1">{{ $periodTitle }}</p></div><span class="w-2 h-2 rounded-full bg-[#0b595b]"></span></div><div class="flex items-end justify-between h-[160px] gap-2">@php $maxRevenue = max(1, max(array_column($chart, 'revenue') ?: [0])); @endphp @foreach ($chart as $point)<button type="button" class="flex-1 min-w-0 flex flex-col items-center justify-end h-full gap-2 rounded-lg" @click="selectedIndex = {{ $loop->index }}" :aria-pressed="selectedIndex === {{ $loop->index }}" aria-label="{{ $point['label'] }}: {{ $currency($point['revenue']) }}"><div class="w-full max-w-[34px] bg-[#0b595b] rounded-t-[5px]" style="height:{{ max(2, $point['revenue'] / $maxRevenue * 100) }}%"></div><span class="text-[10px] text-gray-500 truncate max-w-full">{{ $point['label'] }}</span></button>@endforeach</div><p class="mt-4 text-sm text-[#0b595b] font-semibold" aria-live="polite" x-text="selectedPoint ? selectedPoint.label + ': ' + rupiah(selectedPoint.revenue) : ''"></p></div>
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
        const previousMetrics = @json($previousMetrics);
        const contributors = @json($mobileContributors);
        const today = @json(now()->toDateString());
        const period = @json($period);
        const todayIndex = chart.findIndex(point => point.date === today);
        return {
            chart,
            metrics,
            previousMetrics,
            contributors,
            period,
            mode: 'revenue',
            selectedIndex: todayIndex >= 0 ? todayIndex : Math.max(0, chart.findLastIndex(point => Number(point.revenue || 0) > 0)),
            searchOpen: false,
            searchQuery: '',
            filterOpen: false,
            branchSearchQuery: '',
            periodSearchQuery: '',
            tempBranchId: @json($branchId === null ? '' : (string) $branchId),
            tempOffset: @json($offset),
            filterBranches: @json($filterBranches),
            filterPeriods: @json($filterPeriods),
            showBottom: false,
            swipeStartX: null,
            swipeStartY: null,
            metricModes: [
                { key: 'revenue', label: 'Pendapatan' },
                { key: 'expense', label: 'Pengeluaran' },
                { key: 'profit', label: 'Keuntungan' },
                { key: 'loss', label: 'Kerugian' },
            ],
            get selectedPoint() { return this.chart[this.selectedIndex] || null; },
            get modeIndex() { return this.metricModes.findIndex(item => item.key === this.mode); },
            get modePalette() {
                return {
                    revenue: { accent: '#d1f36e', subtext: '#a7f3d0', bar: 'rgba(16,185,129,.35)' },
                    expense: { accent: '#fda4af', subtext: '#fecdd3', bar: 'rgba(244,63,94,.35)' },
                    profit: { accent: '#34d399', subtext: '#a7f3d0', bar: 'rgba(16,185,129,.35)' },
                    loss: { accent: '#f43f5e', subtext: '#ffe4e6', bar: 'rgba(225,29,72,.35)' },
                }[this.mode];
            },
            get isCostMode() { return this.mode === 'expense' || this.mode === 'loss'; },
            get modeTitle() { return {revenue: 'Total Pendapatan', expense: 'Total Pengeluaran', profit: 'Total Keuntungan', loss: 'Total Kerugian'}[this.mode]; },
            get total() { return Number(this.metrics[this.mode] || 0); },
            get previousTotal() { return Number(this.previousMetrics[this.mode] || 0); },
            get modeContext() {
                const span = { week: '7 Hari', month: 'Bulanan', year: 'Tahunan' }[period];
                return { revenue: 'Omset', expense: 'Beban', profit: 'Laba Bersih', loss: 'Barang Buang' }[this.mode] + ' ' + span;
            },
            get chartTitle() {
                const name = {revenue: 'Omset', expense: 'Beban', profit: 'Laba', loss: 'Barang Buang'}[this.mode];
                return 'Distribusi ' + name + (period === 'year' ? ' Bulanan' : period === 'month' ? ' Mingguan' : period === 'week' && this.mode !== 'loss' ? ' Harian' : '');
            },
            get chartSubtitle() { return {revenue: 'Ketuk bar untuk melihat detail omset', expense: 'Ketuk bar untuk melihat detail beban', profit: 'Ketuk bar untuk melihat detail laba', loss: 'Ketuk bar untuk melihat detail barang buang'}[this.mode]; },
            get contributorTitle() { return {revenue: 'Produk Pendapatan', expense: 'Pos Pengeluaran', profit: 'Produk Margin Keuntungan', loss: 'Produk Susut & Rusak'}[this.mode]; },
            get contributorSuffix() { return this.isCostMode ? (this.showBottom ? 'Terkecil' : 'Terbesar') : (this.showBottom ? 'Terendah' : 'Tertinggi'); },
            get contributorAccentClass() {
                return (this.isCostMode ? !this.showBottom : this.showBottom) ? 'text-rose-700' : 'text-[#0F5C45]';
            },
            get contributorButtonClass() {
                const useRose = this.isCostMode ? !this.showBottom : this.showBottom;
                return useRose ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100';
            },
            get contributorAmountClass() {
                if (this.showBottom) return 'text-gray-700';
                return this.isCostMode ? 'text-rose-700' : 'text-[#0F5C45]';
            },
            get contributorPercentClass() {
                if (this.isCostMode) return this.showBottom ? 'text-emerald-600' : 'text-rose-600';
                return this.showBottom ? 'text-rose-700' : 'text-emerald-600';
            },
            get contributorToggleTitle() {
                if (this.isCostMode) return this.showBottom ? 'Klik untuk urutkan Terbesar' : 'Klik untuk urutkan Terkecil';
                return this.showBottom ? 'Klik untuk urutkan Tertinggi' : 'Klik untuk urutkan Terendah';
            },
            get contributorSubtitle() {
                const type = {revenue: 'omset', expense: 'beban', profit: 'laba bersih', loss: 'barang buang'}[this.mode];
                const size = this.showBottom ? (this.isCostMode ? 'terkecil' : 'terendah') : (this.isCostMode ? 'terbesar' : 'tertinggi');
                return '3 Kontributor ' + type + ' ' + size + (period === 'week' ? ' minggu ini' : ' pada periode ini');
            },
            get filteredContributors() {
                const items = this.contributors[this.mode]?.[this.showBottom ? 'lowest' : 'highest'] || [];
                const query = this.searchQuery.trim().toLocaleLowerCase('id-ID');
                return query ? items.filter(item => item.name.toLocaleLowerCase('id-ID').includes(query)) : items;
            },
            itemIcon(name) {
                const item = String(name || '').toLocaleLowerCase('id-ID');
                if (/stroberi|strawber/.test(item)) return '🍓';
                if (item.includes('pisang')) return '🍌';
                if (item.includes('jeruk')) return '🍊';
                if (item.includes('apel')) return '🍎';
                if (item.includes('mangga')) return '🥭';
                if (item.includes('alpukat')) return '🥑';
                if (item.includes('anggur')) return '🍇';
                if (item.includes('pepaya')) return '🍈';
                if (item.includes('melon')) return '🍈';
                if (item.includes('semangka')) return '🍉';
                if (item.includes('lemon')) return '🍋';
                if (item.includes('kiwi')) return '🥝';
                if (item.includes('sirsak')) return '🌿';
                if (item.includes('salad')) return '🥗';
                if (item.includes('jambu')) return '🍈';
                if (item.includes('buah')) return '🧺';
                if (item.includes('salak')) return '🌰';
                if (item.includes('pir')) return '🍐';
                if (/logistik|armada|pengiriman/.test(item)) return '🚚';
                if (/listrik|storage|pendingin/.test(item)) return '⚡';
                if (/stok|belanja|pembelian/.test(item)) return '🧺';
                if (/kemasan|plastik|packaging/.test(item)) return '📦';
                if (/jus|minuman|free/.test(item)) return '🧃';
                if (/buang|rusak|susut|sortir/.test(item)) return '♻️';
                return '🧾';
            },
            get trendChange() { return this.total - this.previousTotal; },
            get trendLabel() {
                if (this.previousTotal <= 0) return this.total <= 0 ? '0,0%' : 'Baru';
                const percent = Math.abs(this.trendChange / this.previousTotal * 100).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                return (this.trendChange > 0 ? '+' : (this.trendChange < 0 ? '−' : '')) + percent + '%';
            },
            get trendIcon() { return this.trendChange > 0 ? 'ph-caret-up' : (this.trendChange < 0 ? 'ph-caret-down' : 'ph-minus'); },
            get trendColor() {
                const favorable = this.mode === 'expense' || this.mode === 'loss' ? this.trendChange <= 0 : this.trendChange >= 0;
                return this.trendChange === 0 ? 'rgba(255,255,255,.8)' : (favorable ? (this.mode === 'revenue' ? '#d1f36e' : '#34d399') : '#fda4af');
            },
            resetSelectedDay() {
                const lastWithData = this.chart.findLastIndex(point => Number(point[this.mode] || 0) > 0);
                this.selectedIndex = todayIndex >= 0 ? todayIndex : Math.max(0, lastWithData);
            },
            selectMode(key) { if (this.metricModes.some(item => item.key === key)) { this.mode = key; this.resetSelectedDay(); } },
            startSwipe(event) {
                const point = event.touches?.[0] || event;
                this.swipeStartX = point.clientX;
                this.swipeStartY = point.clientY;
            },
            endSwipe(event) {
                if (this.swipeStartX === null) return;
                const point = event.changedTouches?.[0] || event;
                const dx = point.clientX - this.swipeStartX;
                const dy = point.clientY - this.swipeStartY;
                this.swipeStartX = null;
                if (Math.abs(dx) > 35 && Math.abs(dx) > Math.abs(dy)) {
                    const next = Math.max(0, Math.min(this.metricModes.length - 1, this.modeIndex + (dx < 0 ? 1 : -1)));
                    this.selectMode(this.metricModes[next].key);
                }
            },
            rupiah(value) { return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0)); },
            number(value) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0)); },
            formatChartDate(value) {
                if (!value) return '';
                return new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: '2-digit' }).format(new Date(value + 'T12:00:00'));
            },
            shortRupiah(value) {
                const amount = Number(value || 0);
                if (amount >= 1000000000) return 'Rp' + (amount / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
                if (amount >= 1000000) return 'Rp' + (amount / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
                if (amount >= 1000) return 'Rp' + (amount / 1000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb';
                return 'Rp' + amount.toLocaleString('id-ID');
            },
            barHeight(point) {
                const highest = Math.max(1, ...this.chart.map(item => Number(item[this.mode] || 0)));
                const amount = Number(point[this.mode] || 0);
                return amount > 0 ? Math.max(12, amount / highest * 100) : 0;
            },
        };
    }
</script>
@endpush
