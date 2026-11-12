# Evaluasi MySQL strict mode

Bagian dari [pre-deploy-test-plan.md](pre-deploy-test-plan.md). Tanggal: 2026-11-12.

## Mengapa

`config/database.php` memakai `strict => false`. Dengan mode itu MySQL **diam-diam** menyimpan `0`, `''` atau nilai terpotong untuk apa pun yang tidak muat. Hampir semua bug data yang ditemukan di QA berakar di sini: `complete` → `''` (stok pengadaan hilang), `jus` → `''`, draf bercabang `0`, HPP dibulatkan, enum kosong, catatan terpotong. Pertanyaannya: apa yang pecah bila MySQL menolak nilai seperti itu?

## Cara menyalakan (default TIDAK berubah)

Sakelar lewat `.env`; tanpa isi apa pun perilakunya tetap non-strict.

| Pengaturan | `sql_mode` yang berlaku |
|---|---|
| (kosong, default) | `NO_ENGINE_SUBSTITUTION` |
| `DB_SQL_MODES=STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION` (**strict sempit**) | hanya penolakan nilai yang tidak muat |
| `DB_STRICT=true` (**strict penuh** milik Laravel) | `ONLY_FULL_GROUP_BY`, `STRICT_TRANS_TABLES`, `NO_ZERO_IN_DATE`, `NO_ZERO_DATE`, `ERROR_FOR_DIVISION_BY_ZERO`, `NO_ENGINE_SUBSTITUTION` |

## Hasil pengujian

| Uji | Non-strict | Strict sempit | Strict penuh |
|---|---|---|---|
| Gerbang MySQL (133 test) | ✅ | ✅ | ✅ |
| Ledger HPP (8 test) | ✅ | ✅ | ✅ |
| `migrate:fresh` (92 migration) | ✅ | ✅ | ✅ (setelah S1) |
| Alur impor penuh (dump → impor → rekonsiliasi, data nyata) | 170 OK / 0 FAIL | – | 170 OK / 0 FAIL |
| 298 rute GET + endpoint data pada data nyata hasil impor | baseline | **0 beda** | **0 beda** (setelah S3) |

Penelusuran rute memakai akun admin pada database hasil impor (volume data sebenarnya, termasuk semua laporan). Sebelum perbaikan, strict penuh mematahkan 2 rute: `/dashboard` dan `/crm-dashboard`, yaitu halaman setelah login.

## Temuan (semua sudah diperbaiki, masing-masing dengan test)

| # | Severity | Temuan | Perbaikan |
|---|---|---|---|
| S1 | High | **`migrate:fresh` gagal total di mode strict**: data awal di migration memuat `nickname` "datamangement" (13 karakter) ke `varchar(10)` — migration bergantung pada pemotongan diam-diam. | `users.nickname` → `varchar(30)` di migration pembuatnya. |
| S2 | High | `WholesaleController::saveTransaction` membuat baris `wholesale` tanpa `order_number` (diisi sesudahnya) padahal kolomnya `NOT NULL` tanpa default → **semua simpan pengadaan baru 500** di mode strict (20 test pecah). | Nomor nota dibuat bersamaan dengan insert; perilaku tetap sama di non-strict. |
| S3 | Medium | Dashboard CRM mengelompokkan pengeluaran dengan `groupBy('name')`, yang oleh MySQL dibaca sebagai kolom `products.name`, bukan label yang tampil. Di strict penuh → 500. **Di non-strict hasilnya salah**: pada data nyata seluruh **154 baris pengeluaran (Rp 16,9 jt) digabung jadi 1 kelompok, seharusnya 87**, sehingga daftar pengeluaran tertinggi/terendah di dashboard keliru. | Kelompokkan menurut ekspresi label. `CrmDashboardExpenseTest`. |
| S4 | Medium | `stock_opname_history.note` `varchar(255)`, padahal alasan penyesuaian divalidasi sampai 1.000 karakter dan catatan hitungan tanpa batas: terpotong diam-diam di 255 (satu kasus nyata di data lama); di strict → 500. | Migration `…_000004_widen_stock_opname_history_note` (`TEXT`). 2 test. |
| S5 | Low | Fixture `ProductHppLedgerTest` mengisi `products.tipe = 'buah'` (bukan nilai enum), bergantung pada pemotongan menjadi `''`. | Fixture memakai `product`. |

Catatan terpisah (bukan karena strict): 18 rute GET berstatus 500 di **semua** mode (view/method tidak ada, tabel `t_permohonan` dari aplikasi lain, kunci API AI) — kode mati yang perlu dibersihkan; dan 5 deprecation PHP 8.5 (`(Double)` di `StockOpnameController` ×3 dan `DashboardController`, `${var}` di `GoogleAuthenticator`).

## Rekomendasi

1. **Nyalakan strict sempit** (`DB_SQL_MODES=STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION`) — **di staging dulu**, jalankan checklist manual Fase 3 dan form master data, baru production. Strict penuh juga lulus semua uji sekarang, tetapi `ONLY_FULL_GROUP_BY` hanya memberi sedikit manfaat dan paling mungkin mematahkan kueri filter yang tidak tercakup; jadikan langkah terpisah bila diinginkan.
2. **Jangan menyalakannya langsung di production** tanpa masa uji: lihat bagian bawah.
3. Setelah menyala, kegagalan yang tadinya diam-diam menjadi **error 500 yang terlihat** (dan tercatat di log). Itu tujuannya, tetapi pantau `storage/logs/laravel.log` pada minggu pertama.

## Yang BELUM terbukti

- **Penulisan di modul tanpa test**: master data (produk, pelanggan, pemasok, cabang, staf, user, role), produksi, sortir, order book, arus kas, CRM (tier, deposito), pengaturan, chat. Test mencakup login, POS, transfer, pengadaan, stok opname, laporan, impor.
- Input teks pengguna yang lebih panjang dari kolomnya dan tidak divalidasi `max:` akan berubah dari terpotong diam-diam menjadi 500. Contoh yang sudah ditutup: catatan opname. Pola serupa masih mungkin di form lain; `ProductController` juga tidak memvalidasi `tipe`/`status` terhadap daftar nilai yang sah (nilai palsu kini `''`, di strict 500).
- Kolom `NOT NULL` tanpa default yang tidak diisi kode di modul tak teruji. Sinyal dari data lama hanya menunjuk kolom `discount` (`transfer_detail`, `sortir_transaction_detail`, `expenditure_detail`, `pos_transaction_detail`) dan `expenditure_payment.total`; jalur transfer dan POS sudah lulus, sortir dan pengeluaran belum diuji.
