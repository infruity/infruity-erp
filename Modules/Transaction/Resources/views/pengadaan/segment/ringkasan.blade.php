<div class="mt-4 border-top pt-3">
    <div class="d-flex justify-content-between">
        <div>
            <span>Total</span> <span x-text="totalProduk"></span>
        </div>
        <div class="fw-bold">
            <span>Rp</span>
            <span x-text="formatRupiah(totalHargaKeseluruhan)"></span>
        </div>
    </div>

    <div x-data="{ showActions: false }" class="position-fixed d-flex flex-column-reverse align-items-center"
        style="bottom: 70px; right: 30px; z-index: 1050; gap: 10px;">

        <!-- Tombol utama (floating) -->
        @if (Request::segment(3) != 'show')
            <button class="btn btn-primary rounded-circle shadow-lg" style="width: 50px; height: 50px;"
                @click="showActions = !showActions">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
        @endif

        <!-- Tombol tambahan (floating) -->
        <template x-if="showActions">
            <div class="d-flex flex-column align-items-center gap-2 mb-2" x-data="{ loadingDraft: false, loadingFinal: false }">
                <!-- Tombol Simpan Draft -->
                <button class="btn btn-sm btn-success shadow-lg d-flex align-items-center justify-content-center gap-2"
                    :disabled="loadingDraft || isSubmitting" @click="loadingDraft = true; saveTransaction(() => loadingDraft = false)">
                    <span x-show="!loadingDraft">Draft</span>
                    <span x-show="loadingDraft">
                        <span class="spinner-border spinner-border-sm"></span> Menyimpan...
                    </span>
                </button>

                <!-- Tombol Simpan Final -->
                <button class="btn btn-sm btn-light shadow-lg d-flex align-items-center justify-content-center gap-2"
                    :disabled="loadingFinal || isSubmitting" @click="loadingFinal = true; saveToOrderBook(() => loadingFinal = false)">
                    <span x-show="!loadingFinal">Submit</span>
                    <span x-show="loadingFinal">
                        <span class="spinner-border spinner-border-sm"></span> Menyimpan...
                    </span>
                </button>
            </div>
        </template>

    </div>
</div>
