<style>
    #add_product_form { display: block; padding: 0; }
    #add_product_form .product-create-content { display: flex; flex-direction: column; gap: 1.25rem; }
    #add_product_form .product-field-label { display: block; margin-bottom: .375rem; color: #4b5563; font-size: .75rem; font-weight: 600; }
    #add_product_form .product-input,
    #add_product_form .product-select,
    #add_product_form .form-control,
    #add_product_form .form-select { width: 100%; min-height: 44px; border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; padding: .625rem .875rem; color: #1f2937; font-size: .875rem; outline: none; }
    #add_product_form .product-input:focus,
    #add_product_form .product-select:focus,
    #add_product_form .form-control:focus,
    #add_product_form .form-select:focus { border-color: #10b981; box-shadow: 0 0 0 2px rgb(16 185 129 / .12); }
    #add_product_form .product-tabs { display: flex; gap: 1.25rem; border-bottom: 1px solid #e5e7eb; }
    #add_product_form .product-tabs .nav-link { display: block; padding: 0 0 .75rem; border-bottom: 2px solid transparent; color: #9ca3af; font-size: .8125rem; font-weight: 600; }
    #add_product_form .product-tabs .nav-link.active { color: #0f5c45; border-color: #0f5c45; }
    #add_product_form .tab-pane:not(.active) { display: none; }
    #add_product_form .product-price-row,
    #add_product_form .product-option-row { display: flex; align-items: center; gap: .625rem; }
    #add_product_form .product-price-prefix { position: absolute; left: .875rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: .8125rem; pointer-events: none; }
    #add_product_form .product-price-input { padding-left: 2.75rem; text-align: right; font-variant-numeric: tabular-nums; }
    #add_product_form .product-remove-row { display: inline-flex; width: 38px; height: 38px; flex: 0 0 38px; align-items: center; justify-content: center; border-radius: .75rem; background: #fef2f2; color: #ef4444; }
    #add_product_form .product-add-row { display: inline-flex; align-items: center; gap: .375rem; color: #047857; font-size: .75rem; font-weight: 600; }
    #add_product_form #variant_table, #add_product_form #branch_table { width: 100%; font-size: .8125rem; }
    #add_product_form #variant_table th, #add_product_form #branch_table th { padding: .5rem; border-bottom: 1px solid #f3f4f6; color: #9ca3af; font-size: .6875rem; font-weight: 600; text-align: left; }
    #add_product_form #variant_table td, #add_product_form #branch_table td { padding: .5rem; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
    #add_product_form .select2-container { width: 100% !important; }
    #add_product_form .select2-container--default .select2-selection--single { height: 44px; border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff; }
    #add_product_form .select2-container--default .select2-selection--single .select2-selection__rendered { padding: 7px 14px; color: #1f2937; font-size: .875rem; line-height: 28px; }
    #add_product_form .select2-container--default .select2-selection--single .select2-selection__arrow { top: 8px; right: 8px; }
    #add_product_form .select2-container--default.select2-container--focus .select2-selection--single { border-color: #10b981; }
</style>

