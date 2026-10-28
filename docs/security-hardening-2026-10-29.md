# Hardening tahap pertama — 29 Oktober 2026

Tindak lanjut terbatas untuk temuan **A01, A02, A08** pada [audit baseline](audit-2026-10-28.md). Bukan penyelesaian seluruh audit. Scope: reset transaksi via web, penetapan role staf/akun yang mewarisinya, dan endpoint demo broadcast FCM.

## Perubahan perilaku

### A01: reset lintas modul dipensiunkan

- Route bernama `wholesale.reset` dan handler `WholesaleController::reset_transactions()` dihapus.
- Aksi reset dikeluarkan dari katalog permission; baris permission lama di database tidak diubah/dihapus, tetapi tidak lagi mempunyai route/handler yang dapat dipanggil.
- `POST /wholesale/reset` menghasilkan **405** karena path yang sama masih cocok dengan resource route untuk verb lain; tidak ada handler reset yang dijalankan. Ini bukan error yang perlu diperbaiki menjadi fitur reset baru.
- Tidak dibuat command pengganti: belum ada kebutuhan development reset yang disetujui. Tidak ada truncate, reset database, atau migration yang dijalankan.

### A02: role assignment bukan bagian implisit dari izin CRUD

Permission baru: **`staff.assign-roles` — Tetapkan Hak Akses Karyawan**. Ia melengkapi, bukan menggantikan, izin endpoint seperti `staff.store`, `staff.update`, `user.store`, dan `user.update`.

| Operasi | Aturan |
|---|---|
| Membuat staf dengan pending roles | Butuh izin store dan `staff.assign-roles` |
| Mengedit data staf, set role tidak berubah | Tidak membutuhkan izin assignment tambahan; nama/tanggal/jabatan/cabang tetap mengikuti kontrak form yang ada |
| Mengubah role staf/akun tertaut | Butuh `staff.assign-roles`; akun sendiri tidak boleh diubah |
| Memberikan atau menghapus role Administrator | Hanya aktor dengan role Administrator aktif yang benar-benar terverifikasi dari assignment; permission override penolakan tetap dihormati |
| Membuat akun yang mewarisi role staf | Hak assignment diperiksa sebelum commit; kegagalan mengembalikan 403 dan rollback akun/tautan |
| Memindahkan tautan staf yang mengubah role akun | Menggunakan guard yang sama; tidak menjadi jalan memutar untuk menaikkan role sendiri atau memberikan Administrator |
| Memindahkan tautan tanpa mengubah set role | Kontrak izin link yang lama tetap berlaku; tidak menambahkan kebutuhan assignment hanya karena urutan ID berbeda |

Guard bersama yang kecil berada pada `app/Support/RoleAssignmentGuard.php`; tidak dibuat repository/service generik. Staff update mengunci header staf, membandingkan pending roles dan role akun sebelum menulis, dan hanya melakukan sinkronisasi role ketika set-nya berubah. Identitas akun mengikuti `staff.user_id` bila tersedia, dengan fallback mirror legacy `users.staff_id`; pencocokan nama/email tidak digunakan. Perbedaan set role yang akan direkonsiliasi tetap dianggap perubahan hak akses, bukan edit data biasa.

**Dampak rollout yang disengaja:** akun nonadmin yang sebelumnya hanya mempunyai `staff.store`/`user.store` atau `staff.update`/`user.update` tidak otomatis memperoleh hak assignment baru. Administrator harus memberikan `staff.assign-roles` secara eksplisit kepada pengelola yang memang berwenang. Tidak ada role/override akun bisnis yang diedit atau di-seed secara otomatis pada pekerjaan ini. Self-assignment tetap dilarang meskipun permission baru diberikan; minta administrator lain untuk perubahan role akun sendiri.

Ini tidak menyatakan seluruh lifecycle permission aplikasi telah diaudit ulang: penyuntingan definisi role, override permission, dan temuan audit lain tetap memiliki scope review masing-masing.

### A08: demo broadcast FCM dihapus, registrasi token dipertahankan

