<div {{ $attributes->class(['erp-mobile-search-shell']) }}>
    <div class="erp-mobile-search">
        <i class="ph ph-magnifying-glass erp-mobile-search__icon" aria-hidden="true"></i>
        {{ $input }}
        {{ $clear ?? '' }}
        @if (isset($filter) && trim((string) $filter) !== '')
            <span class="erp-mobile-search__divider" aria-hidden="true"></span>
        @endif
        {{ $filter ?? '' }}
    </div>
</div>
