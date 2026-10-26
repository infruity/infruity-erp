# Rencana Upgrade: PHP 8.5 + Laravel 13

Branch: `feature/php-upgrade` (dibuat dari `backend`).

## Progres

| Langkah | Status | Commit |
|---|---|---|
| Bersihkan deprecation `(double)` cast | Selesai | `aa20eb3e` |
| Laravel 10 → 11 | Selesai | `b4a0ce24` |
| Laravel 11 → 12 | Selesai | `5fa5d4dc` |
| Laravel 12 → 13 + PHP 8.5 + Excel 4 | Selesai | `2520735d` |
| Audit sisa paket (livewire, guzzle, phpunit, dbal) | Selesai | commit berikutnya |
| Regression test role/cabang | Tertahan: harness test belum punya database | — |

Hasil verifikasi terakhir (PHP 8.5.10): Laravel 13.38.0, **0 deprecation**, 481 route,
login 200, dashboard/produk/sortir/stok opname/pindah stok render normal,
test suite 36 lulus / 4 gagal (gagal karena setup SQLite tidak punya tabel `users`,
bukan akibat upgrade).

Setelah audit paket: `composer outdated --direct` kosong (semua dependency langsung
mutakhir), `composer audit` tanpa advisory, 485 route, 12 halaman utama render normal.

### Sisa pekerjaan: harness test

4 test di `tests/Feature/TransferConcurrencyTest.php` gagal karena `phpunit.xml`
memakai SQLite `:memory:` tanpa menjalankan migration, sementara migration proyek ini
MySQL-spesifik (gagal di SQLite pada `create_role_menu_table`). Ini kondisi yang sudah
ada sebelum upgrade.

Langkah penyelesaiannya (butuh keputusan pemilik DB):

1. Siapkan database MySQL khusus test, mis. `infruity_test`.
2. Arahkan `phpunit.xml` ke koneksi tersebut dan tambahkan `RefreshDatabase`
   pada test (bukan `DatabaseTransactions` saja).
3. Jalankan migration di database test; bereskan migration MySQL-spesifik yang masih
   belum bisa jalan dari nol (lihat juga temuan "migration ledger tidak sesuai schema").

Catatan lingkungan: `php` default masih 8.3, jadi aplikasi **wajib** dijalankan dengan
`/opt/homebrew/opt/php@8.5/bin/php` sampai symlink Homebrew diarahkan ke 8.5.

## Kondisi awal (sebelum upgrade)

| Komponen | Versi terpasang | Keterangan |
|---|---|---|
| PHP (default `php`) | 8.3.33 | `php artisan serve` memakai ini |
| PHP 8.5 (Homebrew) | 8.5.10 | `/opt/homebrew/opt/php@8.5/bin/php` |
| Laravel | 10.50.3 | EOL; belum mendukung PHP 8.5 secara resmi |
| Composer | 2.10.3 | |

Aplikasi **masih bisa boot** di PHP 8.5, tetapi mengeluarkan deprecation.

## Blocker utama

1. **`phpoffice/phpspreadsheet` 1.30.7 membatasi PHP `< 8.5`** — ini yang menghalangi PHP 8.5 secara resmi.
   - `maatwebsite/excel` 3.1.70 mengunci `phpspreadsheet ^1.30.5`.
   - Jalur keluar: `maatwebsite/excel` 4.0.6 → butuh `phpspreadsheet ^5.9` **dan** `illuminate/support ^12.0 || ^13.0`.
   - Artinya upgrade Excel tidak bisa dilepas dari upgrade Laravel.
2. **Laravel 10 tidak mendukung PHP 8.5.** Target yang masuk akal: Laravel 13.
3. **Deprecation PHP 8.5 di kode aplikasi** (sebagian sudah diperbaiki):
   - `(double)` / `(real)` cast → sudah diganti `(float)` (commit `aa20eb3e`).
   - `PDO::MYSQL_ATTR_SSL_CA` dan `PDO::MYSQL_ATTR_INIT_COMMAND` di `config/database.php:62-63` → diganti `Pdo\Mysql::ATTR_*` (hanya ada sejak PHP 8.4, jadi baru aman setelah constraint PHP dinaikkan).

## Matriks dependency tujuan

| Paket | Sekarang | Target | Catatan |
|---|---|---|---|
| php | ^8.1 | ^8.5 | |
| laravel/framework | ^10.0 | ^13.0 | lompat 3 major |
| maatwebsite/excel | ^3.1 | ^4.0 | wajib untuk phpspreadsheet 5 |
| phpoffice/phpspreadsheet | 1.30.x | ^5.9 | syarat PHP 8.5 |
| nwidart/laravel-modules | 10.0 | ^13.0 | dipakai hampir seluruh modul |
| laravel/sanctum | ^3.2 | ^4.0 | |
| livewire/livewire | ^3.6 | ^4.0 | perlu audit komponen |
| yajra/laravel-datatables-oracle | ^10.0 | ^13.0 | API berubah |
| nunomaduro/collision | ^7.0 | ^8.x | dev |
| spatie/laravel-ignition | ^2.0 | ^3.x | dev |
| barryvdh/laravel-debugbar | ^3.15 | ^4.x | dev |
| doctrine/dbal | ^3.0 | ^4.x | bila masih dipakai |

## Urutan pengerjaan yang disarankan

Setiap langkah = satu commit, dan aplikasi harus tetap boot sebelum lanjut.

1. **Bersihkan deprecation yang aman** (sudah: cast `(double)`).
2. **Laravel 10 → 11** di branch ini:
   - `bootstrap/app.php` gaya baru, `app/Http/Kernel.php` → middleware di `bootstrap/app.php`.
   - Update `composer.json` + `composer update laravel/framework`.
   - Sesuaikan `nwidart/laravel-modules` ke versi 11-compatible.
3. **Laravel 11 → 12** (perubahan kecil, fokus deprecation & config).
4. **Laravel 12 → 13**.
5. **Excel 3 → 4 + phpspreadsheet 5.9**, lalu naikkan constraint `php` ke `^8.5`.
6. **Ganti konstanta PDO** ke `Pdo\Mysql::ATTR_*`.
7. **Audit paket lain**: livewire 4, datatables 13, sanctum 4, debugbar 4.
8. **Uji**: `php artisan about`, `route:list`, `view:cache`, test suite, lalu smoke test halaman utama (dashboard, produk, sortir, transfer, stok opname, POS, laporan).
9. Setelah stabil: jalankan `php artisan serve` dengan PHP 8.5 dan update dokumentasi/CI.

## Risiko

- **Laravel 10 → 13** menyentuh bootstrap, middleware, container binding, dan banyak API internal. Ini perubahan besar, bukan sekadar ganti versi PHP.
- **Livewire 3 → 4** dan **DataTables 10 → 13** berpotensi mengubah perilaku banyak halaman.
- **`nwidart/laravel-modules`** menentukan struktur `Modules/*`; harus ikut naik agar autoload modul tetap jalan.
- Migrasi ini **tidak boleh** dicampur dengan pekerjaan fitur lain supaya mudah di-rollback.

## Catatan lingkungan

- PHP 8.5 sudah terpasang lewat Homebrew, tetapi `php` default masih 8.3.
  Untuk menguji: `/opt/homebrew/opt/php@8.5/bin/php artisan --version`.
- Jangan jalankan `composer update` untuk seluruh proyek sekaligus; lakukan per-langkah agar penyebab kegagalan mudah dilacak.
