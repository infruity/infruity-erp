@props(['name' => '', 'src' => null, 'tone' => null, 'size' => 'md'])
@php
    $sizes = ['sm' => 'erp-avatar--sm', 'md' => 'erp-avatar--md', 'lg' => 'erp-avatar--lg'];
    $tones = [
        'success' => 'erp-avatar--success', 'warning' => 'erp-avatar--warning', 'danger' => 'erp-avatar--danger',
        'info' => 'erp-avatar--info', 'primary' => 'erp-avatar--primary', 'purple' => 'erp-avatar--purple',
    ];
    // Tanpa tone eksplisit, warna ditentukan dari nama (stabil) dari daftar tetap.
    $key = $tone ?: array_keys($tones)[abs(crc32((string) $name)) % count($tones)];
    $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2))));
@endphp
<span {{ $attributes->class(['erp-avatar', $sizes[$size] ?? $sizes['md'], $tones[$key] ?? '']) }} @if($name) title="{{ $name }}" @endif>
    @if ($src)<img src="{{ $src }}" alt="{{ $name }}" loading="lazy">@else{{ $initials !== '' ? $initials : '?' }}@endif
</span>
