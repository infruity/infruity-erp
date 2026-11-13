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
        .erp-mobile-search { height: 44px; padding: 0 6px 0 16px; border-color: rgba(229, 231, 235, .8); }
        .erp-mobile-search__input { font-weight: 500; }
        .erp-mobile-search__filter { color: #9ca3af; }
        .erp-mobile-search:focus-within { border-color: #10b981; background: #fff; box-shadow: 0 0 0 1px #10b981; }
    }
</style>