<form id="add_product_form"
    action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}"
    method="POST" enctype="multipart/form-data" data-kt-redirect="">
    @if (isset($data)) @method('PUT') @endif
    @csrf

    <div class="product-create-content">
        <div class="flex flex-col items-center gap-2">
            <label for="product-avatar" class="group flex h-20 w-20 cursor-pointer items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-gray-200 bg-white text-gray-300 transition hover:border-emerald-300 hover:bg-emerald-50/30">
                <img id="product-image-preview" src="{{ isset($data) && $data->image ? asset('storage/' . $data->image) : '' }}" alt="Pratinjau foto produk" class="{{ isset($data) && $data->image ? '' : 'hidden' }} h-full w-full object-cover">
                <i id="product-image-placeholder" class="ph ph-image text-2xl {{ isset($data) && $data->image ? 'hidden' : '' }}"></i>
            </label>
            <input id="product-avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/jpg" class="sr-only">
            <span class="text-[11px] text-gray-400">Foto Produk (Opsional)</span>
            @error('avatar') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="product-name" class="product-field-label">Nama Produk <span class="text-red-400">*</span></label>
            <input id="product-name" type="text" name="product_name" required value="{{ old('product_name', $data->name ?? '') }}"
                placeholder="Masukkan nama produk" class="product-input @error('product_name') !border-red-400 @enderror">
            @error('product_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="product-type" class="product-field-label">Tipe Produk <span class="text-red-400">*</span></label>
            <div data-picker-root="product-type" class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="relative border-b border-gray-100 bg-gray-50/50">
                    <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                    <input type="search" data-picker-search="product-type-options" placeholder="Cari tipe produk..." class="w-full bg-transparent py-3 pl-9 pr-4 text-sm outline-none" aria-label="Cari tipe produk">
                </div>
                <div id="product-type-options" class="max-h-40 overflow-y-auto overscroll-contain">
                    @foreach ($tipe as $key => $value)
                        <button type="button" data-picker-option data-picker-select="product-type" data-picker-value="{{ $key }}" data-picker-label="{{ $value }}" class="flex w-full items-center gap-3 border-b border-gray-100 px-3.5 py-3 text-left last:border-0 hover:bg-gray-50">
                            <span class="product-picker-radio flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 border-gray-300"><span class="h-2 w-2 scale-0 rounded-full bg-emerald-500"></span></span>
                            <span class="text-[13px] font-medium text-gray-700">{{ $value }}</span>
                        </button>
                    @endforeach
                    <p data-picker-empty class="hidden py-5 text-center text-[13px] text-gray-400">Tipe tidak ditemukan</p>
                </div>
            </div>
            <select id="product-type" name="tipe" class="sr-only" tabindex="-1" aria-hidden="true" aria-required="true">
                @foreach ($tipe as $key => $value)
                    <option value="{{ $key }}" {{ old('tipe', $data->tipe ?? 'product') == $key ? 'selected' : '' }}>{{ $value }}</option>
                @endforeach
            </select>
            @error('tipe') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="product-unit" class="product-field-label">Satuan <span class="text-red-400">*</span></label>
            <div data-picker-root="product-unit" class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="relative border-b border-gray-100 bg-gray-50/50">
                    <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                    <input type="search" data-picker-search="product-unit-options" placeholder="Cari satuan..." class="w-full bg-transparent py-3 pl-9 pr-4 text-sm outline-none" aria-label="Cari satuan">
                </div>
                <div id="product-unit-options" class="max-h-48 overflow-y-auto overscroll-contain">
                    @foreach ($product_units as $item)
                        <button type="button" data-picker-option data-picker-select="product-unit" data-picker-value="{{ $item->id }}" data-picker-label="{{ $item->name }} {{ $item->abbreviation }}" class="flex w-full items-center gap-3 border-b border-gray-100 px-3.5 py-3 text-left last:border-0 hover:bg-gray-50">
                            <span class="product-picker-radio flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 border-gray-300"><span class="h-2 w-2 scale-0 rounded-full bg-emerald-500"></span></span>
                            <span class="flex min-w-0 flex-1 flex-col">
                                <span class="text-[13px] font-medium text-gray-700">{{ $item->name }}</span>
                                <span class="text-[10px] text-gray-500">simbol ({{ $item->abbreviation }})</span>
                            </span>
                        </button>
                    @endforeach
                    <p data-picker-empty class="hidden py-5 text-center text-[13px] text-gray-400">Satuan tidak ditemukan</p>
                </div>
            </div>
            <select id="product-unit" name="product_unit_id" class="sr-only" tabindex="-1" aria-hidden="true" aria-required="true">
                <option value="">Pilih satuan</option>
                @foreach ($product_units as $item)
                    <option value="{{ $item->id }}" {{ old('product_unit_id', $data->product_unit ?? '') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                @endforeach
            </select>
            @error('product_unit_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div class="product-option-row rounded-xl border border-gray-200 bg-white px-4 py-3">
            <div class="min-w-0 flex-1">
                <label for="product-has-variants" class="block text-sm font-semibold text-gray-700">Produk Memiliki Varian</label>
                <p class="mt-0.5 text-[11px] text-gray-400">Tambahkan pilihan produk seperti ukuran atau grade</p>
            </div>
            <input id="product-has-variants" type="checkbox" class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" aria-controls="product-variants">
        </div>

        <section id="product-base-price" class="flex flex-col gap-2">
            <label for="product-price" class="product-field-label !mb-0">Harga Jual <span class="text-red-400">*</span></label>
            <div class="relative">
                <span class="product-price-prefix">Rp</span>
                <input id="product-price" type="text" inputmode="numeric" name="price" required value="{{ old('price', $data->price ?? '') }}"
                    placeholder="0" class="product-input product-price-input format-number @error('price') !border-red-400 @enderror">
            </div>
            @error('price') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <button id="product-branch-toggle" type="button" class="product-add-row self-start"><i class="ph ph-plus-circle"></i> Atur harga per cabang</button>
        </section>

        <section id="product-branch-prices" class="hidden rounded-xl border border-gray-200 bg-white p-3">
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-xs font-semibold text-gray-700">Harga per Cabang</h3>
                <button class="product-add-row" type="button" onclick="addBranch()"><i class="ph ph-plus-circle"></i> Tambah</button>
            </div>
            <div class="overflow-x-auto">
                <table id="branch_table"><thead><tr><th>Cabang</th><th>Harga</th><th></th></tr></thead><tbody id="kt_ecommerce_edit_order_selected_products_branch_body"></tbody></table>
            </div>
        </section>

        <section id="product-variants" class="hidden rounded-xl border border-gray-200 bg-white p-3">
            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-xs font-semibold text-gray-700">Daftar Varian <span class="text-red-400">*</span></h3>
                <button class="product-add-row" type="button" onclick="addVariant()"><i class="ph ph-plus-circle"></i> Tambah Varian</button>
            </div>
            <div class="overflow-x-auto">
                <table id="variant_table"><thead><tr><th>Nama Varian</th><th>Harga</th><th></th></tr></thead><tbody id="kt_ecommerce_edit_order_selected_products_body"></tbody></table>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Pilih produk yang sudah ada atau ketik nama varian baru.</p>
        </section>

        <div>
            <label for="product-status" class="product-field-label">Status Produk <span class="text-red-400">*</span></label>
            <select id="product-status" name="status" required class="product-select @error('status') !border-red-400 @enderror">
                <option value="no-receipt" {{ old('status', $data->status ?? 'no-receipt') == 'no-receipt' ? 'selected' : '' }}>Tanpa Resep</option>
                <option value="receipt" {{ old('status', $data->status ?? '') == 'receipt' ? 'selected' : '' }}>Dengan Resep</option>
            </select>
            @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="category_id" class="product-field-label">Kategori Produk</label>
            <select id="category_id" name="category_id" class="product-select">
                @if (old('category_id'))
                    <option value="{{ old('category_id') }}" selected>{{ \Modules\Master\Entities\ProductCategory::find(old('category_id'))->name ?? old('category_id') }}</option>
                @elseif (isset($data->category_id))
                    <option value="{{ $data->category_id }}" selected>{{ $category->name ?? '' }}</option>
                @endif
            </select>
        </div>

        <div class="product-tabs" role="tablist" aria-label="Detail produk">
            <button type="button" class="nav-link active" data-bs-toggle="tab" href="#product-tab-general" role="tab" aria-selected="true">Umum</button>
            <button type="button" class="nav-link" data-bs-toggle="tab" href="#product-tab-advanced" role="tab" aria-selected="false">Lanjutan</button>
        </div>

        <div class="tab-content">
            <div id="product-tab-general" class="tab-pane active" role="tabpanel">
                <label for="description_input" class="product-field-label">Deskripsi <span class="font-normal text-gray-400">(Opsional)</span></label>
                <textarea id="description_input" name="description" rows="3" maxlength="1000" placeholder="Deskripsi singkat produk..." class="product-input resize-none">{{ old('description', $data->description ?? '') }}</textarea>
                @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div id="product-tab-advanced" class="tab-pane" role="tabpanel">
                <div class="flex flex-col gap-4">
                    <div>
                        <label for="product-sku" class="product-field-label">SKU</label>
                        <input id="product-sku" type="text" name="sku" value="{{ old('sku', $data->sku ?? '') }}" placeholder="Nomor SKU" class="product-input">
                    </div>
                    <div>
                        <label for="product-barcode" class="product-field-label">Barcode</label>
                        <input id="product-barcode" type="text" name="barcode" value="{{ old('barcode', $data->barcode ?? '') }}" placeholder="Nomor barcode" class="product-input">
                    </div>
                    <div>
                        <label for="product-limit" class="product-field-label">Limit Stok</label>
                        <input id="product-limit" type="number" name="limit" value="{{ old('limit', $data->limit ?? '') }}" placeholder="0" min="0" class="product-input">
                    </div>
                    <div>
                        <label for="product-handling" class="product-field-label">Kondisi Penanganan</label>
                        <input id="product-handling" type="text" name="handling" value="{{ old('handling', $data->handling ?? '') }}" placeholder="Contoh: simpan di suhu dingin" class="product-input">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
