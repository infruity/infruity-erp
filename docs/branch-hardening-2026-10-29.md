# Hardening cabang dan transaksi — 29 Oktober 2026

Tahap kedua setelah [hardening awal](security-hardening-2026-10-29.md), untuk temuan **A04, A05, A06, A07** pada [audit baseline](audit-2026-10-28.md). Fokusnya kontrol akses objek/cabang, state transaksi, serta pemeliharaan identitas dan relasi pembayaran saat edit. Ini bukan penutupan seluruh audit.

## 1. Pengadaan: otorisasi detail mengikuti header

Endpoint `PUT /wholesale/update-product/{id}` dan `DELETE /wholesale/delete-product/{id}`:

- Memeriksa permission asli `wholesale.update-product` / `wholesale.delete_product` secara ketat; tanpa izin atau override deny menghasilkan **403**.
- Mencari header melalui cabang dari `UserBranch::getUserBranch()` dan relasi detail; header/detail dikunci di dalam transaksi.
- Detail tidak ada/orphan, header tanpa cabang yang diizinkan, serta target cabang lain menghasilkan **404**. Status final target cabang lain tidak dibocorkan lewat response 409.
- Header `complete` menghasilkan **409**, termasuk untuk Administrator. Header `temp`, `draft`, dan `posting` berizin tetap dapat diedit/dihapus.
- Payload sukses lama tetap: update 200, delete 201. Exception authorization/not-found tidak lagi ditelan menjadi response sukses setelah rollback.

**Batas yang sengaja tidak diperluas:** response kegagalan DB nonotorisasi pada kedua handler masih mengikuti kontrak legacy (update 200 dengan pesan gagal; delete 404). Jangan menafsirkan ini sebagai desain error ideal atau seluruh lifecycle pengadaan sudah dibenahi. Penyeragaman error UI/API dan audit semua writer/finalizer/concurrency adalah pekerjaan terpisah.

## 2. Pengeluaran/pemasukan: identitas eksplisit dan edit in-place

### Kontrak simpan

`POST /expenditure/save-transaction` tetap memakai permission `expenditure.save-transaction` dan response sukses `success`, `message`, `transaksi_id`, tetapi sekarang membutuhkan identitas yang eksplisit:

| Field / keadaan | Arti |
|---|---|
| `expenditure_id: null` | Buat transaksi baru di cabang yang diizinkan |
| `expenditure_id: <integer>` | Edit record tersebut setelah lookup cabang dan lock header |
| Field `expenditure_id` tidak dikirim | **422**, bukan menebak apakah request adalah create atau edit |
| `invoice_number` | Label/preview, **bukan** kunci untuk menimpa record lain; nomor tersimpan dibuat server saat create |
| `branch_id` | Wajib berupa cabang yang dapat diakses aktor; cabang header yang sudah tersimpan tidak dapat dipindahkan lewat editor ini |

**Dampak rollout:** deploy perubahan server dan Blade script bersama, lalu muat ulang tab lama. Client lama yang belum mengirim `expenditure_id` akan ditolak 422, bukan diam-diam membuat transaksi duplikat ketika bermaksud mengedit. Create tetap mengirim key dengan nilai `null`. Preview yang sama dengan invoice transaksi lain tidak menghapus atau mengganti invoice itu.

### Identitas, pembayaran, dan state

- Edit mempertahankan ID, UUID, invoice, pembuat, dan waktu pembuatan header; tidak lagi delete/recreate header berdasarkan pencarian invoice global.
- Item divalidasi sebelum perubahan. Perubahan header/detail/pembayaran berada dalam satu transaksi; kegagalan write pembayaran me-rollback header dan detail.
- Untuk satu nota pembayaran, editor membawa nilai **paid kumulatif**, bukan instruksi menambah pembayaran baru. Baris pembayaran diperbarui in-place dengan ID/UUID dan foreign key header tetap; retry edit yang sama tidak menambah baris pembayaran.
- Jika sudah terdapat beberapa nota pembayaran, sejarah barisnya dipertahankan. Nilai paid neto dan metode header yang tidak berubah tetap dapat digunakan untuk edit detail; upaya menggantinya dengan satu nominal/metode baru menghasilkan **409**. Ini bukan UI settlement multi-nota baru.
- Draft/temp tetap dapat diedit. State pending/paid/debt mengikuti jendela edit 30 menit berdasarkan `updated_at` yang sudah digunakan daftar lama; canceled ditolak. Transaksi final kedaluwarsa menghasilkan **409**, dan paid/debt tidak boleh dikembalikan ke draft/temp/pending.
- Aturan yang sama diterapkan pada halaman edit dan aksi daftar, agar menu tidak menawarkan state yang ditolak backend.
- Halaman edit dan delete scoped cabang. Delete menolak paid/debt, header dengan paid nonnol, atau riwayat pembayaran bernilai nonnol. Draft unpaid dapat dihapus bersama detail serta placeholder pembayaran nol, tanpa meninggalkan baris orphan.
- Endpoint `GET /expenditure/data` juga memeriksa `expenditure.index` sebelum query dan tetap membatasi cabang.

Nominal, rounding, penomoran invoice, dan semantik akuntansi lain belum dinyatakan diaudit menyeluruh. Helper nomor invoice MySQL lama tetap dipakai. Tidak ada unique constraint/idempotency token baru untuk **create**, sehingga perlindungan retry edit tidak boleh diklaim sebagai idempotency seluruh transaksi baru atau pembuktian race-condition nomor invoice.

