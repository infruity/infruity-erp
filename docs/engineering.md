# Panduan engineering Infruity

Dokumen kerja untuk pengembangan dan review. Kondisi runtime diperiksa **28 Oktober 2026**, branch `feature/php-upgrade`; [laporan audit](audit-2026-10-28.md) menyimpan baseline, bukti, dan pekerjaan yang belum selesai. Dokumen ini tidak menyatakan aplikasi sudah production-ready.

## 1. Runtime dan setup aman

Sumber kebenaran dependency adalah `composer.json`, `package.json`, dan dependency yang benar-benar terpasang—bukan catatan upgrade historis.

| Komponen | Constraint repo / hasil inspeksi |
|---|---|
| PHP | `^8.5`; CLI audit 8.5.10 |
| Laravel | `^13.0`; aplikasi boot sebagai 13.38.0 |
| Modularisasi | `nwidart/laravel-modules ^13.0` |
| Database aplikasi | MySQL; reporting/migrasi memuat SQL khusus MySQL |
| Test ringan | PHPUnit 12.5, SQLite `:memory:`, Node built-in test runner |
| Frontend | Blade + Alpine.js, aset ERP langsung, plugin legacy jQuery/Bootstrap |
| Build legacy | Laravel Mix 5 melalui `webpack.mix.js` |

Pada mesin audit, `php` default masih 8.3.33. Pilih interpreter secara eksplisit, tanpa mengubah symlink sistem:

```bash
# macOS Homebrew pada mesin audit
export PATH="$(brew --prefix php)/bin:$PATH"
php --version
php artisan --version
composer validate --no-check-publish
```

Perintah di luar mesin ini cukup menggunakan PHP 8.5 yang benar di PATH. Jangan menggunakan `--ignore-platform-reqs` untuk menutupi interpreter yang salah.

### Onboarding database

1. Jangan menimpa `.env` yang sudah ada; pada checkout baru saja, salin `.env.example` dan isi konfigurasi lokal.
2. Gunakan database dev milik sendiri/salinan yang telah disetujui. Pisahkan database dev, test MySQL, dan Guest.
3. Review migration pending serta backup sebelum `php artisan migrate`. Jalur migrasi dari database kosong **belum terverifikasi**; jangan menjanjikan fresh install berhasil hanya karena Artisan dapat boot.
4. Jangan menjalankan `migrate:fresh`, `db:wipe`, seed, restore dump, atau `guest:prepare` ke database bisnis tanpa izin eksplisit. `guest:prepare` adalah operasi penulisan, bukan health check.
5. Jangan menyimpan dump bisnis, kredensial Google/Firebase, token, atau `.env` dalam Git maupun `public/`.

`composer.lock` ada secara lokal tetapi diabaikan Git pada baseline audit. Karena itu `composer install` pada checkout bersih belum menjamin versi identik dengan mesin audit. Perbaikan lockfile harus berupa pekerjaan tersendiri: review isi, runtime, audit dependency, lalu track lockfile yang disetujui; jangan menjalankan `composer update` massal sebagai bagian refactor UI.

## 2. Peta arsitektur aktual

```text
HTTP request
  -> route provider / Modules/<Domain>/Routes/web.php
  -> web middleware (session, Guest connection, role session, CSRF)
  -> auth + pemeriksaan permission dan scope cabang di endpoint
  -> controller
  -> model/query, atau service/action untuk operasi bisnis multi-langkah
  -> Blade / JSON / export
```

Ini peta tanggung jawab yang harus diperiksa, **bukan jaminan semua endpoint lama sudah menerapkannya**. Audit menemukan gap authorization di beberapa jalur; jangan menganggap middleware `auth` sama dengan izin akses objek.

| Area | Lokasi / tanggung jawab |
|---|---|
| Bootstrap | `bootstrap/app.php` masih mengikat kernel klasik; `app/Http/Kernel.php` tetap aktif |
| Routing utama | `app/Providers/RouteServiceProvider.php`, `routes/web.php`, `routes/admin.php`, `routes/api.php` |
| Domain bisnis | `Modules/{Master,Transaction,Pos,Crm,Report,Chat}` |
| Controller modul | `Modules/<Domain>/Http/Controllers/` |
| Model modul | `Modules/<Domain>/Entities/` |
| Auth provider | `config/auth.php` menunjuk `App\User`; `App\Models\User` juga digunakan bagian lain/test |
| Permission | `app/Models/RoleMenu.php`, role terpilih dari assignment valid + override akun `user_permissions` |
| View global | `resources/views/`, komponen bersama `resources/views/components/erp/` |
| View modul | `Modules/<Domain>/Resources/views/`, namespace seperti `master::...` |
| UI referensi | repo saudara `infruity-frontend`; bukan backend alternatif |
| Asset runtime ERP | `public/assets/css/`, `public/assets/js/`, font/plugin lokal |
| Legacy build | `resources/js/app.js` + `resources/sass/app.scss` -> `public/js/app.js` + `public/css/app.css` |

