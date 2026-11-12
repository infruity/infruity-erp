# Rencana Test Sebelum Deploy — Infruity

Target: branch `main` → VPS sendiri (berdampingan dengan Infruity lama, PHP 8.2.35).
Prinsip: **tidak menyentuh app lama**, semua bukti tercatat, keputusan Go/No-Go di akhir.

## Ringkasan fase

| Fase | Tempat | Isi | Gerbang keluar |
|---|---|---|---|
| 0 | Lokal | Siapkan harness test MySQL + data hasil impor | Suite bisa jalan di MySQL |
| 1 | Lokal | Automated test + test baru untuk alur kritis | Semua hijau |
| 2 | Lokal | Rehearsal impor data lama → schema baru | Rekonsiliasi angka cocok |
| 3 | Lokal (HP) | QA manual alur kritis, mobile-first | 0 bug Critical/High terbuka |
| 4 | Lokal | Keamanan & konfigurasi production | Checklist lolos |
| 5 | VPS (staging) | Pasang PHP 8.5-FPM, vhost staging, smoke test | App lama tetap normal |
| 6 | VPS | Go/No-Go, cutover, rollback siap | Ditandatangani |

Severity: **Critical** (data/uang/stok salah, tidak bisa login/bayar) · **High** (alur kritis terganggu, ada workaround) · **Medium** (fitur non-kritis / tampilan mengganggu) · **Low** (kosmetik).

---

## Fase 0 — Persiapan harness

- [ ] Buat DB `infruity_test` (MySQL 8, sama dengan server). `phpunit.xml` (SQLite) tidak dipakai — migration khusus MySQL.
- [ ] Jalankan `php artisan test -c phpunit.mysql.xml`; catat baseline lulus/gagal.
- [ ] Siapkan seeder/fixture minimum untuk test: 1 admin, 1 kasir (staff), 1 gudang, 1 sales; 2 cabang; role/menu; metode bayar; 5 produk dengan stok.
- [ ] Siapkan dump data lama (read-only copy) di DB lokal terpisah `infruity_legacy`.

### Hasil Fase 0 (2026-11-12)

Lingkungan lokal: PHP 8.5.10 (`/opt/homebrew/opt/php@8.5/bin`, CLI default Mac masih 8.3 — selalu prefix PATH ini), MySQL 8.4, `composer install` di worktree.

| Suite | Perintah | Hasil |
|---|---|---|
| Default (SQLite) | `php vendor/bin/phpunit` | 373 test, 2806 assertion, 12 skipped, 0 gagal |
| MySQL | `php vendor/bin/phpunit -c phpunit.mysql.xml` | 4 test (TransferConcurrency) lulus |
| HPP ledger (MySQL) | `HPP_LEDGER_TEST_DATABASE=infruity_test php vendor/bin/phpunit --filter ProductHppLedgerTest` | 8 test lulus |

- Ke-12 test yang skipped di suite default **bukan gagal**; hanya butuh MySQL (8 HPP + 4 Transfer) dan kini sudah dijalankan terpisah → total efektif **385 test hijau**.
- Catatan di `docs/php-upgrade-plan.md` ("4 test gagal karena SQLite") sudah usang: sekarang test itu skip dan lulus di MySQL.
- DB test: `infruity_test` (sudah ada & ter-migrate). `ProductHppLedgerTest` menjalankan `migrate:fresh` di DB itu — hanya boleh DB berakhiran `_test`. DB dev `infruity_clone` tidak tersentuh.
- Peringatan non-blokir: skema XML phpunit usang (`--migrate-configuration`).
- **Celah yang terbukti:** seluruh suite adalah UI contract + migration + ledger HPP. Belum ada test perilaku untuk login, POS bayar, stok transfer end-to-end, dan laporan → itu isi Fase 1.

## Fase 1 — Automated test

Sudah ada 86 file, mayoritas UI contract + test migration. Tambahkan **feature test perilaku** untuk alur kritis (bukan sekadar markup):

**Login**
- [x] Login benar/salah, user nonaktif, user tanpa role (captcha tidak aktif di kode).
- [ ] User hanya melihat menu & cabang sesuai role (`UserPermissionOverridesTest` diperluas).
- [ ] Route admin (`user.impersonate`, `role.switch`, `user.permissions.update`, `roles.*`) menolak non-admin (403).
- [ ] Login Guest tidak bisa menulis ke DB utama.

