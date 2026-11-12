# Fase 2 — Latihan Impor Data Lama ke Skema Baru

Bagian dari [pre-deploy-test-plan.md](pre-deploy-test-plan.md). Tanggal latihan: 2026-11-12.

## Hasil singkat

| | |
|---|---|
| Sumber (data lama) | database `infruity_clone` (baca saja; **stand-in** untuk data production lama) |
| Target | `infruity_import_rehearsal`, dibuat dengan `migrate:fresh` (skema yang akan dipakai production) |
| Impor | 68 tabel, 0,8–1,0 detik; preflight 0,2 detik; rekonsiliasi 1,7 detik |
| Rekonsiliasi | **177 pemeriksaan: 170 OK, 7 WARN, 0 FAIL** (7 WARN = data yatim/duplikat bawaan data lama, sama di sumber dan hasil impor; HPP cocok **persis**) |
| Diulang dari nol | ya, hasil identik (idempoten) |
| Aplikasi di atas data hasil impor | login dengan akun lama (hash tersalin utuh) ✅, 15 halaman inti 200 ✅, simpan pengadaan baru + stok naik tepat ✅ |
| Auto-increment | semua 68 tabel: id berikutnya > id tertinggi ✅ |

Latihan ini dijalankan terhadap salinan data dev, bukan dump production sungguhan. Prosedur dan perintahnya sama persis untuk dump sungguhan; **ulangi latihan dengan dump production yang sebenarnya sebelum hari-H**.

## Mengapa tidak bisa "salin database apa adanya"

Data lama punya nama tabel dan kolom yang sama dengan skema baru, tetapi:

- **Tidak ada primary key / auto_increment**: 76 dari 83 tabel tanpa PK, 1 foreign key (skema baru: 1 tabel tanpa PK, 8 FK). Tabel itu tidak bisa dipakai menulis transaksi baru.
- 14 kolom beda tipe, 8 kolom menjadi `NOT NULL`, dan satu enum menyempit (lihat temuan). Dua selisih tipe yang berbahaya (enum `jus`, `products.hpp`) sudah diperbaiki lewat migration; sisanya tidak mengubah nilai.
- 8 tabel "view" lama (`vw_*`, `view_wholesale`, `sortir_view`) adalah tabel biasa di data lama, tetapi view sungguhan di skema baru.

Karena itu impor memakai kolom-per-kolom berdasarkan nama, dalam **mode STRICT** (nilai yang tidak muat menghentikan impor, bukan terpotong diam-diam), setelah **preflight** yang mencari semua nilai bermasalah lebih dulu.

## Runbook (untuk hari-H)

Semua perintah dijalankan di folder aplikasi baru dengan PHP 8.5.

0. **Di server lama** (hanya membaca; tidak mengubah apa pun di database lama), ambil dump tabel-saja:
   ```bash
   scripts/legacy/dump-legacy.sh <nama_db_lama> infruity_legacy.sql.gz
   ```
   Nama database lama ada di `.env` aplikasi lama (`DB_DATABASE`). View sengaja dikeluarkan: `mysqldump` tidak selalu bisa memuat ulangnya (view dengan CTE rekursif gagal), dan aplikasi baru membangun view-nya sendiri. Salin file itu, dan folder `storage/app/public/logo/` bila ada, ke server baru.
1. **Di server baru, muat dump ke database sementara** (sumber untuk impor). Skrip ini juga membangun view ledger stok/HPP di database sementara, yang dibutuhkan rekonsiliasi:
   ```bash
   scripts/legacy/load-legacy.sh infruity_legacy infruity_legacy.sql.gz
   ```
   Menolak menimpa database yang sudah ada kecuali diberi `--replace`.
2. **Buat database target kosong** dan bangun skemanya dari nol (termasuk dua migration baru 2026_11_12):
   ```bash
   DB_DATABASE=infruity_prod php artisan migrate:fresh --force
   ```
3. **Preflight** (tidak mengubah apa pun; berhenti dengan kode 1 bila ada masalah):
   ```bash
   DB_DATABASE=infruity_prod php artisan legacy:import infruity_legacy --dry-run
   ```
4. **Impor** (MENGOSONGKAN tabel target satu per satu; wajib mengetik nama database target):
   ```bash
   DB_DATABASE=infruity_prod php artisan legacy:import infruity_legacy --confirm-target=infruity_prod
   ```
5. **Rekonsiliasi** (baca saja; kode 1 bila ada FAIL):
   ```bash
   DB_DATABASE=infruity_prod php artisan legacy:reconcile infruity_legacy        # hanya yang tidak OK
   DB_DATABASE=infruity_prod php artisan legacy:reconcile infruity_legacy --all  # semua pemeriksaan
   ```
6. **Salin file unggahan** (lihat bawah), lalu uji asap aplikasi (login, satu transaksi POS, satu pengadaan, satu laporan).

Pengaman: perintah menolak bila sumber = target, nama database tidak aman, atau `--confirm-target` tidak sama dengan database aktif. Tabel `migrations` dan data wilayah (`reg_*`) tidak pernah disentuh.

