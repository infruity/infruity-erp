<div class="pos-ref-root" x-data="{ sweepOpen: false, touchY: 0 }" @keydown.escape.window="sweepOpen = false">
<div class="pos-ref-backdrop" x-show="sweepOpen" x-cloak x-transition:enter="pos-fade-in" x-transition:enter-start="pos-fade-from" x-transition:enter-end="pos-fade-to" x-transition:leave="pos-fade-out" x-transition:leave-start="pos-fade-to" x-transition:leave-end="pos-fade-from" @click="sweepOpen = false"></div>
<div class="pos-form-footer pos-ref-footer" :class="{ 'is-open': sweepOpen }" @touchstart.passive="touchY = $event.touches[0].clientY" @touchend.passive="const dy = $event.changedTouches[0].clientY - touchY; if (dy > 40 && sweepOpen) sweepOpen = false; else if (dy < -40 && !sweepOpen) sweepOpen = true;">
    <div class="pos-form-footer__inner">
        <button type="button" class="pos-ref-handle" @click="sweepOpen = !sweepOpen" :aria-expanded="sweepOpen" aria-controls="collapseTotal" aria-label="Rincian total"><span></span></button>

        {{-- Lembar ringkasan: diskon nota, ongkos kirim, rincian, lalu catatan, pengiriman dan pengaturan transaksi --}}
        <div id="collapseTotal" class="pos-collapse" x-show="sweepOpen" x-cloak x-transition:enter="pos-collapse-anim" x-transition:enter-start="pos-collapse-from" x-transition:enter-end="pos-collapse-to" x-transition:leave="pos-collapse-anim" x-transition:leave-start="pos-collapse-to" x-transition:leave-end="pos-collapse-from"><div class="pos-ref-sheet">
            <div class="pos-ref-summary">
                <div class="pos-ref-summary__inputs">
                    <div class="pos-ref-money">
                        <span class="pos-ref-money__title" :class="{ 'is-disabled': cart.length === 0 }">Diskon Nota</span>
                        <span class="pos-ref-money__field" :class="{ 'is-disabled': cart.length === 0 }"><span x-text="diskonGlobalType === 'percent' ? '%' : 'Rp'">Rp</span><input type="text" class="form-control" inputmode="numeric" placeholder="0" :disabled="cart.length === 0" :value="diskonGlobal ? formatRupiah(diskonGlobal) : ''" @input="updateDiskonGlobal" @focus="$event.target.select()" aria-label="Diskon nota"></span>
                    </div>
                    <label class="pos-ref-money">
                        <span class="pos-ref-money__title" :class="{ 'is-disabled': cart.length === 0 }">Ongkos Kirim</span>
                        <span class="pos-ref-money__field" :class="{ 'is-disabled': cart.length === 0 }"><span>Rp</span><input type="text" class="form-control" inputmode="numeric" placeholder="0" :disabled="cart.length === 0" :value="ongkirGlobal ? formatRupiah(ongkirGlobal) : ''" @input="updateOngkirGlobal" @focus="$event.target.select()"></span>
                    </label>
                </div>
                <dl class="pos-ref-lines">
                    <div><dt>Subtotal Item (<span x-text="totalProduk"></span>)</dt><dd>Rp <span x-text="formatRupiah(subtotal)"></span></dd></div>
                    <div><dt>Diskon Transaksi</dt><dd x-text="diskonGlobalRupiah > 0 ? '-Rp ' + formatRupiah(diskonGlobalRupiah) + (diskonGlobalType === 'percent' ? ' (' + String(diskonGlobal).replace('.', ',') + '%)' : '') : 'Rp 0'"></dd></div>
                    <div><dt>Ongkos Kirim</dt><dd x-text="ongkirBersih > 0 ? '+Rp ' + formatRupiah(ongkirBersih) : 'Rp 0'"></dd></div>
                </dl>
            </div>


        </div></div>

        <div class="pos-ref-total is-toggle" @click="sweepOpen = !sweepOpen" role="button" tabindex="0" @keydown.enter.self="sweepOpen = !sweepOpen" @keydown.space.prevent.self="sweepOpen = !sweepOpen" :aria-expanded="sweepOpen" aria-controls="collapseTotal" aria-label="Rincian total: Diskon dan ongkos kirim">
            <div class="pos-ref-total__label">Total Belanja <span>(<span x-text="totalProduk"></span>)</span></div>
            <div class="pos-ref-total__amount">Rp <span x-text="formatRupiah(totalHargaKeseluruhan)"></span></div>
        </div>
        <div class="pos-ref-actions">
            <a href="{{ $backUrl ?? route('pos.index') }}" class="pos-ref-icon" aria-label="{{ $backLabel ?? 'Kembali ke daftar transaksi' }}" title="Kembali"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
            <div x-data="{ loading: false }">
                <button type="button" class="pos-ref-icon" @click="loading = true; saveTransaction(() => loading = false)" :disabled="loading" aria-label="Simpan sebagai draf" title="Simpan draf">
                    <i class="ph-bold ph-bookmark-simple" x-show="!loading" aria-hidden="true"></i>
                    <span class="spinner-border spinner-border-sm" x-show="loading" x-cloak role="status" aria-hidden="true"></span>
                </button>
            </div>
            <div class="pos-ref-pay" x-data="{ loading: false }">
                <button type="button" class="pos-ref-pay__btn" @click="loading = true; goToPayment(() => loading = false)" :disabled="loading || cart.length === 0 || hasIncompleteParcel()" :title="hasIncompleteParcel() ? 'Lengkapi komposisi parcel terlebih dahulu' : null">
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
