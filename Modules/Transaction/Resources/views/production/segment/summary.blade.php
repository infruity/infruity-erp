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
            <div class="flex-fill" x-data="{ loading: false }">
                <button type="button" class="btn btn-light w-100 border" @click="loading = true; saveProduction('temp', () => loading = false)"
                    :disabled="loading">
                    <template x-if="!loading"><span>Simpan Draft</span></template>
                    <template x-if="loading"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></template>
                </button>
            </div>
            <div class="flex-fill" x-data="{ loading: false }">
                <button type="button" class="btn btn-primary w-100" @click="loading = true; saveProduction('posting', () => loading = false)"
                    :disabled="loading">
                    <template x-if="!loading"><span>Selesai</span></template>
                    <template x-if="loading">
                        <span><span class="spinner-border spinner-border-sm align-middle me-2"></span>Menyelesaikan...</span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>
