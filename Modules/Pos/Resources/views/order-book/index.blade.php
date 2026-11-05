@extends('template.root')
@section('title', 'Pesanan')

@php
    $orderBookFilterBranches = $branches->map(static fn ($branch) => [
        'id' => (string) $branch->id,
        'name' => ucwords((string) $branch->name),
    ])->values()->all();
@endphp

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/order-book-filter-responsive.css') }}?v={{ filemtime(public_path('assets/css/order-book-filter-responsive.css')) }}">
@include('pos::order-book.partials.detail')
<style>
    :where(#order-book-filter-sheet, #order-book-filter-sheet *) { border-width:0; border-style:solid; border-color:#e5e7eb; }
    :where(#order-book-filter-sheet button) { padding:0; background-color:transparent; color:inherit; }
    :where(#order-book-filter-sheet input) { color:inherit; }
    #order-book-filter-sheet :is(h2, h3, h4, p) { margin-bottom:0; }
    #order-book-filter-sheet :is(h3, h4) { color:#6b7280; line-height:1.5; }
    .order-book-workspace .order-book-toolbar-controls { display:contents; }
    .order-book-workspace .order-book-mobile-search { padding:0; background:transparent; }
    @media (min-width:768px) {
        .order-book-workspace .order-book-mobile-search { display:contents; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search { display:contents; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__icon,
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__input,
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__clear,
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__divider { display:none; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__filter.order-book-filter-trigger { display:inline-flex; width:auto; min-width:44px; height:44px; min-height:44px; align-items:center; justify-content:center; flex:none; gap:8px; border:1px solid var(--erp-border); border-radius:999px; padding:10px 18px; background:white; color:#4b5563; font:600 13px/1.4 var(--erp-font); text-decoration:none; cursor:pointer; }
    }
    .order-book-workspace .order-book-record__summary { display:flex; min-width:102px; max-width:124px; flex:none; flex-direction:column; align-items:flex-end; gap:2px; margin-left:8px; text-align:right; }
    .order-book-workspace .order-book-record__status { color:#6b7280; font-size:13px; font-weight:700; line-height:18px; }
    .order-book-workspace .order-book-record__status--process { color:#f97316; }
    .order-book-workspace .order-book-record__status--done { color:#059669; }
    .order-book-workspace .order-book-record__invoice { max-width:124px; overflow-wrap:anywhere; color:#6b7280; font-size:12px; line-height:18px; }
    .order-book-workspace .order-book-record--tone-0 .erp-record__avatar { border-color:#99f6e4; background:#f0fdfa; color:#0f766e; }
    .order-book-workspace .order-book-record--tone-1 .erp-record__avatar { border-color:#fde68a; background:#fffbeb; color:#b45309; }
    .order-book-workspace .order-book-record--tone-2 .erp-record__avatar { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
    .order-book-workspace .order-book-record--tone-3 .erp-record__avatar { border-color:#99f6e4; background:#f0fdfa; color:#0f766e; }
    .order-book-workspace .order-book-record--tone-4 .erp-record__avatar { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
    .order-book-workspace .order-book-record--tone-5 .erp-record__avatar { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
    .order-book-workspace .order-book-record--tone-6 .erp-record__avatar { border-color:#e9d5ff; background:#faf5ff; color:#7e22ce; }
    .order-book-workspace .order-book-record--tone-7 .erp-record__avatar { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
    .order-book-workspace .order-book-record--tone-8 .erp-record__avatar { border-color:#c7d2fe; background:#eef2ff; color:#4338ca; }
    .order-book-workspace .order-book-record--tone-9 .erp-record__avatar { border-color:#c7d2fe; background:#eef2ff; color:#4338ca; }

    @media (max-width:767px) {
        .order-book-workspace { width:100vw; margin-inline:calc(50% - 50vw); }
        .order-book-workspace .order-book-filter-label { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
        .order-book-workspace .erp-workspace-heading { display:none; }
        .order-book-workspace .erp-workspace-toolbar .order-book-toolbar-controls { position:relative; display:flex; width:100%; min-width:0; flex:1 1 100%; align-items:center; }
        .order-book-workspace .erp-workspace-toolbar .order-book-mobile-search .erp-mobile-search { min-width:0; height:44px; }
        .order-book-workspace .order-book-create,
        .order-book-workspace .erp-record-controls { display:none; }
        .order-book-workspace .erp-record__head { min-height:60px; gap:12px; padding:9px 16px; }
        .order-book-workspace .erp-record__avatar { width:36px; height:36px; border-radius:12px; font-size:14px; font-weight:700; }
        .order-book-workspace .erp-record__title { font-size:14px; line-height:20px; }
        .order-book-workspace .erp-record__meta { margin-top:4px; font-size:12px; line-height:18px; }
        .order-book-workspace .erp-record__chevron { display:none; }
        .order-book-workspace .erp-record__details { padding:0 16px 16px; }
    }
</style>
<x-erp.workspace class="order-book-workspace" title="Pesanan" subtitle="Kelola pesanan dan lihat rincian transaksi."
    x-data="orderBookFilterSheet()"
    @keydown.escape.window="closeFilter()"><x-slot:toolbar><div class="order-book-toolbar-controls"><div class="erp-search d-none d-md-flex"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search-desktop" data-kt-ecommerce-product-filter="search" aria-label="Cari pesanan" placeholder="Cari..."></div><x-erp.mobile-search class="order-book-mobile-search erp-mobile-search-shell--fill"><x-slot:input><input type="search" id="search" data-kt-ecommerce-product-filter="search" aria-label="Cari pesanan" placeholder="Cari..." class="erp-mobile-search__input"></x-slot:input><x-slot:filter><button type="button" class="erp-mobile-search__filter order-book-filter-trigger" aria-label="Filter pesanan" aria-haspopup="dialog" aria-controls="order-book-filter-sheet" :aria-expanded="filterOpen" :class="hasActiveFilters ? 'is-active' : ''" x-ref="filterTrigger" @click="openFilter()"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="active-branch-button-label" class="order-book-filter-label">Filter</span></button></x-slot:filter></x-erp.mobile-search></div><div hidden aria-hidden="true">
                            <select id="order-book-status-filter" data-kt-ecommerce-product-filter="status">
                                <option value="all">Semua Status</option>
                                <option value="draft">Pending</option>
                                <option value="process">Proses</option>
                                <option value="done">Selesai</option>
                            </select>
                            <select id="order-book-branch-filter" data-kt-ecommerce-product-filter="cabang">
                                <option value="all">Semua Cabang</option>
                                @foreach ($orderBookFilterBranches as $branch)
                                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="kt_ecommerce_sales_flatpickr" value="">
                        </div>@if(check_access('order-book.create'))<a href="{{ url('order-book/create') }}" class="erp-action erp-action--primary order-book-create"><i class="ph-bold ph-plus" aria-hidden="true"></i>Tambah Pesanan</a>@endif</x-slot:toolbar>
<script type="application/json" id="order-book-filter-config">@json(['branches' => $orderBookFilterBranches, 'timezone' => config('app.timezone')])</script>
<x-erp.collection table="pos-table"><table class="table align-middle table-row-dashed fs-6 gy-5" id="pos-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="text-start min-w-150px">Nama Pelanggan</th>
                            <th class="text-start min-w-150px">Tanggal Proses</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table></x-erp.collection>
    <template x-teleport="body">
    <div id="order-book-filter-sheet" class="fixed inset-0 z-[1100] flex items-end justify-center p-0 sm:items-center sm:p-[16px] font-sans text-[16px] leading-normal text-[#1f2937]" x-show="filterOpen" x-cloak x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <button type="button" class="absolute inset-0 bg-black/60 backdrop-blur-sm" aria-label="Tutup filter" @click="closeFilter()"></button>

        <section class="relative z-10 flex h-auto max-h-[88vh] max-h-[88dvh] w-full max-w-[512px] flex-col overflow-hidden rounded-t-[24px] bg-white pb-[env(safe-area-inset-bottom)] shadow-2xl sm:rounded-[24px] sm:pb-0" role="dialog" aria-modal="true" aria-labelledby="order-book-filter-title" tabindex="-1" x-ref="filterPanel" @click.stop @keydown.tab="trapFilterFocus($event)" x-show="filterOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:scale-95 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:scale-95 sm:opacity-0">
            <button type="button" class="flex w-full shrink-0 justify-center pt-[12px] pb-[4px] sm:hidden" aria-label="Tutup filter" @click="closeFilter()"><span class="h-[6px] w-[64px] rounded-full bg-gray-300"></span></button>

            <header class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-[20px] py-[16px]">
                <div>
                    <h2 id="order-book-filter-title" class="text-[16px] leading-[24px] font-bold text-[#1f2937] sm:text-[18px] sm:leading-[28px]">Filter Pesanan</h2>
                    <p class="mt-[2px] text-[11px] text-[#9ca3af]">Saring cabang, status, dan periode tanggal pesanan</p>
                </div>
                <button type="button" class="flex h-[32px] w-[32px] items-center justify-center rounded-full bg-gray-100 text-[#6b7280] transition-colors hover:bg-gray-200" aria-label="Tutup filter" x-ref="filterCloseButton" @click="closeFilter()"><i class="ph-bold ph-x text-[14px] leading-[20px]" aria-hidden="true"></i></button>
            </header>

            <div class="min-h-0 flex-1 space-y-[16px] overflow-y-auto overscroll-none p-[20px]">
                <div x-show="!calOpen" class="space-y-[16px]">
                    <section>
                        <div class="mb-[6px] flex items-center justify-between">
                            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[#6b7280]">Cabang Tujuan</h3>
                            <button type="button" x-show="draftBranch !== 'all'" @click="draftBranch = 'all'" class="text-[10px] font-semibold text-red-500 transition-colors hover:text-red-600">Reset</button>
                        </div>
                        <div class="flex flex-col overflow-hidden rounded-[12px] border-[1px] border-solid border-gray-200 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                            <div class="relative shrink-0 border-b border-gray-100">
                                <i class="ph-bold ph-magnifying-glass absolute left-[12px] top-1/2 -translate-y-1/2 text-[12px] leading-[16px] text-[#9ca3af]" aria-hidden="true"></i>
                                <input type="search" x-model="branchSearchQuery" placeholder="Cari cabang..." aria-label="Cari cabang tujuan" autocomplete="off" class="w-full border-0 bg-transparent py-[8px] pl-[32px] pr-[12px] text-[16px] leading-[16px] outline-none transition-colors focus:bg-gray-50">
                            </div>
                            <div class="max-h-[128px] divide-y divide-gray-100/60 overflow-y-auto overscroll-none bg-gray-50/30">
                                <label class="flex cursor-pointer items-center gap-[12px] px-[14px] py-[8px] transition-colors hover:bg-gray-50">
                                    <span class="relative flex h-[16px] w-[16px] shrink-0 items-center justify-center">
                                        <input type="radio" name="orderBookDraftBranch" value="all" x-model="draftBranch" class="peer h-[16px] w-[16px] appearance-none rounded-full border-[2px] border-solid border-gray-300 transition-colors checked:border-emerald-500">
                                        <span class="absolute h-[8px] w-[8px] scale-0 rounded-full bg-emerald-500 transition-transform peer-checked:scale-100"></span>
                                    </span>
                                    <span class="text-[12px] leading-[16px]" :class="draftBranch === 'all' ? 'font-bold text-[#111827]' : 'font-medium text-[#374151]'">Semua Cabang</span>
                                </label>
                                <template x-for="branch in filteredBranches" :key="branch.id">
                                    <label class="flex cursor-pointer items-center gap-[12px] px-[14px] py-[8px] transition-colors hover:bg-gray-50">
                                        <span class="relative flex h-[16px] w-[16px] shrink-0 items-center justify-center">
                                            <input type="radio" name="orderBookDraftBranch" :value="branch.id" x-model="draftBranch" class="peer h-[16px] w-[16px] appearance-none rounded-full border-[2px] border-solid border-gray-300 transition-colors checked:border-emerald-500">
                                            <span class="absolute h-[8px] w-[8px] scale-0 rounded-full bg-emerald-500 transition-transform peer-checked:scale-100"></span>
                                        </span>
                                        <span class="text-[12px] leading-[16px]" :class="draftBranch === branch.id ? 'font-bold text-[#111827]' : 'font-medium text-[#374151]'" x-text="branch.name"></span>
                                    </label>
                                </template>
                                <div x-show="filteredBranches.length === 0" class="py-[12px] text-center text-[12px] leading-[16px] text-[#9ca3af]">Cabang tidak ditemukan</div>
                            </div>
                        </div>
                    </section>

                    <section>
                        <div class="mb-[6px] flex items-center justify-between">
                            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[#6b7280]">Status Pesanan</h3>
                            <button type="button" x-show="draftStatus !== 'all'" @click="draftStatus = 'all'" class="text-[10px] font-semibold text-red-500 transition-colors hover:text-red-600">Reset</button>
                        </div>
                        <div class="mt-[4px] flex flex-wrap gap-[8px]">
                            <template x-for="status in statusOptions" :key="status.value">
                                <button type="button" @click="draftStatus = status.value" :aria-pressed="draftStatus === status.value" class="cursor-pointer rounded-full border-[1px] border-solid px-[12px] py-[6px] text-[12px] leading-[16px] transition-colors" :class="draftStatus === status.value ? 'border-emerald-300 bg-emerald-50 font-semibold text-emerald-700 shadow-[0_1px_2px_rgba(0,0,0,0.05)]' : 'border-gray-200 bg-white font-medium text-[#4b5563] hover:border-emerald-300'" x-text="status.label"></button>
                            </template>
                        </div>
                    </section>

                    <section>
                        <div class="mb-[6px] flex items-center justify-between">
                            <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[#6b7280]">Periode Tanggal</h3>
                            <button type="button" x-show="draftStartDate || draftEndDate" @click="draftStartDate = ''; draftEndDate = ''" class="text-[10px] font-semibold text-red-500 transition-colors hover:text-red-600">Reset</button>
                        </div>
                        <div class="flex w-full flex-col gap-[8px] sm:flex-row">
                            <button type="button" aria-label="Tanggal mulai" :aria-expanded="calOpen" x-ref="dateRangeStart" @click="toggleCalendar('start')" class="flex flex-1 cursor-pointer items-center gap-[8px] rounded-[12px] border-[1px] border-solid bg-white px-[12px] py-[10px] text-left text-[12px] leading-[16px] text-[#374151] shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-colors" :class="calOpen ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-gray-200 hover:border-emerald-400'">
                                <span class="w-[32px] text-[10px] font-bold text-[#9ca3af]">DARI</span>
                                <span class="text-[12px] leading-[16px] font-medium" :class="draftStartDate ? 'text-[#1f2937]' : 'text-[#9ca3af]'" x-text="draftStartDate ? formatDisplayDate(draftStartDate) : 'Pilih Tanggal'"></span>
                            </button>
                            <button type="button" aria-label="Tanggal akhir" :aria-expanded="calOpen" x-ref="dateRangeEnd" @click="toggleCalendar('end')" class="flex flex-1 cursor-pointer items-center gap-[8px] rounded-[12px] border-[1px] border-solid bg-white px-[12px] py-[10px] text-left text-[12px] leading-[16px] text-[#374151] shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-colors" :class="calOpen ? 'border-emerald-500 ring-1 ring-emerald-500' : 'border-gray-200 hover:border-emerald-400'">
                                <span class="w-[32px] text-[10px] font-bold text-[#9ca3af]">KE</span>
                                <span class="text-[12px] leading-[16px] font-medium" :class="draftEndDate ? 'text-[#1f2937]' : 'text-[#9ca3af]'" x-text="draftEndDate ? formatDisplayDate(draftEndDate) : 'Pilih Tanggal'"></span>
                            </button>
                        </div>
                        <div class="flex flex-col gap-[8px] pt-[8px]">
                            <h4 class="text-[10px] font-semibold uppercase tracking-wider text-[#6b7280]">Pilihan Cepat</h4>
                            <div class="flex gap-[8px]">
                                <button type="button" @click="setDraftWeek()" class="flex flex-1 cursor-pointer items-center justify-center rounded-[12px] px-[8px] py-[10px] text-[12px] leading-[16px] font-semibold text-[#111827] shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-colors active:scale-95" style="background-color:#c5f037" title="Tampilkan Minggu Ini">Minggu Ini</button>
                                <button type="button" @click="setDraftToday()" class="flex flex-1 cursor-pointer items-center justify-center rounded-[12px] border-[1px] border-solid border-gray-200 bg-white px-[8px] py-[10px] text-[12px] leading-[16px] font-semibold text-[#374151] shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-colors hover:border-gray-300 hover:bg-gray-50 active:scale-95" title="Tampilkan Hari Ini">Hari Ini</button>
                            </div>
                        </div>
                    </section>
                </div>

                <section x-show="calOpen" x-cloak class="w-full rounded-[16px] border-[1px] border-solid border-gray-100 bg-white p-[16px] shadow-[0_1px_2px_rgba(0,0,0,0.05)]">
                    <div class="mb-[16px] flex items-center justify-between border-b border-gray-100 pb-[8px]">
                        <button type="button" x-ref="calendarHeadingButton" @click="toggleCalendarView()" class="flex items-center gap-[6px] text-[14px] leading-[20px] font-bold text-[#1f2937] transition hover:text-emerald-600" :aria-label="'Tampilan kalender ' + calendarHeading">
                            <span x-text="calendarHeading"></span><i class="ph-bold ph-caret-down text-[12px] leading-[16px] text-[#9ca3af]" aria-hidden="true"></i>
                        </button>
                        <div class="flex gap-[6px]">
                            <button type="button" @click="navigateCalendar(-1)" class="flex h-[32px] w-[32px] items-center justify-center rounded-[8px] bg-gray-50 text-[#4b5563] transition hover:bg-gray-100" aria-label="Bulan sebelumnya"><i class="ph-bold ph-caret-left text-[12px] leading-[16px]" aria-hidden="true"></i></button>
                            <button type="button" @click="navigateCalendar(1)" class="flex h-[32px] w-[32px] items-center justify-center rounded-[8px] bg-gray-50 text-[#4b5563] transition hover:bg-gray-100" aria-label="Bulan berikutnya"><i class="ph-bold ph-caret-right text-[12px] leading-[16px]" aria-hidden="true"></i></button>
                        </div>
                    </div>

                    <div x-show="calendarView === 'date'">
                        <div class="mb-[8px] grid grid-cols-7 gap-[4px] text-center text-[11px] font-bold text-[#9ca3af]"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div>
                        <div class="grid min-h-[228px] grid-cols-7 items-center gap-y-[4px] text-center">
                            <template x-for="blankDay in blankDays" :key="'blank-' + blankDay"><span class="mx-auto h-[32px] w-[32px]"></span></template>
                            <template x-for="day in calendarDays" :key="day">
                                <div class="relative flex h-[36px] w-full items-center justify-center">
                                    <span x-show="draftStartDate && draftEndDate && draftStartDate !== draftEndDate" class="pointer-events-none absolute bottom-0 top-0 transition-all" :class="{ 'left-1/2 right-0': getDateString(day) === draftStartDate, 'left-0 right-1/2': getDateString(day) === draftEndDate, 'left-0 right-0': isDateInRange(day), 'hidden': !isDateInRange(day) && getDateString(day) !== draftStartDate && getDateString(day) !== draftEndDate }" style="background-color:rgba(197,240,55,.28)"></span>
                                    <button type="button" @click="selectDate(day)" class="relative z-10 flex h-[32px] w-[32px] cursor-pointer items-center justify-center rounded-full text-[12px] leading-[16px] transition-all" :class="{ 'font-bold text-[#111827] shadow-[0_1px_2px_rgba(0,0,0,0.05)]': isDateSelected(day), 'font-semibold text-[#111827]': isDateInRange(day) && !isDateSelected(day), 'text-[#374151] hover:bg-gray-100': !isDateSelected(day) && !isDateInRange(day) }" :style="isDateSelected(day) ? 'background-color:#c5f037' : ''" :aria-pressed="isDateSelected(day)" :aria-label="formatDisplayDate(getDateString(day))" x-text="day"></button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="calendarView === 'month'" x-cloak class="mt-[8px] grid grid-cols-3 gap-[8px]">
                        <template x-for="(month, index) in monthNames" :key="month">
                            <button type="button" @click="selectCalendarMonth(index)" class="rounded-[12px] py-[10px] text-[12px] leading-[16px] transition-colors" :class="currentMonth === index ? 'font-bold text-[#111827] shadow-[0_1px_2px_rgba(0,0,0,0.05)]' : 'font-medium text-[#374151] hover:bg-gray-50'" :style="currentMonth === index ? 'background-color:#c5f037' : ''" x-text="month"></button>
                        </template>
                    </div>
                    <div x-show="calendarView === 'year'" x-cloak class="mt-[8px] grid grid-cols-3 gap-[8px]">
                        <template x-for="year in calendarYears" :key="year">
                            <button type="button" @click="selectCalendarYear(year)" class="rounded-[12px] py-[10px] text-[12px] leading-[16px] transition-colors" :class="currentYear === year ? 'font-bold text-[#111827] shadow-[0_1px_2px_rgba(0,0,0,0.05)]' : 'font-medium text-[#374151] hover:bg-gray-50'" :style="currentYear === year ? 'background-color:#c5f037' : ''" x-text="year"></button>
                        </template>
                    </div>
                    <button type="button" @click="closeCalendar()" class="mt-[16px] flex w-full cursor-pointer items-center justify-center gap-[8px] rounded-[12px] bg-gray-50 py-[10px] text-[12px] leading-[16px] font-bold text-[#374151] transition-colors hover:bg-gray-100"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i>Kembali</button>
                </section>
            </div>

            <footer class="flex shrink-0 items-center gap-[12px] border-t border-gray-100 bg-gray-50/70 px-[20px] py-[14px]">
                <button type="button" @click="resetFilters()" class="h-[44px] flex-1 cursor-pointer rounded-[12px] border-[1px] border-solid border-gray-200 bg-white text-[12px] leading-[16px] font-semibold text-[#4b5563] shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-colors hover:bg-gray-50 active:scale-95 sm:text-[14px] sm:leading-[20px]">Reset Filter</button>
                <button type="button" @click="applyFilters()" class="h-[44px] flex-1 cursor-pointer rounded-[12px] bg-[#0b595b] text-[12px] leading-[16px] font-semibold text-[#ffffff] shadow-[0_1px_2px_rgba(0,0,0,0.05)] transition-colors hover:bg-[#08484a] active:scale-95 sm:text-[14px] sm:leading-[20px]">Terapkan Filter</button>
            </footer>
        </section>
    </div>
    </template>
</x-erp.workspace>

@section('script')
    <script type="text/javascript">
        var dataTable;
        const orderBookReferenceUi = (() => {
            const months = {
                jan: 'Jan', feb: 'Feb', mar: 'Mar', apr: 'Apr', may: 'Mei', jun: 'Jun',
                jul: 'Jul', aug: 'Agu', sep: 'Sep', oct: 'Okt', nov: 'Nov', dec: 'Des'
            };

            function initials(name) {
                const words = String(name || '').replace(/\s*\([^)]*\)\s*$/, '').match(/[A-Za-z0-9À-ÖØ-öø-ÿ]+/g) || [];
                if (words.length > 1) return words.slice(0, 2).map(word => Array.from(word)[0]).join('').toUpperCase();
                return Array.from(words[0] || '').slice(0, 2).join('').toUpperCase() || '—';
            }

            function formatDate(value) {
                return String(value || '').trim()
                    .replace(/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/gi, month => months[month.toLowerCase()] || month)
                    .replace(/,?\s+(\d{2}:\d{2})$/, ', $1');
            }

            function status(value) {
                const label = String(value || '').replace(/<[^>]*>/g, '').trim();
                const normalized = label.toLowerCase();
                return ({
                    pending: 'Pending', process: 'Proses', posting: 'Proses',
                    proses: 'Proses', done: 'Selesai', selesai: 'Selesai',
                    draft: 'Draft', temp: 'Pending'
                })[normalized] || label || 'Pending';
            }

            function invoice(statusLabel, pos, fallback) {
                if (statusLabel === 'Pending' || statusLabel === 'Draft') return '-';
                const posNumber = pos && typeof pos === 'object' ? String(pos.invoice_number || '').trim() : '';
                const fallbackNumber = String(fallback || '').trim();
                return posNumber || fallbackNumber || '-';
            }

            return { initials, formatDate, status, invoice };
        })();

        const orderBookFilterUi = (() => {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            function today(timeZone, now = new Date()) {
                const parts = new Intl.DateTimeFormat('en-CA', {
                    timeZone,
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                }).formatToParts(now).reduce((date, part) => {
                    if (part.type !== 'literal') date[part.type] = part.value;
                    return date;
                }, {});

                return `${parts.year}-${parts.month}-${parts.day}`;
            }

            function toIsoDate(date) {
                return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}-${String(date.getUTCDate()).padStart(2, '0')}`;
            }

            function week(timeZone, now = new Date()) {
                const [year, month, day] = today(timeZone, now).split('-').map(Number);
                const monday = new Date(Date.UTC(year, month - 1, day));
                const weekday = monday.getUTCDay() || 7;
                monday.setUTCDate(monday.getUTCDate() - weekday + 1);

                const sunday = new Date(monday.getTime());
                sunday.setUTCDate(sunday.getUTCDate() + 6);

                return { start: toIsoDate(monday), end: toIsoDate(sunday) };
            }

            function formatDisplayDate(value) {
                const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (!match) return '';
                return `${Number(match[3])} ${months[Number(match[2]) - 1] || ''} ${match[1]}`;
            }

            function monthGridOffset(year, month) {
                return (new Date(Date.UTC(year, month, 1)).getUTCDay() + 6) % 7;
            }

            function daysInMonth(year, month) {
                return new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
            }

            return { today, week, formatDisplayDate, monthGridOffset, daysInMonth };
        })();

        function orderBookFilterSheet() {
            let config = {};
            try {
                config = JSON.parse(document.getElementById('order-book-filter-config')?.textContent || '{}');
            } catch (error) {}

            return {
                branches: Array.isArray(config.branches) ? config.branches : [],
                timezone: config.timezone || 'Asia/Jakarta',
                statusOptions: [
                    { value: 'all', label: 'Semua Status' },
                    { value: 'draft', label: 'Pending' },
                    { value: 'process', label: 'Proses' },
                    { value: 'done', label: 'Selesai' }
                ],
                monthNames: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                filterOpen: false,
                calOpen: false,
                calendarView: 'date',
                currentMonth: 0,
                currentYear: 0,
                calendarReturnRef: 'dateRangeStart',
                draftStatus: 'all',
                draftBranch: 'all',
                branchSearchQuery: '',
                draftStartDate: '',
                draftEndDate: '',
                hasActiveFilters: false,
                previousBodyOverflow: '',
                previousMainOverflow: '',

                init() {
                    this.syncAppliedFilters();
                },

                get filteredBranches() {
                    const query = this.branchSearchQuery.trim().toLowerCase();
                    return this.branches.filter(branch => String(branch.name || '').toLowerCase().includes(query));
                },

                get calendarHeading() {
                    if (this.calendarView === 'month') return String(this.currentYear);
                    if (this.calendarView === 'year') return `${this.currentYear - 4} - ${this.currentYear + 7}`;
                    return `${this.monthNames[this.currentMonth]} ${this.currentYear}`;
                },

                get blankDays() {
                    return Array.from({ length: orderBookFilterUi.monthGridOffset(this.currentYear, this.currentMonth) }, (_, index) => index);
                },

                get calendarDays() {
                    return Array.from({ length: orderBookFilterUi.daysInMonth(this.currentYear, this.currentMonth) }, (_, index) => index + 1);
                },

                get calendarYears() {
                    return Array.from({ length: 12 }, (_, index) => this.currentYear - 4 + index);
                },

                syncAppliedFilters() {
                    const status = document.querySelector('[data-kt-ecommerce-product-filter="status"]');
                    const branch = document.querySelector('[data-kt-ecommerce-product-filter="cabang"]');
                    const range = document.getElementById('kt_ecommerce_sales_flatpickr');
                    const dates = String(range?.value || '').split(' to ');

                    this.draftStatus = status?.value || 'all';
                    this.draftBranch = branch?.value || 'all';
                    this.draftStartDate = dates[0] || '';
                    this.draftEndDate = dates[1] || dates[0] || '';
                    this.hasActiveFilters = this.draftStatus !== 'all' || this.draftBranch !== 'all' || Boolean(this.draftStartDate || this.draftEndDate);
                },

                openFilter() {
                    this.syncAppliedFilters();
                    this.branchSearchQuery = '';
                    this.calOpen = false;
                    this.calendarView = 'date';
                    this.previousBodyOverflow = document.body.style.overflow;
                    const main = document.querySelector('.erp-main');
                    this.previousMainOverflow = main?.style.overflow || '';
                    document.body.style.overflow = 'hidden';
                    if (main) main.style.overflow = 'hidden';
                    this.filterOpen = true;
                    this.$nextTick(() => this.$refs.filterCloseButton?.focus());
                },

                closeFilter() {
                    if (!this.filterOpen) return;
                    this.filterOpen = false;
                    this.calOpen = false;
                    document.body.style.overflow = this.previousBodyOverflow;
                    const main = document.querySelector('.erp-main');
                    if (main) main.style.overflow = this.previousMainOverflow;
                    this.$nextTick(() => this.$refs.filterTrigger?.focus());
                },

                trapFilterFocus(event) {
                    const focusable = Array.from(this.$refs.filterPanel.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'))
                        .filter(element => element.getClientRects().length > 0);
                    if (!focusable.length) {
                        event.preventDefault();
                        this.$refs.filterPanel.focus();
                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },

                focusFilterControl(ref) {
                    this.$nextTick(() => requestAnimationFrame(() => this.$refs[ref]?.focus()));
                },

                toggleCalendar(trigger = 'start') {
                    if (this.calOpen) {
                        this.closeCalendar();
                        return;
                    }

                    this.calendarReturnRef = trigger === 'end' ? 'dateRangeEnd' : 'dateRangeStart';
                    const initialDate = this.draftStartDate || this.draftEndDate || orderBookFilterUi.today(this.timezone);
                    const [year, month] = initialDate.split('-').map(Number);
                    this.currentYear = year;
                    this.currentMonth = month - 1;
                    this.calendarView = 'date';
                    this.calOpen = true;
                    this.focusFilterControl('calendarHeadingButton');
                },

                closeCalendar() {
                    this.calOpen = false;
                    this.focusFilterControl(this.calendarReturnRef);
                },

                toggleCalendarView() {
                    this.calendarView = this.calendarView === 'date' ? 'month' : this.calendarView === 'month' ? 'year' : 'date';
                },

                navigateCalendar(step) {
                    if (this.calendarView === 'date') {
                        const nextMonth = new Date(Date.UTC(this.currentYear, this.currentMonth + step, 1));
                        this.currentYear = nextMonth.getUTCFullYear();
                        this.currentMonth = nextMonth.getUTCMonth();
                    } else if (this.calendarView === 'month') {
                        this.currentYear += step;
                    } else {
                        this.currentYear += step * 12;
                    }
                },

                selectCalendarYear(year) {
                    this.currentYear = year;
                    this.calendarView = 'month';
                    this.focusFilterControl('calendarHeadingButton');
                },

                selectCalendarMonth(month) {
                    this.currentMonth = month;
                    this.calendarView = 'date';
                    this.focusFilterControl('calendarHeadingButton');
                },

                getDateString(day) {
                    return `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                },

                formatDisplayDate(value) {
                    return orderBookFilterUi.formatDisplayDate(value);
                },

                selectDate(day) {
                    const selectedDate = this.getDateString(day);
                    if (!this.draftStartDate || this.draftEndDate) {
                        this.draftStartDate = selectedDate;
                        this.draftEndDate = '';
                    } else if (selectedDate < this.draftStartDate) {
                        this.draftEndDate = this.draftStartDate;
                        this.draftStartDate = selectedDate;
                    } else {
                        this.draftEndDate = selectedDate;
                    }
                },

                isDateSelected(day) {
                    const value = this.getDateString(day);
                    return value === this.draftStartDate || value === this.draftEndDate;
                },

                isDateInRange(day) {
                    if (!this.draftStartDate || !this.draftEndDate) return false;
                    const value = this.getDateString(day);
                    return value > this.draftStartDate && value < this.draftEndDate;
                },

                setDraftToday() {
                    this.draftStartDate = orderBookFilterUi.today(this.timezone);
                    this.draftEndDate = this.draftStartDate;
                    const [year, month] = this.draftStartDate.split('-').map(Number);
                    this.currentYear = year;
                    this.currentMonth = month - 1;
                    this.calendarView = 'date';
                },

                setDraftWeek() {
                    const range = orderBookFilterUi.week(this.timezone);
                    this.draftStartDate = range.start;
                    this.draftEndDate = range.end;
                    const [year, month] = range.start.split('-').map(Number);
                    this.currentYear = year;
                    this.currentMonth = month - 1;
                    this.calendarView = 'date';
                },

                applyFilters() {
                    const status = document.querySelector('[data-kt-ecommerce-product-filter="status"]');
                    const branch = document.querySelector('[data-kt-ecommerce-product-filter="cabang"]');
                    const range = document.getElementById('kt_ecommerce_sales_flatpickr');
                    const startDate = this.draftStartDate;
                    const endDate = startDate ? (this.draftEndDate || startDate) : '';

                    if (status) status.value = this.draftStatus;
                    if (branch) branch.value = this.draftBranch;
                    if (range) range.value = startDate ? `${startDate} to ${endDate}` : '';
                    this.draftEndDate = endDate;
                    this.hasActiveFilters = this.draftStatus !== 'all' || this.draftBranch !== 'all' || Boolean(startDate);
                    document.dispatchEvent(new CustomEvent('order-book:filters-applied'));
                    this.closeFilter();
                },

                resetFilters() {
                    this.draftStatus = 'all';
                    this.draftBranch = 'all';
                    this.draftStartDate = '';
                    this.draftEndDate = '';
                    this.applyFilters();
                }
            };
        }

        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="cabang"]');
            const $activeBranchButtonLabel = $('#active-branch-button-label');

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            dataTable = $('#pos-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                columnDefs: [{
                    orderable: false,
                    targets: -1 // Disable sorting for action column
                }, ],
                ajax: {
                    url: "{{ route('order-book.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.status_filter = $('[data-kt-ecommerce-product-filter="status"]').val();
                        d.cabang_filter = $('[data-kt-ecommerce-product-filter="cabang"]').val();
                        var range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            var dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0]; // jika hanya pilih 1 tanggal
                        }
                    }
                },
                order: [
                    [1, 'desc'], // Then by order_date ASC (kolom ke-3)
                ],
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ]
            });
            // Search manual lewat input
            const $searchInputs = $('[data-kt-ecommerce-product-filter="search"]');
            $searchInputs.on('input', function() {
                $searchInputs.not(this).val(this.value);
                dataTable.search(this.value).draw();
            });

            $('[data-kt-ecommerce-product-filter="status"]').on('change', function() {
                dataTable.draw(); // trigger fetch ulang dari server
            });

            $('[data-kt-ecommerce-product-filter="cabang"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw(); // trigger fetch ulang dari server
            });

            document.addEventListener('order-book:filters-applied', function() {
                updateActiveFilterInfo();
                dataTable.draw();
            });

            updateActiveFilterInfo();

            const orderBookCollection = document.querySelector('[data-erp-collection="pos-table"]');
            let orderBookRecordList = null;
            let orderBookListObserver = null;
            let orderBookRenderScheduled = false;

            function decorateOrderBookRecords() {
                if (!orderBookRecordList) return;

                const rows = Array.from(document.querySelectorAll('#pos-table tbody tr'))
                    .filter(row => !row.classList.contains('child') && row.cells.length > 1 && row.style.display !== 'none');
                const records = Array.from(orderBookRecordList.querySelectorAll(':scope > article.erp-record'));

                records.forEach((record, index) => {
                    const row = rows[index];
                    const title = record.querySelector('.erp-record__title');
                    const avatar = record.querySelector('.erp-record__avatar');
                    const meta = record.querySelector('.erp-record__meta');
                    const head = record.querySelector('.erp-record__head');
                    if (!row || !title || !avatar || !meta || !head) return;

                    const dateCell = row.cells[1];
                    const date = dateCell.querySelector('.text-muted')?.textContent.trim() || '';
                    const statusText = dateCell.querySelector('.badge')?.textContent.trim() || '';
                    const rowData = dataTable.row(row).data() || {};
                    const customerName = title.textContent.trim();
                    const formattedDate = orderBookReferenceUi.formatDate(date);
                    const status = orderBookReferenceUi.status(statusText || rowData.status);
                    const invoice = orderBookReferenceUi.invoice(status, rowData.pos, rowData.invoice_number);
                    const signature = JSON.stringify([index, customerName, formattedDate, status, invoice]);
                    if (record.dataset.orderBookSignature === signature) return;
                    record.dataset.orderBookSignature = signature;
                    record.classList.add('order-book-record', `order-book-record--tone-${index % 10}`);
                    avatar.textContent = orderBookReferenceUi.initials(customerName);
                    avatar.setAttribute('aria-hidden', 'true');
                    meta.textContent = formattedDate;

                    let summary = head.querySelector(':scope > .order-book-record__summary');
                    if (!summary) {
                        summary = document.createElement('span');
                        summary.className = 'order-book-record__summary';
                        const statusValue = document.createElement('span');
                        statusValue.className = 'order-book-record__status';
                        const invoiceValue = document.createElement('span');
                        invoiceValue.className = 'order-book-record__invoice';
                        summary.append(statusValue, invoiceValue);
                        head.append(summary);
                    }
                    const statusNode = summary.querySelector('.order-book-record__status');
                    const invoiceNode = summary.querySelector('.order-book-record__invoice');
                    statusNode.className = `order-book-record__status order-book-record__status--${status === 'Selesai' ? 'done' : status === 'Proses' ? 'process' : 'pending'}`;
                    statusNode.textContent = status;
                    invoiceNode.textContent = invoice;
                    head.setAttribute('aria-label', `Pesanan ${customerName}. ${formattedDate}. Status ${status}. Nomor nota ${invoice}. Buka detail dan aksi.`);
                });
            }

            function scheduleOrderBookRecords() {
                if (orderBookRenderScheduled) return;
                orderBookRenderScheduled = true;
                window.requestAnimationFrame(() => {
                    orderBookRenderScheduled = false;
                    decorateOrderBookRecords();
                });
            }

            if (orderBookCollection) {
                const observeOrderBookList = () => {
                    const nextList = orderBookCollection.querySelector(':scope > .erp-record-list');
                    if (!nextList || nextList === orderBookRecordList) return;
                    orderBookListObserver?.disconnect();
                    orderBookRecordList = nextList;
                    orderBookListObserver = new MutationObserver(scheduleOrderBookRecords);
                    orderBookListObserver.observe(orderBookRecordList, { childList: true });
                    scheduleOrderBookRecords();
                };

                new MutationObserver(observeOrderBookList).observe(orderBookCollection, { childList: true });
                observeOrderBookList();
                $('#pos-table').on('draw.dt', scheduleOrderBookRecords);
            }
        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }

        function setSelesai(id) {
            Swal.fire({
                title: 'Selesaikan Pesanan?',
                text: 'Data yang dirubah tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, selesaikan!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/other-book/set-selesai/${id}`, // Ganti dengan URL yang sesuai
                        type: 'PUT',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil diselesaikan.',
                                showConfirmButton: false,
                                timer: 1500
                            });

                            // Reload DataTable setelah berhasil selesaikan data
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat selesaikan data.'
                            });
                        }
                    });
                }
            });
        }

        function deleteProduct(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang dihapus tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/order-book/${id}`, // Ganti dengan URL yang sesuai
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil dihapus.',
                                showConfirmButton: false,
                                timer: 1500
                            });

                            // Reload DataTable setelah berhasil menghapus data
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menghapus data.'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection
@endsection
