@extends('layouts.erp-tailwind')

@section('title', 'Kategori - Master')
@section('page-title', 'Kategori')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Kategori</span>
@endsection

@section('content')
<div x-data="categoryPage(@js($categories))"
     x-init="if (new URLSearchParams(location.search).has('create')) openCreate()"
     @mobile-search-toggle.window="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $refs.mobileSearch?.focus())"
     @mobile-add-toggle.window="openCreate()"
     class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <div class="bg-white rounded-none lg:rounded-2xl lg:shadow-sm lg:border lg:border-gray-100 flex-1 flex flex-col min-h-0 overflow-hidden">
        <div class="hidden md:flex gap-3 items-center p-4 border-b border-gray-100">
            <div class="relative flex-1">
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input x-model.debounce.200ms="search" type="search" placeholder="Cari kategori..."
                       class="w-full h-11 rounded-full border border-gray-100 bg-gray-50/50 pl-11 pr-4 text-[13px] outline-none focus:border-emerald-500">
            </div>
            @if (check_access('category.create'))
            <button type="button" @click="openCreate()" class="flex items-center gap-2 bg-[#0b595b] hover:bg-[#0a4e50] text-white rounded-full h-11 px-5 text-[13px] font-semibold shadow-sm">
                <i class="ph-bold ph-plus"></i> Tambah Kategori
            </button>
            @endif
        </div>
        <div class="hidden md:flex px-4 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 font-medium">
            Menampilkan <span class="mx-1 text-gray-700 font-semibold" x-text="Math.min(visibleCount, filtered.length)"></span> dari
            <span class="ml-1 text-gray-700 font-semibold" x-text="filtered.length"></span> kategori
        </div>
        <div class="flex-1 overflow-y-auto overscroll-none pb-28 md:pb-0">
            <template x-for="category in visible" :key="category.id">
                <div class="border-b border-gray-100 flex items-center gap-3 md:gap-4 px-4 md:px-5 py-3.5 md:py-4 hover:bg-gray-50/50">
                    <div class="w-9 h-9 md:w-12 md:h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i class="ph-fill ph-chart-pie-slice text-lg md:text-2xl"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-800 truncate" x-text="category.name"></div>
                        <div class="text-[11px] text-gray-400 mt-0.5 truncate" x-text="category.description || 'Kategori produk'"></div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        @if (check_access('category.edit'))
                        <button type="button" @click="openEdit(category)" class="w-8 h-8 rounded-lg text-gray-400 hover:text-emerald-700 hover:bg-emerald-50 flex items-center justify-center" :aria-label="'Edit ' + category.name"><i class="ph ph-pencil-simple"></i></button>
                        @endif
                        @if (check_access('category.destroy'))
                        <button type="button" @click="confirmDelete(category)" class="w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center" :aria-label="'Hapus ' + category.name"><i class="ph ph-trash"></i></button>
                        @endif
                    </div>
                </div>
            </template>
            <div x-show="filtered.length === 0" class="py-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3"><i class="ph ph-magnifying-glass text-gray-400 text-xl"></i></div>
                <p class="text-sm text-gray-500 font-medium">Kategori tidak ditemukan</p>
                <p class="text-xs text-gray-400 mt-1">Coba ubah pencarian</p>
            </div>
            <div x-show="visibleCount < filtered.length" class="px-5 py-4">
                <button type="button" @click="visibleCount += 20" class="w-full h-11 rounded-xl border-2 border-dashed border-gray-200 text-sm font-semibold text-gray-500 hover:border-emerald-400 hover:text-emerald-700 flex items-center justify-center gap-2"><i class="ph ph-arrow-circle-down text-base"></i> Muat Kategori Lagi</button>
            </div>
        </div>
    </div>

    <div x-show="searchOpen" x-transition x-cloak class="md:hidden fixed left-0 right-0 z-[80] px-4 flex justify-center" style="bottom: calc(96px + env(safe-area-inset-bottom))">
        <div class="relative w-full max-w-[360px] h-12 bg-white/80 backdrop-blur-xl border border-white/70 rounded-full shadow-lg flex items-center">
            <i class="ph ph-magnifying-glass absolute left-4 text-[#0b595b]/70"></i>
            <input x-ref="mobileSearch" x-model.debounce.200ms="search" type="search" placeholder="Cari kategori..." class="w-full bg-transparent pl-11 pr-10 h-full text-sm font-medium text-gray-800 outline-none rounded-full">
            <button type="button" @click="search = ''; $refs.mobileSearch.focus()" class="absolute right-3 text-gray-500" aria-label="Bersihkan pencarian"><i class="ph ph-x"></i></button>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-[100] flex flex-col justify-end lg:flex-row lg:justify-end" @keydown.escape.window="drawerOpen = false">
            <div class="absolute inset-0 bg-black/70" @click="drawerOpen = false"></div>
            <div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full" class="relative w-full md:max-w-lg lg:max-w-sm mx-auto lg:mx-0 bg-white shadow-2xl flex flex-col max-h-[90vh] lg:max-h-full lg:h-full rounded-t-3xl lg:rounded-none overflow-hidden">
                <div class="flex justify-center pt-3 pb-1 lg:hidden"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div>
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div><h3 class="text-[15px] font-bold text-gray-900" x-text="editing ? 'Edit Kategori' : 'Tambah Kategori'"></h3><p class="text-[12px] text-gray-400 mt-0.5" x-text="editing ? 'Perbarui data kategori' : 'Isi data kategori baru'"></p></div>
                    <button type="button" @click="drawerOpen = false" class="w-9 h-9 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Tutup"><i class="ph-bold ph-x"></i></button>
                </div>
                <form @submit.prevent="save()" class="flex-1 min-h-0 flex flex-col">
                    <div class="flex-1 overflow-y-auto bg-gray-50/60 px-5 py-5 space-y-5">
                        <div><label for="category-name" class="text-xs font-semibold text-gray-600 mb-1.5 block">Nama Kategori <span class="text-red-400">*</span></label><input id="category-name" x-model.trim="form.name" required maxlength="255" type="text" placeholder="Masukkan nama kategori" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></div>
                        <div><label for="category-description" class="text-xs font-semibold text-gray-600 mb-1.5 block">Deskripsi</label><textarea id="category-description" x-model.trim="form.description" maxlength="1000" rows="3" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></textarea></div>
                        <p x-show="error" x-text="error" role="alert" class="text-xs text-red-600"></p>
                    </div>
                    <div class="px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100 flex items-center gap-3">
                        <button type="button" @click="drawerOpen = false" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button>
                        <button type="submit" :disabled="saving" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold disabled:opacity-50" x-text="saving ? 'Menyimpan...' : (editing ? 'Simpan Perubahan' : 'Tambah Kategori')"></button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function categoryPage(categories) {
    return {
        categories, search: '', searchOpen: false, visibleCount: 20, drawerOpen: false, editing: null, saving: false, error: '',
        form: { name: '', description: '' },
        get filtered() { const q = this.search.trim().toLocaleLowerCase('id'); return this.categories.filter(category => [category.name, category.description].some(value => String(value || '').toLocaleLowerCase('id').includes(q))); },
        get visible() { return this.filtered.slice(0, this.visibleCount); },
        openCreate() { if (!@js(check_access('category.create'))) return; this.editing = null; this.form = { name: '', description: '' }; this.error = ''; this.drawerOpen = true; },
        openEdit(category) { this.editing = category.id; this.form = { name: category.name, description: category.description || '' }; this.error = ''; this.drawerOpen = true; },
        async save() {
            if (this.saving) return;
            this.saving = true; this.error = '';
            const url = this.editing ? `{{ url('/category') }}/${this.editing}` : `{{ route('category.store') }}`;
            try {
                const response = await fetch(url, { method: this.editing ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify(this.form) });
                const data = await response.json();
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Gagal menyimpan kategori.');
                window.location.reload();
            } catch (error) { this.error = error.message; this.saving = false; }
        },
        async confirmDelete(category) {
            const result = await Swal.fire({ title: 'Hapus Kategori?', text: `Kategori ${category.name} akan dihapus permanen.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#ef4444' });
            if (!result.isConfirmed) return;
            try {
                const response = await fetch(`{{ url('/category') }}/${category.id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const data = await response.json();
                if (!response.ok || data.success === false) throw new Error(data.message || 'Gagal menghapus kategori.');
                window.location.reload();
            } catch (error) { Swal.fire('Gagal', error.message, 'error'); }
        }
    };
}
</script>
@endpush