## Temuan skema dan data

| # | Severity | Temuan | Penanganan |
|---|---|---|---|
| D3 | **High** | Kode POS menulis `pos_transaction_detail.type = 'jus'`, tetapi migration hanya mendefinisikan `ENUM('product','parcel')`. Data lama berjalan dengan enum yang diubah manual. Pada database hasil migration, baris jus disimpan dengan `type` **kosong** (koneksi non-strict) → penjualan jus kehilangan penanda dan pengecekan stok jus di `savePayment` tidak pernah cocok. 781 baris data lama akan rusak saat diimpor. | **Diperbaiki:** migration `2026_11_12_000001_add_jus_to_pos_transaction_detail_type`. Preflight kini mendeteksi nilai enum yang tidak bisa disimpan. |
| D4 | Medium | `products.hpp`: `double` di data lama, `INT` di migration. Ledger HPP dan perhitungan produksi/resep/sortir memakai kolom ini dikali kuantitas, jadi pembulatan ke Rupiah menggeser hitungan backend (di latihan pertama: 18 produk, total aset selisih Rp 20,15). | **Diputuskan pemilik: tetap pecahan, dibulatkan hanya di tampilan.** Migration `2026_11_12_000002_make_products_hpp_fractional` (kolom `DOUBLE`, sama dengan data lama). Importer menyalin apa adanya. Tampilan dibulatkan di 3 tempat yang tadinya menampilkan desimal (daftar biaya produksi, daftar produksi parcel, sel HPP bahan di form produksi); tampilan lain sudah membulat. Hasil: HPP **cocok persis** (total aset Rp 42.048.716,77 di kedua sisi). |
| D5 | Medium | **115 produk** (id 677–827, dipakai aktif: 2.425 baris penjualan, 298 baris pengadaan) punya `status` dan `tipe` **kosong** (`''`, nilai yang ditulis MySQL ketika enum tidak dikenali). Komposisi: 93 produk berdiri sendiri, 4 induk, 18 varian (mis. "Mangga Tiojai Grade A/B"). Kode memperlakukan `''` sama seperti `product` (filter hanya `!= 'parcel'` / `= 'kemasan'`). | **Dikonfirmasi pemilik: produk biasa / induk.** Diimpor sebagai default kolom (`tipe='product'`, `status='no-receipt'`), tercatat di preflight. Konsisten dengan induk (10) dan varian (28) lain di data lama yang semuanya `product`/`no-receipt`. |
| D6 | Low | 26 baris `wholesale` ber-**id 0** (draf `temp` otomatis yang tersimpan saat tabel belum punya auto-increment, tanpa item) → id ganda. 3 draf `temp` lain bercabang 0 tetapi id valid. | 26 baris id 0 **tidak diimpor** (tercatat). Tiga draf lain ikut dan dipakai ulang oleh perbaikan W5. |
| D7 | Low | NULL pada kolom yang kini `NOT NULL`: `branch.code` (1 dari 2 cabang), `production.service_cost`/`sell_price` (707), `stock_opname.hpp` (1.775). | Kolom produksi/opname → default kolom (0). `branch.code` **diturunkan dari nama cabang** dan dijamin unik ("Toko GBA" → `GBA`; bila bentrok atau tak ada nama yang bisa dipakai → `BR<id>`); kode hanya label di Master > Cabang (tidak dipakai untuk penomoran/logika), ganti di sana bila kode aslinya beda. |
| D8 | Info | Tabel hanya di data lama: `personal_access_tokens` (0 baris), `product_hpp_branch` (0 baris, tidak dipakai kode), serta 8 tabel "view" lama. | Tidak diimpor; tidak ada data yang hilang. |
| D9 | Medium | Token FCM (`user_devices`, 22 baris) ikut tersalin dari data lama. Kode hanya menghapus token `UNREGISTERED`; token milik proyek Firebase lain dibalas `SENDER_ID_MISMATCH` dan **tidak pernah dihapus**, sehingga gagal dan tercatat sebagai error di tiap pengiriman. | **Diputuskan: token tetap disalin** (bila proyek Firebase dan domain sama, notifikasi langsung jalan tanpa daftar ulang). `FcmService` kini juga menghapus token `SENDER_ID_MISMATCH` (`FcmService::isDeadTokenError`), jadi token yang tidak cocok hilang sendiri pada pengiriman pertama; kegagalan lain (payload, kuota, gangguan) tetap tidak menghapus token. |
| D10 | Low | Logo nota (`setting_nota.logo`, satu-satunya file unggahan yang direferensikan) hanya dipakai untuk pratinjau di Pengaturan Nota; nota cetak tidak memakainya. File yang hilang membuat gambar pecah. | Pratinjau kini jatuh ke gambar kosong bila file tidak ada. Salin `storage/app/public/logo/` + `php artisan storage:link` bila ingin logo lama; atau unggah ulang lewat UI. |
| D11 | Medium | Empat kolom dibuat `NOT NULL` tanpa default padahal aplikasi tidak mengisinya: `wholesale.branch_id` (draf yang dibuat saat form dibuka belum punya cabang; kode memakai `NULL` untuk itu), `wholesale.supplier_id`, `wholesale.uuid`, `wholesale_product.category_id` (tak pernah dipakai: 0/`''` di 100% baris lama). Karena koneksi non-strict, MySQL diam-diam menyimpan `0`/`''`; itulah akar bug W5 (draf bercabang 0 ditolak ke pembuatnya sendiri). | **Diperbaiki di skema baru:** migration `2026_11_12_000003_make_unused_wholesale_columns_nullable` (kolom `NULL`). Importer mengubah `0`/`''` lama menjadi `NULL` (`NULLIF`). Hasil: 3 draf lama bercabang `NULL`, 174 nota bercabang tetap, 0 FAIL. Perbaikan kode W5 tetap dipertahankan sebagai pengaman untuk data lama bercabang 0. |

