@extends('layouts.erp')
@section('title', 'Stok Opname')
@section('page-title', 'Stok Opname')
@section('page-breadcrumb')
    <span>Inventaris</span><i class="ph ph-caret-right text-[0.55rem]" aria-hidden="true"></i><span>Stok Opname</span>
@endsection
@section('dashboard-fullscreen', '1')
@push('styles')
    @vite('resources/css/flatpickr.css')
@endpush

@section('content')
    <div class="stock-opname-page">
        <style>
            .stock-opname-page { font-family:'Plus Jakarta Sans',sans-serif; color:#1f2937; }
            .stock-opname-page #transaction-table { table-layout:fixed; }
            .stock-opname-page #active-branch-button-label { display:inline-block; max-width:160px; overflow:hidden; text-overflow:ellipsis; vertical-align:middle; }
            @media (max-width:767.98px) {
                .stock-opname-page { padding-bottom:calc(76px + env(safe-area-inset-bottom)); }
                .stock-opname-page #active-branch-button-label { max-width:90px; }
            }
            .chat-message-avatar { display:flex; width:32px; height:32px; flex-shrink:0; align-items:center; justify-content:center; border-radius:50%; background:#edf7f2; color:#0b595b; font-size:12px; font-weight:700; }
            #transaction-table tbody tr td { border: 0; padding: 0; background: transparent; }
            .chat-message-container { display: flex; gap: 0.75rem; margin-bottom: 1rem; }
            .chat-message-bubble { background-color: #f3f4f6; color: #1f2937; border-radius: 12px; padding: 0.75rem 1rem; font-size: 13.5px; line-height: 1.4; position: relative; }
            .chat-message-container.is-self { flex-direction: row-reverse; }
            .chat-message-container.is-self .chat-message-bubble { background-color: var(--erp-primary-soft); color: var(--erp-primary); border-top-right-radius: 4px; }
            .chat-message-container:not(.is-self) .chat-message-bubble { border-top-left-radius: 4px; background-color: #f3f4f6; color: #1f2937; }
            .chat-message-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.15rem; }
            .chat-message-author { font-size: 11px; font-weight: 700; color: #4b5563; }
            .chat-message-container.is-self .chat-message-author { color: var(--erp-primary); }
            .chat-message-time { font-size: 9.5px; color: #9ca3af; font-weight: 500; }
            .stock-opname-page .so-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:12px 8px; border-top:1px solid #f1f3f5; cursor:pointer; transition:background .15s ease; }
            .stock-opname-page .so-row:hover, .stock-opname-page .so-row:focus-visible { background:#f3faf7; outline:none; }
            #transaction-table tbody tr:first-child .so-row { border-top:0; }
            .so-row__main { min-width:0; flex:1; }
            .so-row__title { display:flex; align-items:center; gap:8px; min-width:0; }
            .so-row__name { overflow:hidden; color:#111827; font-size:13px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
            .so-row__status { flex:none; border-radius:999px; background:#fffbeb; padding:1px 7px; color:#b45309; font-size:10px; font-weight:700; }
            .so-row__time { display:block; margin-top:2px; overflow:hidden; color:#9ca3af; font-size:11px; text-overflow:ellipsis; white-space:nowrap; font-variant-numeric:tabular-nums; }
            .so-row__side { display:flex; flex:none; flex-direction:column; align-items:flex-end; gap:2px; text-align:right; font-variant-numeric:tabular-nums; }
            .so-row__diff { font-size:13px; font-weight:700; color:#9ca3af; }
            .so-row__diff small { font-size:11px; font-weight:500; opacity:.8; }
            .so-row__diff.is-surplus, .so-row__value.is-surplus { color:#047857; }
            .so-row__diff.is-loss, .so-row__value.is-loss { color:#be123c; }
            .so-row__value { font-size:11px; font-weight:500; }
            .stock-opname-modal .so-form-label { color:#374151; font-size:12px; font-weight:700; }
            .stock-opname-modal .so-form-hint { color:#9ca3af; font-size:11px; }
            .stock-opname-modal .so-form-panel { display:grid; grid-template-columns:1fr 1fr; gap:16px; border:1px solid #e5e7eb; border-radius:14px; background:#f9fafb; padding:14px 16px; }
            .stock-opname-modal .so-form-panel > div + div { border-left:1px solid #e5e7eb; padding-left:16px; }
            .stock-opname-modal .so-form-panel__label { display:block; margin-bottom:6px; color:#9ca3af; font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
            .stock-opname-modal .so-form-panel__input .erp-input { background:#fff; border-radius:10px; font-size:1rem; }
            .stock-opname-modal .so-form-panel__value { display:block; margin-top:8px; color:#0b595b; font-size:1rem; font-weight:800; font-variant-numeric:tabular-nums; }
            .stock-opname-modal .so-form-panel small { display:block; margin-top:6px; color:#9ca3af; font-size:10px; }
            .stock-opname-modal .so-form-physical { min-height:46px; border:1px solid #d1d5db; border-radius:12px; font-size:1.05rem; }
            .stock-opname-modal .so-form-physical:focus { border-color:#0b595b; box-shadow:0 0 0 1px #0b595b; }
            .stock-opname-modal .so-form-diff { border:1px solid #e5e7eb; border-radius:12px; padding:12px 14px; background:#f9fafb; color:#374151; font-variant-numeric:tabular-nums; }
            .stock-opname-modal .so-form-diff__row { display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:12px; }
            .stock-opname-modal .so-form-diff__row strong { font-size:15px; font-weight:800; }
            .stock-opname-modal .so-form-diff__row.is-sub { margin-top:8px; border-top:1px solid #e5e7eb; padding-top:8px; }
            .stock-opname-modal .so-form-diff__row.is-sub strong { font-size:13px; }
            .stock-opname-modal .so-form-diff.is-surplus { border-color:#a7f3d0; background:#ecfdf5; color:#065f46; }
            .stock-opname-modal .so-form-diff.is-loss { border-color:#fecdd3; background:#fff1f2; color:#9f1239; }
            .stock-opname-modal .so-form-diff.is-surplus .is-sub, .stock-opname-modal .so-form-diff.is-loss .is-sub { border-color:currentColor; }
            @media (max-width:575px) {
                .stock-opname-modal .so-form-panel { grid-template-columns:1fr; }
                .stock-opname-modal .so-form-panel > div + div { border-left:0; border-top:1px solid #e5e7eb; padding:12px 0 0; }
            }
            .so-hist-head { display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #f1f3f5; padding:8px 4px; color:#9ca3af; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
            .so-hist-head__side { display:flex; gap:4px; }
            .so-hist-col { min-width:58px; text-align:right; font-variant-numeric:tabular-nums; }
            .so-hist-reason-head { flex:1; }
            .so-hist-row { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; border-bottom:1px solid #f1f3f5; padding:12px 4px; }
            .so-hist-main { display:flex; min-width:0; flex-direction:column; gap:2px; }
            .so-hist-kind { color:#374151; font-size:12px; font-weight:700; }
            .so-hist-kind.is-surplus { color:#047857; }
            .so-hist-kind.is-loss { color:#be123c; }
            .so-hist-time, .so-hist-by { color:#9ca3af; font-size:11px; }
            .so-hist-side { display:flex; flex:1; justify-content:flex-end; gap:4px; font-size:13px; }
            .so-hist-side .so-hist-col { color:#374151; }
            .so-hist-side .so-hist-col.is-strong { color:#111827; font-weight:700; }
            .so-hist-side .so-hist-col.is-surplus { color:#047857; font-weight:700; }
            .so-hist-side .so-hist-col.is-loss { color:#be123c; font-weight:700; }
            .so-hist-reason { max-width:240px; color:#4b5563; font-size:12px; line-height:1.5; text-align:right; overflow-wrap:anywhere; }
            .so-hist-details { display:grid; grid-template-columns:1fr auto; gap:2px 10px; margin-top:6px; border-radius:8px; background:#f9fafb; padding:6px 8px; color:#6b7280; font-size:11px; text-align:left; }
            .so-hist-empty { padding:32px 0; color:#9ca3af; text-align:center; font-size:13px; }
            .so-adj-current { display:flex; align-items:center; justify-content:space-between; gap:12px; border:1px solid #f1f3f7; border-radius:12px; background:#f9fafb; padding:16px; }
            .so-adj-current span { color:#4b5563; font-weight:600; }
            .so-adj-current strong { color:#111827; font-size:1.125rem; font-weight:700; }
        </style>
        <link rel="stylesheet" href="{{ asset('assets/css/stock-opname-reference.css') }}?v={{ filemtime(public_path('assets/css/stock-opname-reference.css')) }}">
        <x-erp.workspace class="so-workspace" title="Stok Opname" subtitle="Cocokkan stok fisik dan stok sistem."><x-slot:toolbar>
            <div class="erp-search so-desktop-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="search-desktop" data-so-search placeholder="Cari produk…" aria-label="Cari produk"></div>
            <x-erp.mobile-search class="so-mobile-search"><x-slot:input><input type="search" id="search" data-so-search class="erp-mobile-search__input" placeholder="Cari produk…" aria-label="Cari produk"></x-slot:input><x-slot:clear><button type="button" id="so-search-clear" class="erp-mobile-search__clear" aria-label="Hapus pencarian" hidden><i class="ph ph-x" aria-hidden="true"></i></button></x-slot:clear><x-slot:filter><button type="button" class="erp-mobile-search__filter" data-so-open-filter aria-label="Filter stok opname" aria-haspopup="dialog" aria-controls="so-filter-sheet"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i></button></x-slot:filter></x-erp.mobile-search>
            <x-erp.button icon="sliders-horizontal" class="so-desktop-filter" data-so-open-filter aria-controls="so-filter-sheet"><span id="active-branch-button-label">Filter</span></x-erp.button>
            <div hidden><select data-erp-filter="cabang"><option value="all">Semua Cabang</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ ucwords($branch->name) }}</option>@endforeach</select><input type="hidden" id="kt_ecommerce_sales_flatpickr"></div>
            @if(check_access('stock-opname.create'))<x-erp.button variant="primary" icon="plus" class="so-desktop-create" data-erp-open="#kt_modal_add_customer">Tambah stok opname</x-erp.button>@endif
        </x-slot:toolbar><div class="erp-table-region"><table id="transaction-table" width="100%">
                    <thead class="hidden">
                        <tr>
                            <th>Transaksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table></div></x-erp.workspace>
    </div>

    <x-erp.dialog id="kt_modal_add_customer" size="lg" title="Catat Stock Opname" class="stock-opname-modal" data-erp-create>
        <form action="{{ url(Request::segment(1)) }}" id="kt_modal_add_customer_form" class="grid gap-5">
            @csrf
            <p class="so-form-hint">Data awal bersifat final dan tidak dapat diedit langsung.</p>

            <input type="hidden" id="date" name="date" value="{{ date('Y-m-d') }}" />

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="erp-label erp-label--required so-form-label" for="branch_id">Cabang Pelaksanaan</label>
                    <select class="erp-select" name="branch_id" id="branch_id" data-placeholder="Pilih Cabang">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-label erp-label--required so-form-label" for="product_id">Pilih Produk</label>
                    <select class="erp-select" name="product_id"
                        id="product_id" data-placeholder="Pilih Produk">
                        <option value="">Pilih Product</option>
                    </select>
                </div>
            </div>

            <div class="so-form-panel">
                <div>
                    <span class="so-form-panel__label">Stok Sistem</span>
                    <div class="erp-input-group so-form-panel__input">
                        <input type="number" step="0.01" class="erp-input text-end font-extrabold"
                            name="quantity" value="0" readonly aria-label="Stok sistem saat ini" />
                    </div>
                    <small>Catatan buku saat ini</small>
                </div>
                <div>
                    <span class="so-form-panel__label">HPP / Satuan</span>
                    <strong class="so-form-panel__value" id="stock-hpp-value">-</strong>
                    <small>Dipakai untuk menghitung dampak</small>
                </div>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label class="erp-label erp-label--required so-form-label mb-0" for="real_stock">Stok Fisik Terhitung</label>
                    <span class="so-form-hint">Hasil timbang aktual</span>
                </div>
                <input type="number" step="0.01" inputmode="decimal" id="real_stock"
                    class="erp-input text-end font-extrabold so-form-physical" name="real_stock" value="0" />
            </div>

            <div class="so-form-diff is-match" aria-live="polite">
                <div class="so-form-diff__row">
                    <span id="stock-difference-label">Stok sesuai</span>
                    <strong id="stock-difference-text">0 Kg</strong>
                </div>
                <div class="so-form-diff__row is-sub">
                    <span>Dampak Finansial</span>
                    <strong id="stock-difference-value">Rp 0</strong>
                </div>
            </div>

            <div>
                <span class="erp-label so-form-label">Detail Quantity</span>
                <div id="detail-qty-container" class="flex flex-col gap-3">
                </div>
                <x-erp.button icon="plus" class="mt-3" onclick="addDetailRow()">Tambah Detail</x-erp.button>
            </div>

            <div>
                <label class="erp-label so-form-label" for="so_note">Catatan Koreksi</label>
                <textarea class="erp-textarea" id="so_note" name="note"
                    rows="3" placeholder="Tulis kondisi barang, temuan, atau keterangan lainnya..."></textarea>
            </div>
        </form>
        <x-slot:footer>
            <div class="flex w-full justify-end gap-2">
                <x-erp.button data-erp-close>Batal</x-erp.button>
                <x-erp.button type="submit" variant="primary" form="kt_modal_add_customer_form" id="kt_modal_add_customer_submit">
                    <span class="indicator-label">Simpan Final</span>
                    <span class="indicator-progress hidden">Mohon tunggu...
                        <i class="erp-spinner ml-2" aria-hidden="true"></i></span>
                </x-erp.button>
            </div>
        </x-slot:footer>
    </x-erp.dialog>

    @include('transaction::stock-opname.partials.reference-filters') {{-- so-filter-sheet / so-history-filter --}}

    <dialog id="kt_modal_stock_detail" data-erp-dialog class="erp-dialog erp-dialog--sheet so-detail so-reference-sheet" aria-labelledby="so-detail-title">
        <div class="so-sheet-panel">
            <div class="so-sheet-handle" aria-hidden="true"><span></span></div>
            <header class="so-sheet-header"><div><h2 id="so-detail-title" data-so-field="name"></h2><p data-so-field="branch"></p></div><button type="button" class="so-close" data-erp-close aria-label="Tutup detail"><i class="ph ph-x" aria-hidden="true"></i></button></header>
            <div class="so-sheet-body">
                <div class="so-detail-meta"><div><span><i class="ph ph-clock" aria-hidden="true"></i>Waktu Dibuat</span><strong data-so-field="created"></strong></div><div><span><i class="ph ph-user" aria-hidden="true"></i>Dibuat oleh</span><strong data-so-field="creator"></strong></div></div>
                <div class="so-detail-summary"><div><span>Sistem → Fisik</span><strong><span data-so-field="system"></span><span class="so-summary-arrow"> → </span><b data-so-field="physical"></b></strong></div><div><span>HPP / Satuan</span><strong data-so-field="hpp"></strong></div><div class="so-detail-difference"><strong data-so-field="diff"></strong><strong data-so-field="value"></strong></div></div>
                <form id="so-detail-correction" novalidate>
                    <div class="so-credit-inputs"><label><span class="is-plus">+</span><input type="text" inputmode="decimal" id="so-credit-plus" placeholder="0" aria-label="Kredit lebih" autocomplete="off"><span data-so-field="unit"></span></label><label><span class="is-minus">−</span><input type="text" inputmode="decimal" id="so-credit-minus" placeholder="0" aria-label="Kredit kurang" autocomplete="off"><span data-so-field="unit"></span></label></div>
                    <label class="so-correction-label" for="so-credit-reason">Catatan Koreksi <span>*</span></label><textarea id="so-credit-reason" rows="5" maxlength="1000" placeholder="Tuliskan catatan koreksi…"></textarea>
                    <p id="so-correction-error" class="so-correction-error" role="status" aria-live="polite"></p>
                </form>

            </div>
            <footer class="so-sheet-footer so-detail-footer"><button type="button" class="so-history-button" data-so-action="history" aria-label="Riwayat stok opname"><i class="ph ph-clock-counter-clockwise" aria-hidden="true"></i><span data-so-count="history"></span></button><button type="submit" form="so-detail-correction" id="so-correction-save" disabled>Simpan Koreksi</button>@if(check_access('stock-opname.update'))<button type="button" class="is-primary" data-so-action="finalize">Selesaikan Opname</button>@endif<button type="button" class="is-primary" id="so-detail-done" hidden data-erp-close>Selesaikan opname</button></footer>
        </div>
    </dialog>

    <x-erp.dialog id="kt_modal_stock_adjustment" size="md" title="Penyesuaian Stok" class="stock-opname-modal">
        <form id="kt_modal_stock_adjustment_form" class="grid gap-5">
            @csrf
            <div class="so-adj-current">
                <span>Stock Fisik Saat Ini:</span>
                <strong id="adj-current-stock-label">0 Kg</strong>
            </div>

            <div>
                <label class="erp-label erp-label--required" for="adj-qty-input">Qty Penyesuaian</label>
                <div class="erp-input-group">
                    <span class="erp-input-group__addon justify-center font-bold" id="adj-type-badge" style="width: 46px;">+</span>
                    <input type="number" step="0.01" class="erp-input font-bold" id="adj-qty-input"
                        name="qty" placeholder="1" required min="0.01">
                </div>
                <p class="so-form-hint mt-2 text-end">
                    Menjadi: <strong class="text-gray-900" id="adj-result-stock-label">0 Kg</strong>
                </p>
            </div>

            <x-erp.field label="Alasan Penyesuaian" for="adj-reason-input" required><textarea class="erp-textarea" id="adj-reason-input" name="reason"
                    rows="3" placeholder="Contoh: Salah hitung saat audit awal" required></textarea></x-erp.field>
        </form>
        <x-slot:footer>
            <div class="flex w-full justify-end gap-2">
                <x-erp.button data-erp-close>Batal</x-erp.button>
                <x-erp.button type="submit" variant="primary" form="kt_modal_stock_adjustment_form" id="adj-submit-btn">Simpan</x-erp.button>
            </div>
        </x-slot:footer>
    </x-erp.dialog>

    <dialog id="kt_stock_opname_history" data-erp-dialog class="erp-dialog erp-dialog--drawer so-reference-history" aria-labelledby="so-history-title">
        <div class="so-history-page">
            <header class="so-history-topbar"><button type="button" id="kt_stock_opname_history_close" data-erp-close aria-label="Kembali ke detail"><i class="ph ph-caret-left" aria-hidden="true"></i></button><h2 id="so-history-title">Riwayat Stok Opname</h2><span></span></header>
            <div class="so-history-subheader"><div class="so-history-context"><div><p>Riwayat Stok Opname</p><h3 id="history-transaction-code">—</h3></div><div class="so-history-tools"><button type="button" data-so-history-toggle aria-label="Ubah tampilan menjadi Alasan" aria-pressed="false">Kredit</button><button type="button" id="so-history-filter-open" aria-label="Filter riwayat stok opname" aria-haspopup="dialog" aria-controls="so-history-filter"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i></button></div></div><div id="so-history-columns" class="so-hist-head"></div></div>
            <div id="kt_stock_opname_history_scroll" class="so-history-scroll"><div id="history-timeline-items" class="so-hist"></div></div>
        </div>
    </dialog>

    <x-erp.drawer id="kt_stock_opname_discussion" title="Ruang Diskusi">
        <p class="so-form-hint mb-4" id="discussion-product-name">-</p>
        <div id="discussion-items" class="flex flex-col gap-4">
            {{-- Pesan dirender oleh showDiscussion() --}}
        </div>
        <x-slot:footer>
            <form id="kt_stock_opname_discussion_form" class="flex w-full items-center gap-2">
                <input type="hidden" id="discussion-stock-opname-id" name="stock_opname_id">
                <input type="text" class="erp-input" id="discussion-message-input" placeholder="Tulis pesan..." required autocomplete="off" aria-label="Pesan">
                <x-erp.button type="submit" variant="primary" icon="paper-plane-tilt" bold class="shrink-0" aria-label="Kirim pesan" />
            </form>
        </x-slot:footer>
    </x-erp.drawer>

@section('script')
    <script src="{{ asset('assets/js/stock-opname-reference.js') }}?v={{ filemtime(public_path('assets/js/stock-opname-reference.js')) }}"></script>
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
                    return `<span class="erp-badge erp-badge--danger mr-1 mb-1">${p.name} (${p.branch_name})</span>`;
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
                    confirmButtonColor: '#dc2626',
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
            const unit = $('#product_id').data('unit') || 'Kg';
            const value = difference * price;
            const diffRounded = Math.round(difference * 100) / 100;

            $('#stock-hpp-value').text(price > 0 ? `Rp ${formatRupiah(Math.round(price))}/${unit}` : '-');
            $('#stock-difference-label').text(diffRounded < 0 ? 'Potensi rugi' : (diffRounded > 0 ? 'Surplus stok' : 'Stok sesuai'));
            $('#stock-difference-text').text(`${diffRounded > 0 ? '+' : ''}${diffRounded} ${unit}`);
            $('#stock-difference-value').text(`${value < 0 ? '-' : (value > 0 ? '+' : '')}Rp ${formatRupiah(Math.abs(Math.round(value)))}`);

            $('.so-form-diff')
                .toggleClass('is-surplus', diffRounded > 0)
                .toggleClass('is-loss', diffRounded < 0)
                .toggleClass('is-match', diffRounded === 0);
        }

        function formatLongDateTime(dateValue) {
            return StockOpnameUi.formatLongDateTime(dateValue, @json(config('app.timezone')));
        }

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        // Row data shared with the detail sheet (keyed by stock opname id).
        window.stockOpnameRows = window.stockOpnameRows || {};

        function describeStockOpname(row) {
            const isPending = row?.status === 'pending';
            const stockSystem = isPending ? Number(row?.live_stock || 0) : Number(row?.stock || 0);
            const stockFisik = Number(row?.real_stock || 0);
            const selisih = Math.round((stockFisik - stockSystem) * 100) / 100;
            const hpp = Number(row?.avg_hpp_calc || row?.avg_price || 0);
            return {
                id: row?.id || 0,
                name: row?.product?.name || row?.name || '-',
                code: row?.code || '-',
                branch: row?.branch_name || 'Semua Cabang',
                unit: row?.product?.unit?.abbreviation || 'Kg',
                isPending,
                dateLabel: formatLongDateTime(row?.created_at || row?.date),
                creator: row?.creator_name || '',
                system: stockSystem,
                physical: stockFisik,
                diff: selisih,
                hpp,
                value: Math.floor(selisih * hpp),
                historyCount: Number(row?.history_count || 0),
                discussionsCount: Number(row?.discussions_count || 0)
            };
        }

        function formatStockQty(value) {
            return StockOpnameUi.quantity(value);
        }

        function formatSignedStockQty(value) {
            return (value > 0 ? '+' : '') + formatStockQty(value);
        }

        function formatSignedRupiah(value) {
            return (value < 0 ? '-' : (value > 0 ? '+' : '')) + 'Rp ' + formatRupiah(Math.abs(value));
        }

        function renderStockOpnameCard(row) {
            const info = describeStockOpname(row);
            window.stockOpnameRows[info.id] = info;
            const tone = info.diff > 0 ? 'is-surplus' : (info.diff < 0 ? 'is-loss' : 'is-match');
            const pending = info.isPending ? '<span class="so-row__status">Draf</span>' : '';
            const value = info.diff !== 0
                ? `<span class="so-row__value ${tone}">${escapeHtml(formatSignedRupiah(info.value))}</span>`
                : '';

            return `
                <div class="so-row" role="button" tabindex="0" data-so-id="${info.id}"
                    onclick="openStockDetail(${info.id})"
                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openStockDetail(${info.id}); }">
                    <div class="so-row__main">
                        <div class="so-row__title"><span class="so-row__name">${escapeHtml(info.name)}</span>${pending}</div>
                        <span class="so-row__time">${escapeHtml(info.dateLabel)}</span>
                    </div>
                    <div class="so-row__side">
                        <span class="so-row__diff ${tone}">${escapeHtml(formatSignedStockQty(info.diff))} <small>${escapeHtml(info.unit)}</small></span>
                        ${value}
                    </div>
                </div>
            `;
        }

        function openStockDetail(id) {
            const info = window.stockOpnameRows[id];
            if (!info) return;
            const $sheet = $('#kt_modal_stock_detail');
            const tone = info.diff > 0 ? 'is-surplus' : (info.diff < 0 ? 'is-loss' : 'is-match');
            $sheet.data('stockOpnameId', id);
            $sheet.find('[data-so-field="name"]').text(info.name);
            $sheet.find('[data-so-field="branch"]').text(info.branch);
            $sheet.find('[data-so-field="created"]').text(info.dateLabel);
            $sheet.find('[data-so-field="creator"]').text(info.creator || '—');
            $sheet.find('[data-so-field="system"]').text(formatStockQty(info.system));
            $sheet.find('[data-so-field="physical"]').text(`${formatStockQty(info.physical)} ${info.unit}`);
            $sheet.find('[data-so-field="diff"]').text(`Selisih (${formatSignedStockQty(info.diff)} ${info.unit})`);
            $sheet.find('[data-so-field="value"]').text(formatSignedRupiah(info.value));
            $sheet.find('.so-detail-difference').removeClass('is-surplus is-loss is-match').addClass(tone);
            $sheet.find('[data-so-field="hpp"]').text(`Rp ${formatRupiah(Math.round(Math.abs(info.hpp)))} / ${info.unit}`);
            $sheet.find('[data-so-field="unit"]').text(info.unit);
            $sheet.find('[data-so-action="finalize"]').prop('hidden', !info.isPending);
            const canCorrect = info.isPending && @json(check_access('stock-opname.update'));
            $sheet.find('#so-credit-plus, #so-credit-minus, #so-credit-reason').val('').prop('disabled', !canCorrect);
            $sheet.find('#so-correction-save').prop('disabled', true);
            $sheet.find('#so-detail-done').prop('hidden', canCorrect);
            $sheet.find('#so-correction-error').text(info.isPending && !canCorrect ? 'Tidak memiliki akses koreksi.' : '');
            $sheet.find('[data-so-count="history"]').text(info.historyCount || '');
            erp.dialog.open($sheet[0]);
        }

        $(document).on('click', '#kt_modal_stock_detail [data-so-action]', function() {
            const $sheet = $('#kt_modal_stock_detail');
            const id = $sheet.data('stockOpnameId');
            const action = this.dataset.soAction;
            if (action === 'finalize' && ($('#so-credit-plus').val() || $('#so-credit-minus').val() || $('#so-credit-reason').val().trim())) {
                $('#so-correction-error').text('Simpan atau kosongkan koreksi terlebih dahulu sebelum menyelesaikan opname.');
                return;
            }
            const run = {
                history: () => showHistory(id),
                finalize: () => finalizeStockOpname(id),
            }[action];
            if (!run) return;
            // Close the sheet first so the next dialog/drawer does not stack on top of it.
            $sheet.one('erp:dialog-close', run);
            erp.dialog.close($sheet[0]);
        });

        $(document).ready(function() {
            const $branchFilter = $('[data-erp-filter="cabang"]');
            const $activeBranchButtonLabel = $('#active-branch-button-label');

            function updateActiveFilterInfo() {
                const selectedBranch = $branchFilter.find('option:selected').text().trim() || 'Semua cabang';
                $activeBranchButtonLabel.text(selectedBranch);
            }

            window.exportExcel = function() {
                let cabang = $('[data-erp-filter="cabang"]').val() || '';
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
                let cabang = $('[data-erp-filter="cabang"]').val() || '';
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
                        d.cabang_filter = $('[data-erp-filter="cabang"]').val();
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
            initStockOpnameReference();

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
                        $('#discussion-items').find('.so-form-hint').remove();

                        // Append the new message
                        const item = response.discussion;
                        const isSelf = String(item.created_by) === String(currentUserId);
                        const selfClass = isSelf ? 'is-self' : '';
                        const initial = escapeHtml((item.creator_name || 'U').trim().charAt(0).toUpperCase());

                        const itemHtml = `
                            <div class="chat-message-container ${selfClass}">
                                <span class="chat-message-avatar" aria-hidden="true">${initial}</span>
                                <div class="flex flex-col ${isSelf ? 'items-end' : 'items-start'}">
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
                        const scrollEl = document.querySelector('#kt_stock_opname_discussion .erp-dialog__body');
                        if (scrollEl) {
                            scrollEl.scrollTop = scrollEl.scrollHeight;
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
                            erp.dialog.close('kt_modal_stock_adjustment');

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

            $('[data-erp-filter="cabang"]').on('change', function() {
                updateActiveFilterInfo();
                dataTable.draw();
                showStockAlert($(this).val());
            });

            updateActiveFilterInfo();
            showStockAlert($('[data-erp-filter="cabang"]').val());

            $('#kt_modal_add_customer').on('erp:dialog-close', function(e) {
                if (e.target !== this) return;
                var form = $('#kt_modal_add_customer_form');
                form.trigger('reset');
                form.find('input[name="_method"]').remove();
                $('select[name="product_id"]').val(null).trigger('change');

                var filterBranch = $('[data-erp-filter="cabang"]').val();
                var defaultBranch = (filterBranch && filterBranch !== 'all') ? filterBranch : $(
                    'select[name="branch_id"] option:first').val();
                $('select[name="branch_id"]').val(defaultBranch).trigger('change');

                form.attr('action', '{{ url(Request::segment(1)) }}');
                $('#kt_modal_add_customer-title').text('Catat Stock Opname');
                $('#kt_modal_add_customer_submit .indicator-label').html(
                    'Buat Transaksi <i class="ph ph-arrow-right ml-1" aria-hidden="true"></i>');
                $('#detail-qty-container').empty();
                if (typeof detailRowCount !== 'undefined') {
                    detailRowCount = 0;
                }
                form.find('input, select, textarea, button[type="submit"], button').prop('disabled', false);
                $('#kt_modal_add_customer_submit').prop('disabled', false);
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
                            $('select[name="product_id"]').val(null).trigger('change'); // Reset picker

                            // 3. Kembalikan action form ke default (untuk create)
                            form.attr('action',
                                `/${segment1}`); // Misal segment1 = 'stock-opname'

                            // 4. Kembalikan judul modal (opsional)
                            $('#kt_modal_add_customer-title').text('Catat Stock Opname');

                            $('#kt_modal_add_customer_submit .indicator-label').html(
                                'Simpan Final');
                            updateStockSummary();

                            // 5. Tutup modal
                            erp.dialog.close('kt_modal_add_customer');

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
                    confirmButton: 'erp-action erp-action--danger',
                    cancelButton: 'erp-action ml-3'
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

                    // Clear and reset the product_id picker first
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
                    $('#product_id').data('unit', response.product?.unit?.abbreviation || response.unit || 'Kg');
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
                    $('#kt_modal_add_customer_submit').prop('disabled', false);

                    // Ubah judul modal
                    $('#kt_modal_add_customer-title').text('Edit Transaksi');
                    $('#kt_modal_add_customer_submit .indicator-label').text('Simpan Perubahan');

                    // Tampilkan modal untuk edit produk
                    erp.dialog.open('kt_modal_add_customer');
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

                    // Clear and reset the product_id picker first
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
                    $('#product_id').data('unit', response.product?.unit?.abbreviation || response.unit || 'Kg');
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
                    $('#kt_modal_add_customer_submit').prop('disabled', true);

                    // Ubah judul modal untuk view
                    $('#kt_modal_add_customer-title').text('Lihat Transaksi');

                    // Tampilkan modal untuk view produk
                    erp.dialog.open('kt_modal_add_customer');
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

        // Normalize the complete server history before filtering (never recompute balances on a subset).
        function renderStockOpnameHistory() {
            const state = window.stockOpnameHistory || {entries:[],unit:'Kg',tab:'credit',direction:'all'};
            const entries = StockOpnameUi.filterHistory(StockOpnameUi.history(state.entries), state.direction || 'all');
            const unit = escapeHtml(state.unit);
            const num = value => StockOpnameUi.quantity(value);
            const signed = value => value === null ? '—' : (value > 0 ? '+' : '') + num(value);
            const reasonMode = state.tab === 'reason';
            $('[data-so-history-toggle]').text(reasonMode ? 'Alasan' : 'Kredit').toggleClass('is-reason', reasonMode)
                .attr('aria-pressed', String(reasonMode)).attr('aria-label', reasonMode ? 'Ubah tampilan menjadi Kredit' : 'Ubah tampilan menjadi Alasan');
            $('#so-history-filter-open').toggleClass('is-active', state.direction !== 'all');
            const head = reasonMode ? '<span class="so-hist-reason-head">Alasan Pergerakan</span>'
                : '<div class="so-hist-head__side"><span class="so-hist-col">Awal</span><span class="so-hist-col">Kredit</span><span class="so-hist-col">Saldo</span></div>';
            $('#so-history-columns').html(`<span>Aktivitas</span>${head}`);
            const $list = $('#history-timeline-items').empty();
            if (!entries.length) {
                $list.append('<div class="so-hist-empty">Tidak ada riwayat sesuai filter.</div>');
                return;
            }
            entries.forEach(item => {
                const tone = item.delta > 0 ? 'is-surplus' : item.delta < 0 ? 'is-loss' : '';
                const kind = {initial:'Stok Awal',plus:'Kredit Lebih',minus:'Kredit Kurang',note:'Catatan',unknown:'Perubahan',snapshot:'Snapshot Tersedia'}[item.kind];
                const badge = tone ? `<span class="so-hist-direction ${tone}" aria-label="${item.delta > 0 ? 'Lebih' : 'Kurang'}">${item.delta > 0 ? '↙' : '↗'}</span>` : '';
                let side;
                if (!reasonMode) {
                    const cell = (value,cls='',isSigned=false) => `<span class="so-hist-col ${cls}">${isSigned ? signed(value) : num(value)}${value !== null ? `<small>${unit}</small>` : ''}</span>`;
                    side = cell(item.before) + cell(item.delta,tone,true) + cell(item.after,'is-strong');
                } else {
                    const details = Array.isArray(item.details) && item.details.length
                        ? '<div class="so-hist-details">' + item.details.map(det => `<span>${escapeHtml(det.name)}</span><b>${num(det.qty)} ${unit}</b>`).join('') + '</div>' : '';
                    side = `<div class="so-hist-reason">${escapeHtml(item.note || '—')}${details}</div>`;
                }
                $list.append(`<div class="so-hist-row"><div class="so-hist-main"><span class="so-hist-kind">${kind}${badge}</span><span class="so-hist-by">${escapeHtml(item.creator_name || '—')}</span><span class="so-hist-time">${escapeHtml(formatLongDateTime(item.created_at))}</span></div><div class="so-hist-side ${reasonMode ? 'is-reason' : ''}">${side}</div></div>`);
            });
        }

        $(document).on('click', '[data-so-history-toggle]', function() {
            if (!window.stockOpnameHistory) return;
            window.stockOpnameHistory.tab = window.stockOpnameHistory.tab === 'credit' ? 'reason' : 'credit';
            renderStockOpnameHistory();
        });

        function showHistory(id) {
            $.ajax({url:`/stock-opname/${id}/history`,type:'GET',success:function(response) {
                $('#history-transaction-code').text(response.product_name || window.stockOpnameRows[id]?.name || response.code || '—');
                window.stockOpnameHistory = {id,entries:response.history || [],unit:response.unit || 'Kg',tab:'credit',direction:'all'};
                renderStockOpnameHistory();
                erp.dialog.open('kt_stock_opname_history');
            },error:function() {
                Swal.fire({icon:'error',title:'Gagal',text:'Terjadi kesalahan saat memuat riwayat stok opname.'});
            }});
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
                            const initial = escapeHtml((item.creator_name || 'U').trim().charAt(0).toUpperCase());

                            const itemHtml = `
                                <div class="chat-message-container ${selfClass}">
                                    <span class="chat-message-avatar" aria-hidden="true">${initial}</span>
                                    <div class="flex flex-col ${isSelf ? 'items-end' : 'items-start'}">
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
                            '<div class="so-form-hint py-20 text-center text-sm">Belum ada diskusi. Mulai percakapan terkait selisih stock ini.</div>'
                        );
                    }

                    erp.dialog.open('kt_stock_opname_discussion');

                    // Scroll to bottom
                    setTimeout(() => {
                        const scrollEl = document.querySelector('#kt_stock_opname_discussion .erp-dialog__body');
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

                    const $header = $('#kt_modal_stock_adjustment .erp-dialog__header');
                    const $title = $('#kt_modal_stock_adjustment-title');
                    const $badge = $('#adj-type-badge');
                    const $submitBtn = $('#adj-submit-btn');

                    if (type === 'up') {
                        // Up styling
                        $header.css({
                            'background-color': '#ecfdf5',
                            'color': '#059669'
                        });
                        $title.css('color', '#059669').html(`
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mr-1 inline-block align-text-bottom">
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
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="mr-1 inline-block align-text-bottom">
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

                    erp.dialog.open('kt_modal_stock_adjustment');
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

        $('#kt_modal_add_customer').on('erp:dialog-open', function(e) {
            if (e.target !== this) return;
            const $branch = $('#branch_id');
            const $product = $('#product_id');
            const $quantity = $('input[name="quantity"]');
            const $realStock = $('input[name="real_stock"]');
            const $form = $('#kt_modal_add_customer_form');

            if (!$form.find('input[name="_method"]').length) {
                $('#kt_modal_add_customer-title').text('Input Stock Fisik & Akumulasi');
                $('#kt_modal_add_customer_submit .indicator-label').html(
                    'Buat Transaksi <i class="ph ph-arrow-right ml-1" aria-hidden="true"></i>');
                $('textarea[name="note"]').val('');
                $('#detail-qty-container').empty();
                detailRowCount = 0;
            }

            if (!$realStock.val()) {
                $realStock.val(0);
            }

            // Searchable pickers sit on top of the selects; the ids and the select2:select/clear events stay the contract.
            ErpPicker.attach('#branch_id', {
                title: 'Pilih Cabang',
                placeholder: 'Cari cabang...',
                placeholderTrigger: 'Pilih cabang',
                emptyText: 'Cabang tidak ditemukan'
            });
            ErpPicker.attach('#product_id', {
                title: 'Pilih Produk',
                subtitle: 'Produk yang stoknya dicocokkan',
                placeholder: 'Cari nama produk...',
                placeholderTrigger: 'Pilih produk',
                emptyText: 'Produk tidak ditemukan',
                url: '{{ route('ajax.stock-available') }}',
                params: () => ({ branch_id: $('#branch_id').val() }),
                map: item => ({
                    id: item.id,
                    text: item.name,
                    meta: item.stock_available !== undefined ? 'Stok: ' + item.stock_available : '',
                    data: {
                        stock_available: item.stock_available,
                        unit_price: item.hpp || item.price || item.cost_price || item.purchase_price || 0
                    }
                })
            });

            $product.off('select2:select.stockOpname').on('select2:select.stockOpname', function(e) {
                const data = e.params.data;
                $quantity.val(data.stock_available || 0);
                $product.data('unit-price', data.unit_price || 0);
                updateStockSummary(data.unit_price || 0);
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
                <div class="detail-row flex items-center gap-3" id="detail-row-${detailRowCount}">
                    <input type="text" class="erp-input" name="details[${detailRowCount}][name]" value="${name}" placeholder="Misal: Keranjang A" required>
                    <div class="erp-input-group w-[150px] shrink-0">
                        <input type="number" step="0.01" class="erp-input detail-qty" name="details[${detailRowCount}][qty]" value="${qty}" placeholder="Qty" required oninput="calculateRealStock()">
                        <span class="erp-input-group__addon">Kg</span>
                    </div>
                    <button type="button" class="erp-action erp-action--danger shrink-0" onclick="removeDetailRow(${detailRowCount})" aria-label="Hapus detail">
                        <i class="ph ph-trash" aria-hidden="true"></i>
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
                    confirmButton: 'erp-action erp-action--primary',
                    cancelButton: 'erp-action ml-3'
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
    <script src="{{ asset('assets/js/stock-opname-reference-page.js') }}?v={{ filemtime(public_path('assets/js/stock-opname-reference-page.js')) }}"></script>
@endsection
@endsection
