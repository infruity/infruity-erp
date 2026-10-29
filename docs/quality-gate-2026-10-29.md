# Pemulihan quality gate — 29 Oktober 2026

Tindak lanjut kegagalan baseline dan bagian runner pada Q03 di [audit clean code](audit-2026-10-28.md). Baseline tahap ini diambil pada HEAD `3216c9c8`, termasuk working tree lokal yang belum seluruhnya di-commit. Perubahan tahap ini **hanya test, konfigurasi runner, dan dokumentasi**; tidak mengubah controller, model, permission, atau transaksi produksi.

## Baseline yang direproduksi

Full PHPUnit: **295 test / 1.785 assertions, 4 error + 1 failure, exit 2**, serta satu runner deprecation schema XML.

- Empat metode `TransferConcurrencyTest` berhenti di setup pada `User::first()`: tabel `users` tidak disediakan oleh `DatabaseTransactions`.
- `UserStaffLinkTest::test_role_index_lists_all_roles_assigned_to_an_account` meminta email akun dan badge multi-role pada `/roles`, padahal route itu memuat katalog role.
- XML tidak valid terhadap XSD PHPUnit terpasang: atribut `backupStaticAttributes` dan tiga `convert*ToExceptions`, serta elemen `filter/whitelist`, sudah tidak diterima schema saat ini.

Kegagalan tidak diselesaikan dengan skip, mematikan middleware, mengambil data developer, atau mengurangi assertion bisnis.

## 1. Fixture transfer yang mandiri

`tests/Feature/TransferConcurrencyTest.php` sekarang memakai koneksi SQLite `:memory:` khusus dan schema fixture eksplisit. Aktor disimpan dengan identitas legacy `nm_user/id_user`, assignment role/cabang nyata, serta produk dan ketersediaan stok fixture. Tidak ada lagi `User::first()` atau fallback memilih cabang developer; koneksi dipurge saat teardown.

Empat perilaku asli tetap diuji melalui route/controller/model/view aplikasi:

1. Dua aktor menyimpan preview invoice sama tetapi memperoleh header/invoice berbeda tanpa menimpa record.
2. Edit mempertahankan invoice dan mengubah transaksi berdasarkan ID.
3. Penomoran setelah suffix 999 menghasilkan 1000.
4. Draft temp dipakai ulang oleh aktor pemiliknya dan tidak mengubah draft aktor lain.

Assertion identitas draft, jumlah draft temp, serta kepemilikan/invoice aktor lain ditambah. Tidak diperlukan perubahan payload atau adapter fungsi SQL. **Hasil: 4 test / 30 assertions lulus**, dibanding baseline 4 error sebelum mencapai assertion.

Nama class dipertahankan untuk kompatibilitas command lama, tetapi PHPDoc menjelaskan bahwa ini **regression berurutan, bukan pengujian paralel**. Tabel fixture `product_stock` mewakili antarmuka baca view; penghitungan SQL view dan row-lock MySQL tidak dibuktikan oleh fixture ini.

## 2. Kontrak role dan akun dipisahkan dengan benar

Produksi sudah memisahkan dua halaman:

- `/user`: daftar akun dengan ringkasan role yang ditugaskan.
- `/roles`: katalog role beserta jumlah izin, bukan daftar akun.

Test lama diganti nama menjadi `test_user_index_lists_all_roles_assigned_to_an_account` dan diarahkan ke `/user`. Invariant tidak dibuang: view data wajib berisi akun ID 2, `first@example.test`, role IDs `[2,3]`, dan nama Operator/User Manager. HTML juga diperiksa **di baris akun itu sendiri**, bukan mencocokkan nama role dari option/dropdown lain.

Test katalog `/roles` ditambahkan terpisah: IDs/nama role, jumlah izin, urutan entri katalog, serta tidak adanya email akun. **Seluruh `UserStaffLinkTest`: 30 test / 204 assertions lulus**, dari baseline 29 test / 192 assertions dengan satu failure. Tidak ada perubahan produksi untuk memaksakan akun muncul kembali di katalog role.

## 3. Runner modern dan tidak menyembunyikan issue

Migrator PHPUnit **12.5.40 yang benar-benar terpasang** terlebih dahulu dijalankan pada salinan konfigurasi di scratch. Perubahan schema diterapkan ke `phpunit.xml` setelah hasilnya diperiksa:

