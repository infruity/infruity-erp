@props([
    'id',
    'title' => null,
    'size' => 'md',
    'variant' => 'center',
    'static' => false,
    'closeLabel' => 'Tutup',
])
@php
    $sizes = ['sm' => 'erp-dialog--sm', 'md' => 'erp-dialog--md', 'lg' => 'erp-dialog--lg', 'xl' => 'erp-dialog--xl', 'full' => 'erp-dialog--full'];
    $variants = ['center' => '', 'sheet' => 'erp-dialog--sheet', 'drawer' => 'erp-dialog--drawer'];
@endphp
{{-- Dialog native. Buka dengan data-erp-open="#{{ $id }}" atau erp.dialog.open('{{ $id }}'). Lihat resources/js/erp/dialog.js. --}}
<dialog id="{{ $id }}" data-erp-dialog @if($static) data-static @endif aria-labelledby="{{ $id }}-title"
        {{ $attributes->class(['erp-dialog', $sizes[$size] ?? $sizes['md'], $variants[$variant] ?? '']) }}>
    <div class="erp-dialog__panel">
        <header class="erp-dialog__header">
            <h2 id="{{ $id }}-title" class="erp-dialog__title">{{ $title }}</h2>
            <button type="button" data-erp-close class="erp-dialog__close" aria-label="{{ $closeLabel }}"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
        </header>
        <div class="erp-dialog__body">{{ $slot }}</div>
        @isset($footer)
            <footer class="erp-dialog__footer">{{ $footer }}</footer>
        @endisset
    </div>
</dialog>
