# Penyelesaian tindak lanjut audit clean code/KISS

**Status diperbarui:** 30 Oktober 2026. **Repo:** `infruity-main`, branch `feature/php-upgrade`. Ini status tindak lanjut atas audit baseline 28–29 Oktober; temuan dan angka baseline di `audit-2026-10-28.md` tetap menjadi catatan historis.

## Ringkasan

Tidak dilakukan rewrite atau penambahan layer generik. Perbaikan dibatasi ke invariant yang sebelumnya gagal: harga rupiah bulat, relasi varian, nominal deposito/EXP, kalkulasi HPP jus, stok produksi, finalisasi parcel, target route, dan compatibility PHP 8.5. Tidak ada migration maupun write ke database bisnis. Cleanup hanya menghapus dua file root tracked yang kosong (0 byte) setelah memastikan tidak ada pemanggil path; kandidat conditional tidak dihapus.

| Temuan | Hasil tindak lanjut | Regresi fokus |
|---|---|---:|
| A10 deposito | Nominal input harus sama dengan tier authoritative; parser menerima rupiah plain atau grouping titik UI; benefit tier dan periode disimpan atomik. Update mempertahankan snapshot benefit lama dan tidak dapat mengurangi nominalnya. | Termasuk suite CRM |
| A16 EXP | Rules numerik dipakai bersama pada update EXP dan jadwal; skala harus positif, value tidak negatif, nilai dan hasil perkalian harus finite. Kegagalan tidak meninggalkan update parsial. | Termasuk suite CRM |
| A11 stok produksi | Demand ingredient dijumlahkan per pool stok canonical parent/variant; row mapping varian dan row produk dikunci berurutan sebelum membaca view stock; retry/edit memperhitungkan posting sendiri secara terbatas. | 6 test fitur + 2 skenario MySQL concurrency |
| A12 parcel | Header parcel dikunci; status tak dikenal dan hasil complete yang tidak lengkap ditolak; retry lengkap mengembalikan ID output yang sama. | 3 test fitur |
| A13 HPP jus | Baris jus mengisi `subtotal_hpp` dari biaya unit × jumlah berdasarkan resep `Receipt`/`ProductReceipt` tersimpan, bukan bahan/kuantitas dari payload klien; HPP ingredient varian memakai parent stok canonical. Varian dengan lebih dari satu parent ditolak agar HPP tidak arbitrer. Sumber laporan desktop/mobile memuat seluruh biaya. Tidak ada backfill historis. | **18 test / 197 assertions** |
| A14 lifecycle varian | Endpoint AJAX membuat `is_variant` dan `product_child` dalam transaksi; existing product yang dipilih dapat ditautkan tanpa mengganti identitas/link parent lain. Create/update form menolak assignment cabang di luar `UserBranch::getUserBranch()` dan menyinkronkan harga hanya pada cabang yang diizinkan; update/delete dibatasi parent yang benar dan menjaga produk bersama. | **70 test / 510 assertions** bersama A15 |
| A15 harga | Endpoint harga menerima integer rupiah nonnegatif dalam batas kolom signed INT; decimal, eksponen, tanda, overflow, dan input non-scalar ditolak alih-alih dihapus karakternya. Harga base/branch dan scope cabang tetap diuji. | Termasuk suite produk |
| Q01 route | Empat deklarasi tanpa target/caller aktif dipensiunkan: `department/export`, `position/export`, `transfer/save-stock`, dan `role-menu.edit`. CRUD edit dan aksi transfer yang benar tetap tersedia. Handler POST registrasi kini public agar route dapat dijalankan, tetapi mengembalikan 403 tanpa membuat akun. | **3 test route** |
| Q03 PHP 8.5 | Cast `(Double)` dan interpolasi `${var}` yang deprecated diganti tanpa mengubah tujuan nilai. | 5 test compatibility + lint |
| Q04 build/CI | `composer.lock` dikeluarkan dari ignore dan disertakan dalam perubahan task; workflow mem-pin PHP 8.5.10, MySQL 8.4.11, Node 22.23.4. Suite regresi task menjadi gate blocking; full PHPUnit tetap dijalankan dan diarsipkan non-blocking sampai kegagalan baseline ditangani. | `actionlint` lulus; hosted Actions belum dijalankan |

