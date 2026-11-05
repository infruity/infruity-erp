@props(['actions' => [], 'label' => 'Aksi', 'copies' => false])
@php
    // Item dengan can=false tidak dirender; bila semuanya disembunyikan, komponen tidak merender apa pun.
    $visible = array_values(array_filter($actions, fn ($a) => ($a['can'] ?? true)));
@endphp
@if (count($visible))
    <span data-erp-row-actions class="contents">
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
    @if ($copies)
        {{-- Salinan tersembunyi untuk daftar kartu mobile (public/assets/js/erp-collections.js): panel menu di-teleport ke <body>, jadi tidak ada di dalam baris. --}}
        <span hidden data-erp-action-copies>
            @foreach ($visible as $action)
                @if (! empty($action['href']))
                    <a href="{{ $action['href'] }}">{{ $action['label'] }}</a>
                @else
                    <button type="button" @if (! empty($action['onclick'])) onclick="{{ $action['onclick'] }}" @endif>{{ $action['label'] }}</button>
                @endif
            @endforeach
        </span>
    @endif
    </span>
@endif
