{{-- Modal Add Product --}}
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true" x-show="showAddModal"
    style="display: none;">
    <div class="modal-dialog erp-sheet-dialog modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header" style="background-color: #ff000d; color: #fff;">
                <h5 class="modal-title" style="color: #fff">Tambah Produk</h5>
                <button type="button" class="btn-close" @click="closeAddModal()"></button>
            </div>
            <div class="erp-sheet-body">
                <!-- Select Produk -->
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    {{-- <select id="select_product" class="form-select"></select> --}}
                    <input type="text" class="form-control" x-model="addProduct.name">
                </div>

                <!-- Satuan -->
                <div class="row">
                    <div class="col-3 mb-3">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" x-model="addProduct.unit">
                    </div>

                    <!-- Harga -->
                    <div class="col-9 mb-3">
                        <label class="form-label">Harga</label>
                        <input type="text" inputmode="numeric" class="form-control" x-model="formattedAddPrice">
                    </div>
                </div>

                <!-- Quantity -->
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control" step="0.01" min="0" x-model="addProduct.qty"
                        @input="updateAddTotalFromQty">
                </div>

                <!-- Diskon -->
                <div class="mb-3">
                    <label class="form-label">Diskon (Rp jika > 100, % jika ≤ 100)</label>
                    <input type="text" class="form-control" inputmode="numeric" :value="formatRupiah(addProduct.discountNominal || 0)"
                        @input="updateDiscountValue">
                </div>


                <!-- Jumlah Harga -->
                <div class="mb-3">
                    <label class="form-label">Jumlah Harga</label>
                    <input type="text" class="form-control" inputmode="numeric" x-model="addProduct.formattedAddTotalInput"
                        @input="updateQtyFromAddTotal">
                </div>
            </div>
            <div class="erp-sheet-footer">
                <button class="btn btn-secondary" @click="closeAddModal()">Tutup</button>
                <button class="btn btn-primary" @click="saveAddToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>
{{-- Modal Edit --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true"
    x-show="editModal" style="display: none;">
    <div class="modal-dialog erp-sheet-dialog modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header" style="background-color: #ff000d; color: #fff;">
                <span class="fs-4 text-white fw-bold" x-text="editTitle"></span>
            </div>
            <div class="erp-sheet-body">
                <div x-show="editItem">
                    <div class="mb-3">
                        <label class="form-label">Nama Product</label>
                        <input type="text" class="form-control" x-model="editProductName">
                    </div>
                    <!-- Input Qty -->
                    <div class="row mb-3">
                        <div class="col-4">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" step="0.01" min="0"
                                x-model="editProductUnit">
                        </div>
                        <div class="col-8">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" step="0.01" min="0" x-model="editQty"
                                @input="updateTotalFromEditQty">
                        </div>
                    </div>
                    <!-- Input Mode Harga -->
                    <div class="mb-3">
                        <label class="form-label">Harga Jual (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-control" x-model="editTotalFormatted"
                            @input="updateEditTotalFormatted">
                    </div>
                    <!-- Diskon -->
                    <div class="mb-3">
                        <label class="form-label">Diskon (Rp jika > 100, % jika ≤ 100)</label>
                        <input type="number" class="form-control" x-model="editDiscount" min="0"
                            @input="updateEditDiscount">
                    </div>
                </div>
            </div>
            <div class="erp-sheet-footer">
                <button class="btn btn-danger me-auto" @click="deleteFromCart()">Hapus Produk</button>
                <button class="btn btn-secondary" @click="closeEditModal()">Batal</button>
                <button class="btn btn-primary" @click="saveEditToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>
