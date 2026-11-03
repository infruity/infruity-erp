<div class="sortir-items-body">
    <div x-show="cart.length === 0" class="sortir-empty">
        <h2>Belum ada produk di daftar sortir</h2>
        <p>Klik tombol <button type="button" @click="openAddModal()">+ Tambah</button> untuk melakukan input</p>
    </div>
    <div x-show="cart.length > 0">
        <template x-for="item in cart" :key="item.id">
            <button type="button" class="sortir-cart-item" @click="openEditModal(item)" :aria-label="'Edit ' + item.name" :disabled="saving">
                <span class="sortir-cart-line"><strong x-text="item.name"></strong><b x-text="rupiah(item.qty * item.price)"></b></span>
                <span class="sortir-cart-line"><small x-text="rupiah(item.price) + ' / ' + item.unit"></small><small x-text="number(item.qty) + ' ' + item.unit"></small></span>
            </button>
        </template>
    </div>
</div>
