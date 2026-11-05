@extends('layouts.erp')
@section('title', isset($data) ? 'Edit Sortir Barang' : 'Sortir Barang')
@section('page-title', 'Sortir Barang')
@section('page-breadcrumb', 'Inventaris / Sortir Barang')
@section('dashboard-fullscreen', '1')

@section('content')
    @php
        $sortirConfig = [
            'branches' => ($branches ?? collect())->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name])->values(),
            'transaction' => $data ?? null,
            'details' => $detail ?? [],
            'invoiceNumber' => $invoice_number,
            'today' => now()->format('Y-m-d'),
            'saveUrl' => route('sortir.save-transaction'),
            'productsUrl' => url('/sortir/products'),
            'indexUrl' => route('sortir.index'),
        ];
    @endphp
    @include('transaction::sortir.segment.styles')
    <div class="sortir-form" x-data="sortirForm(@js($sortirConfig))" @keydown.escape.window="branchOpen = false">
        <div class="sortir-canvas">
            <section class="sortir-branch-card" aria-label="Cabang sortir">
                <label class="sortir-field-label" id="sortir-branch-label">Cabang <span class="sortir-required">*</span></label>
                <div class="sortir-branch-picker" @click.outside="branchOpen = false" @keydown.escape.stop="branchOpen = false">
                    <button type="button" class="sortir-branch-trigger" @click="branchOpen = !branchOpen" :disabled="branches.length < 2 || saving"
                        aria-labelledby="sortir-branch-label" aria-haspopup="true" :aria-expanded="branchOpen">
                        <span x-text="selectedBranchName || 'Pilih Cabang'"></span>
                        <i x-show="branches.length > 1" class="ph-bold ph-caret-down" :class="branchOpen ? 'is-open' : ''" aria-hidden="true"></i>
                    </button>
                    <div x-show="branchOpen" x-cloak class="sortir-branch-menu" aria-label="Pilihan cabang">
                        <template x-for="branch in branches" :key="branch.id">
                            <button type="button" @click="changeBranch(branch.id)" :class="String(branch.id) === String(branchId) ? 'is-selected' : ''">
                                <span x-text="branch.name"></span><span class="sortir-branch-dot" x-show="String(branch.id) === String(branchId)" aria-hidden="true"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <p class="sortir-error" x-show="!branches.length" x-cloak>Tidak ada cabang yang dapat diakses. Hubungi administrator.</p>
                <details class="sortir-metadata">
                    <summary>Detail transaksi <span x-text="invoiceNumber"></span><i class="ph ph-caret-down" aria-hidden="true"></i></summary>
                    <div class="sortir-metadata-fields">
                        <div><label for="sortir-date">Tanggal Transaksi</label><input id="sortir-date" name="date" type="date" x-model="date" @change="markDirty()" required></div>
                        <div><label for="sortir-invoice">Nomor Faktur</label><input id="sortir-invoice" name="invoice_number" type="text" :value="invoiceNumber" readonly></div>
                    </div>
                </details>
            </section>
            <section class="sortir-items-card" aria-label="Daftar item sortir">
                <div class="sortir-items-header"><h1>Daftar Item Sortir</h1><button type="button" class="sortir-add-button" @click="openAddModal()" :disabled="saving || !branchId">+ Tambah Item</button></div>
                @include('transaction::sortir.segment.cart')
            </section>
        </div>
        @include('transaction::sortir.segment.ringkasan')
        @include('transaction::sortir.segment.modal-product')
    </div>
    @include('transaction::sortir.js-create')
@endsection
