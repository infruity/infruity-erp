@extends('layouts.erp-tailwind')

@section('title', 'Resep Produk - Master')
@section('page-title', 'Resep Produk')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Resep Produk</span>
@endsection

@section('content')
<div x-data="recipeIndex(@js($receipts->count()), @js($receipts->total()), @js($receipts->hasMorePages()), @js($receipts->nextPageUrl()), @js($showCreateForm ?? false))"
     x-init="Alpine.store('productFilters', { active: @js($type !== 'all' || $category !== 'all') })"
     @keydown.escape.window="closeRecipeDetail(); closeRecipeEditor(); closeRecipeCreate()"
     x-effect="document.body.classList.toggle('overflow-hidden', detailModalOpen || editOverlayOpen || recipeOverlayActive); document.body.classList.toggle('erp-hide-dock', recipeOverlayActive)"
     @mobile-add-toggle.window="openRecipeCreate()"
    class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <form x-data="{ mobileSearch: @js($search) }" action="{{ route('receipt.index') }}" method="GET" class="md:hidden">
        @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
        @if($category !== 'all')<input type="hidden" name="category" value="{{ $category }}">@endif
        <x-erp.mobile-search>
            <x-slot:input><input x-ref="search" x-model="mobileSearch" name="q" type="search" placeholder="Cari resep produk..." aria-label="Cari resep produk" class="erp-mobile-search__input"></x-slot:input>
            <x-slot:clear><button type="button" x-show="mobileSearch" x-cloak @click="mobileSearch = ''; $refs.search.focus()" aria-label="Hapus pencarian" class="erp-mobile-search__clear"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button></x-slot:clear>
            <x-slot:filter><button type="button" @click="filterOpen = true" aria-label="Filter resep" class="erp-mobile-search__filter {{ $category !== 'all' || $type !== 'all' ? 'is-active' : '' }}"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i></button></x-slot:filter>
        </x-erp.mobile-search>
    </form>
    <div class="relative erp-surface bg-white rounded-none lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm flex-1 flex flex-col min-h-0 overflow-hidden">
        <div class="hidden md:flex gap-3 items-center p-4 border-b border-gray-100">
            <form action="{{ route('receipt.index') }}" method="GET" class="relative flex-1">
                @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
                @if($category !== 'all')<input type="hidden" name="category" value="{{ $category }}">@endif
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input name="q" value="{{ $search }}" type="search" placeholder="Cari resep atau produk..." class="w-full h-11 rounded-full border border-gray-100 bg-gray-50/50 pl-11 pr-4 text-[13px] outline-none focus:border-emerald-500">
            </form>
            <form action="{{ route('receipt.index') }}" method="GET" class="w-48">
                @if($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
                @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
                <x-erp.dropdown-select id="receipt-category-desktop" name="category" :options="$categoryOptions" :value="$category === 'all' ? '' : $category" placeholder="Semua Kategori" auto-submit />
            </form>
            @if(check_access('product-receipt.create'))
                <a href="{{ route('receipt.create') }}" @click.prevent="openRecipeCreate()" class="h-11 px-5 rounded-full bg-[#0b595b] text-white text-[13px] font-semibold flex items-center gap-2"><i class="ph-bold ph-plus"></i> Tambah Resep</a>
            @endif
        </div>
        <div class="hidden md:block px-5 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-500">Menampilkan <span class="font-semibold text-gray-700" x-text="loadedCount"></span> dari <span class="font-semibold text-gray-700" x-text="totalCount"></span> resep</div>
        <div class="mobile-list-edge-fade flex-1 overflow-y-auto pb-28 md:pb-0">
            <div id="recipe-list-items">
                @include('transaction::receipt.partials.items', ['receipts' => $receipts])
            </div>
            @if($receipts->isEmpty())
                <div class="py-16 text-center text-gray-400"><i class="ph ph-receipt text-3xl"></i><p class="mt-2 text-sm">Resep produk tidak ditemukan</p></div>
            @endif
            <div x-show="hasMore" class="px-5 py-4">
                <button type="button" @click="loadMoreRecipes()" :disabled="loadingMore" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-200 text-sm font-semibold text-gray-500 transition-colors hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-wait disabled:opacity-60">
                    <i class="ph ph-arrow-circle-down text-base" :class="loadingMore ? 'animate-spin motion-reduce:animate-none' : ''"></i>
                    <span x-text="loadingMore ? 'Memuat resep...' : 'Muat Resep Lagi'"></span>
                </button>
            </div>
        </div>
    </div>
    <template x-teleport="body">
        <div x-show="filterOpen" x-cloak class="md:hidden fixed inset-0 z-[100] flex flex-col justify-end">
            <div class="absolute inset-0 bg-black/70" @click="filterOpen = false"></div>
            <div class="relative w-full overflow-visible rounded-t-3xl bg-white shadow-2xl pb-[env(safe-area-inset-bottom)]">
                <div class="flex justify-center pt-3 pb-1"><div class="w-16 h-1.5 rounded-full bg-gray-300"></div></div>
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="text-lg font-bold text-gray-800">Filter Resep</h3>
                    <button type="button" @click="filterOpen = false" aria-label="Tutup filter" class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500"><i class="ph-bold ph-x"></i></button>
                </div>
                <form action="{{ route('receipt.index') }}" method="GET">
                    @if($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
                    @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
                    <div class="px-5 py-6">
                        <label for="receipt-category-mobile-button" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-500">Kategori Produk</label>
                        <x-erp.dropdown-select id="receipt-category-mobile" name="category" :options="$categoryOptions" :value="$category === 'all' ? '' : $category" placeholder="Semua Kategori" menu-placement="top" />
                    </div>
                    <div class="flex gap-3 border-t border-gray-100 px-5 py-4">
                        <a href="{{ route('receipt.index', $search !== '' ? ['q' => $search] : []) }}" class="flex h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Reset</a>
                        <button type="submit" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white">Terapkan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <template x-teleport="body">
    <div>
    <x-erp.detail-dialog open="detailModalOpen" close="closeRecipeDetail()" title="Detail Resep" labelledby="recipe-detail-title" close-label="Tutup detail resep">
        <x-slot:subtitle><p class="mt-0.5 text-xs text-gray-400" x-text="selectedRecipe?.code || ''"></p></x-slot:subtitle>
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
        <x-slot:actions>
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
        </x-slot:actions>
    </x-erp.detail-dialog>

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
                <header class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
                    <div>
                        <h2 id="recipe-editor-title" class="text-lg font-bold text-gray-900">Edit Resep</h2>
                        <p class="mt-0.5 text-sm text-gray-400">Perbarui data resep</p>
                    </div>
                    <button type="button" @click="closeRecipeEditor()" aria-label="Tutup editor resep" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200"><i class="ph-bold ph-x text-xl"></i></button>
                </header>
                <form x-data="recipeForm(editingRecipe.product, editingRecipe.ingredients, editingRecipe.description)"
                      :action="`{{ url('receipt') }}/${editingRecipe.id}`" method="POST"
                      @submit="if (!productId || !ingredients.length || ingredients.some(item => !item.id || !item.quantity || Number(item.quantity) <= 0)) { $event.preventDefault(); error = 'Pilih produk dan lengkapi semua bahan serta kuantitasnya.'; }"
                      class="flex min-h-0 flex-1 flex-col">
                    @csrf @method('PUT')
                    <input type="hidden" name="description" :value="description">
                    <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto overscroll-contain bg-gray-50/40 px-5 pt-5 pb-3.5">
                        <div class="relative shrink-0">
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">Pilih Produk <span class="text-red-400">*</span></label>
                            <button type="button" @click="toggleProductPicker()" @keydown.escape.stop="closeProductPicker()" :aria-expanded="productOpen" :class="productOpen ? 'border-blue-600 ring-2 ring-blue-600' : ''" class="flex min-h-[48px] w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-left transition hover:border-gray-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600">
                                <span class="flex min-w-0 items-center gap-3"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-100 text-base" :class="productId ? selectedProductIconClass : 'bg-gray-100'"><span x-show="productId" x-text="selectedProductEmoji"></span><i x-show="!productId" class="ph ph-bag text-base text-gray-400"></i></span><span class="min-w-0"><span class="block truncate text-sm" :class="productId ? 'font-semibold text-gray-900' : 'font-normal text-gray-400'" x-text="productSearch || 'Pilih produk...'">Pilih produk...</span><span x-show="productId && selectedProductUnit" x-cloak class="mt-0.5 block text-[11px] text-gray-400" x-text="'Satuan hasil: ' + selectedProductUnit"></span></span></span>
                                <i class="ph-bold ph-caret-down ml-3 shrink-0 text-base text-gray-400 transition-transform" :class="productOpen ? 'rotate-180' : ''"></i>
                            </button>
                            <input type="hidden" name="product_id" :value="productId" required>
                            <div x-show="productOpen" x-cloak x-transition.origin.top @click.outside="closeProductPicker()" class="absolute left-0 top-full z-30 mt-1.5 flex w-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
                                <div class="relative shrink-0 border-b border-gray-100 bg-gray-50/50"><i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i><input x-model="productQuery" @input.debounce.250ms="lookupProduct()" @keydown.escape.stop="closeProductPicker()" autocomplete="off" placeholder="Cari produk..." class="w-full bg-transparent py-2.5 pl-9 pr-4 text-xs outline-none transition-colors focus:bg-white"></div>
                                <div class="max-h-52 divide-y divide-gray-50 overflow-y-auto overscroll-contain">
                                    <div x-show="productLoading" class="px-4 py-4 text-center text-xs text-gray-400">Memuat produk...</div>
                                    <template x-for="item in productResults" :key="item.id"><button type="button" @click="selectProduct(item)" :class="String(productId) === String(item.id) ? 'bg-emerald-50' : ''" class="flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left transition-colors hover:bg-emerald-50/50"><span class="flex min-w-0 flex-1 items-center gap-2.5"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-100 text-sm shadow-sm" :class="item.material_icon_class || 'bg-gray-100'" x-text="item.material_emoji || '📦'"></span><span class="truncate text-xs font-bold text-gray-900" x-text="item.name"></span></span><span class="flex shrink-0 items-center gap-2"><span class="text-xs font-medium text-gray-400" x-text="item.unit || 'unit'"></span><i x-show="String(productId) === String(item.id)" class="ph-bold ph-check text-sm text-emerald-600"></i></span></button></template>
                                    <p x-show="!productLoading && !productLookupError && productResults.length === 0" class="px-4 py-6 text-center text-xs text-gray-400">Produk tidak ditemukan</p>
                                    <p x-show="productLookupError" x-text="productLookupError" role="alert" class="px-4 py-4 text-center text-xs text-red-500"></p>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">Harga Pokok Produksi (HPP)</label>
                            <div class="flex h-11 w-full items-center gap-2.5 rounded-xl border border-gray-200 bg-gray-50 px-3.5"><span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-sm text-emerald-600"><i class="ph-duotone ph-coins"></i></span><span class="text-sm font-extrabold text-gray-900" x-text="'Rp ' + formatMoney(selectedProductHpp)">Rp 0</span><span class="text-[11px] text-gray-400" x-text="selectedProductUnit ? '/' + selectedProductUnit : '/porsi'"></span></div>
                        </div>
                        <section class="flex min-h-[220px] flex-1 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-3.5 shadow-sm">
                            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 pb-3"><div><h3 class="text-sm font-bold text-gray-800">Daftar Bahan Baku <span class="text-red-400">*</span></h3><p class="mt-1 text-xs text-gray-400" x-text="ingredients.length + ' bahan terdaftar'"></p></div><button type="button" @click="addIngredient()" class="flex h-10 shrink-0 items-center gap-1.5 rounded-xl bg-[#0b595b] px-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#084547] active:scale-95"><i class="ph-bold ph-plus"></i> Tambah</button></div>
                            <div x-show="ingredients.length === 0" class="flex flex-1 flex-col items-center justify-center py-8 text-center text-xs text-gray-400"><i class="ph ph-basket text-2xl"></i><p class="mt-2">Belum ada bahan baku.</p><button type="button" @click="addIngredient()" class="mt-1 font-semibold text-emerald-700">+ Tambah bahan</button></div>
                            <div x-show="ingredients.length > 0" class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto">
                                <template x-for="(ingredient, index) in ingredients" :key="ingredient.key"><div class="space-y-3 py-3">
                                    <div class="flex items-center justify-between"><span class="text-xs font-bold text-gray-700" x-text="'Bahan Baku #' + (index + 1)"></span><button x-show="ingredients.length > 1" type="button" @click="ingredients.splice(index, 1)" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-300 transition hover:bg-red-50 hover:text-red-500" :aria-label="'Hapus bahan ' + (index + 1)"><i class="ph-bold ph-x"></i></button></div>
                                    <div class="relative"><label class="mb-1.5 block text-xs font-semibold text-gray-600">Pilih Bahan <span class="text-red-400">*</span></label><input x-model="ingredient.search" @input.debounce.250ms="lookupIngredient(ingredient)" @focus="if (ingredient.results.length) ingredient.open = true" @keydown.escape="ingredient.open = false" autocomplete="off" placeholder="Cari bahan baku..." class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 pr-10 text-base outline-none focus:border-emerald-500 md:text-sm"><i class="ph ph-magnifying-glass absolute right-4 top-[calc(50%+0.55rem)] -translate-y-1/2 text-gray-400"></i><div x-show="ingredient.open" x-cloak @click.outside="ingredient.open = false" class="absolute left-0 right-0 top-full z-40 mt-1 max-h-48 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"><template x-for="item in ingredient.results" :key="item.id"><button type="button" @click="selectIngredient(ingredient, item)" class="w-full px-4 py-3 text-left text-sm text-emerald-900 hover:bg-emerald-50" x-text="item.name"></button></template><p x-show="ingredient.results.length === 0" class="px-4 py-3 text-center text-xs text-gray-400">Bahan tidak ditemukan</p></div></div>
                                    <input type="hidden" name="product_receipt_id[]" :value="ingredient.id" required>
                                    <label class="flex h-9 items-center rounded-xl border border-gray-200 bg-gray-100 px-2.5 focus-within:border-emerald-500 focus-within:bg-white"><span class="mr-2 text-xs font-semibold text-gray-500">Kuantitas</span><input name="ingredients_quantity[]" x-model="ingredient.quantity" type="number" step="0.01" min="0.01" required placeholder="0" class="min-w-0 flex-1 bg-transparent text-right text-sm font-bold text-gray-900 outline-none"><span class="pl-2 text-xs font-semibold text-gray-500" x-text="ingredient.unit || 'unit'"></span></label>
                                </div></template>
                            </div>
                        </section>
                        <p x-show="error" x-text="error" role="alert" class="text-xs text-red-600"></p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3 border-t border-gray-100 bg-white px-5 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                        <button type="button" @click="closeRecipeEditor()" class="flex h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button>
                        <button type="submit" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white">Simpan Perubahan</button>
                    </div>
                </form>
            </section>
        </div>
    </template>
    </div>
    </template>
    @include('transaction::receipt.create-form')
</div>
@endsection

@push('scripts')
<script>
function recipeIndex(initialCount, total, hasMore, nextPageUrl, createFormInitiallyOpen = false) {
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
        recipeOverlayActive: createFormInitiallyOpen,
        recipeOverlayVisible: createFormInitiallyOpen,
        recipeOverlayCloseTimer: null,
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
        openRecipeCreate() {
            window.clearTimeout(this.recipeOverlayCloseTimer);
            this.recipeOverlayVisible = true;
            this.$nextTick(() => { this.recipeOverlayActive = true; });
        },
        closeRecipeCreate() {
            this.recipeOverlayActive = false;
            window.clearTimeout(this.recipeOverlayCloseTimer);
            this.recipeOverlayCloseTimer = window.setTimeout(() => {
                if (!this.recipeOverlayActive) this.recipeOverlayVisible = false;
            }, 220);
            if (window.location.pathname === '{{ parse_url(route('receipt.create'), PHP_URL_PATH) }}') {
                window.history.replaceState(window.history.state, '', '{{ route('receipt.index') }}');
            }
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

function recipeForm(selectedProduct, initialIngredients, initialDescription = '') {
    let nextKey = 0;
    let productLookupSequence = 0;
    const unitOf = item => typeof item?.unit === 'string' ? item.unit : (item?.unit?.abbreviation || item?.unit?.name || '');
    return {
        productId: selectedProduct?.id || '',
        productSearch: selectedProduct?.name || '',
        productQuery: '',
        selectedProductUnit: unitOf(selectedProduct),
        selectedProductHpp: Number(selectedProduct?.hpp || 0),
        selectedProductEmoji: selectedProduct?.material_emoji || '📦',
        selectedProductIconClass: selectedProduct?.material_icon_class || 'bg-gray-100',
        productResults: [],
        productLoading: false,
        productLookupError: '',
        productOpen: false,
        description: initialDescription || '',
        ingredients: initialIngredients.map(item => ({
            key: ++nextKey,
            id: item.id,
            search: item.name,
            quantity: item.quantity,
            unit: unitOf(item),
            hpp: Number(item.hpp || 0),
            materialEmoji: item.material_emoji || '📦',
            materialIconClass: item.material_icon_class || 'bg-gray-100',
            results: [],
            open: false,
        })),
        error: '',
        formatMoney(value) { return Math.round(Number(value || 0)).toLocaleString('id-ID'); },
        toggleProductPicker() {
            if (this.productOpen) { this.closeProductPicker(); return; }
            this.productOpen = true;
            this.productQuery = '';
            this.productResults = [];
            this.productLookupError = '';
            this.lookupProduct();
        },
        closeProductPicker() {
            this.productOpen = false;
            productLookupSequence++;
            this.productLoading = false;
        },
        selectProduct(item) {
            this.productId = item.id;
            this.productSearch = item.name;
            this.selectedProductHpp = Number(item.hpp || 0);
            this.selectedProductUnit = unitOf(item);
            this.selectedProductEmoji = item.material_emoji || '📦';
            this.selectedProductIconClass = item.material_icon_class || 'bg-gray-100';
            this.closeProductPicker();
            this.error = '';
        },
        async lookupProduct() {
            const query = this.productQuery.trim();
            const requestId = ++productLookupSequence;
            this.productLoading = true;
            this.productLookupError = '';
            try {
                const response = await fetch(`{{ route('products.get-product-receipt') }}?search=${encodeURIComponent(query)}`, {headers: {'Accept': 'application/json'}});
                if (!response.ok) throw new Error('Gagal mencari produk.');
                const results = await response.json();
                if (requestId === productLookupSequence && query === this.productQuery.trim()) this.productResults = results.slice(0, 30);
            } catch (error) {
                if (requestId === productLookupSequence) this.productLookupError = 'Gagal memuat produk.';
            } finally {
                if (requestId === productLookupSequence) this.productLoading = false;
            }
        },
        addIngredient() {
            this.ingredients.push({key: ++nextKey, id: '', search: '', quantity: '', unit: '', hpp: 0, materialEmoji: '📦', materialIconClass: 'bg-gray-100', results: [], open: false});
        },
        selectIngredient(ingredient, item) {
            ingredient.id = item.id;
            ingredient.search = item.name;
            ingredient.unit = unitOf(item);
            ingredient.hpp = Number(item.hpp || 0);
            ingredient.materialEmoji = item.material_emoji || '📦';
            ingredient.materialIconClass = item.material_icon_class || 'bg-gray-100';
            ingredient.open = false;
            this.error = '';
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
