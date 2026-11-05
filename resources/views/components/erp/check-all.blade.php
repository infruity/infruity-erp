@props(['target', 'label' => 'Pilih semua'])
{{-- Isi target: <input type="checkbox" class="erp-check" data-erp-check>. Lihat resources/js/erp/check-all.js. --}}
<input type="checkbox" data-erp-check-all="{{ $target }}" aria-label="{{ $label }}" {{ $attributes->class(['erp-check']) }}>