### Invariant yang harus dipertahankan

- Identitas akun memakai `id_user` pada tabel legacy; jangan mengasumsikan semua model memakai `id`.
- Link akun–staff memakai ID eksplisit: `staff.user_id` sebagai relasi kanonis, dengan `users.staff_id` sebagai mirror kompatibilitas. Jangan menyimpulkan link dari nama/email.
- Override permission akun dapat mengizinkan/menolak akses di atas fallback role. Backend endpoint tetap perlu melakukan pemeriksaan; menyembunyikan tombol bukan kontrol akses.
- Scope cabang harus diterapkan saat lookup objek/item, bukan hanya saat menampilkan daftar cabang.
- Simpan ledger, detail, stock, dan status yang saling bergantung secara atomik. Hitung nilai otoritatif di server dan uji retry/idempotency pada operasi keuangan.
- SQLite fixture berguna untuk perilaku endpoint, tetapi tidak membuktikan `lockForUpdate`, isolation, atau race condition MySQL.

## 3. Clean code dan KISS: aturan praktis

Tujuannya mengurangi alasan perubahan dan risiko, bukan menambah layer sebanyak mungkin.

1. **Satu alur bisnis per perubahan.** Mulai dari route aktif dan payload sebenarnya; tulis regression test sebelum memindahkan logika.
2. **Controller mengorkestrasi.** Validasi, authorize, panggil operasi bisnis, return response. CRUD sederhana boleh tetap menggunakan Eloquent langsung.
3. **Service/action hanya jika bermanfaat.** Ekstrak operasi seperti save transaksi/settlement yang mengubah beberapa tabel atau dipakai ulang. Hindari generic repository, interface satu implementasi, event bus, atau base controller baru tanpa kebutuhan nyata.
4. **FormRequest untuk validasi non-trivial.** Pakai data tervalidasi; validasi native HTML dan server harus selaras. Pemeriksaan permission dan cabang tetap eksplisit.
5. **Satu sumber perhitungan.** UI boleh menghitung preview, tetapi nominal/HPP/stok server menjadi sumber kebenaran. Jangan menyamakan helper mata uang integer dengan kuantitas desimal.
6. **Query bisa dibaca dan diuji.** Parameter binding untuk input; scope/query method kecil untuk filter yang benar-benar berulang. Eager load relasi atau agregasi, bukan query per baris dalam Blade.
7. **Jangan menyamarkan kegagalan sebagai sukses/kosong.** Gunakan status HTTP dan error UI yang membedakan validasi, izin, sesi berakhir, jaringan, serta server.
8. **Nama menjelaskan domain.** Suffix `2`, `_temp`, dan `Copy` bukan bukti file mati. Ganti nama hanya setelah seluruh referensi dan state aktif dipetakan.
9. **Ukuran adalah sinyal, bukan aturan mekanis.** Controller besar perlu ditinjau per tanggung jawab; memecah file setiap 200 baris tanpa boundary bisnis justru menambah kompleksitas.
10. **Komentar menjelaskan alasan/invariant.** Hapus debug atau kode yang dikomentari setelah dipastikan tidak dibutuhkan; jangan menyalin badan fungsi ke dokumentasi.

### Boundary UI

- Cari komponen bersama lebih dulu: `components/erp/detail-dialog.blade.php` untuk frame detail; shell/dock tidak didefinisikan ulang di setiap halaman.
- `erp-shell.css` mengelola layout shell; `erp-theme.css` token/adapter; stylesheet halaman mengelola halaman terkait.
- Letakkan state interaksi pada scope pemiliknya. Pertahankan deep link, Back/Forward, validasi, permission, dan kontrak JSON ketika memindahkan script Blade ke asset JS.
- Jangan langsung mengganti seluruh legacy build atau menghapus vendor berdasarkan nama. Layout lama masih dapat memuatnya; library juga dapat memuat font/gambar secara tidak langsung.
- Acuan UI tambahan `ui-design-system.md` tersedia lokal pada saat audit, tetapi belum tracked. Jangan menjadikannya satu-satunya panduan pada checkout bersih sampai perubahan tersebut direview/di-commit pemiliknya.

## 4. Verifikasi yang dapat diulang

Jalankan dari root `infruity-main` setelah interpreter benar. Command baca-saja yang dijalankan pada audit:

```bash
php artisan --version
php artisan route:list --json
composer validate --no-check-publish
node --test tests/js/*.test.cjs
```

