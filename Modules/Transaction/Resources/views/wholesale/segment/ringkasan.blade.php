<div class="wholesale-summary mt-4 border-top pt-3">
    <div class="wholesale-summary-total">
        <div class="d-flex align-items-center gap-2">
            <span>Total Belanja</span>
            <span class="rounded-pill px-2 py-1" style="background:#d1fae5;color:#065f46;font-size:11px;" x-text="totalProduk"></span>
        </div>
        <strong>Rp <span x-text="formatRupiah(totalHargaKeseluruhan)"></span></strong>
    </div>
    <div class="row mt-5 gap-2">
        <div class="col" x-data="{ loadingDraft: false }">
            <button class="btn btn-primary w-100" @click="loadingDraft = true; saveTransaction(() => loadingDraft = false)"
                :disabled="loadingDraft || isSubmitting">
                <template x-if="!loadingDraft">
                    <span>@unless (request()->routeIs('wholesale.edit'))<i class="ph ph-floppy-disk me-2"></i>@endunless{{ request()->routeIs('wholesale.edit') ? 'Simpan Perubahan' : 'Simpan sebagai Draf' }}</span>
                </template>
                <template x-if="loadingDraft">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                </template>
            </button>
        </div>
        <div class="col" x-data="{ loadingFinal: false }">
            <button class="btn btn-success w-100" @click="loadingFinal = true; saveToOrderBook(() => loadingFinal = false)"
                :disabled="loadingFinal || isSubmitting">
                <template x-if="!loadingFinal">
                    <span>@unless (request()->routeIs('wholesale.edit'))<i class="ph-bold ph-check me-2"></i>@endunless Selesaikan Transaksi</span>
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
