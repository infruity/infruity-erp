<!-- Footer produksi: total biaya bahan, estimasi HPP, dan aksi simpan -->
<div class="production-form-footer">
    <div class="production-form-footer__inner">
        <div class="production-total">
            <div class="production-total__label">
                <span>Total Biaya</span>
                <span class="production-total__count" x-text="ingredients.length + ' bahan'"></span>
            </div>
            <div class="production-total__value">
                <strong x-text="formatCurrency(totalHpp)"></strong>
                <span x-text="'Estimasi HPP: ' + formatCurrency(hppPerUnit) + ' / unit'"></span>
            </div>
        </div>
        <div class="production-actions">
            <div class="flex-1" x-data="{ loading: false }">
                <button type="button" class="erp-action w-full border" @click="loading = true; saveProduction('temp', () => loading = false)"
                    :disabled="loading">
                    <template x-if="!loading"><span>Simpan Draft</span></template>
                    <template x-if="loading"><span class="erp-spinner" role="status" aria-hidden="true"></span></template>
                </button>
            </div>
            <div class="flex-1" x-data="{ loading: false }">
                <button type="button" class="erp-action erp-action--primary w-full" @click="loading = true; saveProduction('posting', () => loading = false)"
                    :disabled="loading">
                    <template x-if="!loading"><span>Selesai</span></template>
                    <template x-if="loading">
                        <span><span class="erp-spinner align-middle me-1.5"></span>Menyelesaikan...</span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>
