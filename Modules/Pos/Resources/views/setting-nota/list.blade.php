@extends('template.root')
@section('title', 'Nota & Cetak')
@section('page-title', 'Nota & Cetak')

@section('content')
<style>
    body:has(#nota-branch-list-page) #kt_content { padding:0 !important; }
    #nota-branch-list-page { min-height:calc(100dvh - 61px); background:#fff; font-family:'Plus Jakarta Sans',sans-serif; }
    #nota-branch-list-page .erp-mobile-search-shell { padding:16px; border-bottom:1px solid #f3f4f6; }
    #nota-branch-list-page .erp-mobile-search { height:44px; }
    .nota-branch-row { display:flex; min-height:65px; align-items:center; justify-content:space-between; gap:12px; padding:15px 16px 13px; border-bottom:1px solid #f3f4f6; color:#111827; text-decoration:none; transition:background-color .15s ease; }
    .nota-branch-row:hover { background:rgba(249,250,251,.6); color:#111827; }
    .nota-branch-row__main { display:flex; min-width:0; flex:1; align-items:center; gap:12px; }
    .nota-branch-row__tile { display:flex; width:36px; height:36px; flex:none; align-items:center; justify-content:center; border:1px solid; border-radius:12px; font-size:16px; }
    .nota-branch-row__name { overflow:hidden; color:#111827; font-size:14px; font-weight:600; line-height:20px; text-overflow:ellipsis; white-space:nowrap; }
    .nota-branch-row__chevron { flex:none; color:#d1d5db; font-size:14px; }
    .nota-branch-empty { padding:56px 24px; color:#9ca3af; font-size:13px; text-align:center; }
</style>
<div id="nota-branch-list-page" x-data="{ q: '' }">
    <x-erp.mobile-search>
        <x-slot:input>
            <input type="search" x-model.debounce.150ms="q" aria-label="Cari cabang" placeholder="Cari cabang, kode, atau alamat..." class="erp-mobile-search__input">
        </x-slot:input>
        <x-slot:clear>
            <button type="button" x-show="q" x-cloak @click="q = ''" aria-label="Hapus pencarian" class="erp-mobile-search__clear"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button>
        </x-slot:clear>
    </x-erp.mobile-search>
    <div>
        @foreach ($branches as $branch)
            <a href="{{ url('setting-nota') }}?cabang_filter={{ $branch->id }}" class="nota-branch-row" data-search="{{ strtolower($branch->name . ' ' . ($branch->code ?? '') . ' ' . ($branch->address ?? '')) }}" x-show="!q || $el.dataset.search.includes(q.toLowerCase())">
                <span class="nota-branch-row__main">
                    <span class="nota-branch-row__tile" data-branch-name="{{ $branch->name }}"><i class="ph-bold ph-receipt" aria-hidden="true"></i></span>
                    <span class="nota-branch-row__name">{{ $branch->name }}</span>
                </span>
                <i class="ph-bold ph-caret-right nota-branch-row__chevron" aria-hidden="true"></i>
            </a>
        @endforeach
        @if ($branches->isEmpty())
            <p class="nota-branch-empty">Belum ada cabang.</p>
        @endif
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.nota-branch-row__tile').forEach(function (tile) {
            if (window.ErpTone && ErpTone.customerClassName) tile.classList.add(ErpTone.customerClassName(tile.dataset.branchName));
        });
    });
</script>
@endsection
