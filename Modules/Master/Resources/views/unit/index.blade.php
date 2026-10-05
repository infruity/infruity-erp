@extends('layouts.erp-tailwind')

@section('title', 'Satuan Produk - Master')
@section('page-title', 'Satuan Produk')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Satuan Produk</span>
@endsection

@section('content')
<div x-data="unitPage(@js($units))"
     x-init="if (new URLSearchParams(location.search).has('create')) openCreate()"
     x-effect="search; visibleCount = 50"
     @mobile-search-toggle.window="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $refs.mobileSearch?.focus())"
     @mobile-add-toggle.window="openCreate()"
     class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <div class="bg-white rounded-none lg:rounded-2xl lg:shadow-sm lg:border lg:border-gray-100 flex-1 flex flex-col min-h-0 overflow-hidden relative">
        <div class="hidden md:flex gap-3 items-center p-4 border-b border-gray-100">
            <div class="relative flex-1">
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input x-model.debounce.200ms="search" type="search" placeholder="Cari satuan..."
                       class="w-full h-11 rounded-full border border-gray-100 bg-gray-50/50 pl-11 pr-4 text-[13px] outline-none focus:border-emerald-500">
            </div>
            @if (check_access('unit.create'))
            <button type="button" @click="openCreate()" class="flex items-center gap-2 bg-[#0b595b] hover:bg-[#0a4e50] text-white rounded-full h-11 px-5 text-[13px] font-semibold shadow-sm">
                <i class="ph-bold ph-plus"></i> Tambah Satuan
            </button>
            @endif
        </div>

        <div class="hidden md:flex px-4 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 font-medium">
            Menampilkan <span class="mx-1 text-gray-700 font-semibold" x-text="Math.min(visibleCount, filtered.length)"></span> dari
            <span class="ml-1 text-gray-700 font-semibold" x-text="filtered.length"></span> satuan
        </div>

        <div class="flex-1 overflow-y-auto overscroll-none pb-28 md:pb-0" @scroll.passive="scrolled = true; clearTimeout(scrollTimer); scrollTimer = setTimeout(() => scrolled = false, 600)">
            <div x-show="scrolled" x-cloak class="md:hidden sticky top-2 z-20 flex justify-center pointer-events-none">
                <span class="bg-white/95 backdrop-blur-md border border-gray-200 rounded-full px-4 py-1.5 shadow-lg text-[11px] text-gray-700 font-medium">
                    Menampilkan <strong x-text="Math.min(visibleCount, filtered.length)"></strong> dari <strong x-text="filtered.length"></strong> satuan
                </span>
            </div>
            <template x-for="unit in visible" :key="unit.id">
                <div class="border-b border-gray-100">
                    <button type="button" @click="showDetail(unit)" class="w-full flex items-center gap-3 md:gap-4 px-4 md:px-5 py-3.5 md:py-4 hover:bg-gray-50/50 text-left transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600" :aria-label="'Detail ' + unit.name">
                        <span class="w-9 h-9 md:w-12 md:h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 font-bold text-xs md:text-base overflow-hidden">
                            <span x-text="unit.abbreviation?.slice(0, 3).toUpperCase()"></span>
                        </span>
                        <span class="flex-1 min-w-0 text-left">
                            <span class="block text-sm font-semibold text-gray-800 truncate" x-text="unit.name"></span>
                            <span class="block text-[11px] text-gray-400 mt-0.5 truncate" x-text="unit.description || 'Simbol (' + unit.abbreviation + ')'"></span>
                        </span>
                        <span class="hidden md:block text-sm font-semibold text-gray-700" x-text="unit.abbreviation"></span>
                    </button>
                </div>
            </template>
            <div x-show="filtered.length === 0" class="py-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3"><i class="ph ph-magnifying-glass text-gray-400 text-xl"></i></div>
                <p class="text-sm text-gray-500 font-medium">Satuan tidak ditemukan</p>
                <p class="text-xs text-gray-400 mt-1">Coba ubah pencarian</p>
            </div>
            <div x-show="visibleCount < filtered.length" class="px-5 py-4">
                <button type="button" @click="visibleCount += 50" class="w-full h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-500 hover:border-emerald-400 hover:text-emerald-700 flex items-center justify-center gap-2">
                    <i class="ph ph-arrow-circle-down text-base"></i> Muat <span x-text="Math.min(50, filtered.length - visibleCount)"></span> Satuan Lagi
                </button>
            </div>
        </div>
    </div>

    <div x-show="searchOpen" x-transition x-cloak class="md:hidden fixed left-0 right-0 z-[80] px-4 flex justify-center" style="bottom: calc(96px + env(safe-area-inset-bottom))">
        <div class="relative w-full max-w-[360px] h-12 bg-white/80 backdrop-blur-xl border border-white/70 rounded-full shadow-lg flex items-center">
            <i class="ph ph-magnifying-glass absolute left-4 text-[#0b595b]/70"></i>
            <input x-ref="mobileSearch" x-model.debounce.200ms="search" type="search" placeholder="Cari satuan..." class="w-full bg-transparent pl-11 pr-10 h-full text-sm font-medium text-gray-800 outline-none rounded-full">
            <button type="button" @click="search = ''; $refs.mobileSearch.focus()" class="absolute right-3 text-gray-500" aria-label="Bersihkan pencarian"><i class="ph ph-x"></i></button>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-[100] flex flex-col justify-end lg:flex-row lg:justify-end" @keydown.escape.window="drawerOpen = false">
            <div class="absolute inset-0 bg-black/70" @click="drawerOpen = false"></div>
            <div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full" class="relative w-full md:max-w-lg lg:max-w-sm mx-auto lg:mx-0 bg-white shadow-2xl flex flex-col max-h-[90vh] lg:max-h-full lg:h-full rounded-t-3xl lg:rounded-none overflow-hidden">
                <div class="flex justify-center pt-3 pb-1 lg:hidden"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div>
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div><h3 class="text-[15px] font-bold text-gray-900" x-text="editing ? 'Edit Satuan' : 'Tambah Satuan'"></h3><p class="text-[12px] text-gray-400 mt-0.5" x-text="editing ? 'Perbarui data satuan' : 'Isi data satuan baru'"></p></div>
                    <button type="button" @click="drawerOpen = false" class="w-9 h-9 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Tutup"><i class="ph-bold ph-x"></i></button>
                </div>
                <form @submit.prevent="save()" class="flex-1 min-h-0 flex flex-col">
                    <div class="flex-1 overflow-y-auto bg-gray-50/60 px-5 py-5 space-y-5">
                        <div><label for="unit-name" class="text-xs font-semibold text-gray-600 mb-1.5 block">Nama Satuan <span class="text-red-400">*</span></label><input id="unit-name" x-model.trim="form.name" required maxlength="255" type="text" placeholder="Masukkan nama satuan" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></div>
                        <div><label for="unit-abbreviation" class="text-xs font-semibold text-gray-600 mb-1.5 block">Simbol Satuan <span class="text-red-400">*</span></label><input id="unit-abbreviation" x-model.trim="form.abbreviation" required maxlength="255" type="text" placeholder="Masukkan simbol (contoh: kg, pcs)" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></div>
                        <div><label for="unit-description" class="text-xs font-semibold text-gray-600 mb-1.5 block">Deskripsi</label><textarea id="unit-description" x-model.trim="form.description" maxlength="1000" rows="3" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></textarea></div>
                        <p x-show="error" x-text="error" role="alert" class="text-xs text-red-600"></p>
                    </div>
                    <div class="px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100 flex items-center gap-3">
                        <button type="button" @click="drawerOpen = false" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button>
                        <button type="submit" :disabled="saving" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold disabled:opacity-50" x-text="saving ? 'Menyimpan...' : (editing ? 'Simpan Perubahan' : 'Tambah Satuan')"></button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="detail" x-cloak role="dialog" aria-modal="true" aria-label="Detail Satuan" class="fixed inset-0 z-[110] flex items-center justify-center px-4" @keydown.escape.window="detail = null">
            <div class="absolute inset-0 bg-black/60" @click="detail = null"></div>
            <div class="relative bg-white w-full max-w-sm rounded-2xl shadow-xl p-6">
                <div class="flex items-center justify-between mb-6"><h3 class="text-lg font-bold text-gray-800">Detail Satuan</h3><button @click="detail = null" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div>
                <template x-if="detail"><div class="space-y-5 text-sm"><div><div class="font-bold text-gray-800" x-text="detail.name"></div><div class="text-[11px] text-gray-400">Nama Satuan</div></div><div><div class="font-bold text-gray-800" x-text="detail.abbreviation"></div><div class="text-[11px] text-gray-400">Simbol</div></div><div x-show="detail.description"><div class="font-medium text-gray-700" x-text="detail.description"></div><div class="text-[11px] text-gray-400">Deskripsi</div></div></div></template>
                <div class="flex gap-2 mt-7 border-t border-gray-100 pt-4">
                    @if (check_access('unit.edit'))
                    <button type="button" @click="editDetail()" class="flex-1 h-10 rounded-xl bg-emerald-50 text-[#0b595b] text-sm font-semibold">Edit</button>
                    @endif
                    @if (check_access('unit.destroy'))
                    <button type="button" @click="deleteDetail()" class="flex-1 h-10 rounded-xl bg-red-50 text-red-600 text-sm font-semibold">Hapus</button>
                    @endif
                    @unless (check_access('unit.edit') || check_access('unit.destroy'))
                    <button type="button" @click="detail = null" class="w-full h-10 rounded-xl bg-gray-100 text-gray-600 text-sm font-semibold">Tutup</button>
                    @endunless
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function unitPage(units) {
    return {
        units, search: '', searchOpen: false, visibleCount: 50, scrolled: false, scrollTimer: null,
        drawerOpen: false, editing: null, detail: null, saving: false, error: '',
        form: { name: '', abbreviation: '', description: '' },
        get filtered() { const q = this.search.trim().toLocaleLowerCase('id'); return this.units.filter(unit => [unit.name, unit.abbreviation, unit.description].some(value => String(value || '').toLocaleLowerCase('id').includes(q))); },
        get visible() { return this.filtered.slice(0, this.visibleCount); },
        openCreate() { if (!@js(check_access('unit.create'))) return; this.editing = null; this.form = { name: '', abbreviation: '', description: '' }; this.error = ''; this.drawerOpen = true; },
        openEdit(unit) { this.editing = unit.id; this.form = { name: unit.name, abbreviation: unit.abbreviation, description: unit.description || '' }; this.error = ''; this.drawerOpen = true; },
        showDetail(unit) { this.detail = unit; },
        editDetail() { const unit = this.detail; this.detail = null; if (unit) this.openEdit(unit); },
        deleteDetail() { const unit = this.detail; this.detail = null; if (unit) this.confirmDelete(unit); },
        async save() {
            if (this.saving) return;
            this.saving = true; this.error = '';
            const url = this.editing ? `{{ url('/unit') }}/${this.editing}` : `{{ route('unit.store') }}`;
            try {
                const response = await fetch(url, { method: this.editing ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(this.form) });
                const data = await response.json();
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Gagal menyimpan satuan.');
                window.location.reload();
            } catch (error) { this.error = error.message; this.saving = false; }
        },
        async confirmDelete(unit) {
            const result = await Swal.fire({ title: 'Hapus Satuan?', text: `Satuan ${unit.name} akan dihapus permanen.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#ef4444' });
            if (!result.isConfirmed) return;
            try {
                const response = await fetch(`{{ url('/unit') }}/${unit.id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const data = await response.json();
                if (!response.ok || data.success === false) throw new Error(data.message || 'Gagal menghapus satuan.');
                window.location.reload();
            } catch (error) { Swal.fire('Gagal', error.message, 'error'); }
        }
    };
}
</script>
@endpush
