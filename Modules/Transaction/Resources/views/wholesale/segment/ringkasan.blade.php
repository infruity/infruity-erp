<div class="wholesale-summary mt-3 border-t pt-2.5">
    <div class="wholesale-summary-total">
        <div class="flex items-center gap-1.5">
            <span>Total Belanja</span>
            <span class="rounded-full px-1.5 py-1" style="background:#d1fae5;color:#065f46;font-size:11px;" x-text="totalProduk"></span>
        </div>
        <strong>Rp <span x-text="formatRupiah(totalHargaKeseluruhan)"></span></strong>
    </div>
    <div class="wholesale-summary__actions">
        <div x-data="{ loadingDraft: false }">
            <x-erp.button class="wholesale-btn-draft w-full" @click="loadingDraft = true; saveTransaction(() => loadingDraft = false)" ::disabled="loadingDraft || isSubmitting">
                <template x-if="!loadingDraft">
                    <span>@unless (request()->routeIs('wholesale.edit'))<i class="ph ph-floppy-disk mr-2" aria-hidden="true"></i>@endunless{{ request()->routeIs('wholesale.edit') ? 'Simpan Perubahan' : 'Simpan sebagai Draf' }}</span>
                </template>
                <template x-if="loadingDraft">
                    <span class="erp-spinner" role="status" aria-hidden="true"></span>
                </template>
            </x-erp.button>
        </div>
        <div x-data="{ loadingFinal: false }">
            <x-erp.button class="wholesale-btn-submit w-full" @click="loadingFinal = true; saveToOrderBook(() => loadingFinal = false)" ::disabled="loadingFinal || isSubmitting">
                <template x-if="!loadingFinal">
                    <span>@unless (request()->routeIs('wholesale.edit'))<i class="ph-bold ph-check mr-2" aria-hidden="true"></i>@endunless Selesaikan Transaksi</span>
                </template>
                <template x-if="loadingFinal">
                    <span>
                        <span class="erp-spinner mr-2" aria-hidden="true"></span>
                        Memproses...
                    </span>
                </template>
            </x-erp.button>
        </div>
    </div>
</div>
