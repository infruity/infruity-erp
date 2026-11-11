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
                @include('pos::pos.segment.add-tabs', ['active' => 'parcel'])
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
                        <header class="pos-ref-panel__head"><h3>Tambah Parcel</h3><p>Lengkapi detail parcel dan pilih komposisinya</p></header>
                @endif
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
                @if (! empty($reference))
                    </section>
                @endif
            </div>
            @if (! empty($reference))
            <div class="erp-sheet-footer pos-ref-actions-bar">
                <button type="button" class="pos-ref-back" @click="closeParcelModal()">Kembali</button>
                <button type="button" class="pos-ref-add" @click="saveParcelToCart()">Tambah</button>
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