### PHPUnit dengan isolasi database

Pada baseline audit tidak ada config cache. Jangan menghapus cache aplikasi/server bersama demi test. Jika ada config cache, siapkan lingkungan test terpisah terlebih dahulu.

```bash
# Guard: hentikan sebelum test jika config aplikasi sedang di-cache.
test ! -f bootstrap/cache/config.php || { printf '%s\n' 'Gunakan environment test terpisah: config cache masih ada.'; exit 1; }
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= \
  CACHE_DRIVER=array SESSION_DRIVER=array MAIL_DRIVER=array QUEUE_CONNECTION=sync \
  php vendor/bin/phpunit --do-not-cache-result

# Contoh suite endpoint dengan schema SQLite eksplisit
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= \
  CACHE_DRIVER=array SESSION_DRIVER=array MAIL_DRIVER=array QUEUE_CONNECTION=sync \
  php vendor/bin/phpunit --do-not-cache-result --filter PosPaymentIdempotencyTest
```

Test baru yang menyentuh database harus membuat fixture sendiri atau memakai MySQL khusus test yang disetujui. Jangan mengambil `User::first()`/produk yang kebetulan ada di database developer. Test concurrency nyata membutuhkan koneksi independen pada MySQL khusus test dan harus mempunyai cleanup yang terisolasi.

### Blade, JavaScript, dan UI

- PHP lint memeriksa file PHP non-Blade; Blade perlu dikompilasi lebih dahulu lalu hasil PHP-nya di-lint.
- `view:cache` menyentuh cache view aplikasi. Pada audit, 292 Blade dikompilasi menggunakan compiler aplikasi ke scratch terpisah, kemudian di-lint; cache view aktif tidak dibersihkan.
- Compile/lint tidak mengeksekusi query view dan tidak memverifikasi tampilan browser. Uji browser pada route/state terkait tetap diperlukan untuk perubahan UI.
- Node test runner saat ini menggunakan fixture/stub, bukan browser E2E. Sertakan uji render, cancel, Back/Forward, permission, error, serta tampilan mobile/desktop sesuai perubahan.
- `npm ci` dan build Mix belum dieksekusi dalam audit: tidak ada `node_modules` pada workspace. Jangan menandai build lulus hanya karena asset hasil build lama tersedia.

### Sebelum commit

```bash
git diff --check
git diff --cached --check
git diff --cached --stat
git diff --cached
```

Stage hanya file/hunk pekerjaan sendiri. Pada file yang sudah dirty gunakan `git add -p` atau patch index terpilih, bukan `git add -A`. Setelah commit, periksa `git show --stat --oneline HEAD` serta `git diff` agar perubahan lokal lain tetap utuh.

## 5. Prosedur menghapus file/folder

Tidak ada file yang dihapus pada audit. Gunakan daftar kandidat di laporan, bukan auto-delete berdasarkan scan.

1. Bedakan source, hasil build, vendor, data, cache, dan catatan audit.
2. Cari referensi pada tracked **dan untracked** source, routes, controllers, Blade includes, view namespace provider, config/autoload, JS, CSS `url()`/imports, dan script deployment.
3. Periksa convention/discovery Laravel: migration, provider, command, anonymous Blade component, `errors/*.blade.php`, serta autoload tidak memerlukan pemanggilan eksplisit.
4. Verifikasi route aktif/runtime; nol referensi statis hanya menjadikan file kandidat, bukan bukti universal aman.
5. Hapus kandidat kecil dalam commit tersendiri setelah disetujui, jalankan test/compile, lalu smoke test state yang mungkin memakai file tersebut. Catat batas verifikasinya.
6. Jangan menghapus migration lama, font/lisensi vendor, `vendor/`, storage bisnis, atau repo HTML referensi sebagai cleanup source. Cache hanya boleh dibersihkan secara terarah dan dalam environment yang tepat.

## 6. Standar dokumentasi

- README: tujuan, runtime, setup aman, tautan panduan dan cara menjalankan verifikasi.
- Panduan ini: boundary, invariant, kebiasaan review, dan command yang bisa diulang.
- Laporan audit: tanggal/scope, lokasi bukti, severity, dampak, rekomendasi minimal, batas pengujian, acceptance criteria.
- Riwayat upgrade/laporan lama: dokumen historis; jangan menyalin angka test atau klaim "terbaru" sebagai status saat ini.
- Saat kontrak route/payload, runtime, DB prerequisite, permission, atau alur transaksi berubah, ubah dokumentasi pada commit yang sama. Gunakan path repo relatif; jangan menyalin rahasia ataupun path absolut mesin sebagai instruksi universal.
