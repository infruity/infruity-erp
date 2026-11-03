@extends('layouts.erp-tailwind')
@php
    $receiving = ($type ?? request()->segment(1)) === 'transfer-penerima';
    $readOnly = $is_view ?? false;
    $pageTitle = $receiving ? 'Pindah Stok - Menerima' : 'Pindah Stok - Mengirim';
    $canReceive = $receiving && in_array($data->status ?? '', ['pending', 'proses']) && check_access('transfer-penerima.set_selesai');
    $transferConfig = [
        'branches' => ($branches ?? collect())->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name])->values(),
        'destinations' => ($destinations ?? collect())->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name])->values(),
        'transaction' => $data ?? null, 'details' => $detail ?? [], 'invoiceNumber' => $invoice_number ?? '',
        'today' => now()->format('Y-m-d'), 'receiving' => $receiving, 'readOnly' => $readOnly, 'canReceive' => $canReceive,
        'saveUrl' => route('transfer.save-transaction'), 'productsUrl' => route('transfer.products'),
        'receiveUrl' => isset($data->id) ? route('transfer-penerima.set_selesai', $data->id) : null,
        'indexUrl' => route($receiving ? 'transfer-penerima.index' : 'transfer-pengirim.index'), 'csrf' => csrf_token(),
    ];
