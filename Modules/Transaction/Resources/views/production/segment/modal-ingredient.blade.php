{{-- Tiga dialog bahan baku. Dibuka lewat erp.dialog.open(id) dari js-create; konteks Alpine mengikuti halaman. --}}
<x-erp.dialog id="modal-add-ingredient" title="Tambah Bahan Baku" size="lg">
    <div class="mb-3">
        <label class="erp-label" for="ingredient-search">Cari Bahan Baku:</label>
        <div class="relative">
            <i class="ph ph-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-gray-400" aria-hidden="true"></i>
            <input id="ingredient-search" type="text" class="erp-input pl-10" placeholder="Ketik nama bahan baku..." x-model="ingredientSearchTerm">
        </div>
    </div>

    <div class="max-h-[300px] overflow-y-auto">
        <div x-show="!availableIngredients.length && !ingredientSearchTerm" class="p-4 text-center text-sm text-gray-400">
            <span class="erp-spinner mr-2" aria-hidden="true"></span>Memuat bahan...
        </div>

        <template x-for="ingredient in filteredIngredients" :key="ingredient.id">
            <button type="button" class="flex w-full items-center border-b border-gray-100 p-2.5 text-left hover:bg-teal/5" @click="addIngredientToCart(ingredient)">
                <span class="grow">
                    <span class="block font-semibold text-gray-800" x-text="ingredient.name"></span>
                    <span class="block text-xs text-gray-400" x-text="ingredient.code"></span>
                    <span class="block font-semibold text-emerald-700" x-text="toRupiah(ingredient.hpp)"></span>
                </span>
                <i class="ph ph-plus text-xl text-teal" aria-hidden="true"></i>
            </button>
        </template>

        <div x-show="availableIngredients.length > 0 && filteredIngredients.length === 0" class="p-4 text-center text-sm text-gray-400">
            <i class="ph ph-list-magnifying-glass mb-2 block text-3xl" aria-hidden="true"></i>
            Tidak ada bahan yang ditemukan
        </div>
    </div>
    <x-slot:footer>
        <x-erp.button class="ml-auto" data-erp-close>Tutup</x-erp.button>
    </x-slot:footer>
</x-erp.dialog>

<x-erp.dialog id="modal-input-quantity" title="Input Jumlah Bahan">
    <div class="mb-3 rounded-xl bg-teal/10 p-3" x-show="selectedIngredient">
        <div class="flex items-center gap-2.5">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal text-white"><i class="ph ph-package text-xl" aria-hidden="true"></i></span>
            <div>
                <div class="font-semibold text-gray-800" x-text="selectedIngredient?.name"></div>
                <div class="text-xs text-gray-400">
                    Stok: <span x-text="selectedIngredient?.stock + ' ' + selectedIngredient?.unit"></span> |
                    HPP: <span x-text="toRupiah(selectedIngredient?.hpp)"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="erp-label erp-label--required" for="ingredient-quantity">Jumlah yang Digunakan:</label>
        <div class="erp-input-group">
            <input id="ingredient-quantity" type="number" class="erp-input" x-model="ingredientQuantity" step="0.01" min="0" placeholder="0.00" @keyup.enter="addIngredientToCart()">
            <span class="erp-input-group__addon" x-text="selectedIngredient?.unit"></span>
        </div>
        <p class="erp-help">Masukkan jumlah bahan yang akan digunakan untuk produksi</p>
    </div>

    <div class="rounded-xl bg-emerald-50 p-3" x-show="ingredientQuantity > 0">
        <div class="flex items-center justify-between">
            <span class="text-gray-500">Total Biaya Bahan:</span>
            <span class="text-base font-semibold text-emerald-700" x-text="toRupiah((selectedIngredient?.hpp || 0) * (ingredientQuantity || 0))"></span>
        </div>
    </div>
    <x-slot:footer>
        <div class="ml-auto flex gap-2">
            <x-erp.button data-erp-close>Batal</x-erp.button>
            <x-erp.button variant="primary" icon="plus" @click="addIngredientToCart()" ::disabled="!ingredientQuantity || ingredientQuantity <= 0">Tambah ke Produksi
            </x-erp.button>
        </div>
    </x-slot:footer>
</x-erp.dialog>

<x-erp.dialog id="modal-edit-ingredient" title="Edit Jumlah Bahan">
    <div class="mb-3 rounded-xl bg-amber-50 p-3" x-show="editingIngredient">
        <div class="flex items-center gap-2.5">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500 text-white"><i class="ph ph-package text-xl" aria-hidden="true"></i></span>
            <div>
                <div class="font-semibold text-gray-800" x-text="editingIngredient?.name"></div>
                <div class="text-xs text-gray-400">
                    HPP: <span x-text="formatCurrency(editingIngredient?.hpp)"></span> |
                    Unit: <span x-text="editingIngredient?.unit"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3 flex items-center gap-3 rounded-xl bg-blue-50 p-3 text-blue-800" x-show="editingIngredient">
        <i class="ph ph-info text-2xl" aria-hidden="true"></i>
        <div>
            <div class="text-sm font-semibold">Jumlah Saat Ini</div>
            <span x-text="editingIngredient?.quantity + ' ' + editingIngredient?.unit"></span>
        </div>
    </div>

    <div class="mb-3">
        <label class="erp-label erp-label--required" for="edit-quantity-input">Jumlah Baru:</label>
        <div class="erp-input-group">
            <input id="edit-quantity-input" type="number" class="erp-input" x-model="editQuantity" step="0.01" min="0" placeholder="0.00" @keyup.enter="updateIngredientQuantity()">
            <span class="erp-input-group__addon" x-text="editingIngredient?.unit"></span>
        </div>
        <p class="erp-help">Masukkan jumlah bahan yang baru</p>
    </div>

    <div class="rounded-xl bg-emerald-50 p-3" x-show="editQuantity > 0">
        <div class="flex items-center justify-between">
            <span class="text-gray-500">Total Biaya Bahan:</span>
            <span class="text-base font-semibold text-emerald-700" x-text="formatCurrency((editingIngredient?.hpp || 0) * (editQuantity || 0))"></span>
        </div>
    </div>
    <x-slot:footer>
        <div class="ml-auto flex gap-2">
            <x-erp.button data-erp-close>Batal</x-erp.button>
            <x-erp.button variant="danger" icon="trash" @click="removeIngredientFromEdit()" title="Hapus bahan ini">Hapus</x-erp.button>
            <x-erp.button variant="primary" icon="check" @click="updateIngredientQuantity()" ::disabled="!editQuantity || editQuantity <= 0">Update Jumlah</x-erp.button>
        </div>
    </x-slot:footer>
</x-erp.dialog>
