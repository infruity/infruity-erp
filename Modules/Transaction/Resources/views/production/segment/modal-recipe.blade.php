<!-- Modal Load from Recipe -->
<div class="modal fade" id="modal-load-recipe" tabindex="-1" aria-labelledby="modalLoadRecipeLabel" aria-hidden="true">
    <div class="modal-dialog erp-sheet-dialog modal-lg">
        <div class="modal-content">
            <div class="erp-sheet-header">
                <h5 class="modal-title" id="modalLoadRecipeLabel">
                    <i class="ki-duotone ki-book fs-2 text-primary me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                    </i>
                    Muat dari Resep
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="erp-sheet-body">
                <!-- Recipe Selection -->
                <div class="mb-4">
                    <label class="form-label">Pilih Resep:</label>
                    <select class="form-select form-select-lg" x-model="selectedRecipeId" @change="loadRecipeDetails()">
                        <option value="">-- Pilih Resep --</option>
                        <template x-for="recipe in availableRecipes" :key="recipe.id">
                            <option :value="recipe.id" x-text="recipe.name"></option>
                        </template>
                    </select>
                    <div class="form-text">Pilih resep yang akan digunakan sebagai dasar produksi</div>
                </div>
                
                <!-- Recipe Details -->
                <div x-show="selectedRecipe" class="card card-flush bg-light-info mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="symbol symbol-50px me-3">
                                <div class="symbol-label bg-info">
                                    <i class="ki-duotone ki-chef-hat fs-2 text-white">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold text-gray-800 fs-5" x-text="selectedRecipe?.name"></div>
                                <div class="text-muted">
                                    Untuk <span x-text="selectedRecipe?.yield_quantity"></span> 
                                    <span x-text="selectedRecipe?.yield_unit"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-gray-700" x-text="selectedRecipe?.description"></div>
                    </div>
                </div>
                
                <!-- Recipe Ingredients -->
                <div x-show="selectedRecipe?.ingredients?.length > 0">
                    <h6 class="fw-bold text-gray-800 mb-3">Bahan-bahan dalam Resep:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
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
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-30px me-2">
                                                    <div class="symbol-label bg-light-success">
                                                        <i class="ki-duotone ki-package fs-6 text-success">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                        </i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-gray-700" x-text="ingredient.name"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span x-text="ingredient.quantity + ' ' + ingredient.unit"></span>
                                        </td>
                                        <td class="text-center">
                                            <span x-text="formatCurrency(ingredient.hpp)"></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-primary" 
                                                x-text="formatCurrency(ingredient.quantity * ingredient.hpp)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total HPP Resep:</th>
                                    <th class="text-center">
                                        <span class="fw-bold text-success fs-5" x-text="formatCurrency(recipeTotal)"></span>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Production Multiplier -->
                <div x-show="selectedRecipe" class="mt-4">
                    <label class="form-label">Pengali Produksi:</label>
                    <div class="input-group">
                        <span class="input-group-text">×</span>
                        <input type="number" class="form-control" x-model="recipeMultiplier" 
                            step="0.1" min="0.1" placeholder="1.0">
                        <span class="input-group-text">kali</span>
                    </div>
                    <div class="form-text">
                        Masukkan pengali untuk menyesuaikan jumlah produksi. 
                        Contoh: 2.0 untuk membuat 2x lipat dari resep asli
                    </div>
                </div>
                
                <!-- Total Preview -->
                <div x-show="selectedRecipe && recipeMultiplier > 0" class="card card-flush bg-light-success mt-4">
                    <div class="card-body py-3">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-gray-600">Total Bahan:</div>
                                <div class="fw-bold" x-text="selectedRecipe?.ingredients?.length + ' item'"></div>
                            </div>
                            <div class="col-6">
                                <div class="text-gray-600">Total HPP:</div>
                                <div class="fw-bold text-success" x-text="formatCurrency(recipeTotal * recipeMultiplier)"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="erp-sheet-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" @click="loadRecipeToProduction()" 
                    :disabled="!selectedRecipe || !recipeMultiplier || recipeMultiplier <= 0">
                    <i class="ki-duotone ki-check fs-5 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Muat Resep ke Produksi
                </button>
            </div>
        </div>
    </div>
</div>