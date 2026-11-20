{{-- Add Parcel: reference mode is an in-page category view; Pesanan retains its modal. --}}
@if (! empty($reference))
<section id="parcelModal" class="pos-add-page pos-add-page--parcel" role="region" aria-labelledby="parcelModalLabel"
    x-show="showAddPage && activeAddCategory === 'parcel'" x-cloak x-data="parcelForm()" x-init="initSelect2">
    <div class="pos-add-page__content pos-sheet--ref">
@else
<div class="modal fade" id="parcelModal" tabindex="-1" aria-labelledby="parcelModalLabel" aria-hidden="true"
    x-show="showParcelModal" style="display: none;" x-data="parcelForm()" x-init="initSelect2">
    <div class="modal-dialog erp-sheet-dialog pos-sheet modal-fullscreen-sm-down">
@endif
        <div class="modal-content" x-data>
            @if (! empty($reference))
            <div class="erp-sheet-header pos-sheet-header pos-sheet-header--tabs">
                <span id="parcelModalLabel" class="visually-hidden">Tambah Parcel</span>
                <div x-show="!editId">@include('pos::pos.segment.add-tabs', ['active' => 'parcel'])</div>
                <div x-show="editId" x-cloak>@include('pos::pos.segment.add-tabs', ['active' => 'parcel', 'locked' => true])</div>
            </div>
            @else
            <div class="erp-sheet-header pos-sheet-header">
                <h5 class="modal-title">Tambah Parcel</h5>
                <button type="button" class="btn-close" @click="closeParcelModal()"></button>
            </div>
            @endif
            <div class="erp-sheet-body {{ ! empty($reference) ? 'pos-ref-body' : '' }}">
                @if (! empty($reference))
                    <section class="pos-ref-panel">
                        <header class="pos-ref-panel__head"><h3 x-text="editId ? 'Edit Parcel' : 'Tambah Parcel'">Tambah Parcel</h3><p>Pilih produk dan tentukan kuantitas belanja</p></header>
                @endif
                @if (! empty($reference))
                <div class="pos-ref-parcel-grid">
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label pos-ref-required">PRODUK</span>
                        <span class="pos-ref-cell__field is-readonly"><input type="text" readonly :value="parcelLabel()" placeholder="Otomatis dari Budget"></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label pos-ref-required">BUDGET</span>
                        <span class="pos-ref-cell__field"><span>Rp</span><input type="text" class="format-number" id="parcel_budget" name="parcel_budget" inputmode="numeric" placeholder="0" @input="updateTotal"></span>
                    </label>
                </div>
                {{-- The Plan has no packaging picker; the default kemasan is applied on save (see saveParcelToCart). --}}
                <select id="select_kemasan" class="form-select" hidden aria-hidden="true" tabindex="-1"></select>
                <input type="hidden" id="parcel_qty" name="parcel_qty" value="1">
                <input type="text" id="kemasan_price" name="kemasan_price" hidden readonly>
                <div class="pos-ref-parcel-pick pos-ref-parcel-products" :class="{ 'is-locked': !hasBudget }" @click.outside="pickerOpen = false">
                    <span class="pos-ref-cell__label pos-ref-required">PILIH PRODUK</span>
                    <button type="button" class="pos-ref-parcel-trigger" x-show="!pickerOpen" :aria-expanded="pickerOpen" @click="togglePicker()">
                        <span class="pos-ref-parcel-trigger__text">
                            <span class="is-muted">Pilih buah atau keranjang...</span>
                        </span>
                        <i class="ph-bold ph-caret-down" :class="{ 'is-open': pickerOpen }" aria-hidden="true"></i>
                    </button>
                    <div class="pos-ref-parcel-trigger pos-ref-parcel-trigger--search" x-show="pickerOpen" x-cloak>
                        <i class="ph-bold ph-magnifying-glass pos-ref-parcel-trigger__lead" aria-hidden="true"></i>
                        <input type="search" x-ref="pickerSearch" x-model="pickerSearch" @input.debounce.250ms="loadPickerProducts()" @keydown.escape="pickerOpen = false" placeholder="Cari nama buah atau keranjang..." autocomplete="off">
                        <button type="button" class="pos-ref-parcel-trigger__close" aria-label="Tutup daftar produk" @click="togglePicker()"><i class="ph-bold ph-caret-down is-open" aria-hidden="true"></i></button>
                    </div>
                    <div class="pos-ref-parcel-popover" x-show="pickerOpen" x-cloak>
                        <ul class="pos-ref-parcel-popover__list" role="listbox">
                            <li class="pos-ref-parcel-popover__state" x-show="pickerState === 'loading'">Memuat produk…</li>
                            <li class="pos-ref-parcel-popover__state" x-show="pickerState === 'ready' && pickerResults.length === 0"><strong>Produk buah / keranjang tidak ditemukan</strong><span>Coba kata kunci pencarian yang lain</span></li>
                            <template x-for="row in pickerResults" :key="row.id">
                                <li>
                                    <button type="button" role="option" class="pos-ref-parcel-option" :disabled="row.stock_available <= 0" @click="addPickedProduct(row)">
                                        <span class="pos-ref-parcel-option__main"><strong x-text="row.text"></strong><small x-text="'Rp ' + Number(row.price || 0).toLocaleString('id-ID') + ' / ' + getUnitLabel(row.unit)"></small></span>
                                        <span class="pos-ref-parcel-option__stock" x-text="stockText(row)"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
                <div class="pos-ref-parcel-composition">
                    <div class="pos-ref-parcel-composition__head">
                        <strong>Komposisi Produk</strong>
                        <small x-show="parcels.length > 0" x-text="'(' + parcels.length + ' Komponen)'"></small>
                    </div>
                    <div class="pos-ref-parcel-composition__body">
                        <div class="pos-ref-parcel-composition__empty" x-show="parcels.length === 0">
                            <strong x-text="hasBudget ? 'Pilih Buah atau Keranjang di Atas' : 'Input Budget'"></strong>
                            <span x-show="!hasBudget">Untuk bisa tambahkan produk ke<br>dalam komposisi parcel</span>
                            <span x-show="hasBudget" x-cloak>Daftar buah dan keranjang akan<br>ditampilkan secara otomatis</span>
                        </div>
                        <template x-for="(item, index) in parcels" :key="index">
                            <div class="pos-ref-parcel-line" x-data="{ startX: 0, startY: 0, open: false, dragging: false }" @click.outside="open = false"
                                @mousedown="if (!$event.target.closest('input, button')) { dragging = true; startX = $event.clientX; startY = $event.clientY; }"
                                @mousemove="if (dragging) { const dx = startX - $event.clientX; const dy = Math.abs(startY - $event.clientY); if (dx > 35 && dx > dy) open = true; else if (dx < -25 && Math.abs(dx) > dy) open = false; }"
                                @mouseup.window="dragging = false"
                                @touchstart.passive="startX = $event.touches[0].clientX; startY = $event.touches[0].clientY"
                                @touchmove.passive="const dx = startX - $event.touches[0].clientX; const dy = Math.abs(startY - $event.touches[0].clientY); if (dx > 35 && dx > dy) open = true; else if (dx < -25 && Math.abs(dx) > dy) open = false">
                                <button type="button" class="pos-ref-parcel-line__remove" @click.stop="removeParcel(index)" title="Hapus Produk"><i class="ph-bold ph-trash" aria-hidden="true"></i><span>Hapus</span></button>
                                <div class="pos-ref-parcel-line__body" :class="{ 'is-open': open }">
                                    <div class="pos-ref-parcel-line__top">
                                        <strong x-text="item.displayName || formatParcelProductName(item)"></strong>
                                        <small><span x-text="'Rp ' + Number(item.priceAwal || 0).toLocaleString('id-ID') + ' /'"></span><span class="pos-ref-line-unit" x-text="getUnitLabel(item.unit)"></span></small>
                                    </div>
                                    <div class="pos-ref-parcel-line__bottom">
                                        <label class="pos-ref-parcel-line__price"><span>Rp</span><input type="text" inputmode="numeric" :value="Number(item.price || 0).toLocaleString('id-ID')" @change="item.priceFormatted = $event.target.value; updatePrice(index)" @keydown.enter="$event.target.blur()" aria-label="Harga jual"></label>
                                        <label class="pos-ref-parcel-line__qty"><input type="text" inputmode="decimal" maxlength="5" :value="String(item.qty || 0).replace('.', ',')" @change="item.qty = parseFloat(String($event.target.value).replace(',', '.')) || 0; updateQty(index)" @keydown.enter="$event.target.blur()" aria-label="Kuantitas"><span class="pos-ref-line-unit" x-text="getUnitLabel(item.unit)"></span></label>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="pos-ref-grid">
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">BIAYA JASA</span>
                        <span class="pos-ref-cell__field" :class="{ 'is-readonly': parcels.length === 0 }"><span>Rp</span><input type="text" class="format-number" id="parcel_jasa" name="parcel_jasa" inputmode="numeric" placeholder="0" :disabled="parcels.length === 0" @input="updateTotal"></span>
                    </label>
                    <label class="pos-ref-cell">
                        <span class="pos-ref-cell__label">TOTAL MODAL</span>
                        <span class="pos-ref-cell__field is-readonly"><span>Rp</span><input type="text" readonly :value="totalAll > 0 ? totalAll.toLocaleString('id-ID') : '0'"></span>
                    </label>
                </div>
                @else
                <!-- Select Produk -->
                <div class="row">
                    <div class="{{ ! empty($reference) ? 'col-6' : 'col-3' }} mb-3">
                        <label class="form-label">Qty</label>
                        <input type="number" class="form-control" id="parcel_qty" name="parcel_qty" placeholder="1"
                            min="1" value="1">
                    </div>

                    <!-- Harga -->
                    <div class="{{ ! empty($reference) ? 'col-6' : 'col-9' }} mb-3">
                        <label class="form-label">Budget</label>
                        <input type="text" class="form-control format-number" id="parcel_budget" name="parcel_budget"
                            placeholder="Masukkan budget">
                    </div>
                </div>
                <div class="row">
                    <div class="col mb-3">
                        <label class="form-label">Pilih Kemasan</label>
                        <select id="select_kemasan" class="form-select"></select>
                    </div>
                    <div class="col mb-3">
                        <label class="form-label">Harga</label>
                        <input type="text" class="form-control format-number" id="kemasan_price" name="kemasan_price"
                            readonly placeholder="Rp. 0">
                    </div>
                </div>
                @if (empty($reference))
                    <div class="mb-3">
                        <label class="form-label">Biaya Jasa</label>
                        <input type="text" class="form-control format-number" id="parcel_jasa" name="parcel_jasa"
                            inputmode="numeric" @input="updateTotal" placeholder="Masukkan Biaya jasa">
                    </div>
                @endif
                @if (empty($reference))
                    <div class="pos-sheet-section">Isi Parcel</div>
                @endif
                <div>
                    <div class="{{ ! empty($reference) ? 'pos-ref-parcel-composition' : '' }}">
                        @if (! empty($reference))
                            <h3>Komposisi Produk</h3>
                            <div class="pos-ref-parcel-composition__empty" x-show="parcels.length === 0">
                                <strong>Tambahkan produk parcel untuk mulai mengisi komposisi</strong>
                                <span>Produk yang dipilih akan tampil di sini.</span>
                            </div>
                        @endif
                        <template x-for="(item, index) in parcels" :key="index">
                            <div class="pos-sheet-row">
                                <div class="row">
                                    <div class="col-9 mb-3">
                                        <label class="form-label">Produk</label>
                                        <select class="form-select parcel-select" :data-index="index" x-model="item.product" style="width: 100%">
                                            <option :value="item.product" x-text="item.displayName || formatParcelProductName(item)"></option>
                                        </select>
                                    </div>
                                    <div class="col-3 mb-3">
                                        <label class="form-label">Qty</label>
                                        <input type="number" class="form-control" min="1" x-model.number="item.qty"
                                            @input="updateQty(index)">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Harga Jual (Rp)</label>
                                    <input type="text" class="form-control" x-model="item.priceFormatted"
                                        @input="updatePrice(index)" inputmode="numeric">
                                </div>
                                <button type="button" class="btn btn-danger btn-sm"
                                    @click="removeParcel(index)">Hapus</button>
                            </div>
                        </template>
                    </div>
                    @if (! empty($reference))
                        <div class="mb-3">
                            <label class="form-label">Biaya Jasa</label>
                            <input type="text" class="form-control format-number" id="parcel_jasa" name="parcel_jasa"
                                inputmode="numeric" @input="updateTotal" placeholder="Masukkan Biaya jasa">
                        </div>
                    @endif

                    <button type="button" class="btn btn-light-primary mt-2" @click="addParcel()"><i class="ph-bold ph-plus me-1" aria-hidden="true"></i>Tambah Produk</button>

                    <template x-if="parcels.length > 0">
                        <div class="pos-sheet-total">
                            <span>Total Keseluruhan</span>
                            <strong x-text="formatRupiah(totalAll)"></strong>
                        </div>
                    </template>
                </div>
                @endif
                @if (! empty($reference))
                    </section>
                @endif
            </div>
            @if (! empty($reference))
            <div class="erp-sheet-footer pos-ref-actions-bar">
                <button type="button" class="pos-ref-back" @click="closeParcelModal()">Kembali</button>
                <button type="button" class="pos-ref-add" :disabled="!hasBudget" @click="editId ? editParcelToCart(editId) : saveParcelToCart()" x-text="editId ? 'Simpan' : 'Tambah'">Tambah</button>
            </div>
            @else
            <div class="erp-sheet-footer">
                <button type="button" class="btn btn-light" @click="closeParcelModal()">Tutup</button>
                <button class="btn btn-primary" @click="saveParcelToCart()">Simpan</button>
            </div>
            @endif
        </div>
