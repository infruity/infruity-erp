<div class="production-reference-picker" id="reference-product-picker" x-show="referenceProductPickerOpen" x-cloak
    role="dialog" aria-modal="true" aria-labelledby="reference-product-picker-title"
    @keydown.escape.window="referenceProductPickerOpen && closeReferenceProductPicker()">
    <div class="production-reference-picker__backdrop" x-show="referenceProductPickerOpen" aria-hidden="true" @click="closeReferenceProductPicker()" x-transition.opacity.duration.200ms></div>
    <section class="production-reference-picker__panel" x-show="referenceProductPickerOpen" @click.stop x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">
        <header class="production-reference-picker__header">
            <div>
                <h3 id="reference-product-picker-title">Pilih Produk Olahan</h3>
                <p>Produk yang akan diproduksi hari ini</p>
            </div>
            <button type="button" class="production-reference-picker__close" aria-label="Tutup pilihan produk" @click="closeReferenceProductPicker()">
                <i class="ph-bold ph-x" aria-hidden="true"></i>
            </button>
        </header>
        <div class="production-reference-picker__search">
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <input type="search" x-ref="referenceProductSearchInput" x-model="referenceProductSearch"
                @input.debounce.250ms="loadReferenceProducts()" placeholder="Cari nama produk..." autocomplete="off">
            <button type="button" x-show="referenceProductSearch" aria-label="Hapus pencarian" @click="referenceProductSearch = ''; loadReferenceProducts(); $refs.referenceProductSearchInput.focus()">
                <i class="ph-bold ph-x" aria-hidden="true"></i>
            </button>
        </div>
        <div class="production-reference-picker__list" role="listbox" aria-label="Produk olahan">
            <template x-for="product in referenceProducts" :key="product.id">
                <button type="button" role="option" :aria-selected="String(referenceProductId) === String(product.id)"
                    class="production-reference-picker__option" :class="String(referenceProductId) === String(product.id) ? 'is-selected' : ''"
                    @click="selectReferenceProduct(product)">
                    <span class="production-reference-picker__option-icon" aria-hidden="true" x-text="referenceProductEmoji(product.text)"></span>
                    <span class="production-reference-picker__option-text">
                        <strong x-text="product.text"></strong>
                        <small x-show="product.subtitle" x-text="product.subtitle"></small>
                    </span>
                    <i class="ph-bold ph-check production-reference-picker__check" aria-hidden="true"></i>
                </button>
            </template>
            <div class="production-reference-picker__state" x-show="referenceProductsLoading" role="status">
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Memuat produk…</span>
            </div>
            <div class="production-reference-picker__state" x-show="!referenceProductsLoading && referenceProducts.length === 0">
                <span x-text="referenceProductError || 'Produk olahan tidak ditemukan'"></span>
            </div>
        </div>
    </section>
</div>
