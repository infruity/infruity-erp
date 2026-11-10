@if (! empty($reference))
{{-- Modal Add Product (reference view: tabs, searchable product list, 2x2 fields) --}}
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true" x-show="showAddModal"
    style="display: none;">
    <div class="modal-dialog erp-sheet-dialog pos-sheet pos-sheet--ref modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header pos-sheet-header pos-sheet-header--tabs">
                <span id="addModalLabel" class="visually-hidden">Tambah Produk</span>
                @include('pos::pos.segment.add-tabs', ['active' => 'buah'])
            </div>
            <div class="erp-sheet-body pos-ref-body">
              <section class="pos-ref-panel">
                <header class="pos-ref-panel__head"><h3>Tambah Buah & Olahan</h3><p>Pilih produk dan tentukan kuantitas belanja</p></header>
                <span class="pos-ref-cell__label pos-ref-required">PILIH PRODUK</span>
                <div class="pos-ref-listbox">
                <div class="pos-ref-search">
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" x-model="productSearch" @input.debounce.250ms="loadProductList()" placeholder="Cari nama produk buah..." autocomplete="off" aria-label="Cari produk">
                </div>
                <ul id="pos-product-list" class="pos-ref-products" role="listbox" aria-label="Daftar produk">
                    <li class="pos-ref-products__state" x-show="productListState === 'loading'" role="status">Memuat produk…</li>
                    <li class="pos-ref-products__state" x-show="productListState === 'error'" role="alert">Gagal memuat produk. <button type="button" @click="loadProductList()">Coba lagi</button></li>
                    <li class="pos-ref-products__state" x-show="productListState === 'ready' && productList.length === 0">Produk tidak ditemukan</li>
                    <template x-for="row in productList" :key="row.id">
                        <li>
                            <button type="button" role="option" class="pos-ref-product" :aria-selected="addProduct.id === row.id"
                                :class="{ 'is-selected': addProduct.id === row.id, 'is-empty': rowStock(row) <= 0 }" @click="pickProduct(row)">
                                <span class="pos-ref-product__main">
                                    <strong x-text="row.text"></strong>
                                    <small>Rp <span x-text="formatRupiah(row.price)"></span> / <span x-text="row.unit?.abbreviation"></span></small>
                                </span>
                                <span class="pos-ref-product__stock" x-text="PosAddItem.stockLabel(rowStock(row), row.unit?.abbreviation)"></span>
                            </button>
                        </li>
                    </template>
                </ul>
                </div>

                <div class="pos-ref-grid">
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">HARGA JUAL <small x-text="PosAddItem.priceUnit(addProduct.unit)"></small></span>
                        <span class="pos-ref-cell__field is-readonly"><span>Rp</span><input type="text" x-model="formattedAddPrice" readonly></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label pos-ref-required">KUANTITAS <small x-text="PosAddItem.quantityUnit(addProduct.unit)"></small></span>
                        <span class="pos-ref-cell__field"><input type="number" step="0.01" min="0" placeholder="0" x-model="addProduct.qty" @input="updateAddTotalFromQty"></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">DISKON</span>
                        <span class="pos-ref-cell__field"><span x-text="PosAddItem.discountPrefix(addProduct.discountNominal)">%</span><input type="text" inputmode="numeric" placeholder="0" :value="addProduct.discountNominal ? formatRupiah(addProduct.discountNominal) : ''" @input="updateDiscountValue"></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">TOTAL HARGA</span>
                        <span class="pos-ref-cell__field"><span>Rp</span><input type="text" inputmode="numeric" x-model="addProduct.formattedAddTotalInput" @input="updateQtyFromAddTotal"></span>
                    </label>
                </div>
              </section>
            </div>
            <div class="erp-sheet-footer pos-ref-actions-bar">
                <button type="button" class="pos-ref-back" @click="closeAddModal()">Kembali</button>
                <button type="button" class="pos-ref-add" :disabled="!addProduct.id" @click="saveAddToCart()">Tambah</button>
            </div>
        </div>
    </div>
</div>
@else
{{-- Modal Add Product --}}
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true" x-show="showAddModal"
    style="display: none;">
    <div class="modal-dialog erp-sheet-dialog pos-sheet modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header pos-sheet-header">
                <h5 class="modal-title">Tambah Produk</h5>
                <button type="button" class="btn-close" @click="closeAddModal()"></button>
            </div>
            <div class="erp-sheet-body">
                <!-- Select Produk -->
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <select id="select_product" class="form-select"></select>
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
@endif
@if (! empty($reference))
{{-- Modal Edit (reference layout: locked tab, selected product, 2x2 fields; same bindings as the plain sheet) --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true"
    x-show="editModal" style="display: none;">
    <div class="modal-dialog erp-sheet-dialog pos-sheet pos-sheet--ref modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header pos-sheet-header pos-sheet-header--tabs">
                <span id="editModalLabel" class="visually-hidden" x-text="editTitle"></span>
                @include('pos::pos.segment.add-tabs', ['active' => 'buah', 'locked' => true])
            </div>
            <div class="erp-sheet-body pos-ref-body">
              <section class="pos-ref-panel" x-show="editItem">
                <header class="pos-ref-panel__head"><h3>Edit Buah & Olahan</h3><p>Pilih produk dan tentukan kuantitas belanja</p></header>
                <span class="pos-ref-cell__label pos-ref-required">PILIH PRODUK</span>
                <div class="pos-ref-listbox">
                    <ul class="pos-ref-products" role="listbox" aria-label="Produk yang diedit">
                        <li>
                            <div class="pos-ref-product is-selected" role="option" aria-selected="true">
                                <span class="pos-ref-product__main">
                                    <strong x-text="editProductName"></strong>
                                    <small>Rp <span x-text="formatRupiah(editPrice)"></span> / <span x-text="editProductUnit"></span></small>
                                </span>
                                <span class="pos-ref-product__stock" x-show="editItem?.original_stock !== undefined"
                                    x-text="PosAddItem.stockLabel((editItem?.original_stock ?? 0) - (calculateUsedStock(editItem?.id) - (editItem?.qty || 0)), editProductUnit)"></span>
                            </div>
                        </li>
                    </ul>
                </div>

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
                <button type="button" class="pos-ref-back" @click="closeEditModal()">Kembali</button>
                <button type="button" class="pos-ref-add" @click="saveEditToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>
@else
{{-- Modal Edit --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true"
    x-show="editModal" style="display: none;">
    <div class="modal-dialog erp-sheet-dialog pos-sheet modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header pos-sheet-header">
                <h5 class="modal-title" x-text="editTitle"></h5>
            </div>
            <div class="erp-sheet-body">
                <div x-show="editItem">
                    <!-- Select Produk (Readonly untuk edit) -->
                    <div class="mb-3">
                        <label class="form-label">Nama Produk</label>
                        <input type="text" class="form-control" x-model="editProductName" readonly>
                    </div>

                    <!-- Satuan -->
                    <div class="row">
                        <div class="col-3 mb-3">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" x-model="editProductUnit" readonly>
                        </div>

                        <!-- Harga -->
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
            </div>
            <div class="erp-sheet-footer">
                <button class="btn btn-danger me-auto" @click="deleteFromCart()">Hapus Produk</button>
                <button class="btn btn-secondary" @click="closeEditModal()">Batal</button>
                <button class="btn btn-primary" @click="saveEditToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endif