- `GET /test-fcm` dan handler demo `FCMController::testNotification()` dihapus, termasuk dependency broadcast yang tidak lagi diperlukan controller.
- Endpoint `POST /api/save-fcm-token` tetap membutuhkan autentikasi dan menyimpan token perangkat seperti sebelumnya.
- Regression test memverifikasi penyimpanan token dalam SQLite fixture dan tidak ada request HTTP keluar. Tidak ada broadcast FCM yang dikirim.

## Bukti verifikasi

Baseline diambil ulang **sebelum** perubahan kode; tidak menggunakan asumsi bahwa semua kegagalan berasal dari pekerjaan lama.

| Pemeriksaan | Hasil |
|---|---|
| Baseline full PHPUnit | 116 test, 715 assertions; 4 error setup transfer + 1 failure role/account |
| Dua class regression baru | **22 test / 85 assertions lulus**, 0 failure/error; ada peringatan schema PHPUnit lama |
| Full PHPUnit setelah perubahan | **140 test / 806 assertions**, 4 error + 1 failure yang identitasnya sama persis dengan baseline; **tidak ada identitas failure/error baru**, exit 2 |
| Node suite | **84 lulus**, 0 gagal |
| Lint PHP file task | **11 file lulus**, tanpa syntax error/deprecation pada file yang diubah/ditambahkan |
| Runtime route inventory | Route reset/demo FCM tidak ditemukan; registrasi token tetap mengarah ke `FCMController::store` dengan auth |

Empat error tetap `TransferConcurrencyTest` berhenti pada schema `users` yang belum disediakan fixture. Failure tetap `UserStaffLinkTest::test_role_index_lists_all_roles_assigned_to_an_account`, karena respons tidak memuat email yang diharapkan test. Assertion tersebut **tidak dihapus/dilemahkan**. Fixture manager pada `UserStaffLinkTest` diberi permission assignment eksplisit untuk skenario sah yang memang menguji pembuatan/sinkronisasi role; test negatif baru menghapus grant tersebut untuk membuktikan penolakan dan rollback.

RED–GREEN direproduksi pada route reset, route FCM, eskalasi self melalui staf, perubahan role tanpa izin terpisah, pemberian Administrator oleh delegate, pembuatan staf tanpa izin assignment, dan relink akun sendiri yang mengadopsi role staf lain. Pengujian reset/broadcast lama berhenti pada assertion registrasi route sebelum request sehingga tidak mengeksekusi operasi berbahaya tersebut.

## Menjalankan ulang

Dari root repo, gunakan PHP 8.5 yang benar; contoh mesin audit:

```bash
export PATH="$(brew --prefix php)/bin:$PATH"
test ! -f bootstrap/cache/config.php || { printf '%s\n' 'Gunakan environment test terisolasi; config cache aktif.'; exit 1; }
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= \
  CACHE_DRIVER=array SESSION_DRIVER=array MAIL_DRIVER=array QUEUE_CONNECTION=sync \
  php vendor/bin/phpunit --do-not-cache-result \
  tests/Feature/RetiredDangerousEndpointsTest.php \
  tests/Feature/StaffRoleAuthorizationTest.php

node --test tests/js/*.test.cjs
```

Dua test penolakan provisioning akun juga berada di `tests/Feature/UserStaffLinkTest.php`. Full suite tetap wajib diperiksa dan hasilnya tidak boleh disebut hijau selama kegagalan baseline belum selesai.

## Deployment dan batas verifikasi

- Pastikan pipeline deployment membangun ulang cache route/config yang relevan; cache dari versi lama tidak boleh mempertahankan endpoint yang sudah dihapus. Cache server bersama tidak dibersihkan oleh pekerjaan ini.
- Review dan berikan permission assignment hanya kepada pengelola yang disetujui; tidak membutuhkan perubahan schema baru.
- Tidak mengubah field/layout UI atau kontrak form. Browser E2E dan concurrency MySQL belum diuji; regression endpoint memakai SQLite in-memory, bukan database bisnis.
- Pembayaran COD, scope cabang pada modul lain, integritas stok/parcel, dan cleanup folder conditional belum dikerjakan pada tahap ini. Lihat urutan pekerjaan di laporan audit.
