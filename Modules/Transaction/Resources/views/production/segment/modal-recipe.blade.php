{{-- Dialog "Muat dari Resep". Dibuka lewat erp.dialog.open('modal-load-recipe'); konteks Alpine mengikuti halaman. --}}
<x-erp.dialog id="modal-load-recipe" title="Muat dari Resep" size="lg">
    <div class="mb-3">
        <label class="erp-label" for="recipe-select">Pilih Resep:</label>
        <select id="recipe-select" class="erp-select" x-model="selectedRecipeId" @change="loadRecipeDetails()">
            <option value="">-- Pilih Resep --</option>
            <template x-for="recipe in availableRecipes" :key="recipe.id">
                <option :value="recipe.id" x-text="recipe.name"></option>
            </template>
        </select>
        <p class="erp-help">Pilih resep yang akan digunakan sebagai dasar produksi</p>
    </div>

    <div x-show="selectedRecipe" class="mb-3 rounded-xl bg-blue-50 p-4">
        <div class="mb-2.5 flex items-center gap-2.5">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-500 text-white"><i class="ph ph-chef-hat text-xl" aria-hidden="true"></i></span>
            <div>
                <div class="text-[15px] font-semibold text-gray-800" x-text="selectedRecipe?.name"></div>
                <div class="text-gray-400">
                    Untuk <span x-text="selectedRecipe?.yield_quantity"></span>
                    <span x-text="selectedRecipe?.yield_unit"></span>
                </div>
            </div>
        </div>
        <div class="text-gray-600" x-text="selectedRecipe?.description"></div>
    </div>

    <div x-show="selectedRecipe?.ingredients?.length > 0">
        <h3 class="mb-2.5 text-sm font-semibold text-gray-800">Bahan-bahan dalam Resep:</h3>
        <div class="overflow-x-auto">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Bahan</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-center">HPP</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="ingredient in selectedRecipe?.ingredients" :key="ingredient.id">
                        <tr>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-emerald-50 text-emerald-700"><i class="ph ph-package text-sm" aria-hidden="true"></i></span>
                                    <span class="font-semibold text-gray-600" x-text="ingredient.name"></span>
                                </div>
                            </td>
                            <td class="text-center"><span x-text="ingredient.quantity + ' ' + ingredient.unit"></span></td>
                            <td class="text-center"><span x-text="formatCurrency(ingredient.hpp)"></span></td>
                            <td class="text-center"><span class="font-semibold text-teal" x-text="formatCurrency(ingredient.quantity * ingredient.hpp)"></span></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Total HPP Resep:</th>
                        <th class="text-center"><span class="text-[15px] font-semibold text-emerald-700" x-text="formatCurrency(recipeTotal)"></span></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div x-show="selectedRecipe" class="mt-3">
        <label class="erp-label" for="recipe-multiplier">Pengali Produksi:</label>
        <div class="erp-input-group">
            <span class="erp-input-group__addon">×</span>
            <input id="recipe-multiplier" type="number" class="erp-input" x-model="recipeMultiplier" step="0.1" min="0.1" placeholder="1.0">
            <span class="erp-input-group__addon">kali</span>
        </div>
        <p class="erp-help">Masukkan pengali untuk menyesuaikan jumlah produksi. Contoh: 2.0 untuk membuat 2x lipat dari resep asli</p>
    </div>

    <div x-show="selectedRecipe && recipeMultiplier > 0" class="mt-3 grid grid-cols-2 gap-3 rounded-xl bg-emerald-50 p-3">
        <div>
            <div class="text-gray-500">Total Bahan:</div>
            <div class="font-semibold" x-text="selectedRecipe?.ingredients?.length + ' item'"></div>
        </div>
        <div>
            <div class="text-gray-500">Total HPP:</div>
            <div class="font-semibold text-emerald-700" x-text="formatCurrency(recipeTotal * recipeMultiplier)"></div>
        </div>
    </div>
    <x-slot:footer>
        <div class="ml-auto flex gap-2">
            <x-erp.button data-erp-close>Batal</x-erp.button>
            <x-erp.button variant="primary" icon="check" @click="loadRecipeToProduction()" ::disabled="!selectedRecipe || !recipeMultiplier || recipeMultiplier <= 0">Muat Resep ke Produksi
            </x-erp.button>
        </div>
    </x-slot:footer>
</x-erp.dialog>