**POS bayar**
- [ ] `pos-submit` → `pos.payment` → `pos.save-transaction`: total, diskon, kembalian benar (rupiah bulat).
- [ ] Stok cabang berkurang tepat sesuai qty; transaksi gagal di tengah → rollback (stok & transaksi tidak setengah jadi).
- [ ] Double submit (klik bayar dua kali) tidak membuat dua transaksi / nomor nota ganda.
- [ ] Nomor nota unik per cabang (`pos.cek-nota`).
- [ ] Bayar lebih kecil dari total ditolak / tercatat sesuai aturan bisnis.

**Stok**
- [ ] Penerimaan barang (wholesale) menambah stok cabang tujuan.
- [ ] Transfer pengirim → penerima: stok keluar = stok masuk; `TransferConcurrencyTest` harus lulus di MySQL.
- [ ] Stok opname finalize/adjust menghasilkan selisih yang benar; submit ganda dicegah.
- [ ] Sortir & produksi tidak membuat stok negatif tanpa izin.

**Laporan**
- [ ] Untuk dataset fixture yang diketahui, angka laporan transaksi, penjualan produk, profit, total aset = hitungan manual.
- [ ] Filter tanggal & cabang benar (batas awal/akhir hari, timezone Asia/Jakarta).
- [ ] Export Excel/PDF berhasil dan angkanya sama dengan layar.

**Feature flag**
- [ ] Pengeluaran, Pemasukan, Stok Keluar tetap 404/tersembunyi saat flag off (`HiddenPagesTest`).

Gerbang: semua test hijau di MySQL.

### Hasil Fase 1 (2026-11-12)

122 test baru di gerbang (+1 `known-gap`, lihat W1), semua gerbang lulus. Dijalankan di MySQL (di SQLite otomatis *skipped*):

```bash
export PATH=/opt/homebrew/opt/php@8.5/bin:$PATH
php vendor/bin/phpunit -c phpunit.mysql.xml          # 126 test (122 baru + 4 TransferConcurrency), gerbang hijau
php vendor/bin/phpunit -c phpunit.mysql.xml --group known-gap   # 1 test yang SENGAJA gagal (sisa W1: total header vs item)
php vendor/bin/phpunit                               # 512 test, 135 skipped (butuh MySQL), 0 gagal
```

| File | Test | Yang dibuktikan |
|---|---|---|
| `LoginAccessBehaviorTest` | 15 | login username/email, password salah, user nonaktif, user tanpa role, guest dialihkan, `/register` tertutup, impersonate hanya user id 1, endpoint admin menolak role tanpa izin |
| `PosPaymentBehaviorTest` | 20 | draft tidak memotong stok, bayar pas/kembalian/utang/split, kembalian hanya tunai, idempotency (tidak dobel charge), nota lunas tidak bisa diedit/dibayar lagi, rollback saat stok habis, scope cabang, izin |
| `TransferStockBehaviorTest` | 12 | kirim memotong stok asal, terima menambah stok tujuan, terima kurang = susut, tidak bisa terima > kirim / dua kali, kirim > stok ditolak, cabang & izin |
| `WholesaleBehaviorTest` | 28 (+1 known-gap) | posting menambah stok cabang terpilih, draft/temp tidak, dua posting menumpuk, HPP bergerak ke rata-rata bergerak (10@10.000 + 10@20.000 → 15.000), harga jual cabang tersimpan, wajib ada item, akses cabang & izin, tidak bisa menimpa nota cabang lain, stok tidak boleh negatif saat nota posting diedit/dihapus (W2), item wajib valid saat simpan draft/posting tetapi autosave `temp` tetap longgar (W1), field cart (`sell`, `total_input`) tetap tersimpan, endpoint lama `set-selesai`/`receive`/`save-receive` tidak lagi menghapus stok (W3), `/wholesale/reset` ditolak di luar local/testing (W4) |
| `StockOpnameBehaviorTest` | 18 | hitungan baru hanya mencatat (stok tidak bergerak), satu produk satu hitungan pending per hari, finalisasi mereset stok ke hitungan fisik (kurang/lebih/nol), **penjualan di antara hitung & finalisasi tidak mengubah hasil akhir = hitungan fisik**, tidak bisa finalisasi/edit/adjust/hapus dua kali, adjust tidak boleh < 0, akses cabang & izin |
| `ReportBehaviorTest` | 9 | angka penjualan = hitungan manual (paid+debt saja, draft/batal/terhapus/cabang lain tidak ikut), rentang tanggal inklusif, semua endpoint data menolak role tanpa izin, laporan lintas cabang hanya super admin, user cabang tidak bisa lihat cabang lain |

