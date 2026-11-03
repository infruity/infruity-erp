{{-- Modal Add Gift --}}
<div class="modal fade" id="giftModal" tabindex="-1" aria-labelledby="giftModalLabel" aria-hidden="true"
    x-show="showGiftModal" style="display: none;">
    <div class="modal-dialog erp-sheet-dialog modal-fullscreen-sm-down">
        <div class="modal-content" x-data>
            <div class="erp-sheet-header" style="background-color: #ff000d; color: #fff;">
                <h5 class="modal-title" style="color: #fff">Tambah Hadiah</h5>
                <button type="button" class="btn-close" @click="closeGiftModal()"></button>
            </div>
            <div class="erp-sheet-body">
                <!-- Select Produk -->
                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <select id="select_gift" class="form-select"></select>
                </div>

                <!-- Satuan -->
                <div class="row">
                    <div class="col-3 mb-3">
                        <label class="form-label">Satuan</label>
                        <input type="text" class="form-control" x-model="addProduct.unit" readonly>
                    </div>

                    <!-- Harga -->
                    <div class="col-9 mb-3">
                        <label class="form-label">Harga</label>
                        <input type="text" class="form-control" x-model="formattedAddPrice"
                            @input="updateAddPriceFromFormatted" readonly>
                    </div>
                </div>

                <!-- Quantity -->
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control" step="0.01" min="0" x-model="addProduct.qty">
                </div>
            </div>
            <div class="erp-sheet-footer">
                <button class="btn btn-secondary" @click="closeGiftModal()">Tutup</button>
                <button class="btn btn-primary" @click="saveGiftToCart()">Simpan</button>
            </div>
        </div>
    </div>
</div>
