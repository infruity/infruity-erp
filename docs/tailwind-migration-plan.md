# Plan Migrasi Bootstrap/Metronic → Tailwind CSS

5 November 2026 (revisi 2). Branch fondasi: `feature/tailwind-migration` (dari `origin/main` @ `671fb18a`).

> **Model:** plan ini disusun dengan Opus. **Implementasi B0–B6 dijalankan dengan Sonnet.** Ganti model sesi ke Sonnet sebelum mulai mengerjakan batch mana pun.

Dokumen ini adalah acuan tunggal migrasi. Setiap batch mencentang checklist di [Lampiran A](#lampiran-a--checklist-per-view) dan [Lampiran B](#lampiran-b--backend-yang-menghasilkan-html), lalu memperbarui **Status**. Angka inventaris dihasilkan dengan grep pada 5 November 2026; hitung ulang di awal setiap batch karena `main` terus bergerak.

## Status

| Batch | Branch | PR | Status |
|---|---|---|---|
| B0 Fondasi build | `feature/tailwind-migration` | — | selesai dan terverifikasi; menunggu review dan uji build di server |
| B1 Layout final, komponen, shell, auth, errors, landing | langsung di `integration/tailwind-migration` | — | **B1a, B1b-1 (DataTables npm), B1b-2 (`body` 13px), B1b-3 (kode mati golongan A) selesai**; B1b-4 (`erp-button` → `erp-action`) selesai; menunggu: golongan B kode mati |
| B2 Master | langsung di `integration/tailwind-migration` | — | **B2a selesai** (location, handling, department); berikutnya B2b |
| B3 Report, CRM, Chat, admin | — | — | belum |
| B4 Transaction | — | — | menunggu `ui/produksi-reference` + `ui/stock-opname-reference` masuk `main` |
| B5 POS | — | — | belum |
| B6 Cleanup | — | — | belum |

**Di luar scope, ditindaklanjuti terpisah:** celah XSS di kolom DataTables (§2.6). Akan dibuat PR keamanan sendiri; migrasi tidak menunggu PR itu, tetapi komponen §6.3 otomatis meng-escape.

---

## 1. Keputusan yang sudah dikunci

| # | Topik | Keputusan |
|---|---|---|
| Q1 | Definisi selesai | Bootstrap **dan** Metronic (CSS + JS) tidak dimuat lagi. Tidak ada `data-bs-*`, `data-kt-*`, class Bootstrap/Metronic, maupun ikon `ki-`/`bi-`/`fa-`. Semua halaman memakai satu layout Tailwind. |
| Q2/Q11 | Build | Vite + **Tailwind 3.4** compiled. Laravel Mix dan Tailwind CDN dihapus. 3.4 dipilih demi perangkat lama dan agar cocok dengan class v3 yang sudah dipakai. |
| Q3 | Rollout | Bertahap per batch; setiap batch punya branch + PR sendiri ke `main`. |
| Q4/Q17 | Interaksi | Alpine.js + `<dialog>` native + `erp-overlays.js`, dibungkus Blade component `x-erp.*`. |
| Q5 | Plugin | Select2 → `erp-picker` lalu dihapus. DataTables tetap (npm, styling Tailwind; **dipindah ke npm di B1**, bukan B0, karena skin Metronic harus digantikan sekaligus). SweetAlert2 dan Flatpickr tetap (npm). |
| Q6 | Visual | Ikuti referensi `infruity-frontend` bila ada; selain itu pertahankan tampilan sekarang (dengan konversi ukuran §4.1). Bukan redesign. |
| Q7 | Kode mati | Diaudit, daftarnya dikonfirmasi pemilik repo, lalu dihapus di commit terpisah dalam batch-nya. |
| Q8 | Test | Test = spesifikasi perilaku, diperbarui bersama port, tanpa skip. Ditambah screenshot sebelum/sesudah. |
| Q9 | Branch | Berasal dari `main` murni; branch UI lain tidak di-merge ke sini. |
| Q10 | Deploy | Build di server (`npm ci && npm run build`), Node dipasang bila belum ada. `public/build` di-gitignore. |
| Q12 | Dark mode | Dibuang: toggle Pengaturan, skrip `data-bs-theme`, ikon bulan/matahari. |
| Q13 | Urutan | B0 → B1 → B2 → B3 → B4 → B5 → B6. |
| Q14 | Branch berjalan | `ui/produksi-reference` dan `ui/stock-opname-reference` masuk `main` sebelum B4. |
| Q15 | Print/nota | Dikecualikan dari Tailwind; hasil cetak harus identik. View dompdf/Excel tidak disentuh. |
| Q16 | Data | DB lokal `infruity_clone` (dummy) dari `.env` checkout utama. |
| Q18 | Form | Class komponen `erp-*` (§5.4) ditulis sendiri. `@tailwindcss/forms` **tidak dipakai**: class-nya (`.form-select`, dst.) bentrok dengan `.form-select` Bootstrap/Metronic dan terbukti mengubah padding 61 elemen di halaman lama. Bisa dievaluasi lagi setelah B6. |
| Q19 | Aturan baru | Setelah B1 masuk, UI baru wajib memakai layout/komponen Tailwind. Ditegakkan oleh test ratchet (§8.3) dan ditulis di `AGENTS.md` + `docs/ui-design-system.md`. |
| Q20 | PR | Claude commit, push, dan membuka PR. Merge oleh pemilik repo. |
| **Q30** | Branch integrasi | **Tidak ada yang masuk `main` sampai pemilik repo menyetujui seluruh migrasi.** Pekerjaan dilakukan **langsung di `integration/tailwind-migration`** (dibuat dari `main`): commit kecil per langkah, push ke branch itu. Tidak ada lagi branch dan PR per batch (B0 dan B1a sudah di-merge lewat #5 dan #6). `main` baru menerima satu PR dari `integration/tailwind-migration` setelah disetujui. Sinkronkan branch integrasi dengan `main` secara berkala bila `main` bergerak. |
| Q21/Q22 | Plan | File ini; direview sebelum B0. |
| **Q23** | Ikon | Font **Phosphor** `<i class="ph …" aria-hidden="true">` di semua tempat (Blade, JS, controller). Bobot: regular, bold, fill, duotone. Light/thin dibuang. Penggantian lewat codemod + tabel §4.4. |
| **Q24** | HTML controller | Blade component yang dirender server (`row-actions`, `cell-title`, `badge`, `avatar`). Controller hanya mengirim data. |
| **Q25** | XSS | Ditunda ke PR keamanan terpisah (lihat Status). |
| **Q26** | Ukuran dasar | **`html` 16px, teks body 13px.** Skala Tailwind dan referensi tetap akurat (`text-sm` = 14px). Halaman lama dikonversi dengan tabel §4.1. |
| **Q27** | Penamaan class | Lanjutkan keluarga `erp-*` yang sudah ada (`erp-action`, `erp-record`, `erp-sheet-*`). `erp-button` dilebur ke `erp-action`. Hex di class wajib token. |
| **Q28** | Codemod | Pasangan aman 1:1 diganti script per batch (diff direview). Komponen dan nilai yang beda diganti manual. |
| **Q29** | CSS halaman | 18 file CSS di `public/assets/css` dipindah ke `resources/css/pages/` (Vite) saat halaman pemiliknya diport. |

---

## 2. Kondisi awal (fakta)

### 2.1 Layout dan aset

| Layout | Dipakai | Memuat |
|---|---|---|
| `template.root` | 73 view | Metronic `style.bundle.css` (Bootstrap 5, 1,2 MB), `plugins.bundle.css/js` (11 MB folder), `scripts.bundle.js`, `datatables.bundle.*`, **Tailwind CDN `preflight: false`**, jQuery 3.6 CDN, Alpine (unpkg), `@livewireStyles` bersyarat, firebase messaging, 9 file `erp-*.css`. Shell sudah Tailwind (`x-erp.sidebar/topbar/mobile-navbar`). |
| `layouts.erp-tailwind` | 20 view | Tailwind CDN dengan preflight + `tailwind.config` inline sendiri, Alpine + collapse (jsdelivr), jQuery, `datatables.bundle.*`, SweetAlert2 CDN, ±100 baris CSS inline sidebar. Tanpa `style.bundle.css`. |
| `layouts.app` | 4 view (`auth/passwords/*`, `auth/verify`) | Mix `css/app.css` (Bootstrap 4) + `js/app.js`. Kandidat mati. |
| `errors.layout` | 10 view | Standalone. |
| `auth/login`, `landing` | standalone | Tailwind CDN; `landing` juga memakai `KT*`. |
| `*::layouts.master` | 0–1 view | Scaffold nwidart; kandidat mati. |
| `pos/index` | standalone | Bootstrap 5.3 CDN; kandidat mati (controller memakai `index2`). |

Dua `tailwind.config` inline yang berbeda harus digabung. `package.json` masih Laravel Mix 5 + `bootstrap@4` + `popper.js` + `sass` + `vue-template-compiler`, hanya untuk `layouts.app`. Ada 18 file CSS kustom (±2.300 baris) dan 13 file JS kustom di `public/assets`, dimuat lewat `asset()?v=filemtime`.

### 2.2 Ukuran dasar sekarang (terbukti di browser)

Metronic memasang `body, html { font-size: 13px !important }` dan `12px !important` di bawah 768px. `html.erp-html { font-size: 16px }` di `erp-theme.css` **kalah** karena tidak `!important`. Akibatnya:

| Layout | Root `rem` | `p-4` | `text-sm` |
|---|---|---|---|
| `template.root` ≥ 768px | 13px | 13px | 11,4px |
| `template.root` < 768px | 12px | 12px | 10,5px |
| `layouts.erp-tailwind`, referensi `infruity-frontend` | 16px | 16px | 14px |

**Temuan B0 (terbukti dengan membandingkan dua server berdampingan):** konfigurasi Tailwind inline di `template.root` (`corePlugins.preflight: false` dan seluruh `theme.extend`) **tidak pernah berlaku**, karena diset sebelum skrip CDN dimuat dan tertimpa. Halaman `template.root` sebenarnya berjalan dengan **tema Tailwind default + preflight aktif**: warna border dasar `#e5e7eb`, font shell `ui-sans-serif, system-ui` (bukan Plus Jakarta Sans), dan class kustom seperti `shadow-soft`, `bg-primary`, `bg-strawberry` tidak menghasilkan apa-apa di sana. Layout `erp-tailwind` memakai CDN dengan urutan yang benar sehingga tema kustomnya berlaku. Jadi dua kelompok halaman selama ini tampil dengan font dan token berbeda.

Referensi `infruity-frontend` (38 file, Tailwind CDN) dirancang di root 16px: `text-sm` 2.182×, `text-lg` 1.361×, `text-[11px]` 412×, `text-[10px]` 357×. Shell (`x-erp.sidebar`/`topbar`) sekarang tampil dengan **dua ukuran berbeda** tergantung layout-nya.

### 2.3 Kepadatan per batch

Angka ini diukur dengan penanda presisi di `tests/Support/LegacyUiMarkup.php` (dipakai juga oleh test penjaga), bukan hitungan token kasar. Hitungan awal plan ("BS") ternyata dipenuhi false positive, karena token spasi seperti `p-3` sah di Tailwind; **koreksi penting: shell, layout, dashboard, auth, errors, dan landing sebenarnya sudah bersih dari markup Bootstrap**, jadi B1 bukan batch "memorting halaman" melainkan batch komponen dan alat.

Kolom "Markup" = semua penanda kecuali hex di class (`data-bs`, `data-kt`, ikon lama, `form-*`, `btn*`, `modal`, `select2`, `bootstrap.*`, `KT*`, utilitas `d-*`/`fw-*`/`fs-*`, `badge-light-*`, `@extends('template.root')`). Total hari ini: **217 file, 6.400 hit**. Angka diperbarui dengan `php scripts/ui-migrate/ratchet-snapshot.php --summary`.

| Batch | File | Markup | `data-kt`+`KT*` | Ikon lama | `select2` | `template.root` |
|---|---:|---:|---:|---:|---:|---:|
| B1 | 30 | 1847 | 239 | 288 | 37 | 1 |
| B2 | 35 | 571 | 78 | 79 | 22 | 10 |
| B3 | 47 | 439 | 42 | 49 | 16 | 26 |
| B4 | 61 | 1610 | 162 | 150 | 61 | 21 |
| B5 | 39 | 923 | 67 | 93 | 70 | 15 |
| JS | 4 | 17 | 3 | 0 | 9 | 0 |

B1 didominasi `template/*` (Metronic lama yang mati dan akan dihapus), bukan halaman hidup. Rincian per file ada di `tests/fixtures/legacy-ui-allowlist.json`.

### 2.4 Ketergantungan JavaScript

| Pemakaian | Jumlah | Catatan |
|---|---|---|
| `data-kt-menu*` | 175 | Hampir semua di `template/*` lama dan `auth/footer` |
| `data-kt-ecommerce*` | 164 | Demo Metronic: pengadaan, transfer, sortir, production, stock-opname, `livewire/product-table` |
| `data-kt-check*` | 78 | Select-all checkbox |
| `data-kt-drawer*` | 76 | `template/*`, **stock-opname index** |
| `data-kt-scroll*` | 79 | stock-opname, stock-out, delivery-order, master handling/location/department |
| `data-bs-*` | ±130 | toggle/target/dismiss modal, dropdown, tooltip |
| `bootstrap.Modal` / `.modal()` | 7 file + `erp-crm.js` | |
| Select2 | 79 ref | `erp-picker.js` masih punya 8 ref `select2` (jembatan) |
| SweetAlert2 | 332 panggilan / 47 file | `template.root` dari `plugins.bundle`, `erp-tailwind` dari CDN |
| DataTables 1.13.7 | 101 `ajax:`, 43 `serverSide:`, 25 `fixedColumns:`, 6 `responsive:`, ±10 `select:` | bundle Metronic ikut berisi JSZip + pdfmake; tidak terdeteksi `buttons:` |
| `toastr` | 1 file | `production/js-create` |
| ApexCharts | 1 file | `crm::dashboard.js-dashboard` (kandidat mati) |
| Quill, `KTImageInput`, `KTPasswordMeter` | — | `master::products.edit` (kandidat mati), `crm::tier` |
| `KT*` di view | 3 file | `landing`, `stock-opname/index`, `crm::dashboard.js-dashboard` |
| Livewire 4 | 1 halaman hidup | `produk.blade.php` → `<livewire:produk-form/>`; Livewire membawa Alpine sendiri |

### 2.5 Ikon

| Set | Semua file | **File hidup saja** | Dimuat dari |
|---|---:|---:|---|
| Phosphor `ph-*` | 629 (regular 304, bold 271, fill 54, duotone 42) | — | `public/assets/plugins/phosphor` (6 bobot; light/thin tidak dipakai) |
| Keenicons `ki-*` | 716 | **56 pemakaian, 18 nama** + `<span class="pathN">` di dalamnya | `plugins.bundle.css` |
| Bootstrap Icons `bi-*` | 155 | **145 pemakaian, 20 nama** (sebagian besar di controller) | `plugins.bundle.css` |
| FontAwesome `fa-*` | 56 | **34 pemakaian, 14 nama** | `plugins.bundle.css` |

"File hidup" = view yang direferensikan, controller non-`temp`, dan `public/assets/js` non-bundle. Total **52 nama ikon lama, 235 pemakaian, 58 file**. Begitu `plugins.bundle.css` dilepas, semua ikon ini hilang, jadi semuanya harus sudah diganti sebelum halamannya pindah layout.

### 2.6 HTML dari backend

35 file PHP di luar `Resources/views` (683 atribut `class=`) menghasilkan HTML, terutama kolom Yajra DataTables. Polanya seragam:

| Pola | Jumlah | Contoh |
|---|---:|---|
| Dropdown aksi baris | 41 | `div.dropstart > button.btn.btn-sm.btn-light-primary.dropdown-toggle[data-bs-toggle] > i.bi-three-dots-vertical` + `ul.dropdown-menu > li > a.dropdown-item[onclick]` |
| Badge status | ±95 | `span.badge.badge-light-{success,danger,warning,primary,info,secondary}` |
| Sel nama + subjudul | 21 | `a.text-gray-800.text-hover-primary.fs-5.fw-bold` + `span.text-muted.d-block.fs-7/8` |
| Avatar | beberapa | `div.symbol.symbol-circle.symbol-50px > div.symbol-label.fs-3.bg-light-{$warna}` (class disusun dinamis) |

**Celah keamanan (ditunda, Q25):** data user disambung tanpa escape, lalu dikirim sebagai `rawColumns`. Contoh: `ProductUnitController` (`name`, `description`), `HandlingController`, `LocationController`, `DepartmentController`, `SupplierController` (`name`), `ProductController` (`data-name`).

### 2.7 Sistem class yang hidup bersamaan

1. **Bootstrap/Metronic**, ditambah adapter di `erp-theme.css` yang men-styling ulang `.btn` (14 aturan), `.form-control` (7), `.card` (7), `.form-select`, `.modal-*`, `.select2-*`, `.dataTables_*`, `.swal2-*`, `.flatpickr-*` di bawah `.erp-ui`.
2. **Utility Tailwind dengan nilai ad-hoc**: `text-[11px]` 96, `text-[10px]` 51, `text-[13px]` 47, `bg-[#0b595b]` 37, `text-[#0b595b]` 32, `rounded-[2rem]` 53. Ada 125 atribut class yang lebih dari 180 karakter.
3. **Class semantik `erp-*`** di 18 file CSS: `erp-action` (107; varian `--primary` 29, `--danger` 1), `erp-record` (48), `erp-sheet-dialog/header/body/footer` (±25), `erp-picker`, `erp-workspace*`, `erp-editor*`, `erp-mobile-*`, dan lain-lain. `erp-button` (16) tumpang-tindih dengan `erp-action`.

### 2.8 Test

61 file `tests/Feature`, ±39 di antaranya mengunci markup (`modal` 64×, `select2` 13×), ditambah 23 test JS (`node --test`). Peta test → view ada di Lampiran A. Worktree belum punya `vendor/` dan `node_modules/`, jadi baseline belum diketahui (langkah pertama B0).

---

## 3. Arsitektur akhir

```
resources/
  css/
    app.css                 ← base + token + @layer components (erp-*) + import pages/* dan vendor/*
    legacy.css              ← SEMENTARA B0–B5: @config tailwind.legacy.config.mjs (tema default + preflight) untuk template.root
    components/             ← erp-action, erp-input, erp-badge, erp-table, erp-panel, erp-sheet, erp-avatar …
    pages/                  ← pindahan 18 file public/assets/css (Q29), dipindah saat halamannya diport
    vendor/
      datatables.css  flatpickr.css  swal.css  phosphor.css (4 bobot)
  js/
    alpine.js               ← Alpine (+collapse); module (B0)
    vendor.js               ← jQuery + SweetAlert2, bundle klasik IIFE (B0); DataTables npm + flatpickr menyusul di B1
    erp/                    ← dialog, drawer, dropdown, tabs, collapse, check-all, tooltip, toast, picker, overlays, collections
    pages/                  ← pindahan JS halaman dari public/assets/js saat halamannya diport
    legacy-bridge.js        ← SEMENTARA: shim bootstrap.Modal / $.fn.modal (§5.3), dihapus di B6
  views/
    layouts/erp.blade.php   ← satu-satunya layout aplikasi
    layouts/guest.blade.php ← login, lupa password, errors
    components/erp/*.blade.php
tailwind.config.mjs
tailwind.legacy.config.mjs   ← SEMENTARA, dihapus di B6
postcss.config.cjs
vite.config.mjs
scripts/ui-migrate/         ← codemod class + ikon, audit class, tabel pemetaan (JSON)
```

### 3.1 Ukuran dasar (Q26)

- `html { font-size: 16px }`. Tidak di-override; skala `rem` Tailwind dan referensi berlaku apa adanya.
- `body { font-size: 13px; line-height: 1.5 }` di `@layer base` (`theme.extend.fontSize.body = ['13px', '1.5']`, dipakai `body { @apply text-body }`).
- Input/select/textarea di layar < 768px memakai **16px** agar iOS Safari tidak zoom saat fokus (aturan ini sudah ada di `erp-theme.css` dan dipindah ke `erp-input`).
- Konsekuensi untuk 20 halaman `erp-tailwind`: teks yang **tidak** punya class ukuran sekarang mewarisi 16px dan akan menjadi 13px. Ini dicek di B1 lewat screenshot; teks yang memang harus 16px diberi `text-base` eksplisit.
- Konsekuensi untuk 73 halaman lama: semua nilai `rem` mereka sekarang dihitung dari 13px. Saat diport, gunakan tabel konversi §4.1 agar ukurannya tetap sama dalam px, kecuali bagian yang mengikuti referensi.

### 3.2 `tailwind.config.mjs`

- `content`:
  - `resources/views/**/*.blade.php`, `Modules/*/Resources/views/**/*.blade.php`
  - `app/**/*.php`, `Modules/*/Http/**/*.php`, `Modules/*/Support/**/*.php`
  - `resources/js/**/*.js`, `public/assets/js/*.js` kecuali `*.bundle.js` (selama belum pindah ke Vite)
- `theme.screens`: **default Tailwind** (`sm 640, md 768, lg 1024, xl 1280, 2xl 1536`), sama dengan referensi dan 20 halaman Tailwind. Breakpoint Bootstrap dipetakan di §4.2.
- `theme.extend`:
  - `colors`:
    - `teal { DEFAULT: '#0b595b', hover: '#08484a' }` (primary action/sidebar)
    - `primary: '#0F5C45'` (brand green), `lime: '#d1f36e'`, `accent: '#F2B400'`, `strawberry: '#D6455D'`, `canvas: '#e5e9f3'`
    - Status memakai skala bawaan: emerald (success), amber (warning), red (error), blue (info)
  - `fontFamily`: `sans: ['"Plus Jakarta Sans"', 'sans-serif']`, `serif: ['"Poppins"', 'sans-serif']` (nama `serif` dipertahankan karena dua config lama memakainya)
  - `fontSize.body: ['13px', '1.5']`
  - `boxShadow`: `card: '0 1px 3px rgba(0,0,0,.04), 0 6px 16px rgba(0,0,0,.04)'`, `soft: '0 10px 40px -10px rgba(0,0,0,.08)'`
  - `borderRadius`: `control: '12px'`, `panel: '16px'`
  - `height/minHeight`: `control: '44px'` (dalam px, karena target sentuh tidak boleh ikut skala)
- `plugins`: kosong. `@tailwindcss/forms` tidak dipakai (bentrok `.form-select`, lihat Q18); gaya form ditulis sendiri di `@layer components`.
- `safelist`: **hanya** class yang terbukti disusun dinamis (audit §8.2). Tanpa pola luas.
- Tanpa `prefix`, tanpa `important`.

### 3.3 `tailwind.legacy.config.mjs` (sementara)

`content` sama dengan config utama, `theme: {}` (tema Tailwind **default**, tanpa ekstensi), preflight aktif. Dipakai `resources/css/legacy.css` lewat `@config`, menggantikan CDN di `template.root`. Ini meniru perilaku CDN lama yang sebenarnya (lihat temuan di §2.2), supaya B0 tidak mengubah tampilan halaman-halaman itu. Tema kustom (font Plus Jakarta Sans, `shadow-soft`, `bg-primary`, dst.) baru berlaku untuk sebuah halaman ketika ia pindah ke `layouts.erp`; perubahan font itu disengaja dan tercakup screenshot sebelum/sesudah tiap port. Dihapus di B6.

### 3.4 Vite

Dua build (`vite.config.mjs`, dipilih lewat `--mode`):
- **Utama** (`npm run build`): `resources/css/app.css`, `resources/css/legacy.css`, `resources/js/alpine.js` ke `public/build` (+ `manifest.json`), dimuat dengan `@vite(...)`. Alpine adalah module sehingga berjalan terurut dengan skrip `defer` lain, sama seperti skrip CDN sebelumnya.
- **Vendor** (`--mode vendor`): `resources/js/vendor.js` (jQuery 3.7.1 + SweetAlert2) sebagai **skrip klasik IIFE** ke `public/build/vendor/vendor.js`, dimuat lewat `@include('partials.vendor-js')`. Harus klasik dan sinkron karena skrip inline halaman memakai `$`/`Swal` saat HTML masih di-parse; module baru jalan sesudah parsing.
- **CSS compiled harus dipasang paling akhir di `<head>`**: CDN lama menyisipkan `<style>`-nya di akhir `<head>` (terverifikasi), jadi utility menang atas aturan lain dengan spesifisitas sama. Posisi ini dipertahankan di semua layout.
- `npm run clean` menghapus `public/build/assets` sebelum build agar hash lama tidak menumpuk.
- Script per halaman yang masih di `public/assets/js` tetap memakai `asset()?v=filemtime` sampai halaman pemiliknya diport, lalu pindah ke `resources/js/pages/`.

### 3.5 Alpine dan Livewire

Livewire 4 membawa Alpine sendiri; tidak boleh ada dua instance. **Status (dicek): Livewire tidak punya halaman hidup**, jadi jalur "mati" di bawah yang dipakai dan jalur "masih hidup" hanya cadangan bila server production ternyata memakainya.
- Jika `produk-form` masih hidup (diputuskan awal B2): `app.js` mengimpor `Livewire` dan `Alpine` dari `vendor/livewire/livewire/dist/livewire.esm`, mendaftarkan plugin Alpine ke instance itu, lalu memanggil `Livewire.start()`. Layout memakai `@livewireScriptConfig`.
- Jika mati: Livewire tidak dipakai di layout, dan `alpinejs` diimpor biasa.
- B0–B1: Alpine di-bundle via npm, halaman `produk` tetap memakai auto-inject Livewire dan diuji khusus.

---

## 4. Aturan pemetaan

### 4.1 Konversi ukuran halaman lama (root 13px → 16px)

Berlaku untuk utility **berbasis rem** di halaman `template.root`, baik dari Metronic maupun Tailwind CDN, **kecuali** bagian yang dipindahkan dari referensi (pakai nilai referensi apa adanya). Utility px (`w-50px`, `text-[11px]`, `min-w-[160px]`) tidak berubah.

**Spasi** (`p`, `m`, `gap`, `space`, `inset`, `w`/`h` angka). Metronic dan Tailwind memakai skala yang sama (1 = 0,25rem), jadi hanya root-nya yang berbeda. Rumus: px lama = N × 3,25; pilih Tailwind terdekat (seri → yang lebih kecil).

| Lama N (px @13) | 1 (3,25) | 2 (6,5) | 3 (9,75) | 4 (13) | 5 (16,25) | 6 (19,5) | 7 (22,75) | 8 (26) | 9 (29,25) | 10 (32,5) | 12 (39) | 15 (48,75) | 20 (65) |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **Tailwind** | `1` | `1.5` | `2.5` | `3` | `4` | `5` | `6` | `6` | `7` | `8` | `10` | `12` | `16` |

**Ukuran teks Metronic** (`fs-*`, dihitung di root 13px, lebar 1440px):

| Lama | px | Tailwind |
|---|---|---|
| `fs-9` | 9,75 | `text-[10px]` |
| `fs-8` | 11,05 | `text-[11px]` |
| `fs-7` | 12,35 | `text-xs` (12px) |
| `fs-base` / tanpa class | 13 | `text-body` (13px) / tanpa class |
| `fs-6` | 13,98 | `text-sm` (14px) |
| `fs-5` | 14,95 | `text-[15px]` |
| `fs-4` | 16,25 | `text-base` |
| `fs-3` | ±18 (`calc`, responsif) | `text-lg` |
| `fs-2` | ±21 | `text-xl` |
| `fs-1` | ±25 | `text-2xl` |
| `display-*` | — | manual |

**Ukuran teks Tailwind** di halaman lama (CDN dihitung di 13px): `text-xs` (9,75px) → `text-[10px]`; `text-sm` (11,4) → `text-[11px]`; `text-base` (13) → `text-body`; `text-lg` (14,6) → `text-sm`/`text-[15px]`; `text-xl` (16,25) → `text-base`. Aturannya: jika markup itu disalin dari referensi, kembalikan ke class referensi.

**Radius:** Metronic `rounded` (0,475rem = 6,2px) → `rounded-md` (6px); badge (5,5px) → `rounded-md`. Kontrol dan panel memakai token `rounded-control`/`rounded-panel`.

### 4.2 Pemetaan class (codemod = otomatis; manual = dikerjakan per halaman)

**Display, flex, alignment: codemod**

| Bootstrap/Metronic | Tailwind |
|---|---|
| `d-none` `d-block` `d-inline` `d-inline-block` `d-flex` `d-inline-flex` `d-grid` `d-table` | `hidden` `block` `inline` `inline-block` `flex` `inline-flex` `grid` `table` |
| `d-{bp}-{x}` | `{bp'}:{x}` (bp' di bawah) |
| `flex-column` `flex-row` `flex-column-reverse` | `flex-col` `flex-row` `flex-col-reverse` |
| `flex-wrap` `flex-nowrap` | sama |
| `flex-grow-1` `flex-shrink-0` `flex-fill` | `grow` `shrink-0` `flex-1` |
| `flex-center` (Metronic) | `flex items-center justify-center` |
| `flex-column-fluid` / `flex-row-fluid` (Metronic, `flex: 1 0 auto`) | `grow shrink-0 basis-auto` |
| `align-items-{start,center,end,baseline,stretch}` | `items-{…}` |
| `align-self-*` / `align-content-*` | `self-*` / `content-*` |
| `justify-content-{start,end,center,between,around,evenly}` | `justify-{…}` |
| `order-*` | `order-*` |

**Breakpoint** (prefix, dipakai codemod untuk `d-*`, `text-*`, `flex-*`): `sm` (576) → `sm` (640), `md` → `md` (sama, 768), `lg` (992) → `lg` (1024), `xl` (1200) → `xl` (1280), `xxl` (1400) → `2xl` (1536). **Wajib dicek visual** di rentang 576–639, 992–1023, 1200–1279.

**Teks: codemod**

| Bootstrap/Metronic | Tailwind |
|---|---|
| `text-start` `text-end` `text-center` | sama (Tailwind 3.3+) |
| `text-uppercase` `text-lowercase` `text-capitalize` | `uppercase` `lowercase` `capitalize` |
| `text-nowrap` `text-truncate` `text-wrap` `text-break` | `whitespace-nowrap` `truncate` `whitespace-normal` `break-words` |
| `fw-normal` (400) `fw-semibold` (**500**) `fw-bold` (**600**) `fw-bolder` (700) | `font-normal` `font-medium` `font-semibold` `font-bold` |
| `fst-italic` | `italic` |
| `lh-1` `lh-sm` `lh-base` `lh-lg` | `leading-none` `leading-tight` `leading-normal` `leading-loose` |
| `text-decoration-none` / `text-decoration-underline` | `no-underline` / `underline` |
| `visually-hidden` | `sr-only` |

**Warna: codemod** (Metronic gray kebiruan, digeser satu tingkat ke skala gray Tailwind sesuai ui-design-system)

| Metronic | hex | Tailwind |
|---|---|---|
| `*-gray-100` | `#F9F9F9` | `*-gray-50` |
| `*-gray-200` | `#F1F1F4` | `*-gray-100` |
| `*-gray-300` | `#DBDFE9` | `*-gray-200` |
| `*-gray-400` | `#C4CADA` | `*-gray-300` |
| `*-gray-500`, `text-muted` | `#99A1B7` | `*-gray-400` |
| `*-gray-600` | `#78829D` | `*-gray-500` |
| `*-gray-700` | `#4B5675` | `*-gray-600` |
| `*-gray-800`, `text-dark` | `#252F4A` | `*-gray-800` |
| `*-gray-900` | `#071437` | `*-gray-900` |
| `text-primary` / `bg-primary` | (dioverride ke teal oleh erp-theme) | `text-teal` / `bg-teal` |
| `text-success` / `danger` / `warning` / `info` | | `text-emerald-700` / `text-red-600` / `text-amber-700` / `text-blue-600` |
| `bg-light` / `bg-body` | | `bg-gray-50` / `bg-white` |
| `bg-light-{success,danger,warning,info,primary}` | | `bg-emerald-50` / `bg-red-50` / `bg-amber-50` / `bg-blue-50` / `bg-teal/10` |
| `text-hover-primary` / `bg-hover-light` | | `hover:text-teal` / `hover:bg-gray-50` |
| `bg-[#0b595b]` / `text-[#0b595b]` / `border-[#0b595b]` | | `bg-teal` / `text-teal` / `border-teal` |
| `bg-[#08484a]` / `bg-[#d1f36e]` / `bg-[#e5e9f3]` / `#F2B400` / `#0F5C45` | | `bg-teal-hover` / `bg-lime` / `bg-canvas` / `accent` / `primary` |

**Ukuran, posisi, border, lainnya: codemod**

| Bootstrap/Metronic | Tailwind |
|---|---|
| `w-25` `w-50` `w-75` `w-100` `w-auto` | `w-1/4` `w-1/2` `w-3/4` `w-full` `w-auto` |
| `h-100` `mw-100` `mh-100` `vh-100` `min-vh-100` | `h-full` `max-w-full` `max-h-full` `h-screen` `min-h-screen` |
| `w-{N}px` `h-{N}px` `min-w-{N}px` `mw-{N}px` `mh-{N}px` (Metronic) | `w-[Npx]` `h-[Npx]` `min-w-[Npx]` `max-w-[Npx]` `max-h-[Npx]` |
| `position-{relative,absolute,fixed,sticky,static}` | `relative` … `static` |
| `top-0` `bottom-0` `start-0` `end-0` | sama (Tailwind 3.3+) |
| `translate-middle` | `-translate-x-1/2 -translate-y-1/2` |
| `border-top` `border-bottom` `border-start` `border-end` | `border-t` `border-b` `border-s` `border-e` |
| `border-0` `border-dashed` | sama |
| `rounded-0` `rounded-circle` `rounded-pill` | `rounded-none` `rounded-full` `rounded-full` |
| `rounded` `rounded-1..4` | manual (§4.1) |
| `shadow-none` / `shadow-sm` `shadow` | `shadow-none` / `shadow-card` |
| `overflow-*`, `cursor-pointer`, `opacity-*`, `visible`/`invisible` | sama |
| `user-select-none` / `pe-none` | `select-none` / `pointer-events-none` (hati-hati: `pe-3` = padding-end, sama di keduanya) |
| `z-index-N` (Metronic) | `z-N` |
| `img-fluid` | `max-w-full h-auto` |
| `float-start` / `float-end` / `clearfix` | `float-left` / `float-right` / `flow-root` |
| `scroll-y` / `hover-scroll-y` / `data-kt-scroll` | `overflow-y-auto` + tinggi flex `min-h-0` (manual untuk tingginya) |
| `separator` | `border-t border-gray-100` |
| `row-gap-*` / `column-gap-*` | `gap-y-*` / `gap-x-*` |

**Spasi (`m-*`, `p-*`, `ms-*`, `me-*`, `ps-*`, `pe-*`, `mx-auto`, `m-n*`)**: nama sama, codemod hanya mengonversi nilai menurut §4.1 (dan `m-n3` → `-m-2.5`, dan seterusnya).

**Struktural: manual (ditulis ulang ke komponen §5)**

| Bootstrap/Metronic | Pengganti |
|---|---|
| `row` + `col-*` + `g-*`/`gx-*`/`gy-*` | `grid grid-cols-12 gap-*` + `col-span-*`, atau `flex flex-wrap` untuk kolom auto |
| `card`, `card-header/body/footer/title/toolbar`, `card-flush` | `x-erp.panel` / `erp-panel` |
| `modal*` + `data-bs-toggle="modal"` | `x-erp.dialog` |
| `dropdown*`, `dropstart`, `menu menu-sub-dropdown` + `data-kt-menu-trigger` | `x-erp.dropdown` |
| `nav nav-tabs`, `nav-link`, `tab-content`, `tab-pane` | `x-erp.tabs` |
| `accordion*`, `collapse` | `x-erp.collapse` |
| `data-kt-drawer*` | `x-erp.drawer` |
| `data-kt-check*` | `x-erp.check-all` |
| `data-bs-toggle="tooltip"` | `x-erp.tooltip` atau `title` |
| `badge badge-light-*` / `badge-*` | `x-erp.badge` / `erp-badge erp-badge--{tone}` |
| `btn btn-*` | `erp-action erp-action--{tone}` (§5.4) |
| `form-control` / `form-control-solid` / `form-select` / `form-check*` / `form-label` / `invalid-feedback` / `is-invalid` / `input-group` | `erp-input` / `erp-input` / `erp-select` / `erp-check` / `erp-label` / `erp-error` / `aria-invalid` / `erp-input-group` |
| `table table-row-dashed gy-5 fs-6` | `erp-table` (+ skin DataTables) |
| `symbol symbol-*` + `symbol-label` | `x-erp.avatar` / `erp-avatar` |
| `bullet`, `menu-*`, `aside-*`, `app-*` (Metronic) | dihapus atau ditulis ulang sesuai referensi |

**Bentrok nama (nama sama, makna beda): selalu manual.** `collapse` (Bootstrap: `display:none`; Tailwind: `visibility: collapse`), `container`, `table`, `border` (warna), `rounded`, `shadow`, `text-gray-*`, `bg-gray-*`. Codemod menolak menyentuh token ini dan mencatatnya sebagai "perlu manual".

### 4.3 Lapisan class (Q27)

| Lapis | Isi | Aturan |
|---|---|---|
| **Token** (`tailwind.config`) | warna, font, radius, shadow, tinggi kontrol | Tidak ada hex baru di class. Ukuran font px ad-hoc (`text-[11px]`) boleh, karena referensi juga memakainya. |
| **Komponen** (`@layer components`, prefix `erp-`) | Hanya untuk yang berulang **dan** muncul di string JS/controller: action, input, badge, table, panel, sheet, avatar | Nama BEM: `erp-blok`, `erp-blok__elemen`, `erp-blok--varian`. Melanjutkan nama yang sudah ada. Tidak ada `!important`. |
| **Utility** | layout dan spasi per halaman | Bebas. |
| **Blade component** | `x-erp.*` | Menerima prop varian (`tone`, `size`, `variant`), bukan override class, supaya tidak ada bentrok `p-4` vs `p-6`. `$attributes->merge()` hanya untuk class layout luar (margin, grid). |
| **CSS halaman** | `resources/css/pages/<modul>-<halaman>.css` | Selector diawali class root halaman (misal `.erp-transfer-list …`). Tidak ada selector Bootstrap. |

### 4.4 Ikon → Phosphor (Q23)

Aturan:
- Satu bentuk penulisan di Blade, JS, dan PHP: `<i class="ph ph-{nama}" aria-hidden="true"></i>`.
- Bobot: `ph` (regular, default), `ph-bold` (ikon di tombol/aksi), `ph-fill` (state aktif/status terisi), `ph-duotone` (ilustrasi kecil di header kartu/empty state, seperti di halaman referensi).
- Tombol yang hanya berisi ikon wajib `aria-label` (bukan hanya `title`).
- Ukuran: `text-base` (16px) di dalam teks, `text-xl` (20px) di tombol, `text-2xl` (24px) di navigasi. Bukan `fs-*`.
- Codemod menghapus `<span class="pathN"></span>` di dalam Keenicons, mengganti class sesuai tabel, mempertahankan class lain pada `<i>` (misal `me-2` → `me-2`), dan **gagal** bila ada ikon yang tidak terdaftar.
- Semua nama tujuan sudah diverifikasi ada di `phosphor/regular/style.css`.

**Keenicons (18 nama, 56 pemakaian)**

| Lama | Phosphor | | Lama | Phosphor |
|---|---|---|---|---|
| `ki-cross` | `ph-x` | | `ki-trash` | `ph-trash` |
| `ki-star` | `ph-star` | | `ki-search-list` | `ph-list-magnifying-glass` |
| `ki-purchase` | `ph-shopping-bag` | | `ki-pencil` | `ph-pencil-simple` |
| `ki-plus` | `ph-plus` | | `ki-gift` | `ph-gift` |
| `ki-package` | `ph-package` | | `ki-gear` | `ph-gear` |
| `ki-magnifier` | `ph-magnifying-glass` | | `ki-down` | `ph-caret-down` |
| `ki-information-5` | `ph-info` | | `ki-chef-hat` | `ph-chef-hat` |
| `ki-check` | `ph-check` | | `ki-calculator` | `ph-calculator` |
| `ki-arrow-right` | `ph-arrow-right` | | `ki-book` | `ph-book-open` |

**Bootstrap Icons (20 nama, 145 pemakaian)**

| Lama | Phosphor | | Lama | Phosphor |
|---|---|---|---|---|
| `bi-three-dots-vertical` | `ph-bold ph-dots-three-vertical` | | `bi-whatsapp` | `ph-whatsapp-logo` |
| `bi-trash` | `ph-trash` | | `bi-save` | `ph-floppy-disk` |
| `bi-pencil-square`, `bi-pencil`, `bi-pencil-fill` | `ph-pencil-simple` | | `bi-plus`, `bi-plus-lg` | `ph-plus` |
| `bi-eye` | `ph-eye` | | `bi-dash-lg` | `ph-minus` |
| `bi-cash-stack` | `ph-money` | | `bi-person-bounding-box` | `ph-user-focus` |
| `bi-check2-circle`, `bi-check-circle` | `ph-check-circle` | | `bi-check-circle-fill` | `ph-fill ph-check-circle` |
| `bi-lock-fill` | `ph-fill ph-lock` | | `bi-box-arrow-up-right` | `ph-arrow-square-out` |
| `bi-box`, `bi-box-seam` | `ph-package` | | `bi-arrow-left` / `bi-arrow-right` | `ph-arrow-left` / `ph-arrow-right` |

**FontAwesome (14 nama, 34 pemakaian)**

| Lama | Phosphor | | Lama | Phosphor |
|---|---|---|---|---|
| `fa-plus` | `ph-plus` | | `fa-trash-alt`, `fa-trash` | `ph-trash` |
| `fa-print` | `ph-printer` | | `fa-paste` | `ph-clipboard-text` |
| `fa-eye` | `ph-eye` | | `fa-minus` | `ph-minus` |
| `fa-times` | `ph-x` | | `fa-history` | `ph-clock-counter-clockwise` |
| `fa-pencil`, `fa-edit` | `ph-pencil-simple` | | `fa-file` | `ph-file` |
| `fa-receipt` | `ph-receipt` | | `fa-comment` | `ph-chat-text` |
| `fa-check` | `ph-check` | | `fa-3x`, `fa-lg`, `fa-fw` (ukuran) | `text-3xl` / `text-lg` / `w-5 text-center` |

Pilihan `ph-pencil-simple` untuk semua aksi "ubah" dan `ph-trash` untuk "hapus" mengikuti konvensi yang sudah ada (17 dan 21 pemakaian). Ikon di file kandidat mati tidak dipetakan; file itu dihapus. Bila main bertambah ikon lama baru sebelum batch-nya, codemod akan gagal dan tabel ini ditambah.

---

## 5. Komponen yang dibangun

Semua komponen adalah Blade anonymous component di `resources/views/components/erp/`, dengan logika Alpine di `resources/js/erp/`. Setiap komponen punya feature test render dan, bila ada logika JS, test `node --test`.

### 5.1 Overlay

| Komponen | API | Perilaku wajib |
|---|---|---|
| `x-erp.dialog` | `id`, `title`, `size` (`sm/md/lg/xl/full`), `variant` (`center` / `sheet` = bottom sheet di mobile), slot `header`/default/`footer`, `static` | `<dialog>` + `showModal()`; Esc menutup (kecuali `static`); fokus awal + pemulihan fokus; scroll-lock; tinggi dibatasi `dvh` dengan isi yang scroll (setara `.erp-sheet-dialog` sekarang); event `erp:dialog-open/close`, ditambah `show/shown/hide/hidden.bs.modal` selama masa jembatan |
| `x-erp.drawer` | `id`, `side` (`right`/`bottom`), `width`, slot | Seperti dialog; containment + inert dari `erp-overlays.js` |
| `x-erp.dropdown` | `align`, `placement`, slot `trigger`/default | Klik/Enter/Space membuka; Esc + klik luar menutup; navigasi panah; naik otomatis bila mepet bawah; teleport ke `body` di dalam tabel |
| `x-erp.tooltip` | `text`, `placement` | hover + fokus; `aria-describedby` |
| `erp.toast()` (JS) | `type`, `message` | Di atas `Swal.mixin({ toast: true })`; menggantikan `toastr` |

### 5.2 Konten dan data

| Komponen | API | Catatan |
|---|---|---|
| `x-erp.tabs` + `x-erp.tab-panel` | `tabs=[['name','label']]`, `active`; panel `<x-erp.tab-panel name>` | `role=tablist`, navigasi panah (belum: `?tab=` di URL) |
| `x-erp.collapse` | `open`, slot `trigger`/default | `x-collapse`, `aria-expanded/controls` |
| `x-erp.check-all` | `target` (selector) | header mengontrol `[data-erp-check]`; indeterminate; ikut DataTables `draw` |
| `x-erp.panel` | `title`, slot `toolbar`/default/`footer`, `padding` | pembungkus `erp-panel` |
| `x-erp.badge` | `tone` (`success/warning/danger/info/neutral/primary`), slot | `erp-badge erp-badge--{tone}` |
| `x-erp.avatar` | `name`, `src`, `tone`, `size` | inisial bila tanpa gambar; tone dari daftar tetap (bukan konkatenasi bebas) |
| `x-erp.image-input` | `name`, `value`, `accept` | pengganti `KTImageInput`; dibangun saat batch yang membutuhkan (B3) |

### 5.3 Jembatan sementara `legacy-bridge.js`

Banyak skrip halaman memanggil `bootstrap.Modal.getOrCreateInstance(el).show()`, `$('#x').modal('hide')`, dan mendengarkan `shown.bs.modal`. Supaya port bisa per halaman:
- Di `layouts.erp`, `window.bootstrap = { Modal, Tooltip }` adalah shim kecil: Modal meneruskan ke `x-erp.dialog` (`show/hide/toggle/getInstance/getOrCreateInstance`) dan memancarkan event `*.bs.modal`; Tooltip no-op. `$.fn.modal` mendapat shim yang sama.
- Dropdown/Tab/Collapse **tidak** di-shim; markup-nya langsung diganti komponen.
- Setiap pemakaian shim menulis `console.warn` di mode dev. Grep `bootstrap\.` dan `.modal(` harus 0 di akhir B5; shim dihapus di B6.

### 5.4 Class komponen CSS

| Class | Isi |
|---|---|
| `erp-action` + `--primary` `--secondary` `--danger` `--ghost` `--icon` `--sm` `--block` | Keluarga yang sudah ada (107 pemakaian) dirapikan. `erp-button` (16) dilebur ke sini. Tinggi `h-control`, radius `rounded-control`, focus-visible teal 2px offset 3px, disabled `opacity-50 cursor-not-allowed`, slot spinner `[data-loading]`. |
| `erp-input`, `erp-select`, `erp-textarea` | Ditulis sendiri (`appearance: none` + panah SVG untuk select), tinggi 44px, radius 12px, 16px di < 768px; `[aria-invalid=true]` merah |
| `erp-check`, `erp-radio` | Ditulis sendiri (`appearance: none`, centang SVG), warna teal |
| `erp-label`, `erp-label--required`, `erp-help`, `erp-error` | `erp-error` dihubungkan lewat `aria-describedby` |
| `erp-input-group` | prefix/suffix (Rp, satuan) dengan border bersama |
| `erp-badge` + `--{tone}` | radius `rounded-md`, `text-xs font-semibold`, warna soft (bg-50 + text-700) |
| `erp-table` | header, padding baris, border dashed, `tabular-nums` |
| `erp-panel`, `erp-sheet-*`, `erp-record` | yang sudah ada dipindah ke `resources/css/components/` dan dibersihkan dari ketergantungan Bootstrap |
| `erp-avatar` + `--sm/md/lg` + `--{tone}` | pengganti `symbol` |

---

## 6. Strategi plugin dan backend

### 6.1 DataTables

- **Selesai untuk `layouts.erp` (B1b-1):** `datatables.net@1.13.7` di `vendor.js` + `datatables-bootstrap.js` + `resources/css/datatables.css`. Halaman `template.root` tetap memakai bundle Metronic sampai dipindah. Ganti `datatables.bundle.*` Metronic dengan npm `datatables.net@1.13.x` (API sama dengan 1.13.7 sekarang) + `datatables.net-fixedcolumns`, `datatables.net-responsive`, `datatables.net-select`. **Tanpa** `datatables.net-bs5`. JSZip/pdfmake hanya dipasang bila audit B0 menemukan tombol export.
- Skin `resources/css/vendor/datatables.css` memakai token: wrapper, length, filter, info, paginate, ikon sort, processing, fixedColumns.
- `erp-collections.js` (daftar mobile dari DOM DataTables) dipertahankan; selector-nya diaudit karena `dom` default bs5 (`.row`, `.col-sm-12`) hilang. Opsi `dom` di setiap halaman dicek.
- Upgrade ke DataTables 2.x **bukan** bagian migrasi ini.
- Satu halaman tidak pernah memuat bundle Metronic dan build npm sekaligus.

### 6.2 Select2 → `erp-picker`

Setiap batch mengganti semua `select2` di halamannya. Event `select2:select/unselect/clear` yang didengar skrip halaman dipetakan ke event `erp-picker` (daftar event ditulis di B1). Setelah select2 terakhir hilang (B5), jembatan di `erp-picker.js` dan adapter select2 di `erp-theme.css` dihapus (B6).

### 6.3 HTML dari controller (Q24)

Empat komponen server-render menggantikan pola §2.6:

```php
// Sebelum — ProductUnitController
$html = '<div class="dropstart"><button class="btn btn-sm btn-light-primary dropdown-toggle" data-bs-toggle="dropdown">'
      . '<i class="bi bi-three-dots-vertical"></i></button><ul class="dropdown-menu p-1">';
if (check_access('unit.edit')) {
    $html .= '<li><a class="dropdown-item" onclick="editProduct(' . $row->id . ')"><i class="bi bi-pencil-square"></i></a></li>';
}

// Sesudah
->addColumn('action', fn ($row) => view('components.erp.row-actions', [
    'label'   => "Aksi {$row->name}",
    'actions' => [
        ['label' => 'Ubah',  'icon' => 'pencil-simple', 'onclick' => "editProduct({$row->id})",   'can' => check_access('unit.edit')],
        ['label' => 'Hapus', 'icon' => 'trash', 'tone' => 'danger', 'onclick' => "deleteProduct({$row->id})", 'can' => check_access('unit.delete')],
    ],
])->render())
->addColumn('name', fn ($row) => view('components.erp.cell-title', [
    'title' => $row->name, 'subtitle' => $row->description,
])->render())
->addColumn('type', fn ($row) => view('components.erp.badge', [
    'tone' => ['+' => 'success', '-' => 'danger'][$row->type] ?? 'primary', 'slot' => $row->type,
])->render())
```

| Komponen | Props | Menggantikan |
|---|---|---|
| `x-erp.row-actions` | `label` (aria), `actions[]` = `label`, `icon`, `href`/`onclick`/`data`, `tone`, `can`, `confirm` | 41 dropdown aksi; item dengan `can=false` tidak dirender; bila semua item `can=false`, komponen tidak merender apa-apa |
| `x-erp.cell-title` | `title`, `subtitle`, `href` | 21 sel nama + subjudul |
| `x-erp.badge` | `tone`, slot | ±95 badge |
| `x-erp.avatar` | `name`, `src`, `tone`, `size` | `symbol` + `bg-light-{$warna}` dinamis |

Aturan:
- Pengecekan akses (`check_access`) tetap di controller; nilainya dioper sebagai `can`. Handler `onclick`/`data-*` yang dipakai JS halaman **tidak berubah**.
- Output memakai `{{ }}` (escape). Ini juga menutup XSS untuk kolom yang dimigrasi; PR keamanan Q25 tetap dibuat untuk kolom yang belum dimigrasi.
- Kolom yang hanya berisi angka/teks tanpa HTML tidak disentuh.
- Bila render Blade per baris terlalu lambat (diukur di B2 pada tabel 100 baris), komponen di-cache per request atau dipindah ke helper PHP. Ini fallback, bukan rencana awal.
- Setiap PR mencantumkan controller yang diubah (perubahan backend).

### 6.4 Lainnya

| Plugin | Keputusan |
|---|---|
| SweetAlert2 | npm, `window.Swal`, override token di `vendor/swal.css` |
| Flatpickr | npm + locale `id`, override token di `vendor/flatpickr.css` |
| `toastr` (1 file) | `erp.toast()` di B4 |
| ApexCharts | npm, impor hanya di halaman CRM dashboard bila hidup |
| Quill, `KTImageInput`, `KTPasswordMeter` | Halaman pemakainya kandidat mati, kecuali `crm::tier` → `x-erp.image-input` |
| jQuery | Tetap (npm, `window.$`) selama DataTables dan skrip halaman memakainya. Menghapus jQuery bukan tujuan migrasi ini. |
| Firebase messaging | Dipindah apa adanya ke layout baru |

---

## 7. Yang dibangun dan yang dibuang

### 7.1 Dibangun

| Kategori | Item | Batch |
|---|---|---|
| Build | `package.json` baru, `vite.config.mjs`, `postcss.config.cjs`, `tailwind.config.mjs`, `resources/js/{alpine,vendor}.js`, `partials/vendor-js.blade.php` | B0 |
| Build sementara | `tailwind.legacy.config.mjs` (tema default + preflight), `resources/css/legacy.css` | B0 (dihapus B6) |
| CSS | `resources/css/app.css` (base 16px/body 13px, token, import), `resources/css/components/*`, `resources/css/vendor/{datatables,flatpickr,swal,phosphor}.css` | B0–B1 |
| CSS halaman | `resources/css/pages/*` (pindahan 18 file, Q29) | saat halamannya diport |
| JS | `resources/js/app.js`, `resources/js/erp/{dialog,drawer,dropdown,tabs,collapse,check-all,tooltip,toast}.js`; `erp-overlays`, `erp-picker`, `erp-collections` dipindah ke `resources/js/erp/` | B0–B1 |
| JS sementara | `resources/js/legacy-bridge.js` | B1 (dihapus B6) |
| JS halaman | `resources/js/pages/*` (pindahan `public/assets/js/*`) | saat halamannya diport |
| Layout | `layouts/erp.blade.php`, `layouts/guest.blade.php` | B1 |
| Blade component | `dialog`, `drawer`, `dropdown`, `tooltip`, `tabs`, `tab`, `collapse`, `check-all`, `panel`, `badge`, `avatar`, `row-actions`, `cell-title` | B1 |
| Blade component | `image-input` | B3 |
| Komponen dibangun ulang | `detail-dialog` (di atas `dialog`), `dropdown-select` (dilebur ke `erp-picker` atau dipertahankan), `sidebar`/`topbar`/`mobile-navbar` bersih dari Bootstrap | B1 |
| Class komponen | `erp-action*` (dirapikan), `erp-input`, `erp-select`, `erp-textarea`, `erp-check`, `erp-radio`, `erp-label*`, `erp-help`, `erp-error`, `erp-input-group`, `erp-badge*`, `erp-table`, `erp-avatar*` | B1 |
| Tooling | `scripts/ui-migrate/class-map.json`, `icon-map.json`, `codemod.mjs` (class + ikon, mode `--check` / `--write`, per file/folder), `class-audit.mjs` (class dipakai vs CSS compiled) | B0 (map) / B1 (codemod) |
| Test | `NoLegacyUiMarkupTest` + `tests/fixtures/legacy-ui-allowlist.php`, test render tiap komponen, test JS `erp-collections`, test bahwa view print/pdf tidak memuat `@vite` | B0–B1 |
| Dokumen | README "Build frontend", `docs/ui-design-system.md` (komponen, class, ukuran 16/13), aturan Q19 di `AGENTS.md` | B0–B1 |

### 7.2 Dibuang

| Item | Ukuran / jumlah | Batch | Syarat |
|---|---|---|---|
| `<script src="https://cdn.tailwindcss.com">` + 2 `tailwind.config` inline | 4 file | B0 | — |
| Alpine dari unpkg/jsdelivr, SweetAlert2 CDN, jQuery CDN di layout | — | B0 | diganti bundle npm |
| Laravel Mix: `webpack.mix.js`, `resources/sass/`, `resources/js/bootstrap.js`, `public/mix-manifest.json`, `public/css/app.css`, `public/js/app.js` | 1,3 MB | B0, atau B1 bila `layouts.app` masih hidup | `layouts.app` terbukti mati |
| Dependensi npm: `laravel-mix`, `bootstrap@4`, `popper.js`, `sass`, `sass-loader`, `resolve-url-loader`, `vue-template-compiler`, `cross-env`; `axios`/`lodash` bila tak terpakai | — | B0 | — |
| CSS inline sidebar di `layouts.erp-tailwind` | ±100 baris | B0 | dipindah ke `app.css` |
| Dark mode: toggle Pengaturan, skrip `data-bs-theme`, `ki-night-day`/`ki-moon` | — | B1 | — |
| `layouts.erp-tailwind` (menjadi alias, lalu dihapus) | — | B1 / B6 | — |
| `template - Copy/` | folder | B1 | — |
| View kandidat mati (Lampiran A ⚠) | ±50 view | batch modulnya | dikonfirmasi pemilik repo |
| `PosController.php.temp`, `StockOpnameController.php.temp`, `ProductionController_temp.php` | 3 file | B4/B5 | dikonfirmasi |
| Livewire: `app/Livewire/{PosKasir,ProductTable,ProdukForm}.php`, `resources/views/livewire/*`, `produk.blade.php`, `DashboardController::produk()`, `@livewireStyles` di `template.root` | 3 class + 3 view + 1 halaman | B2 | dikonfirmasi server; paket composer `livewire/livewire` dilepas di PR backend terpisah |
| `auth/register.blade.php` (route `/register` tetap sebagai redirect) | 1 view | B1 | konfirmasi server |
| Bobot Phosphor light + thin | 2 dari 6 | B0 | — |
| `template/root.blade.php` + sisa `template/*` | ±25 view | B6 | 0 view memakai `template.root` |
| `public/assets/css/style.bundle.css` | 1,2 MB | B6 | — |
| `public/assets/plugins/global/` (`plugins.bundle.*`, font ikon Keenicons/BS Icons/FA) | 11 MB | B6 | grep ikon lama = 0 |
| `public/assets/plugins/custom/` (datatables, formrepeater, ckeditor, tinymce, fullcalendar, jstree, leaflet, …: 16 plugin) | 12 MB | B6 | hanya `datatables` + `formrepeater` yang direferensikan; keduanya sudah tidak dipakai |
| `public/assets/js/scripts.bundle.js`, `widgets.bundle.js`, `public/assets/js/custom/` (133 file demo) | 1,4 MB | B6 | — |
| `public/assets/media/` | 47 MB | B6 | **audit per file**: hanya `svg/files/*`, `avatars/300-*`, `flags/indonesia.svg` yang direferensikan file hidup; yang dipakai dipindah ke `public/images/`, sisanya dihapus |
| `public/jquery.js`, `public/js/jquery.min.js` | 88 KB + | B6 | 0 referensi |
| `public/mask/`, `public/error/`, `public/font/`, `public/assets-landing/` | 4,6 MB | B6 | 0 referensi statis terdeteksi; cek ulang (bisa dipakai lewat CSS/JS) |
| `public/infruity-ui/` | 27 MB | **tidak dibuang otomatis** | 7 referensi; ditanyakan ke pemilik repo |
| Adapter Bootstrap/select2/DataTables-bs5 di `erp-theme.css` | ±150 baris | B6 | — |
| Jembatan select2 di `erp-picker.js`; `legacy-bridge.js`; `tailwind.legacy.config.mjs`; `legacy.css`; `layouts.erp-tailwind` | — | B6 | — |
| Fixture allowlist ratchet | — | B6 | allowlist kosong (test-nya tetap ada) |

---

## 8. Risiko, audit, penjaga

### 8.1 Risiko

| # | Risiko | Mitigasi |
|---|---|---|
| R1 | Class yang disusun di runtime (konkatenasi JS/PHP) tidak ikut ter-compile: CDN men-scan DOM, compiled men-scan file | Audit §8.2 di B0; safelist eksplisit; screenshot B0 harus identik |
| R2 | Kehilangan `!important` Metronic mengubah urutan menang CSS `erp-*` | Port per halaman + screenshot; tidak ada `!important` baru |
| R3 | Bentrok nama `collapse`/`container`/`table`/`border`/`rounded`/`shadow` | Selalu manual (§4.2); codemod menolaknya |
| R4 | Ukuran bergeser: root 13px → 16px | Tabel konversi §4.1; screenshot fokus pada ukuran teks dan jarak |
| R5 | Teks tanpa class di 20 halaman Tailwind berubah 16px → 13px karena body 13px | Cek di B1; beri `text-base` eksplisit bila memang 16px |
| R6 | Livewire + Alpine dobel | §3.5; halaman `produk` diuji di B0 dan B2 |
| R7 | DataTables kehilangan markup bs5, `erp-collections.js` patah | §6.1; test JS `erp-collections` ditambahkan di B0 |
| R8 | Uang: POS payment, delivery order payment, arus kas, wholesale | POS paling akhir (B5); makna test kontrak uang tidak berubah; smoke test dua kali (Claude + pemilik repo) |
| R9 | Build di server gagal | B0 menulis langkah deploy + versi Node minimum, diverifikasi di server **sebelum** PR B0 di-merge; fallback sementara: commit `public/build` |
| R10 | Cache `?v=filemtime` vs manifest Vite | Aset Vite memakai hash; file di `public/assets` yang belum dipindah tetap `filemtime` |
| R11 | Pekerjaan UI lain di `main` menambah markup Bootstrap | Test ratchet §8.3 + aturan `AGENTS.md` mulai B1 |
| R12 | Konflik dengan `ui/produksi-reference`/`ui/stock-opname-reference` | Q14 |
| R13 | View dompdf/Excel ikut berubah | Dikecualikan; test memastikan tidak memuat `@vite` |
| R14 | Breakpoint `sm`/`lg`/`xl` bergeser | Screenshot di 390, 600, 1000, 1240, 1440 |
| R15 | Halaman "mati" ternyata dipakai (referensi dinamis, bookmark) | Cek `route:list` + konfirmasi pemilik repo sebelum hapus |
| R16 | Render Blade per baris memperlambat DataTables | Ukur di B2 (100 baris); fallback §6.3 |
| R17 | PHP lokal default 8.3 (repo ^8.5) | Pakai `/opt/homebrew/opt/php@8.5/bin/php` |
| R18 | **Class bertabrakan di satu elemen** (misalnya `class="… bg-white" :class="[bgDinamis]"`, `font-medium` + `font-bold`, `text-gray-700` + `text-gray-900`). Utility dengan spesifisitas sama dimenangkan oleh urutan di file CSS, dan urutan CDN berbeda dari build compiled (di 5 kasus yang ditemukan, pemenang CDN adalah class yang lebih awal secara alfabet, build compiled sebaliknya). Hasil visual berubah diam-diam. | Di B0: 5 elemen terbukti terdampak dan diperbaiki agar maksudnya eksplisit dengan hasil sama seperti baseline (§10 B0). Di B1: detektor tabrakan (`scripts/ui-migrate/class-conflicts`, statis untuk `class` + literal di `:class`, dan runtime untuk elemen di DOM) dijalankan per batch, dan test ratchet melarang tabrakan baru. Aturan penulisan: jangan menaruh dua utility dari kelompok yang sama di satu elemen; pindahkan salah satunya ke cabang kondisi yang eksklusif. |

### 8.2 Audit class dinamis (B0, diulang setiap batch)

1. Grep konkatenasi class di `public/assets/js/*.js`, `<script>` inline Blade, dan PHP (`'badge-' .`, `'bg-light-' . $color`, `"text-{$x}"`, template literal `` `bg-${…}` ``).
2. Setiap temuan diganti map class lengkap (`{ success: 'erp-badge--success', … }`), atau dimasukkan ke `safelist` bila belum bisa diubah.
3. `scripts/ui-migrate/class-audit.mjs` me-render halaman sampel lewat browser, mengumpulkan semua class di DOM, lalu memastikan setiap class Tailwind ada di CSS compiled. Dijalankan di setiap PR.

### 8.3 Test ratchet "tidak ada markup lama baru" (B1)

`tests/Feature/NoLegacyUiMarkupTest.php` memindai `*.blade.php` dan PHP backend untuk penanda: `data-bs-`, `data-kt-`, `ki-(duotone|outline|solid)`, `\bbi-`, `\bfa[srb]? fa-`, `form-control`, `form-select`, `btn btn-`, `class="[^"]*\bmodal\b`, `select2`, `bootstrap\.`, `KT[A-Z]`, `@extends('template.root')`, `cdn.tailwindcss.com`, dan hex di class (`-\[#`). Hasilnya dibandingkan dengan `tests/fixtures/legacy-ui-allowlist.php` (path → jumlah maksimum). Test **gagal** bila ada file baru dengan penanda atau jumlah sebuah file naik. Setiap batch menurunkan allowlist; di akhir B6 kosong.

---

## 9. Prosedur verifikasi

### 9.1 Lingkungan lokal

```bash
cp ../../../.env .env   # sudah dilakukan; tidak di-commit
/opt/homebrew/opt/php@8.5/bin/php /opt/homebrew/bin/composer install
npm ci && npm run dev
/opt/homebrew/opt/php@8.5/bin/php artisan serve
```

DB `infruity_clone` (dummy). Akun login memakai user dari data yang ada (tidak ditulis di repo).

### 9.2 Resep port per halaman (B1–B5)

1. **Sebelum**: screenshot di 390, 1024, 1280, 1440 px untuk state kosong, berisi, modal/sheet terbuka, error validasi, loading DataTables (disimpan di scratchpad, tidak di repo).
2. Jalankan `codemod --check` pada file halaman (+ partial + controller-nya) dan baca laporannya.
3. Jalankan `codemod --write`, lalu review diff.
4. `@extends('template.root')` → `@extends('layouts.erp')`.
5. Kerjakan manual: komponen §5 (modal, dropdown, card, form, tabs), bentrok nama §4.2, konversi ukuran §4.1 (atau nilai referensi), ikon tanpa padanan.
6. JS: `bootstrap.Modal`/`.modal()` → API dialog (atau shim, dicatat), `select2` → `erp-picker`, `data-kt-*` → komponen. JS halaman pindah ke `resources/js/pages/`, CSS ke `resources/css/pages/`.
7. Controller halaman itu → komponen §6.3.
8. Perbarui test pengunci (Lampiran A): selector boleh berubah, **perilaku yang dikunci tidak boleh hilang**.
9. **Sesudah**: screenshot pada state yang sama, bandingkan. Selisih yang disengaja (mengikuti referensi) dicatat di PR.
10. Turunkan allowlist ratchet dan centang Lampiran A/B.

### 9.3 Gerbang setiap PR batch

- [ ] `php artisan test` hijau
- [ ] `node --test tests/js` hijau
- [ ] `npm run build` tanpa peringatan; `class-audit` bersih
- [ ] `codemod --check` pada file batch: 0 token lama tersisa (kecuali yang tercatat)
- [ ] Ratchet: allowlist turun, tidak ada file baru
- [ ] Screenshot sebelum/sesudah semua halaman batch diringkas di PR
- [ ] Smoke test alur sesuai modul batch:
  - Master: CRUD produk, pelanggan, staff
  - Transaction: pengadaan → terima → transfer → sortir → produksi → stok opname
  - POS: kasir → pembayaran → struk → delivery order + pembayaran → arus kas → order book
- [ ] Konsol browser bersih di semua halaman batch
- [ ] Perubahan backend (controller, route) dan view yang dihapus disebutkan di deskripsi PR

---

## 10. Rencana per batch

### B0 — Fondasi build (branch ini)

Tujuan: mengganti **mesin** tanpa mengubah tampilan.

1. **Baseline**: `composer install` (PHP 8.5), test PHP dan JS (catatan di bawah), scan semua route GET, dan sidik jari style (warna, font, ukuran, jarak, radius, bayangan, ukuran kotak) tiap elemen di 73 halaman sehat pada lebar 1280 dan 390.
2. **Audit**: class dinamis (semuanya class Metronic, tanpa safelist), opsi DataTables (tanpa `dom`/`buttons`; ekstensi `fixedColumns` 25×, `select` 8×, `responsive` 6×, jadi JSZip/pdfmake tidak perlu), `axios`/`lodash` (hanya di file Mix lama).
3. `package.json` baru: Vite 8, laravel-vite-plugin 3, Tailwind 3.4, PostCSS, Alpine (+collapse), jQuery **3.7.1** (dipin; jQuery 4 punya perubahan yang memecahkan), SweetAlert2. Node ^20.19 atau >= 22.12.
4. `vite.config.mjs` (dua mode, §3.4), `postcss.config.cjs`, `tailwind.config.mjs` (gabungan 4 config inline; `cream` disamakan ke `#FAF8F2`, hanya dipakai login dan landing), `tailwind.legacy.config.mjs`, `resources/css/{app,legacy}.css`, `resources/js/{alpine,vendor}.js`.
5. Ganti CDN: `layouts.erp-tailwind` (Tailwind, Alpine, jQuery, SweetAlert2), `template.root` (Tailwind, Alpine), `auth/login` dan `landing` (Tailwind). CSS compiled dipasang paling akhir di `<head>` (§3.4). Yang **sengaja belum** diganti: jQuery CDN di `template.root` (Metronic memuat jQuery-nya sendiri sesudahnya), `datatables.bundle.*` (skin Metronic, diganti bersama skin baru di B1), CSS inline sidebar di `erp-tailwind` (urutan cascade; dipindah di B1), dan skrip Phosphor via unpkg di `landing`.
6. `body 13px` **tidak** dipasang di B0; itu perubahan visual dan masuk B1.
7. Phosphor light + thin dibuang (tidak dipakai di mana pun).
8. Mix dihapus: `webpack.mix.js`, `resources/sass`, `resources/js/{app,bootstrap}.js`. `public/css/app.css`, `public/js/app.js` dan `public/mix-manifest.json` tetap sampai `layouts.app` (mati) dihapus di B1.
9. `.gitignore` + `/public/build`; README "Build frontend"; `tests/TestCase.php` memakai `withoutVite()`.
10. `scripts/ui-migrate/class-map.json` + `icon-map.json` + test integritasnya (`tests/js/ui-migrate-maps.test.cjs`: setiap ikon tujuan ada di font Phosphor, tabel konversi spasi hanya berisi langkah skala valid). `class-audit` dan detektor tabrakan class (R18) ditunda ke B1.
11. Gerbang B0: test PHP/JS sama dengan baseline, sidik jari style identik dengan baseline di semua halaman, `npm run build` bersih.

**Baseline sebelum B0** (PHP 8.5.10): PHP 223 lulus / 5 gagal / 8 dilewati (`TransferConcurrencyTest` ×4 karena tabel `users` tidak ada di sqlite; `UserStaffLinkTest` role index). JS 180 lulus / 1 gagal dari 181 (Order Book detail dialog). Scan 105 route: 87 sehat, 18 error 500 yang **sudah ada sebelumnya** (12× `View [create] not found` karena halaman create pindah ke overlay; 6× skema database `guest` tertinggal: kolom `province` di `branch`/`supplier`, tabel `t_permohonan` dan `arus_kas_transactions`). Semuanya di luar scope migrasi.

**Temuan B0 yang mengubah rancangan** (semua terbukti lewat perbandingan dua server berdampingan):
- Config Tailwind di `template.root` tidak pernah berlaku (§2.2) → `legacy.css` memakai tema default + preflight.
- `@tailwindcss/forms` bentrok dengan `.form-select` Bootstrap → dilepas (Q18).
- CDN menyisipkan CSS di akhir `<head>` → CSS compiled dipasang di posisi yang sama.
- `package.json` tidak boleh memakai `"type": "module"`: `public/assets/js/*.js` memakai `module.exports` dan di-`require` oleh test JS, sehingga semuanya akan dianggap ES module. Karena itu file konfigurasi memakai ekstensi eksplisit (`vite.config.mjs`, `tailwind.config.mjs`, `tailwind.legacy.config.mjs`, `postcss.config.cjs`).
- Alpine tidak boleh dijalankan sinkron di `<head>` (butuh `<body>`); jQuery/Swal harus klasik sinkron → dua jenis bundle (§3.4).
- Urutan aturan utility di CSS compiled berbeda dari CDN, sehingga 5 elemen dengan class bertabrakan berubah tampilannya. Diperbaiki di markup (`admin/user/index` chip modul, `order-book/index` tombol bulan/tahun, pemilih cabang di `products/index`, `wholesale/index`, `crm dashboard/index`) dengan hasil sama seperti baseline. Lihat R18.
- Perbedaan yang tersisa pada pemindaian adalah soal waktu (select2, `data-erp-ready`, transisi Alpine yang belum selesai pada saat dipotret), bukan perbedaan CSS; dikonfirmasi dengan pemindaian ulang berjeda 4,5 detik.

Ukuran: M. Risiko utama: R1, R9.

### B1 — Layout final, komponen, alat, penjaga

Dibagi dua karena ukurannya. **B1a selesai** (PR terpisah); **B1b** menyusul.

**B1a (selesai):**
1. `layouts/erp-tailwind` diganti nama menjadi `layouts/erp` dan 20 view diarahkan ke sana. (Layout `guest` tidak dibuat: login, landing, dan errors sudah berdiri sendiri dan bersih; tidak ada duplikasi yang perlu disatukan.)
2. Komponen §5: `dialog`, `drawer`, `dropdown`, `tabs` + `tab-panel`, `collapse`, `check-all`, `tooltip`, `panel`, `badge`, `avatar`, `cell-title`, `row-actions`, plus `erp.toast()`. 19 test render Blade, 14 test JS (termasuk regresi bug check-all yang ditemukan lewat uji di browser), diverifikasi di browser (dialog, fokus kembali ke pemicu, klik latar, `static`, Esc, drawer, sheet, tab + panah, collapse, check-all + indeterminate, dropdown teleport + panah + Esc + klik luar).
3. Class komponen §5.4 (`erp-input/select/textarea/check/radio/label/help/error/input-group/badge/avatar/panel/table/menu/tabs/tooltip/dialog`) di `resources/css/components/`. `erp-action` tetap di `erp-workspace.css`; peleburan `erp-button` ditunda ke B1b.
4. `legacy-bridge.js` (shim `bootstrap.Modal` dan `$.fn.modal` di atas dialog; aman untuk elemen non-`<dialog>`).
5. `scripts/ui-migrate/codemod.mjs` (class + ikon; laporan dulu, `--write`, `--convert-rem`, `--strict`) dengan 15 test, dan `class-conflicts.mjs` (R18) dengan test seluruh repo. Detektor menemukan 11 tabrakan state aktif/tidak aktif yang tidak terlihat oleh pemindaian visual; semuanya diperbaiki.
6. Penjaga `NoLegacyUiMarkupTest` + `tests/fixtures/legacy-ui-allowlist.json` (217 file, 6.400 hit) + `ratchet-snapshot.php`. Terbukti gagal bila ada markup lama baru.
7. Dark mode dibuang (baris Tema di Pengaturan dan skrip tema di `template.root`).
8. Aturan Q19 di `AGENTS.md` dan `docs/ui-design-system.md` (komponen, aturan, alat).

**B1b-1 (selesai): DataTables dari npm, bundle Metronic keluar dari `layouts.erp`.** `layouts.erp` tidak lagi memuat satu pun aset Metronic.
- Survei runtime: di 20 halaman layout ERP hanya **2 tabel DataTables** (`products-table`, `wholesale-table`), keduanya inti saja (server-side, tanpa FixedColumns/Responsive/Select/Buttons). Ekstensi, JSZip, dan pdfmake di bundle 2,4 MB tidak terpakai di sana.
- `datatables.net@1.13.7` (versi yang sama persis dengan bundle) masuk ke `vendor.js` (254 KB, sebelumnya 165 KB + 2,4 MB).
- **Bundle itu ternyata juga membawa integrasi Bootstrap** (wrapper `dt-bootstrap4`, `form-select-solid`, paginasi `ul.pagination > li.page-item`, `dom` bawaan, bawaan bahasa Metronic). Tanpa itu markup tabel berubah. Ditulis ulang agar terbaca di `resources/js/vendor/datatables-bootstrap.js` (dicatat sebagai utang di allowlist penjaga, dihapus di B6).
- Skin inti diekstrak tanpa perubahan nilai ke `resources/css/datatables.css` (12,5 KB dari 36,5 KB; ekstensi dan dark mode dibuang), dimuat di posisi yang sama dengan bundle lama karena adapter `erp-*.css` mengandalkan urutan itu.
- Verifikasi: tabel sintetis 30 baris di konteks halaman asli, enam keadaan (awal, halaman 2, urut, cari, kosong, 25 per halaman) **identik byte demi byte** antara sebelum dan sesudah (markup, class, style, ukuran, teks). Pemindaian visual 20 halaman × 2 lebar: satu-satunya selisih adalah `opacity` animasi masuk (artefak waktu).
- **Tidak teruji:** baris data nyata dari server. Basis data guest tidak punya produk, dan akun yang diberikan ditolak di database lokal. Jalur ajax server-side memakai kode inti yang sama; tetap perlu dicoba sekilas di lingkungan dengan data.
- Ekstensi baru (fixedColumns, responsive, select) ditambahkan bersama halaman yang membutuhkannya (B2 sampai B5), JS dan CSS-nya sekaligus.

**B1b-2 (selesai): `body` 13px.** `body.erp-app` (hanya `layouts.erp`; login juga `erp-ui` dan landing tetap 16px) memakai `text-body` = 13px/1.5 di atas root 16px.
- Diukur dengan pemindaian 20 halaman × 2 lebar sebelum dan sesudah. **3.220 elemen mewarisi ukuran 16px → 13px, tetapi hanya 5 yang berisi teks langsung**: halaman-halaman ini hampir semuanya sudah memakai class ukuran eksplisit, jadi dampak visualnya kecil. Tinggi dokumen tidak berubah di halaman mana pun; tidak ada selisih struktur.
- Yang benar-benar terdampak dan dikalibrasi dengan `text-base` (16px, sama seperti sebelumnya): judul "Notifikasi" di topbar; ikon tanpa ukuran eksplisit (kaca pembesar di kolom cari pada `admin/role`, `admin/user`, `receipt`, `unit`, `partials/mobile-index`, `products`; panah/titik tiga di dashboard CRM; panah di form pelanggan); trigger `x-erp.row-actions`.
- Input/select/textarea tetap 16px (aturan anti-zoom iOS di `layouts.erp`), jadi tidak ikut berubah.
- Yang ikut menjadi 13px dan memang wajar: teks bawaan DataTables (`Showing no records`, `Loading...`, `Memuat…`). Tampilannya kini sama dengan di halaman lama (root 13px).
- Pelajaran untuk B2 sampai B5: halaman `template.root` yang dipindah ke `layouts.erp` mendapat base 13px (sama dengan root lama), tetapi ukuran rem-nya dihitung dari 16px, jadi tetap pakai `codemod --convert-rem` (§4.1). Ikon tanpa ukuran yang berada langsung di body/wadah tanpa ukuran perlu `text-base`.

**B1b-3 (selesai): kode mati golongan A dihapus** (disetujui pemilik repo; satu commit per grup, daftar bukti: nol referensi di `app`, `Modules`, `routes`, `resources`, `config`).
- `resources/views/template - Copy/` (27 file); `template/{aside*,header,footer,drawer,modals,toolbar}` (hanya `root` dan `notif` tersisa); `auth/{passwords/*,verify,footer,login-option,register}`; `layouts/app.blade.php` + `public/css/app.css`, `public/js/{app,jquery.min}.js`, `public/jquery.js`, `public/mix-manifest.json`; `admin-view`, `pemohon-view`, `user`, `produk`; file kosong `segment(2)` dan `your`.
- Livewire dibuang lebih awal dari jatah B2: `app/Livewire/*`, `resources/views/livewire/*`, `DashboardController::produk()`, blok `@livewireStyles` di `template.root`, dan paket `livewire/livewire` dari `composer.json`. `composer.lock` di-gitignore, jadi server cukup `composer install` / `composer update` setelah deploy; cache `bootstrap/cache/{packages,services}.php` dan `php artisan view:clear` perlu dijalankan di server.
- Allowlist ratchet turun dari 218 file / 5.623 temuan menjadi 192 file. Test: sama dengan `main` (4 error + 1 failure bawaan).
- Masih menunggu konfirmasi pemilik repo (golongan B): `public/{mask,font,error,assets-landing}`, `public/template.xlsx`, `Modules/*/Resources/views/{layouts/master,index}`, controller `Auth/{Confirm,Forgot,Reset}Password` + `VerificationController` (tanpa route).

**B1b-4 (selesai): `erp-button` dilebur ke `erp-action`.** 16 pemakaian di 5 view (`errors/layout`, `products/create`, `pos/payment-success2`, `pos/receipt2`, `stock-opname/exports/preview`) diganti; `.erp-ui .erp-button*` dan `.erp-link-button` (tanpa pemakai) dihapus. Aturan `.erp-action*` dipindah dari `erp-workspace.css` ke `erp-theme.css` karena halaman mandiri (error, struk) hanya memuat berkas itu; urutan aturan sama.
- Diverifikasi di browser: 20 elemen `.erp-action` di 9 halaman identik sebelum/sesudah (semua properti gaya + ukuran kotak).
- Keputusan pemilik repo: radius semua tombol memakai token `rounded-control` (12px), bukan pil. `.erp-action` dari `999px` menjadi `var(--erp-radius-control)`. Diverifikasi di 10 halaman (22 tombol): hanya radius yang berubah (15 tombol pil → 12px), ukuran kotak tidak ada yang bergeser. Tombol bekas `erp-button` juga ikut 13px (sebelumnya 14px). Kolom cari toolbar masih berbentuk pil; disamakan saat halamannya dimigrasi.
- Dijaga `TailwindBuildContractTest::test_erp_action_is_the_only_button_family`.

**B1b (menunggu):**
1. Mengisi detail-dialog/dropdown-select lama dengan komponen baru bila perlu.

Ukuran: L. Semua batch berikutnya bergantung pada batch ini.

### B2 — Master (42 view)

- products (`index` sudah Tailwind; `create`; `stock-show` 108 hit; `edit` kandidat mati 215 hit + Quill + `KTImageInput`)
- customer (+ overlay; `membership` kandidat mati), staff (+ overlay), supplier, branch, position, unit, location, department, handling, kurir, category, payment method, account
- `partials/mobile-index` (dikunci 6 test)
- Livewire `produk`, `produk-form`, `product-table`: putuskan nasib Livewire di awal batch (§3.5)
- Backend: lihat Lampiran B (B2). Ukur performa render komponen per baris (R16).

**B2a (selesai): location, handling, department.**
- Tiga view sama bentuknya, jadi dibuat satu partial `master::partials.simple-crud` (DataTables server-side + `x-erp.dialog` sheet); ketiga `index.blade.php` tinggal parameter. Pindah dari `template.root` ke `layouts.erp`.
- Backend: `get_data` tiga controller memakai `App\Support\DataTableCells` (`title`, `rowActions`, `editDeleteMenu`) yang merender komponen `x-erp.*` (di-escape). Kolom nama sebelumnya menyisipkan `$item->name` mentah: celah XSS pada tiga tabel ini tertutup sebagai efek samping. Izin tidak berubah (`department` memakai `category.edit/delete` seperti sebelumnya).
- Temuan platform yang diperbaiki di sini, berlaku untuk semua halaman berikutnya:
  1. **SweetAlert di belakang `<dialog>` modal**: `<dialog>` membuat semua elemen di luarnya inert, jadi konfirmasi/error SweetAlert yang ditempel di `<body>` tidak terlihat dan tidak bisa diklik. `resources/js/vendor.js` kini merender SweetAlert di dalam dialog paling atas dengan `topLayer: true` bila ada dialog terbuka (diuji dengan klik sungguhan).
  2. **Daftar kartu mobile (`erp-collections.js`)** mencari tombol aksi di dalam baris, padahal panel `x-erp.dropdown` di-teleport ke `<body>`. `x-erp.row-actions` punya prop `copies` yang menambah salinan tersembunyi (`data-erp-action-copies`); `DataTableCells` selalu menyalakannya. Script mengabaikan pemicu menu dan sel `data-erp-row-actions` dari daftar field.
  3. **Tombol + di navigasi mobile** membuka `#kt_modal_add_customer` lewat Bootstrap. Kini ia membuka `dialog[data-erp-create]` lebih dulu (id lama tetap didukung selama halaman lain belum pindah).
  4. CSS `erp-input`/`erp-label` baru masuk bundel setelah ada view yang memakainya; jalankan `npm run build` setelah menambah view pertama yang memakai komponen form.
- Perubahan tampilan yang disengaja: font/warna dasar mengikuti `layouts.erp` (Plus Jakarta Sans 13px) menggantikan font sistem 12px halaman `template.root`; konten bergeser sesuai padding shell baru (4px di mobile, ±19/27px di desktop); tabel memakai `erp-table`; isi dialog putih (sebelumnya abu-abu muda). `h2` judul workspace diberi `line-height:1.2` agar tinggi tidak berubah.
- Judul dialog setelah simpan sebelumnya salah ("Tambah Category"/"Tambah Penanganan"); kini selalu "Tambah <judul>". Menutup dialog (Esc, X, Batal) mengembalikan form ke mode tambah (sebelumnya action `PUT` tetap menempel setelah batal ubah).
- Diuji di browser (guest, baris contoh disuntik ke DataTables): kartu mobile + aksi Ubah/Hapus, menu titik tiga desktop, buka/tutup/reset dialog, Batal dengan konfirmasi (klik sungguhan), alur simpan (spinner, sukses, error) dengan `$.ajax` di-stub, tombol + mobile. Server-side data sungguhan belum diuji (DB guest tanpa data).

**B2b-1 (selesai): `products/show-transaction`, `products/stock`, `staff/show`, `customer/show`** pindah ke `layouts.erp` (codemod `--convert-rem`; tabel riwayat HPP memakai `erp-table`). `staff/show` dan `customer/show` tidak diuji di browser karena DB guest tanpa data; hanya `@extends` dan `page-title` yang berubah, class `erp-person-header`/`erp-detail-grid` ada di `erp-workspace.css` yang dimuat kedua layout. Catatan: codemod menggeser skala `gray` Metronic; di halaman `template.root` yang diukur (`/campaign`) `text-gray-400/500/600` memang dirender sebagai warna Metronic (`#C4CADA`, `#99A1B7`, `#78829D`), jadi pergeseran itu menjaga tampilan.

**B2b-3 (selesai): token merek di view Master.** `bg-[#0b595b]`/`text-[#0b595b]` menjadi `bg-teal`/`text-teal`, `hover:bg-[#0a4e50]` menjadi `hover:bg-teal-hover` (token `#08484a`; hover sedikit lebih gelap dari `#0a4e50` lama) di 7 view. **Jangan jalankan `codemod --write` pada view yang sudah di `layouts.erp`**: pergeseran skala `gray` hanya benar untuk halaman `template.root` (Metronic); di halaman Tailwind-native ia mengubah warna. Sapuan hex dilakukan dengan regex terarah.

**B2b-2 (selesai): select2 keluar dari halaman produk, `stock-show` pindah ke `layouts.erp`.**
- `products/create-script`: baris varian dibangun oleh `buildVariantRow()` (DOM, bukan string HTML: nama produk dari server tidak lagi disisipkan sebagai HTML) dengan `<select class="erp-select">` + `ErpPicker.attach`. Kode `addBranch`/`product_branch` dihapus: tabel cabang lama tidak ada di form (harga cabang sudah Alpine `productBranchPricing`). Markup blok halaman `show` diganti kelas `erp-*`.
- `ErpPicker` mendapat opsi `creatable` (+ `creatableLabel`, helper murni `withCreatable`): pengganti `tags: true` select2, yaitu mengetik nama yang belum ada menambah baris "Produk baru" dengan id = teks. Dites di `tests/js/erp-picker.test.cjs`.
- **Overlay picker kini dirender di dalam `<dialog>` terbuka** (`select.closest('dialog[open], .modal')`), alasan sama dengan SweetAlert (modal native membuat elemen luar inert).
- `products/index`: select2 dan CSS select2 CDN dibuang, `data-kt-ecommerce-product-filter` menjadi `data-erp-filter`, tombol SweetAlert memakai `erp-action`. `create-form`/`create` kehilangan CSS select2 dan `.form-control/.form-select` yang tak terpakai.
- `products/stock-show`: kartu Metronic (`card`, `card-header`, `card-toolbar`, `card-body`) diganti kelas halaman (`stock-detail-card-*`), input `erp-input`, `<select>` filter menjadi `erp-select` biasa (select2 tidak pernah ada di `layouts.erp`), utilitas Bootstrap dibuang. Halaman penuh di mobile lewat `-mx-4 md:mx-0` (aturan `width:100%` mobile dihapus).
- **Flatpickr dari npm** (`flatpickr@4.6.13`, versi yang sama dengan bundel Metronic) di `vendor.js`, termasuk plugin jQuery `$.fn.flatpickr` yang dipasang eksplisit (import di-hoist sehingga deteksi `jQuery` otomatis flatpickr tidak jalan). CSS dasar: `@vite('resources/css/flatpickr.css')` lewat `@push('styles')` di halaman pemakainya.
- Diuji di browser dengan rute debug sementara (sudah dihapus, tidak dikomit) dan baris contoh yang disuntik: daftar riwayat, bottom sheet filter, select native, kalender Flatpickr, preset tanggal; form produk: baris varian, picker, baris "Produk baru", tambah/hapus baris; `/products` DataTables + filter. Ajax server-side sungguhan belum diuji (DB guest tanpa produk).

### B3 — Report, CRM, Chat, admin/settings/user (52 view)

- Report hub + semua laporan (`ReportController` 57 class; `report-mobile.js` 703 baris)
- CRM: dashboard + ApexCharts, tier + `x-erp.image-input`, deposito, campaign, report customer (`data-kt-check`), setting-exp (kandidat mati)
- Chat (kecuali `srrd` dompdf)
- `admin/role`, `admin/user`, `user`, `menu`, `role-menu`, `settings`, `list_permohonan`, `qrcode`

### B4 — Transaction (74 view)

**Prasyarat**: `ui/produksi-reference` dan `ui/stock-opname-reference` sudah di `main`; hitung ulang inventaris.

- Terberat: `stock-opname/index` (422 hit BS + 130 KT, `KTDrawer`, `KT*`, select2, 3 test kontrak), `production/process-parcel` (173, `data-kt-ecommerce`), `wholesale/create|show|edit` (132/106/110), `pengadaan/index2`, `stock-out`, `stock-out-type`, `receipt`, `transfer`, `sortir`
- Ganti `toastr` di `production/js-create`
- Kandidat mati terbanyak (22): versi lama `pengadaan/*`, `transfer/index|show|segment/*`, `sortir/index`, `wholesale/edit`, `StockOpnameController.php.temp`, `ProductionController_temp.php`

### B5 — POS (50 view)

- `pos/create2` + `segment/*` + `js/*`; `pos/payment` + `js-payment` + `payment-success2`
- `delivery-order/index` (97 hit BS + 39 KT; `delivery-order-mobile.js` 727 baris; 3 feature test + 3 test JS)
- `order-book/*`, `expenditure`, `otherbook`, `arus-kas`, `setting-nota` (UI pengaturannya; tampilan nota dikecualikan)
- `pos/receipt*`/`print*` hanya dicek bebas Bootstrap (Q15)
- Kandidat mati: `pos/index` (BS5 CDN), `pos/create`, `pos/payment-success`, `pos/print`, `PosController.php.temp`
- Gerbang tambahan: `PosPaymentIdempotencyTest`, `PosPaymentUiContractTest`, `pos-payment-attempt.test.cjs`, `DeliveryOrderMobilePaymentTest` tidak berubah maknanya. Smoke test uang dua kali.

### B6 — Cleanup

Hapus semua item §7.2 bertanda B6. Gerbang: grep penanda §8.3 = 0 di seluruh repo; allowlist kosong; `docs/ui-design-system.md` tanpa bagian "jembatan Bootstrap"; ukuran `public/` turun ±75 MB (tergantung audit media dan `infruity-ui`).

---

## 11. Rollback

- Setiap batch satu PR yang bisa di-revert tanpa memengaruhi batch sebelumnya.
- Aset Metronic baru dihapus di B6, jadi revert B1–B5 mengembalikan halaman ke `template.root` yang masih berfungsi.
- B0 bisa di-revert utuh: CDN dan `package.json` lama kembali.

## 12. Yang dicek di awal B0

1. Baseline test.
2. Node di server production (ada/tidak, versi, siapa yang deploy).
3. Tombol export DataTables (JSZip/pdfmake) yang terlewat grep.
4. ~~`/register`~~ **Selesai dicek:** sengaja dinonaktifkan. `showRegister()` me-redirect ke login dengan pesan "Pendaftaran mandiri tidak tersedia" dan `create()` selalu 403. Referensi `infruity-frontend` (47 halaman HTML) tidak punya halaman daftar, dan halaman login tidak menautkannya. Tidak ada UI yang perlu dibuat; route dibiarkan. `auth/register.blade.php`, `auth/footer`, `auth/login-option` dihapus sebagai kode mati. Konfirmasi akhir ke server oleh pemilik repo.
5. ~~`produk`/`produk-form` (Livewire)~~ **Selesai dicek:** mati. Route `/produk` sudah dikomentari di `routes/web.php`, `DashboardController::produk()` tidak punya route, referensi FE tidak punya halaman setara (Master/Produk di FE adalah modul `products.*`, bukan halaman Livewire ini), dan tidak ada izin/menu bernama `produk` di tabel `role_menu`. Livewire `PosKasir` dan `ProductTable` juga tidak dirujuk di mana pun. Keputusan: seluruh Livewire (`app/Livewire/*`, `views/livewire/*`, `produk.blade.php`, `@livewireStyles`) dihapus di B2, dan §3.5 memakai jalur "Livewire mati".
6. `public/infruity-ui/`, `public/mask/`, `public/error/`, `public/font/`, `public/assets-landing/`: masih dipakai?

---

## Lampiran A — Checklist per view

Legenda: `[ ]` belum, `[x]` selesai, `[-]` dikecualikan. Layout: `root` = `template.root`, `tw` = `layouts.erp-tailwind`, `partial` = include/komponen/standalone. BS/KT = indikator kepadatan (§2.3). "⚠ kandidat mati" = 0 referensi statis di `app/`, `routes/`, `Modules/` dan `resources/views`; wajib dikonfirmasi sebelum dihapus (R15). `template - Copy/` tidak didaftar (dihapus di B1).

### B1 — Shell, auth, errors, landing, dashboard

50 view · 3714 hit class Bootstrap/Metronic · 1390 hit KT.

| ✓ | View | Layout | BS | KT | Plugin | Test pengunci | Catatan |
|---|---|---|---:|---:|---|---|---|
| [ ] | `app/auth/footer.blade.php` | partial | 47 | 34 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/auth/login-option.blade.php` | partial | 16 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/auth/login.blade.php` | partial | 34 | 0 | - | - |  |
| [ ] | `app/auth/passwords/confirm.blade.php` | app(BS4) | 14 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/auth/passwords/email.blade.php` | app(BS4) | 14 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/auth/passwords/reset.blade.php` | app(BS4) | 24 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/auth/register.blade.php` | errors | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/auth/verify.blade.php` | app(BS4) | 8 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/change-password.blade.php` | root | 1 | 0 | - | - |  |
| [ ] | `app/components/erp/collection.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `app/components/erp/detail-dialog.blade.php` | partial | 6 | 0 | - | - |  |
| [ ] | `app/components/erp/dropdown-select.blade.php` | partial | 8 | 0 | - | - |  |
| [ ] | `app/components/erp/load-more.blade.php` | partial | 1 | 0 | - | - |  |
| [ ] | `app/components/erp/mobile-navbar.blade.php` | partial | 2 | 2 | bs-js | - |  |
| [ ] | `app/components/erp/mobile-search.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `app/components/erp/sidebar.blade.php` | partial | 22 | 0 | - | InventorySidebarContract |  |
| [ ] | `app/components/erp/topbar.blade.php` | partial | 35 | 0 | - | - |  |
| [ ] | `app/components/erp/workspace.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `app/dashboard.blade.php` | tw | 81 | 0 | - | - |  |
| [ ] | `app/errors/401.blade.php` | errors | 0 | 0 | - | - | dipanggil implisit oleh exception handler |
| [ ] | `app/errors/403.blade.php` | errors | 0 | 0 | - | - | dipanggil implisit oleh exception handler |
| [ ] | `app/errors/404.blade.php` | errors | 0 | 0 | - | - | dipanggil implisit oleh exception handler |
| [ ] | `app/errors/4041.blade.php` | errors.404 | 0 | 0 | - | - | dipanggil implisit oleh exception handler |
| [ ] | `app/errors/419.blade.php` | errors | 0 | 0 | - | - | dipanggil implisit oleh exception handler |
| [ ] | `app/errors/429.blade.php` | errors | 0 | 0 | - | - | dipanggil implisit oleh exception handler |
| [ ] | `app/errors/500.blade.php` | errors | 0 | 0 | - | - | dipanggil implisit oleh exception handler |
| [ ] | `app/errors/503.blade.php` | errors | 0 | 0 | - | - | dipanggil implisit oleh exception handler |
| [ ] | `app/errors/layout.blade.php` | partial | 1 | 0 | - | - | dipanggil implisit oleh exception handler |
| [ ] | `app/forgot-password.blade.php` | errors | 0 | 0 | - | - |  |
| [ ] | `app/landing.blade.php` | partial | 795 | 1 | - | - |  |
| [ ] | `app/layouts/app.blade.php` | partial | 15 | 0 | - | - |  |
| [ ] | `app/layouts/erp-tailwind.blade.php` | partial | 9 | 0 | DT | erp-picker(js) |  |
| [ ] | `app/partials/erp-chrome.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `app/template/aside.blade.php` | partial | 18 | 29 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/template/aside/menu/apps.blade.php` | partial | 56 | 315 | - | - |  |
| [ ] | `app/template/aside/menu/dashboard.blade.php` | partial | 13 | 65 | - | - |  |
| [ ] | `app/template/aside/menu/helps.blade.php` | partial | 4 | 12 | - | - |  |
| [ ] | `app/template/aside/menu/menus.blade.php` | partial | 42 | 171 | - | - |  |
| [ ] | `app/template/aside/menu/pages.blade.php` | partial | 62 | 456 | - | - |  |
| [ ] | `app/template/aside/search.blade.php` | partial | 485 | 34 | select2 | - |  |
| [ ] | `app/template/aside/user.blade.php` | partial | 67 | 17 | - | - |  |
| [ ] | `app/template/aside/user/language.blade.php` | partial | 37 | 18 | - | - |  |
| [ ] | `app/template/aside/user/subscribetion.blade.php` | partial | 28 | 17 | - | - |  |
| [ ] | `app/template/drawer.blade.php` | partial | 809 | 102 | - | - |  |
| [ ] | `app/template/footer.blade.php` | partial | 13 | 7 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/template/header.blade.php` | partial | 12 | 7 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/template/modals.blade.php` | partial | 824 | 67 | select2 | - |  |
| [ ] | `app/template/notif.blade.php` | partial | 3 | 0 | - | - |  |
| [ ] | `app/template/root.blade.php` | partial | 11 | 6 | DT | OrderBookFormUiContract, PosFormUiContract, ProductionFormUiContract, erp-picker(js) |  |
| [ ] | `app/template/toolbar.blade.php` | partial | 97 | 30 | select2 | - |  |

### B2 — Master (+ Livewire produk)

42 view · 1341 hit class Bootstrap/Metronic · 176 hit KT.

| ✓ | View | Layout | BS | KT | Plugin | Test pengunci | Catatan |
|---|---|---|---:|---:|---|---|---|
| [ ] | `Master/account/index.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Master/branch/index.blade.php` | partial | 0 | 0 | - | BranchOverlayFields |  |
| [ ] | `Master/customer/create-overlay.blade.php` | partial | 15 | 0 | - | CustomerEditOverlay |  |
| [ ] | `Master/customer/create.blade.php` | tw | 10 | 0 | - | CustomerEditOverlay |  |
| [ ] | `Master/customer/edit-overlay.blade.php` | partial | 16 | 0 | - | CustomerEditOverlay |  |
| [ ] | `Master/customer/form-fields.blade.php` | partial | 60 | 0 | - | CustomerEditOverlay |  |
| [ ] | `Master/customer/index.blade.php` | partial | 0 | 0 | - | CustomerEditOverlay |  |
| [ ] | `Master/customer/js-create.blade.php` | partial | 12 | 0 | - | CustomerEditOverlay |  |
| [ ] | `Master/customer/membership.blade.php` | partial | 56 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [x] | `Master/customer/show.blade.php` | root | 0 | 0 | - | - |  |
| [x] | `Master/department/index.blade.php` | root | 42 | 27 | DT, Swal, bs-js | - |  |
| [x] | `Master/handling/index.blade.php` | root | 42 | 27 | DT, Swal, bs-js | - |  |
| [ ] | `Master/index.blade.php` | master::layouts.master | 0 | 0 | - | - |  |
| [ ] | `Master/kurir/index.blade.php` | partial | 3 | 0 | - | - |  |
| [ ] | `Master/layouts/master.blade.php` | partial | 0 | 0 | - | - |  |
| [x] | `Master/location/index.blade.php` | root | 49 | 27 | DT, Swal, bs-js | - |  |
| [ ] | `Master/partials/mobile-index.blade.php` | tw | 139 | 0 | Swal | BranchOverlayFields, CustomerEditOverlay, PositionDrawerSpacing, SupplierDetailUi, SupplierRegionFields, kurir-drawer(js), staff-edit-overlay(js) |  |
| [ ] | `Master/payment-method/index.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Master/position/index.blade.php` | partial | 3 | 0 | - | PositionDrawerSpacing |  |
| [x] | `Master/products/create.blade.php` | tw | 2 | 0 | select2 | - |  |
| [ ] | `Master/products/edit.blade.php` | root | 215 | 24 | select2 | - | ⚠ kandidat mati (0 referensi) |
| [x] | `Master/products/index.blade.php` | tw | 146 | 11 | select2, DT, Swal | - |  |
| [x] | `Master/products/partials/create-form.blade.php` | partial | 77 | 2 | select2 | - |  |
| [x] | `Master/products/partials/create-script.blade.php` | partial | 52 | 14 | select2, DT, Swal | - |  |
| [x] | `Master/products/show-transaction.blade.php` | root | 21 | 0 | DT | - |  |
| [x] | `Master/products/stock-show.blade.php` | root | 108 | 26 | select2, DT, flatpickr | - |  |
| [x] | `Master/products/stock.blade.php` | root | 19 | 2 | - | - |  |
| [ ] | `Master/staff/create-overlay.blade.php` | partial | 12 | 0 | - | UserStaffLink |  |
| [ ] | `Master/staff/create.blade.php` | tw | 6 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Master/staff/edit-form.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Master/staff/edit-overlay.blade.php` | partial | 17 | 0 | - | staff-edit-overlay(js) |  |
| [ ] | `Master/staff/form-fields.blade.php` | partial | 59 | 0 | - | - |  |
| [ ] | `Master/staff/index.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Master/staff/js-create.blade.php` | partial | 0 | 0 | - | staff-edit-overlay(js) |  |
| [ ] | `Master/staff/js-edit.blade.php` | partial | 0 | 0 | - | staff-edit-overlay(js) |  |
| [x] | `Master/staff/show.blade.php` | root | 0 | 0 | - | - |  |
| [ ] | `Master/supllier/index.blade.php` | partial | 0 | 0 | - | SupplierRegionFields |  |
| [ ] | `Master/unit/index.blade.php` | tw | 70 | 0 | Swal | - |  |
| [ ] | `app/livewire/pos-kasir.blade.php` | partial | 6 | 0 | - | - |  |
| [ ] | `app/livewire/product-table.blade.php` | partial | 84 | 16 | select2, DT | - |  |
| [ ] | `app/livewire/produk-form.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `app/produk.blade.php` | root | 0 | 0 | - | - |  |

### B3 — Report, CRM, Chat, admin/settings/user/menu

52 view · 929 hit class Bootstrap/Metronic · 143 hit KT.

| ✓ | View | Layout | BS | KT | Plugin | Test pengunci | Catatan |
|---|---|---|---:|---:|---|---|---|
| [ ] | `Chat/index.blade.php` | root | 0 | 0 | - | - |  |
| [ ] | `Chat/layouts/master.blade.php` | partial | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [-] | `Chat/srrd.blade.php` | partial | 0 | 0 | - | - | print/pdf: dikecualikan, hanya cek bebas Bootstrap |
| [ ] | `Crm/campaign/index.blade.php` | root | 11 | 9 | select2 | - |  |
| [ ] | `Crm/campaign/js-campaign.blade.php` | partial | 14 | 12 | DT, Swal, flatpickr, bs-js | - |  |
| [ ] | `Crm/dashboard/index.blade.php` | tw | 116 | 0 | - | - |  |
| [ ] | `Crm/dashboard/js-dashboard.blade.php` | partial | 0 | 28 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Crm/dashboard/top_distribution.blade.php` | partial | 26 | 0 | - | - |  |
| [ ] | `Crm/dashboard/top_tier.blade.php` | partial | 26 | 0 | - | - |  |
| [ ] | `Crm/deposito/customer-deposito.blade.php` | root | 0 | 1 | DT | - |  |
| [ ] | `Crm/deposito/index.blade.php` | root | 10 | 9 | - | - |  |
| [ ] | `Crm/deposito/js-deposito.blade.php` | partial | 16 | 16 | select2, DT, Swal, flatpickr, bs-js | - |  |
| [ ] | `Crm/deposito/show.blade.php` | root | 6 | 1 | DT | - |  |
| [ ] | `Crm/index.blade.php` | crm::layouts.master | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Crm/layouts/master.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Crm/partials/assets.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Crm/point-schedule/index.blade.php` | root | 6 | 3 | - | - |  |
| [ ] | `Crm/report/customer.blade.php` | root | 21 | 3 | DT | - |  |
| [ ] | `Crm/setting-exp/index.blade.php` | partial | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Crm/setting-exp/index2.blade.php` | root | 2 | 3 | select2 | - |  |
| [ ] | `Crm/tier/index.blade.php` | root | 31 | 13 | - | - |  |
| [ ] | `Crm/tier/js-tier.blade.php` | partial | 132 | 14 | select2, DT, Swal, flatpickr, bs-js | - |  |
| [ ] | `Report/branch-product-rep.blade.php` | root | 9 | 0 | DT, flatpickr | - |  |
| [ ] | `Report/branch-transaction-rep.blade.php` | root | 11 | 0 | DT, flatpickr | - |  |
| [ ] | `Report/customer-transaction-rep.blade.php` | root | 11 | 0 | DT, flatpickr | - |  |
| [ ] | `Report/customer-transaction.blade.php` | root | 12 | 0 | DT, flatpickr | - |  |
| [ ] | `Report/hub.blade.php` | tw | 0 | 0 | - | - |  |
| [ ] | `Report/index.blade.php` | root | 2 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Report/layouts/master.blade.php` | partial | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Report/partials/mobile-detail.blade.php` | partial | 42 | 0 | - | ReportMobilePaymentColor |  |
| [ ] | `Report/product-buang.blade.php` | root | 1 | 0 | - | - |  |
| [ ] | `Report/product-customer-transaction-rep.blade.php` | root | 16 | 0 | DT, flatpickr | - |  |
| [ ] | `Report/product-sales.blade.php` | root | 1 | 0 | - | - |  |
| [ ] | `Report/profit-adjusted.blade.php` | root | 1 | 0 | - | - |  |
| [ ] | `Report/profit-revenue.blade.php` | root | 1 | 0 | - | - |  |
| [ ] | `Report/shipping-cost.blade.php` | root | 1 | 0 | - | - |  |
| [ ] | `Report/total-aset.blade.php` | root | 1 | 0 | - | - |  |
| [ ] | `app/admin-view.blade.php` | partial | 16 | 0 | DT | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/admin/role/detail-fragment.blade.php` | partial | 1 | 0 | - | - |  |
| [ ] | `app/admin/role/edit.blade.php` | partial | 23 | 0 | - | - |  |
| [ ] | `app/admin/role/index.blade.php` | tw | 47 | 0 | - | - |  |
| [ ] | `app/admin/role/js-role.blade.php` | partial | 18 | 16 | select2, DT, Swal, flatpickr, bs-js | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/admin/role/show.blade.php` | partial | 21 | 0 | - | - |  |
| [ ] | `app/admin/user/index.blade.php` | tw | 163 | 0 | Swal | user-edit-drawer(js) |  |
| [ ] | `app/admin/user/js-user.blade.php` | partial | 14 | 13 | select2, DT, Swal, flatpickr, bs-js | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/list_permohonan.blade.php` | root | 9 | 0 | - | - |  |
| [ ] | `app/menu/index.blade.php` | root | 11 | 0 | - | - |  |
| [ ] | `app/pemohon-view.blade.php` | partial | 17 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `app/qrcode.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `app/role-menu.blade.php` | root | 2 | 0 | - | - |  |
| [ ] | `app/settings/index.blade.php` | root | 8 | 2 | - | - |  |
| [ ] | `app/user.blade.php` | root | 53 | 0 | Swal, bs-js | UserStaffLink, user-edit-drawer(js) |  |

### B4 — Transaction

74 view · 3134 hit class Bootstrap/Metronic · 432 hit KT.

| ✓ | View | Layout | BS | KT | Plugin | Test pengunci | Catatan |
|---|---|---|---:|---:|---|---|---|
| [ ] | `Transaction/index.blade.php` | transaction::layouts.master | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/layouts/master.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Transaction/pengadaan/create.blade.php` | root | 36 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/pengadaan/index.blade.php` | root | 33 | 4 | DT | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/pengadaan/index2.blade.php` | root | 61 | 20 | select2, DT, Swal, flatpickr | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/pengadaan/js-create.blade.php` | partial | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/pengadaan/js/js-allowed.blade.php` | partial | 0 | 0 | - | wholesale-buttons(js) |  |
| [ ] | `Transaction/pengadaan/js/js-header.blade.php` | partial | 2 | 0 | select2 | wholesale-buttons(js) |  |
| [ ] | `Transaction/pengadaan/js/js-product.blade.php` | partial | 18 | 0 | select2, Swal, bs-js | wholesale-buttons(js) |  |
| [ ] | `Transaction/pengadaan/segment/cart.blade.php` | partial | 41 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/pengadaan/segment/modal-product.blade.php` | partial | 70 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/pengadaan/segment/ringkasan.blade.php` | partial | 14 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/pengadaan/show.blade.php` | root | 86 | 13 | select2, Swal | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/production/create-parcel.blade.php` | root | 65 | 9 | select2, DT, flatpickr | - |  |
| [ ] | `Transaction/production/create.blade.php` | root | 36 | 0 | - | ProductionFormUiContract |  |
| [ ] | `Transaction/production/index.blade.php` | root | 21 | 14 | select2, DT, Swal, flatpickr | ProductionListUiContract |  |
| [ ] | `Transaction/production/js-create.blade.php` | partial | 68 | 3 | select2, Swal, bs-js | erp-picker(js) |  |
| [ ] | `Transaction/production/js-create2.blade.php` | partial | 32 | 13 | select2, flatpickr | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/production/js-process.blade.php` | partial | 32 | 20 | select2, DT, Swal, flatpickr, bs-js | - |  |
| [ ] | `Transaction/production/parcel.blade.php` | root | 8 | 3 | select2, DT, Swal | - |  |
| [ ] | `Transaction/production/process-parcel.blade.php` | root | 173 | 21 | - | - |  |
| [ ] | `Transaction/production/segment/ingredients-cart.blade.php` | partial | 1 | 0 | - | ProductionFormUiContract |  |
| [ ] | `Transaction/production/segment/modal-ingredient.blade.php` | partial | 136 | 0 | - | ProductionFormUiContract |  |
| [ ] | `Transaction/production/segment/modal-recipe.blade.php` | partial | 73 | 0 | - | ProductionFormUiContract |  |
| [ ] | `Transaction/production/segment/summary.blade.php` | partial | 3 | 0 | - | ProductionFormUiContract |  |
| [ ] | `Transaction/production/show.blade.php` | root | 9 | 0 | - | ProductionDetailUiContract |  |
| [ ] | `Transaction/receipt/create-form.blade.php` | partial | 104 | 0 | - | - |  |
| [ ] | `Transaction/receipt/create.blade.php` | tw | 55 | 0 | - | - |  |
| [ ] | `Transaction/receipt/index.blade.php` | tw | 150 | 0 | Swal | ReceiptRecipeEditUi |  |
| [ ] | `Transaction/receipt/partials/items.blade.php` | partial | 4 | 0 | - | - |  |
| [ ] | `Transaction/sortir/create.blade.php` | tw | 3 | 0 | - | - |  |
| [ ] | `Transaction/sortir/index.blade.php` | root | 33 | 4 | DT | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/sortir/index2.blade.php` | tw | 24 | 0 | - | SortirFilterUiContract |  |
| [ ] | `Transaction/sortir/js-create.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Transaction/sortir/js/js-allowed.blade.php` | partial | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/sortir/js/js-header.blade.php` | partial | 0 | 0 | select2 | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/sortir/js/js-product.blade.php` | partial | 0 | 0 | Swal | sortir-form(js) |  |
| [ ] | `Transaction/sortir/segment/cart.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Transaction/sortir/segment/modal-product.blade.php` | partial | 15 | 0 | - | - |  |
| [ ] | `Transaction/sortir/segment/ringkasan.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Transaction/sortir/segment/styles.blade.php` | partial | 31 | 3 | - | - |  |
| [ ] | `Transaction/sortir/show.blade.php` | tw | 4 | 0 | - | - |  |
| [-] | `Transaction/stock-opname/exports/excel.blade.php` | partial | 12 | 0 | - | - | print/pdf: dikecualikan, hanya cek bebas Bootstrap |
| [-] | `Transaction/stock-opname/exports/preview.blade.php` | partial | 16 | 0 | - | - | print/pdf: dikecualikan, hanya cek bebas Bootstrap |
| [ ] | `Transaction/stock-opname/index.blade.php` | root | 422 | 130 | select2, DT, Swal, flatpickr, bs-js | StockOpnameFormUiContract, StockOpnameHistoryUiContract, StockOpnameListUiContract, erp-picker(js) |  |
| [ ] | `Transaction/stock-out-type/index.blade.php` | root | 38 | 29 | select2, DT, Swal, flatpickr, bs-js | - |  |
| [ ] | `Transaction/stock-out/index.blade.php` | root | 62 | 37 | select2, DT, Swal, flatpickr, bs-js | - |  |
| [ ] | `Transaction/transfer/create.blade.php` | tw | 9 | 0 | - | - |  |
| [ ] | `Transaction/transfer/index.blade.php` | root | 33 | 4 | DT | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/transfer/index2.blade.php` | tw | 24 | 0 | - | TransferFilterUiContract, TransferReceiveFilterUiContract |  |
| [ ] | `Transaction/transfer/js-create.blade.php` | partial | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/transfer/js/js-allowed.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Transaction/transfer/js/js-header.blade.php` | partial | 0 | 0 | select2 | - |  |
| [ ] | `Transaction/transfer/js/js-product.blade.php` | partial | 27 | 0 | select2, Swal, bs-js | - |  |
| [ ] | `Transaction/transfer/segment/cart.blade.php` | partial | 32 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/transfer/segment/detail-dialog.blade.php` | partial | 2 | 0 | - | - |  |
| [ ] | `Transaction/transfer/segment/mobile-item-modal.blade.php` | partial | 14 | 0 | - | - |  |
| [ ] | `Transaction/transfer/segment/modal-correction.blade.php` | partial | 66 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/transfer/segment/modal-product.blade.php` | partial | 45 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/transfer/segment/ringkasan.blade.php` | partial | 16 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/transfer/show.blade.php` | root | 86 | 13 | select2, Swal | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/wholesale/create.blade.php` | root | 132 | 13 | select2 | - |  |
| [ ] | `Transaction/wholesale/create2.blade.php` | tw | 114 | 0 | select2 | InventorySidebarContract, WholesaleEditBranchArrow, WholesaleEditSummaryLayer |  |
| [ ] | `Transaction/wholesale/edit.blade.php` | root | 110 | 13 | select2 | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/wholesale/index.blade.php` | tw | 125 | 0 | DT, Swal | InventorySidebarContract, WholesaleMobileFilterUiContract |  |
| [ ] | `Transaction/wholesale/js-create.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Transaction/wholesale/js-edit.blade.php` | partial | 26 | 19 | select2, DT, Swal, flatpickr, bs-js | - |  |
| [ ] | `Transaction/wholesale/js-show.blade.php` | partial | 22 | 16 | select2, DT, flatpickr, bs-js | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Transaction/wholesale/process.blade.php` | root | 38 | 13 | select2, DT, Swal | - |  |
| [ ] | `Transaction/wholesale/receive_product.blade.php` | root | 48 | 7 | - | - |  |
| [ ] | `Transaction/wholesale/segment/cart.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Transaction/wholesale/segment/modal-product.blade.php` | partial | 56 | 0 | - | - |  |
| [ ] | `Transaction/wholesale/segment/ringkasan.blade.php` | partial | 12 | 0 | - | - |  |
| [ ] | `Transaction/wholesale/show.blade.php` | root | 106 | 11 | select2 | - |  |

### B5 — POS

50 view · 1687 hit class Bootstrap/Metronic · 120 hit KT.

| ✓ | View | Layout | BS | KT | Plugin | Test pengunci | Catatan |
|---|---|---|---:|---:|---|---|---|
| [ ] | `Pos/arus-kas/index.blade.php` | root | 249 | 4 | - | ArusKasUiContract, arus-kas-view(js) |  |
| [ ] | `Pos/delivery-order/index.blade.php` | root | 97 | 39 | select2, DT, Swal, flatpickr, bs-js | DeliveryOrderList, DeliveryOrderMobilePayment, DeliveryOrderToolbarSticky, delivery-order-payment(js), delivery-order-reference-dates(js) |  |
| [ ] | `Pos/delivery-order/list-kurir.blade.php` | partial | 13 | 0 | - | - |  |
| [ ] | `Pos/delivery-order/partials/reference-dates.blade.php` | partial | 1 | 0 | - | delivery-order-reference-dates(js) |  |
| [ ] | `Pos/expenditure/create.blade.php` | root | 33 | 0 | - | - |  |
| [ ] | `Pos/expenditure/index.blade.php` | root | 42 | 20 | select2, DT, Swal, flatpickr | - |  |
| [ ] | `Pos/expenditure/js-create.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Pos/expenditure/js/js-allowed.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Pos/expenditure/js/js-header.blade.php` | partial | 0 | 0 | select2 | - |  |
| [ ] | `Pos/expenditure/js/js-product.blade.php` | partial | 17 | 0 | select2, Swal, bs-js | - |  |
| [ ] | `Pos/expenditure/segment/cart.blade.php` | partial | 1 | 0 | - | - |  |
| [ ] | `Pos/expenditure/segment/modal-product.blade.php` | partial | 55 | 0 | - | - |  |
| [ ] | `Pos/expenditure/segment/ringkasan.blade.php` | partial | 35 | 0 | - | - |  |
| [ ] | `Pos/index.blade.php` | pos::layouts.master | 0 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Pos/layouts/master.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Pos/order-book/create.blade.php` | root | 28 | 0 | select2, Swal, bs-js | OrderBookFormUiContract, PosSheetsUiContract |  |
| [ ] | `Pos/order-book/index.blade.php` | root | 29 | 18 | DT, Swal, flatpickr | OrderBookReferenceUiContract, order-book-detail(js), order-book-filter-responsive(js), order-book-reference-ui(js) |  |
| [ ] | `Pos/order-book/order.blade.php` | root | 86 | 0 | - | PosSheetsUiContract |  |
| [ ] | `Pos/order-book/partials/detail.blade.php` | partial | 0 | 0 | - | order-book-detail(js) |  |
| [ ] | `Pos/otherbook/index.blade.php` | root | 36 | 15 | select2, DT, Swal, flatpickr | - |  |
| [ ] | `Pos/pos/create.blade.php` | root | 172 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Pos/pos/create2.blade.php` | root | 34 | 0 | - | PosFormUiContract, PosSheetsUiContract |  |
| [ ] | `Pos/pos/deleted-nota.blade.php` | errors | 0 | 0 | - | - |  |
| [ ] | `Pos/pos/index.blade.php` | partial | 27 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Pos/pos/index2.blade.php` | root | 58 | 22 | select2, DT, Swal, flatpickr | PosListUiContract |  |
| [ ] | `Pos/pos/js-create.blade.php` | partial | 9 | 0 | select2, Swal, bs-js | - |  |
| [ ] | `Pos/pos/js-create2.blade.php` | partial | 0 | 0 | bs-js | - |  |
| [ ] | `Pos/pos/js-payment.blade.php` | partial | 0 | 0 | select2, Swal | PosPaymentUiContract |  |
| [ ] | `Pos/pos/js/js-allowed.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Pos/pos/js/js-buah.blade.php` | partial | 131 | 0 | select2, Swal, bs-js | PosSheetsUiContract, erp-picker(js) |  |
| [ ] | `Pos/pos/js/js-header.blade.php` | partial | 17 | 0 | select2, Swal, bs-js | erp-picker(js) |  |
| [ ] | `Pos/pos/js/js-jus.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Pos/pos/js/js-parcel.blade.php` | partial | 15 | 0 | select2, Swal, bs-js | - |  |
| [ ] | `Pos/pos/payment-success.blade.php` | root | 31 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Pos/pos/payment-success2.blade.php` | partial | 10 | 0 | - | PosPaymentUiContract |  |
| [ ] | `Pos/pos/payment.blade.php` | root | 38 | 0 | - | PosPaymentUiContract |  |
| [ ] | `Pos/pos/print-list-payment.blade.php` | root | 27 | 0 | - | - |  |
| [-] | `Pos/pos/print.blade.php` | partial | 2 | 0 | - | - | print/pdf: dikecualikan, hanya cek bebas Bootstrap |
| [-] | `Pos/pos/print2.blade.php` | partial | 2 | 0 | - | - | print/pdf: dikecualikan, hanya cek bebas Bootstrap |
| [-] | `Pos/pos/receipt.blade.php` | partial | 49 | 0 | - | - | print/pdf: dikecualikan, hanya cek bebas Bootstrap |
| [-] | `Pos/pos/receipt2.blade.php` | partial | 0 | 0 | - | PosPaymentUiContract, ReceiptViewData | print/pdf: dikecualikan, hanya cek bebas Bootstrap |
| [ ] | `Pos/pos/segment/cart.blade.php` | partial | 1 | 0 | - | PosFormUiContract |  |
| [ ] | `Pos/pos/segment/modal-customer.blade.php` | partial | 20 | 0 | - | OrderBookFormUiContract, PosFormUiContract |  |
| [ ] | `Pos/pos/segment/modal-gift.blade.php` | partial | 22 | 0 | - | PosFormUiContract |  |
| [ ] | `Pos/pos/segment/modal-jus.blade.php` | partial | 57 | 0 | - | PosFormUiContract |  |
| [ ] | `Pos/pos/segment/modal-parcel.blade.php` | partial | 91 | 0 | select2 | PosFormUiContract |  |
| [ ] | `Pos/pos/segment/modal-product.blade.php` | partial | 57 | 0 | - | PosFormUiContract |  |
| [ ] | `Pos/pos/segment/ringkasan.blade.php` | partial | 93 | 0 | - | PosFormUiContract |  |
| [ ] | `Pos/setting-nota/index.blade.php` | root | 0 | 2 | - | - |  |
| [-] | `Pos/setting-nota/view.blade.php` | partial | 2 | 0 | - | - | print/pdf: dikecualikan, hanya cek bebas Bootstrap |

## Lampiran B — Backend yang menghasilkan HTML

Kolom: jumlah atribut `class=` dan baris yang memuat class Bootstrap/Metronic/ikon lama. Dimigrasi bersama batch modulnya (§6.3).

| ✓ | File | class= | BS/ikon | Batch |
|---|---|---:|---:|---|
| [ ] | `Modules/Master/Http/Controllers/ProductController.php` | 60 | 36 | B2 |
| [ ] | `Modules/Report/Http/Controllers/ReportController.php` | 57 | 52 | B3 |
| [ ] | `Modules/Pos/Http/Controllers/DeliveryOrderController.php` | 34 | 30 | B5 |
| [ ] | `Modules/Transaction/Http/Controllers/ProductionController_temp.php` ⚠ kandidat mati | 32 | 28 | B4 |
| [ ] | `Modules/Transaction/Http/Controllers/ProductionParcelController.php` | 31 | 27 | B4 |
| [ ] | `Modules/Transaction/Http/Controllers/WholesaleController.php` | 31 | 12 | B4 |
| [ ] | `Modules/Transaction/Http/Controllers/ProductionController.php` | 30 | 19 | B4 |
| [ ] | `Modules/Pos/Http/Controllers/OrderBookController.php` | 28 | 23 | B5 |
| [ ] | `Modules/Transaction/Http/Controllers/TransferController.php` | 25 | 22 | B4 |
| [ ] | `Modules/Pos/Http/Controllers/PosController.php` | 25 | 13 | B5 |
| [ ] | `Modules/Crm/Http/Controllers/DepositoController.php` | 22 | 19 | B3 |
| [ ] | `Modules/Transaction/Http/Controllers/ProductReceiptController.php` | 22 | 18 | B4 |
| [ ] | `Modules/Master/Http/Controllers/StaffController.php` | 22 | 18 | B2 |
| [ ] | `Modules/Pos/Http/Controllers/OtherBookController.php` | 20 | 17 | B5 |
| [ ] | `Modules/Crm/Http/Controllers/TierController.php` | 20 | 17 | B3 |
| [ ] | `Modules/Pos/Http/Controllers/ExpenditureController.php` | 18 | 15 | B5 |
| [ ] | `Modules/Master/Http/Controllers/CustomerController.php` | 17 | 14 | B2 |
| [ ] | `Modules/Transaction/Http/Controllers/StockOutController.php` | 15 | 12 | B4 |
| [ ] | `Modules/Transaction/Http/Controllers/SortirController.php` | 14 | 11 | B4 |
| [ ] | `Modules/Master/Http/Controllers/SupplierController.php` | 14 | 11 | B2 |
| [ ] | `Modules/Master/Http/Controllers/ProductUnitController.php` | 14 | 11 | B2 |
| [ ] | `Modules/Master/Http/Controllers/PositionController.php` | 13 | 10 | B2 |
| [x] | `Modules/Master/Http/Controllers/LocationController.php` | 13 | 10 | B2 |
| [ ] | `app/Http/Controllers/UserController.php` | 12 | 11 | B3 |
| [ ] | `app/Http/Controllers/P_role.php` | 12 | 11 | B3 |
| [x] | `Modules/Master/Http/Controllers/HandlingController.php` | 11 | 8 | B2 |
| [x] | `Modules/Master/Http/Controllers/DepartmentController.php` | 11 | 8 | B2 |
| [ ] | `Modules/Crm/Http/Controllers/CampaignController.php` | 10 | 9 | B3 |
| [ ] | `Modules/Master/Http/Controllers/KurirController.php` | 9 | 8 | B2 |
| [ ] | `Modules/Transaction/Http/Controllers/StockOutTypeController.php` | 8 | 7 | B4 |
| [ ] | `Modules/Master/Http/Controllers/ProductCategoryController.php` | 8 | 7 | B2 |
| [ ] | `Modules/Master/Http/Controllers/PaymentMethodController.php` | 8 | 7 | B2 |
| [ ] | `Modules/Master/Http/Controllers/BranchController.php` | 8 | 7 | B2 |
| [ ] | `Modules/Master/Http/Controllers/AccountController.php` | 8 | 7 | B2 |