Validasi test (mutation check): merusak sengaja 7 logika (laporan ikut hitung draft, terima > kirim diizinkan, kembalian non-tunai diizinkan, draft dihitung sebagai posting, finalisasi tidak menutup hitungan, guard "sudah final" dihapus, cek cabang pengadaan dihapus) → masing-masing tertangkap oleh test yang tepat, lalu dikembalikan.

Catatan teknis: `WholesaleController::saveTransaction` memanggil `DB::disconnect()`, yang memutus transaksi pembungkus test. Test pengadaan memakai isolasi lain (`QaFixtures::rememberCommittedBaseline()` / `cleanCommittedRows()`: commit sungguhan, lalu hapus semua baris baru) — hanya aman di database `*_test`.

**Temuan dari Fase 1**

| # | Severity | Temuan | Status |
|---|---|---|---|
| 1 | **High** | `GET /user/data` tidak memeriksa izin: role tanpa izin apa pun mendapat 200 berisi email & nomor telepon semua user (hash password tidak ikut). | **Diperbaiki** (`UserController::get_data` kini `authorizeAccess('user.index')`), ada test regresi. |
| 2 | **Medium** | Login tidak dibatasi: 40 password salah berturut-turut tidak diblokir, login benar sesudahnya tetap lolos; captcha di `LoginController` dikomentari. Aplikasi akan terbuka di internet. | **Belum diubah** — perlu keputusan (kasir satu cabang bisa berbagi IP; saran awal `throttle` ±10/menit per IP+username, atau fail2ban/Cloudflare di depan). |
| 3 | Low | `PosController::saveTransaction` dan `StockOpnameController::destroy` membungkus error validasi/404 dalam `catch` → dikembalikan sebagai 500 JSON, bukan 422/404 (penolakan tetap benar). | Dicatat di test, belum diubah. |
| W1 | Medium–High | `WholesaleController::saveTransaction` tidak memvalidasi isi `items`: qty negatif, harga negatif, `product_id` yang tidak ada, dan total yang tidak cocok dengan item diterima (200). | **Sebagian diperbaiki:** untuk simpan `draft`/`posting`, `items.*.id` wajib ada di `products`, `qty > 0`, `price ≥ 0`; autosave `temp` sengaja tetap longgar. **Sisa (known-gap, 1 test):** total/subtotal header belum dicek terhadap item — dihitung di JS dengan diskon, jadi perlu uji form dulu. **Cek manual di Fase 3** (lihat daftar di bawah). |
| W2 | Medium | Nota pengadaan yang sudah `posting` bisa diedit/dihapus setelah barangnya terjual sehingga stok negatif (−5). Aturan bisnis (sesi HPP): **stok tidak boleh negatif**. | **Diperbaiki:** edit (qty lebih kecil, ubah ke draft, ganti produk) dan hapus nota ditolak (422) bila stok produk yang terlibat akan menjadi negatif dan lebih buruk dari sebelumnya; data lama yang sudah negatif tidak ikut disalahkan. 6 test baru. |
| W3 | **High** | Endpoint lama pengadaan `POST /wholesale/set-selesai/{id}` dan `/wholesale/receive/{id}` (juga `save_receive`, `update_receive_product`) mengisi `status='complete'`, padahal enum kolom hanya `draft/posting/temp`. Karena `strict => false` di `config/database.php`, MySQL diam-diam menyimpan status **kosong (`''`)**, dan stok hanya dihitung dari status `posting` → **stok masuk nota itu hilang dari ledger (terbukti: stok 10 → 0 setelah satu request)**. Hanya butuh izin `wholesale.set_selesai`/`wholesale.receive_product` dan tidak dibatasi cabang. Tidak ada tautan menu ke rute ini (hanya view lama `process.blade.php` yang bisa dibuka lewat URL), tetapi rute tetap aktif. | **Diperbaiki:** `receive_product`, `save_receive`, `set_selesai` kini mengembalikan 410 (JSON) / redirect ke daftar dengan pesan, tanpa menulis apa pun; rute tetap terdaftar agar view lama tidak 
| W4 | **High** | `POST /wholesale/reset` (`WholesaleController::reset_transactions`) menjalankan TRUNCATE pada 22 tabel transaksi (POS, pengadaan, transfer, stok opname, produksi, order book, pengeluaran, dll.) hanya dengan izin `wholesale.reset` — super admin otomatis lolos. Tanpa pembatas environment, tanpa konfirmasi password, dan tidak ada pemanggil di UI. Satu request (atau satu klik salah) di production menghapus seluruh data transaksi. | **Diperbaiki:** `reset_transactions` kini mengembalikan 403 kecuali `APP_ENV` adalah `local` atau `testing` (berlaku juga untuk super admin). Test regresi diuji dua arah (tanpa guard, request sungguhan mengosongkan tabel). **Pastikan `APP_ENV=production` di server.** |error. Ada 2 test regresi (diuji dua arah). Catatan: `update_receive_product` tidak diubah (menulis ke kolom lain yang enum-nya valid). Mempertimbangkan `strict => true` masih terbuka (perlu audit kolom lain). |
| W5 | **High** | Penyimpanan pertama dari form pengadaan selalu 403 (draf `temp` dibuat tanpa cabang → `branch_id` 0; aturan akses menolaknya). Ditemukan lewat QA manual di browser; ada di `HEAD` sebelum Fase 1. | **Diperbaiki** + 5 test alur-form (mutasi tertangkap). |
| W6 | **High** | Di halaman edit pengadaan (HP), modal item tergambar di bawah backdrop sehingga tidak bisa disentuh (nota tidak bisa diedit dari UI). | **Diperbaiki** (`animation:none` mode edit) + test kontrak. |
| D1 | **High (Fase 2)** | Database dev `infruity_clone` (dan data lama) **tidak punya primary key / auto_increment**: 76 dari 83 tabel tanpa PK, 1 foreign key (skema `migrate:fresh`: 1 tabel tanpa PK, 8 FK). Tabel itu tidak bisa dipakai menulis transaksi baru ("Field 'id' doesn't have a default value"). | Impor Fase 2 **harus** ke skema hasil `migrate:fresh` dengan id diisi eksplisit; jangan memakai salinan `infruity_clone` apa adanya. |
| D2 | Medium (Fase 4) | Halaman memuat Tailwind dari `cdn.tailwindcss.com` (lambat, bergantung pihak ketiga, tidak untuk production). | Belum diubah; build Tailwind lokal sebelum deploy. |
| D3 | **High** | Kode POS menulis `pos_transaction_detail.type='jus'`, tetapi migration hanya `ENUM('product','parcel')`; pada database hasil migration baris jus tersimpan dengan `type` **kosong** (781 baris data lama akan rusak). Ditemukan saat latihan impor (Fase 2). | **Diperbaiki:** migration `2026_11_12_000001_add_jus_to_pos_transaction_detail_type`; preflight impor mendeteksi enum yang tidak bisa menyimpan nilainya. |
| D4 | Medium | `products.hpp` `double` (data lama) vs `INT` (migration): pembulatan menggeser hitungan ledger/produksi. | **Diperbaiki sesuai keputusan pemilik:** kolom tetap pecahan (migration `…_make_products_hpp_fractional`), importer menyalin apa adanya, tampilan dibulatkan di 3 titik; HPP cocok persis. Test presisi + test tampilan. |
| D5 | Medium | 115 produk aktif (id 677–827; 93 berdiri sendiri, 4 induk, 18 varian) punya `status`/`tipe` kosong di data lama. | Diimpor sebagai default (`product`, `no-receipt`); **dikonfirmasi pemilik** (produk biasa/induk), konsisten dengan induk/varian lain. |
| D6–D10 | Low–Medium | Draf wholesale id 0 (tidak diimpor), NULL→default (kode cabang diturunkan dari nama), 2 tabel hanya-di-data-lama kosong, token FCM basi (kini dibersihkan otomatis termasuk `SENDER_ID_MISMATCH`), logo nota (pratinjau aman bila file hilang). | **Diselesaikan**; detail di [phase2-import-rehearsal.md](phase2-import-rehearsal.md). Skrip dump/muat: `scripts/legacy/`. |

