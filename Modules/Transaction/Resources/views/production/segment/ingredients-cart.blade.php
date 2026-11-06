@if($productionCreateReference ?? false)
<div class="erp-cart production-reference-cart">
    <template x-if="ingredients.length === 0 && !isLoadingIngredients">
        <div class="erp-cart-empty">
            <i class="ph-duotone ph-basket" aria-hidden="true"></i>
            <p>Belum ada bahan baku</p>
            <span>Pilih produk di Card 1 untuk muat resep otomatis, atau klik tombol <strong>+ Tambah Bahan</strong></span>
        </div>
    </template>
    <template x-if="isLoadingIngredients">
        <div class="erp-cart-empty" role="status">
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            <p>Memuat bahan dari resep…</p>
        </div>
    </template>
    <div class="production-reference-cart__items" id="ingredients-container">
        <template x-for="(ingredient,index) in ingredients" :key="ingredient.id">
            <div class="production-reference-cart__row">
                <span class="production-reference-cart__icon"><i class="ph ph-package" aria-hidden="true"></i></span>
                <span class="production-reference-cart__identity">
                    <strong x-text="ingredient.name"></strong>
                    <span><small>Biaya Modal:</small><b x-text="formatCurrency(ingredient.total)"></b></span>
                </span>
                <div class="production-reference-cart__controls">
                    <span class="production-inline-quantity">
                        <input type="text" inputmode="decimal" maxlength="7" :value="inlineQuantityDrafts[ingredient.id] ?? ingredient.quantity"
                            @input="updateIngredientInline(index, $event.target.value)"
                            @blur="normalizeIngredientInline(index, $event.target)" @keydown.enter.prevent="$el.blur()"
                            aria-label="Jumlah bahan">
                        <span x-text="ingredient.unit"></span>
                    </span>
                    <button type="button" class="production-reference-cart__remove" @click="removeIngredient(index)" title="Hapus bahan" aria-label="Hapus bahan">
                        <i class="ph-bold ph-trash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
@else
<div class="erp-cart"><template x-if="ingredients.length === 0 && !isLoadingIngredients"><div class="erp-cart-empty"><i class="ph ph-package" aria-hidden="true"></i><p>Belum ada bahan baku yang dipilih.</p><span>Pilih produk untuk memuat resep atau tambah bahan baku.</span></div></template><template x-if="isLoadingIngredients"><div class="erp-cart-empty" role="status"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span><p>Memuat bahan dari resep…</p></div></template><div id="ingredients-container"><template x-for="(ingredient,index) in ingredients" :key="ingredient.id"><button type="button" class="erp-cart-row ingredient-item" @click="openEditIngredientModal(ingredient,index)"><span class="erp-record__avatar"><i class="ph ph-package" aria-hidden="true"></i></span><span class="erp-cart-identity"><strong x-text="ingredient.name"></strong><span><span x-text="ingredient.quantity"></span> <span x-text="ingredient.unit"></span> × <span x-text="formatCurrency(ingredient.hpp)"></span></span></span><span class="erp-cart-total"><span x-text="formatCurrency(ingredient.total)"></span><i class="ph ph-pencil-simple" aria-hidden="true"></i></span></button></template></div></div>
@endif
