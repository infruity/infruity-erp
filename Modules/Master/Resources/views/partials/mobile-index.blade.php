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
<div x-data="masterList(@js($visibleRecords), @js($formFields), @js($routeBase), @js($nameField), @js($subtitleFields), @js($pageTitle), @js($pageMode ?? 'ajax'), @js(request('q', '')))"
     x-init="if (new URLSearchParams(location.search).has('create')) openCreate()"
     x-effect="search; visibleCount = 50"
     @mobile-search-toggle.window="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $refs.mobileSearch?.focus())"
     @mobile-add-toggle.window="openCreate()"
     class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <div class="bg-white rounded-none lg:rounded-2xl lg:shadow-sm lg:border lg:border-gray-100 flex-1 flex flex-col min-h-0 overflow-hidden relative">
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
        <div class="hidden md:flex px-4 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 font-medium">Menampilkan <strong class="mx-1 text-gray-700" x-text="Math.min(visibleCount, filtered.length)"></strong> dari <strong class="mx-1 text-gray-700" x-text="filtered.length"></strong> {{ strtolower($pageTitle) }}</div>
        <div class="flex-1 overflow-y-auto overscroll-none pb-28 md:pb-0" @scroll.passive="scrolled = true; clearTimeout(scrollTimer); scrollTimer = setTimeout(() => scrolled = false, 700)">
            <div x-show="scrolled" x-cloak class="md:hidden sticky top-2 z-20 flex justify-center pointer-events-none"><span class="bg-white/95 backdrop-blur-md border border-gray-200 rounded-full px-4 py-1.5 shadow-lg text-[11px] text-gray-700 font-medium">Menampilkan <strong x-text="Math.min(visibleCount, filtered.length)"></strong> dari <strong x-text="filtered.length"></strong> {{ strtolower($pageTitle) }}</span></div>
            <template x-for="record in visible" :key="record.id">
                <div class="border-b border-gray-100">
                    <button type="button" @click="detail = record" class="w-full flex items-center gap-3 md:gap-4 px-5 py-3.5 md:py-4 hover:bg-gray-50/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600 text-left transition-colors" :aria-label="'Detail ' + label(record)">
                        <span class="w-9 h-9 md:w-10 md:h-10 rounded-xl border flex items-center justify-center shrink-0 font-bold text-sm" :class="avatarClass(record)" x-text="label(record).slice(0, 2).toUpperCase()"></span>
                        <span class="flex-1 min-w-0"><span class="block text-sm font-semibold text-gray-900 truncate" x-text="label(record)"></span><span class="block text-[11px] text-gray-400 mt-0.5 truncate" x-text="subtitle(record)"></span></span>
                    </button>
                </div>
            </template>
            <div x-show="filtered.length === 0" class="py-12 text-center"><div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3"><i class="ph ph-magnifying-glass text-gray-400 text-xl"></i></div><p class="text-sm text-gray-500 font-medium">{{ $pageTitle }} tidak ditemukan</p><p class="text-xs text-gray-400 mt-1">Coba ubah pencarian</p></div>
            <div x-show="visibleCount < filtered.length" class="px-5 py-4"><button type="button" @click="visibleCount += 50" class="w-full h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-500 hover:border-emerald-400 hover:text-emerald-700 hover:bg-emerald-50 flex items-center justify-center gap-2"><i class="ph ph-arrow-circle-down text-base"></i> Muat <span x-text="Math.min(50, filtered.length - visibleCount)"></span> {{ $pageTitle }} Lagi</button></div>
            @if ($records instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $records->hasPages())
                <div class="px-5 py-5 border-t border-gray-100">{{ $records->links() }}</div>
            @endif
        </div>
    </div>
    <div x-show="searchOpen" x-transition x-cloak class="md:hidden fixed left-0 right-0 z-[80] px-4 flex justify-center" style="bottom: calc(96px + env(safe-area-inset-bottom))"><div class="relative w-full max-w-[360px] h-12 bg-white/80 backdrop-blur-xl border border-white/70 rounded-full shadow-lg flex items-center"><i class="ph ph-magnifying-glass absolute left-4 text-[#0b595b]/70"></i><input x-ref="mobileSearch" x-model="search" @keydown.enter.prevent="if (mode === 'server') searchServer()" type="search" :placeholder="'Cari ' + title.toLowerCase() + '...'" class="w-full bg-transparent pl-11 pr-12 h-full text-sm font-medium text-gray-800 outline-none rounded-full"><button x-show="mode === 'server'" type="button" @click="searchServer()" class="absolute right-3 text-xs font-semibold text-[#0b595b]">Cari</button><button x-show="mode !== 'server'" type="button" @click="search = ''; $refs.mobileSearch.focus()" class="absolute right-3 text-gray-500" aria-label="Bersihkan pencarian"><i class="ph ph-x"></i></button></div></div>
    <template x-teleport="body"><div x-show="drawerOpen" x-cloak class="fixed inset-0 z-[100] flex flex-col justify-end lg:flex-row lg:justify-end" @keydown.escape.window="drawerOpen = false"><div class="absolute inset-0 bg-black/70" @click="drawerOpen = false"></div><div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full" class="relative w-full md:max-w-lg lg:max-w-sm mx-auto lg:mx-0 bg-white shadow-2xl flex flex-col max-h-[90vh] lg:max-h-full lg:h-full rounded-t-3xl lg:rounded-none overflow-hidden"><div class="flex justify-center pt-3 pb-1 lg:hidden"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div><div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"><div><h3 class="text-[15px] font-bold text-gray-900" x-text="(editing ? 'Edit ' : 'Tambah ') + '{{ $singular }}'"></h3><p class="text-[12px] text-gray-400 mt-0.5" x-text="editing ? 'Perbarui data {{ strtolower($singular) }}' : 'Isi data {{ strtolower($singular) }} baru'"></p></div><button type="button" @click="drawerOpen = false" class="w-9 h-9 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div><form @submit.prevent="save()" class="flex-1 min-h-0 flex flex-col"><div class="flex-1 overflow-y-auto bg-gray-50/60 px-5 py-5 space-y-5"><template x-for="field in fields" :key="field.key"><div x-show="fieldVisible(field)"><label :for="'master-field-' + field.key" class="text-xs font-semibold text-gray-600 mb-1.5 block"><span x-text="field.label"></span><span x-show="fieldRequired(field)" class="text-red-400"> *</span></label><template x-if="field.type === 'select'"><select :id="'master-field-' + field.key" x-model="form[field.key]" :required="fieldRequired(field)" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"><option value="" x-text="'Pilih ' + field.label"></option><template x-for="option in field.options" :key="option.value"><option :value="option.value" x-text="option.label"></option></template></select></template><template x-if="field.type === 'textarea'"><textarea :id="'master-field-' + field.key" x-model="form[field.key]" :required="fieldRequired(field)" rows="3" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-emerald-500"></textarea></template><template x-if="!['select', 'textarea'].includes(field.type)"><input :id="'master-field-' + field.key" x-model="form[field.key]" :type="field.type || 'text'" :required="fieldRequired(field)" :placeholder="'Masukkan ' + field.label.toLowerCase()" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"></template></div></template><p x-show="error" x-text="error" role="alert" class="text-xs text-red-600"></p></div><div class="px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100 flex items-center gap-3"><button type="button" @click="drawerOpen = false" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button><button type="submit" :disabled="saving" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold disabled:opacity-50" x-text="saving ? 'Menyimpan...' : (editing ? 'Simpan Perubahan' : 'Tambah {{ $singular }}')"></button></div></form></div></div></template>
    <template x-teleport="body">
        <div x-show="detail" x-cloak role="dialog" aria-modal="true" aria-label="Detail {{ $singular }}" class="fixed inset-0 z-[110] flex items-center justify-center px-4" @keydown.escape.window="detail = null">
            <div class="absolute inset-0 bg-black/60" @click="detail = null"></div>
            <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-xl overflow-hidden">
                <div class="flex items-center justify-between px-6 pt-6 pb-4"><h3 class="text-lg font-bold text-gray-800">Detail {{ $singular }}</h3><button type="button" @click="detail = null" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div>
                <template x-if="detail">
                    <div class="px-6 pb-6 space-y-5 text-sm max-h-[65vh] overflow-y-auto">
                        <div class="flex gap-3 items-center"><div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i class="ph ph-storefront text-xl"></i></div><div><div class="font-bold text-gray-800" x-text="label(detail)"></div><div class="text-[11px] text-gray-400" x-text="fields.find(field => field.key === nameKey)?.label || 'Nama'"></div></div></div>
                        <template x-for="field in fields.filter(field => field.key !== nameKey)" :key="field.key">
                            <div x-show="fieldValue(detail, field.key)" class="flex gap-3 items-center">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><i class="ph text-lg" :class="detailIcon(field.key)"></i></div>
                                <div class="min-w-0 flex-1">
                                    <template x-if="fieldHref(field, fieldValue(detail, field.key))"><a :href="fieldHref(field, fieldValue(detail, field.key))" target="_blank" rel="noopener noreferrer" class="block font-semibold text-emerald-700 break-words hover:underline" x-text="displayField(field, fieldValue(detail, field.key))"></a></template>
                                    <template x-if="!fieldHref(field, fieldValue(detail, field.key))"><div class="font-semibold text-gray-700 break-words" x-text="displayField(field, fieldValue(detail, field.key))"></div></template>
                                    <div class="text-[11px] text-gray-400" x-text="field.label"></div>
                                </div>
                            </div>
                        </template>
                        <div x-show="detail.updated_at" class="flex gap-3 items-center">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><i class="ph ph-clock text-lg"></i></div>
                            <div><div class="font-semibold text-gray-700" x-text="detail.updated_by_name || 'Terakhir diperbarui'"></div><div class="text-[11px] text-gray-400" x-text="formatUpdatedAt(detail.updated_at)"></div></div>
                        </div>
                    </div>
                </template>
                <div class="flex border-t border-gray-100">
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
</div>
@endsection

