@extends('template.root')
@section('title', 'Kasir / POS')

@section('content')
@php
    $posBranches = collect($branches)->map(fn ($branch) => ['id' => (string) $branch->id, 'name' => ucwords($branch->name)])->values();
    $posFilterConfig = [
        'branches' => $posBranches,
        'defaultBranch' => $posBranches->count() === 1 ? $posBranches->first()['id'] : 'all',
        'timezone' => config('app.timezone', 'Asia/Jakarta'),
        'step' => 50,
    ];
    $posStatuses = ['all' => 'Semua Status', 'draft' => 'Pending', 'unpaid' => 'Belum Bayar', 'partial' => 'Kurang Bayar', 'paid' => 'Lunas'];
@endphp
<style>
    .pos-list-row { display:flex; min-width:0; align-items:center; gap:12px; }
    .pos-list-avatar-wrap { position:relative; flex:none; }
    .pos-list-avatar { display:flex; width:36px; height:36px; align-items:center; justify-content:center; border:1px solid #a7f3d0; border-radius:12px; background:#ecfdf5; color:#047857; font-size:12px; font-weight:700; line-height:1; }
    .pos-list-avatar.is-theme-1 { border-color:#bfdbfe; background:#eff6ff; color:#1d4ed8; }
    .pos-list-avatar.is-theme-2 { border-color:#e9d5ff; background:#faf5ff; color:#7e22ce; }
    .pos-list-avatar.is-theme-3 { border-color:#fde68a; background:#fffbeb; color:#b45309; }
    .pos-list-avatar.is-theme-4 { border-color:#99f6e4; background:#f0fdfa; color:#0f766e; }
    .pos-list-avatar.is-theme-5 { border-color:#fecdd3; background:#fff1f2; color:#be123c; }
    .pos-list-avatar.is-theme-6 { border-color:#c7d2fe; background:#eef2ff; color:#4338ca; }
    .pos-list-parcel-badge { position:absolute; right:-4px; bottom:-4px; display:flex; width:18px; height:18px; align-items:center; justify-content:center; border-radius:999px; background:#fff; box-shadow:0 0 0 1px #fff, 0 1px 2px rgba(0,0,0,.12); }
    .pos-list-parcel-badge i { display:flex; width:14px; height:14px; align-items:center; justify-content:center; border-radius:999px; background:#d97706; color:#fff; font-size:9px; }
    .pos-list-copy { display:flex; min-width:0; flex-direction:column; gap:2px; text-decoration:none; }
    .pos-list-customer { display:flex; min-width:0; align-items:baseline; gap:4px; color:#111827; font-size:14px; font-weight:600; }
    .pos-list-customer span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pos-list-customer .pos-list-phone { flex:none; color:#6b7280; font-size:12px; font-weight:400; }
    .pos-list-copy:hover .pos-list-customer { color:#0b595b; }
    .pos-list-sub { color:#6b7280; font-size:12px; font-variant-numeric:tabular-nums; }
    .pos-list-status-cell { display:flex; flex-direction:column; align-items:flex-end; gap:3px; font-variant-numeric:tabular-nums; }
    .pos-list-status { display:inline-flex; align-items:center; font-size:12px; font-weight:700; }
    .pos-list-status.is-paid { color:#047857; }
    .pos-list-status.is-pending { color:#6b7280; }
    .pos-list-status.is-unpaid { color:#be123c; }
    .pos-list-status.is-partial { color:#b45309; }
    .pos-list-status.is-canceled { color:#374151; }
    .pos-list-status.is-other { color:#b45309; }
    .pos-list-total { display:inline-flex; align-items:baseline; gap:2px; color:#111827; font-size:13px; white-space:nowrap; }
    .pos-list-total strong { font-weight:700; }
    .pos-list-paid { color:#1f2937; font-weight:500; }
    .pos-list-sep { margin:0 2px; color:#d1d5db; }
    .pos-list-meta { color:#6b7280; font-size:11px; }
    #pos-table thead th { color:#6b7280; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
    #pos-table tbody tr.is-openable { cursor:pointer; }
    #pos-table :is(thead th, tbody td):first-child { padding-left:20px; }
    #pos-table :is(thead th, tbody td):last-child { padding-right:20px; }
    #pos-table_wrapper :is(.dataTables_info, .dataTables_paginate, .dataTables_length, .dataTables_filter) { display:none !important; }
    .pos-list-more { padding:16px 20px 24px; }
    .pos-list-empty { display:flex; min-height:260px; flex-direction:column; align-items:center; justify-content:center; gap:8px; text-align:center; }
    .pos-list-empty i { display:flex; width:56px; height:56px; align-items:center; justify-content:center; border:1px solid #d1fae5; border-radius:20px; background:#ecfdf5; color:#0b595b; font-size:26px; }
    .pos-list-empty strong { color:#1f2937; font-size:14px; }
    .pos-list-empty span { max-width:280px; color:#6b7280; font-size:12px; line-height:1.55; }
    .pos-list-empty button { margin-top:6px; border:1px solid #d1d5db; border-radius:999px; background:#fff; padding:8px 16px; color:#374151; font-size:12px; font-weight:600; }
    .pos-list-empty button:hover { border-color:#34a684; color:#0b595b; }
    .pos-toolbar-search { min-width:0; flex:1; padding:0; }
    .pos-toolbar-search .erp-mobile-search { height:44px; box-shadow:none; }
    .pos-toolbar-search .erp-mobile-search:not(:focus-within) { border-color:#868e98; }
    /* The POS filter follows the Stock Opname sheet: centered on desktop, anchored to the bottom on mobile. */
    .pos-filter-panel { width:calc(100% - 32px); max-width:512px; max-height:92dvh; border-radius:24px; }
    .pos-filter-handle { display:none; }
    @media (max-width:767px) {
        .pos-filter-overlay { align-items:flex-end; }
        .pos-filter-panel { width:100%; max-width:100%; max-height:92dvh; border-radius:24px 24px 0 0; }
        .pos-filter-handle { display:flex; flex:none; justify-content:center; padding:12px 0 4px; }
        .pos-filter-handle span { width:56px; height:6px; border-radius:999px; background:#d1d5db; }
        .pos-filter-panel .erp-filter-sheet__header { padding:16px 20px; }
    }
    .pos-filter-panel .erp-filter-sheet__body { min-width:0; padding:20px; }
    .pos-filter-main { display:flex; flex-direction:column; gap:16px; }
    .pos-filter-section { min-width:0; margin:0; padding:0; border:0; }
    .pos-filter-heading { display:flex; width:100%; align-items:center; justify-content:space-between; gap:8px; float:none; padding:0; margin:0 0 8px; color:#6b7280; font-size:10px; line-height:16px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; }
    .pos-filter-reset { border:0; padding:0; background:transparent; color:#dc2626; font-size:10px; font-weight:600; text-transform:none; letter-spacing:0; cursor:pointer; }
    .pos-filter-branch-picker { overflow:hidden; border:1px solid #e5e7eb; border-radius:12px; background:#fff; }
    .pos-filter-branch-search { display:flex; align-items:center; gap:8px; margin:0; padding:8px 12px; border-bottom:1px solid #f3f4f6; color:#9ca3af; }
    .pos-filter-branch-search i { flex:none; font-size:14px; }
    .pos-filter-branch-search input { width:100%; min-width:0; height:20px; border:0; padding:0; outline:0; background:transparent; color:#374151; font-size:16px; }
    .pos-filter-branch-options { max-height:128px; overflow-y:auto; overscroll-behavior:contain; }
    .pos-filter-branch-options label { display:flex; min-height:33px; align-items:center; gap:12px; margin:0; padding:6px 14px; border-bottom:1px solid #f3f4f6; color:#4b5563; font-size:12px; cursor:pointer; }
    .pos-filter-branch-options label:last-child { border-bottom:0; }
    .pos-filter-branch-options input[type="radio"] { appearance:none; box-sizing:border-box; width:16px; height:16px; flex:none; margin:0; border:2px solid #d1d5db; border-radius:50%; padding:0; background:#fff; box-shadow:none; cursor:pointer; }
    .pos-filter-branch-options input[type="radio"]:checked { border-color:#10b981; background-image:radial-gradient(circle,#10b981 0 4px,transparent 4px); }
    .pos-filter-branch-options input[type="radio"]:focus-visible { outline:2px solid #10b981; outline-offset:2px; }
    .pos-filter-branch-options label:has(input:checked) { color:#111827; background:#f9fafb; font-weight:700; }
    .pos-filter-branch-empty { padding:12px; margin:0; color:#9ca3af; font-size:12px; text-align:center; }
    .pos-filter-status-options { display:flex; flex-wrap:wrap; gap:8px; }
    .pos-filter-status-options button { min-height:30px; border:1px solid #e5e7eb; border-radius:999px; padding:4px 14px; background:#fff; color:#4b5563; font-size:12px; cursor:pointer; }
    .pos-filter-status-options button[aria-pressed="true"] { border-color:#6ee7b7; background:#ecfdf5; color:#047857; font-weight:600; }
    .pos-filter-dates { display:grid; gap:8px; margin-bottom:10px; }
    .pos-filter-dates button { display:flex; width:100%; min-width:0; min-height:38px; align-items:center; gap:16px; border:1px solid #e5e7eb; border-radius:12px; padding:8px 12px; background:#f9fafb; text-align:left; cursor:pointer; }
    .pos-filter-dates button:focus-visible { outline:2px solid #0b595b; outline-offset:2px; }
    .pos-filter-dates span { width:24px; flex:none; color:#9ca3af; font-size:10px; font-weight:700; }
    .pos-filter-dates strong { min-width:0; overflow:hidden; color:#6b7280; font-size:12px; font-weight:500; text-overflow:ellipsis; white-space:nowrap; }
    .pos-filter-presets { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
    .pos-filter-presets button { min-width:0; min-height:38px; border:1px solid #e5e7eb; border-radius:12px; padding:6px 8px; background:#fff; color:#6b7280; font-size:12px; font-weight:700; cursor:pointer; }
    .pos-filter-presets button.is-selected { border-color:#c5f037; background:#c5f037; color:#1f2937; }
    .pos-filter-calendar { display:flex; flex-direction:column; gap:16px; }
    .pos-filter-calendar-back { display:inline-flex; min-height:36px; align-items:center; gap:8px; align-self:flex-start; border:0; border-radius:8px; padding:4px 6px; margin:0; background:transparent; color:#374151; font-size:14px; font-weight:600; cursor:pointer; }
    .pos-filter-calendar-back i { font-size:18px; }
    .pos-filter-calendar-card { max-width:100%; border:1px solid #f3f4f6; border-radius:20px; padding:16px; background:#fff; box-shadow:0 1px 4px rgba(15,23,42,.06); }
    .pos-filter-calendar-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
    .pos-filter-calendar-header button { display:flex; width:36px; height:36px; align-items:center; justify-content:center; border:0; border-radius:50%; padding:0; background:transparent; color:#6b7280; font-size:18px; cursor:pointer; }
    .pos-filter-calendar-header button:hover { background:#f9fafb; }
    .pos-filter-calendar-header button:nth-child(2) { width:auto; border-radius:8px; padding:4px 10px; color:#1f2937; font-size:18px; font-weight:700; }
    .pos-filter-calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); text-align:center; }
    .pos-filter-calendar-weekday,.pos-filter-calendar-blank { display:flex; height:36px; align-items:center; justify-content:center; color:#9ca3af; font-size:12px; font-weight:600; }
    .pos-filter-calendar-day { position:relative; display:flex; height:40px; align-items:center; justify-content:center; }
    .pos-filter-calendar-day > i { position:absolute; inset:2px 0; background:#ecfdf5; }
    .pos-filter-calendar-day button { position:relative; z-index:1; width:36px; height:36px; border:0; border-radius:50%; padding:0; background:transparent; color:#374151; font-size:14px; cursor:pointer; }
    .pos-filter-calendar-day button:hover { background:#f3f4f6; }
    .pos-filter-calendar-day button.is-in-range { color:#1f2937; font-weight:600; }
    .pos-filter-calendar-day button.is-selected,.pos-filter-calendar-options button.is-selected { background:#c5f037; color:#1f2937; font-weight:700; }
    .pos-filter-calendar-options { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; padding:8px 0; }
    .pos-filter-calendar-options button { min-height:44px; border:0; border-radius:12px; padding:8px; background:transparent; color:#4b5563; font-size:12px; cursor:pointer; }
    .pos-filter-calendar-options button:hover { background:#f9fafb; }
    @media (max-width:360px) {
        .pos-filter-panel .erp-filter-sheet__body { padding:16px; }
        .pos-filter-calendar-card { padding:12px; }
        .pos-filter-calendar-day button { width:32px; height:32px; }
        .pos-filter-calendar-weekday { font-size:11px; }
    }
    /* The shell root is 12px and Metronic utilities are !important, so the shared dialog's rem sizes are pinned to the reference pixels. */
    [data-pos-detail] > section { max-width:384px; border-radius:16px; }
    [data-pos-detail] > section > div:first-child { padding:24px !important; }
    [data-pos-detail] > section > div:first-child > div:first-child { margin-bottom:24px !important; }
    [data-pos-detail] h2 { font-size:18px; line-height:28px; color:#1f2937; }
    [data-pos-detail] button[aria-label^="Tutup detail"] { display:none; } /* the reference closes on backdrop or Esc */
    @media (max-width:1279px) {
        #pos-table, #pos-table tbody { display:block; width:100% !important; }
        #pos-table thead { display:none; }
        #pos-table tbody tr { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:4px 12px; padding:12px 16px; border-bottom:1px solid #f1f3f5; }
        #pos-table tbody td { display:block; width:auto !important; border:0 !important; padding:0 !important; }
        #pos-table tbody td:nth-child(2) { text-align:right; }
        #pos-table .pos-list-meta { display:none; }
        #pos-table td.dataTables_empty { grid-column:1 / -1; }
    }
    @media (max-width:767px) {
        .pos-list-customer { font-size:12px; }
        .pos-list-customer .pos-list-phone { font-size:11px; }
        .pos-list-sub { font-size:11px; }
        .pos-list-total { font-size:11px; }
        #pos-table tbody tr { padding:14.5px 16px; }
        #kt_content:has(.pos-list-page) { padding-inline:0 !important; }
        .pos-list-page .erp-workspace { border:0; border-radius:0; }
        .pos-list-page .erp-workspace-heading { display:none; }
        .pos-list-page .erp-workspace-toolbar { position:sticky; top:0; z-index:25; padding:16px; background:#fff; }
    }
    @media (max-width:639px) {
        .erp-workspace-toolbar > .pos-add { display:none; }
    }
</style>
<script src="{{ asset('assets/js/pos-list-reference.js') }}?v={{ filemtime(public_path('assets/js/pos-list-reference.js')) }}"></script>
<div class="contents pos-list-page" data-pos-list x-data="PosListUi.createFilters(@js($posFilterConfig))" x-init="onApply = (applied) => window.posApplyFilters(applied); onLoadMore = () => window.posLoadMore()" @pos-list-drawn.window="onDrawn($event.detail)" @keydown.escape.window="close()">
<x-erp.workspace title="Kasir / POS" subtitle="Kelola kasir / pos dan lihat rincian transaksi.">
    <x-slot:toolbar>
        <x-erp.mobile-search class="pos-toolbar-search erp-mobile-search-shell--always-visible erp-mobile-search-shell--flush">
            <x-slot:input><input id="search" type="search" class="erp-mobile-search__input" placeholder="Cari..." autocomplete="off" aria-label="Cari transaksi kasir"></x-slot:input>
            <x-slot:clear><button id="search-clear" type="button" class="erp-mobile-search__clear hidden" aria-label="Hapus pencarian"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button></x-slot:clear>
            <x-slot:filter><button type="button" @click="open()" :aria-expanded="filtersOpen" aria-haspopup="dialog" aria-label="Buka filter transaksi" class="erp-mobile-search__filter" :class="(filtersOpen || hasActive) ? 'is-active' : ''"><i class="ph ph-sliders-horizontal text-xl leading-none" aria-hidden="true"></i><span x-show="hasActive" x-cloak class="erp-mobile-search__filter-dot"></span></button></x-slot:filter>
        </x-erp.mobile-search>
        @if(check_access('pos.create'))<a href="{{ url('pos/create') }}" class="erp-action erp-action--primary pos-add" aria-label="Tambah Transaksi"><i class="ph-bold ph-plus" aria-hidden="true"></i><span>Tambah Transaksi</span></a>@endif
    </x-slot:toolbar>
    <div class="pos-list"><table class="table align-middle table-row-dashed fs-6 gy-5" id="pos-table" width="100%">
        <thead>
            <tr class="text-start gs-0">
                <th class="text-start min-w-150px">Pelanggan</th>
                <th class="text-end">Total &amp; Status</th>
            </tr>
        </thead>
        <tbody class="fw-semibold text-gray-600"></tbody>
    </table></div>
    <div class="pos-list-more" x-show="hasMore" x-cloak>
        <x-erp.load-more @click="loadMore()" loading="loading"><span>Muat Lebih Banyak <span x-text="remaining"></span></span></x-erp.load-more>
    </div>
</x-erp.workspace>

<template x-teleport="body">
    <div x-show="filtersOpen" x-cloak class="pos-filter-overlay fixed inset-0 z-[100] flex items-center justify-center" role="dialog" aria-modal="true" aria-labelledby="pos-filter-title">
        <button type="button" class="absolute inset-0 bg-black/60" @click="close()" aria-label="Tutup filter"></button>
        <section x-show="filtersOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full opacity-0 md:translate-y-4 md:scale-95" x-transition:enter-end="translate-y-0 opacity-100 md:scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100 md:scale-100" x-transition:leave-end="translate-y-full opacity-0 md:translate-y-4 md:scale-95" class="pos-filter-panel relative flex w-full flex-col overflow-hidden bg-white pb-0 shadow-2xl">
            <div class="pos-filter-handle"><span></span></div>
            <x-erp.filter-sheet-frame title="Filter Transaksi" title-id="pos-filter-title" title-tag="h3" subtitle="Saring cabang, status, dan periode tanggal transaksi." body-class="pos-filter-body min-h-0 flex-1 overflow-y-auto overscroll-contain">
                <x-slot:close><button type="button" @click="close()" aria-label="Tutup"><i class="ph-bold ph-x" aria-hidden="true"></i></button></x-slot:close>
                <x-slot:body>
                    <div x-show="!calendarOpen" class="pos-filter-main">
                        <fieldset class="pos-filter-section">
                            <legend class="pos-filter-heading"><span>Cabang</span><button type="button" class="pos-filter-reset" x-show="draft.branch !== (branches.length === 1 ? branches[0].id : 'all') || branchSearch.trim()" @click="draft.branch = branches.length === 1 ? branches[0].id : 'all'; branchSearch = ''">Reset</button></legend>
                            <div class="pos-filter-branch-picker">
                                <label class="pos-filter-branch-search" for="pos-branch-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input id="pos-branch-search" type="search" x-model="branchSearch" placeholder="Cari cabang..." aria-label="Cari cabang" autocomplete="off"></label>
                                <div class="pos-filter-branch-options">
                                    <label x-show="branches.length > 1 && showAllBranches"><input type="radio" name="pos-branch" value="all" x-model="draft.branch"><span>Semua Cabang</span></label>
                                    <template x-for="branch in visibleBranches" :key="branch.id"><label><input type="radio" name="pos-branch" :value="branch.id" x-model="draft.branch"><span x-text="branch.name"></span></label></template>
                                    <p x-show="visibleBranches.length === 0 && !showAllBranches" class="pos-filter-branch-empty">Cabang tidak ditemukan</p>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset class="pos-filter-section">
                            <legend class="pos-filter-heading"><span>Status Transaksi</span><button type="button" class="pos-filter-reset" x-show="draft.status !== 'all'" @click="draft.status = 'all'">Reset</button></legend>
                            <div class="pos-filter-status-options">
                                @foreach ($posStatuses as $value => $label)
                                    <button type="button" data-pos-status="{{ $value }}" @click="draft.status = '{{ $value }}'" :aria-pressed="draft.status === '{{ $value }}'">{{ $label }}</button>
                                @endforeach
                            </div>
                        </fieldset>
                        <fieldset class="pos-filter-section">
                            <legend class="pos-filter-heading"><span>Periode Tanggal</span><button type="button" class="pos-filter-reset" x-show="draft.start || draft.end" @click="draft.start = ''; draft.end = ''">Reset</button></legend>
                            <div class="pos-filter-dates">
                                <button type="button" id="pos-start-date" aria-label="Pilih tanggal mulai transaksi" aria-controls="pos-filter-calendar" :aria-expanded="calendarOpen && calendarSide === 'start'" @click="openCalendar('start', $el); $nextTick(() => requestAnimationFrame(() => { $refs.posCalendarBack?.focus({preventScroll:true}); $refs.posCalendarBack?.scrollIntoView({block:'nearest'}); }))"><span>DARI</span><strong x-text="formatDate(draft.start)"></strong></button>
                                <button type="button" id="pos-end-date" aria-label="Pilih tanggal akhir transaksi" aria-controls="pos-filter-calendar" :aria-expanded="calendarOpen && calendarSide === 'end'" @click="openCalendar('end', $el); $nextTick(() => requestAnimationFrame(() => { $refs.posCalendarBack?.focus({preventScroll:true}); $refs.posCalendarBack?.scrollIntoView({block:'nearest'}); }))"><span>KE</span><strong x-text="formatDate(draft.end)"></strong></button>
                            </div>
                            <p x-show="dateError" x-cloak role="alert" class="mt-2 text-xs font-medium text-red-600" x-text="dateError"></p>
                            <div class="flex flex-col gap-2 pt-2">
                                <span class="pos-filter-heading" style="margin:0">Pilihan Cepat</span>
                                <div class="pos-filter-presets">
                                    <button type="button" @click="setDateRange('week')" :class="isDateRangeActive('week') ? 'is-selected' : ''" :aria-pressed="isDateRangeActive('week')">Minggu Ini</button>
                                    <button type="button" @click="setDateRange('today')" :class="isDateRangeActive('today') ? 'is-selected' : ''" :aria-pressed="isDateRangeActive('today')">Hari Ini</button>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div id="pos-filter-calendar" class="pos-filter-calendar" data-pos-calendar x-show="calendarOpen" x-cloak @keydown.escape.stop="closeCalendar()">
                        <button type="button" x-ref="posCalendarBack" class="pos-filter-calendar-back" @click="closeCalendar()"><i class="ph ph-arrow-left" aria-hidden="true"></i><span>Kembali</span></button>
                        <div class="pos-filter-calendar-card">
                            <div class="pos-filter-calendar-header">
                                <button type="button" @click="navigateCalendar(-1)" aria-label="Sebelumnya"><i class="ph ph-caret-left" aria-hidden="true"></i></button>
                                <button type="button" @click="setCalendarView(calendarView === 'date' ? 'month' : calendarView === 'month' ? 'year' : 'date')" x-text="calendarHeading" aria-label="Ubah tampilan kalender"></button>
                                <button type="button" @click="navigateCalendar(1)" aria-label="Berikutnya"><i class="ph ph-caret-right" aria-hidden="true"></i></button>
                            </div>
                            <div x-show="calendarView === 'date'" class="pos-filter-calendar-grid">
                                <template x-for="(weekday, index) in calendarWeekdays" :key="'weekday-' + index"><span class="pos-filter-calendar-weekday" :aria-label="weekday" x-text="weekday[0]"></span></template>
                                <template x-for="(day, index) in calendarDays" :key="day ? day.date : 'blank-' + index"><span class="pos-filter-calendar-day"><i x-show="day && isCalendarDateInRange(day.date)" aria-hidden="true"></i><button type="button" :disabled="!day" :aria-pressed="!!day && isCalendarDateSelected(day.date)" :class="day && isCalendarDateSelected(day.date) ? 'is-selected' : day && isCalendarDateInRange(day.date) ? 'is-in-range' : ''" @click="selectCalendarDate(day)" x-text="day ? day.day : ''"></button></span></template>
                            </div>
                            <div x-show="calendarView === 'month'" class="pos-filter-calendar-options">
                                <template x-for="(month, index) in calendarMonths" :key="'month-' + index"><button type="button" :class="calendarMonth === index ? 'is-selected' : ''" :aria-pressed="calendarMonth === index" @click="selectCalendarMonth(index)" x-text="month"></button></template>
                            </div>
                            <div x-show="calendarView === 'year'" class="pos-filter-calendar-options">
                                <template x-for="year in calendarYears" :key="'year-' + year"><button type="button" :class="calendarYear === year ? 'is-selected' : ''" :aria-pressed="calendarYear === year" @click="selectCalendarYear(year)" x-text="year"></button></template>
                            </div>
                        </div>
                    </div>
                </x-slot:body>
                <x-slot:footer>
                    <button type="button" @click="reset()">Reset Filter</button>
                    <button type="button" class="erp-filter-sheet__primary" @click="apply()" :disabled="!!dateError">Terapkan Filter</button>
                </x-slot:footer>
            </x-erp.filter-sheet-frame>
        </section>
    </div>
</template>

<template x-teleport="body">
    <x-erp.detail-dialog open="detailOpen" close="closeDetail()" title="Detail Transaksi" labelledby="pos-detail-title" close-label="Tutup detail transaksi" data-pos-detail>
        <x-slot:subtitle><p class="mt-[4px] truncate text-[12px] font-medium text-[#6b7280]" x-show="detail && detail.branch" x-text="detail?.branch"></p></x-slot:subtitle>
        <div class="flex flex-col gap-[16px] text-[14px]" x-show="detail">
            <div class="flex items-center gap-[12px]">
                <div class="flex h-[40px] w-[40px] shrink-0 items-center justify-center rounded-[12px] border border-emerald-200/80 bg-emerald-50 text-[#0b595b]"><i class="ph-duotone ph-user text-[18px]" aria-hidden="true"></i></div>
                <div class="min-w-0 flex-1">
                    <div class="text-[14px] font-bold leading-snug text-[#1f2937]"><span x-text="detail?.customer"></span><span class="text-[12px] font-normal text-[#6b7280]" x-show="detail?.phone_last4" x-text="' (' + detail?.phone_last4 + ')'"></span></div>
                    <div class="mt-[2px]">
                        <a x-show="detail?.phone" :href="whatsappUrl(detail?.phone)" target="_blank" rel="noopener" class="inline-block cursor-pointer border-b border-dashed border-gray-300 pb-[2px] text-[12px] font-semibold text-[#1f2937]"><span x-text="detail?.phone"></span></a>
                        <span x-show="!detail?.phone" class="text-[12px] font-semibold text-[#6b7280]">Tidak ada nomor telepon</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-[12px]">
                <div class="flex h-[40px] w-[40px] shrink-0 items-center justify-center rounded-[12px] border border-emerald-200/80 bg-emerald-50 text-[#0b595b]"><i class="ph-duotone ph-calendar-blank text-[18px]" aria-hidden="true"></i></div>
                <div class="min-w-0 flex-1">
                    <div class="text-[14px] font-bold leading-snug tabular-nums text-[#1f2937]" x-text="detail?.date_label"></div>
                    <div class="mt-[2px] text-[11px] font-medium text-[#6b7280]" x-text="detail?.created_by ? 'Dibuat oleh ' + detail.created_by : 'Dibuat oleh -'"></div>
                </div>
            </div>
            <div class="flex items-center gap-[12px]">
                <div class="flex h-[40px] w-[40px] shrink-0 items-center justify-center rounded-[12px] border border-emerald-200/80 bg-emerald-50 text-[#0b595b]"><i class="ph-duotone ph-pencil-simple text-[18px]" aria-hidden="true"></i></div>
                <div class="min-w-0 flex-1">
                    <div class="text-[14px] leading-snug" :class="detail?.edited_label ? 'font-bold text-[#1f2937]' : 'font-normal text-[#6b7280]'" x-text="detail?.edited_label || '-'"></div>
                    <div class="mt-[2px] text-[11px] font-medium text-[#6b7280]" x-text="detail?.edited_label ? 'Waktu transaksi terakhir diubah' : 'Diedit'"></div>
                </div>
            </div>
        </div>
        <x-slot:actions>
            <div class="flex w-full items-center gap-[10px] px-[24px] py-[20px]" x-show="detail">
                <button type="button" x-show="detail && canDelete(detail)" @click="const id = detail.id; closeDetail(); deleteProduct(id)" class="flex h-[44px] cursor-pointer items-center justify-center gap-[6px] rounded-[12px] border border-[#fecaca] px-[16px] text-[14px] font-semibold text-red-600 transition hover:bg-red-50 active:scale-95"><i class="ph-bold ph-trash text-[16px]" aria-hidden="true"></i><span>Hapus</span></button>
                <a :href="detail ? detailPrimary(detail).url : '#'" class="flex h-[44px] flex-1 cursor-pointer items-center justify-center rounded-[12px] bg-[#0b595b] text-[14px] font-bold text-white shadow-md shadow-[#0b595b]/20 transition hover:bg-[#08484a] active:scale-95"><span x-text="detail ? detailPrimary(detail).label : ''"></span></a>
            </div>
        </x-slot:actions>
    </x-erp.detail-dialog>
</template>
</div>


@section('script')
    <script type="text/javascript">
        // Data stock kosong dari server
        const emptyStockData = @json($emptyStockData ?? []);
        let hasShownInitialAlert = false;

        function showStockAlert(selectedBranchId) {
            let filteredProducts = emptyStockData;
            if (selectedBranchId && selectedBranchId !== 'all') {
                filteredProducts = emptyStockData.filter(p => p.branch_id == selectedBranchId);
            }

            if (filteredProducts.length > 0) {
                const count = filteredProducts.length;
                const badges = filteredProducts.slice(0, 10).map(p => {
                    return `<span class="badge badge-light-danger me-1 mb-1">${p.name} (${p.branch_name})</span>`;
                }).join('');

                const remainingHtml = count > 10 ?
                    `<br><small>...dan ${count - 10} produk lainnya</small>` :
                    '';

                const scopeText = selectedBranchId === 'all' ? 'pada seluruh cabang' : 'pada cabang ini';
                const alertHtml = `Terdapat <strong>${count}</strong> produk dengan stok kosong ${scopeText}.<br><br>` +
                    `<div style="text-align: left; max-height: 200px; overflow-y: auto;">` +
                    badges +
                    remainingHtml +
                    `</div>`;

                Swal.fire({
                    title: 'Peringatan Stok Minus!',
                    html: alertHtml,
                    icon: 'warning',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#b45309',
                    allowOutsideClick: false
                });
            }
        }

        @if (session('error'))
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Bisa Diedit',
                    text: "{{ session('error') }}"
                });
            });
        @endif

        var dataTable;
        const POS_PAGE_STEP = {{ $posFilterConfig['step'] }};
        const posRoot = () => document.querySelector('[data-pos-list]');
        const posUi = () => Alpine.$data(posRoot());

        // Reference avatar: initials on a tinted tile, with the parcel flag as a micro-badge.
        function decoratePosRow(row) {
            const label = row.querySelector('.pos-list-customer > span:last-child');
            const avatar = row.querySelector('.pos-list-avatar');
            if (!label || !avatar) return;
            const full = label.textContent.trim();
            const match = full.match(/^(.*?)\s*\((\d{4})\)$/);
            const name = match ? match[1] : full;
            avatar.className = 'pos-list-avatar is-theme-' + PosListUi.avatarTheme(name);
            avatar.textContent = PosListUi.customerInitial(name);
            avatar.setAttribute('aria-hidden', 'true');
            const wrap = document.createElement('span');
            wrap.className = 'pos-list-avatar-wrap';
            avatar.replaceWith(wrap);
            wrap.appendChild(avatar);
            const parcel = row.querySelector('.pos-list-parcel');
            if (parcel) {
                parcel.remove();
                const badge = document.createElement('span');
                badge.className = 'pos-list-parcel-badge';
                badge.title = 'Ada Parcel';
                badge.innerHTML = '<i class="ph-bold ph-gift" role="img" aria-label="Ada Parcel"></i>';
                wrap.appendChild(badge);
            }
            if (match) {
                label.textContent = name;
                const phone = document.createElement('span');
                phone.className = 'pos-list-phone';
                phone.textContent = '(' + match[2] + ')';
                label.after(phone);
            }
        }

        $(document).ready(function() {
            const posEmptyState = '<div class="pos-list-empty"><i class="ph ph-receipt" aria-hidden="true"></i><strong>Belum ada data transaksi</strong><span>Transaksi kasir yang tersimpan akan muncul di sini.</span></div>';
            const posEmptyFiltered = '<div class="pos-list-empty"><i class="ph ph-receipt" aria-hidden="true"></i><strong>Tidak ada transaksi yang cocok</strong><span>Coba ubah kata kunci atau filter yang sedang aktif.</span><button type="button" data-pos-clear>Hapus Filter</button></div>';
            let lastBranch = '{{ $posFilterConfig['defaultBranch'] }}';

            dataTable = $('#pos-table').DataTable({
                language: { emptyTable: posEmptyState, zeroRecords: posEmptyState },
                createdRow: function(row) {
                    const link = row.querySelector('a.pos-list-copy');
                    if (link) {
                        row.classList.add('is-openable');
                        row.dataset.href = link.getAttribute('href');
                        row.dataset.detail = link.dataset.detail || '';
                    }
                    decoratePosRow(row);
                },
                drawCallback: function() {
                    const info = this.api().page.info();
                    if (info.recordsDisplay === 0) {
                        const filtered = posUi().hasActive || $('#search').val().trim() !== '';
                        $('#pos-table td.dataTables_empty').html(filtered ? posEmptyFiltered : posEmptyState);
                    }
                    window.dispatchEvent(new CustomEvent('pos-list-drawn', { detail: { end: info.end, recordsDisplay: info.recordsDisplay } }));
                },
                pageLength: POS_PAGE_STEP,
                processing: true,
                serverSide: true,
                ordering: false, // The server always returns newest transactions first.
                ajax: {
                    url: "{{ route('pos-data') }}",
                    data: function(d) {
                        const applied = posUi().applied;
                        d.url = "{{ request()->segment(1) }}";
                        d.status_filter = applied.status;
                        d.cabang_filter = applied.branch;
                        // A single picked date means that day, like the old range picker.
                        const start = applied.start || applied.end;
                        const end = applied.end || applied.start;
                        if (start && end) {
                            d.start_date = start;
                            d.end_date = end;
                        }
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'text-end'
                    },
                ]
            });

            window.posApplyFilters = function(applied) {
                dataTable.page.len(POS_PAGE_STEP).draw();
                if (applied.branch !== lastBranch) {
                    lastBranch = applied.branch;
                    showStockAlert(applied.branch);
                }
            };
            window.posLoadMore = function() {
                const info = dataTable.page.info();
                dataTable.page.len(PosListUi.nextLength(info.end, POS_PAGE_STEP)).draw(false);
            };

            // A tap on a row opens the detail popup; the link stays a real URL for open-in-new-tab.
            $('#pos-table tbody').on('click', 'tr.is-openable', function(event) {
                if ($(event.target).closest('button, .dropdown, .dropdown-menu').length) return;
                if (event.metaKey || event.ctrlKey || event.shiftKey) return;
                const detail = PosListUi.parseDetail(this.dataset.detail || '');
                if (!detail) return; // no popup data: let the link navigate as before
                event.preventDefault();
                posUi().openDetail(detail);
            });

            // Search manual lewat input
            let searchTimer;
            $('#search').on('input', function() {
                const value = this.value;
                $('#search-clear').toggleClass('hidden', !value);
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    dataTable.search(value).page.len(POS_PAGE_STEP).draw();
                }, 250);
            });
            $('#search-clear').on('click', function() {
                $('#search').val('').trigger('input').trigger('focus');
            });
            $('#pos-table').on('click', '[data-pos-clear]', function() {
                $('#search').val('').trigger('input');
                posUi().reset();
            });

            showStockAlert(lastBranch);
        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
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
                        url: `/pos/${id}`, // Ganti dengan URL yang sesuai
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
