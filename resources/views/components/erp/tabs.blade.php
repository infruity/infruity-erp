@props(['tabs' => [], 'active' => null])
@php($first = $active ?? ($tabs[0]['name'] ?? ''))
{{-- $tabs = [['name' => 'a', 'label' => 'Label'], ...]. Isi panel: <x-erp.tab-panel name="a">. --}}
<div x-data="{ active: @js($first), names: @js(array_column($tabs, 'name')),
        move(step) { const i = this.names.indexOf(this.active); this.active = this.names[(i + step + this.names.length) % this.names.length]; this.$nextTick(() => this.$refs['tab-' + this.active]?.focus()); } }"
     {{ $attributes }}>
    <div role="tablist" class="erp-tabs__list" @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)">
        @foreach ($tabs as $tab)
            <button type="button" role="tab" id="tab-{{ $tab['name'] }}" x-ref="tab-{{ $tab['name'] }}"
                    aria-controls="panel-{{ $tab['name'] }}" :aria-selected="(active === @js($tab['name'])).toString()"
                    :tabindex="active === @js($tab['name']) ? 0 : -1" @click="active = @js($tab['name'])" class="erp-tabs__tab">{{ $tab['label'] }}</button>
        @endforeach
    </div>
    {{ $slot }}
</div>
