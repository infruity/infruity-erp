<!-- Modal Add Ingredient -->
<div class="modal fade" id="modal-add-ingredient" tabindex="-1" aria-labelledby="modalAddIngredientLabel" aria-hidden="true">
    <div class="modal-dialog erp-sheet-dialog modal-lg">
        <div class="modal-content">
            <div class="erp-sheet-header">
                <h5 class="modal-title" id="modalAddIngredientLabel">
                    <i class="ki-duotone ki-plus fs-2 text-primary me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Tambah Bahan Baku
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="erp-sheet-body">
                <!-- Search Input -->
                <div class="mb-4">
                    <label class="form-label">Cari Bahan Baku:</label>
                    <div class="position-relative">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 mt-3">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="text" class="form-control form-control-lg ps-12" 
                            placeholder="Ketik nama bahan baku..." x-model="ingredientSearchTerm">
                    </div>
                </div>
                
                <!-- Ingredients List -->
                <div class="scroll-y me-n7 pe-7" style="max-height: 300px;">
                    <!-- Loading state -->
                    <div x-show="!availableIngredients.length && !ingredientSearchTerm" class="text-center p-5">
                        <div class="text-muted">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Memuat bahan...
                        </div>
                    </div>
                    
                    <template x-for="ingredient in filteredIngredients" :key="ingredient.id">
                        <div class="d-flex align-items-center p-3 border-bottom cursor-pointer hover-bg-light-primary" 
                             @click="addIngredientToCart(ingredient)">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-gray-800" x-text="ingredient.name"></div>
                                <div class="text-muted fs-7" x-text="ingredient.code"></div>
                                <div class="text-success fw-bold" x-text="toRupiah(ingredient.hpp)"></div>
                            </div>
                            <i class="ki-duotone ki-plus fs-2 text-primary"></i>
                        </div>
                    </template>
                    
                    <!-- No results message -->
                    <div x-show="availableIngredients.length > 0 && filteredIngredients.length === 0" class="text-center p-5">
                        <div class="text-muted">
                            <i class="ki-duotone ki-search-list fs-3x mb-3"></i>
                            <div>Tidak ada bahan yang ditemukan</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="erp-sheet-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Input Quantity -->
<div class="modal fade" id="modal-input-quantity" tabindex="-1" aria-labelledby="modalInputQuantityLabel" aria-hidden="true">
    <div class="modal-dialog erp-sheet-dialog">
        <div class="modal-content">
            <div class="erp-sheet-header">
                <h5 class="modal-title" id="modalInputQuantityLabel">
                    <i class="ki-duotone ki-calculator fs-2 text-primary me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Input Jumlah Bahan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="erp-sheet-body">
                <!-- Selected Ingredient Info -->
                <div class="card card-flush bg-light-primary mb-4" x-show="selectedIngredient">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px me-3">
                                <div class="symbol-label bg-primary">
                                    <i class="ki-duotone ki-package fs-2 text-white">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold text-gray-800" x-text="selectedIngredient?.name"></div>
                                <div class="text-muted fs-7">
                                    Stok: <span x-text="selectedIngredient?.stock + ' ' + selectedIngredient?.unit"></span> | 
                                    HPP: <span x-text="toRupiah(selectedIngredient?.hpp)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quantity Input -->
                <div class="mb-4">
                    <label class="form-label required">Jumlah yang Digunakan:</label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-lg" 
                            x-model="ingredientQuantity" step="0.01" min="0" 
                            placeholder="0.00" @keyup.enter="addIngredientToCart()">
                        <span class="input-group-text" x-text="selectedIngredient?.unit"></span>
                    </div>
                    <div class="form-text">Masukkan jumlah bahan yang akan digunakan untuk produksi</div>
                </div>
                
                <!-- Total Cost Preview -->
                <div class="card card-flush bg-light-success" x-show="ingredientQuantity > 0">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-gray-600">Total Biaya Bahan:</span>
                            <span class="fw-bold text-success fs-4" 
                                x-text="toRupiah((selectedIngredient?.hpp || 0) * (ingredientQuantity || 0))"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="erp-sheet-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" @click="addIngredientToCart()" 
                    :disabled="!ingredientQuantity || ingredientQuantity <= 0">
                    <i class="ki-duotone ki-plus fs-5 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Tambah ke Produksi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Ingredient Quantity -->
<div class="modal fade" id="modal-edit-ingredient" tabindex="-1" aria-labelledby="modalEditIngredientLabel" aria-hidden="true">
    <div class="modal-dialog erp-sheet-dialog">
        <div class="modal-content">
            <div class="erp-sheet-header">
                <h5 class="modal-title" id="modalEditIngredientLabel">
                    <i class="ki-duotone ki-pencil fs-2 text-warning me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Edit Jumlah Bahan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="erp-sheet-body">
                <!-- Selected Ingredient Info -->
                <div class="card card-flush bg-light-warning mb-4" x-show="editingIngredient">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-40px me-3">
                                <div class="symbol-label bg-warning">
                                    <i class="ki-duotone ki-package fs-2 text-white">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold text-gray-800" x-text="editingIngredient?.name"></div>
                                <div class="text-muted fs-7">
                                    HPP: <span x-text="formatCurrency(editingIngredient?.hpp)"></span> | 
                                    Unit: <span x-text="editingIngredient?.unit"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Current Quantity Display -->
                <div class="alert alert-info d-flex align-items-center mb-4" x-show="editingIngredient">
                    <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div class="d-flex flex-column">
                        <h5 class="mb-1">Jumlah Saat Ini</h5>
                        <span x-text="editingIngredient?.quantity + ' ' + editingIngredient?.unit"></span>
                    </div>
                </div>
                
                <!-- New Quantity Input -->
                <div class="mb-4">
                    <label class="form-label required">Jumlah Baru:</label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-lg" 
                            x-model="editQuantity" step="0.01" min="0" 
                            placeholder="0.00" @keyup.enter="updateIngredientQuantity()"
                            id="edit-quantity-input">
                        <span class="input-group-text" x-text="editingIngredient?.unit"></span>
                    </div>
                    <div class="form-text">Masukkan jumlah bahan yang baru</div>
                </div>
                
                <!-- Total Cost Preview -->
                <div class="card card-flush bg-light-success" x-show="editQuantity > 0">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-gray-600">Total Biaya Bahan:</span>
                            <span class="fw-bold text-success fs-4" 
                                x-text="formatCurrency((editingIngredient?.hpp || 0) * (editQuantity || 0))"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="erp-sheet-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger me-2" @click="removeIngredientFromEdit()" 
                    title="Hapus bahan ini">
                    <i class="ki-duotone ki-trash fs-5 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>
                    Hapus
                </button>
                <button type="button" class="btn btn-primary" @click="updateIngredientQuantity()" 
                    :disabled="!editQuantity || editQuantity <= 0">
                    <i class="ki-duotone ki-check fs-5 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Update Jumlah
                </button>
            </div>
        </div>
    </div>
</div>