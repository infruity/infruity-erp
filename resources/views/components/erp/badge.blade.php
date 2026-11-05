@props(['tone' => 'neutral'])
@php
    // Nama class ditulis lengkap agar terbaca scanner Tailwind (jangan disusun lewat konkatenasi).
    $tones = [
        'success' => 'erp-badge--success', 'warning' => 'erp-badge--warning', 'danger' => 'erp-badge--danger',
        'info' => 'erp-badge--info', 'primary' => 'erp-badge--primary', 'neutral' => 'erp-badge--neutral',
    ];
@endphp
<span {{ $attributes->class(['erp-badge', $tones[$tone] ?? $tones['neutral']]) }}>{{ $slot }}</span>
