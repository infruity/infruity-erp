@if (! empty($reference))
{{-- Modal Add Jus (reference view: tabs, product dropdown, composition card, 2x2 fields) --}}
<section id="jusModal" class="pos-add-page pos-add-page--jus" role="region" aria-labelledby="jusModalLabel"
    x-show="showAddPage && activeAddCategory === 'jus'" x-cloak>
    <div class="pos-add-page__content pos-sheet--ref">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header pos-sheet-header pos-sheet-header--tabs">
                <span id="jusModalLabel" class="visually-hidden">Tambah Jus</span>
                @include('pos::pos.segment.add-tabs', ['active' => 'jus'])
            </div>
            <div class="erp-sheet-body pos-ref-body">
              <section class="pos-ref-panel">
                <header class="pos-ref-panel__head"><h3>Tambah Jus</h3><p>Pilih produk dan tentukan kuantitas belanja</p></header>
                <div>
                    <label class="pos-ref-cell__label pos-ref-required" for="select_jus">PILIH PRODUK</label>
                    <select id="select_jus" class="form-select"></select>
                </div>

                <section class="pos-ref-card pos-ref-card--jus" aria-labelledby="pos-jus-composition">
                    <h3 id="pos-jus-composition">Komposisi Produk</h3>
                    <p class="pos-ref-card__empty" x-show="!addProduct.id">
                        Pilih Produk Jus di Atas
                        <span class="pos-ref-card__hint">Takaran bahan akan tampil setelah produk dipilih.</span>
                    </p>
                    <div id="receiptContainer"></div>
                </section>

                <div class="pos-ref-grid">
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">HARGA JUAL <small x-text="PosAddItem.priceUnit(addProduct.unit || 'Cup')"></small></span>
                        <span class="pos-ref-cell__field is-readonly"><span>Rp</span><input type="text" x-model="formattedAddPrice" @input="updateAddPriceFromFormatted" readonly></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label pos-ref-required">KUANTITAS <small x-text="PosAddItem.quantityUnit(addProduct.unit || 'Cup')"></small></span>
                        <span class="pos-ref-cell__field"><input type="number" step="0.01" min="0" placeholder="0" x-model="addProduct.qty" @input="updateAddTotalFromQty"></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">DISKON</span>
                        <span class="pos-ref-cell__field"><span x-text="PosAddItem.discountPrefix(addProduct.discountNominal)">%</span><input type="text" inputmode="numeric" placeholder="0" :value="addProduct.discountNominal ? formatRupiah(addProduct.discountNominal) : ''" @input="updateDiscountValue"></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">TOTAL HARGA</span>
                        <span class="pos-ref-cell__field"><span>Rp</span><input type="text" inputmode="numeric" x-model="addProduct.formattedAddTotalInput" @input="updateQtyFromAddTotal" placeholder="0"></span>
                    </label>
                </div>
              </section>
            </div>
            <div class="erp-sheet-footer pos-ref-actions-bar">
                <button type="button" class="pos-ref-back" @click="closeJusModal()">Kembali</button>
                <button type="button" class="pos-ref-add" :disabled="!addProduct.id" @click="saveJusToCart()">Tambah</button>
            </div>
        </div>
    </div>
</section>
@else
{{-- Modal Add Product --}}
<div class="modal fade" id="jusModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true" x-show="showJusModal"
    style="display: none;">
    <div class="modal-dialog erp-sheet-dialog pos-sheet modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header pos-sheet-header">
                <h5 class="modal-title">Tambah Jus</h5>
                <button type="button" class="btn-close" @click="closeJusModal()"></button>
            </div>
            <div class="erp-sheet-body">
                <!-- Select Produk -->
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <select id="select_jus" class="form-select"></select>
                </div>

                <!-- Satuan -->
                <div class="row">
                    <div class="col-3 mb-3">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" x-model="addProduct.unit" readonly>
                    </div>

                    <!-- Harga -->
                    <div class="col-9 mb-3">
                        <label class="form-label">Harga</label>
                        <input type="text" class="form-control" x-model="formattedAddPrice"
                            @input="updateAddPriceFromFormatted" readonly>
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
                    <input type="text" class="form-control" :value="formatRupiah(addProduct.discountNominal || 0)"
                        @input="updateDiscountValue">
                </div>


                <!-- Jumlah Harga -->
                <div class="mb-3">
                    <label class="form-label">Jumlah Harga</label>
                    <input type="text" class="form-control" x-model="addProduct.formattedAddTotalInput"
                        @input="updateQtyFromAddTotal">
                </div>

                <hr>
                <div id="receiptContainer"></div>
            </div>
            <div class="erp-sheet-footer">
                <button class="btn btn-secondary" @click="closeJusModal()">Tutup</button>
                <button class="btn btn-primary" @click="saveJusToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endif
