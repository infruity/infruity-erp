<div class="pos-card pos-card--flush">
<div class="accordion" id="accordionTotal">
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingTotal">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapseTotal" aria-expanded="false" aria-controls="collapseTotal">
                Rincian Total
            </button>
        </h2>
        <div id="collapseTotal" class="accordion-collapse collapse" aria-labelledby="headingTotal"
            data-bs-parent="#accordionTotal">
            <div class="accordion-body">
                <!-- Subtotal -->
                <div class="mb-3 d-flex justify-content-between">
                    <span class="fw-semibold">Subtotal</span>
                    <span class="text-end">Rp <span x-text="formatRupiah(subtotal)"></span></span>
                </div>

                <!-- Input Diskon -->
                <div class="mb-3">
                    <label class="form-label mb-1">Diskon (Rp jika > 100, % jika ≤ 100)</label>
                    <input type="text" class="form-control text-end" :value="formatRupiah(diskonGlobal)"
                        @input="updateDiskonGlobal" @focus="$event.target.select()">
                </div>

                <!-- Note -->
                <div x-data="{ showNote: false }" class="mb-3">
                    <!-- Tombol Toggle -->
                    <button type="button" class="btn btn-sm" :class="showNote ? 'btn-danger' : 'btn-outline-primary'"
                        @click="showNote = !showNote">
                        <template x-if="!showNote">
                            <span><i class="fa fa-plus"></i> Tambah Catatan</span>
                        </template>
                        <template x-if="showNote">
                            <span><i class="fa fa-times"></i> Sembunyikan Catatan</span>
                        </template>
                    </button>

                    <!-- Input Note -->
                    <div x-show="showNote" x-transition class="mt-2">
                        <label class="form-label mb-1">Note</label>
                        <textarea name="note" id="note" cols="30" rows="5" class="form-control"></textarea>
                    </div>
                </div>

                <div x-data="{ showOngkir: false }" class="mb-3">
                    <!-- Tombol Toggle -->
                    <button type="button" class="btn btn-sm" :class="showOngkir ? 'btn-danger' : 'btn-outline-primary'"
                        @click="showOngkir = !showOngkir">
                        <template x-if="!showOngkir">
                            <span><i class="fa fa-plus"></i> Tambah Ongkir</span>
                        </template>
                        <template x-if="showOngkir">
                            <span><i class="fa fa-times"></i> Sembunyikan Ongkir</span>
                        </template>
                    </button>

                    <!-- Input Note -->
                    <div x-show="showOngkir" x-transition class="mt-2">
                        <!-- Input Ongkir -->
                        <div class="mb-3">
                            <label class="form-label mb-1">Alamat Pengiriman</label>
                            {{-- <textarea name="ongkir_address" id="ongkir_address" cols="30" rows="5" class="form-control"></textarea>
                            <br> --}}
                            <select class="form-control" name="ongkir_address" id="address_id"></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label mb-1">Pilih Kurir</label>
                            <select class="form-control" name="courier_id" id="courier_id">
                                <option value="">Pilih Kurir</option>
                            </select>
                            <input type="hidden" name="courier_type" id="courier_type" value="">
                        </div>
                        <div class="mb-3 row">
                            <div class="col">
                                <label class="form-label mb-1">Jadwal</label>
                                <input type="date" class="form-control" name="ongkir_date" id="ongkir_date"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col mt-1">
                                <label class="form-label mb-1"></label>
                                <input type="time" class="form-control" name="ongkir_time" id="ongkir_time">
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col">
                                <label class="form-label mb-1">Biaya Pengiriman</label>
                                <input type="text" class="form-control text-end" inputmode="numeric"
                                    :value="formatRupiah(ongkirGlobal)" @input="updateOngkirGlobal" @focus="$event.target.select()">
                            </div>
                            <div class="mb-3 col">
                                <label class="form-label mb-1">Diskon Ongkir</label>
                                <input type="text" class="form-control text-end" inputmode="numeric"
                                    :value="formatRupiah(diskonOngkir)" @input="updateDiskonOngkir" @focus="$event.target.select()">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- <!-- Total Setelah Diskon -->
                <div class="pt-2 border-top mt-3 d-flex justify-content-between">
                    <span class="fw-bold">Total Setelah Diskon</span>
                    <span class="fw-bold text-end">Rp <span x-text="formatRupiah(totalHargaKeseluruhan)"></span></span>
                </div> --}}
            </div>

        </div>
    </div>
</div>

</div>

<div class="pos-form-footer"><div class="pos-form-footer__inner">
    <div class="d-flex justify-content-between">
        <div>
            <span>Total</span> <span x-text="totalProduk"></span><span> Item</span>
        </div>
        <div class="fw-bold">
            <span>Rp</span>
            <span x-text="formatRupiah(totalHargaKeseluruhan)"></span>
        </div>
    </div>
    <div class="row mt-3 gap-2">
        <div class="col" x-data="{ loading: false }">
            <button class="btn btn-primary w-100" @click="loading = true; saveTransaction(() => loading = false)"
                :disabled="loading">
                <template x-if="!loading">
                    <span><i class="bi bi-save me-2"></i> Simpan</span>
                </template>
                <template x-if="loading">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                </template>
            </button>
        </div>
        <div class="col" x-data="{ loading: false }">
            <button class="btn btn-success w-100" @click="loading = true; goToPayment(() => loading = false)"
                :disabled="loading">
                <template x-if="!loading">
                    <span><i class="bi bi-cash-stack me-2"></i> Bayar</span>
                </template>
                <template x-if="loading">
                    <span>
                        <span class="spinner-border spinner-border-sm align-middle me-2"></span>
                        Memproses...
                    </span>
                </template>
            </button>
        </div>
    </div>
</div>
</div><!-- /pos-form-footer -->

<!-- Modal Tambah Alamat -->
<div class="modal fade" id="modal_tambah_alamat" tabindex="-1" aria-labelledby="modalTambahAlamatLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form_tambah_alamat">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahAlamatLabel">Tambah Alamat Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="alamat_baru" class="form-label">Alamat</label>
                        <textarea id="alamat_baru" class="form-control" rows="3" placeholder="Masukkan alamat lengkap" required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
