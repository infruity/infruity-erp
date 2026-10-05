@extends('layouts.erp-tailwind')

@section('title', 'Resep Produk - Master')
@section('page-title', 'Resep Produk')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Resep Produk</span>
@endsection
@section('hide-mobile-header', '1')

@section('content')
<div x-data="recipeIndex(@js($receipts->count()), @js($receipts->total()), @js($receipts->hasMorePages()), @js($receipts->nextPageUrl()))"
     x-init="Alpine.store('productFilters', { active: @js($type !== 'all') })"
     @keydown.escape.window="closeRecipeDetail(); closeRecipeEditor()"
     x-effect="document.body.classList.toggle('overflow-hidden', detailModalOpen || editOverlayOpen)"
     @mobile-search-toggle.window="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $refs.search.focus())"
     @mobile-filter-toggle.window="filterOpen = !filterOpen; searchOpen = false"
     @mobile-add-toggle.window="window.location.href = '{{ route('receipt.create') }}'"
    class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <div class="relative bg-white rounded-none lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm flex-1 flex flex-col min-h-0 overflow-hidden">
        <div class="sm:hidden absolute inset-x-0 top-0 z-20 flex items-center justify-center border-b border-gray-200/50 bg-white/75 px-4 py-3.5 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.04)] backdrop-blur-md backdrop-saturate-150">
            <img src="{{ asset('images/infruity-wordmark.png') }}" alt="Infruity" class="h-[30px] max-w-[160px] w-auto object-contain">
        </div>
        <div class="hidden md:flex gap-3 items-center p-4 border-b border-gray-100">
            <form action="{{ route('receipt.index') }}" method="GET" class="relative flex-1">
                @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input name="q" value="{{ $search }}" type="search" placeholder="Cari resep atau produk..." class="w-full h-11 rounded-full border border-gray-100 bg-gray-50/50 pl-11 pr-4 text-[13px] outline-none focus:border-emerald-500">
            </form>
            <form action="{{ route('receipt.index') }}" method="GET">
                @if($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
                <select name="type" onchange="this.form.submit()" aria-label="Filter jenis produk" class="h-11 rounded-full border border-gray-100 bg-gray-50/50 px-4 text-[13px] text-gray-600 outline-none"><option value="all" @selected($type === 'all')>Semua Jenis</option><option value="product" @selected($type === 'product')>Produk</option><option value="kemasan" @selected($type === 'kemasan')>Kemasan</option></select>
            </form>
            @if(check_access('product-receipt.create'))
                <a href="{{ route('receipt.create') }}" class="h-11 px-5 rounded-full bg-[#0b595b] text-white text-[13px] font-semibold flex items-center gap-2"><i class="ph-bold ph-plus"></i> Tambah Resep</a>
            @endif
        </div>
        <div class="hidden md:block px-5 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-500">Menampilkan <span class="font-semibold text-gray-700" x-text="loadedCount"></span> dari <span class="font-semibold text-gray-700" x-text="totalCount"></span> resep</div>
        <div class="flex-1 overflow-y-auto pt-[59px] pb-28 sm:pt-0 md:pb-0">
            <div id="recipe-list-items">
                @include('transaction::receipt.partials.items', ['receipts' => $receipts])
            </div>
            @if($receipts->isEmpty())
                <div class="py-16 text-center text-gray-400"><i class="ph ph-receipt text-3xl"></i><p class="mt-2 text-sm">Resep produk tidak ditemukan</p></div>
            @endif
            <div x-show="hasMore" class="px-5 py-4">
                <button type="button" @click="loadMoreRecipes()" :disabled="loadingMore" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-200 text-sm font-semibold text-emerald-800 transition-colors hover:border-emerald-400 hover:bg-emerald-50 disabled:cursor-wait disabled:opacity-60">
                    <i class="ph ph-arrow-circle-down text-base" :class="loadingMore ? 'animate-spin motion-reduce:animate-none' : ''"></i>
                    <span x-text="loadingMore ? 'Memuat resep...' : 'Muat Resep Lagi'"></span>
                </button>
            </div>
        </div>
    </div>
    <form x-show="searchOpen" x-cloak action="{{ route('receipt.index') }}" method="GET" class="md:hidden fixed left-4 right-4 z-[80] h-12 bg-white/90 backdrop-blur-xl border border-gray-200 rounded-full shadow-lg flex items-center" style="bottom: calc(96px + env(safe-area-inset-bottom))">
        @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
        <i class="ph ph-magnifying-glass absolute left-4 text-[#0b595b]"></i>
        <input x-ref="search" name="q" value="{{ $search }}" type="search" placeholder="Cari resep produk..." class="w-full h-full bg-transparent pl-11 pr-12 text-sm outline-none rounded-full">
        <button type="submit" class="absolute right-3 text-xs font-semibold text-[#0b595b]">Cari</button>
    </form>
    <template x-teleport="body"><div x-show="filterOpen" x-cloak class="md:hidden fixed inset-0 z-[100] flex flex-col justify-end"><div class="absolute inset-0 bg-black/70" @click="filterOpen = false"></div><div class="relative w-full bg-white rounded-t-3xl shadow-2xl overflow-hidden pb-[env(safe-area-inset-bottom)]"><div class="flex justify-center pt-3 pb-1"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div><div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"><h3 class="font-bold text-gray-800 text-lg">Filter Resep</h3><button type="button" @click="filterOpen = false" aria-label="Tutup filter" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500"><i class="ph-bold ph-x"></i></button></div><form action="{{ route('receipt.index') }}" method="GET">@if($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif<div class="p-5"><label for="receipt-type" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Jenis Produk</label><select id="receipt-type" name="type" class="w-full h-11 rounded-xl border border-gray-200 px-4 text-sm"><option value="all" @selected($type === 'all')>Semua Jenis</option><option value="product" @selected($type === 'product')>Produk</option><option value="kemasan" @selected($type === 'kemasan')>Kemasan</option></select></div><div class="px-5 py-4 border-t border-gray-100 flex gap-3"><a href="{{ route('receipt.index', $search !== '' ? ['q' => $search] : []) }}" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 flex items-center justify-center">Reset</a><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold">Terapkan</button></div></form></div></div></template>

    <template x-teleport="body">
    <div>
    <div x-show="detailModalOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="recipe-detail-title" class="fixed inset-0 z-[120] flex items-center justify-center px-4">
        <div x-show="detailModalOpen" x-transition.opacity @click="closeRecipeDetail()" class="absolute inset-0 bg-black/60"></div>
        <section x-show="detailModalOpen" x-transition class="relative z-10 flex w-full max-w-sm flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex flex-1 flex-col p-6">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 id="recipe-detail-title" class="text-lg font-bold text-gray-800">Detail Resep</h2>
                        <p class="mt-0.5 text-xs text-gray-400" x-text="selectedRecipe?.code || ''"></p>
                    </div>
                    <button type="button" @click="closeRecipeDetail()" aria-label="Tutup detail resep" class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200"><i class="ph-bold ph-x"></i></button>
                </div>
                <div class="flex flex-col gap-5 text-sm" x-show="selectedRecipe">
                    <div class="flex items-center gap-3">
                        <i class="ph-duotone ph-tag shrink-0 text-2xl text-emerald-600"></i>
                        <div class="min-w-0">
                            <div class="font-bold leading-snug text-gray-800" x-text="selectedRecipe?.product?.name || 'Produk tidak ditemukan'"></div>
                            <div class="mt-0.5 text-[11px] font-medium text-gray-400">Nama Produk</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="ph-duotone ph-coins shrink-0 text-2xl text-emerald-600"></i>
                        <div>
                            <div class="font-bold text-gray-800" x-text="'Rp ' + Number(selectedRecipe?.product?.hpp || 0).toLocaleString('id-ID') + (selectedRecipe?.product?.unit ? ' / ' + selectedRecipe.product.unit : '')"></div>
                            <div class="text-[11px] font-medium text-gray-400">Harga Pokok Produksi</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="ph-duotone ph-clock mt-0.5 shrink-0 text-2xl text-emerald-600"></i>
                        <div class="min-w-0">
                            <div class="text-xs font-bold leading-snug text-gray-800">Terakhir diperbarui</div>
                            <div class="mt-0.5 text-[11px] font-medium text-gray-400" x-text="formatRecipeDate(selectedRecipe?.updatedAt)"></div>
                        </div>
                    </div>
                    <template x-if="selectedRecipe?.description">
                        <div class="rounded-xl bg-gray-50 px-3.5 py-3 text-xs leading-relaxed text-gray-600" x-text="selectedRecipe.description"></div>
                    </template>
                </div>
            </div>
            <div class="flex w-full border-t border-gray-100">
                @if(check_access('product-receipt.edit') && check_access('product-receipt.update'))
                    <button type="button" @click="openRecipeEditor()" class="flex flex-1 items-center justify-center gap-2 py-4 text-sm font-semibold text-[#0b595b] transition-colors hover:bg-emerald-50"><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                @endif
                @if(check_access('product-receipt.destroy'))
                    <div class="w-px bg-gray-100"></div>
                    <button type="button" @click="deleteSelectedRecipe()" class="flex flex-1 items-center justify-center gap-2 py-4 text-sm font-semibold text-red-500 transition-colors hover:bg-red-50"><i class="ph-bold ph-trash"></i> Hapus</button>
                @endif
                @if(!(check_access('product-receipt.edit') && check_access('product-receipt.update')) && !check_access('product-receipt.destroy'))
                    <button type="button" @click="closeRecipeDetail()" class="flex flex-1 items-center justify-center py-4 text-sm font-semibold text-gray-600">Tutup</button>
                @endif
            </div>
        </section>
    </div>

    <template x-if="editingRecipe">
        <div x-show="editOverlayOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-[130] flex flex-col justify-end overflow-hidden motion-reduce:transition-none lg:flex-row lg:justify-end" role="dialog" aria-modal="true" aria-labelledby="recipe-editor-title">
            <div x-show="editOverlayOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="closeRecipeEditor()" class="absolute inset-0 bg-black/70 motion-reduce:transition-none"></div>
            <section x-show="editOverlayOpen"
                     x-transition:enter="transform transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full"
                     x-transition:enter-end="translate-y-0 lg:translate-x-0"
                     x-transition:leave="transform transition ease-in duration-200"
                     x-transition:leave-start="translate-y-0 lg:translate-x-0"
                     x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full"
                     class="relative z-10 mx-auto flex h-[85vh] max-h-[100dvh] w-full flex-col overflow-hidden rounded-t-3xl border-t border-gray-100 bg-white shadow-2xl motion-reduce:transition-none lg:mx-0 lg:h-full lg:max-w-sm lg:rounded-none lg:border-l lg:border-t-0">
                <div class="flex shrink-0 justify-center pt-3 pb-1 lg:hidden" @click="closeRecipeEditor()"><div class="h-1.5 w-16 rounded-full bg-gray-300"></div></div>
                <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 id="recipe-editor-title" class="text-[15px] font-bold text-gray-900">Edit Resep</h2>
                        <p class="mt-0.5 text-xs text-gray-400">Perbarui data resep</p>
                    </div>
                    <button type="button" @click="closeRecipeEditor()" aria-label="Tutup editor resep" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200"><i class="ph-bold ph-x"></i></button>
                </div>
                <form x-data="recipeForm({ id: editingRecipe.product.id, name: editingRecipe.product.name }, editingRecipe.ingredients)"
                      :action="`{{ url('receipt') }}/${editingRecipe.id}`" method="POST"
                      @submit="if (!productId || !ingredients.length || ingredients.some(item => !item.id || !item.quantity || Number(item.quantity) <= 0)) { $event.preventDefault(); error = 'Pilih produk dan lengkapi semua bahan serta kuantitasnya.'; }"
                      class="flex min-h-0 flex-1 flex-col">
                    @csrf @method('PUT')
                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto bg-gray-50/60 px-5 py-5 pb-8">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">Produk Hasil <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <input x-model="productSearch" @input.debounce.250ms="lookupProduct()" @focus="if (productResults.length) productOpen = true" @keydown.escape="productOpen = false" autocomplete="off" placeholder="Cari produk yang membutuhkan resep..." class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 pr-10 text-sm outline-none focus:border-emerald-500">
                                <i class="ph ph-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <div x-show="productOpen" x-cloak @click.outside="productOpen = false" class="absolute z-40 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg">
                                    <template x-for="item in productResults" :key="item.id"><button type="button" @click="productId = item.id; productSearch = item.name; productOpen = false" class="w-full px-4 py-3 text-left text-sm text-emerald-900 hover:bg-emerald-50" x-text="item.name"></button></template>
                                    <p x-show="productResults.length === 0" class="px-4 py-3 text-xs text-gray-400">Produk tidak ditemukan</p>
                                </div>
                            </div>
                            <input type="hidden" name="product_id" :value="productId" required>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">Keterangan</label>
                            <textarea name="description" x-init="$el.value = editingRecipe.description || ''" rows="3" maxlength="255" placeholder="Catatan resep" class="w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500"></textarea>
                        </div>
                        <div class="flex items-center justify-between border-t border-gray-200 pt-5">
                            <div><h3 class="text-sm font-bold text-gray-800">Bahan Baku Resep</h3><p class="mt-0.5 text-xs text-gray-400">Tambahkan bahan dan jumlah yang digunakan</p></div>
                            <button type="button" @click="addIngredient()" class="ml-3 flex h-9 shrink-0 items-center gap-1 rounded-lg bg-emerald-50 px-3 text-xs font-semibold text-emerald-700"><i class="ph-bold ph-plus"></i> Tambah Bahan</button>
                        </div>
                        <template x-for="(ingredient, index) in ingredients" :key="ingredient.key">
                            <div class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between"><span class="text-xs font-bold text-gray-700" x-text="'Bahan Baku #' + (index + 1)"></span><button type="button" @click="ingredients.splice(index, 1)" class="h-8 w-8 rounded-lg text-red-500 hover:bg-red-50" :aria-label="'Hapus bahan ' + (index + 1)"><i class="ph ph-trash"></i></button></div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Pilih Bahan <span class="text-red-400">*</span></label>
                                    <div class="relative">
                                        <input x-model="ingredient.search" @input.debounce.250ms="lookupIngredient(ingredient)" @focus="if (ingredient.results.length) ingredient.open = true" @keydown.escape="ingredient.open = false" autocomplete="off" placeholder="Cari bahan baku..." class="h-11 w-full rounded-xl border border-gray-200 px-4 pr-10 text-sm outline-none focus:border-emerald-500">
                                        <i class="ph ph-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                        <div x-show="ingredient.open" x-cloak @click.outside="ingredient.open = false" class="absolute left-0 right-0 top-full z-40 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg">
                                        <template x-for="item in ingredient.results" :key="item.id"><button type="button" @click="ingredient.id = item.id; ingredient.search = item.name; ingredient.open = false" class="w-full px-4 py-3 text-left text-sm text-emerald-900 hover:bg-emerald-50" x-text="item.name"></button></template>
                                        <p x-show="ingredient.results.length === 0" class="px-4 py-3 text-xs text-gray-400">Bahan tidak ditemukan</p>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="product_receipt_id[]" :value="ingredient.id" required>
                                <div><label class="mb-1.5 block text-xs font-semibold text-gray-600">Kuantitas <span class="text-red-400">*</span></label><input name="ingredients_quantity[]" x-model="ingredient.quantity" type="number" step="0.01" min="0.01" required placeholder="Masukkan kuantitas" class="h-11 w-full rounded-xl border border-gray-200 px-4 text-sm outline-none focus:border-emerald-500"></div>
                            </div>
                        </template>
                        <p x-show="ingredients.length === 0" class="py-5 text-center text-xs text-gray-400">Belum ada bahan baku</p>
                        <p x-show="error" x-text="error" role="alert" class="text-xs text-red-600"></p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3 border-t border-gray-100 bg-white px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                        <button type="button" @click="closeRecipeEditor()" class="flex h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button>
                        <button type="submit" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white">Simpan Perubahan</button>
                    </div>
                </form>
            </section>
        </div>
    </template>
    </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function recipeIndex(initialCount, total, hasMore, nextPageUrl) {
    return {
        searchOpen: false,
        filterOpen: false,
        loadedCount: initialCount,
        totalCount: total,
        hasMore,
        nextPageUrl,
        loadingMore: false,
        detailModalOpen: false,
        editOverlayOpen: false,
        selectedRecipe: null,
        editingRecipe: null,
        openRecipeDetail(recipe) {
            this.selectedRecipe = recipe;
            this.detailModalOpen = true;
        },
        closeRecipeDetail() {
            this.detailModalOpen = false;
        },
        openRecipeEditor() {
            if (!this.selectedRecipe) return;
            this.editingRecipe = this.selectedRecipe;
            this.detailModalOpen = false;
            this.editOverlayOpen = true;
        },
        closeRecipeEditor() {
            this.editOverlayOpen = false;
            window.setTimeout(() => {
                if (!this.editOverlayOpen) this.editingRecipe = null;
            }, 300);
        },
        deleteSelectedRecipe() {
            if (!this.selectedRecipe) return;
            const id = this.selectedRecipe.id;
            this.detailModalOpen = false;
            deleteReceipt(id);
        },
        async loadMoreRecipes() {
            if (this.loadingMore || !this.hasMore || !this.nextPageUrl) return;

            this.loadingMore = true;
            try {
                const url = new URL(this.nextPageUrl, window.location.href);
                url.searchParams.set('partial', '1');
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!response.ok) throw new Error('Gagal memuat resep berikutnya.');

                const result = await response.json();
                const template = document.createElement('template');
                template.innerHTML = result.html.trim();

                const list = document.getElementById('recipe-list-items');
                const rows = Array.from(template.content.children);
                rows.forEach(row => {
                    list.appendChild(row);
                    Alpine.initTree(row);
                });

                this.loadedCount += result.count;
                this.hasMore = result.hasMore;
                this.nextPageUrl = result.nextPageUrl;
            } catch (error) {
                Swal.fire({icon: 'error', title: 'Gagal', text: error.message || 'Gagal memuat resep.'});
            } finally {
                this.loadingMore = false;
            }
        },
        formatRecipeDate(value) {
            if (!value) return 'Waktu pembaruan tidak tersedia';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return 'Waktu pembaruan tidak tersedia';
            return 'Diperbarui pada ' + new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short',
            }).format(date);
        },
    };
}

