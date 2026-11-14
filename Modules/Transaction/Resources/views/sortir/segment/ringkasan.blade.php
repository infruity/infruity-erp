<footer class="sortir-summary">
    <div class="sortir-summary-inner">
        <div class="sortir-summary-total">
            <div class="sortir-summary-label">
                <h2>Total HPP / Modal</h2>
                <span class="sortir-count" x-text="'(' + cart.length + ')'"></span>
            </div>
            <strong x-text="rupiah(total)"></strong>
        </div>
        <p class="sortir-error" x-show="saveError" x-text="saveError" role="alert" x-cloak></p>
        <div class="sortir-summary-actions">
            <a class="sortir-icon-action" href="{{ route('sortir.index') }}" aria-label="Kembali ke daftar sortir" title="Kembali"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
            <button x-show="!isFinal" type="button" class="sortir-icon-action" @click="saveTransaction('draft')" :disabled="saving || !branchId" :aria-label="transactionId ? 'Simpan perubahan' : 'Simpan sebagai draf'" title="Simpan"><i class="ph-bold ph-bookmark-simple" aria-hidden="true"></i></button>
            <button type="button" class="sortir-action sortir-action-primary" @click="confirmComplete()" :disabled="saving || !branchId || cart.length === 0">
                <span x-text="saving && savingStatus === 'paid' ? 'Menyimpan...' : (isFinal ? 'Simpan Perubahan' : 'Submit')"></span>
            </button>
        </div>
    </div>
</footer>