## Bukti verifikasi

Semua test PHP di bawah menggunakan PHP 8.5.10 dan fixture SQLite `:memory:`, kecuali integration MySQL yang disebut tersendiri. Tidak ada koneksi ke database aplikasi.

| Pemeriksaan | Hasil aktual |
|---|---:|
| Full PHPUnit pada working tree gabungan (run sebelumnya) | **508 test / 3.345 assertions; 6 failure**. Keenamnya berada di `DeliveryOrderPaymentTest` (expected 422, got 200) pada kode lokal yang tidak semuanya masuk task commit; run itu mendahului dua regression test review terakhir dan bukan hasil task-only. |
| Baseline source HEAD `6d1002e1`, runner/config terkini | **287 test / 1.803 assertions; 26 failure + 1 error** pada PHP 8.5.10 dan PHPUnit 12.5.40, memakai lock dependency, config PHPUnit, dan environment POS default yang sama dengan postimage. |
| Task-only postimage: full PHPUnit, adapter POS default | **479 test / 3.031 assertions; 26 failure + 1 error**. Identitas failure/error sama persis dengan baseline; tidak ada regression test suite yang baru gagal. Suite tetap tidak hijau; rinciannya di bawah. |
| Task-only focused regressions: produk, CRM, HPP, inventory SQLite, route, PHP 8.5, PHPUnit config dan worker guard | **194 test / 1.240 assertions — lulus**, tanpa error/failure/skipped. |
| Produk A14/A15 | **70 test / 510 assertions — lulus** |
| CRM A10/A16 | **86 test / 421 assertions — lulus** |
| HPP A13 | **18 test / 197 assertions — lulus** pada task-only postimage |
| Route, PHP 8.5, PHPUnit config, worker guard | **11 test / 58 assertions — lulus** |
| Inventory SQLite | **9 test / 54 assertions — lulus** |
| Concurrency MySQL 8.4.11 | **3 test / 66 assertions — lulus** pada server/socket privat; tiap test hanya membuat dan menghapus schema inventory test yang dihasilkan harness |
| JavaScript | **55 test — lulus** pada task-only postimage; **102 test — lulus** pada working tree gabungan (mencakup test lokal tambahan) |
| PHP syntax + deprecation scan | **677 file PHP non-vendor** pada task-only postimage dan **704** pada working tree gabungan — tanpa syntax error/deprecation. Pemeriksaan sebelumnya mengompilasi 292 Blade; tidak ada Blade yang masuk diff task ini. |
| GitHub Actions lint | `actionlint .github/workflows/quality.yml` — exit 0 |
| Composer | `validate --strict`, locked `install`, `check-platform-reqs`, dan strict optimized autoload — exit 0 dengan PHP 8.5.10; lockfile tidak berubah |
| Node/Mix | `npm ci --no-fund` dengan Node 22.23.4 dan headless production build — exit 0; artefak JS/CSS/manifest terbentuk, dengan peringatan dependency legacy dan caniuse-lite usang |

Build menghasilkan bundle lama dengan `NODE_OPTIONS=--openssl-legacy-provider`; `scripts/quality-webpack.mix.js` hanya menonaktifkan notification native yang tidak dibutuhkan headless build. Lockfile tetap sama setelah install/build. Build ini bukan verifikasi visual browser atau bukti workflow sudah dijalankan oleh GitHub-hosted runner.

Batas bukti concurrency: skenario MySQL menguji produksi bersamaan pada produk sama dan pada parent/variant satu pool stok, mengunci/menyelaraskan pembacaan mapping `product_child`, serta dua finalisasi parcel bersamaan; hasilnya tidak membuktikan serialisasi bersama setiap jalur penulis stok lain. Fixture CRM SQLite membuktikan validasi dan rollback, bukan contention `lockForUpdate` MySQL untuk perubahan tier/schedule serentak. Worker inventory mengabaikan override path dari environment dan memuat controller hanya dari root repo-nya sendiri; regression menguji path tak tepercaya. Schema `infruity_test_inventory_*` telah terhapus; harness menghapus direktori worker yang dibuat oleh setiap invocation. Beberapa datadir/log dan hasil worker eksplorasi lama tetap berada di scratch eksternal, bukan di checkout atau commit.

