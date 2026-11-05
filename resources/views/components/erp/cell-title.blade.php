@props(['title', 'subtitle' => null, 'href' => null])
<div {{ $attributes->class(['erp-cell-title']) }}>
    @if ($href)
        <a href="{{ $href }}" class="erp-cell-title__main">{{ $title }}</a>
    @else
        <span class="erp-cell-title__main">{{ $title }}</span>
    @endif
    @if (filled($subtitle))<span class="erp-cell-title__sub">{{ $subtitle }}</span>@endif
</div>
