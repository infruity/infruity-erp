<footer class="sortir-summary">
    <div class="sortir-summary-inner">
        <div class="sortir-summary-total"><div><h2>Total HPP / Modal</h2><span class="sortir-count" x-text="cart.length"></span></div><strong x-text="rupiah(total)"></strong></div>
        <p class="sortir-error" x-show="saveError" x-text="saveError" role="alert" x-cloak></p>
        <div class="sortir-summary-actions">
            <a x-show="isFinal" class="sortir-action" href="{{ route('sortir.index') }}" x-cloak>Kembali</a>
            <button x-show="!isFinal" type="button" class="sortir-action" @click="saveTransaction('draft')" :disabled="saving || !branchId"><span x-text="saving && savingStatus === 'draft' ? 'Menyimpan...' : (transactionId ? 'Simpan Perubahan' : 'Simpan sebagai Draf')"></span></button>
            <button type="button" class="sortir-action sortir-action-primary" @click="confirmComplete()" :disabled="saving || !branchId || cart.length === 0"><span x-text="saving && savingStatus === 'paid' ? 'Menyimpan...' : (isFinal ? 'Simpan Perubahan' : 'Selesaikan Transaksi')"></span></button>
        </div>
    </div>
</footer>
