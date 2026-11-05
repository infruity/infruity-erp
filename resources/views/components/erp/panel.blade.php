@props(['title' => null])
<section {{ $attributes->class(['erp-panel']) }}>
    @if ($title || isset($toolbar))
        <header class="erp-panel__header">
            <h2 class="erp-panel__title">{{ $title }}</h2>
            @isset($toolbar)<div class="flex items-center gap-2">{{ $toolbar }}</div>@endisset
        </header>
    @endif
    <div class="erp-panel__body">{{ $slot }}</div>
    @isset($footer)<footer class="erp-panel__footer">{{ $footer }}</footer>@endisset
</section>
