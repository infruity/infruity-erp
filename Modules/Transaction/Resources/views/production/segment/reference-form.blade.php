{{--
    Production create form in the layout of the HTML reference (infruity-frontend 003. ERP/005. Produksi): one card for the
    kitchen, one card for product + composition + cost/price/quantity, and a bottom bar with back / draft / save.
    Every id and Alpine binding of the old form is kept (productionApp in js-create.blade.php), only the presentation changed.
--}}
<div class="production-form production-ref">
    @if(isset($data) && $data?->status === 'temp')
        <p class="production-resume-note" role="status" x-init="setTimeout(() => $el.remove(), 5000)">Melanjutkan draf sementara #{{ $data->production_number }}. Menyimpan akan memperbarui draf ini.</p>
    @endif

    <!-- Kartu 1: cabang / dapur produksi -->
    <section class="production-ref-card production-ref-card--branch">
        <label class="production-ref-label" for="branch_id">Cabang / Dapur Produksi <span class="production-ref-required">*</span></label>
        <select class="form-select" id="branch_id" name="branch_id">
            <option value="">Pilih Branch</option>
        </select>
    </section>

    <!-- Kartu 2: produk, komposisi, modal dan harga -->
    <section class="production-ref-card production-ref-card--main">
        <div class="production-ref-field production-product-picker-field">
            <label class="production-ref-label" for="product_id" id="production-product-label">Pilih Produk <span class="production-ref-required">*</span></label>
            <select name="product_id" id="product_id" class="form-select" data-placeholder="Pilih produk olahan...">
                @if (isset($edit_product_id) && isset($edit_product_name))
                    <option value="{{ $edit_product_id }}" selected>{{ $edit_product_name }}</option>
                @elseif (isset($receipt) && $receipt != null)
                    <option value="{{ $receipt->id }}" selected>{{ $receipt->products->name }}</option>
                @else
                    <option value="">Pilih produk olahan...</option>
                @endif
            </select>
            <button type="button" class="production-ref-product"
                :aria-label="referenceSelectedProductName ? 'Ganti produk: ' + referenceSelectedProductName : 'Pilih produk olahan'">
                <span class="production-ref-product__text">
                    <strong :class="referenceSelectedProductName ? '' : 'is-placeholder'" x-text="referenceSelectedProductName || 'Pilih produk olahan...'"></strong>
                    <small x-show="referenceSelectedProductName" x-text="formatCurrency(sellPrice) + ' / porsi'"></small>
                </span>
                <i class="ph-bold ph-caret-down" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Komposisi produk -->
        <div class="production-ref-composition">
            <div class="production-ref-composition__head">
                <span>Komposisi Produk</span>
                <button type="button" @click="openAddIngredientModal()" aria-label="Tambah bahan produksi">+ Tambah</button>
            </div>
            <div class="production-ref-composition__body" id="ingredients-container">
                <template x-if="isLoadingIngredients">
                    <div class="production-ref-empty" role="status">
                        <x-erp.spinner size="sm" />
                        <span>Memuat bahan dari resep…</span>
                    </div>
                </template>
                <template x-if="!isLoadingIngredients && ingredients.length === 0">
                    <div class="production-ref-empty">
                        <strong>Pilih Produk di Atas</strong>
                        <span>Daftar bahan dan takaran komposisi racikan akan ditampilkan secara otomatis</span>
                    </div>
                </template>
                <template x-for="(ingredient,index) in ingredients" :key="ingredient.id">
                    <div class="production-ref-row" x-data="{ startX: 0, startY: 0, revealed: false,
                            down(e) { if (e.target.closest('input,button')) return; const t = e.touches ? e.touches[0] : e; this.startX = t.clientX; this.startY = t.clientY; },
                            move(e) { if (!this.startX) return; const t = e.touches ? e.touches[0] : e; const dx = this.startX - t.clientX; const dy = Math.abs(this.startY - t.clientY);
                                if (dx > 35 && dx > dy) this.revealed = true; else if (dx < -25 && Math.abs(dx) > dy) this.revealed = false; },
                            up() { this.startX = 0; this.startY = 0; } }"
                        @click.outside="revealed = false">
                        <button type="button" class="production-ref-row__delete" @click.stop="removeIngredient(index)" title="Hapus bahan" aria-label="Hapus bahan">
                            <i class="ph-bold ph-trash" aria-hidden="true"></i><span>Hapus</span>
                        </button>
                        <div class="production-ref-row__content" :class="revealed ? 'is-revealed' : ''"
                            @touchstart.passive="down($event)" @touchmove="move($event)" @touchend="up()"
                            @mousedown="down($event)" @mousemove="startX && move($event)" @mouseup="up()" @mouseleave="up()">
                            <div class="production-ref-row__line">
                                <strong x-text="ingredient.name"></strong>
                                <span class="production-ref-row__price" x-text="formatCurrency(ingredient.hpp) + ' / ' + ingredient.unit"></span>
                            </div>
                            <div class="production-ref-row__line">
                                <span class="production-ref-row__total"><small>Rp</small><b x-text="formatNumber(ingredient.total)"></b></span>
                                <span class="production-ref-row__qty">
                                    <input type="text" inputmode="decimal" maxlength="7" :value="inlineQuantityDrafts[ingredient.id] ?? ingredient.quantity"
                                        @input="updateIngredientInline(index, $event.target.value)"
                                        @blur="normalizeIngredientInline(index, $event.target)" @keydown.enter.prevent="$el.blur()"
                                        aria-label="Jumlah bahan">
                                    <span x-text="ingredient.unit"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Modal, harga jual dan kuantitas -->
        <div class="production-ref-totals">
            <div class="production-ref-field">
                <span class="production-ref-label">Modal / Satuan</span>
                <div class="production-ref-readout"><small>Rp</small><b x-text="formatNumber(hppPerUnit)"></b></div>
            </div>
            <div class="production-ref-field">
                <span class="production-ref-label">Total Modal</span>
                <div class="production-ref-readout"><small>Rp</small><b x-text="formatNumber(totalHpp)"></b></div>
            </div>
            <div class="production-ref-field">
                <span class="production-ref-label">Harga Jual <em>/porsi</em></span>
                <div class="production-ref-readout"><small>Rp</small><b x-text="formatNumber(sellPrice)"></b></div>
            </div>
            <div class="production-ref-field">
                <label class="production-ref-label" for="quantity">Kuantitas <em>(PORSI)</em> <span class="production-ref-required">*</span></label>
                <input type="number" inputmode="decimal" name="quantity" id="quantity" class="form-control"
                    placeholder="0" step="0.01" min="1" max="9999" x-model="productionQuantity"
                    value="{{ isset($data) ? $data->quantity : '' }}" />
            </div>
        </div>
    </section>

    <div class="production-payload-fields" hidden aria-hidden="true">
        <input type="date" id="production_date" name="production_date" value="{{ old('production_date') ?? date('Y-m-d') }}">
        <input type="text" inputmode="numeric" name="sell_price" id="sell_price" class="format-number" x-model="sellPrice"
            value="{{ isset($edit_sell_price) ? number_format($edit_sell_price, 0, ',', '.') : '' }}" />
        <input type="hidden" name="production_number" id="production_number" value="{{ $production_number }}">
        <input type="hidden" name="id" id="production_id" value="{{ isset($id) ? $id : '' }}">
        <input type="text" inputmode="numeric" name="service_cost" id="service_cost" class="format-number" x-model="serviceCost"
            value="{{ isset($edit_service_cost) ? number_format($edit_service_cost, 0, ',', '.') : '' }}" />
        <input type="text" id="production_notes" name="notes" x-model="notes">
    </div>

    <!-- Bilah bawah: kembali, simpan draf, simpan -->
    <div class="production-form-footer production-ref-footer">
        <div class="production-form-footer__inner production-ref-footer__inner" x-data="{ savingDraft: false, saving: false }">
            <a href="{{ route('production.index') }}" class="production-ref-icon-btn" title="Kembali ke Daftar Produksi" aria-label="Kembali ke Daftar Produksi">
                <i class="ph-bold ph-arrow-left" aria-hidden="true"></i>
            </a>
            <button type="button" class="production-ref-icon-btn" title="{{ request()->routeIs('production.edit') ? 'Simpan Perubahan' : 'Simpan sebagai Draf' }}" aria-label="{{ request()->routeIs('production.edit') ? 'Simpan Perubahan' : 'Simpan sebagai Draf' }}" :disabled="savingDraft || saving"
                @click="savingDraft = true; saveProduction('temp', () => savingDraft = false)">
                <x-erp.spinner size="sm" x-show="savingDraft" />
                <i class="ph-bold ph-bookmark-simple" aria-hidden="true" x-show="!savingDraft"></i>
            </button>
            <button type="button" class="production-ref-save" :disabled="saving || savingDraft"
                @click="saving = true; saveProduction('posting', () => saving = false)">
                <x-erp.spinner size="sm" x-show="saving" class="!text-white" />
                <span x-text="saving ? 'Menyimpan…' : '{{ request()->routeIs('production.edit') ? 'Simpan' : 'Tambah' }}'"></span>
            </button>
        </div>
    </div>
</div>
