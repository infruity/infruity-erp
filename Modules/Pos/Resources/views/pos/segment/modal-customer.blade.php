{{-- Modal Tambah Pelanggan: disimpan lewat /pos/customers, lalu dipilih dengan ID aslinya (lihat saveCustomer di js-buah). --}}
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered erp-sheet-dialog pos-sheet pos-sheet--compact">
        <div class="modal-content" x-data="{ customerName: '', customerPhone: '', customerAddress: '' }">
            <div class="erp-sheet-header pos-sheet-header">
                <div class="pos-sheet-title">
                    <span class="pos-sheet-icon" aria-hidden="true"><i class="ph-bold ph-user-plus"></i></span>
                    <h5 class="modal-title" id="customerModalLabel">Tambah Pelanggan Baru</h5>
                </div>
                <button type="button" data-bs-dismiss="modal" aria-label="Tutup"><i class="ph-bold ph-x" aria-hidden="true"></i></button>
            </div>
            <div class="erp-sheet-body">
                <div class="mb-3">
                    <label class="form-label required">Nama Pelanggan</label>
                    <input type="text" class="form-control" x-model="customerName" placeholder="Masukkan nama pelanggan">
                </div>
                <div class="mb-3">
                    <label class="form-label required">No WhatsApp</label>
                    <input type="tel" inputmode="tel" autocomplete="tel" class="form-control" x-model="customerPhone" placeholder="Contoh: 081234567890">
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea class="form-control" x-model="customerAddress" rows="2" placeholder="Alamat pengiriman (opsional)"></textarea>
                </div>
            </div>
            <div class="erp-sheet-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" @click="saveCustomer()">Simpan &amp; Pilih</button>
            </div>
        </div>
    </div>
</div>
