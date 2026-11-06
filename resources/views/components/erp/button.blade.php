@props(['variant' => 'default', 'icon' => null, 'bold' => false, 'href' => null, 'type' => 'button'])
@php
    // Nama class ditulis lengkap agar terbaca scanner Tailwind (jangan disusun lewat konkatenasi).
    $variants = ['default' => '', 'primary' => 'erp-action--primary', 'danger' => 'erp-action--danger'];
    $iconClass = $bold ? 'ph-bold' : 'ph';
@endphp
{{-- Tombol/tautan aksi. Teks lewat slot; tombol hanya ikon: beri aria-label. --}}
@if($href)
    <a href="{{ $href }}" {{ $attributes->class(['erp-action', $variants[$variant] ?? '']) }}>@if($icon)<i class="{{ $iconClass }} ph-{{ $icon }}" aria-hidden="true"></i>@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['erp-action', $variants[$variant] ?? '']) }}>@if($icon)<i class="{{ $iconClass }} ph-{{ $icon }}" aria-hidden="true"></i>@endif{{ $slot }}</button>
@endif
