@extends('template.root')

@section('content')
    {{-- @livewire('product-table') --}}
    <div>
        <div class="card card-flush">
            <!--begin::Card header-->
            <div class="card-header align-items-center py-4 px-4 px-sm-6 border-bottom d-flex flex-row gap-3 gap-sm-4 flex-nowrap" style="min-height: auto;">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative flex-root">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4 text-gray-400"></i>
                    <input type="text" data-kt-ecommerce-product-filter="search" id="search"
                        class="form-control form-control-solid rounded-pill ps-12 w-100" placeholder="Cari transaksi..." style="background-color: #f9f9f9; border: 1px solid #f0f0f0; height: 44px; font-size: 13px;" />
                </div>
                <!--end::Search-->
                
                <!--begin::Filter-->
                <div class="position-relative flex-shrink-0">
                    <button type="button" class="btn btn-icon btn-bg-light btn-active-light-primary rounded-circle border border-gray-200" 
                        style="width: 44px; height: 44px; background-color: #ffffff;"
                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        <div class="position-relative d-flex align-items-center justify-content-center">
                            <i class="ki-outline ki-filter fs-2 text-gray-600"></i>
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-white border-2 rounded-circle" style="margin-top: 4px; margin-left: -8px;">
                                <span class="visually-hidden">New alerts</span>
                            </span>
                        </div>
                    </button>
                    <!--begin::Menu 1-->
                    <div class="menu menu-sub menu-sub-dropdown w-300px p-4 shadow-lg" data-kt-menu="true" style="border-radius: 1rem; border: 1px solid #f4f4f4;">
                        <div class="d-flex flex-column gap-4 w-100">
                            
                            <!-- Grouping Field -->
                            <div>
                                <label class="d-block fw-bold text-muted text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.05em;">Grup Berdasarkan</label>
                                <div class="d-flex flex-row gap-2">
                                    <div class="flex-grow-1 py-2 px-2 rounded d-flex align-items-center justify-content-center gap-1 cursor-pointer" style="font-size: 11px; font-weight: 600; color: #047857; background-color: #ecfdf5; border: 1px solid #a7f3d0; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
                                        <i class="ki-solid ki-abstract-26 fs-6 text-success"></i> Produk
                                    </div>
                                    <div class="flex-grow-1 py-2 px-2 rounded d-flex align-items-center justify-content-center gap-1 cursor-pointer" style="font-size: 11px; font-weight: 600; color: #6b7280; background-color: #f9fafb; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
                                        <i class="ki-outline ki-calendar fs-6 text-muted"></i> Tanggal
                                    </div>
                                </div>
                                <div class="mt-2 position-relative w-100">
                                    <select class="form-select form-select-solid rounded bg-white text-dark fw-semibold" data-control="select2" data-hide-search="true" style="font-size: 12px; height: 38px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
                                        <option value="harian">Harian</option>
                                        <option value="mingguan">Mingguan</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Cabang Field -->
                            <div>
                                <label class="d-block fw-bold text-muted text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.05em;">Cabang</label>
                                <select class="form-select form-select-solid rounded" id="branch-filter" data-control="select2" data-hide-search="true" data-placeholder="Semua Cabang" style="font-size: 12px; height: 38px; border: 1px solid #e5e7eb; background-color: white;">
                                    <option value="all">Semua Cabang</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Periode Tanggal -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="d-block fw-bold text-muted text-uppercase mb-0" style="font-size: 10px; letter-spacing: 0.05em;">Periode Tanggal</label>
                                    <a href="#" class="text-danger fw-semibold text-decoration-none" style="font-size: 9px;" id="reset-date-btn">Reset</a>
                                </div>
                                
                                <div class="w-100 d-flex flex-column gap-2" id="kt_ecommerce_sales_flatpickr_custom">
                                    <div class="d-flex align-items-center gap-2 bg-white rounded px-2 py-2 w-100 cursor-pointer flatpickr-trigger" style="border: 1px solid #e5e7eb; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
                                        <span class="text-muted fw-semibold" style="font-size: 10px; width: 32px;">DARI</span>
                                        <input type="text" class="form-control form-control-flush p-0 fw-semibold text-dark h-auto cursor-pointer" id="date-start" placeholder="Pilih Tanggal" style="font-size: 11px;" readonly />
                                    </div>
                                    <div class="d-flex align-items-center gap-2 bg-white rounded px-2 py-2 w-100 cursor-pointer flatpickr-trigger" style="border: 1px solid #e5e7eb; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
                                        <span class="text-muted fw-semibold" style="font-size: 10px; width: 32px;">KE</span>
                                        <input type="text" class="form-control form-control-flush p-0 fw-semibold text-dark h-auto cursor-pointer" id="date-end" placeholder="Pilih Tanggal" style="font-size: 11px;" readonly />
                                    </div>
                                    <!-- Hidden input for Flatpickr -->
                                    <input type="text" class="d-none" style="display: none !important;" id="kt_ecommerce_sales_flatpickr" />
                                </div>
                            </div>

                            <!-- Filter Action Buttons -->
                            <div class="pt-3 mt-1 border-top d-flex flex-column gap-2">
                                <div class="d-flex flex-row gap-2">
                                    <button class="flex-grow-1 py-2 px-1 rounded d-flex align-items-center justify-content-center gap-1 border-0" style="font-size: 11px; font-weight: 600; background-color: #c5f037; color: #1f2937;" id="btn-minggu-ini">
                                        Minggu Ini
                                    </button>
                                    <button class="flex-grow-1 py-2 px-1 rounded d-flex align-items-center justify-content-center gap-1" style="font-size: 11px; font-weight: 600; background-color: #ffffff; color: #374151; border: 1px solid #e5e7eb;" id="btn-hari-ini">
                                        Hari Ini
                                    </button>
                                </div>
                                <button class="w-100 py-2 px-1 rounded d-flex align-items-center justify-content-center gap-1 border-0" style="font-size: 11px; font-weight: 600; background-color: #fef2f2; color: #ef4444;" id="btn-reset-semua">
                                    Reset Semua
                                </button>
                            </div>
                        </div>
                    </div>
                    <!--end::Menu 1-->
                </div>
                <!--end::Filter-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0 px-3 px-md-6" style="display: flex; flex-direction: column;">
                <!-- Title removed as per user request -->
                <div class="d-flex flex-column w-100 flex-grow-1" style="height: calc(100dvh - 200px); min-height: 300px; overflow-y: auto; overflow-x: hidden;" id="transaction-scroll-wrapper">
                    <div id="transaction-list-container" class="d-flex flex-column w-100"></div>
                    <div id="lazy-loader" class="text-center py-4 d-none">
                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                    </div>
                </div>
                
                <div class="position-sticky bg-white border-top shadow-sm" style="bottom: 0; z-index: 99; padding: 1.25rem 1.5rem; margin-bottom: -2.25rem; border-bottom-left-radius: 0.475rem; border-bottom-right-radius: 0.475rem;" id="desktop-total-bar">
                    <div class="d-flex flex-column w-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-gray-500 fw-medium fs-6">Total Pendapatan</span>
                            <span class="text-gray-800 fs-5" id="grand-total-pendapatan">Rp 1.200.000</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pb-4 mb-4" style="border-bottom: 1px solid #f0f0f0;">
                            <span class="text-gray-500 fw-medium fs-6">Total HPP</span>
                            <span class="text-danger fs-5" id="grand-total-hpp">- Rp 560.000</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <span class="fs-4 fw-bolder text-gray-900 text-uppercase">TOTAL (LABA KOTOR)</span>
                                <span class="badge fw-bold px-2 py-1" id="grand-laba-percentage" style="background-color: #d1fae5; color: #047857; font-size: 11px; border-radius: 6px;">53,3%</span>
                            </div>
                            <span id="grand-total-laba" class="fs-3 fw-bolder" style="color: #047857;">Rp 640.000</span>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Card body-->
        </div>
    </div>

