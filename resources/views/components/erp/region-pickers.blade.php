{{--
    Provinsi -> Kabupaten / Kota -> Kecamatan -> Kelurahan in the HTML reference's style: a button that reads "Pilih ..."
    (grey until chosen) opens a short list with a search box and radio circles. A level stays locked until the one above
    has a value; choosing or changing a level empties the levels below. Values are posted as province, city, district
    and village. Options come from the ajax.province / ajax.city / ajax.district / ajax.village endpoints.
        <x-erp.region-pickers :values="['province' => 35, ...]" :names="['province' => 'JAWA TIMUR', ...]" />
    The master drawers (partials/mobile-index) follow the same rules; RegionPickerLogicTest pins both.
--}}
@props(['values' => [], 'names' => [], 'labels' => ['province' => 'Provinsi', 'city' => 'Kabupaten / Kota', 'district' => 'Kecamatan', 'village' => 'Kelurahan']])
@php
    $levels = ['province', 'city', 'district', 'village'];
    $initial = collect($levels)->mapWithKeys(fn ($key) => [$key => (string) ($values[$key] ?? '')])->all();
    $initialNames = collect($levels)->mapWithKeys(fn ($key) => [$key => (string) ($names[$key] ?? '')])->all();
@endphp
<div class="flex flex-col gap-5" data-region-pickers x-data="{
    labels: @js($labels),
    endpoints: { province: @js(route('ajax.province')), city: @js(route('ajax.city')), district: @js(route('ajax.district')), village: @js(route('ajax.village')) },
    parents: { city: 'province', district: 'city', village: 'district' },
    children: { province: ['city', 'district', 'village'], city: ['district', 'village'], district: ['village'] },
    values: @js($initial), names: @js($initialNames),
    options: { province: [], city: [], district: [], village: [] }, search: { province: '', city: '', district: '', village: '' },
    open: { province: false, city: false, district: false, village: false }, direction: { province: 'down', city: 'down', district: 'down', village: 'down' },
    loaded: { province: false, city: false, district: false, village: false }, requestId: { province: 0, city: 0, district: 0, village: 0 },
    locked(key) { const parent = this.parents[key]; return !!parent && !this.values[parent]; },
    toggle(key, trigger) {
        if (this.locked(key)) return;
        this.open[key] = !this.open[key];
        if (!this.open[key]) return;
        this.search[key] = '';
        const rect = trigger.getBoundingClientRect();
        this.direction[key] = (window.innerHeight - rect.bottom) < 250 && rect.top > 250 ? 'up' : 'down';
        this.load(key);
    },
    async load(key) {
        const id = ++this.requestId[key];
        const params = new URLSearchParams({ search: this.search[key].trim() });
        const parent = this.parents[key];
        if (parent) params.set({ city: 'province_id', district: 'city_id', village: 'district_id' }[key], this.values[parent]);
        try {
            const response = await fetch(this.endpoints[key] + '?' + params, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('region');
            const rows = await response.json();
            if (id !== this.requestId[key]) return;
            this.options[key] = rows;
        } catch (error) { if (id === this.requestId[key]) this.options[key] = []; }
        this.loaded[key] = true;
    },
    choose(key, option) {
        this.values[key] = String(option.id);
        this.names[key] = option.name;
        this.open[key] = false;
        (this.children[key] || []).forEach(child => { this.values[child] = ''; this.names[child] = ''; this.options[child] = []; this.loaded[child] = false; this.open[child] = false; this.requestId[child]++; });
    },
    selected(key, option) { return String(this.values[key]) === String(option.id); }
}">
    @foreach ($levels as $key)
        <div class="relative">
            <label for="region-{{ $key }}" class="text-xs font-semibold text-gray-600 mb-1.5 block">{{ $labels[$key] }}</label>
            <input type="hidden" name="{{ $key }}" :value="values['{{ $key }}']">
            <button type="button" id="region-{{ $key }}" aria-haspopup="listbox" :aria-expanded="open['{{ $key }}']" :disabled="locked('{{ $key }}')"
                @click="toggle('{{ $key }}', $event.currentTarget)"
                class="w-full h-11 bg-white border {{ isset($errors) && $errors->has($key) ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 flex items-center justify-between text-sm transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                :class="values['{{ $key }}'] ? 'text-gray-900' : 'text-gray-400'">
                <span class="truncate" x-text="names['{{ $key }}'] || 'Pilih {{ $labels[$key] }}'"></span>
                <i class="ph-bold ph-caret-down text-gray-400 shrink-0 transition-transform" :class="open['{{ $key }}'] ? 'rotate-180' : ''" aria-hidden="true"></i>
            </button>

            <div x-show="open['{{ $key }}']" x-cloak style="display: none" class="fixed inset-0 z-[15]" @click="open['{{ $key }}'] = false"></div>
            <div x-show="open['{{ $key }}']" x-cloak style="display: none" x-transition role="listbox"
                class="absolute z-20 w-full flex flex-col bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden"
                :class="direction['{{ $key }}'] === 'up' ? 'bottom-full mb-1' : 'mt-1'">
                <div class="relative border-b border-gray-100 shrink-0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" x-model="search['{{ $key }}']" @input.debounce.250ms="load('{{ $key }}')" @keydown.escape.stop="open['{{ $key }}'] = false" placeholder="Cari..." aria-label="Cari {{ strtolower($labels[$key]) }}" autocomplete="off"
                        class="w-full bg-transparent pl-8 pr-3 py-3 text-xs outline-none focus:bg-gray-50 transition-colors">
                </div>
                <div class="max-h-32 overflow-y-auto overscroll-none scrollbar-hide">
                    <template x-for="option in options['{{ $key }}']" :key="option.id">
                        <button type="button" role="option" :aria-selected="selected('{{ $key }}', option)" @click="choose('{{ $key }}', option)"
                            class="w-full flex items-center gap-3 px-3 py-2.5 border-b border-gray-100 text-left hover:bg-gray-50 transition-colors last:border-b-0">
                            <span class="w-4 h-4 rounded-full border-2 border-gray-300 flex items-center justify-center shrink-0 transition-colors" :class="selected('{{ $key }}', option) ? '!border-emerald-500' : ''">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 transition-transform duration-200" :class="selected('{{ $key }}', option) ? 'scale-100' : 'scale-0'"></span>
                            </span>
                            <span class="text-xs" :class="selected('{{ $key }}', option) ? 'font-bold text-gray-900' : 'font-medium text-gray-700'" x-text="option.name"></span>
                        </button>
                    </template>
                    <div x-show="loaded['{{ $key }}'] && options['{{ $key }}'].length === 0" class="text-center py-4 text-xs text-gray-400">Tidak ditemukan</div>
                </div>
            </div>
            @if (isset($errors) && $errors->has($key))<p class="mt-1 text-xs text-red-600">{{ $errors->first($key) }}</p>@endif
        </div>
    @endforeach
</div>