@if (! empty($reference))
    </div>
</section>
@else
    </div>
</div>
@endif

{{-- Modal Edit Parcel --}}
<div class="modal fade" id="parcelEditModal" tabindex="-1" aria-labelledby="parcelModalLabel" aria-hidden="true"
    x-show="showParcelModal" style="display: none;" x-data="parcelForm()">
    <div class="modal-dialog erp-sheet-dialog pos-sheet modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="erp-sheet-header pos-sheet-header">
                <h5 class="modal-title">Edit Parcel</h5>
                <button type="button" class="btn-close" @click="closeParcelModal()"></button>
            </div>
            <div class="erp-sheet-body">
                <!-- Select Produk -->
                <div class="row">
                    <div class="col-3 mb-3">
                        <label class="form-label">Qty</label>
                        <input type="number" class="form-control" id="parcel_edit_qty" name="parcel_qty"
                            placeholder="1" min="1" value="1">
                    </div>

                    <!-- Harga -->
                    <div class="col-9 mb-3">
                        <label class="form-label">Budget</label>
                        <input type="text" class="form-control format-number" id="parcel_edit_budget"
                            name="parcel_budget" placeholder="Masukkan budget">
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Pilih Kemasan</label>
                        <select id="select_edit_kemasan" class="form-select"></select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Harga</label>
                        <input type="text" class="form-control format-number" id="kemasan_edit_price"
                            name="kemasan_price" readonly placeholder="Rp. 0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Biaya Jasa</label>
                    <input type="text" class="form-control format-number" id="parcel_edit_jasa"
                        name="parcel_jasa" @input="updateTotal" placeholder="Masukkan Biaya jasa">
                </div>
                <div class="pos-sheet-section">Isi Parcel</div>
                <div>
                    <template x-for="(item, index) in parcels" :key="index">
                        <div class="pos-sheet-row">
                            <div class="row">
                                <div class="col-9 mb-3">
                                    <label class="form-label">Produk</label>
                                    <select class="form-select parcel-select-edit" :data-index="index" x-model="item.product" style="width: 100%">
                                        <option :value="item.product" x-text="item.displayName || formatParcelProductName(item)"></option>
                                    </select>
                                </div>
                                <div class="col-3 mb-3">
                                    <label class="form-label">Qty</label>
                                    <input type="number" class="form-control" min="1"
                                        x-model.number="item.qty" @input="updateQty(index)">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Harga Jual (Rp)</label>
                                <input type="text" class="form-control" x-model="item.priceFormatted"
                                    @input="updatePrice(index)" inputmode="numeric">
                            </div>
                            <button type="button" class="btn btn-danger btn-sm"
                                @click="removeParcel(index)">Hapus</button>
                        </div>
                    </template>

                    <button type="button" class="btn btn-light-primary mt-2" @click="addParcel()"><i class="ph-bold ph-plus me-1" aria-hidden="true"></i>Tambah Produk</button>

                    <template x-if="parcels.length > 0">
                        <div class="pos-sheet-total">
                            <span>Total Keseluruhan</span>
                            <strong x-text="formatRupiah(totalAll)"></strong>
                        </div>
                    </template>
                </div>
            </div>
            <div class="erp-sheet-footer">
                <button class="btn btn-danger me-auto" @click="deleteParcel(parcelId)">Hapus Parcel</button>
                <button class="btn btn-secondary" @click="closeParcelEditModal()">Tutup</button>
                <button class="btn btn-primary" @click="editParcelToCart(parcelId)">Simpan</button>
            </div>
        </div>
    </div>
</div>
