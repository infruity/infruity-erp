{{--
    Mobile list chrome of the Master sub modules (Produk, Resep Produk, Satuan Produk, Pemasok, Pelanggan, Cabang,
    Akun Pengguna, Jabatan, Karyawan, Kurir, Metode Pembayaran), as drawn by the HTML reference:
      - search area: 16px padding, a 1px gray-100 line under it, a 44px pill with a 6px right gutter, weight-500 text,
        a muted filter icon and an emerald-500 focus ring;
      - the "Muat N ... Lagi" row sits 16px above the dock (list bottom padding 80px).
    Only the pages that include this component change; the shared erp-mobile-search.css stays as it is for the rest of the app.
--}}
<style>
    /* Master modules draw their avatar tiles with the -600 text / -100 border shades of the reference. */
    .erp-ui .erp-tone-0{color:#059669;border-color:#d1fae5;}
    .erp-ui .erp-tone-1{color:#2563eb;border-color:#dbeafe;}
    .erp-ui .erp-tone-2{color:#9333ea;border-color:#f3e8ff;}
    .erp-ui .erp-tone-3{color:#d97706;border-color:#fef3c7;}
    .erp-ui .erp-tone-4{color:#4f46e5;border-color:#e0e7ff;}
    .erp-ui .erp-tone-5{color:#0284c7;border-color:#e0f2fe;}
    .erp-ui .erp-tone-6{color:#c026d3;border-color:#fae8ff;}
    .erp-ui .erp-tone-7{color:#0d9488;border-color:#ccfbf1;}
    .erp-ui .erp-tone-8{color:#e11d48;border-color:#ffe4e6;}
    .erp-ui .erp-tone-9{color:#ea580c;border-color:#ffedd5;}
    /* Form sheet fields follow the reference: emerald-500 border + 1px ring, dark caret. */
    .erp-ui [id^="master-field-"]:focus, .erp-ui [id^="master-field-"]:focus-visible { border-color:#10b981; box-shadow:0 0 0 1px #10b981; outline:0; caret-color:#1f2937; }
</style>
<style>
    @media (max-width: 767px) {
        .erp-mobile-search-shell { padding: 16px; border-bottom: 1px solid #f3f4f6; }
        .erp-mobile-search { height: 44px; gap: 0; padding: 0 6px 0 16px; border-color: rgba(229, 231, 235, .8); box-shadow: none; }
        .erp-mobile-search__icon { margin-right: 10px; }
        /* Reference magnifier: 18px SVG, stroke 2 (the global one is the thin Phosphor glyph) */
        i.erp-mobile-search__icon::before {
            content: ''; display: block; width: 18px; height: 18px; background: currentColor;
            -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") center / contain no-repeat;
            mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") center / contain no-repeat;
        }
        .erp-mobile-search__clear { width: 24px; height: 24px; }
        .erp-mobile-search__divider { height: 16px; margin: 0 4px; }
        .erp-mobile-search__input { font-weight: 500; }
        .erp-mobile-search__filter { color: #9ca3af; }
        .erp-mobile-search__filter .icon-rounded { font-size: 20px; line-height: 1; }
        .erp-mobile-search__filter.is-active .icon-rounded { font-variation-settings: 'FILL' 0, 'wght' 600; }
        /* Pages without a filter keep the reference's muted, non-interactive icon */
        .erp-mobile-search__filter--idle { display: flex; align-items: center; justify-content: center; color: #d1d5db; cursor: default; pointer-events: none; user-select: none; }
        /* Bottom of the list: 96px (last row 16px above the dock); 80px while a "Muat N ... Lagi" row ends the list */
        .mobile-list-edge-fade.mobile-list-edge-fade, div[data-erp-list] { padding-bottom: 96px; }
        .mobile-list-edge-fade.mobile-list-edge-fade:has([data-load-more]:not(.hidden):not([style*="display: none"])) { padding-bottom: 80px; }
        /* Reference rows carry their 1px line on top (none under the last row) */
        .mobile-list-edge-fade > div.border-b,
        [data-erp-list] > button.border-b,
        #recipe-list-items > div { border-bottom-width: 0; border-top-width: 1px; border-top-style: solid; border-top-color: #f3f4f6; }
        body #products-table.dataTable tbody tr { border-top: 1px solid #f3f4f6 !important; border-bottom: 0 !important; }
        /* Form fields of the Master sheets: 16px labels and the reference's lighter red star */
        .erp-field__label { line-height: 16px; }
        .erp-field__req { color: #f87171; }
        .erp-mobile-search:focus-within { border-color: #10b981; background: #fff; box-shadow: 0 0 0 1px #10b981; }
    }
</style>
