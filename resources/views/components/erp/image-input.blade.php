@props([
    'name' => 'image',
    'removeName' => null,
    'value' => null,
    'placeholder' => null,
    'accept' => '.png,.jpg,.jpeg',
    'label' => 'Pilih gambar',
    'removeLabel' => 'Hapus gambar',
    'cancelLabel' => 'Batal',
])
@php
    $placeholder = $placeholder ?: asset('assets/media/svg/files/blank-image.svg');
    $current = $value ?: $placeholder;
@endphp
{{-- Pilih/ganti/hapus gambar dengan pratinjau. Perilaku: resources/js/erp/image-input.js (pengganti KTImageInput). --}}
<div {{ $attributes->class(['erp-image-input']) }} data-erp-image-input data-placeholder="{{ $placeholder }}" data-original="{{ $current }}">
    <span class="erp-image-input__preview" data-image-preview style="background-image:url('{{ $current }}')" role="img" aria-label="Pratinjau gambar"></span>
    <label class="erp-action"><i class="ph ph-upload-simple" aria-hidden="true"></i> {{ $label }}<input type="file" name="{{ $name }}" accept="{{ $accept }}" class="sr-only" data-image-file></label>
    <button type="button" class="erp-action" data-image-remove aria-label="{{ $removeLabel }}"><i class="ph ph-trash" aria-hidden="true"></i></button>
    <button type="button" class="erp-action" data-image-cancel hidden>{{ $cancelLabel }}</button>
    @if ($removeName)<input type="hidden" name="{{ $removeName }}" data-image-remove-flag>@endif
</div>
