@extends('template.root')

@section('content')
    <div class="pos-index-page">
        <style>
            .pos-index-page .pos-index-search {
                width: 100%;
                max-width: 320px;
            }

            .pos-index-page .pos-index-search .form-control {
                height: 44px;
                border-radius: 12px;
            }

            .pos-index-page .pos-index-filter-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                min-height: 44px;
                border-radius: 12px;
                white-space: nowrap;
            }

            .pos-index-page #active-branch-button-label {
                display: inline-block;
                max-width: 160px;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: middle;
            }

            @media (max-width: 767.98px) {
                .pos-index-page .pos-index-search {
                    max-width: 100%;
                }

                .pos-index-page .pos-index-filter-toolbar {
                    width: 100%;
                    justify-content: flex-start !important;
                }

                .pos-index-page .pos-index-filter-btn {
                    width: 100%;
                }
            }

            .stock-opname-modal .modal-content {
                border-radius: 28px;
                border: 0;
                overflow: hidden;
            }

            .stock-opname-modal .modal-header {
                border-bottom: 1px solid #eef1f7;
                padding: 1.25rem 1.5rem;
            }

            .stock-opname-modal .modal-title {
                color: #1f2937;
                font-size: 1.45rem;
                letter-spacing: -0.01em;
            }

            .stock-opname-modal .btn-close-soft {
                width: 40px;
                height: 40px;
                border: 0;
                border-radius: 50%;
                background: #eef1f7;
                color: #6b7280;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .stock-opname-modal .modal-body {
                padding: 1.5rem;
                background: #fbfcff;
            }

            .stock-opname-modal .time-card {
                border: 1px solid #dbe6ff;
                border-radius: 14px;
                background: #edf3ff;
                padding: 1rem;
                margin-bottom: 1.5rem;
            }

            .stock-opname-modal .time-title {
                color: #2557d6;
                font-weight: 700;
            }

            .stock-opname-modal .time-subtitle {
                color: #4f73ca;
                font-size: 0.9rem;
            }

            .stock-opname-modal .stock-box {
                border: 1px solid #d9dde6;
                border-radius: 12px;
                background: #f2f3f7;
                min-height: 56px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.75rem 0.95rem;
                font-weight: 700;
                color: #5f6878;
            }

            .stock-opname-modal .stock-box.stock-input {
                background: #fff;
                border: 2px solid #2f66ef;
                color: #111827;
            }

            .stock-opname-modal .stock-box input {
                border: 0;
                background: transparent;
                width: 100%;
                text-align: center;
                font-size: 1.35rem;
                font-weight: 800;
                color: inherit;
                outline: none;
            }

            .stock-opname-modal .summary-card {
                border: 1px solid #f3c5cb;
                border-radius: 12px;
                background: #fff6f7;
                color: #b4232d;
                padding: 0.95rem;
            }

            .stock-opname-modal .summary-card.summary-positive {
                border-color: #b8e6c9;
                background: #f0fdf4;
                color: #067647;
            }

            .stock-opname-modal .summary-value {
                font-size: 1.95rem;
                font-weight: 800;
                line-height: 1;
            }

            .stock-opname-modal .modal-footer {
                border-top: 1px solid #eef1f7;
                background: #fff;
                padding: 1rem 1.5rem 1.35rem;
            }

            .stock-opname-modal .btn-submit-stock {
                width: 100%;
                border-radius: 12px;
                min-height: 52px;
                font-weight: 700;
                font-size: 1.08rem;
            }

            .so-card {
                background: #fff;
                border-radius: 16px;
                padding: 1.5rem;
                margin-bottom: 1.25rem;
                border: 1px solid rgba(229, 231, 235, 0.7);
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 1px 2px rgba(0, 0, 0, 0.04);
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .so-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            }

            .so-card-title {
                font-size: 1.15rem;
                font-weight: 800;
                color: #111827;
                line-height: 1.3;
                letter-spacing: -0.015em;
            }

            .so-card-code {
                font-size: 11px;
                font-weight: 700;
                background: #f3f4f6;
                color: #4b5563;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                padding: 0.2rem 0.5rem;
                white-space: nowrap;
            }

            .so-meta-item {
                font-size: 12.5px;
                font-weight: 500;
                color: #6b7280;
            }

            .so-time-row {
                font-size: 12.5px;
                font-weight: 500;
                color: #6b7280;
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
            }

            .so-creator-avatar {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid #e5e7eb;
            }

            .so-stock-box-container {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                width: 100%;
            }

            @media (min-width: 992px) {
                .so-stock-box-container {
                    width: auto;
                }
            }

            .so-stock-box {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                flex: 1;
                height: 64px;
                border-radius: 10px;
                border: 1px solid #e5e7eb;
                background: #f9fafb;
                transition: all 0.2s ease;
            }

            @media (min-width: 992px) {
                .so-stock-box {
                    flex: none;
                    width: 78px;
                }
            }

            .so-stock-box.is-active {
                border: 1.5px solid #dbe6ff;
                background: #f5f8ff;
                cursor: pointer;
            }

            .so-stock-box.is-active:hover {
                border-color: #2f66ef;
                background: #edf3ff;
                transform: translateY(-1px);
            }

            .so-stock-box.is-surplus {
                background-color: #ecfdf5;
                border-color: #c2f0d5;
            }

            .so-stock-box.is-surplus .so-stock-label,
            .so-stock-box.is-surplus .so-stock-number {
                color: #059669;
            }

            .so-stock-box.is-loss {
                background-color: #fef2f2;
                border-color: #fcd2d2;
            }

            .so-stock-box.is-loss .so-stock-label,
            .so-stock-box.is-loss .so-stock-number {
                color: #dc2626;
            }

            .so-stock-label {
                font-size: 9px;
                font-weight: 700;
                color: #8892a2;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                margin-bottom: 0.15rem;
            }

            .so-stock-label.is-primary {
                color: #2563eb;
            }

            .so-stock-number {
                font-size: 16px;
                font-weight: 800;
                color: #1f2937;
            }

            .so-stock-number.is-primary {
                color: #2563eb;
            }

            .so-match-section {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                min-width: 110px;
            }

            @media (min-width: 992px) {
                .so-match-section {
                    align-items: flex-end;
                }
            }

            .so-match-label {
                font-size: 9px;
                font-weight: 700;
                color: #8892a2;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                margin-bottom: 0.15rem;
            }

            .so-match-value {
                font-size: 16px;
                font-weight: 800;
            }

            .so-mobile-match-bar {
                width: 100%;
                min-height: 48px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.05em;
                padding: 0.75rem 1rem;
            }

            .so-mobile-match-bar.is-surplus {
                background-color: #ecfdf5;
                border: 1px solid #c2f0d5;
                color: #059669;
            }

            .so-mobile-match-bar.is-loss {
                background-color: #fef2f2;
                border: 1px solid #fcd2d2;
                color: #dc2626;
            }

            .so-mobile-match-bar.is-match {
                background-color: #f9fafb;
                border: 1px solid #e5e7eb;
                color: #1f2937;
            }

            .so-mobile-divider {
                width: 100%;
                height: 1px;
                background-color: #f1f3f7;
            }

            .so-divider {
                width: 1px;
                height: 28px;
                background-color: #e5e7eb;
            }

            .so-btn-icon-clean {
                position: relative;
                background: transparent;
                border: 0;
                color: #6b7280;
                width: 36px;
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                transition: all 0.2s ease;
                cursor: pointer;
            }

            .so-btn-icon-clean:hover {
                background-color: #f3f4f6;
                color: #111827;
            }

            .so-btn-icon-clean.is-blue {
                color: #0d6efd;
            }

            .so-btn-icon-clean.is-blue:hover {
                background-color: #eef2ff;
                color: #0056b3;
            }

            .so-btn-quick-adj {
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                transition: all 0.2s ease;
                cursor: pointer;
                font-size: 10px;
                font-weight: 700;
                gap: 0.25rem;
                padding: 0.5rem 0.65rem;
                border: 1px solid transparent;
                white-space: nowrap;
            }

            .so-btn-quick-adj svg {
                width: 14px;
                height: 14px;
                flex-shrink: 0;
            }

            @media (min-width: 992px) {
                .so-btn-quick-adj {
                    width: 36px;
                    height: 36px;
                    padding: 0;
                }

                .so-btn-quick-adj svg {
                    width: 16px;
                    height: 16px;
                }
            }

            .so-btn-quick-adj.is-up {
                background-color: #ecfdf5;
                border: 1px solid #a7f3d0;
                color: #059669;
            }

            .so-btn-quick-adj.is-up:hover {
                background-color: #d1fae5;
                border-color: #34d399;
                color: #047857;
            }

            .so-btn-quick-adj.is-down {
                background-color: #fef2f2;
                border: 1px solid #fecaca;
                color: #dc2626;
            }

            .so-btn-quick-adj.is-down:hover {
                background-color: #fee2e2;
                border-color: #f87171;
                color: #b91c1c;
            }

            .so-btn-delete-clean {
                background: transparent;
                border: 0;
                color: #9ca3af;
                width: 36px;
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                transition: all 0.2s ease;
                cursor: pointer;
            }

            .so-btn-delete-clean:hover {
                background-color: #fef2f2;
                color: #ef4444;
            }

            #transaction-table tbody tr td {
                border: 0;
                padding: 0;
                background: transparent;
            }

            /* History Timeline Drawer Styles */
            .history-card {
                background: #fff;
                border: 1px solid #f1f3f7;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
                border-radius: 14px;
                padding: 1.15rem;
                margin-bottom: 0.5rem;
                position: relative;
            }

            .last-border-none:last-child {
                border-bottom: none !important;
                padding-bottom: 0 !important;
                margin-bottom: 0 !important;
            }

            .history-action-badge {
                font-size: 10px;
                font-weight: 700;
                border-radius: 6px;
                padding: 0.2rem 0.5rem;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            .history-action-badge.is-initial {
                background-color: #eef2ff;
                color: #4f46e5;
            }

            .history-action-badge.is-update {
                background-color: #fff7ed;
                color: #ea580c;
            }

            .history-action-badge.is-kredit,
            .history-qty-badge.is-kredit {
                background-color: #ecfdf5;
                color: #059669;
            }

            .history-action-badge.is-debit,
            .history-qty-badge.is-debit {
                background-color: #fef2f2;
                color: #dc2626;
            }

            .history-time-text {
                font-size: 11.5px;
                color: #8892a2;
                font-weight: 500;
            }

            .history-note-text {
                font-size: 13.5px;
                font-weight: 700;
                color: #1f2937;
                line-height: 1.4;
            }

            .history-qty-badge {
                font-size: 10.5px;
                font-weight: 800;
                background-color: #eff6ff;
                color: #2563eb;
                border-radius: 6px;
                padding: 0.25rem 0.45rem;
                white-space: nowrap;
            }

            @media (min-width: 992px) {
                .history-qty-badge {
                    font-size: 12px;
                    border-radius: 8px;
                    padding: 0.35rem 0.75rem;
                }
            }

            #kt_stock_opname_history {
                border-left: 1px solid rgba(229, 231, 235, 0.5);
                box-shadow: -10px 0 30px rgba(0, 0, 0, 0.03);
            }

            /* Discussion Chat Styles */
            .chat-message-container {
                display: flex;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }

            .chat-message-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                object-fit: cover;
                border: 1px solid #e5e7eb;
                flex-shrink: 0;
            }

            .chat-message-bubble {
                background-color: #f3f4f6;
                color: #1f2937;
                border-radius: 12px;
                padding: 0.75rem 1rem;
                font-size: 13.5px;
                line-height: 1.4;
                position: relative;
            }

            .chat-message-container.is-self {
                flex-direction: row-reverse;
            }

            .chat-message-container.is-self .chat-message-bubble {
                background-color: #eef2ff;
                color: #312e81;
                border-top-right-radius: 4px;
            }

            .chat-message-container:not(.is-self) .chat-message-bubble {
                border-top-left-radius: 4px;
                background-color: #f3f4f6;
                color: #1f2937;
            }

            .chat-message-header {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 0.15rem;
            }

            .chat-message-author {
                font-size: 11px;
                font-weight: 700;
                color: #4b5563;
            }

            .chat-message-container.is-self .chat-message-author {
                color: #4f46e5;
            }

            .chat-message-time {
                font-size: 9.5px;
                color: #9ca3af;
                font-weight: 500;
            }

            #kt_stock_opname_discussion {
                border-left: 1px solid rgba(229, 231, 235, 0.5);
                box-shadow: -10px 0 30px rgba(0, 0, 0, 0.03);
            }

            #kt_stock_opname_discussion_footer {
                background-color: #fbfcff;
                border-top: 1px solid #eef1f7;
            }
        </style>
        <div class="card card-flush" style="border:0px">
            <div class="card-header align-items-stretch py-3 gap-3 flex-column flex-md-row">
                <div class="card-title flex-grow-1 w-100 mb-0">
                    <div class="d-flex align-items-center position-relative my-1 pos-index-search">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" data-kt-ecommerce-product-filter="search" id="search"
                            class="form-control form-control-solid w-100 ps-12" placeholder="Cari Transaksi" />
                    </div>
                </div>
                <div class="card-toolbar w-100 w-md-auto ms-md-auto">
                    <div class="d-flex align-items-center justify-content-md-end pos-index-filter-toolbar gap-3"
                        data-kt-user-table-toolbar="base">
                        <button type="button" class="btn btn-light-success px-4 pos-index-filter-btn"
                            onclick="exportExcel()">
                            <i class="ki-duotone ki-file-down fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <span class="d-none d-sm-inline">Export Excel</span>
                        </button>
                        <button type="button" class="btn btn-light-info px-4 pos-index-filter-btn"
                            onclick="previewStock()">
                            <i class="ki-duotone ki-eye fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <span class="d-none d-sm-inline">Preview Stock</span>
                        </button>
                        <button type="button" class="btn btn-light-primary px-4 pos-index-filter-btn"
                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            <i class="ki-duotone ki-filter fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <span id="active-branch-button-label">Cabang</span>
                        </button>
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Pilihan Filter</div>
                            </div>
                            <div class="separator border-gray-200"></div>
                            <div class="px-7 py-5" data-kt-user-table-filter="form">
                                <div class="mb-5">
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
                                        <option value="all">Semua</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label fs-6 fw-semibold">Rentang Tanggal:</label>
                                    <div class="input-group mw-350px">
                                        <input class="form-control form-control-solid rounded rounded-end-0"
                                            placeholder="Pilih rentang tanggal" id="kt_ecommerce_sales_flatpickr" />
                                        <button class="btn btn-icon btn-light" id="kt_ecommerce_sales_flatpickr_clear">
                                            <i class="ki-duotone ki-cross fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--begin::Card body-->
            <div class="card-body p-0">
                <!--begin::Table-->
                <table class="table fs-6" id="transaction-table" width="100%">
                    <thead class="d-none">
                        <tr>
                            <th>Transaksi</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
    </div>
    <button type="button" class="btn btn-primary rounded-circle shadow-lg position-fixed"
        style="bottom: 60px; right: 30px; width: 60px; height: 60px; z-index: 1050; display: flex; align-items: center; justify-content: center;"
        data-bs-toggle="modal" data-bs-target="#kt_modal_add_customer">
        <i class="ki-duotone ki-plus fs-3x text-white">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </button>
    <div class="modal fade stock-opname-modal" id="kt_modal_add_customer" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <!--begin::Modal content-->
            <div class="modal-content" style="border-radius: 20px;">
                <!--begin::Form-->
                <form class="form" action="{{ url(Request::segment(1)) }}" id="kt_modal_add_customer_form"
                    data-kt-redirect="#">
                    @csrf
                    <!--begin::Modal header-->
                    <div class="modal-header border-0 pb-0" id="kt_modal_add_customer_header"
                        style="background-color: #ffffff;">
                        <!--begin::Modal title-->
                        <div>
                            <h2 class="modal-title fw-bold text-dark fs-3">Catat Stock Opname</h2>
                            <span class="text-muted" style="font-size: 0.85rem;">Data awal bersifat final dan tidak dapat
                                diedit langsung.</span>
                        </div>
                        <!--end::Modal title-->
                        <!--begin::Close-->
                        <button type="button" id="kt_modal_add_customer_close" class="btn-close-soft"
                            style="background: transparent; width: 30px; height: 30px; margin-top: -10px;"
                            data-bs-dismiss="modal" aria-label="Close">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                        <!--end::Close-->
                    </div>
                    <!--end::Modal header-->
                    <!--begin::Modal body-->
                    <div class="modal-body py-5" style="background-color: #ffffff;">
                        <!--begin::Scroll-->
                        <div class="scroll-y" id="kt_modal_add_customer_scroll" data-kt-scroll="true"
                            data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_customer_header"
                            data-kt-scroll-wrappers="#kt_modal_add_customer_scroll" data-kt-scroll-offset="220px">

                            <input type="hidden" id="date" name="date" value="{{ date('Y-m-d') }}" />

                            <div class="row g-5 mb-6">
                                <div class="col-md-6 fv-row">
                                    <label class="required form-label text-gray-800 fw-bold fs-7 mb-2">Cabang</label>
                                    <select class="form-select form-select-solid" name="branch_id" id="branch_id"
                                        data-placeholder="Pilih Cabang">
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 fv-row">
                                    <label class="required form-label text-gray-800 fw-bold fs-7 mb-2">Pilih Produk</label>
                                    <select class="form-select" style="border-color: #a3bfff;" name="product_id"
                                        id="product_id" data-placeholder="Pilih Produk">
                                        <option value="">Pilih Product</option>
                                    </select>
                                </div>
                            </div>

                            <div class="p-5 rounded-4 mb-6" style="background-color: #f9fafb;">
                                <div class="row g-5">
                                    <div class="col-6">
                                        <label class="form-label text-gray-700 fw-bold mb-3 text-uppercase"
                                            style="font-size: 0.75rem; letter-spacing: 0.05em;">Stock Sistem Saat
                                            Ini</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01"
                                                class="form-control text-end fw-bolder border-0" name="quantity"
                                                value="0" readonly
                                                style="font-size: 1.15rem; background-color: #ffffff; border-top-left-radius: 0.475rem; border-bottom-left-radius: 0.475rem;" />
                                            <span class="input-group-text border-0 fw-semibold text-gray-600"
                                                style="background-color: #f1f3f7; border-top-right-radius: 0.475rem; border-bottom-right-radius: 0.475rem;">Kg</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-primary fw-bold mb-3 text-uppercase"
                                            style="font-size: 0.75rem; letter-spacing: 0.05em;">Stock Fisik (Audit)</label>
                                        <div class="input-group"
                                            style="border: 1px solid #a3bfff; border-radius: 0.475rem; overflow: hidden;">
                                            <input type="number" step="0.01"
                                                class="form-control text-end fw-bolder border-0" name="real_stock"
                                                value="0"
                                                style="font-size: 1.15rem; background-color: #ffffff; color: #8892a2;" />
                                            <span class="input-group-text border-0 fw-bold text-primary"
                                                style="background-color: #eff4ff;">Kg</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fv-row mb-6">
                                <label class="form-label text-gray-800 fw-bold fs-7 mb-2">Detail Quantity</label>
                                <div id="detail-qty-container" class="d-flex flex-column gap-3">
                                </div>
                                <button type="button" class="btn btn-sm btn-light-primary mt-3"
                                    onclick="addDetailRow()">
                                    <i class="bi bi-plus"></i> Tambah Detail
                                </button>
                            </div>

                            <div class="fv-row mb-2">
                                <label class="form-label text-gray-800 fw-bold fs-7 mb-2">Catatan Audit</label>
                                <textarea class="form-control" style="border: 1px solid #e5e7eb; border-radius: 0.475rem;" name="note"
                                    rows="3" placeholder="Tulis kondisi barang, temuan, atau keterangan lainnya..."></textarea>
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Scroll-->
                    </div>
                    <!--end::Modal body-->
                    <!--begin::Modal footer-->
                    <div class="modal-footer border-0 pt-0 justify-content-end gap-2" style="background-color: #ffffff;">
                        <!--begin::Button-->
                        <button type="button" class="btn btn-light btn-sm fw-bold px-6"
                            style="color: #4b5563; background-color: transparent;" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="kt_modal_add_customer_submit"
                            class="btn btn-primary btn-sm text-white fw-bold px-6" style="border-radius: 8px;">
                            <span class="indicator-label">Simpan Final</span>
                            <span class="indicator-progress">Mohon tunggu...
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

    <!--begin::Stock Adjustment Modal-->
    <div class="modal fade stock-opname-modal" id="kt_modal_stock_adjustment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content"
                style="border-radius: 20px; border: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);">
                <form id="kt_modal_stock_adjustment_form" class="form">
                    @csrf
                    <!--begin::Modal header-->
                    <div class="modal-header d-flex align-items-center justify-content-between py-4 px-6 border-0"
                        id="adj-modal-header"
                        style="border-top-left-radius: 20px; border-top-right-radius: 20px; min-height: 60px;">
                        <h2 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="adj-modal-title"
                            style="font-size: 1.25rem;">
                            <!-- Dynamic Arrow Icon and Text -->
                        </h2>
                        <button type="button" class="btn-close-soft" data-bs-dismiss="modal" aria-label="Close"
                            style="width: 32px; height: 32px; background: rgba(0,0,0,0.05); border: 0; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;">
                            <i class="ki-outline ki-cross fs-3 text-gray-700"></i>
                        </button>
                    </div>
                    <!--end::Modal header-->
                    <!--begin::Modal body-->
                    <div class="modal-body p-6" style="background: #fbfcff;">
                        <!-- Current Stock Box -->
                        <div class="d-flex align-items-center justify-content-between p-4 mb-5"
                            style="background-color: #f9fafb; border-radius: 12px; border: 1px solid #f1f3f7;">
                            <span class="text-gray-600 fw-semibold">Stock Fisik Saat Ini:</span>
                            <span class="fs-4 fw-bold text-gray-900" id="adj-current-stock-label">0 Kg</span>
                        </div>

                        <!-- Qty Input Group -->
                        <div class="fv-row mb-5">
                            <label class="required form-label text-gray-700 fw-semibold mb-2">Qty Penyesuaian</label>
                            <div class="input-group input-group-solid rounded"
                                style="border: 1px solid #d9dde6; overflow: hidden; background: #fff;">
                                <span class="input-group-text border-0 fw-bold fs-3 justify-content-center"
                                    id="adj-type-badge" style="width: 46px;">+</span>
                                <input type="number" step="0.01"
                                    class="form-control border-0 fw-bold text-gray-900 px-3 fs-3" id="adj-qty-input"
                                    name="qty" placeholder="1" required min="0.01"
                                    style="background: transparent;">
                            </div>
                            <div class="text-muted fs-7 mt-2 text-end">
                                Menjadi: <strong class="text-gray-900" id="adj-result-stock-label">0 Kg</strong>
                            </div>
                        </div>

                        <!-- Reason Input -->
                        <div class="fv-row mb-2">
                            <label class="required form-label text-gray-700 fw-semibold mb-2">Alasan Penyesuaian</label>
                            <textarea class="form-control form-control-solid rounded-lg px-4 py-3" id="adj-reason-input" name="reason"
                                rows="3" placeholder="Contoh: Salah hitung saat audit awal" required
                                style="background-color: #f9fafb; border: 1px solid #d9dde6;"></textarea>
                        </div>
                    </div>
                    <!--end::Modal body-->
                    <!--begin::Modal footer-->
                    <div class="modal-footer border-0 d-flex justify-content-end gap-3 pt-0 pb-6 px-6"
                        style="background: #fbfcff; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                            style="border-radius: 10px; font-weight: 600; background: #f3f4f6; color: #4b5563;">Batal</button>
                        <button type="submit" class="btn btn-primary" id="adj-submit-btn"
                            style="border-radius: 10px; font-weight: 600; padding: 0.75rem 1.75rem;">Simpan</button>
                    </div>
                    <!--end::Modal footer-->
                </form>
            </div>
        </div>
    </div>
    <!--end::Stock Adjustment Modal-->

    <!--begin::Stock Opname History Drawer-->
    <div id="kt_stock_opname_history" class="bg-body" data-kt-drawer="true" data-kt-drawer-name="stock-opname-history"
        data-kt-drawer-activate="true" data-kt-drawer-overlay="true"
        data-kt-drawer-width="{default:'100%', 'md': '500px'}" data-kt-drawer-direction="end"
        data-kt-drawer-close="#kt_stock_opname_history_close">
        <div class="card w-100 shadow-none border-0 rounded-0" style="height: 100vh;">
            <!--begin::Header-->
            <div class="card-header border-0 pe-5" id="kt_stock_opname_history_header" style="min-height: 70px;">
                <div class="card-title d-flex flex-column align-items-start">
                    <h3 class="fw-bold text-gray-900 mb-1 d-flex align-items-center gap-2">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: #4b5563;">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                            <path d="M3 3v5h5" />
                            <path d="M12 7v5l4 2" />
                        </svg>
                        Audit Timeline
                    </h3>
                    <span class="text-muted fs-7 fw-semibold" id="history-transaction-code">-</span>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-icon btn-active-light-primary me-n5"
                        id="kt_stock_opname_history_close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
            </div>
            <!--end::Header-->
            <!--begin::Body-->
            <div class="card-body position-relative pt-0" id="kt_stock_opname_history_body">
                <!--begin::Scroll-->
                <div id="kt_stock_opname_history_scroll" class="position-relative scroll-y me-n5 pe-5"
                    data-kt-scroll="true" data-kt-scroll-height="auto"
                    data-kt-scroll-wrappers="#kt_stock_opname_history_body"
                    data-kt-scroll-dependencies="#kt_stock_opname_history_header" data-kt-scroll-offset="5px"
                    style="height: calc(100vh - 100px);">

                    <div class="timeline timeline-border-dashed mt-5" id="history-timeline-items">
                        <!-- Timeline items rendered dynamically -->
                    </div>

                </div>
                <!--end::Scroll-->
            </div>
            <!--end::Body-->
        </div>
    </div>
    <!--end::Stock Opname History Drawer-->

    <div id="kt_stock_opname_discussion" class="bg-body" data-kt-drawer="true"
        data-kt-drawer-name="stock-opname-discussion" data-kt-drawer-activate="true" data-kt-drawer-overlay="true"
        data-kt-drawer-width="{default:'100%', 'md': '500px'}" data-kt-drawer-direction="end"
        data-kt-drawer-close="#kt_stock_opname_discussion_close">
        <div class="card w-100 shadow-none border-0 rounded-0 d-flex flex-column" style="height: 100vh;">
            <!--begin::Header-->
            <div class="card-header border-0 pe-5" id="kt_stock_opname_discussion_header" style="min-height: 70px;">
                <div class="card-title d-flex flex-column align-items-start">
                    <h3 class="fw-bold text-gray-900 mb-1 d-flex align-items-center gap-2">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: #0d6efd;">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        Ruang Diskusi
                    </h3>
                    <span class="text-muted fs-7 fw-semibold" id="discussion-product-name">-</span>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-icon btn-active-light-primary me-n5"
                        id="kt_stock_opname_discussion_close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
            </div>
            <!--end::Header-->
            <!--begin::Body-->
            <div class="card-body position-relative pt-0 flex-grow-1 overflow-hidden d-flex flex-column"
                id="kt_stock_opname_discussion_body">
                <!--begin::Scroll-->
                <div id="kt_stock_opname_discussion_scroll" class="position-relative scroll-y flex-grow-1 me-n5 pe-5 mb-4"
                    data-kt-scroll="true" data-kt-scroll-height="auto"
                    data-kt-scroll-wrappers="#kt_stock_opname_discussion_body"
                    data-kt-scroll-dependencies="#kt_stock_opname_discussion_header, #kt_stock_opname_discussion_footer"
                    data-kt-scroll-offset="5px">

                    <div class="d-flex flex-column gap-4" id="discussion-items">
                        <!-- Chat messages rendered dynamically -->
                    </div>

                </div>
                <!--end::Scroll-->
            </div>
            <!--end::Body-->
            <!--begin::Footer-->
            <div class="card-footer border-0 p-5" id="kt_stock_opname_discussion_footer">
                <form id="kt_stock_opname_discussion_form" class="d-flex align-items-center gap-2">
                    <input type="hidden" id="discussion-stock-opname-id" name="stock_opname_id">
                    <input type="text" class="form-control form-control-solid rounded-pill px-4"
                        id="discussion-message-input" placeholder="Tulis pesan..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary btn-icon rounded-circle w-40px h-40px flex-shrink-0"
                        style="background-color: #3b82f6; border-color: #3b82f6;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13" />
                            <polygon points="22 2 15 22 11 13 2 9 22 2" />
                        </svg>
                    </button>
                </form>
            </div>
            <!--end::Footer-->
        </div>
    </div>
    <!--end::Stock Opname Discussion Drawer-->

