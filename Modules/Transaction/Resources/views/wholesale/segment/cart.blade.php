<div class="wholesale-cart-list">
    <template x-if="cart.length === 0">
        <div class="wholesale-cart-empty">
            <span class="wholesale-cart-empty-icon"><i class="ph ph-basket"></i></span>
            <strong>Belum ada bahan di daftar belanja</strong>
            <span>Tambahkan item untuk mulai menghitung total pengadaan.</span>
        </div>
    </template>

    <div id="cart-items-container">
        <template x-for="(item, index) in cart" :key="index">
            <button type="button" class="wholesale-cart-item cart-item"
                :disabled="{{ $isWholesaleReadOnly ? 'true' : 'false' }}"
                :class="item.typeProduct === 'gift' ? 'is-gift' : ''"
                @click="item.typeProduct === 'parcel' ? openEditParcelModal(item) : openEditModal(item)">
                <span class="wholesale-cart-item-copy">
                    <strong class="wholesale-cart-item-name" x-text="item.name"></strong>
                    <span class="wholesale-cart-item-meta">
                        Rp <span x-text="(item.price || 0).toLocaleString('id-ID')"></span>
                        <span> / </span><span x-text="item.unit || 'satuan'"></span>
                    </span>
                    @unless ($isWholesaleEdit)
                        <span class="wholesale-cart-item-quantity">
                            <span x-text="(item.qty || 0).toLocaleString('id-ID')"></span>
                            <span x-text="item.unit || 'satuan'"></span>
                            <template x-if="item.discount > 0">
                                <span class="wholesale-cart-item-discount">Diskon Rp <span x-text="item.discount.toLocaleString('id-ID')"></span></span>
                            </template>
                        </span>
                    @endunless
                </span>
                <span class="wholesale-cart-item-total">
                    <strong>Rp <span x-text="(item.total_input || ((item.price * item.qty) - item.discount)).toLocaleString('id-ID')"></span></strong>
                    @if ($isWholesaleEdit)
                        <span class="wholesale-edit-item-quantity"><span x-text="(item.qty || 0).toLocaleString('id-ID')"></span> <span x-text="item.unit || 'satuan'"></span></span>
                    @else
                        <i class="ph ph-caret-right" aria-hidden="true"></i>
                    @endif
                </span>
            </button>
        </template>
    </div>
</div>
