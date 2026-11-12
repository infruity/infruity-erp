{{--
    The one status / tag pill of the ERP.
        <x-erp.badge status="selesai">Selesai</x-erp.badge>      tone from the status word (App\Support\StatusBadge)
        <x-erp.badge tone="primary">Toko GKB</x-erp.badge>        tone chosen by hand: success warning danger info primary neutral
        <x-erp.badge tone="danger" icon="ph-lock-simple">Locked</x-erp.badge>
    From PHP (DataTables columns): App\Support\StatusBadge::for($status) / ::html($label, $tone).
--}}
@props(['tone' => null, 'status' => null, 'icon' => null])
@php($resolved = in_array($tone, \App\Support\StatusBadge::TONES, true) ? $tone : \App\Support\StatusBadge::tone($status))
<span {{ $attributes->class(['erp-badge', 'erp-badge--' . $resolved]) }}>
    @if ($icon)<i class="ph {{ $icon }}" aria-hidden="true"></i>@endif{{ $slot }}
</span>
