<dialog class="transfer-modal" x-ref="itemModal" aria-labelledby="transfer-item-title" @close="productRequest++; loadingProducts = false" @click="if ($event.target === $event.currentTarget) closeItemModal()">
    <form class="transfer-modal__content" @submit.prevent="saveItem()">
        <div class="transfer-modal__header"><div><h2 id="transfer-item-title" x-text="receiving ? 'Terima Produk' : (editingId === null ? 'Tambah Produk' : 'Edit Produk')"></h2><p x-text="receiving ? modalItem.name : (editingId === null ? 'Pilih produk dan tentukan kuantitas' : 'Perbarui kuantitas produk')"></p></div><button type="button" class="transfer-close" @click="closeItemModal()" aria-label="Tutup produk"><i class="ph-bold ph-x" aria-hidden="true"></i></button></div>
        <div class="transfer-modal__body">
            <div x-show="!receiving && editingId === null">
                <label class="transfer-modal-label" for="transfer-product-search">Pilih Produk / Bahan <span class="transfer-required">*</span></label>
                <div class="transfer-product-picker">
                    <div class="transfer-search-wrap"><i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i><input id="transfer-product-search" type="search" class="transfer-product-search" x-model="productSearch" @input.debounce.250ms="loadProducts()" placeholder="Cari produk / bahan..." autocomplete="off"></div>
                    <div class="transfer-product-options" :aria-busy="loadingProducts">
                        <div class="transfer-products-state" x-show="loadingProducts" role="status">Memuat produk...</div>
                        <div class="transfer-products-state" x-show="productsError && !loadingProducts" role="alert"><span x-text="productsError"></span><button type="button" @click="loadProducts()">Coba lagi</button></div>
                        <template x-for="product in products" :key="product.id"><label class="transfer-product-option" :class="String(modalItem.id) === String(product.id) ? 'is-selected' : ''" x-show="!loadingProducts && !productsError"><input type="radio" name="transfer-product" :value="product.id" :checked="String(modalItem.id) === String(product.id)" @change="selectProduct(product)"><span x-text="product.name + (product.unit ? ' (' + product.unit + ')' : '')"></span></label></template>
                        <div class="transfer-products-state" x-show="!loadingProducts && !productsError && !products.length">Tidak ada produk dengan stok tersedia.</div>
                    </div>
                </div>
            </div>
            <p class="transfer-product-summary" x-show="!receiving && editingId !== null" x-text="modalItem.name"></p>
            <div :class="receiving ? 'transfer-modal-fields' : 'transfer-quantity-full'">
                <div x-show="receiving"><label class="transfer-modal-label" for="transfer-sent-qty">Dikirim</label><div class="transfer-unit-field"><input id="transfer-sent-qty" class="transfer-modal-input" type="text" :value="number(modalItem.sent)" readonly><span x-text="modalItem.unit"></span></div></div>
                <div><label class="transfer-modal-label" for="transfer-qty" x-text="receiving ? 'Diterima *' : 'Kuantitas *'"></label><div class="transfer-unit-field"><input id="transfer-qty" class="transfer-modal-input" type="text" inputmode="decimal" :value="qtyInput" @input="updateQuantity($event)" placeholder="0" required autocomplete="off"><span x-text="modalItem.unit"></span></div></div>
            </div>
            <p class="transfer-stock" x-show="!receiving && modalItem.stock !== null && modalItem.id" x-text="'Stok tersedia: ' + number(modalItem.stock) + ' ' + (modalItem.unit || '')"></p>
            <div class="transfer-modal-note" x-show="receiving"><label class="transfer-modal-label" for="transfer-item-note">Catatan</label><input id="transfer-item-note" class="transfer-modal-input" type="text" x-model="modalItem.note" placeholder="Tuliskan catatan jika ada..." maxlength="1000"></div>
            <p class="transfer-error" x-show="modalError" x-text="modalError" role="alert"></p>
        </div>
        <div class="transfer-modal__footer"><button type="button" class="transfer-action transfer-action-danger" x-show="editingId !== null && !receiving" @click="deleteItem()"><i class="ph-bold ph-trash" aria-hidden="true"></i> Hapus Item</button><button type="submit" class="transfer-action transfer-action-primary" x-text="receiving ? 'Diterima' : (editingId === null ? 'Tambah' : 'Simpan')"></button></div>
    </form>
</dialog>
