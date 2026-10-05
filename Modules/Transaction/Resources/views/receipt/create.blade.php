@extends('layouts.erp-tailwind')

@section('title', (isset($data) ? 'Edit' : 'Tambah') . ' Resep Produk - Master')
@section('page-title', 'Resep Produk')
@section('dashboard-fullscreen', '1')

@section('content')
@php
    $editing = isset($data);
    $selected = $selectedProduct ?? null;
    $initialIngredients = isset($production_detail)
        ? $production_detail->map(fn ($item) => [
            'id' => $item->product_receipt_id,
            'name' => $item->ingredients?->name ?? '',
            'quantity' => $item->quantity,
        ])->values()->all()
        : [];
@endphp
<div x-data="recipeForm(@js($selected ? ['id' => $selected->id, 'name' => $selected->name] : null), @js($initialIngredients))" class="flex-1 min-h-0 flex flex-col bg-white lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3 shrink-0"><a href="{{ route('receipt.index') }}" aria-label="Kembali ke resep produk" class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center"><i class="ph-bold ph-arrow-left"></i></a><div><h2 class="text-[15px] font-bold text-gray-900">{{ $editing ? 'Edit Resep' : 'Tambah Resep' }}</h2><p class="text-[12px] text-gray-400 mt-0.5">{{ $editing ? $data->code : 'Isi produk dan bahan baku resep' }}</p></div></div>
    <form action="{{ $editing ? route('receipt.update', $data->id) : route('receipt.store') }}" method="POST" class="flex-1 min-h-0 flex flex-col" @submit="if (!productId || !ingredients.length || ingredients.some(item => !item.id || !item.quantity || Number(item.quantity) <= 0)) { $event.preventDefault(); error = 'Pilih produk dan lengkapi semua bahan serta kuantitasnya.'; }">
        @csrf @if($editing) @method('PUT') @endif
        <div class="flex-1 min-h-0 overflow-y-auto bg-gray-50/60 px-5 py-5 pb-8 space-y-5">
            @if($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">{{ $errors->first() }}</div>@endif
            @if(session('error'))<div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">{{ session('error') }}</div>@endif
            <div><label for="recipe-product-search" class="text-xs font-semibold text-gray-600 mb-1.5 block">Produk Hasil <span class="text-red-400">*</span></label><div class="relative"><input id="recipe-product-search" x-model="productSearch" @input.debounce.250ms="lookupProduct()" @focus="if (productResults.length) productOpen = true" @keydown.escape="productOpen = false" autocomplete="off" placeholder="Cari produk yang membutuhkan resep..." class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 pr-10 text-sm outline-none focus:border-emerald-500"><i class="ph ph-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i><div x-show="productOpen" x-cloak @click.outside="productOpen = false" class="absolute z-40 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg"><template x-for="item in productResults" :key="item.id"><button type="button" @click="productId = item.id; productSearch = item.name; productOpen = false" class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-emerald-50" x-text="item.name"></button></template><p x-show="productResults.length === 0" class="px-4 py-3 text-xs text-gray-400">Produk tidak ditemukan</p></div></div><input type="hidden" name="product_id" :value="productId" required></div>
            <div><label for="recipe-description" class="text-xs font-semibold text-gray-600 mb-1.5 block">Keterangan</label><textarea id="recipe-description" name="description" rows="3" maxlength="255" placeholder="Catatan resep" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-emerald-500 resize-none">{{ old('description', $data->description ?? '') }}</textarea></div>
            <div class="border-t border-gray-200 pt-5 flex items-center justify-between"><div><h3 class="text-sm font-bold text-gray-800">Bahan Baku Resep</h3><p class="text-xs text-gray-400 mt-0.5">Tambahkan bahan dan jumlah yang digunakan</p></div><button type="button" @click="addIngredient()" class="shrink-0 ml-3 h-9 px-3 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-semibold flex items-center gap-1"><i class="ph-bold ph-plus"></i> Tambah Bahan</button></div>
            <template x-for="(ingredient, index) in ingredients" :key="ingredient.key"><div class="rounded-2xl border border-gray-200 bg-white p-4 space-y-4"><div class="flex items-center justify-between"><span class="text-xs font-bold text-gray-700" x-text="'Bahan Baku #' + (index + 1)"></span><button type="button" @click="ingredients.splice(index, 1)" class="w-8 h-8 rounded-lg text-red-500 hover:bg-red-50" :aria-label="'Hapus bahan ' + (index + 1)"><i class="ph ph-trash"></i></button></div><div><label class="text-xs font-semibold text-gray-600 mb-1.5 block">Pilih Bahan <span class="text-red-400">*</span></label><div class="relative"><input x-model="ingredient.search" @input.debounce.250ms="lookupIngredient(ingredient)" @focus="if (ingredient.results.length) ingredient.open = true" @keydown.escape="ingredient.open = false" autocomplete="off" placeholder="Cari bahan baku..." class="w-full h-11 border border-gray-200 rounded-xl px-4 pr-10 text-sm outline-none focus:border-emerald-500"><i class="ph ph-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i><div x-show="ingredient.open" x-cloak @click.outside="ingredient.open = false" class="absolute z-40 left-0 right-0 top-full mt-1 w-full max-h-48 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg"><template x-for="item in ingredient.results" :key="item.id"><button type="button" @click="ingredient.id = item.id; ingredient.search = item.name; ingredient.open = false" class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-emerald-50" x-text="item.name"></button></template><p x-show="ingredient.results.length === 0" class="px-4 py-3 text-xs text-gray-400">Bahan tidak ditemukan</p></div></div></div><input type="hidden" name="product_receipt_id[]" :value="ingredient.id" required><div><label class="text-xs font-semibold text-gray-600 mb-1.5 block">Kuantitas <span class="text-red-400">*</span></label><input name="ingredients_quantity[]" x-model="ingredient.quantity" type="number" step="0.01" min="0.01" required placeholder="Masukkan kuantitas" class="w-full h-11 border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"></div></div></template>
            <p x-show="ingredients.length === 0" class="text-center text-xs text-gray-400 py-5">Belum ada bahan baku</p><p x-show="error" x-text="error" role="alert" class="text-xs text-red-600"></p>
        </div>
        <div class="shrink-0 bg-white border-t border-gray-100 px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] flex items-center gap-3"><a href="{{ route('receipt.index') }}" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 flex items-center justify-center">Batal</a><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold">{{ $editing ? 'Simpan Perubahan' : 'Tambah Resep' }}</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function recipeForm(selectedProduct, initialIngredients) {
    let nextKey = 0;
    return {
        productId: selectedProduct?.id || '', productSearch: selectedProduct?.name || '', productResults: [], productOpen: false,
        ingredients: initialIngredients.map(item => ({ key: ++nextKey, id: item.id, search: item.name, quantity: item.quantity, results: [], open: false })), error: '',
        addIngredient() { this.ingredients.push({key: ++nextKey, id: '', search: '', quantity: '', results: [], open: false}); },
        async lookupProduct() {
            this.productId = '';
            if (this.productSearch.trim().length < 2) { this.productResults = []; this.productOpen = false; return; }
            const response = await fetch(`{{ route('products.get-product-receipt') }}?search=${encodeURIComponent(this.productSearch.trim())}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) { this.error = 'Gagal mencari produk.'; return; }
            this.productResults = (await response.json()).slice(0, 30); this.productOpen = true;
        },
        async lookupIngredient(ingredient) {
            ingredient.id = '';
            if (ingredient.search.trim().length < 2) { ingredient.results = []; ingredient.open = false; return; }
            const response = await fetch(`{{ route('ajax.getProduct') }}?search=${encodeURIComponent(ingredient.search.trim())}`, {headers: {'Accept': 'application/json'}});
            if (!response.ok) { this.error = 'Gagal mencari bahan.'; return; }
            ingredient.results = (await response.json()).slice(0, 30); ingredient.open = true;
        }
    };
}
</script>
@endpush
