{{--
    The single-choice dropdown of the HTML reference's forms (Jenis Kelamin, ...): a button with a caret that opens a
    short radio list, upward when there is little room below. The chosen value is posted through a hidden input.
        <x-erp.field label="Jenis Kelamin" for="gender"><x-erp.picker-select id="gender" name="gender" :options="[['value' => 'male', 'label' => 'Laki-laki'], ...]" :value="old('gender', $data->gender ?? '')" placeholder="Pilih Jenis Kelamin" /></x-erp.field>
    The button carries the id (the label focuses it), the hidden input carries the name.
--}}
@props(['id', 'name', 'options' => [], 'value' => '', 'placeholder' => 'Pilih'])
@php
    $pickerOptions = collect($options)->map(fn ($option) => ['value' => (string) data_get($option, 'value', ''), 'label' => (string) data_get($option, 'label', '')])->values();
@endphp
<div class="relative" x-data="{ value: @js((string) $value), options: @js($pickerOptions), pickerOpen: false, pickerDirection: 'down',
    get selectedLabel() { return this.options.find(option => option.value === this.value)?.label || ''; } }"
    @keydown.escape.window="pickerOpen = false">
    <input type="hidden" name="{{ $name }}" :value="value">
    <button type="button" id="{{ $id }}" aria-haspopup="listbox" :aria-expanded="pickerOpen"
        @click="const rect = $event.currentTarget.getBoundingClientRect(); pickerDirection = (window.innerHeight - rect.bottom) < 200 ? 'up' : 'down'; pickerOpen = !pickerOpen"
        class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 flex items-center justify-between text-sm transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none"
        :class="value ? 'text-gray-900' : 'text-gray-400'">
        <span x-text="selectedLabel || @js($placeholder)"></span>
        <i class="ph-bold ph-caret-down text-gray-400 shrink-0 transition-transform" :class="pickerOpen ? 'rotate-180' : ''" aria-hidden="true"></i>
    </button>

    <div x-show="pickerOpen" x-cloak style="display: none" class="fixed inset-0 z-[15]" @click="pickerOpen = false"></div>
    <div x-show="pickerOpen" x-cloak style="display: none" x-transition role="listbox"
        class="absolute z-20 w-full flex flex-col bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
        :class="pickerDirection === 'up' ? 'bottom-full mb-1' : 'mt-1'">
        <div class="max-h-32 overflow-y-auto overscroll-none scrollbar-hide">
            <template x-for="option in options" :key="option.value">
                <label class="flex items-center gap-3 px-3 py-2.5 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors last:border-b-0">
                    <input type="radio" :value="option.value" x-model="value" @change="pickerOpen = false" class="hidden">
                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center shrink-0 transition-colors" :class="value === option.value ? 'border-emerald-500' : ''">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 transition-transform duration-200" :class="value === option.value ? 'scale-100' : 'scale-0'"></div>
                    </div>
                    <span class="text-xs" :class="value === option.value ? 'font-bold text-gray-900' : 'font-medium text-gray-700'" x-text="option.label"></span>
                </label>
            </template>
        </div>
    </div>
</div>
