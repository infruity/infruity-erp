@extends('template.root')
@section('title', 'Pesanan')

@php
    $orderBookFilterBranches = $branches->map(static fn ($branch) => [
        'id' => (string) $branch->id,
        'name' => ucwords((string) $branch->name),
    ])->values()->all();
@endphp

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/order-book-filter-responsive.css') }}?v={{ filemtime(public_path('assets/css/order-book-filter-responsive.css')) }}">
@include('pos::order-book.partials.detail')
<style>
    :where(#order-book-filter-sheet, #order-book-filter-sheet *) { border-width:0; border-style:solid; border-color:#e5e7eb; }
    :where(#order-book-filter-sheet button) { padding:0; background-color:transparent; color:inherit; }
    :where(#order-book-filter-sheet input) { color:inherit; }
    #order-book-filter-sheet :is(h2, h3, h4, p) { margin-bottom:0; }
    #order-book-filter-sheet :is(h3, h4) { color:#6b7280; line-height:1.5; }
    .order-book-workspace .order-book-toolbar-controls { display:contents; }
    .order-book-workspace .order-book-mobile-search { padding:0; background:transparent; }
    @media (min-width:768px) {
        .order-book-workspace .order-book-mobile-search { display:contents; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search { display:contents; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__icon,
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__input,
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__clear,
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__divider { display:none; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__filter.order-book-filter-trigger { display:inline-flex; width:auto; min-width:44px; height:44px; min-height:44px; align-items:center; justify-content:center; flex:none; gap:8px; border:1px solid var(--erp-border); border-radius:999px; padding:10px 18px; background:white; color:#4b5563; font:600 13px/1.4 var(--erp-font); text-decoration:none; cursor:pointer; }
    }
    .order-book-workspace .order-book-filter-trigger .icon-rounded { font-family:'Material Symbols Rounded'; font-size:20px; font-weight:300; line-height:1; }
    .order-book-workspace .order-book-record__summary { display:flex; min-width:102px; max-width:124px; flex:none; flex-direction:column; align-items:flex-end; gap:2px; margin-left:8px; text-align:right; }
    .order-book-workspace .order-book-record__status { color:#6b7280; font-size:13px; font-weight:700; line-height:18px; }
    .order-book-workspace .order-book-record__status--process { color:#f97316; }
    .order-book-workspace .order-book-record__status--done { color:#059669; }
    .order-book-workspace .order-book-record__invoice { max-width:124px; overflow-wrap:anywhere; color:#6b7280; font-size:12px; line-height:18px; }

    @media (max-width:767px) {
        .order-book-workspace { width:100vw; margin-inline:calc(50% - 50vw); }
        .order-book-workspace .order-book-filter-label { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
        .order-book-workspace .erp-workspace-heading { display:none; }
        .order-book-workspace .erp-workspace-toolbar { position:sticky; top:0; z-index:25; background:#fff; }
        .order-book-workspace .erp-workspace-toolbar .order-book-toolbar-controls { position:relative; display:flex; width:100%; min-width:0; flex:1 1 100%; align-items:center; }
        .order-book-workspace .erp-workspace-toolbar .order-book-mobile-search .erp-mobile-search { min-width:0; height:44px; }
        .order-book-workspace .order-book-create,
        .order-book-workspace .erp-record-controls { display:none; }
        .order-book-workspace .erp-record__head { min-height:60px; gap:12px; padding:9px 16px; }
        .order-book-workspace .erp-record__avatar { width:36px; height:36px; border-radius:12px; font-size:14px; font-weight:700; }
        .order-book-workspace .erp-record__title { font-size:14px; line-height:20px; }
        .order-book-workspace .erp-record__meta { margin-top:4px; font-size:12px; line-height:18px; }
        .order-book-workspace .erp-record__chevron { display:none; }
        .order-book-workspace .erp-record__details { padding:0 16px 16px; }
        /* Rows follow the Pesanan reference (measured at 400px): 61px rows, 12px name, creator under the status. */
        .order-book-workspace .erp-workspace-toolbar { padding:16px; }
        .order-book-workspace .erp-workspace-toolbar .order-book-mobile-search .erp-mobile-search { gap:0; padding:0 6px 0 16px; box-shadow:none; border-color:rgba(229,231,235,.8); }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__icon { margin-right:10px; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__divider { height:16px; margin:0 4px; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__input { font-weight:500; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__filter .icon-rounded { font-family:'Material Symbols Rounded'; font-size:20px; font-weight:300; line-height:1; }
        .order-book-workspace .erp-record__head { min-height:60px; padding:12px 16px; }
        .order-book-workspace .erp-record__title { font-size:12px; font-weight:600; line-height:18px; }
        .order-book-workspace .order-book-record__phone { margin-left:2px; color:#9ca3af; font-size:11px; font-weight:400; }
        .order-book-workspace .erp-record__meta { margin-top:2px; color:#9ca3af; font-size:11px; line-height:16.5px; }
        .order-book-workspace .order-book-record__summary { width:120px; min-width:120px; max-width:120px; align-self:flex-start; margin:1.8px 0 0 12px; gap:2px; }
        .order-book-workspace .order-book-record__status { font-size:12px; font-weight:600; line-height:16px; }
        .order-book-workspace .order-book-record__invoice { max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:11px; line-height:16.5px; }
        /* Pesanan reference: 61px rows (60 + 1 border), 12px initials, bold status, grey filter icon. */
        .order-book-workspace .erp-record__head { height:60px; min-height:60px; box-sizing:border-box; }
        .order-book-workspace .erp-record__avatar { font-size:12px; font-weight:700; }
        .order-book-workspace .order-book-record__status { font-weight:700; }
        .order-book-workspace .order-book-mobile-search .erp-mobile-search__filter { color:#9ca3af; }
        .order-book-workspace .erp-record__avatar { position:relative; overflow:visible; }
        .order-book-workspace .order-book-record__parcel { position:absolute; right:-4px; bottom:-4px; display:flex; width:18px; height:18px; align-items:center; justify-content:center; border-radius:999px; background:#fff; box-shadow:0 0 0 1px #fff, 0 1px 2px rgba(0,0,0,.12); }
        .order-book-workspace .order-book-record__parcel i { display:flex; width:14px; height:14px; align-items:center; justify-content:center; border-radius:999px; background:#f59e0b; color:#fff; font-size:9px; }
    }
</style>
<x-erp.workspace count-table="pos-table" count-noun="pesanan" class="order-book-workspace" title="Pesanan" subtitle="Kelola pesanan dan lihat rincian transaksi."
    x-data="orderBookFilterSheet()"
    @erp-filter-apply.window="if ($event.detail.id === 'order-book') applyFilters($event.detail.values)"
    @erp-filter-close.window="if ($event.detail.id === 'order-book') closeFilter()"><x-slot:toolbar><div class="order-book-toolbar-controls"><div class="erp-search d-none d-md-flex"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search-desktop" data-kt-ecommerce-product-filter="search" aria-label="Cari pesanan" placeholder="Cari..."></div><x-erp.mobile-search class="order-book-mobile-search erp-mobile-search-shell--fill"><x-slot:input><input type="search" id="search" data-kt-ecommerce-product-filter="search" aria-label="Cari pesanan" placeholder="Cari..." class="erp-mobile-search__input"></x-slot:input><x-slot:filter><button type="button" class="erp-mobile-search__filter order-book-filter-trigger" aria-label="Filter pesanan" aria-haspopup="dialog" aria-controls="order-book-filter-sheet" :aria-expanded="filterOpen" :class="hasActiveFilters ? 'is-active' : ''" x-ref="filterTrigger" @click="openFilter()"><span class="icon-rounded" aria-hidden="true">tune</span><span id="active-branch-button-label" class="order-book-filter-label">Filter</span></button></x-slot:filter></x-erp.mobile-search></div><div hidden aria-hidden="true">
                            <select id="order-book-status-filter" data-kt-ecommerce-product-filter="status">
                                <option value="all">Semua Status</option>
                                <option value="draft">Pending</option>
                                <option value="process">Proses</option>
                                <option value="done">Selesai</option>
                            </select>
                            <select id="order-book-branch-filter" data-kt-ecommerce-product-filter="cabang">
                                <option value="all">Semua Cabang</option>
                                @foreach ($orderBookFilterBranches as $branch)
                                    <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="kt_ecommerce_sales_flatpickr" value="">
                        </div>@if(check_access('order-book.create'))<a href="{{ url('order-book/create') }}" class="erp-action erp-action--primary order-book-create"><i class="ph-bold ph-plus" aria-hidden="true"></i>Tambah Pesanan</a>@endif</x-slot:toolbar>
<x-erp.collection table="pos-table" empty-title="Belum ada data pesanan" empty-noun="pesanan baru" :create-url="url('order-book/create')" create-label="Tambah Pesanan"><table class="table align-middle table-row-dashed fs-6 gy-5" id="pos-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="text-start min-w-150px">Nama Pelanggan</th>
                            <th class="text-start min-w-150px">Tanggal Proses</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table></x-erp.collection>
    <x-erp.filter-sheet
        id="order-book"
        title="Filter Pesanan"
        subtitle="Saring cabang, status, dan periode tanggal pesanan"
        aria-label="Filter pesanan"
        branch-label="Cabang Tujuan"
        branch-all-label="Semua Cabang"
        status-label="Status Pesanan"
        :branches="$orderBookFilterBranches"
        :statuses="['all' => 'Semua Status', 'draft' => 'Pending', 'process' => 'Proses', 'done' => 'Selesai']"
        :timezone="config('app.timezone')"
        week-start="monday"
        desktop="modal"
        reset-applies
        reset-label="Reset Filter"
        apply-label="Terapkan Filter"
    />
</x-erp.workspace>

@section('script')
    <script type="text/javascript">
        var dataTable;
        const orderBookReferenceUi = (() => {
            const months = {
                jan: 'Jan', feb: 'Feb', mar: 'Mar', apr: 'Apr', may: 'Mei', jun: 'Jun',
                jul: 'Jul', aug: 'Agu', sep: 'Sep', oct: 'Okt', nov: 'Nov', dec: 'Des'
            };

            function initials(name) {
                const words = String(name || '').replace(/\s*\([^)]*\)\s*$/, '').match(/[A-Za-z0-9À-ÖØ-öø-ÿ]+/g) || [];
                if (words.length > 1) return words.slice(0, 2).map(word => Array.from(word)[0]).join('').toUpperCase();
                return Array.from(words[0] || '').slice(0, 2).join('').toUpperCase() || '—';
            }

            function formatDate(value) {
                return String(value || '').trim()
                    .replace(/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/gi, month => months[month.toLowerCase()] || month)
                    .replace(/,?\s+(\d{2}:\d{2})$/, ', $1');
            }

            function status(value) {
                const label = String(value || '').replace(/<[^>]*>/g, '').trim();
                const normalized = label.toLowerCase();
                return ({
                    pending: 'Pending', process: 'Proses', posting: 'Proses',
                    proses: 'Proses', done: 'Selesai', selesai: 'Selesai',
                    draft: 'Pending', temp: 'Pending'
                })[normalized] || label || 'Pending';
            }

            function invoice(statusLabel, pos, fallback) {
                if (statusLabel === 'Pending' || statusLabel === 'Draft') return '-';
                const posNumber = pos && typeof pos === 'object' ? String(pos.invoice_number || '').trim() : '';
                const fallbackNumber = String(fallback || '').trim();
                return posNumber || fallbackNumber || '-';
            }

            return { initials, formatDate, status, invoice };
        })();

        // The sheet itself is the shared filter-sheet component (id "order-book"). This state only opens
        // it with the values the list is using and writes the chosen ones back to the request controls.
        function orderBookFilterSheet() {
            const controls = () => ({
                status: document.querySelector('[data-kt-ecommerce-product-filter="status"]'),
                branch: document.querySelector('[data-kt-ecommerce-product-filter="cabang"]'),
                range: document.getElementById('kt_ecommerce_sales_flatpickr')
            });

            return {
                filterOpen: false,
                hasActiveFilters: false,

                init() {
                    this.hasActiveFilters = this.hasCriteria(this.appliedValues());
                },

                appliedValues() {
                    const { status, branch, range } = controls();
                    const dates = String(range?.value || '').split(' to ');

                    return {
                        status: status?.value || 'all',
                        branch: branch?.value || 'all',
                        start: dates[0] || '',
                        end: dates[1] || dates[0] || ''
                    };
                },

                hasCriteria(values) {
                    return values.status !== 'all' || values.branch !== 'all' || Boolean(values.start);
                },

                openFilter() {
                    this.filterOpen = true;
                    this.$dispatch('erp-filter-open', { id: 'order-book', values: this.appliedValues() });
                },

                closeFilter() {
                    this.filterOpen = false;
                },

                applyFilters(values) {
                    const { status, branch, range } = controls();
                    // A single picked day is a one-day range.
                    const start = values.start || '';
                    const end = start ? (values.end || start) : '';

                    if (status) status.value = values.status;
                    if (branch) branch.value = values.branch;
                    if (range) range.value = start ? `${start} to ${end}` : '';
                    this.hasActiveFilters = this.hasCriteria({ status: values.status, branch: values.branch, start });
                    document.dispatchEvent(new CustomEvent('order-book:filters-applied'));
                }
            };
        }

        $(document).ready(function() {
            const $branchFilter = $('[data-kt-ecommerce-product-filter="cabang"]');
            const $activeBranchButtonLabel = $('#active-branch-button-label');

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            dataTable = $('#pos-table').DataTable({
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
                    url: "{{ route('order-book.data') }}",
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
                    [1, 'desc'], // Then by order_date ASC (kolom ke-3)
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
            // Search manual lewat input
            const $searchInputs = $('[data-kt-ecommerce-product-filter="search"]');
            $searchInputs.on('input', function() {
                $searchInputs.not(this).val(this.value);
                dataTable.search(this.value).draw();
            });

            $('[data-kt-ecommerce-product-filter="status"]').on('change', function() {
                dataTable.draw(); // trigger fetch ulang dari server
            });

            $('[data-kt-ecommerce-product-filter="cabang"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw(); // trigger fetch ulang dari server
            });

            document.addEventListener('order-book:filters-applied', function() {
                updateActiveFilterInfo();
                dataTable.draw();
            });

            updateActiveFilterInfo();

            const orderBookCollection = document.querySelector('[data-erp-collection="pos-table"]');
            let orderBookRecordList = null;
            let orderBookListObserver = null;
            let orderBookRenderScheduled = false;

            function decorateOrderBookRecords() {
                if (!orderBookRecordList) return;

                const rows = Array.from(document.querySelectorAll('#pos-table tbody tr'))
                    .filter(row => !row.classList.contains('child') && row.cells.length > 1 && row.style.display !== 'none');
                const records = Array.from(orderBookRecordList.querySelectorAll(':scope > article.erp-record'));

                records.forEach((record, index) => {
                    const row = rows[index];
                    const title = record.querySelector('.erp-record__title');
                    const avatar = record.querySelector('.erp-record__avatar');
                    const meta = record.querySelector('.erp-record__meta');
                    const head = record.querySelector('.erp-record__head');
                    if (!row || !title || !avatar || !meta || !head) return;

                    const dateCell = row.cells[1];
                    const date = dateCell.querySelector('.text-muted')?.textContent.trim() || '';
                    const statusText = dateCell.querySelector('.badge')?.textContent.trim() || '';
                    const rowData = dataTable.row(row).data() || {};
                    const customerName = title.textContent.trim();
                    const formattedDate = orderBookReferenceUi.formatDate(date);
                    const status = orderBookReferenceUi.status(statusText || rowData.status);
                    const invoice = orderBookReferenceUi.invoice(status, rowData.pos, rowData.invoice_number);
                    const signature = JSON.stringify([index, customerName, formattedDate, status, invoice]);
                    if (record.dataset.orderBookSignature === signature) return;
                    record.dataset.orderBookSignature = signature;
                    record.classList.add('order-book-record');
                    Array.from(avatar.classList).filter(name => name.startsWith('erp-tone-')).forEach(name => avatar.classList.remove(name));
                    avatar.classList.add(ErpTone.customerClassName(customerName));
                    avatar.textContent = orderBookReferenceUi.initials(customerName);
                    avatar.setAttribute('aria-hidden', 'true');
                    avatar.querySelector(':scope > .order-book-record__parcel')?.remove();
                    if (rowData.has_parcel) {
                        const badge = document.createElement('span');
                        badge.className = 'order-book-record__parcel';
                        badge.title = 'Ada Parcel';
                        badge.innerHTML = '<i class="ph-fill ph-gift" role="img" aria-label="Ada Parcel"></i>';
                        avatar.append(badge);
                    }
                    meta.textContent = formattedDate;
                    const phoneMatch = customerName.match(/^(.*?)\s*(\(\d{3,4}\))$/);
                    if (phoneMatch && !title.querySelector('.order-book-record__phone')) {
                        const phone = document.createElement('span');
                        phone.className = 'order-book-record__phone';
                        phone.textContent = phoneMatch[2];
                        title.textContent = phoneMatch[1];
                        title.append(phone);
                    }

                    let summary = head.querySelector(':scope > .order-book-record__summary');
                    if (!summary) {
                        summary = document.createElement('span');
                        summary.className = 'order-book-record__summary';
                        const statusValue = document.createElement('span');
                        statusValue.className = 'order-book-record__status';
                        const invoiceValue = document.createElement('span');
                        invoiceValue.className = 'order-book-record__invoice';
                        summary.append(statusValue, invoiceValue);
                        head.append(summary);
                    }
                    const statusNode = summary.querySelector('.order-book-record__status');
                    const invoiceNode = summary.querySelector('.order-book-record__invoice');
                    statusNode.className = `order-book-record__status order-book-record__status--${status === 'Selesai' ? 'done' : status === 'Proses' ? 'process' : 'pending'}`;
                    statusNode.textContent = status;
                    const staff = String(rowData.created_by_name || rowData.user?.nm_user || '').toLowerCase().replace(/\b\w/g, letter => letter.toUpperCase()) || '-';
                    invoiceNode.textContent = staff;
                    head.setAttribute('aria-label', `Pesanan ${customerName}. ${formattedDate}. Status ${status}. Dibuat oleh ${staff}. Buka detail dan aksi.`);
                });
            }

            function scheduleOrderBookRecords() {
                if (orderBookRenderScheduled) return;
                orderBookRenderScheduled = true;
                window.requestAnimationFrame(() => {
                    orderBookRenderScheduled = false;
                    decorateOrderBookRecords();
                });
            }

            if (orderBookCollection) {
                const observeOrderBookList = () => {
                    const nextList = orderBookCollection.querySelector(':scope > .erp-record-list');
                    if (!nextList || nextList === orderBookRecordList) return;
                    orderBookListObserver?.disconnect();
                    orderBookRecordList = nextList;
                    orderBookListObserver = new MutationObserver(scheduleOrderBookRecords);
                    orderBookListObserver.observe(orderBookRecordList, { childList: true });
                    scheduleOrderBookRecords();
                };

                new MutationObserver(observeOrderBookList).observe(orderBookCollection, { childList: true });
                observeOrderBookList();
                $('#pos-table').on('draw.dt', scheduleOrderBookRecords);
            }
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
                title: 'Selesaikan Pesanan?',
                text: 'Data yang dirubah tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, selesaikan!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/other-book/set-selesai/${id}`, // Ganti dengan URL yang sesuai
                        type: 'PUT',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil diselesaikan.',
                                showConfirmButton: false,
                                timer: 1500
                            });

                            // Reload DataTable setelah berhasil selesaikan data
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat selesaikan data.'
                            });
                        }
                    });
                }
            });
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
                        url: `/order-book/${id}`, // Ganti dengan URL yang sesuai
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
