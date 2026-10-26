@extends('layouts.erp-tailwind')

@section('title', $pageTitle . ' - Master')
@section('page-title', $pageTitle)
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>{{ $pageTitle }}</span>
@endsection

@section('content')
@php
    $visibleRecords = $records instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $records->items() : $records;
@endphp
<div x-data="masterList(@js($visibleRecords), @js($formFields), @js($routeBase), @js($nameField), @js($subtitleFields), @js($pageTitle), @js($pageMode ?? 'ajax'), @js(request('q', '')), @js($routeBase === 'supplier' ? ['province' => route('ajax.province'), 'city' => route('ajax.city'), 'district' => route('ajax.district'), 'village' => route('ajax.village')] : []), @js($routeBase === 'customers' ? ($customerCreateOpen ?? false) : false), @js($routeBase === 'customers' ? route('customers.index') : ''), @js($routeBase === 'customers' ? route('customers.create') : ''))"
     x-init="if (new URLSearchParams(location.search).has('create')) openCreate()"
     x-effect="search; visibleCount = 50"
     @mobile-add-toggle.window="openCreate()"
     @popstate.window="syncCustomerCreateUrl()"
     class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <x-erp.mobile-search class="md:hidden">
        <x-slot:input><input x-ref="mobileSearch" x-model="search" @keydown.enter.prevent="if (mode === 'server') searchServer()" type="search" :placeholder="'Cari ' + title.toLowerCase() + '...'" :aria-label="'Cari ' + title.toLowerCase()" class="erp-mobile-search__input"></x-slot:input>
        <x-slot:clear><button type="button" x-show="search" x-cloak @click="search = ''; if (mode === 'server') searchServer(); else $refs.mobileSearch.focus()" aria-label="Hapus pencarian" class="erp-mobile-search__clear"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button></x-slot:clear>
    </x-erp.mobile-search>
    <div class="erp-surface bg-white rounded-none lg:rounded-2xl lg:shadow-sm lg:border lg:border-gray-100 flex-1 flex flex-col min-h-0 overflow-hidden relative">
        <div class="hidden md:flex gap-3 items-center p-4 border-b border-gray-100">
            <div class="relative flex-1">
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input x-model="search" @keydown.enter.prevent="if (mode === 'server') searchServer()" type="search" :placeholder="'Cari ' + title.toLowerCase() + '...'" class="w-full h-11 rounded-full border border-gray-100 bg-gray-50/50 pl-11 pr-20 text-[13px] outline-none focus:border-emerald-500">
                <button x-show="mode === 'server'" type="button" @click="searchServer()" class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#0b595b]">Cari</button>
                <button x-show="mode !== 'server' && search" x-cloak type="button" @click="search = ''" class="absolute right-4 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100" aria-label="Bersihkan pencarian"><i class="ph ph-x"></i></button>
            </div>
            @if (check_access($routeBase . '.create'))
            <button type="button" @click="openCreate()" class="flex items-center gap-2 bg-[#0b595b] hover:bg-[#0a4e50] text-white rounded-full h-11 px-5 text-[13px] font-semibold shadow-sm"><i class="ph-bold ph-plus"></i> Tambah {{ $singular }}</button>
            @endif
        </div>
        <div class="hidden md:flex px-4 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 font-medium">
            @if (($pageMode ?? 'ajax') === 'server' && $records instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                Menampilkan <strong class="mx-1 text-gray-700">{{ $records->firstItem() }}–{{ $records->lastItem() }}</strong> dari <strong class="mx-1 text-gray-700">{{ $records->total() }}</strong> {{ strtolower($pageTitle) }}
            @else
                Menampilkan <strong class="mx-1 text-gray-700" x-text="Math.min(visibleCount, filtered.length)"></strong> dari <strong class="mx-1 text-gray-700" x-text="filtered.length"></strong> {{ strtolower($pageTitle) }}
            @endif
        </div>
        <div x-show="scrolled" x-cloak class="md:hidden absolute top-2 left-0 right-0 z-20 flex justify-center pointer-events-none">
            <span class="bg-white/95 backdrop-blur-md border border-gray-200 rounded-full px-4 py-1.5 shadow-lg text-[11px] text-gray-700 font-medium">
                @if (($pageMode ?? 'ajax') === 'server' && $records instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                    Menampilkan <strong>{{ $records->firstItem() }}–{{ $records->lastItem() }}</strong> dari <strong>{{ $records->total() }}</strong> {{ strtolower($pageTitle) }}
                @else
                    Menampilkan <strong x-text="Math.min(visibleCount, filtered.length)"></strong> dari <strong x-text="filtered.length"></strong> {{ strtolower($pageTitle) }}
                @endif
            </span>
        </div>
        <div class="mobile-list-edge-fade flex-1 overflow-y-auto overscroll-none pb-28 md:pb-0" @scroll.passive="scrolled = true; clearTimeout(scrollTimer); scrollTimer = setTimeout(() => scrolled = false, 700)">
            <template x-for="record in visible" :key="record.id">
                <div class="border-b border-gray-100">
                    <button type="button" @click="detail = record" class="w-full flex items-center gap-3 md:gap-4 px-5 py-3.5 md:py-4 hover:bg-gray-50/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600 text-left transition-colors" :aria-label="'Detail ' + label(record)">
                        <span class="w-9 h-9 md:w-10 md:h-10 rounded-xl border flex items-center justify-center shrink-0 font-bold text-sm" :class="avatarClass(record)" x-text="label(record).slice(0, 2).toUpperCase()"></span>
                        <span class="flex-1 min-w-0"><span class="block text-sm font-semibold text-gray-900 truncate" x-text="label(record)"></span><span class="block text-[11px] text-gray-400 mt-0.5 truncate" x-text="subtitle(record)"></span></span>
                        @if (!empty($trailingField ?? null))
                            <span class="w-24 shrink-0 text-right"><span class="block text-[12px] font-semibold text-gray-700" x-text="fieldValue(record, @js($trailingField))"></span><span class="block text-[10px] text-gray-400 mt-0.5">Masa Kerja</span></span>
                        @endif
                    </button>
                </div>
            </template>
            <div x-show="filtered.length === 0" class="py-12 text-center"><div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3"><i class="ph ph-magnifying-glass text-gray-400 text-xl"></i></div><p class="text-sm text-gray-500 font-medium">{{ $pageTitle }} tidak ditemukan</p><p class="text-xs text-gray-400 mt-1">Coba ubah pencarian</p></div>
            <div x-show="visibleCount < filtered.length" class="px-5 py-4"><x-erp.load-more @click="visibleCount += 50">Muat <span x-text="Math.min(50, filtered.length - visibleCount)"></span> {{ $pageTitle }} Lagi</x-erp.load-more></div>
            @if ($records instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $records->hasPages())
                <div class="md:hidden flex items-center justify-between gap-3 border-t border-gray-100 bg-white px-4 py-3">
                    <p class="min-w-0 text-xs text-gray-500" aria-live="polite">
                        <span class="font-semibold text-gray-700">{{ $records->firstItem() }}–{{ $records->lastItem() }}</span>
                        <span> dari {{ $records->total() }} {{ strtolower($pageTitle) }}</span>
                    </p>
                    <nav class="flex shrink-0 items-center gap-2" aria-label="Navigasi halaman {{ strtolower($pageTitle) }}">
                        @if ($records->previousPageUrl())
                            <a href="{{ $records->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-[#0b595b] transition-colors hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0b595b]">
                                <i class="ph-bold ph-caret-left text-sm" aria-hidden="true"></i><span>Kembali</span>
                            </a>
                        @else
                            <span aria-disabled="true" aria-label="Tidak ada halaman sebelumnya" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-gray-100 bg-gray-50 px-3 text-xs font-semibold text-gray-300">
                                <i class="ph-bold ph-caret-left text-sm" aria-hidden="true"></i><span>Kembali</span>
                            </span>
                        @endif
                        @if ($records->nextPageUrl())
                            <a href="{{ $records->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#0b595b] px-3 text-xs font-semibold text-white transition-colors hover:bg-[#0a4e50] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0b595b]">
                                <span>Selanjutnya</span><i class="ph-bold ph-caret-right text-sm" aria-hidden="true"></i>
                            </a>
                        @else
                            <span aria-disabled="true" aria-label="Tidak ada halaman berikutnya" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-gray-100 bg-gray-50 px-3 text-xs font-semibold text-gray-300">
                                <span>Selanjutnya</span><i class="ph-bold ph-caret-right text-sm" aria-hidden="true"></i>
                            </span>
                        @endif
                    </nav>
                </div>
                <div class="hidden border-t border-gray-100 px-5 py-5 md:block">{{ $records->links() }}</div>
            @endif
        </div>
    </div>
    <template x-teleport="body"><div x-show="drawerOpen" x-cloak data-erp-modal class="fixed inset-0 z-[100] flex flex-col justify-end lg:flex-row lg:justify-end" @keydown.escape.window="drawerOpen = false"><div class="absolute inset-0 bg-black/70" @click="drawerOpen = false"></div><div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full" class="relative w-full md:max-w-lg lg:max-w-sm mx-auto lg:mx-0 bg-white shadow-2xl flex flex-col max-h-[90dvh] lg:max-h-full lg:h-full rounded-t-3xl lg:rounded-none overflow-hidden"><div class="flex justify-center pt-3 pb-1 lg:hidden"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div><div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"><div><h3 class="text-[15px] font-bold text-gray-900" x-text="(editing ? 'Edit ' : 'Tambah ') + '{{ $singular }}'"></h3><p class="text-[12px] text-gray-400 mt-0.5" x-text="editing ? 'Perbarui data {{ strtolower($singular) }}' : 'Isi data {{ strtolower($singular) }} baru'"></p></div><button type="button" @click="drawerOpen = false" class="w-9 h-9 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div><form @submit.prevent="save()" class="flex-1 min-h-0 flex flex-col"><div class="flex-1 overflow-y-auto bg-gray-50/60 px-5 py-5 space-y-5"><template x-for="field in fields" :key="field.key"><div x-show="fieldVisible(field)"><label :for="'master-field-' + field.key" class="text-xs font-semibold text-gray-600 mb-1.5 block"><span x-text="field.label"></span><span x-show="fieldRequired(field)" class="text-red-400"> *</span></label><template x-if="field.type === 'region'"><div class="relative" @click.outside="regionOpen[field.key] = false"><button type="button" :id="'master-field-' + field.key" @click="toggleRegionPicker(field.key, $event.currentTarget)" :disabled="field.key === 'city' ? !form.province : field.key === 'district' ? !form.city : field.key === 'village' ? !form.district : false" :aria-expanded="!!regionOpen[field.key]" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 flex items-center justify-between text-sm transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 disabled:bg-gray-50 disabled:text-gray-400" :class="!form[field.key] ? 'text-gray-400' : 'text-gray-900'"><span x-text="regionNames[field.key] || ('Pilih ' + field.label)"></span><i class="ph-bold ph-caret-down text-gray-400 shrink-0 transition-transform" :class="regionOpen[field.key] ? 'rotate-180' : ''"></i></button><div x-show="regionOpen[field.key]" x-cloak x-transition class="absolute z-30 w-full flex flex-col bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden" :class="regionDirection[field.key] === 'up' ? 'bottom-full mb-1' : 'mt-1'"><div class="relative border-b border-gray-100 shrink-0"><i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i><input type="search" :aria-label="'Cari ' + field.label" :placeholder="'Cari ' + field.label.toLowerCase() + '...'" x-model="regionSearch[field.key]" @input="searchRegionOptions(field.key, $event.target.value)" @keydown.escape.stop="regionOpen[field.key] = false" class="w-full bg-transparent pl-9 pr-3 py-3 text-xs outline-none focus:bg-gray-50 transition-colors"></div><div class="max-h-32 overflow-y-auto overscroll-contain"><template x-for="option in regionOptions[field.key]" :key="option.id"><button type="button" @click="selectRegionOption(field.key, option)" class="w-full px-3 py-2.5 border-b border-gray-100 text-left text-xs font-medium text-gray-700 hover:bg-gray-50 last:border-b-0" x-text="option.name"></button></template><div x-show="regionLoaded[field.key] && regionOptions[field.key].length === 0" class="text-center py-4 text-xs text-gray-400">Wilayah tidak ditemukan</div></div></div></div></template><template x-if="field.type === 'select'"><select :id="'master-field-' + field.key" x-model="form[field.key]" :required="fieldRequired(field)" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"><option value="" x-text="'Pilih ' + field.label"></option><template x-for="option in field.options" :key="option.value"><option :value="option.value" x-text="option.label"></option></template></select></template><template x-if="field.type === 'textarea'"><textarea :id="'master-field-' + field.key" x-model="form[field.key]" :required="fieldRequired(field)" rows="3" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-emerald-500"></textarea></template><template x-if="!['select', 'textarea', 'region'].includes(field.type)"><input :id="'master-field-' + field.key" x-model="form[field.key]" :type="field.type || 'text'" :required="fieldRequired(field)" :placeholder="'Masukkan ' + field.label.toLowerCase()" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"></template></div></template><p x-show="error" x-text="error" role="alert" class="text-xs text-red-600"></p></div><div class="px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100 flex items-center gap-3"><button type="button" @click="drawerOpen = false" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button><button type="submit" :disabled="saving" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold disabled:opacity-50" x-text="saving ? 'Menyimpan...' : (editing ? 'Simpan Perubahan' : 'Tambah {{ $singular }}')"></button></div></form></div></div></template>
    <template x-teleport="body">
        <div x-show="detail" x-cloak data-erp-modal role="dialog" aria-modal="true" aria-label="Detail {{ $singular }}" class="fixed inset-0 z-[110] flex items-center justify-center px-4" @keydown.escape.window="detail = null">
            <div class="absolute inset-0 bg-black/60" @click="detail = null"></div>
            <div class="relative bg-white w-full max-w-sm max-h-[calc(100dvh-2rem)] rounded-2xl shadow-xl overflow-hidden flex flex-col">
                <div class="flex shrink-0 items-center justify-between px-6 pt-6 pb-4"><h3 class="text-lg font-bold text-gray-800">Detail {{ $singular }}</h3><button type="button" @click="detail = null" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div>
                <template x-if="detail">
                    @if ($routeBase === 'supplier')
                        <div class="min-h-0 overflow-y-auto px-6 pb-6 space-y-5 text-sm break-words">
                            <div class="flex gap-3 items-center"><i class="ph-duotone ph-storefront text-2xl text-emerald-500 shrink-0"></i><div><div class="font-bold text-gray-800" x-text="label(detail)"></div><div class="text-[11px] text-gray-400" x-text="fields.find(field => field.key === nameKey)?.label || 'Nama'"></div></div></div>
                            <template x-for="field in fields.filter(field => field.key !== nameKey)" :key="field.key">
                                <div x-show="fieldValue(detail, field.key)" class="flex gap-3 items-center">
                                    <i class="ph-duotone text-2xl text-emerald-500 shrink-0" :class="detailIcon(field.key)"></i>
                                    <div class="min-w-0 flex-1">
                                        <template x-if="fieldHref(field, fieldValue(detail, field.key))">
                                            <a :href="fieldHref(field, fieldValue(detail, field.key))" target="_blank" rel="noopener noreferrer" class="inline-flex max-w-full items-center gap-1.5 border-b border-dashed pb-0.5 font-semibold transition-colors hover:underline" :class="field.key === 'address' ? 'text-blue-600 border-blue-400 hover:text-blue-700' : 'text-emerald-700 border-emerald-400 hover:text-emerald-800'"><span class="truncate" x-text="displayField(field, fieldValue(detail, field.key), detail)"></span><i x-show="['pic_whatsapp', 'address'].includes(field.key)" class="ph-bold ph-arrow-up-right shrink-0 text-xs"></i></a>
                                        </template>
                                        <template x-if="!fieldHref(field, fieldValue(detail, field.key))"><div class="font-semibold text-gray-700 break-words" x-text="displayField(field, fieldValue(detail, field.key), detail)"></div></template>
                                        <div class="text-[11px] text-gray-400" x-text="field.label"></div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="detail.updated_at" class="flex gap-3 items-start"><i class="ph-duotone ph-clock mt-0.5 text-2xl text-emerald-500 shrink-0"></i><div class="min-w-0"><div class="text-xs font-bold text-gray-800 leading-snug" x-text="detail.updated_by_name || 'Terakhir diperbarui'"></div><div class="text-[11px] text-gray-400 mt-0.5" x-text="formatUpdatedAt(detail.updated_at)"></div></div></div>
                        </div>
                    @else
                        <div class="min-h-0 overflow-y-auto px-6 pb-6 space-y-5 text-sm break-words">
                            <div class="flex gap-3 items-center"><div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i class="ph ph-storefront text-xl"></i></div><div><div class="font-bold text-gray-800" x-text="label(detail)"></div><div class="text-[11px] text-gray-400" x-text="fields.find(field => field.key === nameKey)?.label || 'Nama'"></div></div></div>
                            <template x-for="field in fields.filter(field => field.key !== nameKey)" :key="field.key">
                                <div x-show="fieldValue(detail, field.key)" class="flex gap-3 items-center">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><i class="ph text-lg" :class="detailIcon(field.key)"></i></div>
                                    <div class="min-w-0 flex-1">
                                        <template x-if="fieldHref(field, fieldValue(detail, field.key))"><a :href="fieldHref(field, fieldValue(detail, field.key))" target="_blank" rel="noopener noreferrer" class="block font-semibold text-emerald-700 break-words hover:underline" x-text="displayField(field, fieldValue(detail, field.key), detail)"></a></template>
                                        <template x-if="!fieldHref(field, fieldValue(detail, field.key))"><div class="font-semibold text-gray-700 break-words" x-text="displayField(field, fieldValue(detail, field.key), detail)"></div></template>
                                        <div class="text-[11px] text-gray-400" x-text="field.label"></div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="detail.updated_at" class="flex gap-3 items-center">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><i class="ph ph-clock text-lg"></i></div>
                                <div><div class="font-semibold text-gray-700" x-text="detail.updated_by_name || 'Terakhir diperbarui'"></div><div class="text-[11px] text-gray-400" x-text="formatUpdatedAt(detail.updated_at)"></div></div>
                            </div>
                        </div>
                    @endif
                </template>
                <div class="flex shrink-0 border-t border-gray-100">
                    @if (check_access($routeBase . '.edit'))
                        <button type="button" @click="editDetail()" class="flex-1 h-12 text-sm font-semibold text-[#0b595b] hover:bg-emerald-50"><i class="ph ph-pencil-simple mr-1"></i> Edit</button>
                    @endif
                    @if (check_access($routeBase . '.destroy'))
                        <button type="button" @click="deleteDetail()" class="flex-1 h-12 text-sm font-semibold text-red-600 hover:bg-red-50 border-l border-gray-100"><i class="ph ph-trash mr-1"></i> Hapus</button>
                    @endif
                    @unless (check_access($routeBase . '.edit') || check_access($routeBase . '.destroy'))
                        <button type="button" @click="detail = null" class="w-full h-12 text-sm font-semibold text-gray-600">Tutup</button>
                    @endunless
                </div>
            </div>
        </div>
    </template>
    @if ($routeBase === 'customers' && !isset($editingCustomer) && check_access('customers.create'))
        @include('master::customer.create-overlay')
    @endif
</div>
@if ($routeBase === 'customers' && isset($editingCustomer))
    @include('master::customer.edit-overlay', ['data' => $editingCustomer])
@endif
@endsection

@push('scripts')
<script>
function masterList(records, fields, routeBase, nameKey, subtitleFields, title, mode, initialSearch = '', regionEndpoints = {}, initialCreateOpen = false, createIndexUrl = '', createPageUrl = '') {
    return {
        records, fields, routeBase, nameKey, subtitleFields, title, mode, regionEndpoints, createIndexUrl, createPageUrl,
        search: initialSearch, visibleCount: 50, scrolled: false, scrollTimer: null,
        drawerOpen: false, editing: null, detail: null, saving: false, error: '', form: {},
        customerCreateOpen: initialCreateOpen,
        createReturnUrl: initialCreateOpen ? createIndexUrl : `${window.location.pathname}${window.location.search}`,
        regionNames: {}, regionSearch: {}, regionOptions: {}, regionOpen: {}, regionDirection: {}, regionLoaded: {}, regionRequestId: {},
        get filtered() { if (this.mode === 'server') return this.records; const q = this.search.trim().toLocaleLowerCase('id'); return this.records.filter(record => Object.values(record).some(value => String(value ?? '').toLocaleLowerCase('id').includes(q))); },
        get visible() { return this.filtered.slice(0, this.visibleCount); },
        label(record) { return String(record[this.nameKey] || record.name || ''); },
        avatarClass(record) {
            const palette = ['bg-emerald-50 border-emerald-100 text-emerald-700', 'bg-blue-50 border-blue-100 text-blue-700', 'bg-amber-50 border-amber-100 text-amber-700', 'bg-rose-50 border-rose-100 text-rose-700', 'bg-violet-50 border-violet-100 text-violet-700'];
            return palette[Math.abs(Number(record.id) || this.label(record).length) % palette.length];
        },
        subtitle(record) { return this.subtitleFields.map(key => key.split('.').reduce((value, part) => value?.[part], record)).filter(Boolean).join(' • ') || record.code || ''; },
        fieldVisible(field) { return !field.visibleWhen || String(this.form[field.visibleWhen.key]) === String(field.visibleWhen.value); },
        fieldRequired(field) { return !!field.required || (!!field.requiredWhen && String(this.form[field.requiredWhen.key]) === String(field.requiredWhen.value)); },
        fieldValue(record, key) { return key.split('.').reduce((value, part) => value?.[part], record); },
        displayField(field, value, record = {}) {
            if (field.type === 'select') return field.options?.find(option => String(option.value) === String(value))?.label || value;
            if (field.type === 'region') return record[`${field.key}_name`] || value;
            return value;
        },
        regionParent(key) { return ({ city: 'province', district: 'city', village: 'district' })[key] || ''; },
        regionChildren(key) { return ({ province: ['city', 'district', 'village'], city: ['district', 'village'], district: ['village'] })[key] || []; },
        resetRegionPickerState(record = null) {
            const keys = ['province', 'city', 'district', 'village'];
            this.regionNames = Object.fromEntries(keys.map(key => [key, record?.[`${key}_name`] || '']));
            this.regionSearch = Object.fromEntries(keys.map(key => [key, '']));
            this.regionOptions = Object.fromEntries(keys.map(key => [key, []]));
            this.regionOpen = Object.fromEntries(keys.map(key => [key, false]));
            this.regionDirection = Object.fromEntries(keys.map(key => [key, 'down']));
            this.regionLoaded = Object.fromEntries(keys.map(key => [key, false]));
            this.regionRequestId = Object.fromEntries(keys.map(key => [key, (this.regionRequestId[key] || 0) + 1]));
        },
        resetRegionDescendants(key) {
            for (const child of this.regionChildren(key)) {
                this.form[child] = '';
                this.regionNames[child] = '';
                this.regionSearch[child] = '';
                this.regionOptions[child] = [];
                this.regionLoaded[child] = false;
                this.regionOpen[child] = false;
                this.regionRequestId[child] = (this.regionRequestId[child] || 0) + 1;
            }
        },
        toggleRegionPicker(key, trigger) {
            const parent = this.regionParent(key);
            if (parent && !this.form[parent]) return;
            this.regionOpen[key] = !this.regionOpen[key];
            if (!this.regionOpen[key]) return;
            this.regionSearch[key] = '';
            const rect = trigger.getBoundingClientRect();
            this.regionDirection[key] = window.innerHeight - rect.bottom < 250 && rect.top > 250 ? 'up' : 'down';
            this.searchRegionOptions(key, '');
        },
        async searchRegionOptions(key, query = '') {
            const endpoint = this.regionEndpoints[key];
            const parent = this.regionParent(key);
            const parentId = parent ? this.form[parent] : '';
            if (!endpoint || (parent && !parentId)) return;
            this.regionSearch[key] = query;
            this.regionOpen[key] = true;
            const requestId = (this.regionRequestId[key] || 0) + 1;
            this.regionRequestId[key] = requestId;
            this.regionLoaded[key] = false;
            const params = new URLSearchParams({ search: query.trim() });
            if (key === 'city') params.set('province_id', parentId);
            if (key === 'district') params.set('city_id', parentId);
            if (key === 'village') params.set('district_id', parentId);
            try {
                const response = await fetch(`${endpoint}?${params}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Gagal memuat wilayah.');
                const rows = await response.json();
                if (requestId !== this.regionRequestId[key] || query !== this.regionSearch[key] || parentId !== (parent ? this.form[parent] : '')) return;
                this.regionOptions[key] = rows;
                this.regionLoaded[key] = true;
            } catch (error) {
                if (requestId !== this.regionRequestId[key]) return;
                this.regionOptions[key] = [];
                this.regionLoaded[key] = true;
                this.error = error.message || 'Gagal memuat wilayah.';
            }
        },
        selectRegionOption(key, option) {
            this.form[key] = option.id;
            this.regionNames[key] = option.name;
            this.regionOpen[key] = false;
            this.regionSearch[key] = '';
            this.error = '';
            this.resetRegionDescendants(key);
        },
        detailIcon(key) {
            if (['pic_whatsapp', 'whatsapp', 'contact', 'phone'].includes(key)) return 'ph-phone';
            if (key === 'address') return 'ph-map-pin';
            if (key === 'email') return 'ph-envelope';
            if (key === 'description') return 'ph-note';
            return 'ph-info';
        },
        formatUpdatedAt(value) {
            const date = new Date(String(value).replace(' ', 'T'));
            return Number.isNaN(date.getTime()) ? '' : 'Memperbarui pada ' + new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
        },
        fieldHref(field, value) {
            if (!value) return '';
            if (['pic_whatsapp', 'whatsapp', 'contact', 'phone'].includes(field.key)) return 'https://wa.me/' + String(value).replace(/\D/g, '');
            if (field.key === 'address') return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(value);
            if (field.key === 'email') return 'mailto:' + value;
            return '';
        },
        editDetail() { const record = this.detail; this.detail = null; if (record) this.openEdit(record); },
        deleteDetail() { const record = this.detail; this.detail = null; if (record) this.confirmDelete(record); },
        searchServer() { const query = this.search.trim(); window.location.href = query ? `/${this.routeBase}?q=${encodeURIComponent(query)}` : `/${this.routeBase}`; },
        openCreate() {
            if (this.routeBase === 'customers') {
                if (!this.customerCreateOpen) this.createReturnUrl = `${window.location.pathname}${window.location.search}`;
                if (window.location.pathname !== new URL(this.createPageUrl, window.location.origin).pathname) {
                    window.history.pushState({}, '', this.createPageUrl);
                }
                this.customerCreateOpen = true;
                this.error = '';
                return;
            }
            if (this.mode !== 'ajax') { window.location.href = `/${this.routeBase}/create`; return; }
            this.editing = null;
            this.form = Object.fromEntries(this.fields.map(field => [field.key, '']));
            this.resetRegionPickerState();
            this.error = '';
            this.drawerOpen = true;
        },
        closeCustomerCreate() {
            this.customerCreateOpen = false;
            if (this.routeBase === 'customers' && window.location.pathname === new URL(this.createPageUrl, window.location.origin).pathname) {
                window.history.replaceState({}, '', this.createReturnUrl || this.createIndexUrl);
            }
        },
        syncCustomerCreateUrl() {
            if (this.routeBase !== 'customers') return;
            const createPath = new URL(this.createPageUrl, window.location.origin).pathname;
            if (window.location.pathname === createPath) {
                this.customerCreateOpen = true;
                return;
            }
            this.customerCreateOpen = false;
            this.createReturnUrl = `${window.location.pathname}${window.location.search}`;
        },
        openEdit(record) { if (this.mode !== 'ajax') { window.location.href = `/${this.routeBase}/${record.id}/edit`; return; } this.editing = record.id; this.form = Object.fromEntries(this.fields.map(field => [field.key, record[field.key] ?? ''])); this.resetRegionPickerState(record); this.error = ''; this.drawerOpen = true; },
        async save() { if (this.saving) return; this.saving = true; this.error = ''; try { const url = this.editing ? `/${this.routeBase}/${this.editing}` : `/${this.routeBase}`; const response = await fetch(url, { method: this.editing ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(this.form) }); const data = await response.json(); if (!response.ok || data.success === false) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Gagal menyimpan data.'); window.location.reload(); } catch (error) { this.error = error.message; this.saving = false; } },
        async confirmDelete(record) { const result = await Swal.fire({ title: `Hapus ${title}?`, text: `${this.label(record)} akan dihapus.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#ef4444' }); if (!result.isConfirmed) return; try { const response = await fetch(`/${this.routeBase}/${record.id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }); const data = await response.json(); if (!response.ok || data.success === false) throw new Error(data.message || 'Gagal menghapus data.'); window.location.reload(); } catch (error) { Swal.fire({ icon: 'error', title: 'Gagal', text: error.message }); } },
    };
}
</script>
@endpush