<!-- Drawer Riwayat Transaksi -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="history-drawer" style="width: 400px; border-left: none; box-shadow: -4px 0 15px rgba(0,0,0,0.05);">
    <div class="offcanvas-header pb-2 d-flex flex-column align-items-start border-bottom">
        <div class="d-flex justify-content-between w-100 align-items-center mb-1">
            <h5 class="offcanvas-title fw-bolder text-gray-900 fs-4">Riwayat Transaksi</h5>
            <button type="button" class="btn-close btn-sm btn-light rounded-circle" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <span class="text-muted fw-medium" id="history-product-name" style="font-size: 13px;"></span>
        
        <!-- Search and Filter in drawer -->
        <div class="d-flex align-items-center mt-3 mb-2 w-100 gap-2">
            <div class="position-relative flex-grow-1">
                <i class="ki-outline ki-magnifier fs-5 position-absolute ms-3 top-50 translate-middle-y text-gray-400"></i>
                <input type="text" class="form-control form-control-solid rounded-pill ps-10" id="search-history" placeholder="Cari transaksi..." style="background-color: #f9f9f9; border: 1px solid #f0f0f0; height: 42px; font-size: 12px;">
            </div>
            
            <div class="dropdown" id="history-filter-dropdown-container">
                <button class="btn btn-icon btn-light rounded-circle border border-gray-200 shadow-sm flex-shrink-0" type="button" id="historyFilterBtn" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside" style="width: 42px; height: 42px; background-color: white;">
                    <div class="position-relative d-flex align-items-center justify-content-center" id="historyFilterIconContainer">
                        <i class="ki-outline ki-filter fs-4 text-gray-600" id="historyFilterIcon"></i>
                        <span class="position-absolute bg-warning rounded-circle border border-white d-none" id="historyFilterIndicator" style="width: 8px; height: 8px; top: -2px; right: -2px;"></span>
                    </div>
                </button>
                
                <div class="dropdown-menu dropdown-menu-end p-4 border-gray-100 shadow-lg" aria-labelledby="historyFilterBtn" style="width: 260px; border-radius: 1rem; margin-top: 10px !important;">
                    <!-- TIPE PEMBAYARAN -->
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.05em;">Tipe Pembayaran</label>
                        <select class="form-select form-select-sm form-select-solid" id="historyPaymentFilter" style="border-radius: 8px; font-size: 12px;">
                            <option value="Semua Metode">Semua Metode</option>
                            <option value="Tunai">Tunai</option>
                            <option value="QRIS">QRIS</option>
                            <option value="Transfer">Transfer</option>
                            <option value="Split">Split (Tunai & QRIS)</option>
                        </select>
                    </div>
                    
                    <!-- TANGGAL -->
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold text-uppercase mb-2" style="font-size: 10px; letter-spacing: 0.05em;">Tanggal</label>
                        <input type="text" class="form-control form-control-sm form-control-solid" id="historyDateFilter" placeholder="Pilih Tanggal" style="border-radius: 8px; font-size: 12px; cursor: pointer; background-color: #fff; border: 1px solid #e4e6ef;" readonly>
                    </div>
                    
                    <!-- ACTION BUTTONS -->
                    <div class="d-flex gap-2 pt-3 border-top border-gray-100">
                        <button class="btn btn-sm btn-light-primary flex-grow-1 fw-bold border border-gray-200" id="historyBtnHariIni" style="font-size: 11px; background-color: white; color: #4b5563;">Hari Ini</button>
                        <button class="btn btn-sm flex-grow-1 fw-bold" id="historyBtnReset" style="font-size: 11px; background-color: #fef2f2; color: #ef4444;">Reset Semua</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="offcanvas-body position-relative" style="background-color: #f9fafb;" id="history-content">
        <!-- Loader -->
        <div class="position-absolute top-50 start-50 translate-middle d-none" id="history-loader">
            <span class="spinner-border text-primary" role="status"></span>
        </div>
        <!-- Empty State -->
        <div class="text-center py-10 d-none" id="history-empty">
            <span class="text-muted fs-6">Tidak ada transaksi</span>
        </div>
        <!-- List container -->
        <div class="d-flex flex-column gap-4" id="history-list">
        </div>
    </div>
