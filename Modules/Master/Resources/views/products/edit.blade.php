@extends('template.root')

@section('content')
    <form id="add_product_form" class="form d-flex flex-column flex-lg-row" action="{{ url(Request::segment(1), $data->id) }}"
        method="POST" data-kt-redirect="" enctype="multipart/form-data">
        {{ method_field('PUT') }}
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
                            background-image: url('assets/media/svg/files/blank-image.svg');
                        }

                        [data-bs-theme="dark"] .image-input-placeholder {
                            background-image: url('assets/media/svg/files/blank-image-dark.svg');
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
                    <div class="text-muted fs-7">Tentukan gambar thumbnail produk. Hanya berkas gambar *.png, *.jpg dan
                        *.jpeg yang diterima</div>
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
                        <option></option>
                        <option value="no-receipt" {{ old('status', $data->status) == 'no-receipt' ? 'selected' : '' }}>
                            Tanpa Resep</option>
                        <option value="receipt" {{ old('status', $data->status) == 'receipt' ? 'selected' : '' }}>Dengan
                            Resep</option>
                    </select>
                    @error('status')
                        <div class="text-danger fs-7">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-muted fs-7">Atur status produk.</div>
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
                    <label class="form-label">Satuan Produk</label>
                    <!--end::Label-->
                    <!--begin::Select2-->
                    <select class="form-select mb-2 @error('product_unit_id') is-invalid @enderror" data-control="select2"
                        data-placeholder="Pilih opsi" data-allow-clear="true" {{-- multiple="multiple" --}} name="product_unit_id">
                        <option></option>
                        @foreach ($product_units as $item)
                            <option value="{{ $item->id }}"
                                {{ old('product_unit_id', $data->product_unit_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('product_unit_id')
                        <div class="text-danger fs-7">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-muted fs-7 mb-7">Tambah produk ke kategori.</div>
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
                        @elseif (isset($data->category_id))
                            @php
                                $city = \Modules\Master\Entities\ProductCategory::find($data->category_id);
                            @endphp
                            @if ($city)
                                <option value="{{ $city->id }}" selected>{{ $city->name }}</option>
                            @endif
                        @endif
                    </select>
                    @error('category_id')
                        <div class="text-danger fs-7">{{ $message }}</div>
                    @enderror
                    <!--end::Select2-->
                    <!--begin::Description-->
                    <div class="text-muted fs-7 mb-7">Tambah produk ke unit.</div>
                    <!--end::Description-->
                    <!--end::Input group-->
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
                                        placeholder="Nama produk" value="{{ old('product_name', $data->name) }}" />
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
                                    <div id="kt_ecommerce_add_product_description"
                                        name="kt_ecommerce_add_product_description"
                                        class="min-h-200px mb-2 @error('description') is-invalid @enderror"></div>
                                    <input type="hidden" name="description" id="description_input"
                                        value="{{ old('description', $data->description) }}">
                                    @error('description')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Editor-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Tentukan deskripsi produk untuk visibilitas yang lebih
                                        baik.
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
                                    <label class="required form-label">Harga Dasar</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="price"
                                        class="form-control mb-2 @error('price') is-invalid @enderror"
                                        placeholder="Harga produk" value="{{ old('price', $data->price) }}" />
                                    @error('price')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Tentukan harga produk.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                                {{-- <!--begin::Input group-->
                                <div class="fv-row mb-10">
                                    <!--begin::Label-->
                                    <label class="fs-6 fw-semibold mb-2">Product Unit
                                        <span class="ms-1" data-bs-toggle="tooltip" title="Pilih Satuan Produk">
                                            <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                        </span></label>
                                    <!--End::Label-->
                                    <!--begin::Row-->
                                    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-1 row-cols-xl-3 g-9"
                                        data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
                                        <!--begin::Col-->
                                        <div class="col">
                                            <!--begin::Option-->
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary active d-flex text-start p-6"
                                                data-kt-button="true">
                                                <!--begin::Radio-->
                                                <span
                                                    class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                                    <input class="form-check-input" type="radio" name="product_unit"
                                                        value="1" checked="checked" />
                                                </span>
                                                <!--end::Radio-->
                                                <!--begin::Info-->
                                                <span class="ms-5">
                                                    <span class="fs-4 fw-bold text-gray-800 d-block">Kg</span>
                                                </span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col">
                                            <!--begin::Option-->
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6"
                                                data-kt-button="true">
                                                <!--begin::Radio-->
                                                <span
                                                    class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                                    <input class="form-check-input" type="radio" name="product_unit"
                                                        value="2" />
                                                </span>
                                                <!--end::Radio-->
                                                <!--begin::Info-->
                                                <span class="ms-5">
                                                    <span class="fs-4 fw-bold text-gray-800 d-block">Pcs</span>
                                                </span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col">
                                            <!--begin::Option-->
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6"
                                                data-kt-button="true">
                                                <!--begin::Radio-->
                                                <span
                                                    class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                                    <input class="form-check-input" type="radio" name="product_unit"
                                                        value="3" />
                                                </span>
                                                <!--end::Radio-->
                                                <!--begin::Info-->
                                                <span class="ms-5">
                                                    <span class="fs-4 fw-bold text-gray-800 d-block">Liter</span>
                                                </span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Input group--> --}}
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
                                        placeholder="Nomor SKU" value="{{ old('sku', $data->sku) }}" />
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
                                        placeholder="Nomor Barcode" value="{{ old('barcode', $data->barcode) }}" />
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
                                    <label class="required form-label">Limit Stok</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="number" name="limit"
                                        class="form-control mb-2 @error('limit') is-invalid @enderror"
                                        placeholder="Limit stok" value="{{ old('limit', $data->limit) }}" />
                                    @error('limit')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Masukkan limit stok produk.</div>
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
                                        placeholder="Tipe Penanganan" value="{{ old('handling', $data->handling) }}" />
                                    @error('handling')
                                        <div class="text-danger fs-7">{{ $message }}</div>
                                    @enderror
                                    <!--end::Input-->
                                    <!--begin::Description-->
                                    <div class="text-muted fs-7">Enter limit stock of product.</div>
                                    <!--end::Description-->
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Card header-->
                        </div>
                        <!--end::Inventory-->
                    </div>
                </div>
                <!--end::Tab pane-->
            </div>
            <!--end::Tab content-->
            <div class="d-flex justify-content-end">
                <!--begin::Button-->
                <a href="apps/ecommerce/catalog/products.html" id="kt_ecommerce_add_product_cancel"
                    class="btn btn-light me-5">Batal</a>
                <!--end::Button-->
                <!--begin::Button-->
                <button type="submit" id="kt_ecommerce_add_product_submit" class="btn btn-primary">
                    <span class="indicator-label">Simpan Perubahan</span>
                    <span class="indicator-progress">Mohon tunggu...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
                <!--end::Button-->
            </div>
        </div>
        <!--end::Main column-->
    </form>
@section('script')
    <script>
        let quill; // Definisikan di luar agar bisa diakses global

        document.addEventListener("DOMContentLoaded", function() {
            const quillElement = document.getElementById('kt_ecommerce_add_product_description');

            if (quillElement) {
                quill = new Quill(quillElement, {
                    modules: {
                        toolbar: [
                            [{
                                header: [1, 2, false]
                            }],
                            ['bold', 'italic', 'underline'],
                            ['image', 'code-block']
                        ]
                    },
                    placeholder: "Ketik teks Anda di sini...",
                    theme: "snow"
                });
            }

            quill.root.innerHTML = `{!! old('description', $data->description) !!}`; // Set konten awal

            document.getElementById('add_product_form').addEventListener('submit', function() {
                const description = document.getElementById('description_input');
                description.value = quill.root.innerHTML; // Ambil konten HTML
            });
        });

        $('#category_id').select2({
            placeholder: 'Pilih Kategori',
            ajax: {
                url: '{{ route('ajax.category') }}',
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

        $("form").submit(function() {
            $(this).find(":submit").attr('disabled', 'disabled');
            $(this).find(":submit").html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memuat...`
            );
        });
    </script>
@endsection
@endsection
