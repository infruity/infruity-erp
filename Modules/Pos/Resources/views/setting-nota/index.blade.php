@extends('template.root')

@section('content')
    <form id="add_product_form" class="form d-flex flex-column flex-lg-row"
        action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST"
        enctype="multipart/form-data" data-kt-redirect="">
        @if (isset($data))
            @method('PUT')
        @endif
        @csrf
        <!--begin::Main column-->
        <div class="d-flex flex-column flex-lg-row-fluid gap-7 gap-lg-10">
            <div class="card card-flush py-4 mb-5">
                <div class="card-header">
                    <div class="card-title">
                        <h2>Pilih Cabang</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <select name="branch_id" class="form-select mb-2" onchange="window.location.href='{{ url('setting-nota') }}?cabang_filter='+this.value">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $selected_branch == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="text-end">
                <a href="{{ url('setting-nota/view-receipt') }}?cabang_filter={{ $selected_branch }}" class="btn btn-success"><i class="fa fa-eye"></i>Preview
                    Struk</a>
            </div>
            <!--begin::Thumbnail settings-->
            <div id="logo_upload_container" class="card card-flush py-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Logo</h2>
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
                            background-image: url({{ isset($data) && isset($data->logo) ? asset('storage/' . $data->logo) : asset('assets/media/svg/files/blank-image.svg') }});
                        }

                        [data-bs-theme="dark"] .image-input-placeholder {
                            background-image: url({{ isset($data) && isset($data->logo) ? asset('storage/' . $data->logo) : asset('assets/media/svg/files/blank-image-dark.svg') }});
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
                            data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                            <i class="ki-outline ki-pencil fs-7"></i>
                            <!--begin::Inputs-->
                            <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                            <input type="hidden" name="avatar_remove" />
                            <!--end::Inputs-->
                        </label>
                        <!--end::Label-->
                        <!--begin::Cancel-->
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </span>
                        <!--end::Cancel-->
                        <!--begin::Remove-->
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                            data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </span>
                        <!--end::Remove-->
                    </div>
                    <!--end::Image input-->
                    <!--begin::Description-->
                    <div class="text-muted fs-7">Set the product thumbnail image. Only *.png, *.jpg and *.jpeg image files
                        are accepted</div>
                    <!--end::Description-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Thumbnail settings-->
            <div class="card card-flush py-4">
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <!--begin::Billing address-->
                    <div class="d-flex flex-column gap-5 gap-md-7">
                        <!--begin::Input group-->
                        <div>
                            <!--begin::Label-->
                            <label class="form-label">Header</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="text" class="form-control mb-2" name="brand_name" placeholder="Ex : in!fruity"
                                value="{{ $data->brand_name ?? old('brand_name') }}">
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div>
                            <!--begin::Label-->
                            <label class="form-label">Alamat</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="text" class="form-control mb-2" name="brand_address"
                                placeholder="Ex : Jl. Raya No. 1, Jakarta"
                                value="{{ $data->brand_address ?? old('brand_address') }}">
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div>
                            <!--begin::Label-->
                            <label class="form-label">Social Media</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="text" class="form-control mb-2" name="brand_social_media"
                                placeholder="Ex : @infruity"
                                value="{{ $data->brand_social_media ?? old('brand_social_media') }}">
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div>
                            <!--begin::Label-->
                            <label class="form-label">No. Telepon</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="text" class="form-control mb-2" name="brand_phone"
                                placeholder="Ex : (021) 12345678"
                                value="{{ $data->brand_phone ?? old('brand_phone') }}">
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div>
                            <!--begin::Label-->
                            <label class="form-label">Header Pesan</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="text" class="form-control mb-2" name="brand_greeting"
                                placeholder="Ex : Selamat datang di Infruity"
                                value="{{ $data->brand_greeting ?? old('brand_greeting') }}">
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div>
                            <!--begin::Label-->
                            <label class="form-label">Catatan</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <input type="text" class="form-control mb-2" name="note"
                                placeholder="Ex : Selamat datang di Infruity"
                                value="{{ $data->note ?? old('note') }}">
                            <!--end::Editor-->
                        </div>
                        <!--end::Input group-->
                        <!--end::Input group-->
                        <div class="fv-row">
                            <label class="form-label d-flex justify-content-between">
                                <span>Tampil Nama Kasir?</span>
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_using_cashier" value="1"
                                        {{ isset($data) && $data->is_using_cashier ? 'checked' : '' }}>
                                </div>
                            </label>
                        </div>
                        <!--begin::Input group-->
                        <div class="fv-row">
                            <label class="form-label d-flex justify-content-between">
                                <span>Tampil Nama Pelanggan?</span>
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_using_customer" value="1"
                                        {{ isset($data) && $data->is_using_customer ? 'checked' : '' }}>
                                </div>
                            </label>
                        </div>

                        <div class="fv-row">
                            <label class="form-label d-flex justify-content-between">
                                <span>Tampil Tanggal Transaksi?</span>
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_using_date" value="1"
                                        {{ isset($data) && $data->is_using_date ? 'checked' : '' }}>
                                </div>
                            </label>
                        </div>

                        <div class="fv-row">
                            <label class="form-label d-flex justify-content-between">
                                <span>Tampil Nomor Faktur?</span>
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_using_invoice_number"
                                        value="1"
                                        {{ isset($data) && $data->is_using_invoice_number ? 'checked' : '' }}>
                                </div>
                            </label>
                        </div>
                        <!--end::Input group-->
                        {{-- <!--begin::Input group-->
                        <div>
                            <!--begin::Label-->
                            <label class="form-label">Header</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <div id="header_editor" name="header_editor" class="min-h-150px mb-2"></div>
                            <!--end::Editor-->
                            <!--begin::Description-->
                            <div class="text-muted fs-7">Set a header nota.</div>
                            <input type="hidden" name="header" id="header_input">
                            <!--end::Description-->
                        </div>
                        <!--end::Input group--> --}}
                        {{-- <!--begin::Input group-->
                        <div>
                            <!--begin::Label-->
                            <label class="form-label">Footer</label>
                            <!--end::Label-->
                            <!--begin::Editor-->
                            <div id="footer_editor" name="footer_editor" class="min-h-150px mb-2"></div>
                            <!--end::Editor-->
                            <!--begin::Description-->
                            <div class="text-muted fs-7">Set a footer nota.</div>
                            <input type="hidden" name="footer" id="footer_input">
                            <!--end::Description-->
                        </div>
                        <!--end::Input group--> --}}
                    </div>
                    <!--end::Billing address-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Order details-->
            <div class="d-flex justify-content-end">
                <!--begin::Button-->
                <a href="{{ url(Request::segment(1)) }}" id="kt_ecommerce_edit_order_cancel"
                    class="btn btn-light me-5">Cancel</a>
                <!--end::Button-->
                <!--begin::Button-->
                <button type="submit" id="kt_ecommerce_edit_order_submit" class="btn btn-primary">
                    <span class="indicator-label">Save Changes</span>
                    <span class="indicator-progress">Please wait...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
                <!--end::Button-->
            </div>
        </div>
        <!--end::Main column-->
    </form>
@section('script')
    <script>
        $(document).ready(function() {
            // Initialize Select2 for province
            const headerEditor = document.getElementById('header_editor');
            const footerEditor = document.getElementById('footer_editor');

            if (headerEditor) {
                header = new Quill(headerEditor, {
                    modules: {
                        toolbar: [
                            [{
                                header: [1, 2, false]
                            }],
                            ['bold', 'italic', 'underline'],
                            ['image', 'code-block']
                        ]
                    },
                    placeholder: "Type your text here...",
                    theme: "snow"
                });
            }

            if (footerEditor) {
                footer = new Quill(footerEditor, {
                    modules: {
                        toolbar: [
                            [{
                                header: [1, 2, false]
                            }],
                            ['bold', 'italic', 'underline'],
                            ['image', 'code-block']
                        ]
                    },
                    placeholder: "Type your text here...",
                    theme: "snow"
                });
            }

            // header.root.innerHTML = `{!! $data->header ?? old('description') !!}`; // Set konten awal
            footer.root.innerHTML = `{!! $data->footer ?? old('description') !!}`; // Set konten awal

            document.getElementById('add_product_form').addEventListener('submit', function() {
                // const headerData = document.getElementById('header_input');
                const footerData = document.getElementById('footer_input');
                // headerData.value = header.root.innerHTML; // Ambil konten HTML
                footerData.value = footer.root.innerHTML; // Ambil konten HTML
            });

            $('input[name="logo_option"]').on('change', function() {
                const selectedValue = $(this).val();
                console.log(selectedValue);
                if (selectedValue == '1') {
                    $('#logo_upload_container').show();
                } else {
                    $('#logo_upload_container').hide();
                }
            });

            // Trigger langsung saat page load agar sesuai kondisi awal
            $('input[name="logo_option"]:checked').trigger('change');
        });
    </script>
@endsection
@endsection
