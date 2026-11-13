{{--
    "Menampilkan x dari y satuan": the one place and look of the list count in the ERP.
      - desktop: a grey bar under the toolbar (put the component right after it);
      - mobile: a pill right under the search field (zero-height anchor in the flow) that shows while the list scrolls.
    The component finds the scrolling list among its siblings (marked data-erp-list, or .mobile-list-edge-fade) and
    listens to its scroll, so a page only adds the marker. Put the component directly under the search field.

    Alpine pages pass JS expressions:      <x-erp.list-count noun="satuan" shown="Math.min(visibleCount, filtered.length)" total="filtered.length" />
    Server-rendered pages pass numbers:    <x-erp.list-count noun="role" :shown="$roles->count()" :total="$roles->total()" server />
    Other pages (DataTables) can give the numbers an id and fill them in:  <x-erp.list-count noun="produk" shown-id="products-count-display" total-id="products-count-total" />
    Pages whose whole page scrolls (no inner list) use window-scroll (the search and pill are sticky together there).
--}}
@props(['noun', 'shown' => null, 'total' => null, 'server' => false, 'shownId' => null, 'totalId' => null, 'list' => '[data-erp-list], .mobile-list-edge-fade', 'windowScroll' => false])
@php
    $numberAttrs = function (?string $expression, ?string $id) use ($server) {
        $attrs = $id ? ' id="'.e($id).'"' : '';

        return $attrs.(! $server && $expression !== null ? ' x-text="'.e($expression).'"' : '');
    };
@endphp
<div class="contents" x-data="{ scrolled: false, scrollTimer: null }"
    x-init="const show = () => { scrolled = true; clearTimeout(scrollTimer); scrollTimer = setTimeout(() => scrolled = false, 600); }; @if ($windowScroll) window.addEventListener('scroll', show, { passive: true, capture: true }); @else const list = $el.parentElement.querySelector(@js($list)); if (list) list.addEventListener('scroll', show, { passive: true }); @endif">
    <div class="hidden md:flex px-4 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 font-medium">
        Menampilkan <span class="mx-1 text-gray-700 font-semibold"{!! $numberAttrs($shown, $shownId) !!}>{{ $server ? $shown : ($shownId ? '0' : '') }}</span> dari
        <span class="ml-1 text-gray-700 font-semibold"{!! $numberAttrs($total, $totalId) !!}>{{ $server ? $total : ($totalId ? '0' : '') }}</span>&nbsp;{{ $noun }}
    </div>
    <div class="md:hidden relative z-20 h-0 flex-none pointer-events-none">
        <div x-show="scrolled" x-cloak style="display: none" class="absolute inset-x-0 top-1 flex justify-center">
            <span class="bg-white/95 backdrop-blur-md border border-gray-200 rounded-full px-4 py-1.5 shadow-lg text-[11px] text-gray-700 font-medium">
                Menampilkan <strong{!! $numberAttrs($shown, $shownId ? $shownId.'-mobile' : null) !!}>{{ $server ? $shown : ($shownId ? '0' : '') }}</strong> dari <strong{!! $numberAttrs($total, $totalId ? $totalId.'-mobile' : null) !!}>{{ $server ? $total : ($totalId ? '0' : '') }}</strong> {{ $noun }}
            </span>
        </div>
    </div>
</div>