async function deleteReceipt(id) {
    const confirmed = await Swal.fire({title: 'Hapus resep?', text: 'Data resep akan dihapus permanen.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#ef4444'});
    if (!confirmed.isConfirmed) return;
    try {
        const response = await fetch(`{{ url('receipt') }}/${id}`, {method: 'DELETE', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}});
        const result = await response.json();
        if (!response.ok || result.success === false) throw new Error(result.message || 'Gagal menghapus resep.');
        window.location.reload();
    } catch (error) {
        Swal.fire({icon: 'error', title: 'Gagal', text: error.message});
    }
}

function recipeForm(selectedProduct, initialIngredients) {
    let nextKey = 0;
    return {
        productId: selectedProduct?.id || '',
        productSearch: selectedProduct?.name || '',
        productResults: [],
        productOpen: false,
        ingredients: initialIngredients.map(item => ({
            key: ++nextKey,
            id: item.id,
            search: item.name,
            quantity: item.quantity,
            results: [],
            open: false,
        })),
        error: '',
        addIngredient() {
            this.ingredients.push({key: ++nextKey, id: '', search: '', quantity: '', results: [], open: false});
        },
        async lookupProduct() {
            this.productId = '';
            if (this.productSearch.trim().length < 2) {
                this.productResults = [];
                this.productOpen = false;
                return;
            }
            const response = await fetch(`{{ route('products.get-product-receipt') }}?search=${encodeURIComponent(this.productSearch.trim())}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) {
                this.error = 'Gagal mencari produk.';
                return;
            }
            this.productResults = (await response.json()).slice(0, 30);
            this.productOpen = true;
        },
        async lookupIngredient(ingredient) {
            ingredient.id = '';
            if (ingredient.search.trim().length < 2) {
                ingredient.results = [];
                ingredient.open = false;
                return;
            }
            const response = await fetch(`{{ route('ajax.getProduct') }}?search=${encodeURIComponent(ingredient.search.trim())}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) {
                this.error = 'Gagal mencari bahan.';
                return;
            }
            ingredient.results = (await response.json()).slice(0, 30);
            ingredient.open = true;
        },
    };
}
</script>
@endpush