@if (! empty($reference))
{{-- Modal Edit Jus (reference layout; the composition rows are still filled into #receiptEditContainer by js-buah) --}}
<div class="modal fade" id="editJusModal" tabindex="-1" aria-labelledby="editJusModalLabel" aria-hidden="true" x-show="editJusModal"
    style="display: none;">
    <div class="modal-dialog erp-sheet-dialog pos-sheet pos-sheet--ref modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header pos-sheet-header pos-sheet-header--tabs">
                <span id="editJusModalLabel" class="visually-hidden">Edit Jus</span>
                @include('pos::pos.segment.add-tabs', ['active' => 'jus', 'locked' => true])
            </div>
            <div class="erp-sheet-body pos-ref-body">
              <section class="pos-ref-panel" x-show="editItem">
                <header class="pos-ref-panel__head"><h3>Edit Jus</h3><p>Pilih produk dan tentukan kuantitas belanja</p></header>
                <div>
                    <span class="pos-ref-cell__label pos-ref-required">PILIH PRODUK</span>
                    <div class="pos-ref-select is-readonly">
                        <span class="pos-ref-product__main">
                            <strong x-text="editProductName"></strong>
                            <small>Rp <span x-text="formatRupiah(editPrice)"></span> / <span x-text="editProductUnit"></span></small>
                        </span>
                    </div>
                </div>

                <section class="pos-ref-card" aria-labelledby="pos-jus-edit-composition">
                    <h3 id="pos-jus-edit-composition">Komposisi Produk</h3>
                    <div id="receiptEditContainer"></div>
                </section>

                <div class="pos-ref-grid">
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">HARGA JUAL <small x-text="PosAddItem.priceUnit(editProductUnit)"></small></span>
                        <span class="pos-ref-cell__field is-readonly"><span>Rp</span><input type="text" :value="formatRupiah(editPrice)" readonly></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label pos-ref-required">KUANTITAS <small x-text="PosAddItem.quantityUnit(editProductUnit)"></small></span>
                        <span class="pos-ref-cell__field"><input type="number" step="0.01" min="0" placeholder="0" x-model="editQty" @input="updateTotalFromEditQty"></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">DISKON</span>
                        <span class="pos-ref-cell__field"><span x-text="PosAddItem.discountPrefix(editDiscountNominal)">%</span><input type="text" inputmode="numeric" placeholder="0" :value="editDiscountNominal ? formatRupiah(editDiscountNominal) : ''" @input="updateEditDiscountValue"></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">TOTAL HARGA</span>
                        <span class="pos-ref-cell__field"><span>Rp</span><input type="text" inputmode="numeric" x-model="editTotalFormatted" @input="updateQtyFromEditTotalFormatted"></span>
                    </label>
                </div>
              </section>
            </div>
            <div class="erp-sheet-footer pos-ref-actions-bar">
                <button type="button" class="pos-ref-back" @click="closeEditJusModal()">Kembali</button>
                <button type="button" class="pos-ref-add" @click="saveEditJusToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>
@else
{{-- Modal Edit Jus --}}
<div class="modal fade" id="editJusModal" tabindex="-1" aria-labelledby="editJusModalLabel" aria-hidden="true" x-show="editJusModal"
    style="display: none;">
    <div class="modal-dialog erp-sheet-dialog pos-sheet modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header pos-sheet-header">
                <h5 class="modal-title">Edit Jus</h5>
                <button type="button" class="btn-close" @click="closeEditJusModal()"></button>
            </div>
            <div class="erp-sheet-body">
                <div x-show="editItem">
                    <div class="mb-3">
                        <label class="form-label">Nama Produk</label>
                        <input type="text" class="form-control" x-model="editProductName" readonly>
                    </div>

                    <!-- Satuan & Harga -->
                    <div class="row">
                        <div class="col-3 mb-3">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" x-model="editProductUnit" readonly>
                        </div>
                        <div class="col-9 mb-3">
                            <label class="form-label">Harga</label>
                            <input type="text" class="form-control" :value="formatRupiah(editPrice)" readonly>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" step="0.01" min="0" x-model="editQty"
                            @input="updateTotalFromEditQty">
                    </div>

                    <!-- Diskon -->
                    <div class="mb-3">
                        <label class="form-label">Diskon (Rp jika > 100, % jika ≤ 100)</label>
                        <input type="text" class="form-control" inputmode="numeric" :value="formatRupiah(editDiscountNominal || 0)"
                            @input="updateEditDiscountValue">
                    </div>

                    <!-- Jumlah Harga -->
                    <div class="mb-3">
                        <label class="form-label">Jumlah Harga</label>
                        <input type="text" class="form-control" inputmode="numeric" x-model="editTotalFormatted"
                            @input="updateQtyFromEditTotalFormatted">
                    </div>
                </div>

                <hr>
                <div id="receiptEditContainer"></div>
            </div>
            <div class="erp-sheet-footer">
                <button class="btn btn-danger me-auto" @click="deleteFromCart()">Hapus Produk</button>
                <button class="btn btn-secondary" @click="closeEditJusModal()">Batal</button>
                <button class="btn btn-primary" @click="saveEditJusToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endif