@endphp
@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-breadcrumb', 'Inventaris / ' . $pageTitle)
@section('dashboard-fullscreen', '1')
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/transfer-form.css') }}?v={{ filemtime(public_path('assets/css/transfer-form.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('assets/js/transfer-form.js') }}?v={{ filemtime(public_path('assets/js/transfer-form.js')) }}"></script>
@endpush
@section('content')
    <div class="transfer-form" x-data="transferForm(@js($transferConfig))" @keydown.escape.window="branchOpen = false; destinationOpen = false">
        <div class="transfer-canvas">
            <section class="transfer-branch-card" aria-label="{{ $receiving ? 'Asal pengiriman' : 'Tujuan pengiriman' }}">
                <div class="transfer-field-label transfer-card-label"><span>{{ $receiving ? 'Asal Pengiriman' : 'Tujuan Pengiriman' }} @if (!$readOnly)<span class="transfer-required">*</span>@endif</span><small x-show="readOnly" x-text="invoiceNumber"></small></div>
                @if ($receiving || $readOnly)
                    <div class="transfer-branch-trigger"><span><i class="ph-bold ph-storefront" aria-hidden="true"></i> <span x-text="receiving ? branchName : destinationName"></span></span><span class="transfer-status" x-text="statusLabel"></span></div>
                    <details class="transfer-metadata"><summary>Detail pengiriman <i class="ph ph-caret-down" aria-hidden="true"></i></summary>
                        <div class="transfer-metadata-fields"><div><label>{{ $receiving ? 'Cabang Penerima' : 'Cabang Pengirim' }}</label><strong x-text="receiving ? destinationName : branchName"></strong></div><div><label>Tanggal Pengiriman</label><strong x-text="date"></strong></div>
                        <div><label>Pengirim</label><strong>{{ $data->createdBy->nm_user ?? '—' }}</strong></div><div><label>Penerima</label><strong>{{ $data->picPenerima->nm_user ?? '—' }}</strong></div></div>
                    </details>
                @else
                    <div class="transfer-branch-picker" @click.outside="destinationOpen = false">
                        <button type="button" class="transfer-branch-trigger" @click="destinationOpen = !destinationOpen" :disabled="saving" aria-label="Pilih tujuan pengiriman" aria-haspopup="true" :aria-expanded="destinationOpen"><span x-text="destinationName || 'Pilih Tujuan Pengiriman'"></span><i class="ph-bold ph-caret-down" :class="destinationOpen ? 'is-open' : ''" aria-hidden="true"></i></button>
                        <div x-show="destinationOpen" x-cloak class="transfer-branch-menu" aria-label="Pilihan cabang tujuan"><template x-for="branch in availableDestinations" :key="branch.id"><button type="button" @click="selectDestination(branch.id)" :class="String(branch.id) === String(destinationId) ? 'is-selected' : ''"><span x-text="branch.name"></span><span class="transfer-branch-dot" x-show="String(branch.id) === String(destinationId)" aria-hidden="true"></span></button></template><p class="transfer-stock" x-show="!availableDestinations.length">Belum ada cabang tujuan yang tersedia.</p></div>
                    </div>
                    <details class="transfer-metadata"><summary>Detail pengiriman <span x-text="invoiceNumber"></span><i class="ph ph-caret-down" aria-hidden="true"></i></summary>
                        <div class="transfer-metadata-fields"><div><label for="transfer-origin">Cabang Pengirim</label><select id="transfer-origin" class="transfer-modal-input" :value="branchId" @change="changeBranch($event.target.value); $event.target.value = branchId" :disabled="saving || branches.length < 2"><template x-for="branch in branches" :key="branch.id"><option :value="branch.id" x-text="branch.name"></option></template></select></div><div><label for="transfer-date">Tanggal Pengiriman</label><input id="transfer-date" type="date" x-model="date" @change="markDirty()" :disabled="saving" required></div></div>
                    </details>
                    <p class="transfer-error" x-show="!branches.length" x-cloak>Tidak ada cabang asal yang dapat diakses. Hubungi administrator.</p>
                @endif
            </section>
            <section class="transfer-items-card" aria-label="Daftar produk transfer">
                <div class="transfer-items-header"><h1>Daftar Produk</h1>@if (!$readOnly)<button type="button" class="transfer-add-button" @click="openAddModal()" :disabled="saving || !branchId">+ Tambah</button>@endif</div>
                <div class="transfer-items-body">
                    <div class="transfer-empty" x-show="!cart.length"><h2>Belum ada produk di daftar pengiriman</h2>@if (!$readOnly)<p>Klik tombol <button type="button" @click="openAddModal()">+ Tambah</button> untuk melakukan input</p>@endif</div>
                    <template x-for="item in cart" :key="item.id">
                        <button type="button" class="transfer-cart-item" @click="openItem(item)" :disabled="saving || (readOnly && !canReceive)" :aria-label="(receiving ? 'Periksa ' : 'Ubah ') + item.name">
                            <span class="transfer-cart-line"><strong x-text="item.name"></strong><b x-text="number(item.qty) + ' ' + item.unit"></b></span>
                            <span class="transfer-cart-line" x-show="receiving || item.sent !== item.qty && readOnly"><small x-text="'Dikirim: ' + number(item.sent) + ' ' + item.unit"></small><small class="transfer-shortage" x-show="item.qty < item.sent" x-text="'Kurang ' + number(item.sent - item.qty) + ' ' + item.unit"></small></span>
                            <small class="transfer-stock" x-show="item.note" x-text="item.note"></small>
                        </button>
                    </template>
                </div>
            </section>
        </div>
        <footer class="transfer-summary"><div class="transfer-summary-inner">
            <p class="transfer-error" x-show="saveError" x-cloak x-text="saveError" role="alert"></p>
            <div class="transfer-summary-actions">
                @if ($canReceive)
                    <a href="{{ route('transfer-penerima.index') }}" class="transfer-action" :aria-disabled="saving" @click="if (saving) $event.preventDefault()">Batal</a>
                    <button type="button" class="transfer-action transfer-action-primary" @click="openConfirm()" :disabled="saving || !cart.length"><span x-text="saving ? 'Menyimpan...' : 'Diterima (' + cart.length + ')' "></span></button>
                @elseif (!$readOnly)
                    <button type="button" class="transfer-action" @click="save('draft')" :disabled="saving || !cart.length"><span x-text="saving && savingStatus === 'draft' ? 'Menyimpan...' : 'Simpan sebagai Draf'"></span></button>
                    <button type="button" class="transfer-action transfer-action-primary" @click="openConfirm()" :disabled="saving || !cart.length"><span x-text="cart.length ? 'Kirim Stok (' + cart.length + ')' : 'Kirim Stok'"></span></button>
                @else
                    <a href="{{ route($receiving ? 'transfer-penerima.index' : 'transfer-pengirim.index') }}" class="transfer-action transfer-action-primary">Kembali</a>
                @endif
            </div>
        </div></footer>
        @include('transaction::transfer.segment.mobile-item-modal')
        <dialog class="transfer-modal" x-ref="confirmModal" aria-labelledby="transfer-confirm-title" @cancel="if (saving) $event.preventDefault()" @click="if ($event.target === $event.currentTarget && !saving) $refs.confirmModal.close()">
            <div class="modal-content"><div class="modal-header"><h2 id="transfer-confirm-title" x-text="receiving ? (hasShortage ? 'Terdapat Selisih Kurang' : 'Terima Pengiriman?') : 'Kirim Stok?' "></h2><button type="button" class="transfer-close" @click="$refs.confirmModal.close()" :disabled="saving" aria-label="Tutup konfirmasi"><i class="ph-bold ph-x" aria-hidden="true"></i></button></div>
                <div class="transfer-confirm-body"><strong x-text="receiving ? branchName : destinationName"></strong><p x-text="receiving ? (hasShortage ? 'Jumlah fisik yang diterima akan dicatat sesuai pemeriksaan. Selisih kurang tetap menjadi stok cabang pengirim.' : 'Pastikan seluruh produk dan jumlah fisik sudah diperiksa sebelum mengonfirmasi penerimaan.') : 'Kirim ' + cart.length + ' produk ke cabang tujuan. Stok akan berpindah setelah penerima mengonfirmasi barang.'"></p></div>
                <div class="modal-footer"><button type="button" class="transfer-action" @click="$refs.confirmModal.close()" :disabled="saving" x-text="receiving ? 'Periksa Lagi' : 'Batal'"></button><button type="button" class="transfer-action transfer-action-primary" @click="receiving ? receive() : save('pending')" :disabled="saving"><span x-text="saving ? 'Menyimpan...' : (receiving ? (hasShortage ? 'Tetap Terima' : 'Diterima') : 'Kirim Stok')"></span></button></div>
            </div>
        </dialog>
    </div>
@endsection
