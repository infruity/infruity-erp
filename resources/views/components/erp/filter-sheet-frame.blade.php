@props([
    'title',
    'titleId',
    'subtitle' => null,
    'headerClass' => '',
    'bodyClass' => '',
    'footerClass' => '',
    'titleClass' => '',
    'titleTag' => 'h2',
    'subtitleClass' => '',
    'subtitleId' => null,
])

<header {{ $attributes->class(['erp-filter-sheet__header', $headerClass]) }}>
    <div class="erp-filter-sheet__heading">
        <{{ $titleTag }} id="{{ $titleId }}" class="erp-filter-sheet__title {{ $titleClass }}">{{ $title }}</{{ $titleTag }}>
        @if ($subtitle)
            <p @if ($subtitleId) id="{{ $subtitleId }}" @endif class="erp-filter-sheet__subtitle {{ $subtitleClass }}">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $close ?? '' }}
</header>

<div @class(['erp-filter-sheet__body', $bodyClass])>
    {{ $body ?? $slot }}
</div>

@isset($footer)
    <footer class="erp-filter-sheet__footer {{ $footerClass }}">
        {{ $footer }}
    </footer>
@endisset
