<div class="pos-cart">
    <template x-if="cart.length === 0">
        <div class="erp-cart-empty"><i class="ph ph-shopping-cart" aria-hidden="true"></i><p>Belum ada produk di daftar belanja</p><span>Klik + Tambah untuk memilih buah, parcel, atau jus.</span></div>
    </template>
    <div id="cart-items-container">
        <template x-for="(item,index) in cart" :key="index">
            <button type="button" class="pos-cart-row cart-item"
                @click="item.typeProduct === 'parcel' ? openEditParcelModal(item) : (item.typeProduct === 'jus' ? openEditJusModal(item) : openEditModal(item))">
                <span class="pos-cart-top">
                    <span class="pos-cart-name">
                        <strong x-text="item.name"></strong>
                        <span class="pos-cart-tag is-parcel" x-show="item.typeProduct === 'parcel'">Parcel</span>
                        <span class="pos-cart-tag is-discount" x-show="item.discount > 0">Diskon Rp <span x-text="item.discount.toLocaleString('id-ID')"></span></span>
                    </span>
                    <span class="pos-cart-total">Rp <span x-text="(item.total_input || ((item.price * item.qty) - item.discount)).toLocaleString('id-ID')"></span></span>
                </span>
                <span class="pos-cart-bottom">
                    <span>Rp <span x-text="item.price.toLocaleString('id-ID')"></span> / <span x-text="item.unit"></span></span>
                    <span><span x-text="item.qty"></span> <span x-text="item.unit"></span></span>
                </span>
            </button>
        </template>
    </div>
</div>
