<div class="pos-cart">
    <template x-if="cart.length === 0">
        <div class="pos-cart-empty"><p>Belum ada produk didaftar belanja</p><span>Klik tombol <strong>+ Tambah</strong> untuk melakukan input</span></div>
    </template>
    <div id="cart-items-container">
        <template x-for="(item,index) in cart" :key="index">
            <div class="pos-cart-row cart-item" role="button" tabindex="0"
                @click="item.typeProduct === 'parcel' ? openEditParcelModal(item) : (item.typeProduct === 'jus' ? openEditJusModal(item) : openEditModal(item))"
                @keydown.enter.self="$el.click()">
                <span class="pos-cart-top">
                    <span class="pos-cart-name">
                        <strong x-text="item.name"></strong>
                        <span class="pos-cart-tag is-parcel" x-show="item.typeProduct === 'parcel'">Parcel</span>
                    </span>
                    <span class="pos-cart-side">
                        <span class="pos-cart-total">Rp <span x-text="(item.total_input || ((item.price * item.qty) - item.discount)).toLocaleString('id-ID')"></span></span>
                        <button type="button" class="pos-cart-remove" aria-label="Hapus produk"
                            @click.stop="Swal.fire({ title: 'Hapus produk?', text: item.name, icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' }).then(result => { if (result.isConfirmed) { editItem = item; deleteFromCart(); } })">
                            <i class="ph ph-trash" aria-hidden="true"></i>
                        </button>
                    </span>
                </span>
                <span class="pos-cart-bottom"><span x-text="item.price.toLocaleString('id-ID') + ' x ' + item.qty + ' ' + item.unit"></span></span>
                <span class="pos-cart-discount" :class="item.discount > 0 ? 'has-discount' : ''" x-text="item.discount > 0 ? 'Diskon Rp ' + item.discount.toLocaleString('id-ID') : 'Diskon -'"></span>
            </div>
        </template>
    </div>
</div>
