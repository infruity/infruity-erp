@props(['title', 'subtitle' => '', 'icon' => 'squares-four'])
<section {{ $attributes->class(['erp-workspace']) }} aria-label="{{ $title }}">
    <header class="erp-workspace-heading"><div><h2>{{ $title }}</h2>@if($subtitle)<p>{{ $subtitle }}</p>@endif</div></header>
    @isset($toolbar)<div class="erp-workspace-toolbar">{{ $toolbar }}</div>@endisset
    <div class="erp-workspace-content">{{ $slot }}</div>
    @isset($footer)<footer class="erp-workspace-footer">{{ $footer }}</footer>@endisset
</section>
