{{-- Dialog tambah / ubah item. Dibuka lewat erp.dialog.open() dari openAddModal()/openEditModal(). --}}
<x-erp.dialog id="addModal" title="Tambah Produk" size="md" variant="sheet">
    <div class="space-y-3">
        <x-erp.field label="Nama Produk" for="add-product-name"><input id="add-product-name" type="text" class="erp-input" x-model="addProduct.name"></x-erp.field>
        <div class="grid grid-cols-4 gap-3">
            <x-erp.field label="Satuan" for="add-product-unit"><input id="add-product-unit" type="text" class="erp-input" x-model="addProduct.unit"></x-erp.field>
            <x-erp.field label="Harga" for="add-product-price" class="col-span-3"><input id="add-product-price" type="text" inputmode="numeric" class="erp-input" x-model="formattedAddPrice"></x-erp.field>
        </div>
        <x-erp.field label="Quantity" for="add-product-qty"><input id="add-product-qty" type="number" class="erp-input" step="0.01" min="0" x-model="addProduct.qty" @input="updateAddTotalFromQty"></x-erp.field>
        <x-erp.field label="Diskon (Rp jika > 100, % jika ≤ 100)" for="add-product-discount"><input id="add-product-discount" type="text" class="erp-input" inputmode="numeric" :value="formatRupiah(addProduct.discountNominal || 0)" @input="updateDiscountValue"></x-erp.field>
        <x-erp.field label="Jumlah Harga" for="add-product-total"><input id="add-product-total" type="text" class="erp-input" inputmode="numeric" x-model="addProduct.formattedAddTotalInput" @input="updateQtyFromAddTotal"></x-erp.field>
    </div>
    <x-slot:footer>
        <div class="flex w-full justify-end gap-2">
            <x-erp.button @click="closeAddModal()">Tutup</x-erp.button>
            <x-erp.button variant="primary" @click="saveAddToCart()">Simpan</x-erp.button>
        </div>
    </x-slot:footer>
</x-erp.dialog>

<x-erp.dialog id="editModal" size="md" variant="sheet">
    <x-slot:title><span x-text="editTitle"></span></x-slot:title>
    <div x-show="editItem" class="space-y-3">
        <x-erp.field label="Nama Product" for="edit-product-name"><input id="edit-product-name" type="text" class="erp-input" x-model="editProductName"></x-erp.field>
        <div class="grid grid-cols-3 gap-3">
            <x-erp.field label="Satuan" for="edit-product-unit"><input id="edit-product-unit" type="text" class="erp-input" x-model="editProductUnit"></x-erp.field>
            <x-erp.field label="Quantity" for="edit-qty" class="col-span-2"><input id="edit-qty" type="number" class="erp-input" step="0.01" min="0" x-model="editQty" @input="updateTotalFromEditQty"></x-erp.field>
        </div>
        <x-erp.field label="Harga Jual (Rp)" for="edit-total"><input id="edit-total" type="text" inputmode="numeric" class="erp-input" x-model="editTotalFormatted" @input="updateEditTotalFormatted"></x-erp.field>
        <x-erp.field label="Diskon (Rp jika > 100, % jika ≤ 100)" for="edit-discount"><input id="edit-discount" type="number" class="erp-input" x-model="editDiscount" min="0" @input="updateEditDiscount"></x-erp.field>
    </div>
    <x-slot:footer>
        <div class="flex w-full gap-2">
            <x-erp.button variant="danger" class="mr-auto" @click="deleteFromCart()">Hapus Produk</x-erp.button>
            <x-erp.button @click="closeEditModal()">Batal</x-erp.button>
            <x-erp.button variant="primary" @click="saveEditToCart()">Simpan</x-erp.button>
        </div>
    </x-slot:footer>
</x-erp.dialog>
