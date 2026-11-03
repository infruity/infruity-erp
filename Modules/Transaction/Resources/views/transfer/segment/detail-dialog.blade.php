<template x-teleport="body">
    <div x-show="selected" x-cloak class="transfer-overlay" @click.self="closeDetail()" @keydown.escape.window="if (selected) { documentOpen ? closeDocument() : closeDetail() }" @keydown.tab="trapFocus($event)">
        <section class="transfer-dialog transfer-shipment-dialog" :class="documentOpen ? 'is-document' : ''" id="transfer-detail-dialog" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="transfer-detail-title">
            <div class="transfer-dialog-head">
                <h2 id="transfer-detail-title" x-text="documentOpen ? 'Surat Jalan' : @js($receiving ? 'Detail Penerimaan' : 'Detail Pengiriman')"></h2>
                <button type="button" class="transfer-icon-button transfer-no-print" @click="documentOpen ? closeDocument() : closeDetail()" :disabled="deleting" :aria-label="documentOpen ? 'Kembali ke detail transfer' : 'Tutup detail transfer'"><i class="ph-bold" :class="documentOpen ? 'ph-arrow-left' : 'ph-x'" aria-hidden="true"></i></button>
            </div>
            <template x-if="selected">
                <div>
                    <div x-show="!documentOpen" class="transfer-shipment-details">
                        <dl class="transfer-shipment-facts">
                            <div><dt><i class="ph-duotone ph-receipt" aria-hidden="true"></i>No. Pengiriman</dt><dd x-text="selected.invoice_number"></dd></div>
                            <div><dt><i class="ph-duotone ph-info" aria-hidden="true"></i>Status Pengiriman</dt><dd class="transfer-shipment-status" :class="selected.status === 'selesai' ? 'is-final' : (selected.status === 'pending' ? 'is-pending' : '')" x-text="statusLabel(selected.status)"></dd></div>
                            <div><dt><i class="ph-duotone ph-storefront" aria-hidden="true"></i>Cabang Pengirim</dt><dd x-text="selected.origin"></dd></div>
                            <div><dt><i class="ph-duotone ph-paper-plane-tilt" aria-hidden="true"></i>Pengirim</dt><dd x-text="selected.staff_name || '—'"></dd></div>
                            <div><dt><i class="ph-duotone ph-calendar" aria-hidden="true"></i>Tanggal Pengiriman</dt><dd x-text="dayLabel(selected.date)"></dd></div>
                            <div><dt><i class="ph-duotone ph-buildings" aria-hidden="true"></i>Cabang Penerima</dt><dd x-text="selected.destination"></dd></div>
                            <div><dt><i class="ph-duotone ph-user-check" aria-hidden="true"></i>Penerima</dt><dd x-text="selected.receiver_name || '—'"></dd></div>
                            <div><dt><i class="ph-duotone ph-calendar-check" aria-hidden="true"></i>Waktu Menerima</dt><dd x-text="dateLabel(selected.received_at)"></dd></div>
                        </dl>
                        <button type="button" id="transfer-document-trigger" class="transfer-document-trigger" x-show="selected.can_show" @click="openDocument()"><i class="ph-bold ph-file-text" aria-hidden="true"></i><span x-text="'Lihat Surat Jalan · ' + selected.items.length + ' Produk'"></span><i class="ph-bold ph-caret-right" aria-hidden="true"></i></button>
                    </div>
                    <div x-show="documentOpen" class="transfer-document">
                        <div class="transfer-document-heading"><img src="{{ asset('infruity-ui/003. ERP/assets/logo/Infruity Logo - 6.png') }}" alt="Infruity" class="transfer-document-logo"><div><strong x-text="selected.invoice_number"></strong><span x-text="dayLabel(selected.date)"></span><span class="transfer-shipment-status" :class="selected.status === 'selesai' ? 'is-final' : (selected.status === 'pending' ? 'is-pending' : '')" x-text="statusLabel(selected.status)"></span></div></div>
                        <dl class="transfer-document-parties"><div><dt>Cabang Pengirim</dt><dd x-text="selected.origin"></dd><small x-text="selected.staff_name || '—'"></small></div><div><dt>Cabang Penerima</dt><dd x-text="selected.destination"></dd><small x-text="selected.receiver_name || 'Belum diterima'"></small></div></dl>
                        <div class="transfer-document-table-wrap"><table class="transfer-document-table"><caption>Rincian Produk</caption><thead><tr><th scope="col">Produk</th><th scope="col" class="is-numeric">Dikirim</th><th scope="col" class="is-numeric" x-show="selected.status === 'selesai'">Diterima</th><th scope="col" class="is-numeric">Satuan</th></tr></thead><tbody><template x-for="item in selected.items" :key="item.id"><tr><th scope="row"><span x-text="item.name"></span><small x-show="item.note" x-text="item.note"></small></th><td class="is-numeric" x-text="quantity(item.sent)"></td><td class="is-numeric" x-show="selected.status === 'selesai'" x-text="quantity(item.quantity)"></td><td class="is-numeric" x-text="item.unit || '—'"></td></tr></template></tbody></table></div>
                        <p class="transfer-document-received" x-show="selected.received_at" x-text="'Diterima: ' + dateLabel(selected.received_at)"></p>
                    </div>
                    <div class="transfer-shipment-primary transfer-no-print" x-show="!documentOpen && selected.can_show">
                        <a :href="selected.show_url" x-text="selected.can_receive ? 'Terima Barang' : 'Lihat Rincian'"></a>
                    </div>
                    <div class="transfer-detail-actions transfer-no-print" x-show="!documentOpen && (selected.can_edit || selected.can_delete)">
                        <a x-show="selected.can_edit" :href="selected.edit_url"><i class="ph-bold ph-pencil-simple" aria-hidden="true"></i>Edit</a>
                        <button type="button" x-show="selected.can_delete" class="transfer-danger" @click="deleteSelected()" :disabled="deleting"><i class="ph-bold ph-trash" aria-hidden="true"></i><span x-text="deleting ? 'Menghapus...' : 'Hapus'"></span></button>
                    </div>
                    <div class="transfer-document-actions transfer-no-print" x-show="documentOpen"><button type="button" @click="closeDocument()">Kembali</button><button type="button" class="is-primary" @click="printDocument()"><i class="ph-bold ph-printer" aria-hidden="true"></i>Cetak Surat Jalan</button></div>
                </div>
            </template>
        </section>
    </div>
</template>
