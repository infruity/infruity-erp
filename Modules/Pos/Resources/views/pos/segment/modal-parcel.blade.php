{{-- Modal Add Parcel --}}
<div class="modal fade" id="parcelModal" tabindex="-1" aria-labelledby="parcelModalLabel" aria-hidden="true"
    x-show="showParcelModal" style="display: none;" x-data="parcelForm()" x-init="initSelect2">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="modal-header" style="background-color: #ff000d; color: #fff;">
                <h5 class="modal-title" style="color: #fff">Tambah Parcel</h5>
                <button type="button" class="btn-close" @click="closeParcelModal()"></button>
            </div>
            <div class="modal-body">
                <!-- Select Produk -->
                <div class="row">
                    <div class="col-3 mb-3">
                        <label class="form-label">Qty</label>
                        <input type="number" class="form-control" id="parcel_qty" name="parcel_qty" placeholder="1"
                            min="1" value="1">
                    </div>

                    <!-- Harga -->
                    <div class="col-9 mb-3">
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
                <div class="mb-3">
                    <label class="form-label">Biaya Jasa</label>
                    <input type="text" class="form-control format-number" id="parcel_jasa" name="parcel_jasa"
                        inputmode="numeric" @input="updateTotal" placeholder="Masukkan Biaya jasa">
                </div>
                <hr>

                <div>
                    <template x-for="(item, index) in parcels" :key="index">
                        <div class="border rounded p-3 mb-3">
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

                    <button type="button" class="btn btn-primary mt-2" @click="addParcel()">+ Tambah Produk</button>

                    <template x-if="parcels.length > 0">
                        <div>
                            <hr>
                            <h5>Total Keseluruhan: <span x-text="formatRupiah(totalAll)"></span></h5>
                        </div>
                    </template>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" @click="closeParcelModal()">Tutup</button>
                <button class="btn btn-primary" @click="saveParcelToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Edit Parcel --}}
<div class="modal fade" id="parcelEditModal" tabindex="-1" aria-labelledby="parcelModalLabel" aria-hidden="true"
    x-show="showParcelModal" style="display: none;" x-data="parcelForm()">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #ff000d; color: #fff;">
                <h5 class="modal-title" style="color: #fff">Edit Parcel</h5>
                <button type="button" class="btn-close" @click="closeParcelModal()"></button>
            </div>
            <div class="modal-body">
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
                <hr>

                <div>
                    <template x-for="(item, index) in parcels" :key="index">
                        <div class="border rounded p-3 mb-3">
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

                    <button type="button" class="btn btn-primary mt-2" @click="addParcel()">+ Tambah Produk</button>

                    <template x-if="parcels.length > 0">
                        <div>
                            <hr>
                            <h5>Total Keseluruhan: <span x-text="formatRupiah(totalAll)"></span></h5>
                        </div>
                    </template>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger me-auto" @click="deleteParcel(parcelId)">Hapus Parcel</button>
                <button class="btn btn-secondary" @click="closeParcelEditModal()">Tutup</button>
                <button class="btn btn-primary" @click="editParcelToCart(parcelId)">Simpan</button>
            </div>
        </div>
    </div>
</div>
