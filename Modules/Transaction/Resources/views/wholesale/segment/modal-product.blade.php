@push('styles')
<style>
    #addModal .modal-dialog { display:flex; align-items:center; width:calc(100% - 32px); max-width:512px; min-height:calc(100% - 32px); margin:16px auto; }
    #addModal .modal-content { display:flex; width:100%; max-height:92dvh; min-height:0; overflow:hidden; border:1px solid #e5e7eb; border-radius:24px; background:#fff; box-shadow:0 24px 60px rgba(0,0,0,.2); font-family:'Plus Jakarta Sans',sans-serif; }
    #addModal .modal-header { display:flex; flex:none; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #f3f4f6; padding:18px 20px 14px; background:#fafbfc; }
    #addModal .modal-title { margin:0; color:#111827; font-size:16px; line-height:1.4; font-weight:700; }
    #addModal .wholesale-item-subtitle { margin:2px 0 0; color:#6b7280; font-size:12px; line-height:1.5; }
    #addModal .wholesale-item-close { display:flex; width:32px; height:32px; flex:none; align-items:center; justify-content:center; border:0; border-radius:50%; background:#fff; color:#6b7280; cursor:pointer; }
    #addModal .modal-body { min-height:0; overflow-y:auto; padding:16px; overscroll-behavior:contain; }
    #addModal .wholesale-item-label { display:block; margin:0 0 7px; color:#6b7280; font-size:11px; font-weight:700; letter-spacing:.025em; line-height:1.5; text-transform:uppercase; }
    #addModal .wholesale-item-label em { color:#ef4444; font-style:normal; }
    #addModal .wholesale-product-picker { overflow:hidden; border:1px solid #e5e7eb; border-radius:12px; }
    #addModal .wholesale-product-search { display:flex; align-items:center; gap:9px; border-bottom:1px solid #f3f4f6; padding:0 12px; color:#9ca3af; }
    #addModal .wholesale-product-search input { width:100%; min-width:0; height:40px; border:0; outline:none; background:#fff; color:#1f2937; font:inherit; font-size:13px; }
    #addModal input::placeholder { color:#9ca3af; opacity:1; }
    #addModal .wholesale-product-options { height:118px; overflow-y:auto; overscroll-behavior:contain; }
    #addModal .wholesale-product-option { display:flex; min-height:39px; align-items:center; gap:12px; margin:0; border-bottom:1px solid #f3f4f6; padding:9px 14px; color:#1f2937; font-size:12px; line-height:20px; cursor:pointer; }
    #addModal .wholesale-product-option:last-child { border-bottom:0; }
    #addModal .wholesale-product-option:hover { background:#f9fafb; }
    #addModal .wholesale-product-option:has(input:checked) { background:#ecfdf5; font-weight:600; }
    #addModal .wholesale-product-option input { width:12px; height:12px; flex:none; margin:0; accent-color:#059669; }
    #addModal .wholesale-product-option span { min-width:0; overflow-wrap:anywhere; }
    #addModal .wholesale-picker-message { margin:0; padding:18px 14px; color:#6b7280; font-size:12px; text-align:center; }
    #addModal .wholesale-picker-retry { border:0; background:transparent; color:#0b595b; font:inherit; text-decoration:underline; cursor:pointer; }
    #addModal .wholesale-item-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px 12px; margin-top:14px; }
    #addModal .wholesale-item-input { box-sizing:border-box; width:100%; min-width:0; height:40px; border:1px solid #e5e7eb; border-radius:12px; padding:0 14px; background:#f9fafb; color:#1f2937; font-family:inherit; font-size:14px; font-weight:600; }
    #addModal .wholesale-item-input:focus { border-color:#059669; outline:2px solid rgba(5,150,105,.12); background:#fff; }
    #addModal .wholesale-item-margin { background:#f3f4f6; color:#047857; font-size:16px; }
    #addModal .wholesale-item-total { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:14px; border:1px solid #bbf7d0; border-radius:12px; padding:13px 12px; background:#f0fdf8; color:#065f46; font-size:12px; font-weight:600; }
    #addModal .wholesale-item-total strong { color:#0b595b; font-size:14px; font-variant-numeric:tabular-nums; }
    #addModal .wholesale-item-error { margin:12px 0 0; color:#b91c1c; font-size:12px; }
    #addModal .modal-footer { flex:none; border-top:1px solid #f3f4f6; padding:16px; background:#fafbfc; }
    #addModal .wholesale-item-submit { width:100%; min-height:44px; border:0; border-radius:12px; background:#0b595b; color:#fff; box-shadow:0 4px 8px rgba(11,89,91,.18); font:inherit; font-size:12px; font-weight:700; cursor:pointer; }
    #addModal .wholesale-item-submit:hover { background:#08484a; }
    #addModal button:focus-visible, #addModal input:focus-visible { outline:2px solid #0b595b; outline-offset:2px; }
    #addModal #wholesale-product-search,
    #addModal #wholesale-product-search:focus,
    #addModal #wholesale-product-search:focus-visible,
    #addModal #wholesale-product-search:active { -webkit-appearance:none; appearance:none; border:0 !important; outline:0 !important; box-shadow:none !important; -webkit-box-shadow:none !important; }
    @media (max-width:380px) { #addModal .wholesale-item-label { font-size:10px; } #addModal .modal-header { padding:16px; } }
</style>
@endpush

{{-- Teleport keeps the dialog outside the scrolling mobile canvas. --}}
<template x-teleport="body">
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-describedby="addModalDescription" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" @submit.prevent="saveAddToCart()">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="addModalLabel">Tambah Item Belanja</h2>
                        <p class="wholesale-item-subtitle" id="addModalDescription">Pilih produk dan tentukan detail belanja</p>
                    </div>
                    <button type="button" class="wholesale-item-close" @click="closeAddModal()" aria-label="Tutup tambah item"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
                </div>
                <div class="modal-body">
                    <div role="group" aria-labelledby="wholesale-product-label">
                        <span class="wholesale-item-label" id="wholesale-product-label">Pilih Produk / Bahan <em>*</em></span>
                        <div class="wholesale-product-picker">
                            <div class="wholesale-product-search">
                                <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                                <input id="wholesale-product-search" type="search" x-model="addProductSearch" @input.debounce.250ms="loadAddProducts()" placeholder="Cari produk / bahan..." aria-label="Cari produk atau bahan" autocomplete="off">
                            </div>
                            <div class="wholesale-product-options" :aria-busy="addProductsLoading">
                                <p class="wholesale-picker-message" x-show="addProductsLoading" role="status">Memuat produk...</p>
                                <p class="wholesale-picker-message" x-show="addProductsError" x-cloak role="alert">Produk gagal dimuat. <button type="button" class="wholesale-picker-retry" @click="loadAddProducts()">Coba lagi</button></p>
                                <p class="wholesale-picker-message" x-show="!addProductsLoading && !addProductsError && addProducts.length === 0" x-cloak role="status">Produk tidak ditemukan</p>
                                <template x-for="product in addProducts" :key="product.id">
                                    <label class="wholesale-product-option" x-show="!addProductsLoading && !addProductsError">
                                        <input type="radio" name="wholesale-add-product" :value="product.id" :checked="String(addProduct.id) === String(product.id)" @change="selectAddProduct(product)">
                                        <span x-text="product.name + (product.unit?.abbreviation ? ' (' + product.unit.abbreviation + ')' : '')"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="wholesale-item-grid">
                        <div>
                            <label for="wholesale-item-qty" class="wholesale-item-label">Kuantitas <span x-show="addProduct.unit" x-text="'(' + addProduct.unit + ')'"></span></label>
                            <input id="wholesale-item-qty" type="number" inputmode="decimal" step="0.01" min="0.01" class="wholesale-item-input" x-model.number="addProduct.qty" @input="updateAddTotalFromQty()" placeholder="0" required>
                        </div>
                        <div>
                            <label for="wholesale-item-price" class="wholesale-item-label">Harga Beli / Satuan</label>
                            <input id="wholesale-item-price" type="text" inputmode="numeric" class="wholesale-item-input" x-model="formattedAddPrice" placeholder="0">
                        </div>
                        <div>
                            <label for="wholesale-item-sell" class="wholesale-item-label">Harga Jual</label>
                            <input id="wholesale-item-sell" type="text" inputmode="numeric" class="wholesale-item-input" x-model="formattedAddSell" placeholder="0">
                        </div>
                        <div>
                            <label for="wholesale-item-margin" class="wholesale-item-label">Margin (%)</label>
                            <input id="wholesale-item-margin" type="text" class="wholesale-item-input wholesale-item-margin" :value="addProduct_HPPPercent + '%'" readonly>
                        </div>
                    </div>
                    <div class="wholesale-item-total" aria-live="polite">
                        <span>Total Harga Item</span><strong x-text="'Rp ' + formatRupiah(addProduct.total || 0)"></strong>
                    </div>
                    <p class="wholesale-item-error" x-show="addValidationError" x-text="addValidationError" role="alert" x-cloak></p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="wholesale-item-submit">Tambah Item</button>
                </div>
            </form>
        </div>
    </div>
</template>
{{-- Modal Edit --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true"
    x-show="editModal" style="display: none;">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="modal-header" style="background-color: #ff000d; color: #fff;">
                <span class="fs-4 text-white fw-bold" x-text="editTitle"></span>
            </div>
            <div class="modal-body">
                <div x-show="editItem">
                    <div class="row mb-3">
                        <div class="col-8">
                            <label class="form-label">Nama Product</label>
                            <input type="text" class="form-control" x-model="editProductName" readonly>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Satuan</label>
                            <input type="text" class="form-control" step="0.01" min="0"
                                x-model="editProductUnit" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Harga Beli Satuan</label>
                            <input type="text" class="form-control" x-model="formattedEditPrice" @input="updateTotalFromEditQty">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Harga Jual Nanti</label>
                            <input type="text" class="form-control" step="0.01" min="0"
                                x-model="formattedEditSell">
                        </div>
                    </div>
                    <!-- Input Qty & Persentase HPP -->
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" step="0.01" min="0" x-model="editQty"
                                @input="updateTotalFromEditQty">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Margin (%)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" x-model="edit_HPPPercent" readonly>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <!-- Input Mode Harga -->
                    <div class="mb-3">
                        <label class="form-label">Jumlah</label>
                        <input type="text" class="form-control" x-model="editTotalFormatted"
                            @input="updateEditTotalFormatted" inputmode="numeric">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger me-auto" @click="deleteFromCart()">Hapus Produk</button>
                <button class="btn btn-secondary" @click="closeEditModal()">Batal</button>
                <button class="btn btn-primary" @click="saveEditToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>