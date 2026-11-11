<div class="pos-ref-root" x-data="{ sweepOpen: false }" @keydown.escape.window="sweepOpen = false">
<div class="pos-ref-backdrop" x-show="sweepOpen" x-cloak x-transition.opacity @click="sweepOpen = false"></div>
<div class="pos-form-footer pos-ref-footer">
    <div class="pos-form-footer__inner">
        <button type="button" class="pos-ref-handle" @click="sweepOpen = !sweepOpen" :aria-expanded="sweepOpen" aria-controls="collapseTotal" aria-label="Rincian total"><span></span></button>

        {{-- Lembar ringkasan: diskon nota, ongkos kirim, rincian, lalu catatan, pengiriman dan pengaturan transaksi --}}
        <div id="collapseTotal" class="pos-ref-sheet" x-show="sweepOpen" x-cloak x-transition>
            <div class="pos-ref-summary">
                <div class="pos-ref-summary__inputs">
                    <label class="pos-ref-money">
                        <span class="pos-ref-money__title">Diskon Nota</span>
                        <span class="pos-ref-money__field"><span>Rp</span><input type="text" class="form-control" inputmode="numeric" placeholder="0" :value="diskonGlobal ? formatRupiah(diskonGlobal) : ''" @input="updateDiskonGlobal" @focus="$event.target.select()" aria-describedby="pos-discount-hint"></span>
                    </label>
                    <label class="pos-ref-money">
                        <span class="pos-ref-money__title">Ongkos Kirim</span>
                        <span class="pos-ref-money__field"><span>Rp</span><input type="text" class="form-control" inputmode="numeric" placeholder="0" :value="ongkirGlobal ? formatRupiah(ongkirGlobal) : ''" @input="updateOngkirGlobal" @focus="$event.target.select()"></span>
                    </label>
                </div>
                <p id="pos-discount-hint" class="pos-ref-hint">Diskon nota: Rp jika lebih dari 100, persen jika 100 atau kurang.</p>
                <dl class="pos-ref-lines">
                    <div><dt>Subtotal Item (<span x-text="totalProduk"></span>)</dt><dd>Rp <span x-text="formatRupiah(subtotal)"></span></dd></div>
                    <div><dt>Diskon Transaksi</dt><dd>Rp <span x-text="formatRupiah((diskonGlobal > 0 && diskonGlobal <= 100) ? subtotal * diskonGlobal / 100 : diskonGlobal)"></span></dd></div>
                    <div><dt>Ongkos Kirim</dt><dd>+Rp <span x-text="formatRupiah(Math.max(0, (diskonOngkir > 0 && diskonOngkir <= 100) ? ongkirGlobal * (1 - diskonOngkir / 100) : ongkirGlobal - diskonOngkir))"></span></dd></div>
                </dl>
            </div>

            <div class="pos-ref-more">
                <details class="pos-ref-group">
                    <summary>Catatan</summary>
                    <div class="pos-ref-group__body">
                        <label class="form-label" for="note">Note</label>
                        <textarea name="note" id="note" cols="30" rows="4" class="form-control"></textarea>
                    </div>
                </details>
                <details class="pos-ref-group">
                    <summary>Pengiriman</summary>
                    <div class="pos-ref-group__body">
                        <div class="mb-3">
                            <label class="form-label mb-1">Alamat Pengiriman</label>
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
                                <label class="form-label mb-1" for="ongkir_date">Jadwal</label>
                                <input type="date" class="form-control" name="ongkir_date" id="ongkir_date" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col mt-1">
                                <label class="form-label mb-1" for="ongkir_time"></label>
                                <input type="time" class="form-control" name="ongkir_time" id="ongkir_time">
                            </div>
                        </div>
                        <div>
                            <label class="form-label mb-1">Diskon Ongkir</label>
                            <input type="text" class="form-control text-end" inputmode="numeric" :value="formatRupiah(diskonOngkir)" @input="updateDiskonOngkir" @focus="$event.target.select()">
                        </div>
                    </div>
                </details>
                <details class="pos-ref-group">
                    <summary>Pengaturan Transaksi</summary>
                    <div class="pos-ref-group__body">
                        <div class="mb-3">
                            <label class="form-label" for="branch_process_id">Di Proses oleh</label>
                            <select class="form-select" id="branch_process_id" name="branch_process_id">
                                <option value="">Pilih Branch</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pos_date">Tanggal Transaksi</label>
                            <input type="date" class="form-control" id="pos_date" name="date" value="{{ date('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="form-label" for="invoice_number">Nomor Faktur</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{ $invoice_number }}" readonly>
                        </div>
                    </div>
                </details>
            </div>
        </div>

        <div class="pos-ref-total">
            <div class="pos-ref-total__label">Total Belanja <span>(<span x-text="totalProduk"></span>)</span></div>
            <div class="pos-ref-total__amount">Rp <span x-text="formatRupiah(totalHargaKeseluruhan)"></span></div>
        </div>
        <div class="pos-ref-actions">
            <a href="{{ route('pos.index') }}" class="pos-ref-icon" aria-label="Kembali ke daftar transaksi" title="Kembali"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
            <div x-data="{ loading: false }">
                <button type="button" class="pos-ref-icon" @click="loading = true; saveTransaction(() => loading = false)" :disabled="loading" aria-label="Simpan sebagai draf" title="Simpan draf">
                    <i class="ph-bold ph-bookmark-simple" x-show="!loading" aria-hidden="true"></i>
                    <span class="spinner-border spinner-border-sm" x-show="loading" x-cloak role="status" aria-hidden="true"></span>
                </button>
            </div>
            <div class="pos-ref-pay" x-data="{ loading: false }">
                <button type="button" class="pos-ref-pay__btn" @click="loading = true; goToPayment(() => loading = false)" :disabled="loading || cart.length === 0">
                    <span x-show="!loading">Bayar</span>
                    <span x-show="loading" x-cloak><span class="spinner-border spinner-border-sm align-middle me-2" aria-hidden="true"></span>Memproses...</span>
                </button>
            </div>
        </div>
    </div>
</div><!-- /pos-form-footer -->
</div><!-- /pos-ref-root -->

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
