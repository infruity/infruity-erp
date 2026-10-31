@extends('template.root')
@section('title', 'Pengiriman')

@section('content')
<style>
.delivery-order-filter-chips { display:flex; flex-wrap:wrap; gap:6px; }
.delivery-order-filter-chip { border:1px solid #e5e7eb; border-radius:999px; padding:6px 10px; background:#fff; color:#4b5563; font:500 12px/1.35 var(--erp-font); cursor:pointer; transition:background .15s,border-color .15s,color .15s; }
.delivery-order-filter-chip:hover { border-color:#99f6e4; color:#0f766e; }
.delivery-order-filter-chip.is-active { border-color:#a7f3d0; background:#ecfdf5; color:#047857; font-weight:600; }
.delivery-order-filter-chip:focus-visible { outline:2px solid #0f766e; outline-offset:2px; }
.delivery-order-detail-modal { position:fixed; inset:0; z-index:1200; display:flex; align-items:center; justify-content:center; padding:16px; }
.delivery-order-detail-modal[hidden] { display:none !important; }
.delivery-order-detail-modal__backdrop { position:absolute; inset:0; border:0; padding:0; background:rgba(0,0,0,.6); }
.delivery-order-detail-modal__panel { position:relative; z-index:1; display:flex; width:min(100%,384px); max-height:calc(100vh - 32px); max-height:calc(100dvh - 32px); flex-direction:column; overflow:hidden; border-radius:16px; background:#fff; color:#1f2937; box-shadow:0 24px 64px rgba(15,23,42,.25); }
.delivery-order-detail-modal__header { flex:none; padding:20px 20px 16px; }
.delivery-order-detail-modal__title { margin:0; color:#24364f; font:700 18px/1.4 var(--erp-font); }
.delivery-order-detail-modal__body { display:flex; min-height:0; flex:1 1 auto; flex-direction:column; gap:14px; overflow-y:auto; padding:4px 20px 16px; overscroll-behavior:contain; }
.delivery-order-detail-modal__row { display:flex; min-width:0; align-items:center; gap:12px; }
.delivery-order-detail-modal__icon { display:flex; width:40px; height:40px; flex:0 0 40px; align-items:center; justify-content:center; border:1px solid rgba(167,243,208,.8); border-radius:12px; background:#ecfdf5; color:#0b595b; font-size:18px; box-shadow:0 1px 2px rgba(0,0,0,.05); }
.delivery-order-detail-modal__copy { min-width:0; flex:1; }
.delivery-order-detail-modal__value { display:block; color:#24364f; font:700 14px/1.35 var(--erp-font); }
.delivery-order-detail-modal__label { display:block; margin-top:3px; color:#8da0ba; font:500 11px/1.35 var(--erp-font); }
.delivery-order-detail-modal__link { max-width:100%; overflow:hidden; color:#0b9a80; text-decoration:none; text-overflow:ellipsis; white-space:nowrap; }
.delivery-order-detail-modal__link--dashed { border-bottom:1px dashed currentColor; padding-bottom:2px; }
.delivery-order-detail-modal__link--wrap { overflow:visible; overflow-wrap:anywhere; text-overflow:clip; white-space:normal; }
.delivery-order-detail-modal__link--phone { color:#24364f; border-bottom-color:#d1d5db; font:600 12px/1.35 var(--erp-font); }
.delivery-order-detail-modal__link[aria-disabled="true"] { color:#8da0ba; border:0; pointer-events:none; }
.delivery-order-detail-modal__footer { flex:none; border-top:1px solid rgba(148,163,184,.2); padding:12px 16px 16px; }
.delivery-order-detail-modal__action { width:100%; min-height:44px; border:0; border-radius:15px; background:#0b595b; color:#fff; font:700 15px/1.2 var(--erp-font); cursor:pointer; box-shadow:0 5px 12px rgba(11,89,91,.18); transition:background .15s,transform .15s; }
.delivery-order-detail-modal__action:hover { background:#08484a; }
.delivery-order-detail-modal__action:active { transform:scale(.99); }
.delivery-order-detail-modal__action:focus-visible { outline:3px solid #2dd4bf; outline-offset:2px; }
@media (max-width:767.98px) {
    body:has(#delivery-order-page) #kt_content { padding:0!important; scroll-padding-bottom:0; }
    body:has(#delivery-order-page) #kt_content_container { display:flex; min-height:0; flex:1; flex-direction:column; padding:0!important; }
    #delivery-order-page { min-height:0; flex:1; border-radius:0; }
    #delivery-order-page .erp-workspace-content { min-height:0; }
    #delivery-order-page .erp-workspace-heading { display:none; }
    #delivery-order-page .erp-workspace-toolbar { position:relative; gap:0; padding:16px; }
    #delivery-order-page .erp-workspace-toolbar .erp-search { flex:1 0 100%; min-width:0; padding-right:68px; }
    #delivery-order-page .erp-workspace-toolbar .erp-search > input { font-size:16px; }
    #delivery-order-page .delivery-order-filter-trigger { position:absolute; top:16px; right:16px; width:52px; min-height:44px; gap:0; border:0; border-left:1px solid var(--erp-border); border-radius:0; padding:0; background:transparent; box-shadow:none; color:var(--erp-muted); }
    #delivery-order-page .delivery-order-filter-trigger:hover { background:transparent; color:var(--erp-primary); }
    #delivery-order-page .delivery-order-filter-trigger > i { font-size:20px; }
    #delivery-order-page .delivery-order-filter-trigger > span { display:none; }
    #delivery-order-page .erp-record-controls { display:none; }
    .erp-collection[data-erp-collection="pos-table"] .delivery-order-card__head { width:100%; min-height:61px; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; border:0; border-radius:0; background:#fff; color:#1f2937; text-align:left; font:inherit; }
    .erp-collection[data-erp-collection="pos-table"] .delivery-order-card__head:hover { background:#f9fafb; }
    .delivery-order-card__customer { display:flex; min-width:0; flex:1; align-items:center; gap:12px; }
    .delivery-order-card__avatar { position:relative; display:flex; width:36px; height:36px; flex:0 0 36px; align-items:center; justify-content:center; border:1px solid; border-radius:12px; font:700 12px/1 var(--erp-font); }
    .delivery-order-card__avatar[data-avatar-theme="emerald"] { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
    .delivery-order-card__avatar[data-avatar-theme="blue"] { border-color:#bfdbfe; background:#eff6ff; color:#1d4ed8; }
    .delivery-order-card__avatar[data-avatar-theme="purple"] { border-color:#e9d5ff; background:#faf5ff; color:#7e22ce; }
    .delivery-order-card__avatar[data-avatar-theme="amber"] { border-color:#fde68a; background:#fffbeb; color:#b45309; }
    .delivery-order-card__avatar[data-avatar-theme="teal"] { border-color:#99f6e4; background:#f0fdfa; color:#0f766e; }
    .delivery-order-card__avatar[data-avatar-theme="rose"] { border-color:#fecdd3; background:#fff1f2; color:#be123c; }
    .delivery-order-card__avatar[data-avatar-theme="indigo"] { border-color:#c7d2fe; background:#eef2ff; color:#4338ca; }
    .delivery-order-card__payment { position:absolute; right:-5px; bottom:-4px; display:flex; width:16px; height:16px; align-items:center; justify-content:center; border:2px solid #fff; border-radius:50%; color:#fff; font-size:9px; box-shadow:0 1px 2px rgb(15 23 42 / 16%); }
    .delivery-order-card__payment[data-payment-state="paid"] { background:#059669; }
    .delivery-order-card__payment[data-payment-state="unpaid"] { background:#f43f5e; }
    .delivery-order-card__payment i { font-size:8px; line-height:1; }
    .delivery-order-card__copy { display:flex; min-width:0; flex:1; flex-direction:column; }
    .delivery-order-card__title { display:block; overflow:hidden; color:#1f2937; font-size:12px; font-weight:600; line-height:16px; text-overflow:ellipsis; white-space:nowrap; }
    .delivery-order-card__phone { color:#9ca3af; font-size:11px; font-weight:400; }
    .delivery-order-card__amount { margin-top:2px; color:#6b7280; font-size:11px; font-weight:400; line-height:16px; }
    .delivery-order-card__status { display:flex; min-width:0; max-width:42%; flex:none; flex-direction:column; align-items:flex-end; text-align:right; }
    .delivery-order-card__delivery { overflow:hidden; font-size:12px; font-weight:600; line-height:16px; text-overflow:ellipsis; white-space:nowrap; }
    .delivery-order-card__delivery[data-delivery-state="received"] { color:#059669; }
    .delivery-order-card__delivery[data-delivery-state="unreceived"] { color:#e11d48; }
    .delivery-order-card__courier { max-width:120px; margin-top:2px; overflow:hidden; color:#6b7280; font-size:11px; font-weight:400; line-height:16px; text-overflow:ellipsis; white-space:nowrap; }
}
</style>
<x-erp.workspace id="delivery-order-page" title="Pengiriman" subtitle="Kelola pengiriman dan lihat rincian transaksi."><x-slot:toolbar><div class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search" data-kt-ecommerce-product-filter="search" aria-label="Cari nama pelanggan, kurir, atau 4 digit HP" placeholder="Cari..."></div><a href="{{ route('pos.index') }}" class="erp-action erp-action--primary d-none d-sm-inline-flex" aria-label="Tambah pengiriman lewat POS"><i class="ph ph-plus" aria-hidden="true"></i><span>Tambah Pengiriman</span></a><button type="button" class="erp-action delivery-order-filter-trigger" data-delivery-order-filter-trigger data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" aria-label="Filter pengiriman"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span id="active-branch-button-label">Filter</span></button><div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px delivery-order-filter-menu"
                            data-kt-menu="true">
                            <!--begin::Header-->
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Pilihan Filter</div>
                                <div class="fs-7 text-muted mt-1">Atur penerimaan, pembayaran, cabang, dan tanggal pengiriman.</div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Separator-->
                            <div class="separator border-gray-200"></div>
                            <!--end::Separator-->
                            <div class="px-7 py-2">
                                <div class="mb-4">
                                    <p class="form-label fs-6 fw-semibold mb-2">Status Penerimaan</p>
                                    <div class="delivery-order-filter-chips" role="group" aria-label="Status Penerimaan">
                                        <button type="button" class="delivery-order-filter-chip is-active" data-delivery-order-filter="delivery" data-filter-value="all" aria-pressed="true">Semua Status</button>
                                        <button type="button" class="delivery-order-filter-chip" data-delivery-order-filter="delivery" data-filter-value="unreceived" aria-pressed="false">Belum Diterima</button>
                                        <button type="button" class="delivery-order-filter-chip" data-delivery-order-filter="delivery" data-filter-value="received" aria-pressed="false">Sudah Diterima</button>
                                    </div>
                                </div>
                                <div>
                                    <p class="form-label fs-6 fw-semibold mb-2">Status Pembayaran</p>
                                    <div class="delivery-order-filter-chips" role="group" aria-label="Status Pembayaran">
                                        <button type="button" class="delivery-order-filter-chip is-active" data-delivery-order-filter="payment" data-filter-value="all" aria-pressed="true">Semua</button>
                                        <button type="button" class="delivery-order-filter-chip" data-delivery-order-filter="payment" data-filter-value="unpaid" aria-pressed="false">Belum Lunas</button>
                                        <button type="button" class="delivery-order-filter-chip" data-delivery-order-filter="payment" data-filter-value="paid" aria-pressed="false">Sudah Lunas</button>
                                    </div>
                                </div>
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
                        </div></x-slot:toolbar><x-erp.collection table="pos-table"><table class="table align-middle table-row-dashed fs-6 gy-5" id="pos-table" width="100%">
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
                </table></x-erp.collection></x-erp.workspace>
<div id="delivery-order-detail-modal" class="delivery-order-detail-modal" hidden aria-hidden="true">
    <button type="button" class="delivery-order-detail-modal__backdrop" data-delivery-order-detail-close tabindex="-1" aria-label="Tutup detail pengiriman"></button>
    <section class="delivery-order-detail-modal__panel" role="dialog" aria-modal="true" aria-labelledby="delivery-order-detail-title" tabindex="-1">
        <header class="delivery-order-detail-modal__header">
            <h2 id="delivery-order-detail-title" class="delivery-order-detail-modal__title">Detail Pengiriman</h2>
        </header>
        <div class="delivery-order-detail-modal__body">
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-user text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <span class="delivery-order-detail-modal__value" data-delivery-order-detail-field="customer"></span>
                    <a class="delivery-order-detail-modal__value delivery-order-detail-modal__link delivery-order-detail-modal__link--dashed delivery-order-detail-modal__link--wrap delivery-order-detail-modal__link--phone" data-delivery-order-detail-field="phone" target="_blank" rel="noopener noreferrer"></a>
                </div>
            </div>
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-map-pin text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <a class="delivery-order-detail-modal__value delivery-order-detail-modal__link delivery-order-detail-modal__link--dashed" data-delivery-order-detail-field="address" target="_blank" rel="noopener noreferrer"></a>
                    <span class="delivery-order-detail-modal__label">Alamat Pengiriman (Klik untuk Buka Gmaps)</span>
                </div>
            </div>
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-bag text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <span class="delivery-order-detail-modal__value" data-delivery-order-detail-field="subtotal"></span>
                    <span class="delivery-order-detail-modal__label">Total Belanja</span>
                </div>
            </div>
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-moped text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <span class="delivery-order-detail-modal__value" data-delivery-order-detail-field="shipping-fee"></span>
                    <span class="delivery-order-detail-modal__label">Ongkos Kirim Kurir</span>
                </div>
            </div>
            <div class="delivery-order-detail-modal__row">
                <span class="delivery-order-detail-modal__icon" aria-hidden="true"><i class="ph-duotone ph-receipt text-lg"></i></span>
                <div class="delivery-order-detail-modal__copy">
                    <span class="delivery-order-detail-modal__value" data-delivery-order-detail-field="grand-total"></span>
                    <span class="delivery-order-detail-modal__label">Total Belanja (Include Ongkir)</span>
                </div>
            </div>
        </div>
        <footer class="delivery-order-detail-modal__footer">
            <button type="button" class="delivery-order-detail-modal__action" data-delivery-order-detail-action>Tutup</button>
        </footer>
    </section>
</div>
<div class="modal fade" id="kt_modal_add_customer" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog erp-sheet-dialog modal-dialog-scrollable mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content rounded-4 shadow-sm">
                <!--begin::Form-->
                <form class="form" action="{{ url(Request::segment(1)) . '/update-courier' }}"
                    id="kt_modal_add_customer_form" data-kt-redirect="#">
                    @csrf
                    <!--begin::Modal header-->
                    <div class="erp-sheet-header border-0 pb-0" id="kt_modal_add_customer_header">
                        <!--begin::Modal title-->
                        <div>
                            <h2 class="fw-bold mb-1">Pilih Kurir</h2>
                            <div class="fs-7 text-muted">Tentukan staff yang bertugas menerima order pengiriman.</div>
                        </div>
                        <!--end::Modal title-->
                        <!--begin::Close-->
                        <button type="button" aria-label="Tutup formulir" id="kt_modal_add_customer_close" class="btn btn-icon btn-sm btn-active-icon-primary">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                        <!--end::Close-->
                    </div>
                    <!--end::Modal header-->
                    <!--begin::Modal body-->
                    <div class="erp-sheet-body py-7 px-5 px-lg-10">
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
                    <div class="erp-sheet-footer flex-center border-0 pt-0 delivery-order-modal-footer">
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
@section('script')
    <script src="{{ asset('assets/js/delivery-order-mobile.js') }}"></script>
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
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                searchDelay: 300,
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
                        d.delivery_status_filter = $('[data-delivery-order-filter="delivery"][aria-pressed="true"]').attr('data-filter-value') || 'all';
                        d.payment_status_filter = $('[data-delivery-order-filter="payment"][aria-pressed="true"]').attr('data-filter-value') || 'all';
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
            if (window.DeliveryOrderMobileCards) {
                window.DeliveryOrderMobileCards.attach(dataTable);
            }

            let searchTimer;
            $('#search').on('input', function() {
                clearTimeout(searchTimer);
                const query = this.value;
                searchTimer = setTimeout(function() {
                    dataTable.search(query).draw();
                }, 250);
            });

            $('[data-delivery-order-filter]').on('click', function() {
                const group = this.getAttribute('data-delivery-order-filter');
                const value = this.getAttribute('data-filter-value');
                $(`[data-delivery-order-filter="${group}"]`).each(function() {
                    const active = this.getAttribute('data-filter-value') === value;
                    this.classList.toggle('is-active', active);
                    this.setAttribute('aria-pressed', String(active));
                });
                dataTable.draw();
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
                    <input type="text" inputmode="numeric" id="nominalBayar" class="swal2-input" placeholder="Masukkan nominal">
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
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                        responsive: true,
                        searching: true,
                        paging: true,
                        lengthChange: false,

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