@push('scripts')
<script>
function masterList(records, fields, routeBase, nameKey, subtitleFields, title, mode, initialSearch = '') {
    return {
        records, fields, routeBase, nameKey, subtitleFields, title, mode,
        search: initialSearch, searchOpen: false, visibleCount: 50, scrolled: false, scrollTimer: null,
        drawerOpen: false, editing: null, detail: null, saving: false, error: '', form: {},
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
        displayField(field, value) {
            if (field.type === 'select') return field.options?.find(option => String(option.value) === String(value))?.label || value;
            return value;
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
        openCreate() { if (this.mode !== 'ajax') { window.location.href = `/${this.routeBase}/create`; return; } this.editing = null; this.form = Object.fromEntries(this.fields.map(field => [field.key, ''])); this.error = ''; this.drawerOpen = true; },
        openEdit(record) { if (this.mode !== 'ajax') { window.location.href = `/${this.routeBase}/${record.id}/edit`; return; } this.editing = record.id; this.form = Object.fromEntries(this.fields.map(field => [field.key, record[field.key] ?? ''])); this.error = ''; this.drawerOpen = true; },
        async save() { if (this.saving) return; this.saving = true; this.error = ''; try { const url = this.editing ? `/${this.routeBase}/${this.editing}` : `/${this.routeBase}`; const response = await fetch(url, { method: this.editing ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(this.form) }); const data = await response.json(); if (!response.ok || data.success === false) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Gagal menyimpan data.'); window.location.reload(); } catch (error) { this.error = error.message; this.saving = false; } },
        async confirmDelete(record) { const result = await Swal.fire({ title: `Hapus ${title}?`, text: `${this.label(record)} akan dihapus.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#ef4444' }); if (!result.isConfirmed) return; try { const response = await fetch(`/${this.routeBase}/${record.id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }); const data = await response.json(); if (!response.ok || data.success === false) throw new Error(data.message || 'Gagal menghapus data.'); window.location.reload(); } catch (error) { Swal.fire({ icon: 'error', title: 'Gagal', text: error.message }); } },
    };
}
</script>
@endpush