**Koreksi plan:** `/register` sudah tertutup (403 / redirect), jadi butir Fase 4 untuk itu dicoret; captcha login tidak aktif (lihat temuan 2).

**Celah Fase 1 yang BELUM tertutup** (tidak dites perilakunya): sortir & produksi, order book & delivery order, export Excel/PDF, double-submit konkuren sungguhan (dua request bersamaan, bukan replay berurutan), batas hari/timezone di laporan, laporan profit & total aset (hanya dites aksesnya), pengadaan `temp`/autosave. Sisanya bisa lewat QA manual Fase 3.

## Fase 2 — Rehearsal impor data lama

Ini risiko terbesar. Lakukan minimal **2 kali** sampai bersih.

- [ ] Buat mapping tabel/kolom lama → schema baru (dokumen tertulis: kolom baru, kolom dihapus, perubahan tipe, default).
- [ ] Skrip impor idempotent (bisa diulang dari nol), dijalankan ke DB lokal kosong yang sudah `migrate`.
- [ ] **Rekonsiliasi** (lama vs baru, harus sama):
  - [ ] Jumlah baris: user, staff, customer, supplier, produk, cabang, transaksi.
  - [ ] Total stok per produk per cabang.
  - [ ] Total nominal transaksi per bulan per cabang.
  - [ ] Saldo/arus kas per akun.