### Perubahan client yang diperlukan

Pada `Modules/Pos/Resources/views/expenditure/js/js-product.blade.php`:

- Kedua aksi simpan mengirim `expenditure_id`; hasil create yang sukses memperbarui identitas di state client.
- Satu helper submit menangani draft/final, status HTTP, `success` aplikasi, pesan error, dan pelepasan loading state.
- 403/409/422/500, response aplikasi gagal, atau koneksi gagal tidak memicu redirect sukses dan tidak mengosongkan cart.
- Prefill cart menggunakan `product_id`, bukan primary key baris detail sebagai identitas produk.
- Tidak mengubah desain atau susunan field halaman. Browser E2E belum dijalankan; perilaku script diuji dengan Node VM/stub DOM/fetch, dan halaman edit positif juga dirender lewat Laravel HTTP test.

## 3. Laporan barang buang mobile

Query loss pada `MobileReportController` sekarang memakai `scopeBranch()` yang sudah digunakan jenis laporan lain. Payload dates/history tidak diubah:

- `all` atau filter kosong hanya mengembalikan cabang yang ditugaskan kepada pengguna.
- Cabang eksplisit yang tidak diizinkan dan pilihan pusat/null oleh nonadmin menghasilkan **403**.
- Pengguna tanpa assignment tidak memperoleh kerugian cabang lain.
- Administrator tetap dapat memilih seluruh cabang atau pusat/null sesuai kebijakan helper existing.

Ini perbaikan scope, bukan rekonsiliasi ulang definisi kerugian atau nilai historis laporan.

## 4. Permission endpoint data

Pemeriksaan ditempatkan sebelum query listing, bukan hanya pada halaman yang memanggil endpoint:

| Endpoint | Permission |
|---|---|
| `GET /user/data` | `user.index` |
| `GET /tier/data` | `tier.index` |
| `GET /customer-report/data` | `customer-report.index` |

Explicit allow/deny tetap melalui `RoleMenu` yang ada, termasuk deny untuk Administrator. Test penolakan sengaja tidak menyediakan tabel listing dan memantau query agar terbukti 403 terjadi sebelum akses tabel data; positive tests menyediakan record fixture nyata. Tidak dibuat sistem permission atau schema baru.

## 5. Hasil verifikasi gabungan

Baseline diambil sebelum perubahan tahap ini pada HEAD `fa04baa7`, working tree yang masih mempunyai perubahan lokal pengguna.

| Pemeriksaan | Hasil |
|---|---|
| Baseline full PHPUnit | 140 test / 806 assertions; 4 error setup transfer + 1 failure role/account |
| Empat class regression baru | **93 test / 421 assertions**, 0 error/failure |
| Rincian focused | Pengadaan 33/172; pengeluaran 26/88; mobile loss 16/79; endpoint data 18/82 |
| Full PHPUnit sesudah perubahan | **233 test / 1.227 assertions**, 4 error + 1 failure dengan identitas sama persis seperti baseline; exit 2 |
| JavaScript seluruh suite | **90 test lulus**, 0 gagal; mencakup 6 test baru pengeluaran |
| PHP lint | **9 file PHP** produksi/test task lulus tanpa syntax error/deprecation |
| Blade script berubah | Dikompilasi ke scratch dan hasil PHP di-lint: lulus; JavaScript dieksekusi melalui Node harness |

Empat error tetap pada setup `TransferConcurrencyTest` karena schema `users` belum disediakan. Failure tetap `UserStaffLinkTest::test_role_index_lists_all_roles_assigned_to_an_account` karena respons tidak mengandung email yang diharapkan. Assertion lama tidak dihapus atau dilemahkan. Peringatan XML schema PHPUnit legacy juga masih ada. Full suite **belum hijau**, walaupun tahap ini tidak menambah identitas failure/error baru.

Contoh pengulangan setelah memilih interpreter PHP 8.5 dan memastikan config cache tidak memaksa koneksi aplikasi:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= \
  CACHE_DRIVER=array SESSION_DRIVER=array MAIL_DRIVER=array QUEUE_CONNECTION=sync \
  php vendor/bin/phpunit --do-not-cache-result \
  tests/Feature/WholesaleDetailAuthorizationTest.php \
  tests/Feature/ExpenditureAuthorizationTest.php \
  tests/Feature/MobileLossBranchAuthorizationTest.php \
  tests/Feature/DataEndpointAuthorizationTest.php

node --test tests/js/*.test.cjs
```

Semua mutasi pengujian dilakukan pada SQLite in-memory fixture, bukan database bisnis. Fixture pengeluaran mengadaptasi fungsi scalar `RIGHT/MONTH/YEAR/NOW` untuk menjalankan helper invoice lama; ini tidak menggantikan uji MySQL. Trigger SQLite sengaja digunakan untuk membuktikan rollback, bukan sebagai migrasi produksi. Row locking dan interaksi seluruh writer/finalizer MySQL tetap perlu pengujian concurrency khusus.

## 6. Yang belum dikerjakan

Pembayaran COD/ledger bersama, integritas kebutuhan stok dan finalisasi parcel, escaping varian, perhitungan HPP/deposito/EXP, quality-gate baseline, serta deletion folder/aset conditional tetap menjadi pekerjaan berikutnya. Tahap ini tidak melakukan migration, backfill, grant permission massal, reset, atau pembersihan data bisnis. Perubahan lokal di luar task tetap harus dibiarkan unstaged.
