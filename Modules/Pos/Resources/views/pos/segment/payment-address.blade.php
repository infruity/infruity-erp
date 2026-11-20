<link rel="stylesheet" href="{{ asset('assets/css/pos-delivery-address.css') }}?v={{ filemtime(public_path('assets/css/pos-delivery-address.css')) }}">
<script src="{{ asset('assets/js/pos-delivery-address.js') }}?v={{ filemtime(public_path('assets/js/pos-delivery-address.js')) }}"></script>
<div class="pos-pay-who{{ $isDraftLike ? ' is-link' : '' }}" x-data="posDeliveryAddress(@js([
    'url' => route('pos.addresses', $data->id), 'csrf' => csrf_token(),
    'snapshot' => $data->delivery_address_snapshot, 'name' => $data->customer?->name ?? 'Pelanggan Umum',
    'phone' => $data->customer?->whatsapp ?? '', 'address' => $addressText, 'pickup' => $isPickup, 'canDelete' => \App\Models\RoleMenu::isMasterAccount(),
]))" @pos-pickup-change.window="pickup = $event.detail.pickup" @keydown.escape.window="if (formOpen) dismissForm(); else if (pageOpen) close()"
    @if ($isDraftLike) @click="if (! $event.target.closest('#pay_customer')) document.getElementById('pay_customer')?.click()" @endif>
    <div class="pos-pay-customer">
        <div class="pos-pay-customer__name"><strong x-text="recipient"></strong><span x-show="phone" x-text="phone"></span></div>
    </div>
    <{{ $isDraftLike ? 'button' : 'div' }} @if ($isDraftLike) type="button" @endif id="pay_customer" class="pos-pay-address{{ $isDraftLike ? ' is-link' : '' }}{{ $isPickup ? ' is-locked' : '' }}"
        @if ($isDraftLike) @click="if ($el.getAttribute('aria-disabled') !== 'true') open()" data-address="{{ $addressText }}" data-pickup="{{ $pickupText }}" aria-label="Atur alamat pengiriman" @if ($isPickup) aria-disabled="true" tabindex="-1" @endif @endif>
        <p class="pos-pay-customer__address" x-text="pickup ? @js($pickupText) : addressText">{{ $isPickup ? $pickupText : $addressText }}</p>
        @if ($isDraftLike)<i class="ph ph-caret-right pos-pay-customer__chevron" aria-hidden="true" @if ($isPickup) hidden @endif></i>@endif
    </{{ $isDraftLike ? 'button' : 'div' }}>

    @if ($isDraftLike)
    <template x-teleport="body">
    <div class="pos-address-page" x-show="pageOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="pos-address-title" @keydown.tab="if (!formOpen) trap($event, $el)">
        <header class="pos-address-page__header">
            <button type="button" x-ref="pageBack" @click="close()" :disabled="busy" aria-label="Kembali ke Pembayaran"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></button>
            <h1 id="pos-address-title">Pilih Alamat</h1>
        </header>
        <div class="pos-address-page__canvas">
            <section class="pos-address-book" aria-label="Alamat tersimpan">
                <div class="pos-address-book__heading"><strong>Alamat</strong><span x-text="addresses.length + ' Alamat Tersimpan'"></span></div>
                <div class="pos-address-book__list">
                    <p class="pos-address-message" role="status" x-show="fetching">Memuat alamat...</p>
                    <div class="pos-address-message is-error" x-show="error" x-cloak role="alert"><span x-text="error"></span><button type="button" @click="open()">Coba Lagi</button></div>
                    <template x-for="addr in addresses" :key="addr.id">
                        <div class="pos-address-row" :class="{'is-selected': selectedId === addr.id}">
                            <button type="button" class="pos-address-choice" @click="choose(addr)" :disabled="busy || fetching" :aria-label="'Pilih alamat ' + addr.name" :aria-pressed="selectedId === addr.id">
                                <span class="pos-address-radio" aria-hidden="true"><span></span></span>
                                <span class="pos-address-copy"><span class="pos-address-recipient"><strong x-text="addr.name" :title="addr.name"></strong><span class="pos-address-separator">|</span><span x-text="phoneLabel(addr.phone)"></span></span>
                                <span class="pos-address-detail" x-text="addr.address"></span><span class="pos-address-tag" x-show="addr.is_default">Utama</span></span>
                            </button>
                            <button type="button" class="pos-address-edit" @click="edit(addr)" :disabled="busy || fetching" :aria-label="'Ubah alamat ' + addr.name">Ubah</button>
                        </div>
                    </template>
                    <div class="pos-address-empty" x-show="!fetching && !error && addresses.length === 0">
                        <strong>Belum Ada Alamat Tersimpan</strong><p>Klik tombol di bawah untuk menambah alamat baru.</p>
                    </div>
                </div>
                <footer class="pos-address-book__footer"><button type="button" @click="edit()" :disabled="busy || fetching || !!error"><i class="ph-bold ph-plus" aria-hidden="true"></i>Tambah Alamat Baru</button></footer>
            </section>
        </div>

        <div class="pos-address-modal" x-show="formOpen" x-cloak @click.self="dismissForm()" @keydown.tab.stop="trap($event, $refs.addressForm)">
            <form x-ref="addressForm" class="pos-address-form" @submit.prevent="save()" role="dialog" aria-modal="true" aria-labelledby="pos-address-form-title" x-show="formOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <header><h2 id="pos-address-form-title" x-text="editingId ? 'Ubah Alamat' : 'Tambah Alamat Baru'"></h2><button type="button" @click="dismissForm()" :disabled="busy" aria-label="Tutup formulir alamat"><i class="ph-bold ph-x" aria-hidden="true"></i></button></header>
                <div class="pos-address-form__body">
                    <p x-show="formError" x-cloak x-text="formError" class="pos-address-message is-error" role="alert"></p>
                    <label class="pos-address-label">Nama Penerima <em>*</em><input type="text" x-model="form.name" required maxlength="255" placeholder="Contoh: Noorman / Bu Maya..." :disabled="busy"></label>
                    <label class="pos-address-label">Nomor Telepon / WhatsApp <em>*</em><span class="pos-address-phone"><span>+62</span><input type="tel" x-model="form.phone" required inputmode="numeric" maxlength="15" @input="form.phone = $event.target.value.replace(/\D/g, '').replace(/^(62|0)/, '')" placeholder="81234567890..." :disabled="busy"></span></label>
                    <label class="pos-address-label is-detail">Detail Alamat <em>*</em><textarea x-model="form.address" required rows="3" maxlength="2000" placeholder="Nama jalan, gedung, nomor rumah, RT/RW, patokan, kecamatan, kota..." :disabled="busy"></textarea></label>
                    <div class="pos-address-default"><span>Atur sebagai Alamat Utama</span><button type="button" role="switch" aria-label="Atur sebagai Alamat Utama" :aria-checked="form.is_default" :class="{'is-on': form.is_default}" @click="form.is_default = !form.is_default" :disabled="busy"><span></span></button></div>
                </div>
                <footer><div><button type="button" class="pos-address-delete" x-show="editingId && canDelete" @click="remove()" :disabled="busy"><i class="ph-bold ph-trash" aria-hidden="true"></i>Hapus</button></div><div><button type="button" @click="dismissForm()" :disabled="busy">Batal</button><button type="submit" class="pos-address-save" :disabled="busy || !canSave" x-text="busy ? 'Menyimpan...' : 'Simpan Alamat'">Simpan Alamat</button></div></footer>
            </form>
        </div>
    </div>
    </template>
    @endif
</div>
