@extends('template.root')

@section('content')
    <form id="add_product_form" class="form d-flex flex-column flex-lg-row"
        action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST"
        enctype="multipart/form-data" data-kt-redirect="">
        @if (isset($data))
            @method('PUT')
        @endif
        @csrf
        <!--begin::Aside column-->
        <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
            <!--begin::Thumbnail settings-->
            <div class="card card-flush py-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Gambar</h2>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body text-center pt-0">
                    <!--begin::Image input-->
                    <!--begin::Image input placeholder-->
                    <style>
                        .image-input-placeholder {
                            background-image: url({{ isset($data) && isset($data->image) ? asset('storage/' . $data->image) : asset('assets/media/svg/files/blank-image.svg') }});
                        }

                        [data-bs-theme="dark"] .image-input-placeholder {
                            background-image: url({{ isset($data) && isset($data->image) ? asset('storage/' . $data->image) : asset('assets/media/svg/files/blank-image-dark.svg') }});
                        }
                    </style>
                    <!--end::Image input placeholder-->
                    <div class="image-input image-input-empty image-input-outline image-input-placeholder mb-3"
                        data-kt-image-input="true">
                        <!--begin::Preview existing avatar-->
                        <div class="image-input-wrapper w-150px h-150px"></div>
                        <!--end::Preview existing avatar-->
                        <!--begin::Label-->
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                            data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Ganti gambar">
                            <i class="ki-outline ki-pencil fs-7"></i>
                            <!--begin::Inputs-->
                            <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                            <input type="hidden" name="avatar_remove" />
                            <!--end::Inputs-->
                        </label>
                        <!--end::Label-->
                        <!--begin::Cancel-->
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Batal">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </span>
                        <!--end::Cancel-->
                        <!--begin::Remove-->
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                            data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Hapus gambar">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </span>
                        <!--end::Remove-->
                    </div>
                    <!--end::Image input-->
                    <!--begin::Description-->
                    <div class="text-muted fs-7">Tentukan gambar produk. Hanya berkas gambar dengan ekstensi *.png,
                        *.jpg, dan *.jpeg yang diterima.</div>
                    @error('avatar')
                        <div class="text-danger fs-7">{{ $message }}</div>
                    @enderror
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Thumbnail settings-->
            <!--begin::Status-->
            <div class="card card-flush py-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Status</h2>
                    </div>
                    <!--end::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <div class="rounded-circle bg-success w-15px h-15px" id="kt_ecommerce_add_product_status"></div>
                    </div>
                    <!--begin::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <!--begin::Select2-->
                    <select class="form-select mb-2 @error('status') is-invalid @enderror" data-control="select2"
                        data-hide-search="true" data-placeholder="Pilih opsi" id="kt_ecommerce_add_product_status_select"
                        name="status">
                        <option value="no-receipt"
                            {{ old('status', $data->status ?? '') == 'no-receipt' ? 'selected' : '' }}>
                            Tanpa Resep</option>
                        <option value="receipt" {{ old('status', $data->status ?? '') == 'receipt' ? 'selected' : '' }}>
                            Dengan Resep</option>
                    </select>
                    @error('status')
                        <div class="text-danger fs-7">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-muted fs-7">Set Status Produk.</div>
                    <!--end::Description-->
                    <!--begin::Datepicker-->
                    <div class="d-none mt-10">
                        <label for="kt_ecommerce_add_product_status_datepicker" class="form-label">Pilih tanggal dan waktu
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
            <div class="card card-flush py-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Detail Produk</h2>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <!--begin::Input group-->
                    <!--begin::Label-->
                    <label class="form-label required">Satuan Produk</label>
                    <!--end::Label-->
                    <!--begin::Select2-->
                    <select class="form-select mb-2 @error('product_unit_id') is-invalid @enderror" data-control="select2"
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
                        <div class="text-danger fs-7">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-muted fs-7 mb-7">Tambah satuan produk.</div>
                    <!--end::Description-->
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <!--begin::Label-->
                    <label class="form-label">Kategori Produk</label>
                    <!--end::Label-->
                    <!--begin::Select2-->
                    <select class="form-select mb-2 @error('category_id') is-invalid @enderror"
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
                        <div class="text-danger fs-7">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-muted fs-7 mb-7">Tambah produk ke dalam kategori.</div>
                    <!--end::Description-->
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <!--begin::Label-->
                    <label class="form-label required">Tipe</label>
                    <!--end::Label-->
                    <!--begin::Select2-->
                    <select class="form-select mb-2 @error('tipe') is-invalid @enderror" data-control="select2"
                        data-hide-search="true" data-placeholder="Pilih opsi" id="kt_ecommerce_add_product_status_select"
                        name="tipe">
                        @foreach ($tipe as $key => $value)
                            <option value="{{ $key }}"
                                {{ old('tipe', $data->tipe ?? '') == $key ? 'selected' : '' }}>
                                {{ $value }}</option>
                        @endforeach
                    </select>
                    @error('tipe')
                        <div class="text-danger fs-7">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-muted fs-7 mb-7">Tambah produk ke dalam unit.</div>
                    <!--end::Description-->
                    <!--end::Input group-->
                    <!--begin::Button-->
                    <a href="{{ url('category') }}" class="btn btn-light-primary btn-sm mb-10">
                        <i class="ki-outline ki-plus fs-2"></i>Tambah Kategori</a>
                    <!--end::Button-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Category & tags-->

        </div>
        <!--end::Aside column-->
        <!--begin::Main column-->
        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <!--begin:::Tabs-->
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-n2">
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                        href="#kt_ecommerce_add_product_general">Umum</a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_ecommerce_add_product_advanced">Lanjutan</a>
                </li>
                <!--end:::Tab item-->
            </ul>
            <!--end:::Tabs-->
            <!--begin::Tab content-->
            <div class="tab-content">
                <!--begin::Tab pane-->
                <div class="tab-pane fade show active" id="kt_ecommerce_add_product_general" role="tab-panel">
                    <div class="d-flex flex-column gap-7 gap-lg-10">
                        <!--begin::General options-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>Umum</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="required form-label">Nama Produk</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="product_name"
                                        class="form-control mb-2 @error('product_name') is-invalid @enderror"
                                        placeholder="Nama Produk" value="{{ old('product_name', $data->name ?? '') }}" />
                                    @error('product_name')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Nama produk diperlukan dan disarankan untuk unik.
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div>
                                    <!--begin::Label-->
                                    <label class="form-label">Deskripsi</label>
                                    <!--end::Label-->
                                    <!--begin::Editor-->
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" id="description_input"
                                        cols="30" rows="10">{{ old('description', $data->description ?? '') }}</textarea>
                                    @error('description')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror

                                    <!--end::Editor-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Tentukan deskripsi produk
                                    </div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card header-->
                        </div>
                        <!--end::General options-->
                        <!--begin::Pricing-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>Harga</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="required form-label">Harga Jual</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="price"
                                        class="form-control format-number mb-2 @error('price') is-invalid @enderror"
                                        placeholder="Harga produk" value="{{ old('price', $data->price ?? '') }}" />
                                    @error('price')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Tentukan harga produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                @if (isset($data) && ($data->product_unit == 3 && $data->status == 'receipt'))
                                    <!--begin::Input group-->
                                    <div class="mb-10 fv-row">
                                        <!--begin::Label-->
                                        <label class="form-label">Fee</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="fee"
                                            class="form-control format-number mb-2 @error('fee') is-invalid @enderror"
                                            placeholder="Fee produk" value="{{ old('fee', $data->fee ?? '') }}" />
                                        @error('fee')
                                            <div class="text-danger fs-7">{{ $message }}</div>
                                        @enderror
                                        <!--end::Input-->
                                        <!--begin::Description-->
                                        <div class="text-muted fs-7">Tentukan fee produk.</div>
                                        <!--end::Description-->
                                    </div>
                                    <!--end::Input group-->
                                @endif
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label">Limit</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="number" name="limit"
                                        class="form-control mb-2 @error('limit') is-invalid @enderror"
                                        placeholder="Limit stok" value="{{ old('limit', $data->limit ?? '') }}" />
                                    @error('limit')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Masukkan limit stok produk.</div>
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
                    <div class="d-flex flex-column gap-7 gap-lg-10">
                        <!--begin::Inventory-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>Inventori</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label">SKU</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="sku"
                                        class="form-control mb-2 @error('sku') is-invalid @enderror"
                                        placeholder="Nomor SKU" value="{{ old('sku', $data->sku ?? '') }}" />
                                    @error('sku')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Masukkan SKU produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label">Barcode</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="barcode"
                                        class="form-control mb-2 @error('barcode') is-invalid @enderror"
                                        placeholder="Nomor Barcode" value="{{ old('barcode', $data->barcode ?? '') }}" />
                                    @error('barcode')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Masukkan nomor barcode produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                <!--begin::Input group-->
                                <div class="mb-10 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label">Kondisi Penanganan</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="handling"
                                        class="form-control mb-2 @error('handling') is-invalid @enderror"
                                        placeholder="Handling" value="{{ old('handling', $data->handling ?? '') }}" />
                                    @error('handling')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Masukkan limit stok produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card header-->
                        </div>
                        <!--end::Inventory-->
                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>Varian</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <div class="table table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-3 mb-5" id="variant_table">
                                        <thead>
                                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                                <th class="min-w-200px">Produk</th>
                                                <th class="min-w-100px">Harga</th>
                                                <th class="min-w-100px text-end">Opsi</th>
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
                                <button class="variant btn btn-light-primary btn-sm mb-10" type="button"
                                    onclick="addVariant()">
                                    <i class="ki-outline ki-plus fs-2"></i>Buat variant baru
                                </button>
                            </div>
                            <!--end::Card header-->
                        </div>

                        <div class="card card-flush py-4">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>Cabang</h2>
                                </div>
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-0">
                                <div class="table table-responsive">
                                    <table class="table align-middle table-row-dashed fs-6 gy-3 mb-5" id="branch_table">
                                        <thead>
                                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                                <th class="min-w-200px">Cabang</th>
                                                <th class="min-w-100px">Harga</th>
                                                <th class="min-w-100px text-end">Opsi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="kt_ecommerce_edit_order_selected_products_branch_body">
                                        </tbody>
                                    </table>
                                </div>
                                <!--end::Input group-->
                                <button class="variant btn btn-light-primary btn-sm mb-10" type="button"
                                    onclick="addBranch()">
                                    <i class="ki-outline ki-plus fs-2"></i>Buat cabang baru
                                </button>
                            </div>
                            <!--end::Card header-->
                        </div>
                    </div>
                </div>
                <!--end::Tab pane-->
            </div>
            <!--end::Tab content-->
            <div class="d-flex justify-content-end">
                <!--begin::Button-->
                <a href="{{ url(Request::segment(1)) }}" id="kt_ecommerce_add_product_cancel"
                    class="btn btn-light me-5">Batal</a>
                <!--end::Button-->
                <!--begin::Button-->
                <button type="submit" id="kt_ecommerce_add_product_submit" class="btn btn-primary">
                    <span class="indicator-label">Simpan Perubahan</span>
                    <span class="indicator-progress">Mohon ditunggu...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
                <!--end::Button-->
            </div>
        </div>
        <!--end::Main column-->
    </form>
    <div class="modal fade" id="editVariantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editVariantForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Varian</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="variant_id">
                        <div class="mb-3">
                            <label>Nama Produk</label>
                            <input type="text" name="product_name" id="variant_name" class="form-control variant">
                        </div>
                        <div class="mb-3">
                            <label>Harga Produk</label>
                            <input type="number" name="price" id="variant_price"
                                class="form-control format-number variant">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary variant">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@section('script')
    <script>
        $('#category_id').select2({
            placeholder: 'Ketik nama kategori',
            tags: true, // ini aktifkan fitur menambah item baru
            ajax: {
                url: '{{ route('ajax.category') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data, params) {
                    const term = params.term || '';

                    // Map hasil dari server
                    let results = data.map(item => ({
                        id: item.id, // penting: pastikan id-nya sesuai yg mau kamu simpan
                        text: item.name
                    }));

                    // Kalau term (yang diketik user) tidak ada di hasil, tambahkan manual
                    if (term && !results.some(r => r.text.toLowerCase() === term.toLowerCase())) {
                        results.push({
                            id: term, // kita pakai term sebagai id juga (karena kategori baru)
                            text: term
                        });
                    }

                    return {
                        results: results
                    };
                },
                cache: true
            },
            createTag: function(params) {
                const term = $.trim(params.term);

                if (term === '') {
                    return null;
                }

                return {
                    id: term,
                    text: term,
                    newTag: true // optional: kalau mau tandai item baru
                };
            }
        });

        $("form").submit(function() {
            $(this).find(":submit").attr('disabled', 'disabled');
            $(this).find(":submit").html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memuat...`
            );
        });

        $(document).ready(function() {
            const path = window.location.pathname;
            if (/products\/\d+\/show/.test(path)) {
                // Disable semua input/select/textarea kecuali yang punya class 'variant'
                document.querySelectorAll('input, select, textarea, button').forEach(function(el) {
                    if (!el.classList.contains('variant')) {
                        el.disabled = true;
                    }
                });

                // Khusus tombol submit: sembunyikan jika tidak class 'variant'
                const submitBtn = document.getElementById('kt_ecommerce_add_product_submit');
                if (submitBtn) {
                    submitBtn.style.display = 'none';
                }
            }
        });

        function addVariant() {
            let html = `
            <tr>
                <td>
                    <select name="variant[id][]" class="form-select mb-2 select2_product"></select>
                </td>
                <td>
                    <input type="text" inputmode="numeric" name="variant[price][]" class="form-control format-number mb-2" placeholder="Harga Produk" />
                </td>                    
                <td class="text-end">
                    <button type="button" class="btn btn-icon btn-danger remove_variant">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </td>
            </tr>
            `;
            $('#kt_ecommerce_edit_order_selected_products_body').append(html);
            $('#kt_ecommerce_edit_order_selected_products_body .select2_product').select2({
                placeholder: 'Ketik nama produk',
                tags: true, // ini aktifkan fitur menambah item baru
                ajax: {
                    url: "{{ route('ajax.getProduct') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function(data, params) {
                        const term = params.term || '';

                        // Map hasil dari server
                        let results = data.map(item => ({
                            id: item.id, // penting: pastikan id-nya sesuai yg mau kamu simpan
                            text: item.name
                        }));

                        // Kalau term (yang diketik user) tidak ada di hasil, tambahkan manual
                        if (term && !results.some(r => r.text.toLowerCase() === term.toLowerCase())) {
                            results.push({
                                id: term, // kita pakai term sebagai id juga (karena produk baru)
                                text: term
                            });
                        }

                        return {
                            results: results
                        };
                    },
                    cache: true
                },
                createTag: function(params) {
                    const term = $.trim(params.term);

                    if (term === '') {
                        return null;
                    }

                    return {
                        id: term,
                        text: term,
                        newTag: true // optional: kalau mau tandai item baru
                    };
                }
            });



            bindFormatNumber();
        }
        $('#variant_table').on('click', '.remove_variant', function() {
            $(this).closest('tr').remove();
        });

        @if (isset($variant) && $variant->count() > 0)
            const response = {!! json_encode($variant) !!};
            $('#kt_ecommerce_edit_order_selected_products_body').empty();

            // Loop hasil response
            response.forEach(item => {
                let html = `
                <tr>
                    <td>
                        <select name="variant[id][]" class="form-select mb-2 select2_product">
                            <option value="${item.product_id}" selected>${item.product.name}</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="variant[price][]" class="form-control format-number mb-2" placeholder="Product price" value="${item.product.price || ''}"/>
                    </td>                    
                    <td class="text-end">
                        <button type="button" class="btn btn-icon btn-danger remove_variant">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                    </td>
                </tr>
                `;
                $('#kt_ecommerce_edit_order_selected_products_body').append(html);
            });

            $('#kt_ecommerce_edit_order_selected_products_body .select2_product').select2({
                placeholder: 'Pilih produk',
                ajax: {
                    url: "{{ route('ajax.getProduct') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name
                        }))
                    })
                }
            });
        @endif

        function addBranch() {
            let html = `
            <tr>
                <td>
                    <select name="branch[id][]" class="form-select mb-2 select2_product"></select>
                </td>
                <td>
                    <input type="text" inputmode="numeric" name="branch[price][]" class="form-control format-number mb-2" placeholder="Harga Produk" />
                </td>                    
                <td class="text-end">
                    <button type="button" class="btn btn-icon btn-danger remove_branch">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </td>
            </tr>
            `;
            $('#kt_ecommerce_edit_order_selected_products_branch_body').append(html);
            $('#kt_ecommerce_edit_order_selected_products_branch_body .select2_product').select2({
                placeholder: 'Ketik nama branch',
                tags: true, // ini aktifkan fitur menambah item baru
                ajax: {
                    url: "{{ route('ajax.getBranch') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function(data, params) {
                        const term = params.term || '';
                        let results = data.map(item => ({
                            id: item.id, // penting: pastikan id-nya sesuai yg mau kamu simpan
                            text: item.name
                        }));
                        return {
                            results: results
                        };
                    },
                    cache: true
                },
            });
            bindFormatNumber();
        }
        $('#branch_table').on('click', '.remove_branch', function() {
            $(this).closest('tr').remove();
        });

        @if (isset($product_branch) && $product_branch->count() > 0)
            const productBranch = {!! json_encode($product_branch) !!};
            $('#kt_ecommerce_edit_order_selected_products_branch_body').empty();

            // Loop hasil response
            productBranch.forEach(item => {
                let html = `
                <tr>
                    <td>
                        <select name="branch[id][]" class="form-select mb-2 select2_product">
                            <option value="${item.branch_id}" selected>${item.branch.name}</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="branch[price][]" class="form-control format-number mb-2" placeholder="Branch price" value="${item.price || ''}"/>
                    </td>                    
                    <td class="text-end">
                        <button type="button" class="btn btn-icon btn-danger remove_branch">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                    </td>
                </tr>
                `;
                $('#kt_ecommerce_edit_order_selected_products_branch_body').append(html);
            });

            $('#kt_ecommerce_edit_order_selected_products_branch_body .select2_product').select2({
                placeholder: 'Pilih produk',
                ajax: {
                    url: "{{ route('ajax.getProduct') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name
                        }))
                    })
                }
            });
        @endif
    </script>
    @if (request()->segment(3) === 'show')
        <script>
            $(document).ready(function() {
                const productId = "{{ $data->id }}"; // dari Blade

                $('#variant_table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('variants.get') }}',
                        data: function(d) {
                            d.product_id = productId;
                        }
                    },
                    columns: [{
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'price',
                            name: 'price'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            className: 'text-end'
                        }
                    ]
                });

                let table = $('#variant_table').DataTable();

                // Edit: buka modal dan isi data
                $('#variant_table').on('click', '.edit-variant', function() {
                    $('#variant_id').val($(this).data('id'));
                    $('#variant_name').val($(this).data('name'));
                    $('#variant_price').val($(this).data('price'));
                    $('#editVariantModal').modal('show');
                });

                // Submit Edit
                $('#editVariantForm').submit(function(e) {
                    e.preventDefault();
                    let id = $('#variant_id').val();

                    $.ajax({
                        url: '/products/variants/' + id,
                        type: 'PUT',
                        data: {
                            product_name: $('#variant_name').val(),
                            price: $('#variant_price').val(),
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function() {
                            Swal.fire('Berhasil', 'Varian berhasil diperbarui.', 'success');
                            $('#editVariantModal').modal('hide');
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire('Gagal', 'Terjadi kesalahan saat memperbarui.', 'error');
                        }
                    });
                });

                // Hapus Variant
                $('#variant_table').on('click', '.delete-variant', function() {
                    let id = $(this).data('id');

                    Swal.fire({
                        title: 'Yakin?',
                        text: "Data akan dihapus!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '/products/variants/' + id,
                                type: 'DELETE',
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function() {
                                    Swal.fire('Dihapus!', 'Varian telah dihapus.',
                                        'success');
                                    table.ajax.reload();
                                },
                                error: function() {
                                    Swal.fire('Gagal', 'Tidak dapat menghapus data.',
                                        'error');
                                }
                            });
                        }
                    });
                });
            });

            function addVariant() {
                let html = `
                <tr>
                    <td>
                        <input type="text" name="product_name[]" class="form-control mb-2" placeholder="Nama produk" />
                    </td>
                    <td>
                        <input type="text" name="price[]" class="form-control format-number mb-2" placeholder="Harga produk" />
                    </td>                    
                    <td class="text-end">
                        <button type="button" class="btn btn-icon btn-danger save_variant">
                            <i class="ki-outline ki-check fs-2"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-danger remove_variant">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                    </td>
                </tr>
            `;
                $('#kt_ecommerce_edit_order_selected_products_body').append(html);
                $('#variant_table').on('click', '.remove_variant', function() {
                    $(this).closest('tr').remove();
                });
                bindFormatNumber(); // Re-bind ke elemen baru setelah append
            }
            $('#variant_table').on('click', '.remove_variant', function() {
                $(this).closest('tr').remove();
            });

            $('#variant_table').on('click', '.save_variant', function() {
                let $row = $(this).closest('tr');
                let productName = $row.find('input[name="product_name[]"]').val();
                let price = $row.find('input[name="price[]"]').val();
                let productId = {{ $data->id }}; // jika kamu butuh ID produk utama

                if (productName === '' || price === '') {
                    alert('Nama produk dan harga harus diisi.');
                    return;
                }

                $.ajax({
                    url: '/products/variant/store',
                    method: 'POST',
                    data: {
                        product_name: productName,
                        price: price,
                        parent_id: productId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Varian produk berhasil disimpan.',
                            showConfirmButton: false
                        });
                        $('#variant_table').DataTable().ajax.reload(null,
                            false); // false = tetap di halaman sekarang
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Gagal menyimpan varian produk. Cek console untuk detail.',
                        });
                        console.error(xhr.responseText);
                    }
                });
            });
        </script>
    @endif
@endsection
@endsection
