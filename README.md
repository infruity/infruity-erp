# Infruity

Infruity adalah aplikasi backend berbasis **Laravel 10** dengan arsitektur modular (menggunakan [nwidart/laravel-modules](https://nwidart.com/laravel-modules/)) untuk mengelola operasional bisnis retail/distribusi — mulai dari manajemen data master, transaksi, point of sale (POS), hingga pelaporan dan CRM.

## Daftar Isi

- [Fitur & Modul](#fitur--modul)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Build frontend](#build-frontend)
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
- Dokumentasi API berbasis OpenAPI.

## Teknologi yang Digunakan

- **Backend:** PHP 8.1+, Laravel 10
- **Modularisasi:** nwidart/laravel-modules
- **Database:** MySQL (via Eloquent ORM & DBAL)
- **Frontend:** Blade, Tailwind CSS 3.4 (Vite), Alpine.js, jQuery + DataTables. Sedang bermigrasi dari Bootstrap/Metronic ke Tailwind, lihat [docs/tailwind-migration-plan.md](docs/tailwind-migration-plan.md)
- **Lainnya:** Laravel Sanctum (API auth), Yajra Datatables, Google API Client, Guzzle, Spatie Laravel HTML

## Persyaratan Sistem

Pastikan environment pengembangan Anda memiliki:

- PHP >= 8.1
- Composer
- Node.js ^20.19 atau >= 22.12, dan NPM (dibutuhkan Vite untuk build frontend)
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
   npm install
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

7. **Jalankan migrasi database**

   ```bash
   php artisan migrate
   ```

   Jika tersedia seeder untuk data awal:

   ```bash
   php artisan db:seed
   ```

8. **Buat symbolic link untuk storage**

   ```bash
   php artisan storage:link
   ```

9. **Build asset frontend**

   ```bash
   npm ci
   npm run build
   ```

   Lihat [Build frontend](#build-frontend) untuk detail dan mode pengembangan.

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
GUEST_DB_DATABASE=infruity_guest_local

QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

Tombol **Guest** menggunakan halaman Laravel yang sama dengan login biasa, tetapi seluruh query aplikasi Guest memakai database MySQL terpisah. Nama `GUEST_DB_DATABASE` wajib berbeda dari `DB_DATABASE`. Jika server Guest memakai kredensial berbeda, atur juga `GUEST_DB_HOST`, `GUEST_DB_PORT`, `GUEST_DB_USERNAME`, dan `GUEST_DB_PASSWORD`.

Setelah database utama siap, jalankan `php artisan guest:prepare` satu kali. Perintah ini membuat struktur tabel dan view pada database Guest serta akun demo, tanpa menyalin data bisnis dari database utama. Gunakan `SESSION_DRIVER=file` atau penyimpanan sesi non-database agar sesi Guest tidak ditulis ke database utama.

Jika menggunakan fitur notifikasi push (Firebase/Web Push), siapkan juga kredensial VAPID dan Firebase Service Account sesuai konfigurasi yang digunakan pada `app/Services/FcmService.php`.

## Menjalankan Aplikasi

Jalankan server pengembangan bawaan Laravel:

```bash
php artisan serve
```

Aplikasi akan tersedia di `http://127.0.0.1:8000`.

Untuk pengembangan asset secara real-time, jalankan di terminal terpisah (lihat [Build frontend](#build-frontend)):

```bash
npm run dev          # CSS Tailwind dengan HMR
npm run dev:vendor   # rebuild bundle jQuery + SweetAlert2 bila resources/js/vendor.js berubah
```

## Build frontend

Frontend dibangun dengan Vite. Hasil build ada di `public/build` dan **tidak di-commit** (di-gitignore), jadi build harus dijalankan di setiap lingkungan, termasuk saat deploy.

```bash
npm ci
npm run build
```

`npm run build` menghasilkan dua hal:

| Output | Isi | Cara dimuat |
|---|---|---|
| `public/build/assets/*.css`, `alpine-*.js`, `manifest.json` | CSS Tailwind (`app.css` untuk layout baru, `legacy.css` untuk layout `template.root` tanpa preflight) dan Alpine.js | `@vite(...)` |
| `public/build/vendor/vendor.js` | jQuery + SweetAlert2 sebagai skrip klasik (IIFE) | `@include('partials.vendor-js')` |

jQuery dan SweetAlert2 sengaja bukan module, karena skrip inline di banyak halaman memakai `$` dan `Swal` saat HTML masih di-parse.

**Deploy:** server butuh Node ^20.19 atau >= 22.12. Tambahkan `npm ci && npm run build` ke prosedur deploy. Tanpa `public/build/manifest.json`, halaman yang memakai `@vite` akan error; tanpa `public/build/vendor/vendor.js`, konsol browser menampilkan pesan dan halaman yang butuh jQuery akan rusak.

**Pengembangan:** `npm run dev` menjalankan dev server Vite (HMR untuk CSS dan Alpine). File `vendor.js` tidak ikut HMR; jalankan `npm run dev:vendor` bila mengubahnya. Test PHP tidak butuh hasil build (`withoutVite()` di `tests/TestCase.php`).

**Tailwind:** konfigurasi di `tailwind.config.mjs` (token warna, radius, bayangan) dan `tailwind.legacy.config.mjs` (sementara, dihapus saat migrasi selesai). Class yang disusun lewat konkatenasi string tidak akan ter-compile; tulis nama class lengkap di sumber.

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
└── public/                # Asset publik hasil compile
```

Setiap modul mengikuti struktur standar `laravel-modules`, sehingga dapat dikembangkan, diaktifkan, atau dinonaktifkan secara independen.

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

Menjalankan test suite PHPUnit:

```bash
php artisan test
npm run test:js
```

## Kontribusi

Kontribusi sangat terbuka. Silakan ikuti langkah berikut:

1. Fork repository ini.
2. Buat branch baru untuk fitur/perbaikan Anda (`git checkout -b fitur/nama-fitur`).
3. Commit perubahan Anda dengan pesan yang jelas.
4. Push ke branch Anda dan buat Pull Request.

## Lisensi

Proyek ini bersifat privat/proprietary. Hubungi pemilik proyek untuk informasi lebih lanjut terkait lisensi dan penggunaan.

> Tes integrasi bot Telegram @InfruityyyBot

> Tes integrasi bot Telegram @InfruityyyBot
