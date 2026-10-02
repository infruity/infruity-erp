    <style>
        .nav-link { border-bottom: 2px solid transparent; }
        .nav-link.active { color: #0F5C45 !important; border-bottom-color: #0F5C45 !important; }
        #add_product_form .tab-pane:not(.active) { display: none; }
        #add_product_form .form-control, #add_product_form .form-select { width: 100%; min-height: 40px; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #f9fafb; padding: 0.5rem 0.75rem; font-size: 0.8125rem; outline: none; }
        #add_product_form .form-control:focus, #add_product_form .form-select:focus { border-color: #10b981; background: #fff; box-shadow: 0 0 0 1px #10b981; }
        #add_product_form .remove_variant, #add_product_form .remove_branch { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; min-height: 36px; border-radius: 0.75rem; background: #fef2f2; color: #ef4444; }
        #add_product_form .save_variant { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; min-height: 36px; border-radius: 0.75rem; background: #ecfdf5; color: #047857; }
        #variant_table, #branch_table { width: 100%; font-size: 0.8125rem; }
        #variant_table th, #branch_table th { text-align: left; color: #9ca3af; font-weight: 600; text-transform: uppercase; font-size: 0.7rem; padding: 0.5rem 0.5rem; border-bottom: 1px solid #f3f4f6; }
        #variant_table td, #branch_table td { padding: 0.5rem; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
    </style>
    <form id="add_product_form" class="flex flex-col lg:flex-row p-4 lg:p-6"
        action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST"
        enctype="multipart/form-data" data-kt-redirect="">
        @if (isset($data))
            @method('PUT')
        @endif
        @csrf
        <!--begin::Aside column-->
        <div class="flex flex-col gap-5 w-full lg:w-[320px] mb-6 lg:mr-6">
            <!--begin::Thumbnail settings-->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <!--begin::Card header-->
                <div class="mb-4 flex items-center justify-between">
                    <!--begin::Card title-->
                    <div class="">
                        <h2 class="text-base font-bold text-gray-800">Gambar</h2>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="text-center">
                    <div class="mb-3 flex flex-col items-center gap-3">
                        <div class="w-36 h-36 rounded-2xl bg-gray-50 border border-dashed border-gray-200 overflow-hidden flex items-center justify-center">
                            <img id="product-image-preview" src="{{ isset($data) && $data->image ? asset('storage/' . $data->image) : asset('assets/media/svg/files/blank-image.svg') }}" alt="Pratinjau gambar produk" class="w-full h-full object-contain">
                        </div>
                        <div class="flex items-center gap-2">
                            <label for="product-avatar" class="cursor-pointer inline-flex items-center gap-2 h-9 px-4 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold"><i class="ph ph-image"></i> Pilih Gambar</label>
                            <button id="product-avatar-cancel" type="button" class="hidden h-9 px-3 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">Batal Pilih</button>
                        </div>
                        <input id="product-avatar" type="file" name="avatar" accept="image/png,image/jpeg" class="sr-only">
                    </div>
                    <!--begin::Description-->
                    <div class="text-gray-400 text-xs">Tentukan gambar produk. Hanya berkas gambar dengan ekstensi *.png,
                        *.jpg, dan *.jpeg yang diterima.</div>
                    @error('avatar')
                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                    @enderror
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Thumbnail settings-->
            <!--begin::Status-->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <!--begin::Card header-->
                <div class="mb-4 flex items-center justify-between">
                    <!--begin::Card title-->
                    <div class="">
                        <h2 class="text-base font-bold text-gray-800">Status</h2>
                    </div>
                    <!--end::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <div class="rounded-full bg-emerald-500 w-3.5 h-3.5" id="kt_ecommerce_add_product_status"></div>
                    </div>
                    <!--begin::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="">
                    <!--begin::Select2-->
                    <select class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 mb-2 @error('status') border-red-400 @enderror" data-control="select2"
                        data-hide-search="true" data-placeholder="Pilih opsi" id="kt_ecommerce_add_product_status_select"
                        name="status">
                        <option value="no-receipt"
                            {{ old('status', $data->status ?? '') == 'no-receipt' ? 'selected' : '' }}>
                            Tanpa Resep</option>
                        <option value="receipt" {{ old('status', $data->status ?? '') == 'receipt' ? 'selected' : '' }}>
                            Dengan Resep</option>
                    </select>
                    @error('status')
                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-gray-400 text-xs">Set Status Produk.</div>
                    <!--end::Description-->
                    <!--begin::Datepicker-->
                    <div class="d-none mt-10">
                        <label for="kt_ecommerce_add_product_status_datepicker" class="block text-sm font-semibold text-gray-700 mb-1.5">Pilih tanggal dan waktu
                            publikasi</label>
                        <input class="form-control" id="kt_ecommerce_add_product_status_datepicker"
                            placeholder="Pilih tanggal & waktu" />
                    </div>
                    <!--end::Datepicker-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Status-->
            <!--begin::Category & tags-->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <!--begin::Card header-->
                <div class="mb-4 flex items-center justify-between">
                    <!--begin::Card title-->
                    <div class="">
                        <h2 class="text-base font-bold text-gray-800">Detail Produk</h2>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="">
                    <!--begin::Input group-->
                    <!--begin::Label-->
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5 after:content-['*'] after:text-strawberry after:ml-0.5">Satuan Produk</label>
                    <!--end::Label-->
                    <!--begin::Select2-->
                    <select class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 mb-2 @error('product_unit_id') border-red-400 @enderror" data-control="select2"
                        data-placeholder="Pilih opsi" data-allow-clear="true" {{-- multiple="multiple" --}} name="product_unit_id"
                        required>
                        <option></option>
                        @foreach ($product_units as $item)
                            <option value="{{ $item->id }}"
                                {{ old('product_unit_id', $data->product_unit ?? '') == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('product_unit_id')
                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-gray-400 text-xs mb-4">Tambah satuan produk.</div>
                    <!--end::Description-->
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <!--begin::Label-->
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kategori Produk</label>
                    <!--end::Label-->
                    <!--begin::Select2-->
                    <select class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 mb-2 @error('category_id') border-red-400 @enderror"
                        data-placeholder="Pilih opsi" data-allow-clear="true" {{-- multiple="multiple" --}} name="category_id"
                        id="category_id">
                        @if (old('category_id'))
                            <option value="{{ old('category_id') }}" selected>
                                {{ \Modules\Master\Entities\ProductCategory::find(old('category_id'))->name ?? '' }}
                            </option>
                        @elseif(isset($data->category_id))
                            <option value="{{ $category->id }}" selected>{{ $category->name }}</option>
                        @endif
                    </select>
                    @error('category_id')
                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-gray-400 text-xs mb-4">Tambah produk ke dalam kategori.</div>
                    <!--end::Description-->
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <!--begin::Label-->
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5 after:content-['*'] after:text-strawberry after:ml-0.5">Tipe</label>
                    <!--end::Label-->
                    <!--begin::Select2-->
                    <select class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 mb-2 @error('tipe') border-red-400 @enderror" data-control="select2"
                        data-hide-search="true" data-placeholder="Pilih opsi" id="kt_ecommerce_add_product_status_select"
                        name="tipe">
                        @foreach ($tipe as $key => $value)
                            <option value="{{ $key }}"
                                {{ old('tipe', $data->tipe ?? '') == $key ? 'selected' : '' }}>
                                {{ $value }}</option>
                        @endforeach
                    </select>
                    @error('tipe')
                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-gray-400 text-xs mb-4">Tambah produk ke dalam unit.</div>
                    <!--end::Description-->
                    <!--end::Input group-->
                    <!--begin::Button-->
                    <a href="{{ url('category') }}" class="inline-flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold px-3 py-2 rounded-full transition mb-4">
                        <i class="ph ph-plus"></i>Tambah Kategori</a>
                    <!--end::Button-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Category & tags-->

        </div>
        <!--end::Aside column-->
        <!--begin::Main column-->
        <div class="flex flex-col flex-1 gap-5">
            <!--begin:::Tabs-->
            <ul class="nav flex gap-6 border-b border-gray-100 text-sm font-semibold text-gray-400">
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link pb-3 active text-primary border-b-2 border-primary" data-bs-toggle="tab"
                        href="#kt_ecommerce_add_product_general">Umum</a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link pb-3 hover:text-gray-600" data-bs-toggle="tab"
                        href="#kt_ecommerce_add_product_advanced">Lanjutan</a>
                </li>
                <!--end:::Tab item-->
            </ul>
            <!--end:::Tabs-->
            <!--begin::Tab content-->
            <div class="tab-content">
                <!--begin::Tab pane-->
                <div class="tab-pane fade show active" id="kt_ecommerce_add_product_general" role="tab-panel">
                    <div class="flex flex-col gap-5">
                        <!--begin::General options-->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <!--begin::Card header-->
                            <div class="mb-4 flex items-center justify-between">
                                <div class="">
                                    <h2 class="text-base font-bold text-gray-800">Umum</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="">
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Produk</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="product_name"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 mb-2 @error('product_name') border-red-400 @enderror"
                                        placeholder="Nama Produk" value="{{ old('product_name', $data->name ?? '') }}" />
                                    @error('product_name')
                                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-gray-400 text-xs">Nama produk diperlukan dan disarankan untuk unik.
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div>
                                    <!--begin::Label-->
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Deskripsi</label>
                                    <!--end::Label-->
                                    <!--begin::Editor-->
                                    <textarea name="description" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 @error('description') border-red-400 @enderror" id="description_input"
                                        cols="30" rows="10">{{ old('description', $data->description ?? '') }}</textarea>
                                    @error('description')
                                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                                    @enderror

                                    <!--end::Editor-->
                                    <!--begin::Description-->
                                    <div class="text-gray-400 text-xs">Tentukan deskripsi produk
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card header-->
                        </div>
                        <!--end::General options-->
                        <!--begin::Pricing-->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <!--begin::Card header-->
                            <div class="mb-4 flex items-center justify-between">
                                <div class="">
                                    <h2 class="text-base font-bold text-gray-800">Harga</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="">
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Harga Jual</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="price"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 format-number mb-2 @error('price') border-red-400 @enderror"
                                        placeholder="Harga produk" value="{{ old('price', $data->price ?? '') }}" />
                                    @error('price')
                                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-gray-400 text-xs">Tentukan harga produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                @if (isset($data) && ($data->product_unit == 3 && $data->status == 'receipt'))
                                    <!--begin::Input group-->
                                    <div class="mb-10 fv-row">
                                        <!--begin::Label-->
                                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Fee</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="fee"
                                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 format-number mb-2 @error('fee') border-red-400 @enderror"
                                            placeholder="Fee produk" value="{{ old('fee', $data->fee ?? '') }}" />
                                        @error('fee')
                                            <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                        <!--end::Input-->
                                        <!--begin::Description-->
                                        <div class="text-gray-400 text-xs">Tentukan fee produk.</div>
                                        <!--end::Description-->
                                    </div>
                                    <!--end::Input group-->
                                @endif
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Limit</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="number" name="limit"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 mb-2 @error('limit') border-red-400 @enderror"
                                        placeholder="Limit stok" value="{{ old('limit', $data->limit ?? '') }}" />
                                    @error('limit')
                                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-gray-400 text-xs">Masukkan limit stok produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card header-->
                        </div>
                        <!--end::Pricing-->
                    </div>
                </div>
                <!--end::Tab pane-->
                <!--begin::Tab pane-->
                <div class="tab-pane fade" id="kt_ecommerce_add_product_advanced" role="tab-panel">
                    <div class="flex flex-col gap-5">
                        <!--begin::Inventory-->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <!--begin::Card header-->
                            <div class="mb-4 flex items-center justify-between">
                                <div class="">
                                    <h2 class="text-base font-bold text-gray-800">Inventori</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="">
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">SKU</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="sku"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 mb-2 @error('sku') border-red-400 @enderror"
                                        placeholder="Nomor SKU" value="{{ old('sku', $data->sku ?? '') }}" />
                                    @error('sku')
                                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-gray-400 text-xs">Masukkan SKU produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Barcode</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="barcode"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 mb-2 @error('barcode') border-red-400 @enderror"
                                        placeholder="Nomor Barcode" value="{{ old('barcode', $data->barcode ?? '') }}" />
                                    @error('barcode')
                                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-gray-400 text-xs">Masukkan nomor barcode produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kondisi Penanganan</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="handling"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 mb-2 @error('handling') border-red-400 @enderror"
                                        placeholder="Handling" value="{{ old('handling', $data->handling ?? '') }}" />
                                    @error('handling')
                                        <div class="text-strawberry text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-gray-400 text-xs">Masukkan limit stok produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card header-->
                        </div>
                        <!--end::Inventory-->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <!--begin::Card header-->
                            <div class="mb-4 flex items-center justify-between">
                                <div class="">
                                    <h2 class="text-base font-bold text-gray-800">Varian</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="">
                                <div class="overflow-x-auto">
                                    <table class="mb-4" id="variant_table">
                                        <thead>
                                            <tr class="">
                                                <th class="">Produk</th>
                                                <th class="">Harga</th>
                                                <th class="text-right">Opsi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="kt_ecommerce_edit_order_selected_products_body">
                                            {{-- @if (isset($variant) && $variant->count() > 0)
                                                @foreach ($variant as $item)
                                                    <tr>
                                                        <td>
                                                            <input type="text" name="variant_name[]"
                                                                class="form-control mb-2" placeholder="Nama Produk"
                                                                value="{{ $item->name }}" />
                                                        </td>
                                                        <td>
                                                            <input type="text" name="variant_price[]"
                                                                class="form-control format-number mb-2"
                                                                placeholder="Harga Produk" inputmode="numeric"
                                                                value="{{ $item->price }}" />
                                                        </td>
                                                        <td class="text-end">
                                                            <button type="button"
                                                                class="btn btn-icon btn-danger remove_variant">
                                                                <i class="ki-outline ki-cross fs-2"></i>
                                                            </button>
                                                        </td>
                                                @endforeach
                                            @endif --}}
                                        </tbody>
                                    </table>
                                </div>
                                <!--end::Input group-->
                                <button class="variant inline-flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold px-3 py-2 rounded-full transition mb-4" type="button"
                                    onclick="addVariant()">
                                    <i class="ph ph-plus"></i>Buat variant baru
                                </button>
                            </div>
                            <!--end::Card header-->
                        </div>

                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                            <!--begin::Card header-->
                            <div class="mb-4 flex items-center justify-between">
                                <div class="">
                                    <h2 class="text-base font-bold text-gray-800">Cabang</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="">
                                <div class="overflow-x-auto">
                                    <table class="mb-4" id="branch_table">
                                        <thead>
                                            <tr class="">
                                                <th class="">Cabang</th>
                                                <th class="">Harga</th>
                                                <th class="text-right">Opsi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="kt_ecommerce_edit_order_selected_products_branch_body">
                                        </tbody>
                                    </table>
                                </div>
                                <!--end::Input group-->
                                <button class="variant inline-flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold px-3 py-2 rounded-full transition mb-4" type="button"
                                    onclick="addBranch()">
                                    <i class="ph ph-plus"></i>Buat cabang baru
                                </button>
                            </div>
                            <!--end::Card header-->
                        </div>
                    </div>
                </div>
                <!--end::Tab pane-->
            </div>
            <!--end::Tab content-->
            <div class="product-form-actions flex justify-end mt-4">
                <!--begin::Button-->
                <a href="{{ url(Request::segment(1)) }}" id="kt_ecommerce_add_product_cancel"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-5 py-2.5 rounded-full transition mr-3">Batal</a>
                <!--end::Button-->
                <!--begin::Button-->
                <button type="submit" id="kt_ecommerce_add_product_submit" class="bg-primary hover:bg-primary-light text-white text-sm font-semibold px-6 py-2.5 rounded-full transition">
                    <span class="indicator-label">Simpan Perubahan</span>
                    <span class="indicator-progress">Mohon ditunggu...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
                <!--end::Button-->
            </div>
        </div>
        <!--end::Main column-->
    </form>
    <dialog id="editVariantModal" class="w-[calc(100%-2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-black/60">
            <form id="editVariantForm" class="bg-white">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h5 class="text-base font-bold text-gray-900">Ubah Varian</h5>
                    <button type="button" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500" onclick="document.getElementById('editVariantModal').close()" aria-label="Tutup"><i class="ph-bold ph-x"></i></button>
                </div>
                    <div class="px-5 py-5 space-y-4">
                        <input type="hidden" name="id" id="variant_id">
                        <div class="mb-3">
                            <label for="variant_name" class="block text-xs font-semibold text-gray-600 mb-1.5">Nama Produk</label>
                            <input type="text" name="product_name" id="variant_name" class="w-full h-11 rounded-xl border border-gray-200 px-4 text-sm variant">
                        </div>
                        <div class="mb-3">
                            <label for="variant_price" class="block text-xs font-semibold text-gray-600 mb-1.5">Harga Produk</label>
                            <input type="number" name="price" id="variant_price"
                                class="w-full h-11 rounded-xl border border-gray-200 px-4 text-sm variant">
                        </div>
                    </div>
                    <div class="px-5 py-4 border-t border-gray-100 flex gap-3">
                        <button type="button" onclick="document.getElementById('editVariantModal').close()" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold">Batal</button>
                        <button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold variant">Simpan Perubahan</button>
                    </div>
            </form>
    </dialog>
