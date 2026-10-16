@props([
    'id',
    'name',
    'options' => [],
    'value' => '',
    'placeholder' => 'Pilih kategori',
    'menuPlacement' => 'bottom',
    'autoSubmit' => false,
])

@php
    $dropdownOptions = collect($options)
        ->map(fn ($option) => [
            'value' => (string) data_get($option, 'value', ''),
            'label' => (string) data_get($option, 'label', ''),
        ])
        ->prepend(['value' => '', 'label' => $placeholder])
        ->values();
@endphp

<div
    id="{{ $id }}"
    x-data="{
        open: false,
        value: @js((string) $value),
        options: @js($dropdownOptions),
        autoSubmit: @js((bool) $autoSubmit),
        get selectedOption() {
            return this.options.find((option) => option.value === this.value) || this.options[0];
        },
        select(option) {
            this.value = option.value;
            this.open = false;
            if (this.autoSubmit) this.$nextTick(() => this.$el.closest('form')?.requestSubmit());
        },
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative w-full"
>
    <input type="hidden" name="{{ $name }}" :value="value">
    <button
        id="{{ $id }}-button"
        type="button"
        :aria-label="selectedOption?.label || @js($placeholder)"
        aria-haspopup="menu"
        aria-controls="{{ $id }}-options"
        :aria-expanded="open"
        @click="open = !open"
        @keydown.arrow-down.prevent="open = true"
        class="flex h-12 w-full items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 text-left text-sm font-medium text-gray-800 outline-none transition-colors hover:border-gray-300 focus-visible:border-emerald-500 focus-visible:ring-2 focus-visible:ring-emerald-100"
    >
        <span class="min-w-0 truncate" x-text="selectedOption?.label || @js($placeholder)"></span>
        <i class="ph ph-caret-down shrink-0 text-base text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
    </button>

    <div
        id="{{ $id }}-options"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-120"
        x-transition:enter-start="translate-y-1 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-1 opacity-0"
        role="menu"
        aria-label="{{ $placeholder }}"
        @class([
            'absolute left-0 right-0 z-[130] overflow-y-auto rounded-2xl border border-gray-100 bg-white p-1.5 shadow-xl',
            'max-h-[min(30vh,12rem)]' => $menuPlacement === 'top',
            'max-h-[min(40vh,15rem)]' => $menuPlacement !== 'top',
            'bottom-full mb-2' => $menuPlacement === 'top',
            'top-full mt-2' => $menuPlacement !== 'top',
        ])
    >
        <template x-for="option in options" :key="option.value">
            <button
                type="button"
                role="menuitemradio"
                :aria-checked="value === option.value"
                @click="select(option)"
                class="flex w-full items-center justify-between gap-3 rounded-xl px-3.5 py-3 text-left text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200"
                :class="value === option.value ? 'bg-emerald-50 font-semibold text-[#0b595b]' : 'text-[#243041] hover:bg-gray-50'"
            >
                <span class="min-w-0 truncate" x-text="option.label"></span>
                <i x-show="value === option.value" class="ph-bold ph-check shrink-0 text-sm text-[#0b595b]" aria-hidden="true"></i>
            </button>
        </template>
    </div>
</div>
