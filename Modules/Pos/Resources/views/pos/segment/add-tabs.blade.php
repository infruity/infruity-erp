@php($tabs = ['buah' => 'Buah & Olahan', 'parcel' => 'Parcel', 'jus' => 'Jus'])
{{-- $locked: edit mode, only the item's own category stays usable (same as the reference). --}}
@php($locked = $locked ?? false)
<div class="pos-tabs" role="tablist" aria-label="Jenis item">
    <div class="pos-tabs__track">
        @foreach ($tabs as $key => $label)
            @php($isLocked = $locked && $key !== $active)
            <button type="button" role="tab" aria-selected="{{ $key === $active ? 'true' : 'false' }}"
                class="pos-tabs__tab {{ $key === $active ? 'is-active' : '' }} {{ $isLocked ? 'is-locked' : '' }}"
                @if ($isLocked) disabled title="Kategori terkunci saat mode edit"
                @elseif ($key !== $active) onclick="posSwitchAddTab('{{ $key }}', '{{ $active }}')" @endif>{{ $label }}</button>
        @endforeach
    </div>
</div>
