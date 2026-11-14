@push('styles')
<style>
    :is(#addModal, #editModal) .modal-dialog { display:flex; align-items:center; width:calc(100% - 32px); max-width:512px; min-height:calc(100% - 32px); margin:16px auto; }
    :is(#addModal, #editModal) .modal-content { display:flex; width:100%; max-height:92dvh; min-height:0; overflow:hidden; border:1px solid #e5e7eb; border-radius:24px; background:#fff; box-shadow:0 25px 50px -12px rgba(0,0,0,.25); font-family:'Plus Jakarta Sans',sans-serif; }
    :is(#addModal, #editModal) .modal-header { display:flex; flex:none; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #f3f4f6; padding:16px 20px 14px; background:rgba(249,250,251,.5); }
    :is(#addModal, #editModal) .modal-title { margin:0; color:#111827; font-size:16px; line-height:1.25; font-weight:700; }
    :is(#addModal, #editModal) .wholesale-item-subtitle { margin:2px 0 0; color:#6b7280; font-size:12px; line-height:16px; }
    :is(#addModal, #editModal) .wholesale-item-close { display:flex; width:32px; height:32px; flex:none; align-items:center; justify-content:center; border:0; border-radius:50%; background:#fff; color:#9ca3af; cursor:pointer; }
    :is(#addModal, #editModal) .modal-body { min-height:0; overflow-y:auto; padding:16px; overscroll-behavior:contain; }
    :is(#addModal, #editModal) .wholesale-item-label { display:block; margin:0 0 6px; color:#6b7280; font-size:11px; font-weight:700; letter-spacing:.025em; line-height:1.5; text-transform:uppercase; }
    :is(#addModal, #editModal) .wholesale-item-label em { color:#ef4444; font-style:normal; }
    :is(#addModal, #editModal) .wholesale-product-picker { overflow:hidden; border:1px solid #e5e7eb; border-radius:12px; }
    :is(#addModal, #editModal) .wholesale-product-search { display:flex; align-items:center; gap:8px; border-bottom:1px solid #f3f4f6; padding:0 12px; color:#9ca3af; font-size:12px; }
    :is(#addModal, #editModal) .wholesale-product-search input { width:100%; min-width:0; height:40px; border:0; outline:none; background:#fff; color:#1f2937; font:inherit; font-size:13px; }
    :is(#addModal, #editModal) input::placeholder { color:#9ca3af; opacity:1; }
    :is(#addModal, #editModal) .wholesale-product-options { height:118px; overflow-y:auto; overscroll-behavior:contain; scrollbar-width:none; }
    :is(#addModal, #editModal) .wholesale-product-options::-webkit-scrollbar { display:none; }
    :is(#addModal, #editModal) .wholesale-product-option { display:flex; min-height:37px; align-items:center; gap:12px; margin:0; border-bottom:1px solid #f3f4f6; padding:10px 14px; color:#1f2937; font-size:12px; line-height:16px; cursor:pointer; }
    :is(#addModal, #editModal) .wholesale-product-option:last-child { border-bottom:0; }
    :is(#addModal, #editModal) .wholesale-product-option:hover { background:rgba(249,250,251,.8); }
    :is(#addModal, #editModal) .wholesale-product-option:has(input:checked) span { color:#111827; font-weight:700; }
    :is(#addModal, #editModal) .wholesale-product-option input { -webkit-appearance:none; appearance:none; width:16px; height:16px; flex:none; margin:0; border:2px solid #d1d5db; border-radius:50%; background:transparent; cursor:pointer; transition:border-color .15s; }
    :is(#addModal, #editModal) .wholesale-product-option input:checked { border-color:#10b981; background:radial-gradient(circle, #10b981 0 4px, transparent 4.5px); }
    :is(#addModal, #editModal) .wholesale-product-option span { min-width:0; flex:1; overflow:hidden; font-weight:500; color:#1f2937; text-overflow:ellipsis; white-space:nowrap; }
    :is(#addModal, #editModal) .wholesale-picker-message { margin:0; padding:18px 14px; color:#6b7280; font-size:12px; text-align:center; }
    :is(#addModal, #editModal) .wholesale-picker-retry { border:0; background:transparent; color:#0b595b; font:inherit; text-decoration:underline; cursor:pointer; }
    :is(#addModal, #editModal) .wholesale-item-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px 12px; margin-top:14px; }
    :is(#addModal, #editModal) .wholesale-item-input { box-sizing:border-box; width:100%; min-width:0; height:40px; border:1px solid #e5e7eb; border-radius:12px; padding:0 14px; background:#f9fafb; color:#1f2937; font-family:inherit; font-size:14px; font-weight:700; }
    :is(#addModal, #editModal) .wholesale-item-input::placeholder { color:#d1d5db; font-weight:400; }
    :is(#addModal, #editModal) .wholesale-item-input:focus { border-color:#059669; outline:2px solid rgba(5,150,105,.12); background:#fff; }
    :is(#addModal, #editModal) .wholesale-item-margin { background:#f3f4f6; color:#047857; font-size:16px; }
    :is(#addModal, #editModal) .wholesale-item-total { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:14px; border:1px solid #d1fae5; border-radius:12px; padding:12px; background:rgba(236,253,245,.6); color:#065f46; font-size:12px; font-weight:600; }
    :is(#addModal, #editModal) .wholesale-item-total strong { color:#0b595b; font-size:14px; font-weight:800; line-height:20px; font-variant-numeric:tabular-nums; }
    :is(#addModal, #editModal) .wholesale-item-error { margin:12px 0 0; color:#b91c1c; font-size:12px; }
    :is(#addModal, #editModal) .modal-footer { flex:none; border-top:1px solid #f3f4f6; margin:0; padding:16px; background:rgba(249,250,251,.5); }
    :is(#addModal, #editModal) .modal-footer > * { margin:0; }
    :is(#addModal, #editModal) .wholesale-item-submit { width:100%; min-height:44px; border:0; border-radius:12px; background:#0b595b; color:#fff; box-shadow:0 4px 6px -1px rgba(11,89,91,.2), 0 2px 4px -2px rgba(11,89,91,.2); font:inherit; font-size:12px; font-weight:700; cursor:pointer; }
    :is(#addModal, #editModal) .wholesale-item-submit:hover { background:#08484a; }
    :is(#addModal, #editModal) button:focus-visible, :is(#addModal, #editModal) input:focus-visible { outline:2px solid #0b595b; outline-offset:2px; }
    #editModal #wholesale-edit-search { -webkit-appearance:none; appearance:none; border:0 !important; outline:0 !important; box-shadow:none !important; background:#fff !important; }
    :is(#addModal, #editModal) #wholesale-product-search,
    :is(#addModal, #editModal) #wholesale-product-search:focus,
    :is(#addModal, #editModal) #wholesale-product-search:focus-visible,
    :is(#addModal, #editModal) #wholesale-product-search:active { -webkit-appearance:none; appearance:none; border:0 !important; outline:0 !important; box-shadow:none !important; -webkit-box-shadow:none !important; }
    @media (max-width:380px) { :is(#addModal, #editModal) .wholesale-item-label { font-size:10px; } :is(#addModal, #editModal) .modal-header { padding:16px; } }
    .wholesale-item-footer-split { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    :is(#addModal, #editModal) .wholesale-item-footer-split .wholesale-item-submit { flex:1; width:auto; }
    #editModal .wholesale-item-delete { display:flex; height:44px; flex:none; align-items:center; justify-content:center; gap:6px; border:1px solid #fecaca; border-radius:12px; padding:0 16px; background:#fef2f2; color:#dc2626; font:inherit; font-size:12px; font-weight:700; cursor:pointer; }
    #editModal .wholesale-item-delete:hover { background:#fee2e2; }
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
{{-- Modal Edit: same look as the add dialog (Plan "Edit Item Belanja"). --}}
<template x-teleport="body">
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-describedby="editModalDescription" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" @submit.prevent="saveEditToCart()">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="editModalLabel">Edit Item Belanja</h2>
                        <p class="wholesale-item-subtitle" id="editModalDescription">Perbarui detail item belanja</p>
                    </div>
                    <button type="button" class="wholesale-item-close" @click="closeEditModal()" aria-label="Tutup edit item"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
                </div>
                <div class="modal-body">
                    <div role="group" aria-labelledby="wholesale-edit-product-label">
                        <span class="wholesale-item-label" id="wholesale-edit-product-label">Pilih Produk / Bahan <em>*</em></span>
                        <div class="wholesale-product-picker">
                            <div class="wholesale-product-search">
                                <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                                <input id="wholesale-edit-search" type="search" placeholder="Cari produk / bahan..." aria-label="Cari produk atau bahan" autocomplete="off" disabled>
                            </div>
                            <div class="wholesale-product-options">
                                <label class="wholesale-product-option">
                                    <input type="radio" name="wholesale-edit-product" checked disabled>
                                    <span x-text="editProductName + (editProductUnit ? ' (' + editProductUnit + ')' : '')"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="wholesale-item-grid">
                        <div>
                            <label for="wholesale-edit-qty" class="wholesale-item-label">Kuantitas <span x-show="editProductUnit" x-text="'(' + editProductUnit + ')'"></span></label>
                            <input id="wholesale-edit-qty" type="number" inputmode="decimal" step="0.01" min="0.01" class="wholesale-item-input" x-model.number="editQty" @input="updateTotalFromEditQty()" placeholder="0" required>
                        </div>
                        <div>
                            <label for="wholesale-edit-price" class="wholesale-item-label">Harga Beli / Satuan</label>
                            <input id="wholesale-edit-price" type="text" inputmode="numeric" class="wholesale-item-input" x-model="formattedEditPrice" @input="updateTotalFromEditQty()" placeholder="0">
                        </div>
                        <div>
                            <label for="wholesale-edit-sell" class="wholesale-item-label">Harga Jual</label>
                            <input id="wholesale-edit-sell" type="text" inputmode="numeric" class="wholesale-item-input" x-model="formattedEditSell" placeholder="0">
                        </div>
                        <div>
                            <label for="wholesale-edit-margin" class="wholesale-item-label">Margin (%)</label>
                            <input id="wholesale-edit-margin" type="text" class="wholesale-item-input wholesale-item-margin" :value="edit_HPPPercent + '%'" readonly>
                        </div>
                    </div>
                    <div class="wholesale-item-total" aria-live="polite">
                        <span>Total Harga Item</span><strong x-text="'Rp ' + (editTotalFormatted || '0')"></strong>
                    </div>
                </div>
                <div class="modal-footer wholesale-item-footer-split">
                    <button type="button" class="wholesale-item-delete" @click="deleteFromCart()"><i class="ph-bold ph-trash" aria-hidden="true"></i> Hapus Item</button>
                    <button type="submit" class="wholesale-item-submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</template>