@section('script')
    <script type="text/javascript">
        const currentUserId = {{ Auth::user()->id_user }};
        // Data stock kosong dari server
        const emptyStockData = @json($emptyStockData ?? []);

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

                const scopeText = (selectedBranchId && selectedBranchId !== 'all') ? 'pada cabang ini' :
                    'pada seluruh cabang';
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
                    confirmButtonColor: '#f1416c',
                    allowOutsideClick: false
                });
            }
        }

        var dataTable;
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const segment1 = "{{ Request::segment(1) }}";

        function formatRupiah(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value || 0));
        }

        function updateStockSummary(unitPrice = null) {
            const systemStock = Number($('input[name="quantity"]').val() || 0);
            const realStock = Number($('input[name="real_stock"]').val() || 0);
            const difference = realStock - systemStock;
            const price = Number(unitPrice ?? $('#product_id').data('unit-price') ?? 0);
            const value = difference * price;
            const diffRounded = Math.round(difference * 100) / 100;

            $('#stock-difference-text').text(`${diffRounded} Kg`);
            $('#stock-difference-value').text(`${value < 0 ? '-' : ''}Rp ${formatRupiah(Math.abs(value))}`);

            const $card = $('.summary-card');
            if (difference > 0) {
                $card.addClass('summary-positive').removeClass('summary-negative');
            } else if (difference < 0) {
                $card.removeClass('summary-positive').addClass('summary-negative');
            } else {
                $card.removeClass('summary-positive summary-negative');
            }
        }

        function formatLongDateTime(dateValue) {
            if (!dateValue) return '-';
            const dt = new Date(dateValue);
            if (Number.isNaN(dt.getTime())) return dateValue;
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
                'Oktober', 'November', 'Desember'
            ];
            const day = dt.getDate();
            const month = months[dt.getMonth()];
            const year = dt.getFullYear();
            const h = String(dt.getHours()).padStart(2, '0');
            const m = String(dt.getMinutes()).padStart(2, '0');
            return `${day} ${month} ${year}, ${h}.${m}`;
        }

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function renderStockOpnameCard(row) {
            const productName = row?.product?.name || row?.name || '-';
            const code = row?.code ? `#${row.code}` : '-';
            const cleanCode = row?.code ? row.code : '-';
            const branchName = row?.branch_name || 'Semua Cabang';
            const isPending = row?.status === 'pending';
            const dateLabel = formatLongDateTime(row?.created_at || row?.date);
            const creatorName = row?.creator_name || '';
            const stockSystem = isPending ? Number(row?.live_stock || 0) : Number(row?.stock || 0);
            const stockFisik = Number(row?.real_stock || 0);
            const selisih = stockFisik - stockSystem;
            const hpp = Number(row?.avg_hpp_calc || row?.avg_price || 0);
            const nilaiSelisih = Math.floor(selisih * hpp);
            const id = row?.id || 0;
            const discussionsCount = Number(row?.discussions_count || 0);
            const historyCount = Number(row?.history_count || 0);

            const isMinus = selisih < 0;
            const hppText =
                `HPP: Rp ${formatRupiah(Math.round(Math.abs(hpp)))}/${row?.product?.unit?.abbreviation || 'Kg'}`;
            const nilaiText = `Rp ${formatRupiah(Math.abs(nilaiSelisih))}`;

            // Dynamic Stock Match Label & Color
            let stockMatchLabel = 'STOCK MATCH';
            let stockMatchColor = '#111827';
            if (selisih < 0) {
                stockMatchLabel = 'POTENSI RUGI';
                stockMatchColor = '#ef4444';
            } else if (selisih > 0) {
                stockMatchLabel = 'SURPLUS STOK';
                stockMatchColor = '#10b981';
            }

            // Dynamic Selisih Box Class
            let selisihClass = '';
            if (selisih > 0) {
                selisihClass = 'is-surplus';
            } else if (selisih < 0) {
                selisihClass = 'is-loss';
            }

            // Mobile Match Bar Class & Content
            let mobileMatchClass = 'is-match';
            let mobileMatchText = 'STOCK MATCH: Rp 0';
            if (selisih > 0) {
                mobileMatchClass = 'is-surplus';
                mobileMatchText = `NILAI KELEBIHAN: Rp ${formatRupiah(Math.abs(nilaiSelisih))}`;
            } else if (selisih < 0) {
                mobileMatchClass = 'is-loss';
                mobileMatchText = `NILAI KERUGIAN: -Rp ${formatRupiah(Math.abs(nilaiSelisih))}`;
            }

            // Dynamic User Avatar using userId modulo
            const userId = row?.created_by || 1;
            const avatarNum = (userId % 30) + 1;
            const avatarUrl = `/assets/media/avatars/300-${avatarNum}.jpg`;

            // Helper to format quantity nicely (e.g. keeping decimal if needed)
            const formatQty = (val) => {
                const num = Number(val || 0);
                if (num === 0) return '0.0';
                return num % 1 === 0 ? num.toString() : num.toFixed(2);
            };

            const creatorHtml = creatorName ?
                `
                <span class="text-muted mx-1">•</span>
                <span class="so-time-row">
                    <img src="${avatarUrl}" class="so-creator-avatar" alt="Avatar">
                    <span>${escapeHtml(creatorName)}</span>
                </span>
                ` :
                '';

            const statusHtml = isPending ?
                `<span class="badge badge-light-warning fs-8 fw-bold">Pending</span>` :
                `<span class="badge badge-light-success fs-8 fw-bold">Finalized</span>`;

            return `
                <div class="so-card d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                    <!-- Left: Info -->
                    <div class="d-flex flex-column flex-grow-1" style="min-width: 280px;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="so-card-title mb-0">${escapeHtml(productName)}</h4>
                            ${statusHtml}
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap text-muted fs-7">
                            <span class="so-card-code">${escapeHtml(cleanCode)}</span>
                            <span class="text-muted">•</span>
                            <span class="so-meta-item">${escapeHtml(branchName)}</span>
                            <span class="text-muted">•</span>
                            <span class="so-meta-item">${escapeHtml(hppText)}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1 text-muted fs-8 flex-wrap">
                            <span class="so-time-row">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>${escapeHtml(dateLabel)}</span>
                            </span>
                            ${creatorHtml}
                        </div>
                    </div>

                    <!-- Middle: Stock Boxes -->
                    <div class="so-stock-box-container justify-content-start justify-content-lg-center">
                        <!-- Box 1: Sistem -->
                        <div class="so-stock-box">
                            <span class="so-stock-label">Sistem</span>
                            <span class="so-stock-number">${formatQty(stockSystem)}</span>
                        </div>
                        <!-- Box 2: Fisik -->
                        <div class="so-stock-box is-active" onclick="editProduct(${id})" title="Klik untuk sesuaikan stock fisik">
                            <span class="so-stock-label is-primary">Fisik</span>
                            <span class="so-stock-number is-primary">${formatQty(stockFisik)}</span>
                        </div>
                        <!-- Box 3: Selisih -->
                        <div class="so-stock-box ${selisihClass}">
                            <span class="so-stock-label">Selisih</span>
                            <span class="so-stock-number">${formatQty(selisih)}</span>
                        </div>
                    </div>

                    <!-- Mobile Match Bar (only visible on mobile) -->
                    <div class="so-mobile-match-bar ${mobileMatchClass} d-flex d-lg-none align-items-center justify-content-center">
                        <span>${escapeHtml(mobileMatchText)}</span>
                    </div>

                    <!-- Mobile Divider (only visible on mobile) -->
                    <div class="so-mobile-divider d-lg-none mt-2 mb-3"></div>

                    <!-- Right: Match, Actions, Delete -->
                    <div class="d-flex align-items-center justify-content-between justify-content-lg-end gap-2 gap-lg-3 flex-nowrap w-100 w-lg-auto">
                        <!-- Value Match -->
                        <div class="so-match-section d-none d-lg-flex">
                            <span class="so-match-label">${stockMatchLabel}</span>
                            <span class="so-match-value" style="color: ${stockMatchColor};">${selisih < 0 ? '-' : ''}${nilaiText}</span>
                        </div>

                        <!-- Logs & Chats -->
                        <div class="d-flex align-items-center gap-1">
                            <!-- History -->
                            <button class="so-btn-icon-clean" onclick="showHistory(${id})" title="History Transaksi">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                    <path d="M3 3v5h5"/>
                                    <path d="M12 7v5l4 2"/>
                                </svg>
                                ${historyCount > 0 ? `<span class="position-absolute badge rounded-pill bg-danger text-white" style="top: 2px; right: 2px; font-size: 8px; padding: 0.25em 0.5em; z-index: 1;">${historyCount}</span>` : ''}
                            </button>
                            <!-- Chat/Discussion -->
                            <button class="so-btn-icon-clean is-blue" data-discussion-btn-id="${id}" title="Diskusi / Catatan" onclick="showDiscussion(${id})">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                ${discussionsCount > 0 ? `<span class="position-absolute badge rounded-pill bg-danger text-white" style="top: 2px; right: 2px; font-size: 8px; padding: 0.25em 0.5em; z-index: 1;">${discussionsCount}</span>` : ''}
                            </button>
                        </div>

                        <div class="so-divider"></div>

                        <!-- Quick Adjustments -->
                        <div class="d-flex align-items-center gap-2">
                            <!-- Adjust Up -->
                            <button class="so-btn-quick-adj is-up" onclick="openAdjustmentModal(${id}, 'up')" title="Sesuaikan Stok Masuk">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="7" y1="17" x2="17" y2="7"/>
                                    <polyline points="7 7 17 7 17 17"/>
                                </svg>
                                <span class="d-lg-none">+ Kredit</span>
                            </button>
                            <!-- Adjust Down -->
                            <button class="so-btn-quick-adj is-down" onclick="openAdjustmentModal(${id}, 'down')" title="Sesuaikan Stok Keluar">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="7" y1="7" x2="17" y2="17"/>
                                    <polyline points="17 7 17 17 7 17"/>
                                </svg>
                                <span class="d-lg-none">- Kredit</span>
                            </button>
                        </div>

                        <div class="so-divider"></div>

                        <!-- Finalize (if pending) -->
                        ${isPending ? `
                            <button class="so-btn-icon-clean text-success" onclick="finalizeStockOpname(${id})" title="Finalisasi Transaksi">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M9 12l2 2 4-4"></path>
                                </svg>
                            </button>
                            ` : ''}

                        <!-- Delete -->
                        <button class="so-btn-delete-clean" onclick="deleteProduct(${id})" title="Hapus Transaksi">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }

        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="cabang"]');
            const $activeBranchButtonLabel = $('#active-branch-button-label');

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            window.exportExcel = function() {
                let cabang = $('[data-kt-ecommerce-product-filter="cabang"]').val() || '';
                let range = $('#kt_ecommerce_sales_flatpickr').val() || '';
                let startDate = '';
                let endDate = '';
                if (range) {
                    let dates = range.split(' to ');
                    startDate = dates[0];
                    endDate = dates[1] ?? dates[0];
                }

                let url = "{{ route('stock-opname.export') }}?cabang_filter=" + cabang + "&start_date=" +
                    startDate + "&end_date=" + endDate;
                window.open(url, '_blank');
            };

            window.previewStock = function() {
                let cabang = $('[data-kt-ecommerce-product-filter="cabang"]').val() || '';
                let range = $('#kt_ecommerce_sales_flatpickr').val() || '';
                let startDate = '';
                let endDate = '';
                if (range) {
                    let dates = range.split(' to ');
                    startDate = dates[0];
                    endDate = dates[1] ?? dates[0];
                }

                let url = "{{ route('stock-opname.preview') }}?cabang_filter=" + cabang + "&start_date=" +
                    startDate + "&end_date=" + endDate;
                window.open(url, '_blank');
            };

            $("#kt_ecommerce_sales_flatpickr").flatpickr({
                altInput: !0,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                mode: "range",
                onChange: function(e, t, n) {
                    if (typeof dataTable !== 'undefined') {
                        dataTable.draw();
                    }
                }
            });

            $("#kt_ecommerce_sales_flatpickr_clear").on("click", function() {
                $("#kt_ecommerce_sales_flatpickr").val("");
                if (typeof dataTable !== 'undefined') {
                    dataTable.draw();
                }
            });

            dataTable = $('#transaction-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                ordering: false,
                ajax: {
                    url: "{{ route('stock-opname.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                        d.cabang_filter = $('[data-kt-ecommerce-product-filter="cabang"]').val();
                        var range = $('#kt_ecommerce_sales_flatpickr').val();
                        if (range) {
                            var dates = range.split(' to ');
                            d.start_date = dates[0];
                            d.end_date = dates[1] ?? dates[0];
                        }
                    }
                },
                columns: [{
                    data: null,
                    name: 'name',
                    orderable: false,
                    searchable: true,
                    render: function(data, type, row) {
                        return renderStockOpnameCard(row);
                    }
                }]
            });
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            $('#kt_stock_opname_discussion_form').on('submit', function(e) {
                e.preventDefault();

                const id = $('#discussion-stock-opname-id').val();
                const message = $('#discussion-message-input').val();
                if (!message || !id) return;

                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true);

                $.ajax({
                    url: `/stock-opname/${id}/discussion`,
                    type: 'POST',
                    data: {
                        message: message,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#discussion-message-input').val('');

                        // Remove empty message text if present
                        $('#discussion-items').find('.text-muted').remove();

                        // Append the new message
                        const item = response.discussion;
                        const isSelf = String(item.created_by) === String(currentUserId);
                        const selfClass = isSelf ? 'is-self' : '';
                        const avatarNum = ((item.created_by || 1) % 30) + 1;
                        const avatarUrl = `/assets/media/avatars/300-${avatarNum}.jpg`;

                        const itemHtml = `
                            <div class="chat-message-container ${selfClass}">
                                <img src="${avatarUrl}" class="chat-message-avatar" alt="Avatar">
                                <div class="d-flex flex-column align-items-${isSelf ? 'end' : 'start'}">
                                    <div class="chat-message-header">
                                        <span class="chat-message-author">${escapeHtml(item.creator_name || 'User')}</span>
                                        <span class="chat-message-time">${formatLongDateTime(item.created_at)}</span>
                                    </div>
                                    <div class="chat-message-bubble shadow-sm">
                                        ${escapeHtml(item.message || '')}
                                    </div>
                                </div>
                            </div>
                        `;

                        $('#discussion-items').append(itemHtml);

                        // Scroll to bottom
                        const scrollEl = document.getElementById(
                            'kt_stock_opname_discussion_scroll');
                        if (scrollEl) {
                            scrollEl.scrollTop = scrollEl.scrollHeight;
                        }

                        // Update badge count on the card button dynamically
                        const $btn = $(`[data-discussion-btn-id="${id}"]`);
                        if ($btn.length > 0) {
                            let $badge = $btn.find('.badge');
                            if ($badge.length > 0) {
                                const newCount = parseInt($badge.text() || 0) + 1;
                                $badge.text(newCount);
                            } else {
                                $btn.append(
                                    `<span class="position-absolute badge rounded-pill bg-danger text-white" style="top: 2px; right: 2px; font-size: 8px; padding: 0.25em 0.5em; z-index: 1;">1</span>`
                                );
                            }
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Terjadi kesalahan saat mengirim pesan.'
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            $('#kt_modal_stock_adjustment_form').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const id = form.data('id');
                const type = form.data('type');
                const qty = $('#adj-qty-input').val();
                const reason = $('#adj-reason-input').val();

                const submitBtn = $('#adj-submit-btn');
                submitBtn.prop('disabled', true).text('Menyimpan...');

                $.ajax({
                    url: `/stock-opname/${id}/adjust`,
                    type: 'POST',
                    data: {
                        qty: qty,
                        type: type,
                        reason: reason,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message ||
                                'Penyesuaian stok berhasil disimpan.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            const modal = bootstrap.Modal.getInstance(document
                                .getElementById('kt_modal_stock_adjustment'));
                            if (modal) modal.hide();

                            // Refresh DataTable
                            if (typeof dataTable !== 'undefined') {
                                dataTable.ajax.reload(null, false);
                            }
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat memproses penyesuaian.'
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text('Simpan');
                    }
                });
            });

            $('[data-kt-ecommerce-product-filter="cabang"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw();
                showStockAlert($(this).val());
            });

            updateActiveFilterInfo();
            showStockAlert($('[data-kt-ecommerce-product-filter="cabang"]').val());

            const cancelButton = document.getElementById('kt_modal_add_customer_cancel');
            if (cancelButton) {
                cancelButton.addEventListener('click', function(e) {
                    e.preventDefault();
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
                            const modal = bootstrap.Modal.getInstance(document.getElementById(
                                'kt_modal_add_customer'));
                            modal.hide();
                        }
                    });
                });
            }

            $('#kt_modal_add_customer').on('hidden.bs.modal', function() {
                var form = $('#kt_modal_add_customer_form');
                form.trigger('reset');
                form.find('input[name="_method"]').remove();
                $('select[name="product_id"]').val(null).trigger('change');

                var filterBranch = $('[data-kt-ecommerce-product-filter="cabang"]').val();
                var defaultBranch = (filterBranch && filterBranch !== 'all') ? filterBranch : $(
                    'select[name="branch_id"] option:first').val();
                $('select[name="branch_id"]').val(defaultBranch).trigger('change');

                form.attr('action', '{{ url(Request::segment(1)) }}');
                $('#kt_modal_add_customer_header h2').text('Catat Stock Opname');
                $('#kt_modal_add_customer_submit .indicator-label').html(
                    'Buat Transaksi <i class="ki-outline ki-arrow-right text-white fs-4 ms-1"></i>');
                $('#detail-qty-container').empty();
                if (typeof detailRowCount !== 'undefined') {
                    detailRowCount = 0;
                }
                form.find('input, select, textarea, button[type="submit"], button').prop('disabled', false);
                $('input[name="quantity"]').val(0);
                $('input[name="real_stock"]').val(0);
                $('textarea[name="note"]').val('');
                if (typeof updateStockSummary === 'function') {
                    updateStockSummary(0);
                }
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
                            form.trigger('reset');

                            // 2. Hapus input _method
                            form.find('input[name="_method"]').remove();
                            $('select[name="product_id"]').val(null).trigger(
                                'change'); // Reset select2
                            $('#product_id select').val(null).trigger('change');

                            // 3. Kembalikan action form ke default (untuk create)
                            form.attr('action',
                                `/${segment1}`); // Misal segment1 = 'stock-opname'

                            // 4. Kembalikan judul modal (opsional)
                            $('#kt_modal_add_customer_header h2').text(
                                'Catat Stock Opname');

                            $('#kt_modal_add_customer_submit .indicator-label').html(
                                'Simpan Final');
                            updateStockSummary();

                            // 5. Tutup modal
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
                        var msg = 'Terjadi kesalahan saat menyimpan data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
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
                        url: `/stock-opname/${id}`, // Ganti dengan URL yang sesuai
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
                                timer: 1500 // notifikasi akan hilang otomatis setelah 1.5 detik
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

        function editProduct(id) {
            $.ajax({
                url: `/stock-opname/${id}/edit`,
                type: 'GET',
                success: function(response) {
                    console.log(response);

                    // Prevent old branch change handler from overwriting quantity
                    $('#branch_id').off('change.stockOpname');

                    // Clear and reset product_id select2 first
                    var $productSelect = $('select[name="product_id"]');
                    $productSelect.empty();
                    $productSelect.append(new Option('Pilih Product', '', false, false));
                    $productSelect.append(new Option(response.name, response.product_id, true, true));
                    $productSelect.val(response.product_id).trigger('change');

                    // Set flatpickr date
                    var fp = $('#date')[0]._flatpickr;
                    if (fp) {
                        fp.setDate(response.date);
                    }

                    // Set branch_id BEFORE quantity so re-init won't overwrite
                    $('select[name="branch_id"]').val(response.branch_id).trigger('change');

                    // Set form fields from database values (not live stock)
                    $('input[name="quantity"]').val(response.stock);
                    $('input[name="real_stock"]').val(response.real_stock);
                    $('textarea[name="note"]').val(response.note || '');
                    updateStockSummary(response.avg_price || 0);

                    // Ubah action form untuk update
                    var form = $('#kt_modal_add_customer_form');
                    form.attr('action', `/stock-opname/${id}`);
                    form.find('input[name="_method"]').remove();
                    form.append(
                        '<input type="hidden" name="_method" value="PUT">'
                    );

                    // Populate details if any
                    $('#detail-qty-container').empty();
                    detailRowCount = 0;
                    if (response.details) {
                        try {
                            const details = JSON.parse(response.details);
                            if (Array.isArray(details)) {
                                details.forEach(function(detail) {
                                    addDetailRow(detail.name, detail.qty);
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing details JSON', e);
                        }
                    }

                    // --- ENABLE semua input/select/textarea di form untuk edit ---
                    form.find('input, select, textarea, button[type="submit"]').prop('disabled', false);

                    // Ubah judul modal
                    $('#kt_modal_add_customer_header h2').text('Edit Transaksi');
                    $('#kt_modal_add_customer_submit .indicator-label').text('Simpan Perubahan');

                    // Tampilkan modal untuk edit produk
                    var modal = new bootstrap.Modal(document.getElementById('kt_modal_add_customer'));
                    modal.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data produk.'
                    });
                }
            });
        }

        function viewProduct(id) {
            $.ajax({
                url: `/stock-opname/${id}/edit`,
                type: 'GET',
                success: function(response) {
                    console.log(response);

                    // Prevent old branch change handler from overwriting quantity
                    $('#branch_id').off('change.stockOpname');

                    // Clear and reset product_id select2 first
                    var $productSelect = $('select[name="product_id"]');
                    $productSelect.empty();
                    $productSelect.append(new Option('Pilih Product', '', false, false));
                    $productSelect.append(new Option(response.name, response.product_id, true, true));
                    $productSelect.val(response.product_id).trigger('change');

                    // Set flatpickr date
                    var fp = $('#date')[0]._flatpickr;
                    if (fp) {
                        fp.setDate(response.date);
                    }

                    // Set branch_id BEFORE quantity so re-init won't overwrite
                    $('select[name="branch_id"]').val(response.branch_id).trigger('change');

                    // Set form fields from database values (not live stock)
                    $('input[name="quantity"]').val(response.stock);
                    $('input[name="real_stock"]').val(response.real_stock);
                    $('textarea[name="note"]').val(response.note || '');
                    updateStockSummary(response.avg_price || 0);

                    // Ubah action form untuk view (no action)
                    var form = $('#kt_modal_add_customer_form');
                    form.attr('action', '#');
                    form.find('input[name="_method"]').remove();

                    // Populate details if any
                    $('#detail-qty-container').empty();
                    detailRowCount = 0;
                    if (response.details) {
                        try {
                            const details = JSON.parse(response.details);
                            if (Array.isArray(details)) {
                                details.forEach(function(detail) {
                                    addDetailRow(detail.name, detail.qty);
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing details JSON', e);
                        }
                    }

                    // --- DISABLE semua input/select/textarea di form supaya read-only ---
                    form.find('input, select, textarea, button').prop('disabled', true);

                    // Ubah judul modal untuk view
                    $('#kt_modal_add_customer_header h2').text('Lihat Transaksi');

                    // Tampilkan modal untuk view produk
                    var modal = new bootstrap.Modal(document.getElementById('kt_modal_add_customer'));
                    modal.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data produk.'
                    });
                }
            });
        }

        function showHistory(id) {
            $.ajax({
                url: `/stock-opname/${id}/history`,
                type: 'GET',
                success: function(response) {
                    $('#history-transaction-code').text(response.code || '-');

                    const $timeline = $('#history-timeline-items');
                    $timeline.empty();

                    const unit = response.unit || 'Kg';

                    // Helper to format quantity nicely
                    const formatNum = (v) => {
                        const n = Number(v || 0);
                        return n % 1 === 0 ? n.toString() : n.toFixed(2);
                    };

                    if (response.history && response.history.length > 0) {
                        response.history.forEach((item, index) => {
                            const isInitial = item.action === 'INITIAL';

                            let badgeClass = 'is-initial';
                            let actionLabel = 'INITIAL';
                            let qtyHtml = '';

                            if (isInitial) {
                                badgeClass = 'is-initial';
                                actionLabel = 'INITIAL';
                                qtyHtml =
                                    `<span class="history-qty-badge">${formatNum(item.real_stock)} ${unit}</span>`;
                            } else {
                                // Since history is descending, the item at index+1 is chronological predecessor
                                const nextItem = response.history[index + 1];
                                const oldStock = nextItem ? Number(nextItem.real_stock) : Number(item
                                    .real_stock);
                                const newStock = Number(item.real_stock);

                                if (newStock >= oldStock) {
                                    badgeClass = 'is-kredit';
                                    actionLabel = '+ KREDIT';
                                    qtyHtml = `
                                        <div class="d-flex align-items-center gap-1 flex-nowrap">
                                            <span class="text-muted text-decoration-line-through fs-7">${formatNum(oldStock)}</span>
                                            <span class="text-muted fs-7 mx-1">&rarr;</span>
                                            <span class="history-qty-badge is-kredit">${formatNum(newStock)} ${unit}</span>
                                        </div>
                                    `;
                                } else {
                                    badgeClass = 'is-debit';
                                    actionLabel = '- DEBIT';
                                    qtyHtml = `
                                        <div class="d-flex align-items-center gap-1 flex-nowrap">
                                            <span class="text-muted text-decoration-line-through fs-7">${formatNum(oldStock)}</span>
                                            <span class="text-muted fs-7 mx-1">&rarr;</span>
                                            <span class="history-qty-badge is-debit">${formatNum(newStock)} ${unit}</span>
                                        </div>
                                    `;
                                }
                            }

                            const formattedTime = formatLongDateTime(item.created_at);

                            // Dynamic User Avatar using userId modulo
                            const userId = item.created_by || 1;
                            const avatarNum = (userId % 30) + 1;
                            const avatarUrl = `/assets/media/avatars/300-${avatarNum}.jpg`;

                            let detailsHtml = '';
                            if (item.details && Array.isArray(item.details) && item.details.length >
                                0) {
                                detailsHtml =
                                    '<div class="history-details-box mt-3 mb-3 p-3 bg-light rounded">';
                                detailsHtml +=
                                    '<div class="fw-bold fs-8 text-muted mb-2">Detail Stock Fisik:</div>';
                                item.details.forEach(det => {
                                    detailsHtml += `<div class="d-flex justify-content-between align-items-center fs-8 text-gray-700 border-bottom border-gray-300 pb-1 mb-1 last-border-none">
                                        <span>${escapeHtml(det.name)}</span>
                                        <span class="fw-bold">${formatNum(det.qty)} ${unit}</span>
                                    </div>`;
                                });
                                detailsHtml += '</div>';
                            }

                            const timelineItemHtml = `
                                <div class="timeline-item">
                                    <div class="timeline-line"></div>
                                    <div class="timeline-icon">
                                        <span class="bullet bullet-dot bg-primary h-10px w-10px"></span>
                                    </div>
                                    <div class="timeline-content mb-8 mt-n1">
                                        <div class="history-card">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <span class="history-action-badge ${badgeClass}">${actionLabel}</span>
                                                <span class="history-time-text d-flex align-items-center gap-1">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                    ${formattedTime}
                                                </span>
                                            </div>
                                            
                                            <div class="history-note-text ${detailsHtml ? '' : 'mb-3'}">
                                                "${escapeHtml(item.note || '')}"
                                            </div>
                                            
                                            ${detailsHtml}
                                            
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="${avatarUrl}" class="so-creator-avatar" alt="Avatar">
                                                    <span class="fs-7 fw-semibold text-gray-700">${escapeHtml(item.creator_name || 'System')}</span>
                                                </div>
                                                ${qtyHtml}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            $timeline.append(timelineItemHtml);
                        });
                    } else {
                        $timeline.append(
                            '<div class="text-muted text-center py-5">Belum ada riwayat audit.</div>');
                    }

                    // Show Metronic Drawer
                    const drawerEl = document.getElementById('kt_stock_opname_history');
                    let drawer = KTDrawer.getInstance(drawerEl);
                    if (!drawer) {
                        drawer = new KTDrawer(drawerEl);
                    }
                    drawer.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data riwayat audit.'
                    });
                }
            });
        }

        function showDiscussion(id) {
            $.ajax({
                url: `/stock-opname/${id}/discussion`,
                type: 'GET',
                success: function(response) {
                    $('#discussion-product-name').text(response.product_name || '-');
                    $('#discussion-stock-opname-id').val(id);
                    $('#discussion-message-input').val('');

                    const $items = $('#discussion-items');
                    $items.empty();

                    if (response.discussions && response.discussions.length > 0) {
                        response.discussions.forEach((item) => {
                            const isSelf = String(item.created_by) === String(currentUserId);
                            const selfClass = isSelf ? 'is-self' : '';
                            const avatarNum = ((item.created_by || 1) % 30) + 1;
                            const avatarUrl = `/assets/media/avatars/300-${avatarNum}.jpg`;

                            const itemHtml = `
                                <div class="chat-message-container ${selfClass}">
                                    <img src="${avatarUrl}" class="chat-message-avatar" alt="Avatar">
                                    <div class="d-flex flex-column align-items-${isSelf ? 'end' : 'start'}">
                                        <div class="chat-message-header">
                                            <span class="chat-message-author">${escapeHtml(item.creator_name || 'User')}</span>
                                            <span class="chat-message-time">${formatLongDateTime(item.created_at)}</span>
                                        </div>
                                        <div class="chat-message-bubble shadow-sm">
                                            ${escapeHtml(item.message || '')}
                                        </div>
                                    </div>
                                </div>
                            `;
                            $items.append(itemHtml);
                        });
                    } else {
                        $items.append(
                            '<div class="text-muted text-center py-20 fs-6">Belum ada diskusi. Mulai percakapan terkait selisih stock ini.</div>'
                        );
                    }

                    // Show Metronic Drawer
                    const drawerEl = document.getElementById('kt_stock_opname_discussion');
                    let drawer = KTDrawer.getInstance(drawerEl);
                    if (!drawer) {
                        drawer = new KTDrawer(drawerEl);
                    }
                    drawer.show();

                    // Scroll to bottom
                    setTimeout(() => {
                        const scrollEl = document.getElementById('kt_stock_opname_discussion_scroll');
                        if (scrollEl) {
                            scrollEl.scrollTop = scrollEl.scrollHeight;
                        }
                    }, 100);
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data diskusi.'
                    });
                }
            });
        }

        function openAdjustmentModal(id, type) {
            $.ajax({
                url: `/stock-opname/${id}/edit`,
                type: 'GET',
                success: function(response) {
                    const unit = response.product?.unit?.abbreviation || 'Kg';
                    const currentStock = Number(response.real_stock || 0);

                    $('#adj-current-stock-label').text(`${currentStock} ${unit}`);
                    $('#adj-qty-input').val('1');
                    $('#adj-reason-input').val('');

                    // Store details on form
                    const $form = $('#kt_modal_stock_adjustment_form');
                    $form.data('id', id);
                    $form.data('type', type);
                    $form.data('unit', unit);
                    $form.data('current-stock', currentStock);

                    const $header = $('#adj-modal-header');
                    const $title = $('#adj-modal-title');
                    const $badge = $('#adj-type-badge');
                    const $submitBtn = $('#adj-submit-btn');

                    if (type === 'up') {
                        // Up styling
                        $header.css({
                            'background-color': '#ecfdf5',
                            'color': '#059669'
                        });
                        $title.css('color', '#059669').html(`
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                <line x1="7" y1="17" x2="17" y2="7"/>
                                <polyline points="7 7 17 7 17 17"/>
                            </svg>
                            Adjustment + Kredit
                        `);
                        $badge.text('+').css({
                            'background-color': '#ecfdf5',
                            'color': '#059669'
                        });
                        $submitBtn.css({
                            'background-color': '#5ec5a5',
                            'border-color': '#5ec5a5',
                            'color': '#fff'
                        });
                        $('#adj-reason-input').attr('placeholder', 'Contoh: Salah hitung saat audit awal');
                    } else {
                        // Down styling
                        $header.css({
                            'background-color': '#fef2f2',
                            'color': '#dc2626'
                        });
                        $title.css('color', '#dc2626').html(`
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                <line x1="7" y1="7" x2="17" y2="17"/>
                                <polyline points="17 7 17 17 7 17"/>
                            </svg>
                            Adjustment - Kredit
                        `);
                        $badge.text('-').css({
                            'background-color': '#fef2f2',
                            'color': '#dc2626'
                        });
                        $submitBtn.css({
                            'background-color': '#f87171',
                            'border-color': '#f87171',
                            'color': '#fff'
                        });
                        $('#adj-reason-input').attr('placeholder',
                            'Contoh: Koreksi buah busuk/penyusutan harian');
                    }

                    function updatePreview() {
                        const qty = parseFloat($('#adj-qty-input').val()) || 0;
                        let result = currentStock;
                        if (type === 'up') {
                            result = currentStock + qty;
                        } else {
                            result = currentStock - qty;
                        }
                        // Format preview
                        const formattedResult = Number(result % 1 === 0 ? result.toString() : result.toFixed(
                            2));
                        $('#adj-result-stock-label').text(`${formattedResult} ${unit}`);
                    }

                    $('#adj-qty-input').off('input change').on('input change', updatePreview);
                    updatePreview();

                    const modal = new bootstrap.Modal(document.getElementById('kt_modal_stock_adjustment'));
                    modal.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data produk.'
                    });
                }
            });
        }

        $('#kt_modal_add_customer').on('shown.bs.modal', function() {
            const $branch = $('#branch_id');
            const $product = $('#product_id');
            const $quantity = $('input[name="quantity"]');
            const $realStock = $('input[name="real_stock"]');
            const $form = $('#kt_modal_add_customer_form');

            if (!$form.find('input[name="_method"]').length) {
                $('#kt_modal_add_customer_header h2').text('Input Stock Fisik & Akumulasi');
                $('#kt_modal_add_customer_submit .indicator-label').html(
                    'Buat Transaksi <i class="ki-outline ki-arrow-right text-white fs-4 ms-1"></i>');
                $('textarea[name="note"]').val('');
                $('#detail-qty-container').empty();
                detailRowCount = 0;
            }

            if (!$realStock.val()) {
                $realStock.val(0);
            }

            if ($product.hasClass('select2-hidden-accessible')) {
                $product.select2('destroy');
            }

            $product.select2({
                placeholder: 'Pilih Produk',
                dropdownParent: $('#kt_modal_add_customer'),
                ajax: {
                    url: '{{ route('ajax.stock-available') }}',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term,
                        branch_id: $branch.val()
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name,
                            stock_available: item.stock_available,
                            unit_price: item.hpp || item.price || item.cost_price ||
                                item.purchase_price || 0
                        }))
                    })
                }
            }).on('select2:select', function(e) {
                const data = e.params.data;
                $quantity.val(data.stock_available || 0);
                $product.data('unit-price', data.unit_price || 0);
                updateStockSummary(data.unit_price || 0);
            });

            if ($branch.hasClass('select2-hidden-accessible')) {
                $branch.select2('destroy');
            }

            $branch.select2({
                placeholder: 'Pilih Cabang',
                dropdownParent: $('#kt_modal_add_customer'),
            });

            $branch.off('change.stockOpname').on('change.stockOpname', function() {
                const branchId = $(this).val();
                const productId = $product.val();

                $quantity.val('');
                updateStockSummary();

                if (!branchId) {
                    $product.val(null).trigger('change');
                    return;
                }

                if (!productId) {
                    return;
                }

                $.ajax({
                    url: '{{ route('ajax.stock-available') }}',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        branch_id: branchId,
                        search: ''
                    },
                    success: function(items) {
                        const selected = (items || []).find(item => String(item.id) === String(
                            productId));

                        if (selected) {
                            $quantity.val(selected.stock_available || 0);
                            $product.data('unit-price', selected.hpp || selected.price ||
                                selected.cost_price ||
                                selected.purchase_price || 0);
                            updateStockSummary();
                            return;
                        }

                        $product.val(null).trigger('change');
                    },
                    error: function() {
                        $product.val(null).trigger('change');
                    }
                });
            });

            $realStock.off('input.stockSummary change.stockSummary').on('input.stockSummary change.stockSummary',
                function() {
                    updateStockSummary();
                });

            updateStockSummary();
        });

        let detailRowCount = 0;

        function addDetailRow(name = '', qty = '') {
            const container = $('#detail-qty-container');
            const rowHtml = `
                <div class="d-flex align-items-center gap-3 detail-row" id="detail-row-${detailRowCount}">
                    <input type="text" class="form-control" name="details[${detailRowCount}][name]" value="${name}" placeholder="Misal: Keranjang A" required>
                    <div class="input-group" style="width: 150px;">
                        <input type="number" step="0.01" class="form-control detail-qty" name="details[${detailRowCount}][qty]" value="${qty}" placeholder="Qty" required oninput="calculateRealStock()">
                        <span class="input-group-text">Kg</span>
                    </div>
                    <button type="button" class="btn btn-icon btn-light-danger" onclick="removeDetailRow(${detailRowCount})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            container.append(rowHtml);
            detailRowCount++;
            calculateRealStock();
        }

        function removeDetailRow(id) {
            $(`#detail-row-${id}`).remove();
            calculateRealStock();
        }

        function calculateRealStock() {
            let total = 0;
            $('.detail-qty').each(function() {
                const val = parseFloat($(this).val());
                if (!isNaN(val)) {
                    total += val;
                }
            });
            $('input[name="real_stock"]').val(total);
            updateStockSummary();
        }

        function finalizeStockOpname(id) {
            Swal.fire({
                title: 'Konfirmasi Finalisasi',
                text: 'Stock saat ini akan dikunci dan selisih akan disimpan. Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Finalisasi!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/stock-opname/${id}/finalize`,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil difinalisasi.',
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
                                    'Terjadi kesalahan saat memproses data.'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection
@endsection
