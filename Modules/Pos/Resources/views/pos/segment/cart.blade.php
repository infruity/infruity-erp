<div class="pos-cart">
    <template x-if="cart.length === 0">
        <div class="pos-cart-empty"><p>Belum ada produk didaftar belanja</p><span>Klik tombol <strong>+ Tambah</strong> untuk melakukan input</span></div>
    </template>
    <div id="cart-items-container">
        <template x-for="(item,index) in cart" :key="index">
            <div class="pos-cart-swipe"
                x-data="{ startX: 0, startY: 0, swiped: false, open: false,
                    down(e) { if (e.target.closest('button')) return; const t = e.touches ? e.touches[0] : e; this.startX = t.clientX; this.startY = t.clientY; this.swiped = false; },
                    move(e) { if (!this.startX) return; const t = e.touches ? e.touches[0] : e; const dx = this.startX - t.clientX; const dy = Math.abs(this.startY - t.clientY);
                        if (Math.abs(dx) > 10 || dy > 10) this.swiped = true;
                        if (dx > 35 && dx > dy) this.open = true; else if (dx < -25 && Math.abs(dx) > dy) this.open = false; },
                    up() { this.startX = 0; this.startY = 0; setTimeout(() => { this.swiped = false; }, 300); },
                    activate(item) { if (this.swiped) return; if (this.open) { this.open = false; return; }
                        item.typeProduct === 'parcel' ? openEditParcelModal(item) : (item.typeProduct === 'jus' ? openEditJusModal(item) : openEditModal(item)); }
                }"
                @click.outside="open = false">
                <button type="button" class="pos-cart-swipe__remove" aria-label="Hapus produk" title="Hapus Produk"
                    @click.stop="open = false; Swal.fire({ title: 'Hapus produk?', text: item.name, icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal' }).then(result => { if (result.isConfirmed) { editItem = item; deleteFromCart(); } })">
                    <i class="ph-bold ph-trash" aria-hidden="true"></i><span>Hapus</span>
                </button>
                <div class="pos-cart-row cart-item" :class="{ 'is-open': open }" role="button" tabindex="0"
                    @click="activate(item)"
                    @keydown.enter.self="$el.click()"
                    @touchstart.passive="down($event)" @touchmove.passive="move($event)" @touchend="up()"
                    @mousedown="down($event)" @mousemove="if (startX) move($event)" @mouseup="up()" @mouseleave="up()">
                    <span class="pos-cart-top">
                        <span class="pos-cart-name">
                            <strong x-text="item.name"></strong>
                            <i class="ph-bold ph-warning-circle pos-cart-lock" x-show="parcelNeedsComposition(item)" title="Komposisi parcel belum diisi" aria-label="Komposisi parcel belum diisi"></i>
                        </span>
                    </span>
                    <span class="pos-cart-bottom"><span x-text="item.price.toLocaleString('id-ID') + ' x ' + item.qty + ' ' + item.unit"></span><span class="pos-cart-total">Rp <span x-text="(item.price * item.qty).toLocaleString('id-ID')"></span></span></span>
                    <span class="pos-cart-discount" :class="item.discount > 0 ? 'has-discount' : ''">
                        <span x-text="item.discount > 0 ? (Number(item.discountPercent) > 0 ? 'Diskon (' + String(Number(item.discountPercent)).replace('.', ',') + '%)' : 'Diskon') : 'Diskon -'"></span>
                        <span x-show="item.discount > 0" x-text="'-Rp ' + item.discount.toLocaleString('id-ID')"></span>
                    </span>
                </div>
            </div>
        </template>
    </div>
</div>