- [ ] Integritas: tidak ada foreign key yatim (transaksi tanpa produk/cabang/customer), tidak ada duplikat username/nomor nota.
- [ ] Akun admin dari data lama bisa login dan role/menu-nya benar; password lama tetap valid (cek format hash).
- [ ] File upload lama (gambar produk, logo nota) ikut dipindah dan tampil.
- [ ] Catat durasi impor → untuk estimasi downtime cutover.

Gerbang: semua angka rekonsiliasi cocok, selisih dijelaskan tertulis.

### Hasil Fase 2 (2026-11-12) — detail: [phase2-import-rehearsal.md](phase2-import-rehearsal.md)

Latihan impor `infruity_clone` → database hasil `migrate:fresh`, dengan `php artisan legacy:import` / `legacy:reconcile` (perintah baru, dapat dipakai ulang untuk production): **177 pemeriksaan, 170 OK, 7 WARN bawaan data lama, 0 FAIL (HPP cocok persis)**; diulang dari nol hasilnya identik; rekonsiliasi terbukti mendeteksi 4 kerusakan sengaja (15 FAIL). Impor ±32.600 baris dalam ±1 detik. Aplikasi di atas data hasil impor: login akun lama, 15 halaman inti, simpan pengadaan baru, auto-increment lanjut di 68 tabel — semua ✅.

Checklist Fase 2 di atas: mapping tertulis ✅ (dokumen), skrip impor idempoten ✅, rekonsiliasi ✅, integritas ✅ (yatim terdata), akun admin dari data lama bisa login ✅ (hash identik), file unggahan ⚠ (hanya logo nota, belum disalin), durasi ✅.
**Belum:** menjalankan alur yang sama dengan database lama yang asli di server (skrip `scripts/legacy/dump-legacy.sh` + `load-legacy.sh` sudah teruji dari file dump di sini; `migrate:fresh` terukur 3 detik lokal).

## Fase 3 — QA manual (mobile-first)

Perangkat: minimal 1 Android murah (Chrome), 1 iPhone (Safari), lebar 360px & 390px; desktop sebagai sekunder.
Pakai data hasil impor Fase 2.

- [ ] **Login**: keyboard HP tidak menutupi tombol, captcha terbaca, pesan error jelas.
- [ ] **POS end-to-end per role kasir**: cari produk, tambah/edit item, customer, bayar tunai & non-tunai, nota tampil, cetak, **share WA** (nomor admin benar, teks nota benar).
- [ ] **Order book & delivery order**: buat order → assign kurir → selesai.
- [ ] **Stok**: penerimaan, transfer kirim/terima di dua HP berbeda, stok opname lengkap dengan diskusi.
- [ ] **Form pengadaan (dikencangkan di Fase 1 — wajib dicoba nyata di HP; daftar lengkap: [phase3-manual-pengadaan.md](phase3-manual-pengadaan.md)):** tambah produk dengan harga & qty desimal (mis. 2,5), harga berformat ribuan, diskon, harga jual cabang; simpan Draft; Posting; ketik lalu biarkan autosave jalan (baris belum lengkap tidak boleh ditolak); edit nota yang sudah diposting dalam batas stok; coba menurunkan qty di bawah yang sudah terjual (harus ditolak dengan pesan jelas); hapus nota yang sudah terpakai (harus ditolak).
- [ ] **Laporan** di HP: tabel bisa discroll, filter sheet berfungsi, angka sama dengan desktop.
- [ ] Koneksi lambat (throttle 3G): tombol bayar tidak bisa dobel klik, ada indikator loading.
- [ ] Push notification FCM diterima; Slack order webhook terkirim (pakai webhook staging).
- [ ] Sesi habis di tengah transaksi → diarahkan login tanpa data hilang diam-diam.

