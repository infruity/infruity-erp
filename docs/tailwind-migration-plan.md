# Plan Migrasi Bootstrap/Metronic → Tailwind CSS

5 November 2026. Branch fondasi: `feature/tailwind-migration` (dari `origin/main` @ `671fb18a`).

Dokumen ini adalah acuan tunggal migrasi. Setiap batch mencentang checklist di [Lampiran A](#lampiran-a--checklist-per-view) dan memperbarui bagian **Status** di bawah. Angka inventaris dihasilkan dengan grep pada 5 November 2026. Hitung ulang di awal setiap batch karena `main` terus bergerak.

## Status

| Batch | Branch | PR | Status |
|---|---|---|---|
| B0 Fondasi build | `feature/tailwind-migration` | — | plan ditulis |
| B1 Shell, auth, errors, landing, komponen | — | — | belum |
| B2 Master | — | — | belum |
| B3 Report, CRM, Chat, admin | — | — | belum |
| B4 Transaction | — | — | menunggu `ui/produksi-reference` + `ui/stock-opname-reference` masuk `main` |
| B5 POS | — | — | belum |
| B6 Cleanup | — | — | belum |

---

## 1. Keputusan yang sudah dikunci

| # | Topik | Keputusan |
|---|---|---|
| Q1 | Definisi selesai | Bootstrap **dan** Metronic (CSS + JS) tidak dimuat lagi. Tidak ada `data-bs-*`, `data-kt-*` maupun class Bootstrap/Metronic. Semua halaman memakai satu layout Tailwind. |
| Q2/Q11 | Build | Vite + **Tailwind 3.4** compiled. Laravel Mix dan Tailwind CDN dihapus. Tailwind 3.4 dipilih demi perangkat lama (Safari 14+, Chrome 88+ kira-kira) dan karena cocok dengan class v3 yang sudah dipakai lewat CDN. |
| Q3 | Rollout | Bertahap per batch. Setiap batch punya branch + PR sendiri ke `main`. |
| Q4/Q17 | Interaksi | Alpine.js + `<dialog>` native + `erp-overlays.js`, dibungkus sebagai Blade component `x-erp.*`. |
| Q5 | Plugin | Select2 → `erp-picker` lalu dihapus. DataTables tetap (paket npm, styling Tailwind). SweetAlert2 dan Flatpickr tetap (npm). |
| Q6 | Visual | Mengikuti referensi `infruity-frontend` bila ada; selain itu tampilan sekarang dipertahankan. Bukan redesign. |
| Q7 | Kode mati | Diaudit dulu, lalu dihapus di commit terpisah dalam batch-nya. Daftar kandidat ada di Lampiran A (⚠). |
| Q8 | Test | Test diperlakukan sebagai spesifikasi perilaku dan diperbarui bersama port. Tidak ada skip. Ditambah screenshot sebelum/sesudah. |
| Q9 | Branch | Branch ini berasal dari `main` murni. Branch UI lain tidak di-merge ke sini. |
| Q10 | Deploy | Build di server (`npm ci && npm run build`). Node dipasang bila belum ada. `public/build` di-gitignore. |
| Q12 | Dark mode | Dibuang, termasuk toggle di Pengaturan, skrip `data-bs-theme` di `template.root`, dan ikon `ki-night-day`/`ki-moon`. |
| Q13 | Urutan | B0 → B1 → B2 → B3 → B4 → B5 → B6. |
| Q14 | Branch berjalan | `ui/produksi-reference` (3 commit) dan `ui/stock-opname-reference` (3 commit, 7 di belakang) di-merge ke `main` sebelum B4. |
| Q15 | Print/nota | Dikeluarkan dari Tailwind. Hasil cetak harus identik. View dompdf/Excel juga tidak disentuh. |
| Q16 | Data | Memakai DB lokal `infruity_clone` (dummy) dari `.env` checkout utama. |
| Q18 | Form/tombol | `@tailwindcss/forms` (strategi `class`) + class `erp-input`, `erp-select`, `erp-btn-*`, `erp-label`, `erp-error` di `@layer components`. |
| Q19 | Aturan baru | Setelah B1 masuk, UI baru wajib memakai layout/komponen Tailwind. Ditegakkan oleh test ratchet (§7.3) dan ditulis di `AGENTS.md` + `docs/ui-design-system.md`. |
| Q20 | PR | Claude commit, push, dan membuka PR. Merge dilakukan pemilik repo. |
| Q21/Q22 | Plan | File ini. Direview dulu sebelum B0 dikerjakan. |

---

## 2. Kondisi awal (fakta)

### 2.1 Layout dan aset

| Layout | Dipakai | Memuat |
|---|---|---|
| `template.root` | 73 view | Metronic `style.bundle.css` (Bootstrap 5, 1,27 MB), `plugins.bundle.css/js`, `scripts.bundle.js`, `datatables.bundle.*`, **Tailwind CDN `preflight: false`**, jQuery 3.6 CDN, Alpine (unpkg), `@livewireStyles` bersyarat, firebase messaging, dan 9 file `erp-*.css`. Shell-nya sudah Tailwind (`x-erp.sidebar`, `x-erp.topbar`, `x-erp.mobile-navbar`). |
| `layouts.erp-tailwind` | 20 view | Tailwind CDN dengan preflight, `tailwind.config` inline sendiri, Alpine + collapse (jsdelivr), jQuery, `datatables.bundle.*`, SweetAlert2 CDN, sekitar 100 baris CSS inline untuk sidebar. **Tanpa** `style.bundle.css`. |
| `layouts.app` | 4 view (`auth/passwords/*`, `auth/verify`) | Mix `css/app.css` (Bootstrap 4) + `js/app.js`. Semuanya kandidat mati. |
| `errors.layout` | 10 view | Standalone. |
| `auth/login`, `landing` | standalone | Tailwind CDN. `landing` juga memakai `KT*`. |
| `*::layouts.master` (Chat, CRM, Master, POS, Report, Transaction) | 0–1 view | Scaffold nwidart, kandidat mati. |
| `pos/index` | standalone | Bootstrap 5.3 dari CDN. Kandidat mati (controller memakai `index2`). |

- Ada **dua konfigurasi Tailwind inline yang berbeda** (`template.root` vs `erp-tailwind`), keduanya harus digabung.
- `package.json` masih memakai Laravel Mix 5 + `bootstrap@4` + `popper.js` + `sass` + `vue-template-compiler`. Pipeline ini hanya melayani `layouts.app`.
- Ada 18 file CSS kustom di `public/assets/css` (sekitar 2.300 baris) dan 13 file JS kustom di `public/assets/js`. Semuanya dimuat lewat `asset()?v=filemtime`, bukan bundler.

### 2.2 Kepadatan per batch

Angka "BS" menghitung token class yang bergaya Bootstrap/Metronic: `form-control`, `btn-*`, `modal*`, `card*`, `col-*`, `d-*`, `fs-*`, `fw-*`, `text-gray-*`, spasi, dan seterusnya. Ini indikator kepadatan, bukan jumlah pasti yang harus diganti. Sebagian nama (misalnya `p-3`, `mb-4`) juga sah di Tailwind; lihat §4.

| Batch | View | Hit BS | Kandidat mati |
|---|---:|---:|---:|
| B1 | 50 | 3.714 | 10 (sebagian besar `template/*` Metronic lama) |
| B2 | 42 | 1.341 | 3 |
| B3 | 52 | 929 | 10 |
| B4 | 74 | 3.134 | 22 |
| B5 | 50 | 1.687 | 4 |

### 2.3 Ketergantungan JavaScript

| Pemakaian | Jumlah | Catatan |
|---|---|---|
| `data-kt-menu*` | 175 | Hampir semua di `template/*` lama dan `auth/footer` |
| `data-kt-ecommerce*` | 164 | Dari demo Metronic: pengadaan, transfer, sortir, production, stock-opname, `livewire/product-table` |
| `data-kt-check*` | 78 | Select-all checkbox |
| `data-kt-drawer*` | 76 | `template/aside`, `template/drawer`, **stock-opname index** |
| `data-kt-scroll*` | 79 | stock-opname, stock-out, delivery-order, master handling/location/department |
| `data-bs-*` | sekitar 130 | toggle/target/dismiss modal, dropdown, tooltip |
| `bootstrap.Modal` / `.modal()` | 7 file + `erp-crm.js` | |
| Select2 | 79 referensi | `erp-picker.js` masih punya 8 referensi `select2` (jembatan) |
| SweetAlert2 | 332 panggilan / 47 file | Di `template.root` datang dari `plugins.bundle`, di `erp-tailwind` dari CDN |
| DataTables 1.13.7 | 101 `ajax:`, 43 `serverSide:`, 25 `fixedColumns:`, 6 `responsive:`, sekitar 10 `select:` | Bundle Metronic juga berisi JSZip + pdfmake, tapi tidak ada konfigurasi `buttons:` yang terdeteksi |
| `toastr` | 1 file | `production/js-create` |
| ApexCharts | 1 file | `crm::dashboard.js-dashboard` (kandidat mati) |
| Quill | 1 file | `master::products.edit` (kandidat mati) |
| `KTImageInput`, `KTPasswordMeter` | — | `crm::tier`, `master::products.edit` |
| `KT*` di view | 3 file | `landing`, `stock-opname/index`, `crm::dashboard.js-dashboard` |
| Livewire 4 | 1 halaman hidup | `produk.blade.php` → `<livewire:produk-form/>`. Livewire 4 menyuntik Alpine sendiri, jadi bentrok dengan Alpine dari CDN/bundle. |

### 2.4 Ikon (semuanya ikut termuat lewat `plugins.bundle.css`)

| Set | Ref | Tujuan |
|---|---:|---|
| Keenicons `ki-*` | 716 (57 file, 24 di luar `template/*`) | Phosphor |
| Bootstrap Icons `bi-*` | 155 (termasuk controller) | Phosphor |
| FontAwesome `fa-*` | 56 | Phosphor |
| Phosphor `ph-*` | 629 | tetap (dilayani lokal) |

### 2.5 HTML dari backend

35 file PHP di luar `Resources/views` (683 atribut `class=`) menghasilkan HTML. Sebagian besar berupa kolom Yajra DataTables (`addColumn`/`editColumn`) yang berisi badge, tombol aksi, dropdown dan ikon `bi-`. Daftarnya ada di [Lampiran B](#lampiran-b--backend-yang-menghasilkan-html). Controller-controller ini **wajib** ikut dimigrasi di batch modulnya, dan path-nya **wajib** masuk `content` Tailwind.

### 2.6 Test

- 61 file di `tests/Feature`, kurang lebih 39 di antaranya mengunci markup: membaca file view atau me-render Blade, lalu `assertStringContainsString` class/struktur. Kata `modal` muncul 64 kali, `select2` 13 kali.
- 23 test JS (`tests/js/*.test.cjs`, `node --test`) mengunci `public/assets/js/*` dan beberapa view.
- Peta test → view ada di kolom "Test pengunci" pada Lampiran A.
- Worktree ini belum punya `vendor/` dan `node_modules/`. Baseline hijau/merah belum diketahui; ini langkah pertama B0.

---

## 3. Arsitektur akhir (target B6)

```
resources/
  css/
    app.css            ← @tailwind base/components/utilities + token + @layer components (erp-*)
    legacy.css         ← SEMENTARA (B0–B5): @config tailwind.legacy.config.js, preflight off, untuk template.root
    vendor/
      datatables.css   ← skin Tailwind untuk DataTables (tanpa dataTables.bootstrap5)
      flatpickr.css    ← import flatpickr + override token
      swal.css         ← override token SweetAlert2
  js/
    app.js             ← Alpine (+collapse, +focus), komponen erp, jQuery global, DataTables, Swal, flatpickr
    erp/
      dialog.js  dropdown.js  tabs.js  drawer.js  check-all.js  tooltip.js  toast.js
    legacy-bridge.js   ← SEMENTARA: shim bootstrap.Modal → x-erp.dialog (lihat §5.3)
  views/
    layouts/erp.blade.php   ← satu-satunya layout aplikasi
    layouts/guest.blade.php ← login, lupa password, errors
    components/erp/*.blade.php
tailwind.config.js
tailwind.legacy.config.js   ← SEMENTARA, dihapus di B6
postcss.config.js
vite.config.js
```

### 3.1 `tailwind.config.js`

- `content`:
  - `resources/views/**/*.blade.php`
  - `Modules/*/Resources/views/**/*.blade.php`
  - `app/**/*.php`
  - `Modules/*/Http/**/*.php` dan `Modules/*/Support/**/*.php` (HTML dari controller)
  - `resources/js/**/*.js`
  - `public/assets/js/*.js` kecuali `*.bundle.js` (selama file-file itu belum masuk Vite)
- `theme.extend`: gabungan dua config inline lama ditambah token `docs/ui-design-system.md`:
  - `colors`:
    - `primary #0F5C45`, `teal { DEFAULT #0b595b, hover #08484a }`, `lime #d1f36e`, `accent #F2B400`, `strawberry #D6455D`
    - `canvas #e5e9f3`
    - status success/warning/error/info memakai skala bawaan
  - `fontFamily.sans = "Plus Jakarta Sans"`, `fontFamily.serif = "Poppins"` (dua config lama sama-sama memakai `serif` untuk Poppins; nama ini dipertahankan agar class lama tidak patah)
  - `boxShadow.soft`, `boxShadow.card` (nilai dari ui-design-system)
  - `borderRadius`: `control 12px`, `panel 16px`
  - `height.control 44px`
- `plugins`: `@tailwindcss/forms({ strategy: 'class' })`. Strategi `class` dipilih supaya reset tidak menimpa input plugin (DataTables, flatpickr) secara global.
- `safelist`: **hanya** class yang terbukti disusun dinamis (hasil audit §7.2), bukan pola luas.
- Tanpa `prefix`. 20 halaman Tailwind dan shell yang ada sudah memakai class tanpa prefix.

### 3.2 `tailwind.legacy.config.js` (sementara)

Isinya `presets: [require('./tailwind.config.js')]`, `corePlugins.preflight: false`, `important: false`. Dipakai `resources/css/legacy.css` lewat `@config`, menggantikan CDN di `template.root`. Hasilnya: halaman lama tetap tampil persis seperti sekarang (CDN tanpa preflight), tetapi class-nya sudah compiled.

### 3.3 Vite

- `laravel-vite-plugin`, dengan input `resources/css/app.css`, `resources/css/legacy.css`, `resources/js/app.js`.
- Layout memakai `@vite([...])`.
- Script per-halaman yang masih di `public/assets/js` tetap memakai `asset()?v=filemtime` dan dipindahkan ke `resources/js` saat halaman pemiliknya diport.

### 3.4 Alpine dan Livewire

Livewire 4 membawa Alpine sendiri. Supaya tidak ada dua instance Alpine:

- **Opsi utama (diputuskan di B2):** jika `produk-form` masih hidup, `app.js` mengimpor `Livewire` dan `Alpine` dari `vendor/livewire/livewire/dist/livewire.esm` lalu memanggil `Livewire.start()`, dan layout memakai `@livewireScriptConfig`. Plugin Alpine (collapse, focus) didaftarkan ke Alpine yang sama.
- Jika `produk-form` mati: hapus pemakaian Livewire dan impor `alpinejs` biasa. Paket composer `livewire/livewire` tetap ada sampai ada keputusan backend terpisah.
- Selama B0–B1, untuk menjaga perilaku sekarang, Alpine di-bundle via npm dan Livewire tetap memakai skrip auto-inject hanya di halaman `produk`. Halaman itu diuji khusus di B0.

---

## 4. Aturan pemetaan class

### 4.1 Prinsip

1. **Metronic memakai skala spasi 0,25rem, sama seperti Tailwind.** `p-3` = .75rem, `mb-4` = 1rem, `px-10` = 2,5rem, `gap-3` = .75rem. Utility spasi bernama sama **aman** dibiarkan. Perhatian: Metronic menambahkan `!important` dan Tailwind tidak. Ketika Metronic dilepas, aturan lain yang dulu kalah bisa menang.
2. **Nama sama, nilai beda: wajib dicek manual.**

| Class | Metronic | Tailwind 3.4 | Tindakan |
|---|---|---|---|
| `text-gray-100..900` | `--bs-text-gray-*`, misalnya 600 = `#78829D` | skala gray Tailwind (600 = `#4b5563`) | Ganti dengan token tampilan yang dimaksud, bukan sekadar menyamakan nama |
| `bg-gray-*`, `border-gray-*` | skala Metronic | skala Tailwind | sama seperti di atas |
| `rounded` | .475rem | .25rem | `rounded-lg`/`rounded-control` |
| `rounded-1..4`, `rounded-pill`, `rounded-circle` | Metronic | tidak ada | `rounded-md/lg/xl/2xl`, `rounded-full` |
| `shadow`, `shadow-sm`, `shadow-lg` | var Metronic | Tailwind | `shadow-card`/`shadow-soft` |
| `collapse` | `display:none` tanpa `.show` | `visibility: collapse` | **Bentrok aktif**: ganti dengan `x-erp.collapse`/`x-collapse` |
| `container` | Bootstrap max-width | Tailwind container | hindari dan pakai wrapper eksplisit |
| `table` | komponen tabel | `display: table` | `erp-table` (§5.2) |
| `border` | warna Metronic | `currentColor`/gray-200 lewat preflight | `border border-gray-200` |
| `fs-1..7`, `fs-8..9` | skala Metronic (fs-6 = 1.075rem) | tidak ada | `text-xs/sm/base/lg/xl` sesuai token ui-design-system |
| `fw-bold/semibold/bolder` | 600/500/... (Metronic) | tidak ada | `font-semibold`/`font-medium`/`font-bold` |
| `w-100`, `h-100`, `w-50px`, `h-50px`, `mw-*`, `mh-*` | Metronic | tidak ada | `w-full`, `h-full`, `w-[50px]` |
| `ms-*`/`me-*`/`ps-*`/`pe-*` | margin/padding kiri-kanan | Tailwind 3.3+ juga punya (logical) | aman, nilai sama |
| `text-start/end` | Bootstrap | Tailwind 3.3+ punya `text-start/end` | aman |
| `d-none`, `d-flex`, `d-md-block` | Bootstrap | tidak ada | `hidden`, `flex`, `md:block` |
| `visible`/`invisible` | Bootstrap | Tailwind | aman |

3. Breakpoint: Bootstrap `sm 576 / md 768 / lg 992 / xl 1200 / xxl 1400`, Tailwind `sm 640 / md 768 / lg 1024 / xl 1280 / 2xl 1536`. Hanya `md` yang sama. Untuk `col-lg-*`/`d-lg-*` dan `col-xl-*`/`d-xl-*`, uji di 992–1023 px dan 1200–1279 px. Tidak menambah breakpoint kustom kecuali ada layar yang benar-benar patah.

### 4.2 Pemetaan struktural

| Bootstrap/Metronic | Pengganti |
|---|---|
| `row` + `col-*` / `g-*` | `grid grid-cols-12 gap-*` + `col-span-*`, atau `flex flex-wrap` bila kolomnya auto |
| `card`, `card-header/body/footer`, `card-flush` | `erp-panel` (sudah ada di erp-theme) atau `x-erp.panel` |
| `modal` + `data-bs-toggle="modal"` | `x-erp.dialog` (§5.1) |
| `dropdown`, `dropdown-menu`, `menu menu-sub-dropdown` + `data-kt-menu-trigger` | `x-erp.dropdown` |
| `nav nav-tabs` + `tab-pane` | `x-erp.tabs` |
| `accordion`, `collapse` | `x-erp.collapse` |
| `data-kt-drawer` | `x-erp.drawer` (sheet kanan/bawah, memakai `erp-overlays.js`) |
| `data-kt-check` (select-all) | `x-erp.check-all` (Alpine) |
| `data-bs-toggle="tooltip"` | `x-erp.tooltip` atau atribut `title` bila cukup |
| `data-kt-scroll` | `overflow-y-auto` + `max-h-[...]`/flex `min-h-0` (tanpa JS) |
| `badge badge-light-*` | `erp-badge erp-badge--{success,warning,danger,info,neutral}` |
| `btn btn-primary/light/danger/icon/sm` | `erp-btn erp-btn-primary/secondary/danger/ghost/icon` + ukuran `erp-btn-sm` |
| `form-control`, `form-control-solid` | `erp-input` |
| `form-select`, select2 | `erp-select` / `erp-picker` |
| `form-check`, `form-check-input` | `erp-check` (berbasis `form-checkbox` dari plugin forms) |
| `form-label`, `required` | `erp-label`, `erp-label--required` |
| `invalid-feedback`, `is-invalid` | `erp-error` + `aria-invalid` + `aria-describedby` |
| `input-group` | `erp-input-group` (flex + border bersama) |
| `table table-row-dashed gy-5 fs-6` | `erp-table` (+ skin DataTables §6.1) |
| `symbol symbol-50px` | `erp-avatar erp-avatar--md` |
| `separator` | `border-t border-gray-100` |
| `bg-light-*` / `text-*` status | token status ui-design-system |

### 4.3 Ikon → Phosphor

Phosphor sudah dilayani lokal. Pemetaan untuk ikon yang paling sering dipakai (sisanya dipetakan per file saat port):

| Lama | Phosphor |
|---|---|
| `ki-cross`, `bi-x`, `fa-times` | `ph ph-x` |
| `ki-cross-circle` | `ph ph-x-circle` |
| `ki-check-circle`, `bi-check-circle(-fill)`, `bi-check2-circle` | `ph ph-check-circle` / `ph-fill ph-check-circle` |
| `ki-plus`, `bi-plus(-lg)`, `fa-plus` | `ph ph-plus` |
| `ki-minus`, `fa-minus` | `ph ph-minus` |
| `ki-magnifier` | `ph ph-magnifying-glass` |
| `ki-basket`, `fa-shopping-cart` | `ph ph-shopping-cart` |
| `ki-pencil`, `bi-pencil(-square/-fill)`, `fa-pencil`, `fa-edit` | `ph ph-pencil-simple` |
| `bi-trash`, `fa-trash(-alt)` | `ph ph-trash` |
| `bi-eye`, `fa-eye` | `ph ph-eye` |
| `bi-three-dots-vertical` | `ph-bold ph-dots-three-vertical` |
| `ki-arrow-right`, `bi-arrow-right` / `bi-arrow-left` | `ph ph-arrow-right` / `ph ph-arrow-left` |
| `ki-information-5`, `bi-info-circle` | `ph ph-info` |
| `ki-security-user` | `ph ph-user-gear` |
| `ki-sms`, `ki-message-text-2`, `fa-comment` | `ph ph-chat-text` |
| `ki-package`, `bi-box` | `ph ph-package` |
| `ki-purchase`, `bi-cash-stack`, `fa-credit-card` | `ph ph-receipt` / `ph ph-money` / `ph ph-credit-card` |
| `fa-print` | `ph ph-printer` |
| `fa-receipt` | `ph ph-receipt` |
| `fa-thumbtack` | `ph ph-push-pin` |
| `bi-save` | `ph ph-floppy-disk` |
| `bi-lock-fill` | `ph-fill ph-lock` |
| `ki-chart*` | `ph ph-chart-bar` / `ph ph-chart-line` / `ph ph-chart-line-down` |
| `ki-down` | `ph ph-caret-down` |
| `ki-night-day`, `ki-moon` | dihapus (dark mode dibuang) |
| `bi-whatsapp` | `ph ph-whatsapp-logo` |

Ikon tanpa padanan yang jelas (`ki-abstract-26`, `ki-color-swatch`, `ki-switch`, `ki-star`) diputuskan per halaman mengikuti referensi `infruity-frontend`.

---

## 5. Komponen yang dibangun (B1)

Semua komponen adalah Blade anonymous component di `resources/views/components/erp/` dengan logika Alpine di `resources/js/erp/`. Setiap komponen punya test Blade render (feature test) dan, bila ada logika JS, test `node --test`.

### 5.1 Overlay

| Komponen | API | Perilaku wajib |
|---|---|---|
| `x-erp.dialog` | `id`, `title`, `size` (`sm/md/lg/xl/full`), `variant` (`center` / `sheet` = bottom sheet di mobile), slot `header`, default, `footer`, `static` (tidak tertutup klik backdrop) | `<dialog>` + `showModal()`; Esc menutup (kecuali `static`); fokus pertama dan pemulihan fokus; scroll-lock body; tinggi dibatasi `dvh` dengan isi yang scroll (seperti `.erp-sheet-dialog` sekarang); event `erp:dialog-open`/`erp:dialog-close` (ditambah `shown.bs.modal`/`hidden.bs.modal` selama masa jembatan, §5.3) |
| `x-erp.drawer` | `id`, `side` (`right`/`bottom`), `width`, slot | sama seperti dialog; memakai containment + inert di `erp-overlays.js`; menggantikan `data-kt-drawer` |
| `x-erp.dropdown` | `align`, `placement`, slot `trigger`, default | klik/Enter/Space membuka; Esc + klik luar menutup; navigasi panah; posisi otomatis naik bila mepet bawah (row aksi DataTables di baris terakhir); tidak terpotong `overflow` tabel (teleport ke body untuk tabel) |
| `x-erp.tooltip` | `text`, `placement` | hover + fokus; `aria-describedby` |
| `x-erp.toast` / `erp.toast()` | `type`, `message` | dibangun di atas `Swal.mixin({toast:true})`; menggantikan `toastr` |

### 5.2 Konten dan form

| Komponen / class | Catatan |
|---|---|
| `x-erp.tabs` + `x-erp.tab` | `role=tablist`; navigasi panah; mendukung `?tab=` di URL bila halaman lama memakai hash |
| `x-erp.collapse` | `x-collapse`; `aria-expanded`/`aria-controls` |
| `x-erp.check-all` | header checkbox mengontrol `[data-erp-check]`; state indeterminate; terhubung ke DataTables redraw |
| `x-erp.panel` | pembungkus `erp-panel` |
| `x-erp.badge` | variant status; dipakai juga oleh controller (§6.3) |
| `x-erp.row-actions` | tombol titik tiga + dropdown standar untuk kolom aksi DataTables; dirender di server |
| `erp-input`, `erp-select`, `erp-textarea`, `erp-check`, `erp-radio`, `erp-label`, `erp-error`, `erp-help`, `erp-input-group` | `@layer components`; tinggi 44px, radius 12px, focus-visible teal 2px offset 3px, state error merah + `aria-invalid` |
| `erp-btn` + `-primary/-secondary/-danger/-ghost/-icon/-sm/-block` | disabled = opacity + `cursor-not-allowed`; slot spinner `[data-loading]` |
| `erp-table` | header, padding, border dashed, angka tabular |

Komponen yang sudah ada (`workspace`, `collection`, `detail-dialog`, `dropdown-select`, `load-more`, `mobile-*`, `sidebar`, `topbar`) dibersihkan dari class Bootstrap di B1. `detail-dialog` dibangun ulang di atas `x-erp.dialog`, dan `dropdown-select` dievaluasi: dilebur ke `erp-picker` atau dipertahankan.

### 5.3 Jembatan sementara `legacy-bridge.js`

Banyak skrip halaman memanggil `bootstrap.Modal.getOrCreateInstance(el).show()`, `$('#x').modal('hide')`, dan mendengarkan `shown.bs.modal`. Supaya port bisa dilakukan per halaman tanpa menulis ulang semua JS sekaligus:

- Di layout baru (`layouts.erp`), `window.bootstrap = { Modal }` adalah shim kecil yang meneruskan ke `x-erp.dialog` (`show`, `hide`, `toggle`, `getInstance`, `getOrCreateInstance`) dan memancarkan `show/shown/hide/hidden.bs.modal`. `$.fn.modal` mendapat shim yang sama.
- Shim **hanya** untuk Modal (dan Tooltip no-op). Dropdown/Tab/Collapse langsung diganti markup komponen.
- Setiap pemakaian shim dicatat (`console.warn` di dev) dan **dihapus di B6**. Grep `bootstrap\.` dan `.modal(` harus 0 di akhir B5.

---

## 6. Strategi plugin

### 6.1 DataTables

- Ganti `datatables.bundle.*` Metronic dengan npm:
  - `datatables.net@1.13.x` (versi sama dengan sekarang, 1.13.7, supaya API tidak berubah)
  - `datatables.net-fixedcolumns`, `datatables.net-responsive`, `datatables.net-select` (sesuai pemakaian §2.3)
- **Tanpa** `datatables.net-bs5`. JSZip/pdfmake tidak dipasang kecuali audit B0 menemukan tombol export.
- Skin `resources/css/vendor/datatables.css`: wrapper, `dataTables_length/filter/info/paginate`, sort icon, `processing`, `fixedColumns`. Semuanya memakai token.
- `erp-collections.js` (membaca DOM DataTables untuk daftar mobile) dipertahankan. Selector-nya diaudit karena class wrapper bs5 seperti `.row`, `.col-sm-12` di `dom` default bs5 akan hilang. Opsi `dom` di setiap halaman dicek.
- Upgrade ke DataTables 2.x **bukan** bagian migrasi ini.
- Selama B0–B5: halaman lama tetap memuat bundle Metronic dan halaman baru memuat build npm. Satu halaman tidak pernah memuat keduanya.

### 6.2 Select2 → `erp-picker`

- `erp-picker.js` sudah mendukung sumber ajax/statis, pagination, extra data dan modal hosting.
- Setiap batch mengganti semua `select2` di halamannya dengan `erp-picker`. Event `select2:select` yang didengar skrip halaman dipetakan ke event `erp-picker`; daftar event ditulis di §5 saat B1.
- Saat select2 terakhir hilang (B5), jembatan `select2` di `erp-picker.js` dan CSS adapter select2 di `erp-theme.css` dihapus (B6).

### 6.3 HTML dari controller

- Semua HTML di `addColumn`/`editColumn` dipindah ke Blade partial/komponen: `view('components.erp.row-actions', [...])->render()` atau partial modul `master::products.partials.actions`. Hal yang sama berlaku untuk badge (`x-erp.badge`).
- Logika otorisasi (`check_access`) **tetap di controller atau dioper ke partial apa adanya**. Tidak ada perubahan perilaku.
- Ini perubahan backend dan dilaporkan di setiap PR.
- `ProductionController_temp.php`, `PosController.php.temp` dan `StockOpnameController.php.temp` adalah kandidat mati (B6).

### 6.4 Lainnya

| Plugin | Keputusan |
|---|---|
| SweetAlert2 | npm `sweetalert2`, diekspor ke `window.Swal`. Override token di `swal.css`. |
| Flatpickr | npm `flatpickr` + locale `id`. |
| `toastr` (1 file) | Diganti `erp.toast()` di B4. |
| ApexCharts (`crm::dashboard`) | Bila halamannya hidup, npm `apexcharts` diimpor hanya di halaman itu; bila mati, dihapus. |
| Quill, `KTImageInput`, `KTPasswordMeter` | Hanya ada di `master::products.edit` (kandidat mati) dan `crm::tier`. Image input ditulis ulang sebagai komponen Alpine kecil `x-erp.image-input` di batch yang membutuhkannya. |
| Firebase messaging | Tidak tersentuh; dipindah apa adanya ke layout baru. |
| jQuery | Tetap (npm `jquery`, `window.$`), selama DataTables dan skrip halaman masih memakainya. Penghapusan jQuery **bukan** tujuan migrasi ini. |

---

## 7. Risiko dan jebakan

### 7.1 Daftar risiko

| # | Risiko | Mitigasi |
|---|---|---|
| R1 | Class yang hanya dibentuk di runtime (konkatenasi JS/PHP, `'text-' + status`) tidak ikut ter-compile, karena CDN dulu men-scan DOM sedangkan compiled men-scan file. | Audit §7.2 di B0. Safelist eksplisit. Screenshot sebelum/sesudah B0 harus identik. |
| R2 | Kehilangan `!important` Metronic mengubah urutan menang CSS kustom `erp-*.css`. | Port per halaman + screenshot. Tidak menambah `!important` baru (aturan ui-design-system). |
| R3 | `collapse`, `container`, `table` bentrok (§4.1). | Diganti di setiap port; grep di B1 untuk `class="...collapse` di halaman Tailwind. |
| R4 | Skala gray Metronic ≠ Tailwind, sehingga warna teks bergeser diam-diam. | Aturan §4.1; review screenshot fokus pada warna teks. |
| R5 | Livewire + Alpine dobel. | §3.4; halaman `produk` diuji di B0 dan B2. |
| R6 | DataTables kehilangan markup bs5 dan `erp-collections.js` patah. | §6.1; test JS untuk `erp-collections` ditambahkan di B0. |
| R7 | Uang: POS payment, delivery order payment, arus kas, wholesale. | POS di B5 (paling akhir). Test kontrak uang tidak boleh diubah maknanya, hanya selector. Smoke test alur uang manual (§8.3). |
| R8 | Build di server gagal: Node tidak ada atau versinya tidak cocok. | B0 menulis langkah deploy + versi Node minimum di README dan memverifikasinya di server **sebelum** PR B0 di-merge. Fallback: commit `public/build` sementara. |
| R9 | Cache: `asset()?v=filemtime` vs manifest Vite. | Aset Vite memakai hash otomatis. File di `public/assets` yang belum dipindah tetap memakai `filemtime`. |
| R10 | Branch UI lain di `main` terus menambah markup Bootstrap. | Test ratchet §7.3 dan aturan di `AGENTS.md` mulai B1. |
| R11 | `ui/produksi-reference` / `ui/stock-opname-reference` konflik dengan B4. | Q14: kedua branch masuk `main` dulu, B4 dibuat setelahnya. |
| R12 | View dompdf (`chat::srrd`) dan Excel ikut terkena perubahan. | Dikecualikan (Q15). Test memastikan view tersebut tidak memuat `@vite`. |
| R13 | Breakpoint lg/xl bergeser (§4.1). | Screenshot di 1024 dan 1280 selain 390 dan 1440. |
| R14 | Halaman yang dikira mati ternyata masih dipakai (referensi dinamis, bookmark user, route di `.temp`). | Sebelum menghapus: cek route (`php artisan route:list`), cek log akses bila ada, dan **konfirmasi ke pemilik repo** dengan daftar per batch. |
| R15 | PHP lokal default 8.3 (repo butuh ^8.5). | Jalankan dengan `/opt/homebrew/opt/php@8.5/bin/php`. |

### 7.2 Audit class dinamis (B0)

1. `grep` konkatenasi class di `public/assets/js/*.js` (non-bundle), `<script>` inline Blade, dan PHP (`'badge-' .`, `"text-{$x}"`, template literal `` `bg-${...}` ``).
2. Untuk setiap temuan: ganti dengan map class lengkap (`{success: 'erp-badge--success', ...}`), atau tambahkan ke `safelist` bila tidak bisa diubah di B0.
3. Bandingkan CSS: render 20 halaman `erp-tailwind` + sampel `template.root`, kumpulkan `document.querySelectorAll('[class]')` → set class unik, lalu cek setiap class ada di CSS compiled. Script ini disimpan di `scripts/tailwind-class-audit.js` dan dijalankan ulang di setiap batch.

### 7.3 Test ratchet "tidak ada Bootstrap baru"

`tests/Feature/NoLegacyUiMarkupTest.php` (B1):

- Memindai semua `*.blade.php` dan PHP backend untuk penanda: `data-bs-`, `data-kt-`, `\bki-(duotone|outline|solid)`, `\bbi-`, `\bfa[srb]? fa-`, `form-control`, `form-select`, `btn btn-`, `class="[^"]*\bmodal\b`, `select2`, `bootstrap\.`, `KT[A-Z]`, `@extends('template.root')`.
- Membandingkan dengan `tests/fixtures/legacy-ui-allowlist.php` (path → jumlah hit maksimum).
- **Gagal** bila ada file baru dengan penanda, atau jumlah hit sebuah file **naik**.
- Setiap batch menurunkan atau menghapus entri allowlist. Di akhir B6 allowlist kosong dan file fixture dihapus.

---

## 8. Prosedur verifikasi

### 8.1 Lingkungan lokal

```bash
cp ../../../.env .env   # sudah dilakukan; tidak di-commit
/opt/homebrew/opt/php@8.5/bin/php /opt/homebrew/bin/composer install
npm ci
npm run dev             # atau npm run build
/opt/homebrew/opt/php@8.5/bin/php artisan serve
```

DB: `infruity_clone` di MySQL lokal. Akun login memakai user dari data dummy yang ada (tidak ditulis di repo).

### 8.2 Resep port per halaman (B1–B5)

1. **Sebelum**: screenshot di 390, 1024, 1280 dan 1440 px untuk state kosong, berisi, modal/sheet terbuka, error validasi, dan loading DataTables. Disimpan di scratchpad, tidak di repo.
2. Ubah `@extends('template.root')` → `@extends('layouts.erp')`.
3. Ganti markup sesuai §4 dan komponen §5.
4. Ganti JS: `bootstrap.Modal`/`.modal()` → API dialog (atau shim §5.3 bila skripnya besar; dicatat), `select2` → `erp-picker`, `data-kt-*` → komponen.
5. HTML controller untuk halaman itu → partial/komponen (§6.3).
6. Ikon → Phosphor (§4.3).
7. Perbarui test pengunci (kolom Lampiran A): selector boleh berubah, **perilaku yang dikunci tidak boleh hilang**.
8. **Sesudah**: screenshot pada state yang sama; bandingkan. Selisih yang disengaja (mengikuti referensi) dicatat di PR.
9. Turunkan atau hapus entri allowlist ratchet dan centang Lampiran A.

### 8.3 Gerbang setiap PR batch

- [ ] `php artisan test` hijau
- [ ] `node --test tests/js` hijau
- [ ] `npm run build` tanpa warning content/safelist
- [ ] Audit class (§7.2) tidak menemukan class hilang
- [ ] Screenshot sebelum/sesudah semua halaman batch dilampirkan atau diringkas di PR
- [ ] Smoke test alur yang relevan, sesuai modul batch:
  - Master: CRUD produk, pelanggan, staff
  - Transaction: pengadaan → terima → transfer → sortir → produksi → stok opname
  - POS: transaksi kasir → pembayaran → struk cetak → delivery order + pembayaran → arus kas → order book
- [ ] Konsol browser bersih (tidak ada error JS) di semua halaman batch
- [ ] Perubahan backend (controller HTML, route) disebutkan di deskripsi PR

---

## 9. Rencana per batch

### B0 — Fondasi build (branch ini)

Tujuan: mengganti **mesin**-nya tanpa mengubah tampilan sedikit pun.

1. Baseline:
   - `composer install`, `npm ci` (dengan `package.json` lama)
   - jalankan semua test PHP + JS, catat hasilnya di PR (merah yang sudah ada dicatat, bukan diperbaiki diam-diam)
   - screenshot baseline semua route GET yang me-render view (dari `php artisan route:list`)
2. Audit B0:
   - class dinamis (§7.2)
   - opsi DataTables per halaman (`dom`, extensions, ada/tidaknya `buttons`)
   - siapa yang memakai `axios`/`lodash` (dari Mix)
   - ketersediaan Node di server (R8)
3. `package.json` baru:
   - hapus `laravel-mix`, `bootstrap`, `popper.js`, `sass`, `sass-loader`, `resolve-url-loader`, `vue-template-compiler`, `cross-env`, serta `axios`/`lodash` bila tidak terpakai
   - tambah `vite`, `laravel-vite-plugin`, `tailwindcss@^3.4`, `postcss`, `autoprefixer`, `@tailwindcss/forms`, `alpinejs`, `@alpinejs/collapse`, `@alpinejs/focus`, `jquery`, `datatables.net@1.13` + extensions §6.1, `sweetalert2`, `flatpickr`
   - script `dev`, `build`, `test:js`
4. `vite.config.js`, `postcss.config.js`, `tailwind.config.js`, `tailwind.legacy.config.js`, `resources/css/app.css`, `resources/css/legacy.css`, `resources/js/app.js` (§3).
5. Ganti CDN:
   - `layouts.erp-tailwind`, `auth/login` dan `landing` → `@vite(['resources/css/app.css', 'resources/js/app.js'])`
   - `template.root` → `@vite(['resources/css/legacy.css', ...])`
   - hapus `<script src="https://cdn.tailwindcss.com">` dan `tailwind.config` inline
   - CSS inline sidebar di `erp-tailwind` dipindah ke `app.css` `@layer components`
6. Alpine: dari CDN ke bundle (§3.4). jQuery, SweetAlert2 dan DataTables di `erp-tailwind` dipindah ke bundle npm; `template.root` **tetap** memakai `plugins.bundle` sampai halamannya diport.
7. Hapus `webpack.mix.js`, `resources/sass/`, `resources/js/bootstrap.js` dan `mix-manifest.json` **setelah** memastikan `css/app.css`/`js/app.js` (Mix) hanya dipakai `layouts.app`. Jika `layouts.app` masih hidup, tunda ke B1.
8. `.gitignore`: tambah `/public/build`.
9. README: bagian "Build frontend" (versi Node minimum, `npm ci && npm run build` saat deploy) + `scripts/tailwind-class-audit.js`.
10. Gerbang B0: screenshot setelah = baseline (toleransi hanya anti-aliasing font), semua test sama dengan baseline, halaman `produk` (Livewire) berfungsi.

Ukuran: M. Risiko utama: R1, R5, R8.

### B1 — Shell, layout final, komponen, auth, errors, landing

1. `layouts/erp.blade.php` (dari `erp-tailwind` + bagian `template.root` yang masih perlu: firebase, `template.notif`, `erp-chrome`) dan `layouts/guest.blade.php`. `layouts.erp-tailwind` menjadi alias (`@extends('layouts.erp')`) sampai 20 view-nya dialihkan di batch ini.
2. Bangun komponen §5 + test-nya + `legacy-bridge.js` (§5.3).
3. Bersihkan komponen `x-erp.*` yang ada dari class Bootstrap (`topbar` 35 hit, `sidebar` 22, `detail-dialog`, `dropdown-select`, `mobile-navbar` memakai bs-js).
4. Dark mode dibuang: toggle di `settings/index`, skrip theme di `template.root`, `data-bs-theme`.
5. Port: `dashboard` (81 hit), `change-password`, `forgot-password`, `auth/login`, `errors/*`, `landing` (lepas dari `KT*`).
6. Audit kandidat mati B1 (`template/*` Metronic lama, `auth/passwords/*`, `auth/verify`, `auth/register`, `auth/footer`, `auth/login-option`, `layouts.app`, `*::layouts.master`, `admin-view`, `pemohon-view`, `template - Copy/`). Kirim daftar ke pemilik repo, lalu hapus setelah disetujui. Ini juga mengakhiri Mix bila tertunda dari B0.
7. Test ratchet §7.3 + aturan di `AGENTS.md` dan `docs/ui-design-system.md` (Q19).
8. Perbarui `docs/ui-design-system.md`: komponen baru, class `erp-*`, dan aturan pemetaan.

Ukuran: L. Ini batch dengan desain paling banyak, dan semua batch berikutnya bergantung padanya.

### B2 — Master (42 view)

Halaman utama: products (index sudah Tailwind; `create`, `stock-show` 108 hit; `edit` kandidat mati 215 hit + Quill + `KTImageInput`), customer (+ overlay, membership kandidat mati), staff (+ overlay), supplier, branch, position, unit, location, department, handling, kurir, category, serta `partials/mobile-index` (dikunci 6 test). Ditambah Livewire `produk`/`produk-form`/`product-table`.

Backend: `ProductController` (60 class), `StaffController`, `CustomerController`, `SupplierController`, `ProductUnitController`, `PositionController`, `LocationController`, `HandlingController`, `DepartmentController`, `KurirController`, `ProductCategoryController`.

Keputusan di awal B2: nasib Livewire (§3.4).

### B3 — Report, CRM, Chat, admin/settings/user (52 view)

Report hub dan semua laporan (`ReportController` 57 class; `report-mobile.js` 703 baris), CRM (dashboard + ApexCharts, tier + image input, deposito, campaign, report customer `data-kt-check`, setting-exp kandidat mati), Chat (kecuali `srrd` dompdf), `admin/role`, `admin/user`, `user`, `menu`, `role-menu`, `settings`, `list_permohonan`, `qrcode`.

### B4 — Transaction (74 view)

**Prasyarat**: `ui/produksi-reference` dan `ui/stock-opname-reference` sudah di `main`. Hitung ulang inventaris setelahnya.

Halaman paling berat: `stock-opname/index` (422 hit BS + 130 KT, `KTDrawer`, `KT*`, select2, 3 test kontrak), `production/process-parcel` (173, `data-kt-ecommerce`), `wholesale/create|show|edit` (132/106/110), `pengadaan/index2`, `stock-out`, `stock-out-type`, `receipt`, `transfer`, `sortir`. Ganti `toastr` di `production/js-create`.

Kandidat mati terbanyak ada di sini (22): versi lama `pengadaan/*`, `transfer/index|show|segment/*`, `sortir/index`, `wholesale/edit`, `*.temp`, `ProductionController_temp.php`.

Backend: `WholesaleController`, `ProductionParcelController`, `ProductionController`, `TransferController`, `ProductReceiptController`, `StockOutController`, `SortirController`, `StockOutTypeController`.

### B5 — POS (50 view)

Paling akhir karena menyangkut uang. Halaman:
- `pos/create2` + `segment/*` + `js/*`
- `pos/payment` + `js-payment` + `payment-success2`
- `delivery-order/index` (97 hit BS + 39 KT; `delivery-order-mobile.js` 727 baris; 3 feature test + 3 test JS)
- `order-book/*`
- `expenditure`, `otherbook`, `arus-kas`, `setting-nota` (UI pengaturannya saja; tampilan nota dikecualikan)

`pos/receipt*`/`print*` hanya dicek bebas Bootstrap (Q15). Kandidat mati: `pos/index` (BS5 CDN), `pos/create`, `pos/payment-success`, `pos/print`.

Backend: `DeliveryOrderController`, `OrderBookController`, `PosController`, `OtherBookController`, `ExpenditureController`.

Gerbang tambahan: `PosPaymentIdempotencyTest`, `PosPaymentUiContractTest`, `pos-payment-attempt.test.cjs` dan `DeliveryOrderMobilePaymentTest` maknanya **tidak berubah**. Smoke test uang dilakukan dua kali: oleh Claude di lokal, lalu oleh pemilik repo.

### B6 — Cleanup

1. Hapus `template/root.blade.php` dan seluruh `template/*` yang tersisa, `tailwind.legacy.config.js`, `resources/css/legacy.css`, `legacy-bridge.js`, dan `layouts.erp-tailwind` (alias).
2. Hapus aset Metronic:
   - `public/assets/css/style.bundle.css`
   - `public/assets/plugins/global/`, `public/assets/plugins/custom/datatables/`
   - `public/assets/js/scripts.bundle.js`, `widgets.bundle.js`, `public/assets/js/custom/` (133 file demo)
   - aset Keenicons/BS Icons/FontAwesome
   - `public/jquery.js` bila tidak dipakai
   - `public/css/app.css`, `public/js/app.js` (Mix)
3. Hapus adapter Bootstrap/select2 di `erp-theme.css` ("token Bootstrap menjadi jembatan") dan jembatan select2 di `erp-picker.js`.
4. Pindahkan CSS/JS kustom yang tersisa di `public/assets` ke `resources/` (Vite), atau dokumentasikan alasannya bila tetap di sana.
5. Gerbang grep = 0 untuk semua penanda §7.3 di seluruh repo (kecuali view print/pdf yang memang tidak memakainya). Allowlist dan fixture ratchet dihapus; test ratchet tetap ada dengan allowlist kosong.
6. Perbarui `docs/ui-design-system.md`: hapus bagian "jembatan Bootstrap".

---

## 10. Rollback

- Setiap batch adalah satu PR yang bisa di-revert tanpa memengaruhi batch sebelumnya.
- Aset Metronic tidak dihapus sampai B6, jadi revert batch mana pun di B1–B5 mengembalikan halaman ke `template.root` yang masih berfungsi.
- B0 bisa di-revert utuh: CDN kembali, `package.json` lama kembali.

## 11. Yang harus dicek di awal B0 (belum diketahui)

1. Baseline test hijau/merah.
2. Node di server production: ada atau tidak, versinya, dan siapa yang menjalankan deploy.
3. Apakah ada tombol export DataTables (JSZip/pdfmake) yang terlewat oleh grep.
4. `RegisterController@showRegister` tidak memanggil `view()`. Cek apakah route `/register` hidup atau rusak (memengaruhi `auth.register`).
5. Apakah `produk`/`produk-form` (Livewire) masih dipakai user.

---

## Lampiran A — Checklist per view

Legenda: `[ ]` belum, `[x]` selesai, `[-]` dikecualikan. Layout: `root` = `template.root`, `tw` = `layouts.erp-tailwind`, `partial` = include/komponen/standalone. BS/KT = indikator kepadatan (§2.2). "⚠ kandidat mati" = 0 referensi statis di `app/`, `routes/`, `Modules/` dan `resources/views`; wajib dikonfirmasi sebelum dihapus (R14). `template - Copy/` tidak didaftar (dihapus di B1).

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
| [ ] | `Master/customer/show.blade.php` | root | 0 | 0 | - | - |  |
| [ ] | `Master/department/index.blade.php` | root | 42 | 27 | DT, Swal, bs-js | - |  |
| [ ] | `Master/handling/index.blade.php` | root | 42 | 27 | DT, Swal, bs-js | - |  |
| [ ] | `Master/index.blade.php` | master::layouts.master | 0 | 0 | - | - |  |
| [ ] | `Master/kurir/index.blade.php` | partial | 3 | 0 | - | - |  |
| [ ] | `Master/layouts/master.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Master/location/index.blade.php` | root | 49 | 27 | DT, Swal, bs-js | - |  |
| [ ] | `Master/partials/mobile-index.blade.php` | tw | 139 | 0 | Swal | BranchOverlayFields, CustomerEditOverlay, PositionDrawerSpacing, SupplierDetailUi, SupplierRegionFields, kurir-drawer(js), staff-edit-overlay(js) |  |
| [ ] | `Master/payment-method/index.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Master/position/index.blade.php` | partial | 3 | 0 | - | PositionDrawerSpacing |  |
| [ ] | `Master/products/create.blade.php` | tw | 2 | 0 | select2 | - |  |
| [ ] | `Master/products/edit.blade.php` | root | 215 | 24 | select2 | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Master/products/index.blade.php` | tw | 146 | 11 | select2, DT, Swal | - |  |
| [ ] | `Master/products/partials/create-form.blade.php` | partial | 77 | 2 | select2 | - |  |
| [ ] | `Master/products/partials/create-script.blade.php` | partial | 52 | 14 | select2, DT, Swal | - |  |
| [ ] | `Master/products/show-transaction.blade.php` | root | 21 | 0 | DT | - |  |
| [ ] | `Master/products/stock-show.blade.php` | root | 108 | 26 | select2, DT, flatpickr | - |  |
| [ ] | `Master/products/stock.blade.php` | root | 19 | 2 | - | - |  |
| [ ] | `Master/staff/create-overlay.blade.php` | partial | 12 | 0 | - | UserStaffLink |  |
| [ ] | `Master/staff/create.blade.php` | tw | 6 | 0 | - | - | ⚠ kandidat mati (0 referensi) |
| [ ] | `Master/staff/edit-form.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Master/staff/edit-overlay.blade.php` | partial | 17 | 0 | - | staff-edit-overlay(js) |  |
| [ ] | `Master/staff/form-fields.blade.php` | partial | 59 | 0 | - | - |  |
| [ ] | `Master/staff/index.blade.php` | partial | 0 | 0 | - | - |  |
| [ ] | `Master/staff/js-create.blade.php` | partial | 0 | 0 | - | staff-edit-overlay(js) |  |
| [ ] | `Master/staff/js-edit.blade.php` | partial | 0 | 0 | - | staff-edit-overlay(js) |  |
| [ ] | `Master/staff/show.blade.php` | root | 0 | 0 | - | - |  |
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
| [ ] | `Modules/Master/Http/Controllers/LocationController.php` | 13 | 10 | B2 |
| [ ] | `app/Http/Controllers/UserController.php` | 12 | 11 | B3 |
| [ ] | `app/Http/Controllers/P_role.php` | 12 | 11 | B3 |
| [ ] | `Modules/Master/Http/Controllers/HandlingController.php` | 11 | 8 | B2 |
| [ ] | `Modules/Master/Http/Controllers/DepartmentController.php` | 11 | 8 | B2 |
| [ ] | `Modules/Crm/Http/Controllers/CampaignController.php` | 10 | 9 | B3 |
| [ ] | `Modules/Master/Http/Controllers/KurirController.php` | 9 | 8 | B2 |
| [ ] | `Modules/Transaction/Http/Controllers/StockOutTypeController.php` | 8 | 7 | B4 |
| [ ] | `Modules/Master/Http/Controllers/ProductCategoryController.php` | 8 | 7 | B2 |
| [ ] | `Modules/Master/Http/Controllers/PaymentMethodController.php` | 8 | 7 | B2 |
| [ ] | `Modules/Master/Http/Controllers/BranchController.php` | 8 | 7 | B2 |
| [ ] | `Modules/Master/Http/Controllers/AccountController.php` | 8 | 7 | B2 |
