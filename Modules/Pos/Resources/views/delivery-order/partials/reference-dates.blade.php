<section class="delivery-order-filter-sheet__section delivery-order-reference-period">
    <div class="delivery-order-reference-label-row">
        <h3 class="delivery-order-filter-sheet__label">Periode Tanggal</h3>
        <button type="button" class="delivery-order-reference-reset" data-delivery-date-clear hidden>Reset</button>
    </div>
    <div class="delivery-order-reference-range">
        <button type="button" data-delivery-date="start" aria-label="Tanggal mulai" aria-expanded="false"><span>DARI</span><span data-delivery-date-label="start">Pilih Tanggal</span></button>
        <button type="button" data-delivery-date="end" aria-label="Tanggal akhir" aria-expanded="false"><span>KE</span><span data-delivery-date-label="end">Pilih Tanggal</span></button>
    </div>
    <h4 class="delivery-order-filter-sheet__label delivery-order-reference-quick-label">Pilihan Cepat</h4>
    <div class="delivery-order-reference-presets">
        <button type="button" data-delivery-date-preset="week">Minggu Ini</button>
        <button type="button" data-delivery-date-preset="today">Hari Ini</button>
    </div>
</section>
<section class="delivery-order-calendar" data-delivery-calendar hidden aria-label="Kalender periode pengiriman">
    <div class="delivery-order-calendar__header">
        <button type="button" data-delivery-calendar-heading aria-label="Pilih bulan dan tahun"></button>
        <div>
            <button type="button" data-delivery-calendar-move="-1" aria-label="Periode sebelumnya"><i class="ph-bold ph-caret-left" aria-hidden="true"></i></button>
            <button type="button" data-delivery-calendar-move="1" aria-label="Periode berikutnya"><i class="ph-bold ph-caret-right" aria-hidden="true"></i></button>
        </div>
    </div>
    <div class="delivery-order-calendar__weekdays" data-delivery-calendar-weekdays aria-hidden="true"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div>
    <div class="delivery-order-calendar__grid" data-delivery-calendar-grid></div>
    <button type="button" class="delivery-order-calendar__back" data-delivery-calendar-back><i class="ph-bold ph-arrow-left" aria-hidden="true"></i>Kembali</button>
</section>