</div>

@section('script')
    <style>
        @media (max-width: 991.98px) {
            #desktop-total-bar {
                margin-left: -0.75rem !important;
                margin-right: -0.75rem !important;
            }
            #transaction-scroll-wrapper {
                padding-bottom: 20px !important;
            }
        }
        @media (min-width: 992px) {
            #desktop-total-bar {
                margin-left: -1.5rem !important;
                margin-right: -1.5rem !important;
            }
        }
    </style>
    <script type="text/javascript">
        var dataTable;
        const defaultDate = @json($defaultDate ?? date('Y-m-d'));
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const segment1 = "{{ Request::segment(1) }}";

        $(document).ready(function() {
            let currentStart = 0;
            const pageLength = 100;
            let isLoading = false;
            let hasMore = true;

            function loadTransactions(reset = false) {
                if (reset) {
                    currentStart = 0;
                    hasMore = true;
                    $('#transaction-list-container').empty();
                }
                if (isLoading || !hasMore) return;
                
                isLoading = true;
                $('#lazy-loader').removeClass('d-none');
                
                let d = {
                    draw: 1,
                    start: currentStart,
                    length: pageLength,
                    search: { value: $('#search').val() },
                    branch_id: $('#branch-filter').val()
                };
                let range = $('#kt_ecommerce_sales_flatpickr').val();
                if (range) {
                    let dates = range.split(' to ');
                    d.start_date = dates[0];
                    d.end_date = dates[1] ?? dates[0];
                }
                
                $.ajax({
                    url: "{{ route('report-profit-revenue.data') }}",
                    data: d,
                    success: function(json) {
                        $('#lazy-loader').addClass('d-none');
                        
                        if (json.grand_total_pendapatan) {
                            $('#grand-total-pendapatan').html(json.grand_total_pendapatan);
                            $('#grand-total-hpp').html(json.grand_total_hpp);
                            $('#grand-total-laba').html(json.grand_total_laba);
                            $('#grand-laba-percentage').html(json.laba_percentage);
                        } else if (reset) {
                            $('#grand-total-pendapatan').html('Rp 0');
                            $('#grand-total-hpp').html('- Rp 0');
                            $('#grand-total-laba').html('Rp 0');
                            $('#grand-laba-percentage').html('0%');
                        }
                        
                        if (json.data && json.data.length > 0) {
                            let html = '';
                            json.data.forEach(function(row) {
                                let unit = row.unit ? row.unit : 'pcs';
                                html += `
                                    <div class="custom-list-item w-100 cursor-pointer history-trigger px-3 py-4 mb-2 bg-white" style="border-bottom: 1px solid #f4f4f4;" data-product-id="${row.product_id}">
                                        <div class="row align-items-center w-100 m-0">
                                            <div class="col-5 p-0">
                                                <div class="fw-bold text-gray-900 text-truncate" style="font-size: 13px;">${row.name}</div>
                                            </div>
                                            <div class="col-7 p-0 d-flex justify-content-end gap-3 gap-sm-4 text-end">
                                                <div class="d-none d-md-flex flex-column align-items-end">
                                                    <span class="text-gray-400 text-uppercase d-block mb-1" style="font-size: 9px; letter-spacing: 0.05em;">Pendapatan</span>
                                                    <span class="text-gray-600 fw-medium" style="font-size: 11px;">${row.pendapatan_formatted}</span>
                                                </div>
                                                <div class="d-none d-md-flex flex-column align-items-end" style="min-width: 80px;">
                                                    <span class="text-gray-400 text-uppercase d-block mb-1" style="font-size: 9px; letter-spacing: 0.05em;">HPP</span>
                                                    <span class="text-danger fw-medium" style="font-size: 11px;">- ${row.hpp_formatted}</span>
                                                </div>
                                                <div class="d-flex flex-column align-items-end" style="min-width: 85px;">
                                                    <span class="text-gray-400 text-uppercase d-block mb-1" style="font-size: 9px; letter-spacing: 0.05em;">Laba Kotor</span>
                                                    <span class="text-success fw-bold" style="font-size: 12px;">${row.laba_formatted}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            $('#transaction-list-container').append(html);
                            currentStart += pageLength;
                            
                            if (json.data.length < pageLength) {
                                hasMore = false;
                            }
                        } else {
                            hasMore = false;
                            if (reset) {
                                $('#transaction-list-container').html('<div class="text-center py-10 text-muted">Tidak ada data penjualan</div>');
                            }
                        }
                        isLoading = false;
                    },
                    error: function() {
                        $('#lazy-loader').addClass('d-none');
                        isLoading = false;
                    }
                });
            }

            // Load initial data
            loadTransactions(true);

            // Infinite Scroll on container
            $('#transaction-scroll-wrapper').on('scroll', function() {
                if ($(this).scrollTop() + $(this).innerHeight() >= this.scrollHeight - 20) {
                    loadTransactions(false);
                }
            });

            // ✅ reload data saat filter diubah
            $('#branch-filter, #kt_ecommerce_sales_flatpickr').on('change', function() {
                loadTransactions(true);
            });

            // Search manual lewat input
            let searchTimeout;
            $('#search').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    loadTransactions(true);
                }, 500);
            });

            // Action: open drawer when clicking item
            $('#transaction-list-container').on('click', '.history-trigger', function() {
                const productId = $(this).data('product-id');
                const productName = $(this).find('.text-truncate').text();
                
                $('#history-product-name').text(productName);
                $('#history-list').empty();
                $('#history-empty').addClass('d-none');
                $('#history-loader').removeClass('d-none');
                
                let drawer = bootstrap.Offcanvas.getInstance(document.getElementById('history-drawer'));
                if (!drawer) {
                    drawer = new bootstrap.Offcanvas(document.getElementById('history-drawer'));
                }
                drawer.show();

                // Fetch data
                const branch = $('#branch-filter').val();
                let dates = $('#kt_ecommerce_sales_flatpickr').val();
                let startDate = '', endDate = '';
                if (dates) {
                    const splitted = dates.split(' to ');
                    startDate = splitted[0];
                    endDate = splitted.length > 1 ? splitted[1] : splitted[0];
                }

                // Reset drawer filter state
                $('#historyPaymentFilter').val('Semua Metode');
                if (window.historyDateFlatpickr) {
                    window.historyDateFlatpickr.clear();
                }
                updateHistoryFilterUI();

                $.ajax({
                    url: "{{ route('report-profit-revenue.history') }}",
                    data: {
                        product_id: productId,
                        branch: branch,
                        start_date: startDate,
                        end_date: endDate
                    },
                    success: function(res) {
                        $('#history-loader').addClass('d-none');
                        if (res.data && res.data.length > 0) {
                            let html = '';
                            res.data.forEach(item => {
                                const dataDate = item.pos_date ? item.pos_date : (item.tx_date ? item.tx_date.substring(0, 10) : '');
                                
                                html += `
                                <div class="position-relative bg-white border border-gray-200 p-4 history-item-card cursor-pointer" onclick="window.location.href='/pos/show/${item.pos_id}'" data-date="${dataDate}" data-payment="${item.payment}" style="border-radius: 0.5rem 0.5rem 0.75rem 0.75rem; box-shadow: 0 2px 10px -3px rgba(0,0,0,0.08); transition: all 0.2s;" onmouseover="this.style.boxShadow='0 4px 15px -3px rgba(0,0,0,0.15)'; this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='0 2px 10px -3px rgba(0,0,0,0.08)'; this.style.transform='translateY(0)'">
                                    <div class="position-absolute bg-light rounded-circle border-end border-gray-200" style="width: 16px; height: 16px; left: -8px; top: 55%;"></div>
                                    <div class="position-absolute bg-light rounded-circle border-start border-gray-200" style="width: 16px; height: 16px; right: -8px; top: 55%;"></div>
                                    
                                    <div class="d-flex justify-content-between align-items-center pb-3 mb-3" style="border-bottom: 2px dashed #e4e6ef;">
                                        <div class="d-flex flex-column">
                                            <span class="text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.05em;">No. Nota</span>
                                            <span class="fw-bolder text-gray-900 fs-6 mt-1">${item.invoice}</span>
                                        </div>
                                        <div class="d-flex flex-column text-end">
                                            <span class="fw-bold text-gray-900" style="font-size: 11px;">${item.date_formatted}</span>
                                            <span class="text-muted fw-medium" style="font-size: 10px; margin-top: 2px;">${item.time_formatted}</span>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <div class="fw-bold text-gray-700 mb-2 text-truncate" style="font-size: 11px;">${item.branch_name}</div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-gray-500" style="font-size: 11px;">Jumlah Terjual</span>
                                            <span class="text-gray-800 fw-medium" style="font-size: 11px;">${item.qty}</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-gray-500" style="font-size: 11px;">Harga Jual Satuan</span>
                                            <span class="text-gray-800 fw-medium" style="font-size: 11px;">${item.harga_satuan}</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-gray-500" style="font-size: 11px;">Penjualan Kotor</span>
                                            <span class="text-gray-800 fw-medium" style="font-size: 11px;">${item.penjualan_kotor}</span>
                                        </div>
                                        
                                        <div style="border-top: 1px solid #f0f0f0; margin: 12px 0;"></div>
                                        
                                        <div class="d-flex justify-content-between align-items-center my-3">
                                            <span class="text-gray-800 fw-bolder" style="font-size: 12px;">Pendapatan Bersih</span>
                                            <span class="text-gray-800 fw-bolder" style="font-size: 12px;">${item.pendapatan_bersih}</span>
                                        </div>
                                        
                                        <div style="border-top: 2px dashed #f0f0f0; margin: 12px 0;"></div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-gray-500" style="font-size: 11px;">HPP Satuan</span>
                                            <span class="text-gray-800 fw-medium" style="font-size: 11px;">${item.hpp_satuan}</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-gray-500" style="font-size: 11px;">Total HPP</span>
                                            <span class="text-danger fw-medium" style="font-size: 11px;">${item.total_hpp}</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-end pt-3 mt-3 border-top border-gray-100">
                                            <span class="fw-bold text-gray-900 text-uppercase" style="font-size: 11px; letter-spacing: 0.05em;">Total Laba Kotor</span>
                                            <span class="fw-bolder text-success" style="font-size: 16px; line-height: 1; color: #047857 !important;">${item.laba_kotor}</span>
                                        </div>
                                    </div>
                                </div>`;
                            });
                            $('#history-list').html(html);
                        } else {
                            $('#history-empty').removeClass('d-none');
                        }
                    },
                    error: function() {
                        $('#history-loader').addClass('d-none');
                        $('#history-empty').removeClass('d-none');
                    }
                });
            });

            $('#search-history').on('keyup', function() {
                applyHistoryFilter();
            });

            // Initialize History Drawer Datepicker
            window.historyDateFlatpickr = flatpickr("#historyDateFilter", {
                dateFormat: "Y-m-d",
                onChange: function(selectedDates, dateStr, instance) {
                    updateHistoryFilterUI();
                    applyHistoryFilter();
                }
            });

            $('#historyPaymentFilter').on('change', function() {
                updateHistoryFilterUI();
                applyHistoryFilter();
            });

            $('#historyBtnHariIni').on('click', function() {
                window.historyDateFlatpickr.setDate(new Date());
                updateHistoryFilterUI();
                applyHistoryFilter();
            });

            $('#historyBtnReset').on('click', function() {
                window.historyDateFlatpickr.clear();
                $('#historyPaymentFilter').val('Semua Metode');
                updateHistoryFilterUI();
                applyHistoryFilter();
            });

            function updateHistoryFilterUI() {
                const dateStr = $('#historyDateFilter').val();
                const payment = $('#historyPaymentFilter').val();
                
                if (dateStr || payment !== 'Semua Metode') {
                    $('#historyFilterIndicator').removeClass('d-none');
                    $('#historyFilterBtn').addClass('border-success').removeClass('border-gray-200');
                    $('#historyFilterIcon').addClass('text-success').removeClass('text-gray-600');
                } else {
                    $('#historyFilterIndicator').addClass('d-none');
                    $('#historyFilterBtn').removeClass('border-success').addClass('border-gray-200');
                    $('#historyFilterIcon').removeClass('text-success').addClass('text-gray-600');
                }
            }

            function applyHistoryFilter() {
                const term = $('#search-history').val().toLowerCase();
                const dateFilter = $('#historyDateFilter').val();
                let paymentFilter = $('#historyPaymentFilter').val();
                
                let hasVisible = false;
                
                $('#history-list > .history-item-card').each(function() {
                    const cardDate = $(this).attr('data-date');
                    const cardPayment = $(this).attr('data-payment');
                    const content = $(this).text().toLowerCase();
                    
                    let match = true;
                    
                    if (term && !content.includes(term)) {
                        match = false;
                    }
                    
                    if (dateFilter && cardDate !== dateFilter) {
                        match = false;
                    }
                    
                    if (paymentFilter !== 'Semua Metode') {
                        if (paymentFilter === 'Split') {
                            if (!cardPayment.includes('Split')) match = false;
                        } else if (paymentFilter !== cardPayment) {
                            match = false;
                        }
                    }
                    
                    if (match) {
                        $(this).removeClass('d-none');
                        hasVisible = true;
                    } else {
                        $(this).addClass('d-none');
                    }
                });
                
                if (!hasVisible && $('#history-list').children('.history-item-card').length > 0) {
                    // show empty state if needed
                }
            }

            $('#branch-filter').on('change', function() {
                updateDateRangeLabel(); // Update label
                loadTransactions(true); // Reload data
            });

            var flatpickrInstance = $("#kt_ecommerce_sales_flatpickr").flatpickr({
                altInput: false,
                dateFormat: "Y-m-d",
                mode: "range",
                defaultDate: [defaultDate, defaultDate],
                positionElement: document.getElementById('kt_ecommerce_sales_flatpickr_custom'),
                disableMobile: true, // Force standard web UI on mobile so positioning works
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0) {
                        // Format ke nama bulan Indonesia
                        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                        let startFormat = selectedDates[0].toLocaleDateString('id-ID', options);
                        $('#date-start').val(startFormat);
                        
                        if (selectedDates.length === 2) {
                            let endFormat = selectedDates[1].toLocaleDateString('id-ID', options);
                            $('#date-end').val(endFormat);
                        } else {
                            $('#date-end').val(startFormat);
                        }
                    } else {
                        $('#date-start').val('');
                        $('#date-end').val('');
                    }
                    
                    updateDateRangeLabel(); // Update label
                    loadTransactions(true); // Reload data
                },
                onReady: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0) {
                        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                        let startFormat = selectedDates[0].toLocaleDateString('id-ID', options);
                        $('#date-start').val(startFormat);
                        $('#date-end').val(startFormat);
                    }
                }
            });

            // Trigger open flatpickr when the custom container is clicked
            $('.flatpickr-trigger').on('click', function(e) {
                e.stopPropagation();
                flatpickrInstance.open();
            });

            // Action Buttons
            $('#reset-date-btn').on('click', function(e) {
                e.preventDefault();
                flatpickrInstance.clear();
            });

            $('#btn-hari-ini').on('click', function(e) {
                e.preventDefault();
                flatpickrInstance.setDate([new Date(), new Date()], true);
            });

            $('#btn-minggu-ini').on('click', function(e) {
                e.preventDefault();
                let today = new Date();
                let day = today.getDay(); // 0 is Sunday
                
                // Find most recent Saturday (day 6)
                // If today is Sat (6), diff = 0.
                // If today is Sun (0), diff = -1. Mon (1), diff = -2, etc.
                let diff = day === 6 ? 0 : -1 - day;
                
                let firstDay = new Date(today);
                firstDay.setDate(today.getDate() + diff); // Saturday
                
                let lastDay = new Date(firstDay);
                lastDay.setDate(firstDay.getDate() + 6); // Friday (7 days total)
                
                flatpickrInstance.setDate([firstDay, lastDay], true);
            });

            $('#btn-reset-semua').on('click', function(e) {
                e.preventDefault();
                $('#branch-filter').val('all').trigger('change');
                flatpickrInstance.setDate([new Date(), new Date()], true); // Default to today
            });

            $('#branch-filter, #kt_ecommerce_sales_flatpickr').on('change', function() {
                loadTransactions(true);
            });

            function updateDateRangeLabel() {
                let branchText = "Semua Cabang";
                const branchVal = $('#branch-filter').val();
                if (branchVal) {
                    const selectedOption = $('#branch-filter option:selected').text().trim();
                    branchText = selectedOption;
                }

                let dateText = "Semua Waktu";
                const range = $('#kt_ecommerce_sales_flatpickr').val();
                if (range) {
                    const dates = range.split(' to ');
                    
                    // Format to local id-ID if possible, simple string formatting
                    let d1 = new Date(dates[0]).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});
                    if (dates.length === 2 && dates[0] !== dates[1]) {
                        let d2 = new Date(dates[1]).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});
                        dateText = `${d1} – ${d2}`;
                    } else {
                        dateText = d1;
                    }
                }

                $('#date-range-label').text(`${branchText}, ${dateText}`);
            }

            // Inisialisasi awal label
            updateDateRangeLabel();

        });

        function reloadDataTable() {
            if (typeof loadTransactions === 'function') {
                loadTransactions(true);
            }
        }
        $("#date").flatpickr({
            altInput: !0,
            altFormat: "d F, Y",
            dateFormat: "Y-m-d"
        });
    </script>
@endsection

@endsection