**Perbandingan baseline dan batas integrasi checkout:** baseline adalah source HEAD `6d1002e1` pada worktree terpisah, dijalankan dengan dependency lock, config PHPUnit modern, environment POS default (variabel legacy adapter tidak disetel), PHP 8.5.10, PHPUnit 12.5.40, dan SQLite `:memory:` yang sama. Baseline menghasilkan **287 test / 1.803 assertions, 26 failure + 1 error**; task-only postimage final menghasilkan **479 test / 3.031 assertions, 26 failure + 1 error**. Perbandingan identitas JUnit menemukan **failure dan error sama persis**, tanpa identitas baru. Failure berada pada `ExpenditureAuthorizationTest`, `TransferConcurrencyTest`, `UserPermissionOverridesTest`, `UserStaffLinkTest`, dan empat kasus `WholesaleDetailAuthorizationTest`; error tetap pada `ChangePasswordTopbarBackButtonTest`. Empat respons 404 wholesale juga terjadi pada baseline HEAD dan akar semuanya belum diisolasi. Jadi kegagalan tersisa tidak diperkenalkan oleh diff ini, tetapi full suite task-only tetap **gagal**. Run sebelumnya pada working tree gabungan menghasilkan **508 test / 3.345 assertions dengan 6 failure**, semuanya di `DeliveryOrderPaymentTest` (expected 422 tetapi menerima 200); run itu mendahului dua regression test review terakhir dan bukan perbandingan task-only. Hosted Actions belum dijalankan; full suite dijalankan non-blocking sebagai baseline debt, sedangkan suite fokus menjadi gate blocking.

## Dependency security debt yang terlihat

Tidak ada versi dependency yang dinaikkan sebagai efek samping audit. Snapshot audit lokal 29 Oktober 2026 menunjukkan:

- Composer: satu advisory **medium** untuk `symfony/mime` versi lock `8.1.7` (CVE-2026-45070).
- npm: **143 advisories** pada dependency frontend terkunci (**13 critical, 56 high, 68 moderate, 6 low**), termasuk dependency langsung dan transitif dari stack Laravel Mix 5 lama. Beberapa perbaikan yang ditawarkan memerlukan major upgrade.

Karena itu workflow mengarsipkan hasil `composer audit` dan `npm audit` sebagai **report-only/non-blocking**. Test, lint, install, dan build tetap menjadi gate utama; laporan advisory tidak disembunyikan. Upgrade Mix/dependency dan evaluasi dampaknya perlu task tersendiri—jangan jalankan `npm audit fix` atau update massal saat refactor UI.

## Cleanup: penghapusan terbatas; kandidat conditional dipertahankan

- Root `segment(2)` dan `your`: keduanya tracked, file reguler 0 byte, dan tidak ditemukan consumer/path access pada source. Keduanya dihapus dalam cleanup ini; penghematan praktis nol.
- `Modules/Transaction/Http/Controllers/ProductionController_temp.php`: snapshot 602 baris yang mendeklarasikan nama class sama dengan controller aktif; tidak ditemukan consumer source dan kini dikecualikan dari optimized classmap. Tetap **conditional** sampai deployment/autoload eksternal dipastikan tidak merujuknya.
- `resources/views/template - Copy/`, tujuh view lama, aset `assets-landing`, `mask`, dan bundle/plugin duplikat tetap **conditional**. Dynamic view loading, URL publik langsung, atau consumer eksternal tidak dapat dibuktikan hanya lewat pencarian teks. Jangan hapus folder atau file modified milik user berdasarkan suffix/hash saja.

Tidak ada data bisnis/database aplikasi, migration, cache aplikasi, `vendor/`, `node_modules/`, atau aset kerja pengguna yang dibersihkan. Schema tes di MySQL privat dihapus oleh harness setelah integration test; datadir/log dan beberapa hasil worker eksplorasi lama di scratch eksternal dipertahankan dan tidak masuk repository. Pemeriksaan dilakukan terhadap source/fixture; access log, deployment override, URL publik eksternal, dan perilaku production browser tidak diperiksa. Silakan lihat tabel kandidat lengkap dan batas scan di `audit-2026-10-28.md`.
