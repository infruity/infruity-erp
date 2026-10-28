# Infruity

Infruity adalah aplikasi backend berbasis **Laravel 13 / PHP 8.5** dengan arsitektur modular (menggunakan [nwidart/laravel-modules](https://nwidart.com/laravel-modules/)) untuk mengelola operasional bisnis retail/distribusi — mulai dari manajemen data master, transaksi, point of sale (POS), hingga pelaporan dan CRM.

## Dokumentasi engineering

- [Panduan pengembangan, arsitektur, testing, dan clean code](docs/engineering.md).
- [Audit clean code/KISS dan kandidat cleanup, 28 Oktober 2026](docs/audit-2026-10-28.md).
- [Riwayat upgrade PHP/Laravel](docs/php-upgrade-plan.md) — catatan historis, bukan status test terkini.
- Acuan UI tambahan: `docs/ui-design-system.md` — tersedia lokal saat audit, belum tracked; panduan dasar UI juga dicatat pada panduan engineering.

Audit mencakup working tree pada branch `feature/php-upgrade`. Versi runtime yang diperiksa: Laravel 13.38.0, PHP 8.5.10; lihat laporan audit untuk batas pemeriksaan dan kegagalan test yang masih terbuka.

## Daftar Isi

- [Fitur & Modul](#fitur--modul)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Struktur Proyek](#struktur-proyek)
- [Perintah Artisan Berguna](#perintah-artisan-berguna)
- [Testing](#testing)
- [Kontribusi](#kontribusi)
- [Lisensi](#lisensi)

## Fitur & Modul

Aplikasi ini dibangun secara modular, dengan setiap domain bisnis dipisahkan ke dalam modulnya sendiri di direktori `Modules/`:

| Modul | Deskripsi |
|---|---|
| **Master** | Pengelolaan data master: akun, cabang (branch), pelanggan, kurir, metode pembayaran, posisi/jabatan, kategori produk, satuan produk, staff, dan supplier. |
| **Transaction** | Transaksi bisnis seperti penerimaan produk (product receipt) dan transaksi POS. |
| **Pos** | Fitur Point of Sale (penjualan di kasir). |
| **Crm** | Customer Relationship Management, termasuk dashboard CRM. |
| **Report** | Modul pelaporan dan rekap data bisnis. |
| **Chat** | Fitur komunikasi/chat internal aplikasi. |

Fitur pendukung lainnya meliputi:

- Autentikasi & otorisasi berbasis role/permission.
- Notifikasi push (Firebase Cloud Messaging / Web Push).
- Ekspor data ke Excel (Maatwebsite Excel) dan PDF (DomPDF).
- Barcode & QR Code generator.
- Captcha untuk keamanan form.
- Log viewer bawaan untuk memudahkan debugging.
- Berkas routing OpenAPI tersedia, tetapi `routes/openapi.php` masih kosong; spesifikasi API belum tersedia.

## Teknologi yang Digunakan

- **Backend:** PHP ^8.5, Laravel ^13.0 (sesuai `composer.json`)
- **Modularisasi:** nwidart/laravel-modules
- **Database:** MySQL (via Eloquent ORM/query builder; migrasi dan reporting mengandung SQL khusus MySQL)
- **Frontend:** Blade, Alpine.js, stylesheet ERP, serta plugin legacy Bootstrap/jQuery; dependensi Livewire ^4.0 dan Laravel Mix 5 masih tercantum. Build Mix tidak mewakili seluruh aset runtime.
- **Lainnya:** Laravel Sanctum (API auth), Yajra Datatables, Google API Client, Guzzle, Spatie Laravel HTML

## Persyaratan Sistem

Pastikan environment pengembangan Anda memiliki:

- PHP ^8.5; pastikan `php --version` memenuhi constraint sebelum menjalankan Composer/Artisan
- Composer
- Node.js & NPM
- MySQL (atau database kompatibel lainnya)
- Ekstensi PHP yang umum dibutuhkan Laravel (mbstring, openssl, pdo, tokenizer, xml, ctype, json, bcmath, fileinfo, gd)

## Instalasi

1. **Clone repository**

   ```bash
   git clone <url-repository-anda>
   cd infruity-main
   ```

2. **Install dependency PHP**

   ```bash
   composer install
   ```

3. **Install dependency JavaScript**

   ```bash
   npm ci
   ```

4. **Salin file environment**

   ```bash
   cp .env.example .env
   ```

5. **Generate application key**

   ```bash
   php artisan key:generate
   ```

6. **Konfigurasi database** pada file `.env` (lihat [Konfigurasi Environment](#konfigurasi-environment)).

7. **Siapkan database pengembangan terisolasi**

   Gunakan salinan schema/data yang disetujui pemilik database. Jangan mengasumsikan migrasi legacy dapat membangun instalasi kosong: jalur fresh install belum terverifikasi. Tinjau migration yang pending dan backup sebelum menjalankannya; jangan arahkan langkah berikut ke database produksi atau database bersama.

   ```bash
   php artisan migrate
   ```

   Seeder bersifat opsional dan perlu ditinjau terlebih dahulu; bukan prasyarat setup yang telah terverifikasi:

   ```bash
   php artisan db:seed
   ```

8. **Buat symbolic link untuk storage**

   ```bash
   php artisan storage:link
   ```

9. **Compile asset frontend legacy (opsional, bila mengubah input Mix)**

   UI ERP juga memuat aset langsung dari `public/assets/`. Jangan menganggap build berikut memperbarui seluruh UI. Jalur build legacy belum dijalankan pada audit ini; lihat [panduan engineering](docs/engineering.md).

   ```bash
   npm run dev
   # atau untuk production
   npm run production
   ```

## Konfigurasi Environment

Beberapa variabel penting yang perlu disesuaikan di file `.env`:

```env
APP_NAME=Infruity
APP_ENV=local
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=infruity
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

Jika menggunakan fitur notifikasi push (Firebase/Web Push), siapkan juga kredensial VAPID dan Firebase Service Account sesuai konfigurasi yang digunakan pada `app/Services/FcmService.php`.

## Menjalankan Aplikasi

Jalankan server pengembangan bawaan Laravel:

```bash
php artisan serve
```

Aplikasi akan tersedia di `http://127.0.0.1:8000`.

Untuk pengembangan asset secara real-time, jalankan di terminal terpisah:

```bash
npm run watch
```

## Struktur Proyek

```
infruity-main/
├── app/                   # Core application (controller, service, request, dsb.)
├── Modules/               # Modul-modul bisnis (Master, Transaction, Pos, Crm, Report, Chat)
│   └── <NamaModul>/
│       ├── Config/
│       ├── Http/Controllers/
│       ├── Resources/views/
│       └── ...
├── database/
│   └── migrations/        # Migrasi database
├── resources/views/       # View global (layout, admin, dll.)
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── admin.php
│   └── channels.php
└── public/                # Asset ERP langsung, vendor legacy, dan hasil compile Mix
```

Modul mengikuti struktur `laravel-modules` dan status aktivasinya diatur melalui `modules_statuses.json`. Beberapa alur saling bergantung (misalnya POS, stok, dan CRM); jangan menganggap menonaktifkan satu modul selalu aman tanpa meninjau dependensinya.

## Perintah Artisan Berguna

```bash
# Melihat daftar seluruh modul beserta statusnya
php artisan module:list

# Membuat modul baru
php artisan module:make <NamaModul>

# Menjalankan migrasi khusus modul tertentu
php artisan module:migrate <NamaModul>

# Membersihkan cache aplikasi
php artisan optimize:clear
```

## Testing

Jalankan test hanya dengan koneksi terisolasi. Pastikan `bootstrap/cache/config.php` tidak ada atau gunakan cache path khusus test; konfigurasi yang di-cache dapat mengalahkan environment test.

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= \
  CACHE_DRIVER=array SESSION_DRIVER=array MAIL_DRIVER=array QUEUE_CONNECTION=sync \
  php vendor/bin/phpunit --do-not-cache-result

# JavaScript: memakai Node test runner, tanpa instalasi npm untuk suite saat ini
node --test tests/js/*.test.cjs
```

Baseline audit 28 Oktober 2026: PHPUnit 116 test / 715 assertions, 4 error setup schema transfer dan 1 failure role/account; JavaScript 84 test lulus. Ini bukan suite hijau. SQLite tidak membuktikan perilaku lock/concurrency MySQL. Detail serta urutan perbaikannya ada pada [laporan audit](docs/audit-2026-10-28.md).

## Kontribusi

Kontribusi sangat terbuka. Silakan ikuti langkah berikut:

1. Fork repository ini.
2. Buat branch baru untuk fitur/perbaikan Anda (`git checkout -b fitur/nama-fitur`).
3. Commit perubahan Anda dengan pesan yang jelas.
4. Push ke branch Anda dan buat Pull Request.

## Lisensi

Proyek ini bersifat privat/proprietary. Hubungi pemilik proyek untuk informasi lebih lanjut terkait lisensi dan penggunaan.
