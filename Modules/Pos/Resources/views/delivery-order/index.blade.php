@extends('template.root')

@section('content')
    {{-- @livewire('product-table') --}}
    <div class="delivery-order-page">
        <style>
            .delivery-order-page .delivery-order-filter-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                min-height: 44px;
                border-radius: 12px;
                white-space: nowrap;
            }

            .delivery-order-page #active-branch-button-label {
                display: inline-block;
                max-width: 160px;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: middle;
            }

            .delivery-order-page .delivery-order-modal-note {
                border: 1px dashed var(--bs-gray-300);
                background: #fffaf3;
            }

            .delivery-order-page #kurir-table thead th {
                font-size: 11px;
                text-transform: uppercase;
                color: var(--bs-gray-600);
                letter-spacing: 0.04em;
                white-space: nowrap;
            }

            .delivery-order-page #kurir-table tbody td {
                vertical-align: middle;
            }

            .delivery-order-page #kurir-table .form-check {
                min-height: 0;
                margin-bottom: 0;
            }

            @media (max-width: 767.98px) {
                .delivery-order-page .delivery-order-filter-toolbar {
                    width: 100%;
                    justify-content: flex-start !important;
                }

                .delivery-order-page .delivery-order-filter-btn {
                    width: 100%;
                }

                .delivery-order-page .delivery-order-filter-menu {
                    width: min(100vw - 2rem, 360px) !important;
                    left: auto !important;
                    right: 0 !important;
                }

                .delivery-order-page .delivery-order-modal-footer {
                    flex-direction: column-reverse;
                    gap: 0.75rem;
                }

                .delivery-order-page .delivery-order-modal-footer .btn {
                    width: 100%;
                    margin: 0 !important;
                }

                .delivery-order-page .modal-dialog {
                    margin: 0.75rem;
                }

                .delivery-order-page #kurir-table thead th:first-child,
                .delivery-order-page #kurir-table tbody td:first-child {
                    width: 52px;
                }
            }
        </style>
        <div class="card card-flush shadow-sm">
            <!--begin::Card header-->
            <div class="card-header align-items-stretch py-4 gap-3 flex-column flex-md-row">
                <!--begin::Card title-->
                <div class="card-title flex-grow-1 min-w-0 me-md-2 w-100">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1 w-100">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" data-kt-ecommerce-product-filter="search" id="search"
                            class="form-control form-control-solid w-100 ps-12 h-45px rounded-4"
                            placeholder="Cari kiriman" />
                    </div>
                    <!--end::Search-->
                </div>
                <!--end::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar w-100 w-md-auto ms-md-auto">
                    <!--begin::Toolbar-->
                    <div class="d-flex align-items-center justify-content-md-end delivery-order-filter-toolbar"
                        data-kt-user-table-toolbar="base">
                        <!--begin::Filter-->
                        <button type="button" class="btn btn-light-primary px-4 delivery-order-filter-btn"
                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            <i class="ki-duotone ki-filter fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <span id="active-branch-button-label">Cabang</span>
                        </button>
                        <!--begin::Menu 1-->
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px delivery-order-filter-menu"
                            data-kt-menu="true">
                            <!--begin::Header-->
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Pilihan Filter</div>
                                <div class="fs-7 text-muted mt-1">Atur cabang, status, dan tanggal pengiriman.</div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Separator-->
                            <div class="separator border-gray-200"></div>
                            <!--end::Separator-->
                            <!--begin::Content-->
                            <div class="px-7 py-2" data-kt-user-table-filter="form">
                                <!--begin::Input group-->
                                <div>
                                    <label class="form-label fs-6 fw-semibold">Status:</label>
                                    @php
                                        $category = ['draft', 'paid', 'debt', 'canceled'];
                                    @endphp
                                    <select class="form-select form-select-solid" data-control="select2"
                                        data-hide-search="true" data-placeholder="Status"
                                        data-kt-ecommerce-product-filter="status">
                                        <option value="all">All</option>
                                        @foreach ($category as $category)
                                            <option value="{{ $category }}">{{ ucwords($category) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Content-->
                            <!--begin::Content-->
                            <div class="px-7 py-2" data-kt-user-table-filter="form">
                                <!--begin::Input group-->
                                <div>
                                    <label class="form-label fs-6 fw-semibold">Cabang:</label>
                                    <select class="form-select form-select-solid" data-control="select2"
                                        data-hide-search="true" data-placeholder="Cabang"
                                        data-kt-ecommerce-product-filter="cabang">
                                        @if (count($branches) > 1)
                                            <option value="all">All</option>
                                        @endif
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Content-->
                            <!--begin::Content-->
                            <div class="px-7 py-5" data-kt-user-table-filter="form">
                                <!--begin::Input group-->
                                <div class="input-group mw-350px">
                                    <input class="form-control form-control-solid rounded rounded-end-0"
                                        placeholder="Pilih range tanggal" id="kt_ecommerce_sales_flatpickr" />
                                    <button class="btn btn-icon btn-light" id="kt_ecommerce_sales_flatpickr_clear">
                                        <i class="ki-duotone ki-cross fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </button>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <!--end::Content-->
                        </div>
                        <!--end::Menu 1-->
                        <!--end::Filter-->

                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="pos-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="text-start min-w-150px">Nama Pelanggan</th>
                            <th class="text-start min-w-150px">Tanggal Pengiriman</th>
                            <th class="text-start min-w-150px">Nama Kurir</th>
                            <th class="text-start min-w-150px">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
    </div>
    <div class="modal fade" id="kt_modal_add_customer" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content rounded-4 shadow-sm">
                <!--begin::Form-->
                <form class="form" action="{{ url(Request::segment(1)) . '/update-courier' }}"
                    id="kt_modal_add_customer_form" data-kt-redirect="#">
                    @csrf
                    <!--begin::Modal header-->
                    <div class="modal-header border-0 pb-0" id="kt_modal_add_customer_header">
                        <!--begin::Modal title-->
                        <div>
                            <h2 class="fw-bold mb-1">Pilih Kurir</h2>
                            <div class="fs-7 text-muted">Tentukan staff yang bertugas menerima order pengiriman.</div>
                        </div>
                        <!--end::Modal title-->
                        <!--begin::Close-->
                        <div id="kt_modal_add_customer_close" class="btn btn-icon btn-sm btn-active-icon-primary">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                        <!--end::Close-->
                    </div>
                    <!--end::Modal header-->
                    <!--begin::Modal body-->
                    <div class="modal-body py-7 px-5 px-lg-10">
                        <div class="rounded-3 px-4 py-3 mb-5 delivery-order-modal-note">
                            <div class="fs-7 text-gray-700">Centang staff yang ingin dijadikan kurir aktif.</div>
                        </div>
                        <!--begin::Scroll-->
                        <div class="scroll-y me-lg-n5 pe-lg-5" id="kt_modal_add_customer_scroll" data-kt-scroll="true"
                            data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_customer_header"
                            data-kt-scroll-wrappers="#kt_modal_add_customer_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Input group-->
                            <div id="listKurir"></div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Scroll-->
                    </div>
                    <!--end::Modal body-->
                    <!--begin::Modal footer-->
                    <div class="modal-footer flex-center border-0 pt-0 delivery-order-modal-footer">
                        <!--begin::Button-->
                        <button type="reset" id="kt_modal_add_customer_cancel" class="btn btn-light me-3">Batal</button>
                        <!--end::Button-->
                        <!--begin::Button-->
                        <button type="submit" id="kt_modal_add_customer_submit" class="btn btn-primary">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                        <!--end::Button-->
                    </div>
                    <!--end::Modal footer-->
                </form>
                <!--end::Form-->
            </div>
        </div>
    </div>
    <a href="{{ url('pos/create') }}" class="btn btn-primary rounded-circle shadow-lg position-fixed"
        style="bottom: 60px; right: 30px; width: 60px; height: 60px; z-index: 1050; display: flex; align-items: center; justify-content: center;">
        <i class="ki-duotone ki-purchase fs-3x text-white">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </a>
@section('script')
    <script type="text/javascript">
        var dataTable;
        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="cabang"]');
            const $dateFilter = $('#kt_ecommerce_sales_flatpickr');
            const $activeBranchButtonLabel = $('#active-branch-button-label');
            const isMobileView = window.matchMedia('(max-width: 767.98px)').matches;

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            dataTable = $('#pos-table').DataTable({
                processing: true,
                serverSide: true,
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                columnDefs: [{
                    orderable: false,
                    targets: -1 // Disable sorting for action column
                }, ],
                ajax: {
                    url: "{{ route('delivery-order.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.status_filter = $('[data-kt-ecommerce-product-filter="status"]').val();
                        d.cabang_filter = $('[data-kt-ecommerce-product-filter="cabang"]').val();
                        var range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            var dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0]; // jika hanya pilih 1 tanggal
                        }
                    }
                },
                order: [
                    [4, 'desc'],

                ],
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'text-center'
                    },
                    {
                        data: 'courier',
                        name: 'courier'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    },
                ]
            });
            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('[data-kt-ecommerce-product-filter="status"]').on('change', function() {
                dataTable.draw(); // trigger fetch ulang dari server
            });

            $('[data-kt-ecommerce-product-filter="cabang"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw(); // trigger fetch ulang dari server
            });

            const salesFlatpickr = $("#kt_ecommerce_sales_flatpickr").flatpickr({
                altInput: !0,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                mode: "range",
                onChange: function(e, t, n) {
                    updateActiveFilterInfo();
                    dataTable.draw();
                }
            });

            $('#kt_ecommerce_sales_flatpickr_clear').on('click', function() {
                salesFlatpickr.clear(false);
                updateActiveFilterInfo();
                dataTable.draw();
            });

            updateActiveFilterInfo();
        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }

        function setSelesai(id) {
            Swal.fire({
                title: 'Barang diterima?',
                text: 'Data yang dirubah tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, diterima!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/delivery-order/set-selesai/${id}`, // Ganti dengan URL yang sesuai
                        type: 'PUT',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil diubah.',
                                showConfirmButton: false,
                                timer: 1500
                            });

                            // Reload DataTable setelah berhasil menghapus data
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat mengubah data.'
                            });
                        }
                    });
                }
            });
        }

        function setBayar(id) {
            Swal.fire({
                title: 'Barang diterima?',
                html: `
                    <p>Pemohon belum melakukan bayar, Harap masukkan nominal pembayaran:</p>
                    <label for="nominalBayar">Nominal Pembayaran:</label>
                    <input type="text" id="nominalBayar" class="swal2-input" placeholder="Masukkan nominal">
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, diterima!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
                didOpen: () => {
                    const input = document.getElementById('nominalBayar');
                    input.addEventListener('input', function() {
                        this.value = formatRupiah(this.value);
                    });
                },
                preConfirm: () => {
                    return document.getElementById('nominalBayar').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/delivery-order/set-selesai/${id}`,
                        type: 'PUT',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            nominal: result.value // ambil nominal yang sudah diformat
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil diubah.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat mengubah data.'
                            });
                        }
                    });
                }
            });
        }

        function formatRupiah(angka) {
            if (!angka) return '';
            angka = angka.toString().replace(/[^,\d]/g, '');
            const parts = angka.split(',');
            let sisa = parts[0].length % 3;
            let rupiah = parts[0].substr(0, sisa);
            let ribuan = parts[0].substr(sisa).match(/\d{3}/g);

            if (ribuan) {
                const separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = parts[1] !== undefined ? rupiah + ',' + parts[1] : rupiah;
            return rupiah;
        }


        $('#kt_modal_add_customer').on('show.bs.modal', function() {
            let targetDiv = $('#listKurir');
            targetDiv.html('<div class="text-center py-5">Loading...</div>');

            $.ajax({
                url: "{{ url('/delivery-order/get-courier') }}",
                type: "GET",
                success: function(data) {
                    targetDiv.html(data);

                    // hancurkan dulu kalau sebelumnya sudah ada
                    if ($.fn.DataTable.isDataTable('#kurir-table')) {
                        $('#kurir-table').DataTable().destroy();
                    }

                    // baru init ulang
                    $('#kurir-table').DataTable({
                        responsive: true,
                        searching: true,
                        paging: true,
                        lengthChange: false,
                        pageLength: isMobileView ? 5 : 10,
                        info: !isMobileView,
                        autoWidth: false
                    });
                },
                error: function() {
                    targetDiv.html('<div class="alert alert-danger">Gagal load data kurir.</div>');
                }
            });
        });

        document.getElementById('kt_modal_add_customer_cancel').addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah form reset langsung
            Swal.fire({
                text: "Apakah Anda yakin ingin membatalkan?",
                icon: "warning",
                showCancelButton: !0,
                buttonsStyling: !1,
                confirmButtonText: "Ya, Batalkan!",
                cancelButtonText: "Tidak, Kembali",
                customClass: {
                    confirmButton: "btn btn-primary",
                    cancelButton: "btn btn-active-light"
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tutup modal manual
                    const modal = bootstrap.Modal.getInstance(document.getElementById(
                        'kt_modal_add_customer'));
                    modal.hide();
                    document.getElementById('kt_modal_add_customer_form').reset();
                }
            });
        });

        $('#kt_modal_add_customer_form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var submitBtn = $('#kt_modal_add_customer_submit');

            // Show loading
            submitBtn.prop('disabled', true);
            submitBtn.find('.indicator-label').hide();
            submitBtn.find('.indicator-progress').show();

            $.ajax({
                type: 'POST',
                url: url,
                data: form.serialize(), // gunakan FormData(form)[... jika pakai file]
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message || 'Data berhasil disimpan.',
                        showConfirmButton: false,
                        timer: 1500 // notifikasi akan hilang otomatis setelah 1.5 detik
                    }).then(() => {
                        // 1. Reset form

                        const modal = bootstrap.Modal.getInstance(document
                            .getElementById('kt_modal_add_customer'));
                        if (modal) modal.hide();

                        // 6. Refresh DataTable
                        if (typeof dataTable !== 'undefined') {
                            dataTable.ajax.reload(null, false);
                        }
                    });
                },
                error: function(xhr) {
                    let errorText = 'Terjadi kesalahan.';

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Menggabungkan semua pesan error dalam <ul>
                        const errors = xhr.responseJSON.errors;
                        errorText = '<ul>';
                        for (const key in errors) {
                            if (errors.hasOwnProperty(key)) {
                                errors[key].forEach(function(msg) {
                                    errorText += `<li>${msg}</li>`;
                                });
                            }
                        }
                        errorText += '</ul>';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorText = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        html: errorText // gunakan html, bukan text
                    });
                },
                complete: function() {
                    // Reset loading state
                    submitBtn.prop('disabled', false);
                    submitBtn.find('.indicator-label').show();
                    submitBtn.find('.indicator-progress').hide();
                }
            });
        });
    </script>
@endsection
@endsection
