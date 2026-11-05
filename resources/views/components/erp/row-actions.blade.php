@props(['actions' => [], 'label' => 'Aksi'])
@php
    // Item dengan can=false tidak dirender; bila semuanya disembunyikan, komponen tidak merender apa pun.
    $visible = array_values(array_filter($actions, fn ($a) => ($a['can'] ?? true)));
@endphp
@if (count($visible))
    <x-erp.dropdown :label="$label">
        <x-slot:trigger><i class="ph-bold ph-dots-three-vertical text-[16px]" aria-hidden="true"></i></x-slot:trigger>
        @foreach ($visible as $action)
            @php
                $tone = ($action['tone'] ?? null) === 'danger' ? 'erp-menu__item--danger' : '';
                $icon = $action['icon'] ?? null;
            @endphp
            @if (! empty($action['href']))
                <a role="menuitem" href="{{ $action['href'] }}" class="erp-menu__item {{ $tone }}"
                   @foreach (($action['data'] ?? []) as $k => $v) data-{{ $k }}="{{ $v }}" @endforeach>
                    @if ($icon)<i class="ph ph-{{ $icon }}" aria-hidden="true"></i>@endif{{ $action['label'] }}
                </a>
            @else
                <button type="button" role="menuitem" class="erp-menu__item {{ $tone }}"
                        @if (! empty($action['onclick'])) onclick="{{ $action['onclick'] }}" @endif
                        @foreach (($action['data'] ?? []) as $k => $v) data-{{ $k }}="{{ $v }}" @endforeach>
                    @if ($icon)<i class="ph ph-{{ $icon }}" aria-hidden="true"></i>@endif{{ $action['label'] }}
                </button>
            @endif
        @endforeach
    </x-erp.dropdown>
@endif
