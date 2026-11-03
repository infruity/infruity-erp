<link rel="stylesheet" href="{{ asset('assets/css/order-book-detail.css') }}?v={{ filemtime(public_path('assets/css/order-book-detail.css')) }}">
<script src="{{ asset('assets/js/order-book-detail.js') }}?v={{ filemtime(public_path('assets/js/order-book-detail.js')) }}"></script>
<div x-data="orderBookDetailDialog()" data-order-book-timezone="{{ config('app.timezone') }}">
    <template x-teleport="body">
        <x-erp.detail-dialog open="detailOpen" close="closeDetail()" title="Detail Pesanan" labelledby="order-book-detail-title"
            id="order-book-detail-dialog" x-ref="detailDialog" @keydown.tab.window="trapDetailFocus($event)">
            <div class="order-book-detail__fields">
                <div class="order-book-detail__field">
                    <span class="order-book-detail__icon" aria-hidden="true"><i class="ph-duotone ph-user"></i></span>
                    <div class="order-book-detail__value">
                        <div class="order-book-detail__primary" x-text="selected.customerDisplay"></div>
                        <a class="order-book-detail__phone" x-show="selected.whatsappUrl" :href="selected.whatsappUrl" target="_blank" rel="noopener noreferrer" x-text="selected.phone"></a>
                        <span class="order-book-detail__secondary" x-show="!selected.whatsappUrl" x-text="selected.phone"></span>
                    </div>
                </div>
                <div class="order-book-detail__field">
                    <span class="order-book-detail__icon" aria-hidden="true"><i class="ph-duotone ph-storefront"></i></span>
                    <div class="order-book-detail__value">
                        <div class="order-book-detail__primary" x-text="selected.branch"></div>
                        <div class="order-book-detail__secondary">Cabang Penerima</div>
                    </div>
                </div>
                <div class="order-book-detail__field">
                    <span class="order-book-detail__icon" aria-hidden="true"><i class="ph-duotone ph-calendar-blank"></i></span>
                    <div class="order-book-detail__value">
                        <div class="order-book-detail__primary" x-text="selected.createdAt"></div>
                        <div class="order-book-detail__secondary" x-text="selected.createdBy"></div>
                    </div>
                </div>
                <div class="order-book-detail__field">
                    <span class="order-book-detail__icon" aria-hidden="true"><i class="ph-duotone ph-pencil-simple"></i></span>
                    <div class="order-book-detail__value">
                        <div class="order-book-detail__primary" :class="{ 'order-book-detail__muted': selected.editedAt === '-' }" x-text="selected.editedAt"></div>
                        <div class="order-book-detail__secondary" x-text="selected.editedBy"></div>
                    </div>
                </div>
            </div>
            <x-slot:actions>
                <div class="order-book-detail__footer">
                    <span class="order-book-detail__locked" x-show="selected.locked">Pesanan terkunci</span>
                    <div class="order-book-detail__actions" x-ref="detailActions"></div>
                </div>
            </x-slot:actions>
        </x-erp.detail-dialog>
    </template>
</div>
