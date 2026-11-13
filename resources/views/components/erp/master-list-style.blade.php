{{--
    Mobile list chrome of the Master sub modules (Produk, Resep Produk, Satuan Produk, Pemasok, Pelanggan, Cabang,
    Akun Pengguna, Jabatan, Karyawan, Kurir, Metode Pembayaran), as drawn by the HTML reference:
      - search area: 16px padding, a 1px gray-100 line under it, a 44px pill with a 6px right gutter, weight-500 text,
        a muted filter icon and an emerald-500 focus ring;
      - the "Muat N ... Lagi" row sits 16px above the dock (list bottom padding 80px).
    Only the pages that include this component change; the shared erp-mobile-search.css stays as it is for the rest of the app.
--}}
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
        /* Bottom of the list: 96px (last row 16px above the dock); 80px while a "Muat N ... Lagi" row ends the list */
        .mobile-list-edge-fade.mobile-list-edge-fade { padding-bottom: 96px; }
        .mobile-list-edge-fade.mobile-list-edge-fade:has([data-load-more]:not(.hidden):not([style*="display: none"])) { padding-bottom: 80px; }
        /* Reference rows carry their line on top, so the last row of the list has no line below it */
        .mobile-list-edge-fade > div.border-b:has(+ :not(div.border-b)),
        .mobile-list-edge-fade > div.border-b:last-child,
        #recipe-list-items > div:last-child { border-bottom-width: 0; }
        #products-table.dataTable tbody tr:last-child { border-bottom-width: 0 !important; }
        .erp-mobile-search:focus-within { border-color: #10b981; background: #fff; box-shadow: 0 0 0 1px #10b981; }
    }
</style>
