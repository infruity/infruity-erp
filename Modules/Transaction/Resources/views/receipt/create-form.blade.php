@extends('layouts.erp-tailwind')

@section('title', 'Tambah Resep Produk - Master')
@section('page-title', 'Resep Produk')
@section('dashboard-fullscreen', '1')

@section('content')
<template x-teleport="body">
<div x-data="recipeCreateForm()" x-init="document.body.classList.add('erp-hide-dock')" class="fixed inset-0 z-[130] flex flex-col justify-end overflow-hidden motion-reduce:transition-none lg:flex-row lg:justify-end">
    <div class="absolute inset-0 bg-black/60" @click="window.location.href = '{{ route('receipt.index') }}'"></div>
    <section class="relative z-10 mx-auto flex h-[85vh] max-h-[100dvh] w-full flex-col overflow-hidden rounded-t-3xl border-t border-gray-100 bg-white shadow-2xl motion-reduce:transition-none lg:mx-0 lg:h-full lg:max-w-sm lg:rounded-none lg:border-l lg:border-t-0">
    <div class="flex shrink-0 justify-center pt-3 pb-1 lg:hidden"><div class="h-1.5 w-16 rounded-full bg-gray-300"></div></div>
    <header class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Tambah Resep</h2>
            <p class="mt-0.5 text-sm text-gray-400">Isi data resep baru</p>
        </div>
        <a href="{{ route('receipt.index') }}" aria-label="Tutup form resep" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:bg-gray-200"><i class="ph-bold ph-x text-xl"></i></a>
    </header>

    <form action="{{ route('receipt.store') }}" method="POST" class="flex min-h-0 flex-1 flex-col" @submit="if (!productId || !ingredients.length || ingredients.some(item => !item.id || !item.quantity || Number(item.quantity) <= 0)) { $event.preventDefault(); error = 'Pilih produk dan lengkapi semua bahan serta kuantitasnya.'; }">
        @csrf
        <input type="hidden" name="description" value="{{ old('description') }}">
        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto overscroll-contain bg-gray-50/40 px-5 pt-5 pb-3.5">
            @if($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">{{ $errors->first() }}</div>@endif
            @if(session('error'))<div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">{{ session('error') }}</div>@endif

            <div class="shrink-0">
                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Pilih Produk <span class="text-red-400">*</span></label>
                <button type="button" @click="productOpen = !productOpen" :aria-expanded="productOpen" class="flex min-h-[48px] w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-left transition hover:border-gray-300 focus:border-emerald-500 focus:outline-none">
                    <span class="flex min-w-0 items-center gap-3"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-100 bg-rose-50 text-base text-rose-500"><i class="ph ph-package"></i></span><span class="min-w-0"><span class="block truncate text-sm font-semibold text-gray-900" x-text="productSearch || 'Pilih produk...'">Pilih produk...</span><span x-show="productId && selectedProductUnit" x-cloak class="mt-0.5 block text-[11px] text-gray-400" x-text="'Satuan hasil: ' + selectedProductUnit"></span></span></span>
                    <i class="ph-bold ph-caret-down ml-3 shrink-0 text-base text-gray-400 transition-transform" :class="productOpen ? 'rotate-180' : ''"></i>
                </button>
                <input type="hidden" name="product_id" :value="productId" required>
                <div x-show="productOpen" x-cloak @click.outside="productOpen = false" class="relative z-20 mt-2 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                    <div class="relative border-b border-gray-100 bg-gray-50/60"><i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i><input x-model="productQuery" @input.debounce.250ms="lookupProduct()" @keydown.escape="productOpen = false" autocomplete="off" placeholder="Cari produk..." class="h-11 w-full bg-transparent pl-10 pr-4 text-xs outline-none focus:bg-white"></div>
                    <div class="max-h-52 overflow-y-auto"><template x-for="item in productResults" :key="item.id"><button type="button" @click="selectProduct(item)" class="flex w-full items-center justify-between px-3.5 py-2.5 text-left text-xs text-gray-700 hover:bg-emerald-50"><span x-text="item.name"></span><i x-show="productId === item.id" class="ph-bold ph-check text-emerald-700"></i></button></template><p x-show="productQuery.length >= 2 && productResults.length === 0" class="px-4 py-3 text-xs text-gray-400">Produk tidak ditemukan</p><p x-show="productQuery.length < 2" class="px-4 py-3 text-xs text-gray-400">Ketik minimal 2 karakter untuk mencari.</p></div>
                </div>
            </div>

            <div class="shrink-0">
                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Harga Pokok Produksi (HPP)</label>
                <div class="flex h-11 w-full items-center gap-2.5 rounded-xl border border-gray-200 bg-gray-50 px-3.5"><span class="flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 text-sm text-emerald-600"><i class="ph-duotone ph-coins"></i></span><span class="text-sm font-extrabold text-gray-900" x-text="'Rp ' + formatMoney(totalHpp)">Rp 0</span><span class="text-[11px] text-gray-400" x-text="selectedProductUnit ? '/' + selectedProductUnit : '/porsi'"></span></div>
            </div>

            <section class="flex min-h-[220px] flex-1 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white p-3.5 shadow-sm">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 pb-3"><div><h3 class="text-sm font-bold text-gray-800">Daftar Bahan Baku <span class="text-red-400">*</span></h3><p class="mt-1 text-xs text-gray-400" x-text="ingredients.length + ' bahan terdaftar'"></p></div><button type="button" @click="openIngredientPicker()" class="flex h-10 shrink-0 items-center gap-1.5 rounded-xl bg-[#0b595b] px-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#084547] active:scale-95"><i class="ph-bold ph-plus"></i> Tambah</button></div>
                <div x-show="ingredients.length === 0" class="flex flex-1 flex-col items-center justify-center py-8 text-center text-xs text-gray-400"><i class="ph ph-basket text-2xl"></i><p class="mt-2">Belum ada bahan baku.</p><button type="button" @click="openIngredientPicker()" class="mt-1 font-semibold text-emerald-700">+ Tambah bahan</button></div>
                <div x-show="ingredients.length > 0" class="min-h-0 flex-1 divide-y divide-gray-100 overflow-y-auto">
                    <template x-for="(ingredient, index) in ingredients" :key="ingredient.key"><div class="flex items-center justify-between gap-3 py-2.5">
                        <div class="flex min-w-0 flex-1 items-center gap-2.5"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-base text-rose-500"><i class="ph ph-orange-slice"></i></span><span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-gray-900" x-text="ingredient.search"></span><span class="mt-0.5 block text-xs text-gray-400">Total Modal <strong class="ml-1 font-bold text-emerald-700" x-text="'Rp ' + formatMoney(ingredientCost(ingredient))"></strong></span></span></div>
                        <div class="flex shrink-0 items-center gap-1.5"><label class="flex h-9 items-center rounded-xl border border-gray-200 bg-gray-100 px-2.5 focus-within:border-emerald-500 focus-within:bg-white"><input type="text" inputmode="decimal" maxlength="9" :value="ingredient.displayQuantity" @input="updateQuantity(ingredient, $event)" aria-label="Kuantitas bahan" class="w-12 bg-transparent text-right text-sm font-bold text-gray-900 outline-none"><span class="pl-1 text-xs font-semibold text-gray-500" x-text="ingredient.unit || 'unit'"></span></label><button x-show="ingredients.length > 1" type="button" @click="removeIngredient(index)" :aria-label="'Hapus ' + ingredient.search" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-300 transition hover:bg-red-50 hover:text-red-500"><i class="ph-bold ph-x"></i></button></div>
                        <input type="hidden" name="product_receipt_id[]" :value="ingredient.id" required><input type="hidden" name="ingredients_quantity[]" :value="ingredient.quantity" required>
                    </div></template>
                </div>
            </section>
            <p x-show="error" x-text="error" role="alert" class="shrink-0 text-xs text-red-600"></p>
        </div>
        <div class="flex shrink-0 items-center gap-3 border-t border-gray-100 bg-white px-5 pt-3 pb-[calc(1rem+env(safe-area-inset-bottom))]"><a href="{{ route('receipt.index') }}" class="flex h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Batal</a><button type="submit" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white shadow-sm transition hover:bg-[#084547]">Tambah Resep</button></div>
    </form>

    <div x-show="ingredientPickerOpen" x-cloak class="fixed inset-0 z-[150] flex items-end justify-center overflow-hidden lg:items-stretch lg:justify-end"><div class="fixed inset-0 bg-black/50 backdrop-blur-[1px] transition-opacity duration-300" @click="ingredientPickerOpen = false"></div><section role="dialog" aria-modal="true" aria-labelledby="ingredient-picker-title" class="relative z-10 mx-auto flex h-[85vh] max-h-[100dvh] w-full flex-col overflow-hidden rounded-t-3xl border-t border-gray-100 bg-white shadow-2xl lg:mx-0 lg:h-full lg:max-w-sm lg:rounded-none lg:border-l lg:border-t-0">
        <div class="flex w-full shrink-0 cursor-pointer justify-center pt-3 pb-1 lg:hidden" @click="ingredientPickerOpen = false"><div class="h-1.5 w-16 rounded-full bg-gray-300"></div></div>
        <header class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-4"><div><h3 id="ingredient-picker-title" class="text-[15px] font-bold text-gray-900">Pilih Bahan Baku</h3><p class="mt-0.5 text-xs text-gray-400">Pilih bahan untuk racikan resep</p></div><button type="button" @click="ingredientPickerOpen = false" aria-label="Tutup pilihan bahan" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200 active:scale-95"><i class="ph-bold ph-x text-base"></i></button></header>
        <div class="min-h-0 flex-1 divide-y divide-gray-100/80 overflow-y-auto overscroll-contain bg-gray-50/30 px-4 py-2">
            <template x-for="item in ingredientResults" :key="item.id">
                <button type="button" @click="selectIngredient(item)" class="group flex w-full cursor-pointer items-center justify-between rounded-xl p-2.5 text-left transition-colors hover:bg-emerald-50/60">
                    <span class="flex min-w-0 flex-1 items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-100 text-base shadow-sm" :class="item.material_icon_class || 'bg-gray-100'"><span x-text="item.material_emoji || '📦'"></span></span><span class="flex min-w-0 flex-1 flex-col"><span class="truncate text-xs font-bold text-gray-900 transition-colors group-hover:text-emerald-800" x-text="item.name"></span><span class="mt-0.5 truncate text-[11px] text-gray-400" x-text="'Rp ' + formatMoney(item.hpp || 0) + ' / ' + (unitOf(item) || 'unit')"></span></span></span>
                    <span class="ml-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-emerald-200/60 bg-emerald-50 text-emerald-700 transition-all group-hover:border-emerald-600 group-hover:bg-emerald-600 group-hover:text-white active:scale-90"><i class="ph-bold ph-plus text-xs"></i></span>
                </button>
            </template>
            <div x-show="ingredientLoading" class="py-12 text-center text-xs text-gray-400">Memuat bahan baku...</div>
            <div x-show="!ingredientLoading && ingredientQuery.trim().length > 0 && ingredientResults.length === 0" class="py-16 text-center text-xs text-gray-400"><span class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400"><i class="ph ph-magnifying-glass text-lg"></i></span>Bahan tidak ditemukan</div>
            <div x-show="!ingredientLoading && ingredientQuery.trim().length === 0 && ingredientResults.length === 0" class="py-12 text-center text-xs text-gray-400">Belum ada bahan baku.</div>
        </div>

        <div class="shrink-0 border-t border-gray-100 bg-white px-4 pt-3 pb-[calc(0.875rem+env(safe-area-inset-bottom))] shadow-[0_-4px_16px_rgba(0,0,0,0.04)]"><div class="relative flex items-center"><i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i><input x-model="ingredientQuery" @input.debounce.250ms="lookupIngredient()" @keydown.escape="ingredientPickerOpen = false" autocomplete="off" placeholder="Cari bahan (misal: Stroberi, Susu)..." class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50/80 pl-9 pr-9 text-xs outline-none transition-all focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500"><button type="button" x-show="ingredientQuery.length > 0" @click="ingredientQuery = ''; lookupIngredient()" aria-label="Hapus pencarian bahan" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-0.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"><i class="ph-bold ph-x text-xs"></i></button></div></div>
    </section>
</div>
</template>
@endsection

@push('scripts')
<script>
function recipeCreateForm() {
    let nextKey = 0;
    const makeIngredient = item => {
        const quantity = String(item.quantity ?? '');
        return {key: ++nextKey, id: item.id || '', search: item.name || '', quantity, displayQuantity: quantity.replace('.', ','), hpp: Number(item.hpp || 0), unit: unitOf(item)};
    };
    return {
        productId: '', productSearch: '', productQuery: '', selectedProductUnit: '', productResults: [], productOpen: false,
        ingredients: [], ingredientPickerOpen: false, ingredientQuery: '', ingredientResults: [], ingredientLoading: false, error: '',
        get totalHpp() { return this.ingredients.reduce((total, item) => total + this.ingredientCost(item), 0); },
        formatMoney(value) { return Math.round(Number(value || 0)).toLocaleString('id-ID'); },
        ingredientCost(item) { return Number(item.quantity || 0) * Number(item.hpp || 0); },
        selectProduct(item) { this.productId = item.id; this.productSearch = item.name; this.selectedProductUnit = item.unit || ''; this.productOpen = false; this.error = ''; },
        async lookupProduct() {
            if (this.productQuery.trim().length < 2) { this.productResults = []; return; }
            const response = await fetch(`{{ route('products.get-product-receipt') }}?search=${encodeURIComponent(this.productQuery.trim())}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) { this.error = 'Gagal mencari produk.'; return; }
            this.productResults = (await response.json()).slice(0, 30);
        },
        openIngredientPicker() { this.ingredientQuery = ''; this.ingredientResults = []; this.ingredientPickerOpen = true; this.lookupIngredient(); },
        async lookupIngredient() {
            const query = this.ingredientQuery.trim();
            this.ingredientLoading = true;
            try {
                const response = await fetch(`{{ route('ajax.getProduct') }}?search=${encodeURIComponent(query)}&raw_materials=1`, {headers: {'Accept': 'application/json'}});
                if (!response.ok) throw new Error('Gagal mencari bahan.');
                const results = await response.json();
                if (query === this.ingredientQuery.trim()) this.ingredientResults = results.slice(0, 30);
            } catch (error) {
                if (query === this.ingredientQuery.trim()) this.error = 'Gagal mencari bahan.';
            } finally {
                if (query === this.ingredientQuery.trim()) this.ingredientLoading = false;
            }
        },
        selectIngredient(item) {
            if (this.ingredients.some(ingredient => String(ingredient.id) === String(item.id))) { this.error = 'Bahan ini sudah ada di resep.'; return; }
            const unit = unitOf(item);
            const quantity = ['kg', 'liter', 'l'].includes(unit.toLowerCase()) ? '0.1' : '1';
            this.ingredients.push(makeIngredient({id: item.id, name: item.name, quantity, hpp: item.hpp, unit}));
            this.ingredientPickerOpen = false; this.error = '';
        },
        updateQuantity(item, event) {
            const display = event.target.value.replace(',', '.').replace(/[^\d.]/g, '');
            const pieces = display.split('.');
            item.displayQuantity = event.target.value.replace(/[^\d,\.]/g, '');
            item.quantity = pieces.length > 2 ? pieces[0] + '.' + pieces.slice(1).join('') : display;
        },
        removeIngredient(index) { this.ingredients.splice(index, 1); this.error = ''; },
    };
}
function unitOf(item) { return typeof item?.unit === 'string' ? item.unit : (item?.unit?.abbreviation || item?.unit?.name || ''); }
</script>
@endpush