- `backupStaticProperties` menggantikan atribut lama; source include `./app` tetap dipertahankan melalui elemen `source/include`.
- Atribut konversi exception lama dibuang karena sudah tidak valid, **bukan** untuk mengabaikan warning.
- `failOnAllIssues="true"` membuat issue runner/test menjadi exit gagal.
- `cacheResult="false"` menghindari penulisan result cache runner sebagai default; suite, bootstrap, dan environment SQLite/array tetap dipertahankan.

`tests/Unit/PhpunitConfigurationTest.php` memeriksa schema vendor terpasang, kebijakan issue/cache, dan default environment in-memory. **2 test / 12 assertions lulus**.

Probe Native PHPUnit terisolasi (tanpa aplikasi/database bisnis) dijalankan untuk memverifikasi efek kebijakan, bukan hanya memeriksa string XML:

| Probe satu test | Exit | Hasil |
|---|---:|---|
| Assertion bersih | 0 | Lulus |
| `E_USER_WARNING` | 1 | Warning menggagalkan gate |
| `E_USER_NOTICE` | 1 | Notice menggagalkan gate |
| `E_USER_DEPRECATED` | 1 | Deprecation menggagalkan gate |
| Test tanpa assertion | 1 | Risky menggagalkan gate |
| Exception tak tertangani | 2 | Error menggagalkan gate |

Probe berada di scratch dan sengaja tidak masuk discovery suite. Backup migrator juga dibuat di scratch, bukan menambah `phpunit.xml.bak` ke repo. Validasi DOM/XSD awal gagal dengan lima pelanggaran schema; konfigurasi final valid tanpa pelanggaran.

## 4. Hasil verifikasi akhir

| Pemeriksaan | Hasil |
|---|---|
| Baseline full PHPUnit | 295 test / 1.785 assertions; 4 error + 1 failure; exit 2 |
| Full PHPUnit setelah perbaikan | **298 test / 1.839 assertions; 0 error, 0 failure, 0 skipped; exit 0** |
| Tiga class terkait | **36 test / 246 assertions; exit 0** |
| Seluruh JavaScript | **95 test lulus; exit 0** |
| PHP lint tiga file test task | Lulus |
| Schema runner | Valid terhadap XSD vendor terpasang; runner deprecation lama hilang |

Full run memakai `--display-all-issues` dan berakhir `OK`, bukan `OK, but there were issues`. Hasil berlaku untuk **working tree audit**: paket idempotency POS dan pekerjaan lokal lain yang sebelumnya belum tracked tetap tidak dimasukkan ke commit tahap ini. Hasil ini bukan bukti checkout bersih memuat semua pekerjaan lokal.

Pada working tree dengan adapter POS lokal ber-request-key, ulangi dari root dengan PHP 8.5 di PATH:

```bash
test ! -f bootstrap/cache/config.php || { printf '%s\n' 'Gunakan environment test terpisah: config cache masih ada.'; exit 1; }
INFRUITY_TEST_LEGACY_POS_ADAPTER=0 APP_ENV=testing \
  DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= \
  CACHE_DRIVER=array SESSION_DRIVER=array MAIL_DRIVER=array QUEUE_CONNECTION=sync \
  php vendor/bin/phpunit --do-not-cache-result --display-all-issues

node --test tests/js/*.test.cjs
```

Mode test POS tracked/legacy vs lokal dijelaskan pada [laporan COD/POS](cod-payment-hardening-2026-10-29.md#isolasi-pekerjaan-lokal-dan-commit); flag tersebut tidak mengubah aplikasi produksi. Jangan menghapus config cache aplikasi bersama demi menjalankan test.

## Batas dan pekerjaan berikutnya

- Suite hijau bukan sertifikasi seluruh bisnis aman: browser E2E, SQL stock views, serta concurrency MySQL sungguhan belum diuji.
- Temuan lint/deprecation PHP 8.5 di source yang belum dieksekusi suite, stale routes, dependency lockfile, dan CI tetap pekerjaan tersendiri pada Q01/Q03/Q04. Tahap ini tidak mengklaim full-project lint atau coverage menyeluruh sudah bersih.
- Tidak menjalankan migration, reset, grant permission, atau transaksi terhadap database developer/bisnis; fixture dibuat/dimutasi hanya pada SQLite in-memory.
- Semua pekerjaan lokal di luar task dipertahankan; tidak ada folder/aset kandidat cleanup yang dihapus.
