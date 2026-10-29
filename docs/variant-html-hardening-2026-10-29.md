# Escaping HTML varian produk — 29 Oktober 2026

Tindak lanjut **A09** pada [audit clean code/KISS](audit-2026-10-28.md#a09--tinggi-html-raw-varian-tidak-meng-escape-nama-pada-atribut). Baseline tahap ini HEAD `6ebae63d`; working tree sudah berisi pekerjaan lokal, termasuk controller produk dan script form. Perubahan produksi tahap ini hanya **empat ekspresi escaping atribut dan satu pembacaan nama edit**.

## Masalah dan akar penyebab

Dua route berautentikasi mengarah ke `ProductController::getVariant()`:

- `GET /ajax/getVariant` — `ajax.getVariant`.
- `GET /products/variants/get` — `variants.get`, dipakai tabel pada detail produk.

Nama dari database digabung langsung ke `data-name="..."` pada kolom `action` yang sengaja diizinkan sebagai HTML melalui `rawColumns(['action'])`. Validasi `string|max:255` tidak membatasi konteks HTML. Kutip dapat mengakhiri atribut dan membentuk event attribute atau elemen tambahan.

Ada masalah pembacaan terkait: handler edit memakai `.data('name')`. jQuery mengonversi beberapa string yang mirip nilai JSON. Nama literal `null` menjadi input kosong, object JSON menjadi `[object Object]`, dan array JSON menjadi daftar dipisahkan koma. Ini terjadi meskipun markup atribut sudah benar.

## Solusi minimal

1. Escape seluruh nilai dinamis pada atribut dua tombol action menggunakan helper Laravel `e()`: ID edit/hapus, nama, dan harga. Kolom `action` tetap HTML supaya tombol tetap berfungsi; kolom nama tetap menggunakan escaping bawaan Yajra.
2. Baca nama melalui `.attr('data-name')` supaya DOM memberikan string atribut asli tanpa konversi tipe otomatis. Pembacaan ID/harga dan alur modal tidak diubah.
3. Jangan strip tags atau mengubah nama saat menyimpan. Escaping berada di boundary output; literal entity seperti `&quot;` harus tetap literal setelah satu kali parsing HTML, bukan berubah menjadi karakter kutip.

Tidak menambah service/helper/view baru, dependency, sanitizer global, migration, atau mengubah route, payload, harga, izin, maupun scope data.

## Regression test dan hasil RED → GREEN

`tests/Feature/ProductVariantEscapingTest.php` memakai koneksi SQLite `:memory:` tersendiri. Schema minimal berasal dari kontrak model/migration: produk, akun legacy aktif, role, dan assignment. Aktor adalah `App\User` yang benar-benar tersimpan; route dan middleware asli tetap aktif, bukan controller/response mock. Koneksi dipurge saat teardown.

- Delapan jenis nama × kedua endpoint: biasa, kutip/ampersand/markup, Unicode, breakout atribut, breakout elemen, breakout script, entity literal, dan teks JSON.
- Parse HTML hasil response dengan DOM: hanya dua tombol semula, atribut persis yang diizinkan, ID/harga benar, nama kembali identik dengan fixture, dan nama tersimpan tidak berubah.
- Kontrak DataTables (`draw`, total/filter count, data) dan filter parent A/B/tidak ada tetap benar.
- Kedua endpoint tetap menolak guest dengan 401.

**RED:** 20 test / 216 assertions, **12 failure**, tanpa setup error. Contoh: `onmouseover` menjadi atribut baru, payload elemen/script menghasilkan empat elemen, dan entity literal ter-decode menjadi nilai berbeda.

**GREEN:** **20 test / 324 assertions**, 0 error/failure/skipped.

`tests/js/product-variant-edit.test.cjs` mengeksekusi binding edit sebenarnya dari Blade, dengan boundary double jQuery/input yang dinyatakan eksplisit. **RED: 3 dari 7 gagal** pada null/object/array; **GREEN: 7/7 lulus**. Harness Node ini bukan browser E2E.

## Verifikasi browser dan postimage commit

Browser memakai sesi fixture terisolasi, **tanpa login atau menutup browser pengguna**. Fragment action dihasilkan melalui HTTP kernel aplikasi sebenarnya terhadap SQLite in-memory, menggunakan controller postimage, bukan JSON/HTML response rekaan. Fixture memuat asset jQuery lokal versi **3.5.1** dan binding edit asli.

- Binding lama direproduksi dengan 10 nama: tiga mismatch pada JSON object, literal `null`, dan JSON array.
- Binding working tree serta postimage masing-masing lulus untuk **10/10 nama**: isi input nama identik, ID 20/harga 12500, modal benar-benar terbuka, tepat dua tombol, tanpa node/atribut injeksi yang diperiksa.
- Fragment browser sudah memakai escaping baru saat membandingkan binding lama/baru; perbandingan ini membuktikan masalah konversi jQuery. Bukti HTML tidak ter-escape sebelum fix berasal dari regression HTTP/DOM RED di atas.

Ini verifikasi komponen fragment/modal, **bukan E2E halaman produk penuh atau pembuktian eksploitasi/CSP deployment**. Tidak ada save/delete produk atau request bisnis ke aplikasi live.

Controller dan script sudah dirty sebelum task. Postimage dibuat dari **blob HEAD + hanya empat escape dan satu pembacaan atribut**, dengan byte/line ending dipertahankan; bagian pekerjaan lokal lain tidak ikut staging. Controller postimage dimuat lewat classmap autoloader terisolasi; JS test membaca script postimage melalui preload khusus di scratch.

| Pemeriksaan | Hasil |
|---|---|
| Fokus varian, working tree | **20 test / 324 assertions lulus** |
| Fokus varian, controller postimage | **20 test / 324 assertions lulus** |
| Handler edit, script postimage | **7 test JS lulus** |
| Full PHPUnit working tree | **318 test / 2.163 assertions; 0 error/failure/skipped; exit 0** |
| Seluruh JavaScript working tree | **102 test lulus; exit 0** |
| PHP lint controller dan test | Lulus |
| Compile Blade + lint PHP, working/postimage | Keduanya lulus; hasil compile hanya ke scratch |

Full suite tetap memakai kebijakan `failOnAllIssues` dari [pemulihan quality gate](quality-gate-2026-10-29.md), dengan mode adapter POS lokal `INFRUITY_TEST_LEGACY_POS_ADAPTER=0`. Hasil seluruh working tree tidak diklaim sebagai hasil checkout bersih yang berisi semua pekerjaan lokal.

## Mengulang regression

Dari root, gunakan PHP 8.5 di PATH dan pastikan config aplikasi tidak di-cache:

```bash
test ! -f bootstrap/cache/config.php || { printf '%s\n' 'Gunakan environment test terpisah: config cache masih ada.'; exit 1; }
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= \
  CACHE_DRIVER=array SESSION_DRIVER=array MAIL_DRIVER=array QUEUE_CONNECTION=sync \
  php vendor/bin/phpunit --do-not-cache-result --display-all-issues tests/Feature/ProductVariantEscapingTest.php

node --test tests/js/product-variant-edit.test.cjs
```

Untuk full suite, ikuti [panduan environment dan mode POS](engineering.md#phpunit-dengan-isolasi-database). Tidak perlu migration, seed, reset, grant permission, atau menghapus cache aplikasi bersama.

## Batas scope

- A09 ditutup untuk sink action `getVariant` beserta pembacaan nama edit tersebut; bukan klaim seluruh HTML raw atau seluruh modul bebas XSS.
- A14, invariant relasi varian saat create/edit/delete, tetap pekerjaan terpisah. Test tahap ini memeriksa populasi editor, bukan mengklaim lifecycle simpan/hapus varian telah diperbaiki.
- Tidak ada data developer/bisnis yang diubah dan tidak ada file/aset kandidat cleanup yang dihapus.
