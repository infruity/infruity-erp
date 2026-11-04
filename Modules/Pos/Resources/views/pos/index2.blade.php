@extends('template.root')
@section('title', 'Kasir / POS')

@section('content')
<style>
    .pos-list-row { display:flex; min-width:0; align-items:center; gap:12px; }
    .pos-list-avatar { display:flex; width:40px; height:40px; flex:none; align-items:center; justify-content:center; border:1px solid #d1fae5; border-radius:12px; background:#ecfdf5; color:#0b595b; font-size:19px; }
    .pos-list-copy { display:flex; min-width:0; flex-direction:column; gap:2px; text-decoration:none; }
    .pos-list-customer { display:flex; min-width:0; align-items:center; gap:6px; color:#1f2937; font-size:14px; font-weight:700; }
    .pos-list-customer span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .pos-list-copy:hover .pos-list-customer { color:#0b595b; }
    .pos-list-parcel { flex:none; color:#0b595b; font-size:15px; }
    .pos-list-sub { color:#6b7280; font-size:12px; font-variant-numeric:tabular-nums; }
    .pos-list-status-cell { display:flex; flex-direction:column; align-items:center; gap:3px; font-variant-numeric:tabular-nums; }
    .pos-list-status { display:inline-flex; align-items:center; border-radius:999px; padding:3px 9px; font-size:11px; font-weight:700; }
    .pos-list-status.is-paid { background:#f0fdf4; color:#166534; }
    .pos-list-status.is-pending { background:#f3f4f6; color:#6b7280; }
    .pos-list-status.is-debt { background:#fff1f2; color:#be123c; }
    .pos-list-status.is-canceled { background:#e5e7eb; color:#1f2937; }
    .pos-list-status.is-other { background:#fffbeb; color:#b45309; }
    .pos-list-total { color:#111827; font-size:13px; font-weight:700; }
    .pos-list-due { color:#be123c; font-size:11px; }
    .pos-list-meta { color:#9ca3af; font-size:11px; }
    #pos-table tbody tr.is-openable { cursor:pointer; }
    .pos-list-empty { display:flex; min-height:260px; flex-direction:column; align-items:center; justify-content:center; gap:8px; text-align:center; }
    .pos-list-empty i { display:flex; width:56px; height:56px; align-items:center; justify-content:center; border:1px solid #d1fae5; border-radius:20px; background:#ecfdf5; color:#0b595b; font-size:26px; }
    .pos-list-empty strong { color:#1f2937; font-size:14px; }
    .pos-list-empty span { max-width:280px; color:#6b7280; font-size:12px; line-height:1.55; }
    @media (max-width:1279px) {
        #pos-table, #pos-table tbody { display:block; width:100% !important; }
        #pos-table thead { display:none; }
        #pos-table tbody tr { display:grid; grid-template-columns:minmax(0,1fr) auto 36px; align-items:center; gap:4px 12px; padding:12px 16px; border-bottom:1px solid #f1f3f5; }
        #pos-table tbody td { display:block; width:auto !important; border:0 !important; padding:0 !important; }
        #pos-table tbody td:nth-child(2) { text-align:right; }
        #pos-table .pos-list-status-cell { align-items:flex-end; }
        #pos-table .pos-list-status { padding:0; background:transparent !important; font-size:12px; }
        #pos-table .pos-list-status.is-paid { color:#0b595b; }
        #pos-table .pos-list-status.is-pending { color:#9ca3af; }
        #pos-table .pos-list-status.is-debt { color:#be123c; }
        #pos-table .pos-list-meta { display:none; }
        #pos-table_wrapper .dataTables_info, #pos-table_wrapper .dataTables_paginate { display:none !important; }
        #pos-table td.dataTables_empty { grid-column:1 / -1; }
    }
    @media (max-width:767px) {
        .pos-list-avatar { width:36px; height:36px; border-radius:11px; font-size:17px; }
        .pos-list-customer { font-size:13px; }
        .pos-list-sub { font-size:11px; }
        .pos-list-total { font-size:12px; }
    }
</style>
<x-erp.workspace title="Kasir / POS" subtitle="Kelola kasir / pos dan lihat rincian transaksi."><x-slot:toolbar><div class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" data-kt-ecommerce-product-filter="search" aria-label="Cari kasir / pos" placeholder="Cari kasir / pos…"></div><button type="button" class="erp-action" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="active-branch-button-label">Filter</span></button><div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                            <!--begin::Header-->
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Pilihan Filter</div>
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
                                        $statuses = [
                                            ['value' => 'all', 'label' => 'Semua', 'color' => ''],
                                            ['value' => 'draft', 'label' => 'Pending', 'color' => 'gray'],
                                            ['value' => 'paid', 'label' => 'Lunas', 'color' => 'success'],
                                            ['value' => 'debt', 'label' => 'Piutang', 'color' => 'danger'],
                                            ['value' => 'canceled', 'label' => 'Dihapus', 'color' => 'dark'],
                                        ];
                                    @endphp
                                    <select class="form-select form-select-solid" data-control="select2"
                                        data-hide-search="true" data-placeholder="Status"
                                        data-kt-ecommerce-product-filter="status">
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
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
                        </div>@if(check_access('pos.create'))<a href="{{ url('pos/create') }}" class="erp-action erp-action--primary"><i class="ph-bold ph-plus" aria-hidden="true"></i>Tambah Kasir / POS</a>@endif</x-slot:toolbar><div class="pos-list"><table class="table align-middle table-row-dashed fs-6 gy-5" id="pos-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="text-start min-w-150px">Name</th>
                            <th class="text-start min-w-150px">Date</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table></div></x-erp.workspace>

@section('script')
    <script type="text/javascript">
        // Data stock kosong dari server
        const emptyStockData = @json($emptyStockData ?? []);
        let hasShownInitialAlert = false;

        function showStockAlert(selectedBranchId) {
            let filteredProducts = emptyStockData;
            if (selectedBranchId && selectedBranchId !== 'all') {
                filteredProducts = emptyStockData.filter(p => p.branch_id == selectedBranchId);
            }

            if (filteredProducts.length > 0) {
                const count = filteredProducts.length;
                const badges = filteredProducts.slice(0, 10).map(p => {
                    return `<span class="badge badge-light-danger me-1 mb-1">${p.name} (${p.branch_name})</span>`;
                }).join('');

                const remainingHtml = count > 10 ?
                    `<br><small>...dan ${count - 10} produk lainnya</small>` :
                    '';

                const scopeText = selectedBranchId === 'all' ? 'pada seluruh cabang' : 'pada cabang ini';
                const alertHtml = `Terdapat <strong>${count}</strong> produk dengan stok kosong ${scopeText}.<br><br>` +
                    `<div style="text-align: left; max-height: 200px; overflow-y: auto;">` +
                    badges +
                    remainingHtml +
                    `</div>`;

                Swal.fire({
                    title: 'Peringatan Stok Minus!',
                    html: alertHtml,
                    icon: 'warning',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#b45309',
                    allowOutsideClick: false
                });
            }
        }

        @if (session('error'))
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Bisa Diedit',
                    text: "{{ session('error') }}"
                });
            });
        @endif

        var dataTable;
        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="cabang"]');
            const $activeBranchButtonLabel = $('#active-branch-button-label');

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            const posEmptyState = '<div class="pos-list-empty"><i class="ph ph-receipt" aria-hidden="true"></i><strong>Belum ada transaksi</strong><span>Transaksi kasir yang tersimpan akan muncul di sini.</span></div>';

            dataTable = $('#pos-table').DataTable({
                language: { emptyTable: posEmptyState, zeroRecords: posEmptyState },
                createdRow: function(row) {
                    const link = row.querySelector('a.pos-list-copy');
                    if (link) {
                        row.classList.add('is-openable');
                        row.dataset.href = link.getAttribute('href');
                    }
                },
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
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
                    url: "{{ route('pos-data') }}",
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
                    [2, 'desc'], // Then by order_date ASC (kolom ke-3)
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
                        data: 'action',
                        name: 'action'
                    },
                ]
            });

            // Fix dropdown z-index in fixed columns
            $('body').on('show.bs.dropdown', '.dropstart', function(e) {
                $(e.target).closest('td').css('z-index', '1050');
            }).on('hide.bs.dropdown', '.dropstart', function(e) {
                $(e.target).closest('td').css('z-index', '');
            });
            // A tap on the row opens the existing detail page; row actions keep their own behaviour.
            $('#pos-table tbody').on('click', 'tr.is-openable', function(event) {
                if ($(event.target).closest('a, button, .dropdown, .dropdown-menu').length) return;
                window.location.href = this.dataset.href;
            });

            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('[data-kt-ecommerce-product-filter="status"]').on('change', function() {
                dataTable.draw(); // trigger fetch ulang dari server
            });

            function formatStatusOption(state) {
                if (!state.id) return state.text;
                var statusColors = {
                    'Semua': '',
                    'Pending': '#6c757d',
                    'Lunas': '#198754',
                    'Piutang': '#dc3545',
                    'Dihapus': '#1f1f1f'
                };
                var color = statusColors[state.text] || '';
                if (color) {
                    return $('<span><span class="badge badge-light-' + (state.text === 'Lunas' ? 'success' : state
                            .text === 'Piutang' ? 'danger' : state.text === 'Pending' ? 'secondary' : 'dark') +
                        '">' + state.text + '</span></span>');
                }
                return state.text;
            }

            $('[data-kt-ecommerce-product-filter="status"]').select2({
                templateResult: formatStatusOption
            });
            $('[data-kt-ecommerce-product-filter="cabang"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw(); // trigger fetch ulang dari server
                showStockAlert($(this).val());
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
            showStockAlert($branchFilter.val());
        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }

        function deleteProduct(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang dihapus tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/pos/${id}`, // Ganti dengan URL yang sesuai
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil dihapus.',
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
                                    'Terjadi kesalahan saat menghapus data.'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection
@endsection
