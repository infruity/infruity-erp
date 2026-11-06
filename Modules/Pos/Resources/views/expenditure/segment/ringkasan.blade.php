<x-erp.collapse class="mb-3 rounded-xl border border-gray-100 p-3">
    <x-slot:trigger><span class="text-sm font-semibold text-gray-800">Rincian Total</span></x-slot:trigger>
    <div class="mt-3 space-y-3">
        <div class="flex justify-between">
            <span class="font-semibold">Subtotal</span>
            <span class="text-right">Rp <span x-text="formatRupiah(subtotal)"></span></span>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <select class="erp-select" id="payment_id" name="payment_id" aria-label="Tipe pembayaran">
                <option value="">Pilih Tipe</option>
            </select>
            <input type="text" class="erp-input text-right" :value="formatRupiah(payment)" @input="updatePayment" inputmode="numeric" aria-label="Nominal pembayaran">
        </div>
    </div>
</x-erp.collapse>

<div class="mt-4 border-t border-gray-100 pt-3">
    <div class="flex justify-between">
        <div>
            <span>Total</span> <span x-text="totalProduk"></span>
        </div>
        <div class="font-bold">
            <span>Rp</span>
            <span x-text="formatRupiah(totalHargaKeseluruhan)"></span>
        </div>
    </div>
    <div class="erp-payment-actions mt-5" x-data="{ loadingDraft:false, loadingFinal:false }">
        <x-erp.button variant="primary" ::disabled="loadingDraft || loadingFinal" @click="loadingFinal = true; saveToOrderBook(() => loadingFinal = false)"><span x-show="!loadingFinal">Submit</span><span x-show="loadingFinal">Menyimpan…</span></x-erp.button>
        <x-erp.button ::disabled="loadingDraft || loadingFinal" @click="loadingDraft = true; saveTransaction(() => loadingDraft = false)"><span x-show="!loadingDraft">Simpan Draft</span><span x-show="loadingDraft">Menyimpan…</span></x-erp.button>
    </div>
</div>