Gerbang: 0 bug Critical/High terbuka; Medium dicatat dan disetujui.

## Fase 4 — Keamanan & konfigurasi production

- [ ] `.env` production: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru, `APP_URL` https.
- [x] `/register` sudah tertutup (diverifikasi test Fase 1).
- [ ] **Login tanpa rate limit & captcha nonaktif** — putuskan pembatasan (lihat temuan Fase 1 #2).
- [ ] Log viewer (rap2hpoutre) dan debugbar tidak bisa diakses publik.
- [ ] `/infruity-ui`, `/produk`, `products-data` (route publik) — pastikan tidak membocorkan data.
- [ ] Nomor WA hardcoded (`6281249702030`, `6285138882070`) — pastikan benar nomor admin; idealnya pindah ke setting.
- [ ] Session/cookie `secure`, CSRF aktif di semua form POST, rate limit login.
- [ ] Upload: validasi tipe & ukuran file, folder upload tidak bisa eksekusi PHP.
- [ ] Kredensial FCM (`GOOGLE_APPLICATION_CREDENTIALS`) di luar `public/`, tidak ikut git.
- [ ] **Scheduler**: `app/Console/Kernel.php` menjadwalkan `tarik:absensi` & `insert:absensi`, tapi command-nya **tidak ada** di repo → cron akan error terus. Hapus jadwalnya atau tambahkan command-nya.
- [ ] Hapus file sampah di root: `your`, `segment(2)`.
- [ ] `composer audit` bersih; `npm run production` sukses, aset ter-commit/ter-build.

## Fase 5 — Staging di VPS (tanpa mengganggu app lama)

Sebelum mulai: **backup penuh** app lama (file + DB) dan catat konfigurasi web server/FPM yang berjalan.

- [ ] Pasang PHP 8.5-FPM berdampingan; **CLI default tetap 8.2**. Ekstensi: mbstring, xml, curl, zip, gd, bcmath, intl, pdo_mysql, fileinfo.
- [ ] Vhost **staging** (subdomain) → socket 8.5; DB, `.env`, `storage`, cron terpisah.
- [ ] `php8.5 composer install --no-dev -o`, `php8.5 artisan migrate`, impor data (skrip Fase 2), `config:cache route:cache view:cache`, `storage:link`.
- [ ] Permission `storage/` & `bootstrap/cache` benar untuk user FPM.
- [ ] Smoke test: login, 1 transaksi POS, 1 transfer, 1 laporan, export PDF/Excel, push notif.
- [ ] **Regression app lama**: buka & login app lama, 1 transaksi — harus tetap normal (PHP 8.2, DB tidak tersentuh).
- [ ] Cek RAM/CPU saat dua pool FPM jalan; set `pm.max_children` sesuai RAM.
- [ ] SSL untuk domain staging & domain final.

## Fase 6 — Go/No-Go & cutover

**Go jika semua benar:**
- [ ] Fase 1–5 lolos gerbangnya.
- [ ] Rekonsiliasi impor final cocok.
- [ ] App lama terbukti tidak terdampak.
- [ ] Rencana rollback sudah diuji minimal sekali.

**Cutover:**
1. Umumkan jam freeze ke pegawai (transaksi di app lama berhenti).
2. Backup final DB lama.
3. Jalankan impor final → rekonsiliasi cepat (jumlah baris + total stok + total transaksi hari terakhir).
4. Arahkan domain/vhost ke app baru, aktifkan cron app baru.
5. Smoke test oleh 1 admin + 1 kasir di HP.

**Rollback (batas waktu ditentukan, mis. 1 jam):**
- Kembalikan vhost/domain ke app lama; app lama dan DB-nya tidak pernah diubah, jadi langsung jalan.
- Transaksi yang sempat masuk di app baru dicatat untuk diinput ulang.

**Pemantauan 1–3 hari pertama:** `storage/logs`, error FCM/Slack, keluhan kasir, rekonsiliasi stok harian.

---

## Yang masih perlu diputuskan

- Domain final app baru (pakai domain app lama atau baru?).
- Jumlah user/cabang awal dan deadline → menentukan lama Fase 3.
- Cara membatasi percobaan login (throttle / captcha / fail2ban).
- Aturan bisnis POS: boleh bayar kurang (piutang)? boleh stok negatif?
