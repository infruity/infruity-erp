@php($tabs = ['buah' => 'Buah & Olahan', 'parcel' => 'Parcel', 'jus' => 'Jus'])
<div class="pos-tabs" role="tablist" aria-label="Jenis item">
    @foreach ($tabs as $key => $label)
        <button type="button" role="tab" aria-selected="{{ $key === $active ? 'true' : 'false' }}"
            class="pos-tabs__tab {{ $key === $active ? 'is-active' : '' }}"
            @if ($key !== $active) onclick="posSwitchAddTab('{{ $key }}', '{{ $active }}')" @endif>{{ $label }}</button>
    @endforeach
</div>
