{{--
    Inline message box in the Infruity colours (the Tailwind replacement of Bootstrap's .alert).
        <x-erp.alert type="danger" title="Data belum dapat disimpan">Periksa isian yang ditandai.</x-erp.alert>
    type: info | success | warning | danger. dismissible lets the user close it.
--}}
@props(['type' => 'info', 'title' => null, 'dismissible' => false])
@php
    $styles = [
        'info' => ['box' => 'bg-blue-50 border-blue-200 text-blue-700', 'icon' => 'ph-info'],
        'success' => ['box' => 'bg-emerald-50 border-emerald-200 text-emerald-700', 'icon' => 'ph-check-circle'],
        'warning' => ['box' => 'bg-amber-50 border-amber-200 text-amber-700', 'icon' => 'ph-warning'],
        'danger' => ['box' => 'bg-red-50 border-red-200 text-red-700', 'icon' => 'ph-warning-circle'],
    ];
    $style = $styles[$type] ?? $styles['info'];
@endphp
<div {{ $attributes->class(['flex items-start gap-3 rounded-xl border px-4 py-3 text-[13px] leading-relaxed', $style['box']]) }}
    role="{{ in_array($type, ['danger', 'warning'], true) ? 'alert' : 'status' }}"
    @if ($dismissible) x-data="{ shown: true }" x-show="shown" @endif>
    <i class="ph-fill {{ $style['icon'] }} mt-0.5 shrink-0 text-base" aria-hidden="true"></i>
    <div class="min-w-0 flex-1">
        @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div @class(['break-words', 'mt-0.5' => $title])>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" @click="shown = false" class="-mr-1 shrink-0 rounded-md p-1 opacity-70 transition hover:opacity-100" aria-label="Tutup"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button>
    @endif
</div>
