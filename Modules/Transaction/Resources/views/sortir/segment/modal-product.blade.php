<dialog class="sortir-modal" id="sortirItemModal" aria-labelledby="sortir-modal-title" x-ref="itemModal" @click.self="closeModal()" @cancel="onModalClosed()" @close="onModalClosed()">
    <div class="modal-dialog"><form class="modal-content" @submit.prevent="saveModalItem()">
        <div class="modal-header"><div><h2 id="sortir-modal-title" x-text="editingId === null ? 'Tambah Item Sortir' : 'Edit Item Sortir'"></h2><p x-text="editingId === null ? 'Pilih produk dan tentukan detail sortir' : 'Perbarui detail item sortir'"></p></div><button type="button" class="sortir-close" @click="closeModal()" aria-label="Tutup"><i class="ph-bold ph-x" aria-hidden="true"></i></button></div>
        <div class="modal-body">
            <label class="sortir-modal-label" for="sortir-product-search">Pilih Produk / Bahan <span class="sortir-required">*</span></label>
            <div class="sortir-product-picker">
                <div class="sortir-search-wrap"><i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i><input id="sortir-product-search" class="sortir-product-search" type="search" x-model="productSearch" @input.debounce.250ms="loadProducts()" placeholder="Cari produk / bahan..." autocomplete="off"></div>
                <div class="sortir-product-options" aria-label="Pilihan produk">
                    <div class="sortir-products-state" x-show="loadingProducts" role="status">Memuat produk...</div>
                    <div class="sortir-products-state" x-show="!loadingProducts && productsError" x-cloak><span x-text="productsError"></span><button type="button" @click="loadProducts()">Coba lagi</button></div>
                    <div class="sortir-products-state" x-show="!loadingProducts && !productsError && !products.length" x-cloak>Produk tidak ditemukan di cabang ini.</div>
                    <template x-for="product in products" :key="product.id"><label class="sortir-product-option" x-show="!loadingProducts && !productsError" :class="String(modalItem.id) === String(product.id) ? 'is-selected' : ''">
                        <input type="radio" name="sortir_product" :value="product.id" :checked="String(modalItem.id) === String(product.id)" @change="selectProduct(product)">
                        <span x-text="product.name + (product.unit?.abbreviation ? ' (' + product.unit.abbreviation + ')' : '')"></span>
                    </label></template>
                </div>
            </div>
            <p class="sortir-stock" x-show="modalItem.id && modalItem.stock !== null" x-text="'Stok tersedia: ' + number(modalItem.stock) + ' ' + modalItem.unit"></p>
            <div class="sortir-modal-fields"><div><label for="sortir-qty" class="sortir-modal-label">Kuantitas <span x-text="modalItem.unit ? '(' + modalItem.unit + ')' : ''"></span></label><input id="sortir-qty" class="sortir-modal-input" type="text" inputmode="decimal" :value="qtyInput" @input="updateQuantity($event)" placeholder="0" autocomplete="off"></div>
                <div><label for="sortir-price" class="sortir-modal-label">HPP / Satuan</label><input id="sortir-price" class="sortir-modal-input" type="text" inputmode="decimal" :value="number(modalItem.price)" @input="updatePrice($event)" placeholder="0" autocomplete="off"></div></div>
            <div class="sortir-modal-total"><span>Total HPP / Modal</span><strong x-text="rupiah(modalItem.qty * modalItem.price)"></strong></div>
            <p class="sortir-error" x-show="modalError" x-text="modalError" role="alert" x-cloak></p>
        </div>
        <div class="modal-footer"><button type="button" class="sortir-action sortir-action-danger" x-show="editingId !== null" @click="deleteModalItem()" x-cloak><i class="ph ph-trash" aria-hidden="true"></i>&nbsp; Hapus Item</button><button type="submit" class="sortir-action sortir-action-primary"><span x-text="editingId === null ? 'Tambah Item' : 'Simpan Perubahan'"></span></button></div>
    </form></div>
</dialog>