## Kualitas data lama yang terbawa (WARN, sama persis di sumber dan hasil impor)

Ini bukan kerusakan impor, tetapi akan muncul sebagai "data yatim" di aplikasi baru. Perlu diketahui pemilik data:

| Relasi | Baris yatim |
|---|---|
| `pos_transaction_detail.product_id` → produk yang sudah tidak ada | 117 |
| `product_branch.product_id` → produk | 118 |
| `production_detail.production_id` → produksi | 166 |
| `stock_opname.product_id` → produk | 26 |
| `transfer_detail.product_id` → produk | 14 |
| `wholesale_product.product_id` → produk | 9 |
| nomor nota `pos_transaction.invoice_number` ganda | 18 |

Dampak langsung: laporan "Pendapatan Penjualan" memakai `INNER JOIN products`, jadi baris penjualan yatim tidak ikut dihitung. Terverifikasi tepat: jumlah mentah detail penjualan 2026 Rp 282.142.621 − laporan aplikasi Rp 266.460.265 = Rp 15.682.356, sama dengan nilai baris yatim (Rp 15.682.355, beda pembulatan). Angka mentah identik di sumber dan hasil impor; perilaku ini sudah ada di aplikasi lama.

Lainnya: 12 pengguna aktif (15 nonaktif), **semua pengguna aktif punya role** (tidak ada yang langsung ter-logout). Tidak ada stok negatif di data lama.

## Yang diverifikasi rekonsiliasi (178 pemeriksaan)

- jumlah baris tiap tabel (dengan pengecualian id 0 yang tercatat);
- SUM setiap kolom numerik tiap tabel (menangkap nilai terpotong);
- omzet POS per cabang × bulan × status (jumlah, total, terbayar);
- ledger stok `product_stock`: jumlah baris, total, dan selisih per produk × cabang;
- ledger HPP `product_hpp`: HPP, qty, dan nilai aset terakhir per produk — **harus identik** (selisih sekecil apa pun = FAIL);
- baris yatim dan nomor nota ganda (FAIL hanya bila **impor menambah** masalah);
- akun: id/email/username/hash password/status aktif identik, jumlah `role_menu`.

Rekonsiliasi diuji: setelah 4 kerusakan sengaja pada hasil impor (baris pengadaan dihapus, total POS diubah, hash password diubah, qty opname diubah) muncul **15 FAIL**; satu HPP katalog digeser Rp 0,4 juga langsung FAIL (3 pemeriksaan). Impor ulang memulihkan ke 0 FAIL.

## Yang BELUM tercakup

- **Dump production sungguhan.** Latihan memakai salinan data dev (`infruity_clone`), tetapi seluruh alur sudah dijalankan dari **file dump** lewat kedua skrip (dump → muat → `migrate:fresh` → impor → rekonsiliasi), sama dengan hari-H. Tinggal dijalankan dengan database lama yang asli; saya tidak punya akses ke server itu.
- Waktu yang terukur di sini: `migrate:fresh` (91 migration, termasuk data wilayah) **3 detik**, impor ±32.600 baris **<1 detik**, dump 1,7 MB. Waktu muat dump dan transfer file di server sungguhan bergantung ukuran data dan jaringan; ukur saat latihan di server.
- Kode: `legacy:import` dan `legacy:reconcile` berjalan di MySQL saja dan membutuhkan sumber di server MySQL yang sama dengan target.

## Keputusan (semuanya sudah diputuskan)

| Pertanyaan | Keputusan |
|---|---|
| HPP katalog dibulatkan? | Tetap pecahan di database, dibulatkan hanya di tampilan (D4). |
| 115 produk bertipe kosong? | Produk biasa/induk, dikonfirmasi pemilik (D5). |
| Kode cabang yang kosong? | Diturunkan dari nama dan dapat diganti di Master > Cabang (D7). |
| Token FCM lama? | Disalin; yang tidak cocok dihapus otomatis oleh `FcmService` (D9). |
| Logo nota? | Opsional; pratinjau aman bila file tidak ada (D10). |
| Dump production? | Skrip `scripts/legacy/dump-legacy.sh` dan `load-legacy.sh` (teruji di sini) — tinggal dijalankan di server lama/baru. |
