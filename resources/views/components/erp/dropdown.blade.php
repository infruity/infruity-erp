@props([
    'label' => 'Menu',
    'align' => 'end',
    'triggerClass' => 'erp-action erp-action--icon',
])
@php($placement = $align === 'start' ? 'bottom-start' : 'bottom-end')
{{-- Menu aksi. Panel di-teleport ke <body> supaya tidak terpotong overflow tabel dan naik otomatis bila mepet bawah.
     Item memakai role="menuitem" dan class erp-menu__item (lihat x-erp.row-actions). --}}
<div x-data="erpDropdown" class="relative inline-block" @keydown.escape.window="close(true)">
    <button type="button" x-ref="trigger" @click="toggle()" @keydown.arrow-down.prevent="show()"
            :aria-expanded="open.toString()" aria-haspopup="menu" aria-label="{{ $label }}" {{ $attributes->class([$triggerClass]) }}>
        {{ $trigger ?? '' }}
    </button>
    <template x-teleport="body">
        <div x-show="open" x-cloak x-ref="menu" x-anchor.{{ $placement }}.offset.6="$refs.trigger"
             @click.outside="close()" @click="close()"
             @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
             @keydown.tab="close()" x-transition.opacity.duration.100ms
             role="menu" aria-label="{{ $label }}" class="erp-menu">
            {{ $slot }}
        </div>
    </template>
</div>
