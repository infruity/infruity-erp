{{--
    "Menampilkan x dari y satuan": the one place and look of the list count in the ERP.
      - desktop: a grey bar under the toolbar (put the component right after it);
      - mobile: a plain line right under the search field, always visible (it does not scroll with the list).

    Alpine pages pass JS expressions:      <x-erp.list-count noun="satuan" shown="Math.min(visibleCount, filtered.length)" total="filtered.length" />
    Server-rendered pages pass numbers:    <x-erp.list-count noun="role" :shown="$roles->count()" :total="$roles->total()" server />
    Other pages (DataTables) can give the numbers an id and fill them in:  <x-erp.list-count noun="produk" shown-id="products-count-display" total-id="products-count-total" />
    list / window-scroll are kept so existing pages keep working; they no longer change anything.
--}}
@props(['noun', 'shown' => null, 'total' => null, 'server' => false, 'shownId' => null, 'totalId' => null, 'list' => '[data-erp-list], .mobile-list-edge-fade', 'windowScroll' => false])
@php
    $numberAttrs = function (?string $expression, ?string $id) use ($server) {
        $attrs = $id ? ' id="'.e($id).'"' : '';

        return $attrs.(! $server && $expression !== null ? ' x-text="'.e($expression).'"' : '');
    };
@endphp
<div class="contents">
    <div class="hidden md:flex px-4 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 font-medium">
        Menampilkan <span class="mx-1 text-gray-700 font-semibold"{!! $numberAttrs($shown, $shownId) !!}>{{ $server ? $shown : ($shownId ? '0' : '') }}</span> dari
        <span class="ml-1 text-gray-700 font-semibold"{!! $numberAttrs($total, $totalId) !!}>{{ $server ? $total : ($totalId ? '0' : '') }}</span>&nbsp;{{ $noun }}
    </div>
    <div class="erp-list-count-mobile md:hidden flex-none px-5 pb-2 pt-0.5 bg-white text-[11px] font-medium text-gray-400">
        Menampilkan <strong class="text-gray-700 font-semibold"{!! $numberAttrs($shown, $shownId ? $shownId.'-mobile' : null) !!}>{{ $server ? $shown : ($shownId ? '0' : '') }}</strong> dari <strong class="text-gray-700 font-semibold"{!! $numberAttrs($total, $totalId ? $totalId.'-mobile' : null) !!}>{{ $server ? $total : ($totalId ? '0' : '') }}</strong> {{ $noun }}
    </div>
</div>
