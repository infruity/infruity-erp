<div class="wholesale-summary mt-4 border-top pt-3">
    <div class="wholesale-summary-total">
        <div class="d-flex align-items-center gap-2">
            <span>Total Belanja</span>
            <span class="wholesale-summary-count" x-text="'(' + totalProduk() + ')'"></span>
        </div>
        <strong>Rp <span x-text="formatRupiah(totalHargaKeseluruhan)"></span></strong>
    </div>
    <div class="row mt-5 gap-2">
        <div class="col wholesale-summary-icons" x-data="{ loadingDraft: false }">
            <a href="{{ route('wholesale.index') }}" class="btn btn-primary wholesale-summary-icon" aria-label="Kembali ke daftar pengadaan" title="Kembali"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
            <button type="button" class="btn btn-primary wholesale-summary-icon" @click="loadingDraft = true; saveTransaction(() => loadingDraft = false)"
                :disabled="loadingDraft || isSubmitting" aria-label="{{ request()->routeIs('wholesale.edit') ? 'Simpan perubahan' : 'Simpan sebagai draf' }}" title="Simpan">
                <template x-if="!loadingDraft"><i class="ph-bold ph-bookmark-simple" aria-hidden="true"></i></template>
                <template x-if="loadingDraft"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></template>
            </button>
        </div>
        <div class="col" x-data="{ loadingFinal: false }">
            <button class="btn btn-success w-100" @click="loadingFinal = true; saveToOrderBook(() => loadingFinal = false)"
                :disabled="loadingFinal || isSubmitting || cart.length === 0">
                <template x-if="!loadingFinal">
                    <span>Submit</span>
                </template>
                <template x-if="loadingFinal">
                    <span>
                        <span class="spinner-border spinner-border-sm align-middle me-2"></span>
                        Memproses...
                    </span>
                </template>
            </button>
        </div>
    </div>
</div>
