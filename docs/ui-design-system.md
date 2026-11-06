# Acuan UI Infruity ERP

25 Oktober 2026. Implementasi mengikuti HTML yang tersedia di infruity-frontend. File ini merekam nilai yang dipakai oleh adapter Laravel; bukan arah desain baru.

## Sumber desain

- Master/Produk, Pelanggan, Satuan Produk: shell, daftar, panel, input, avatar, aksi, drawer.
- Pengaturan/Pengaturan: primary hover, profil, aksi sekunder, ganti kata sandi.
- Laporan/Total Aset dan laporan shared: filter, status, ringkasan dan sheet.
- Login ERP: typography, identitas dan tombol login.
- Halaman referensi POS, produksi, dan beberapa inventaris masih WIP. Fungsinya dipertahankan dengan pola kontrol Master dan Pengaturan yang sudah tersedia.

## Token terpusat

Sumber runtime: public/assets/css/erp-theme.css. Token Bootstrap menjadi jembatan untuk komponen lama; utility Tailwind yang sudah sesuai referensi tetap dipakai.

| Fungsi | Nilai | Asal referensi |
|---|---|---|
| Primary action / sidebar | #0b595b | Master dan shell .dark-sidebar |
| Primary hover | #08484a | Pengaturan dan dock Pelanggan |
| Brand green | #0F5C45 | tailwind.config colors.primary |
| Selected navigation | #d1f36e | .sidebar-link.active |
| Accent | #F2B400 | colors.accent dan submenu aktif |
| Canvas / surface | #e5e9f3 / #ffffff | body dan panel Master |
| Surface muted | #f9fafb | gray-50 |
| Border / subtle | #e5e7eb / #f3f4f6 | gray-200 / gray-100 |
| Text / heading / muted | #1f2937 / #111827 / #6b7280 | gray-800 / gray-900 / gray-500 |
| Success / soft | #047857 / #ecfdf5 | emerald-700 / emerald-50 |
| Warning / soft | #b45309 / #fffbeb | amber-700 / amber-50 |
| Error / soft | #dc2626 / #fef2f2 | red-600 / red-50 |
| Info / soft | #2563eb / #eff6ff | blue-600 / blue-50 |

Nilai utility merujuk skala Tailwind yang dipakai referensi. Biru tetap sah untuk informasi, avatar bergilir, status pengiriman dan seri data; bukan primary action.

## Komponen dan state

- Plus Jakarta Sans untuk UI; Poppins untuk heading/identitas tertentu. Ukuran dasar HTML ERP 16px; isi 13–14px, label 12px, heading panel 16–20px. Tabel memakai angka tabular.
- Kontrol utama 44px; radius input/button 12px (`erp-action` memakai token `--erp-radius-control`); panel 16px. Pil (`rounded-full`) hanya untuk elemen yang memang dibuat pil, mis. kolom cari dan chip; tombol `erp-action` tidak lagi pil (diputuskan pemilik repo). Padding panel legacy 24px desktop / 20px mobile; gap 8/12/16px.
- Shadow card: 0 1px 3px rgba(0,0,0,.04), 0 6px 16px rgba(0,0,0,.04). Shadow soft: 0 10px 40px -10px rgba(0,0,0,.08).
- Primary teal; secondary putih/abu dengan border; destructive merah; disabled opacity dan cursor. Hover, active, focus-visible, spinner dan selection mengikuti fungsi tersebut.
- Focus-visible outline teal 2px dengan offset 3px. Error input merah, pesan terkait aria-describedby; tidak mengandalkan warna saja.
- Modal legacy dibatasi dvh dengan header/footer tidak menyusut dan isi yang scroll. Custom sheet memakai erp-overlays.js untuk containment, inert background, pemulihan fokus; Bootstrap/native dialog/SweetAlert mempertahankan lifecycle masing-masing.
- Dock diberi ruang bawah 104px + safe-area. Halaman yang memiliki action bar sendiri menonaktifkan dock melalui erp-hide-dock.
- Adapter plugin dibatasi .erp-ui + selector plugin, bukan mengganti CSS vendor: Select2, DataTables, Flatpickr, SweetAlert, tooltip, accordion, badge, tab, pagination, checkbox/radio dan range.
- Empty/loading/error tetap memiliki teks; riwayat laporan membedakan data kosong, akses ditolak, sesi berakhir dan kegagalan jaringan/server.
- Ikon mengikuti Phosphor/Material yang sudah dipakai referensi. Dua ikon inti dock memakai SVG setara untuk menghindari teks ligature ketika font terlambat.

## Batas pemakaian

Jangan mengganti semua biru secara global. Aset logo pembayaran pada landing, avatar, info, status shipping, seri grafik dan warna tier kartu adalah kategori terpisah. Nota thermal mempertahankan Courier New di isi struk untuk lebar cetak; kontrol layar memakai token UI. CSS demo Metronic yang tidak dimuat halaman bisnis tidak ditulis ulang.

Tidak ada !important baru pada adapter. Aturan penting vendor yang tidak dipakai tidak perlu dilawan dengan override global. Sumber default Bootstrap tetap disimpan utuh.

- [Master Produk](</Users/maaullntechicloud.com/Develop/sheetup-projects/infruity-frontend/003. ERP/002. Master/001. Produk/index.html>)
- [Master Pelanggan](</Users/maaullntechicloud.com/Develop/sheetup-projects/infruity-frontend/003. ERP/002. Master/005. Pelanggan/index.html>)
- [Pengaturan](</Users/maaullntechicloud.com/Develop/sheetup-projects/infruity-frontend/003. ERP/009. Pengaturan/002. Pengaturan/index.html>)
- [Login](</Users/maaullntechicloud.com/Develop/sheetup-projects/infruity-frontend/002. Login ERP/index.html>)

## Migrasi struktur, 26 Oktober 2026

`x-erp.workspace` menyusun heading mobile, toolbar pencarian/filter/aksi, isi, dan footer. `x-erp.collection` memiliki area tabel desktop dan daftar mobile. `erp-collections.js` membaca halaman DataTables yang sama: tidak membuat API kedua. Baris mobile membuka detail; aksi dan input meneruskan interaksi ke kontrol asli. Urutan, arah, jumlah per halaman, pagination, serta perubahan select tetap tersedia.

Form transaksi memakai `.erp-editor` dengan area informasi/item dan `.erp-editor-summary`. Keranjang menggunakan satu tombol baris per item untuk desktop/mobile, bukan dua pohon card yang berbeda. Dialog Bootstrap menggunakan struktur `.erp-sheet-dialog/header/body/footer`; lifecycle fokus Bootstrap tetap digunakan. Benefit tier tampil sebagai sheet, menggantikan editor pada child-row tabel.

Font Plus Jakarta Sans, Poppins, Material Symbols Rounded dan Phosphor dilayani lokal di `public/assets/fonts/infruity` serta `public/assets/plugins/phosphor`; sumber dan lisensi disertakan. Capture visual tahap kedua menunggu font tersedia serta transisi dialog selesai. Semua klaim keselarasan harus merujuk matriks halaman/state; keberadaan token atau wrapper baru saja tidak cukup.

## Komponen dan aturan penulisan (migrasi Tailwind, B1)

Ringkasan pemakaian. Rancangan lengkap dan alasannya ada di [tailwind-migration-plan.md](tailwind-migration-plan.md).

### Aturan

1. **Halaman baru memakai `layouts.erp`** dan komponen `x-erp.*`. Jangan menambah markup Bootstrap/Metronic (`form-control`, `btn btn-*`, `modal`, `data-bs-*`, `data-kt-*`, ikon `ki-`/`bi-`/`fa-`, `select2`). `tests/Feature/NoLegacyUiMarkupTest` gagal bila ada file baru yang memakainya atau jumlah di file lama naik. Setelah memigrasi halaman, perbarui batas: `php scripts/ui-migrate/ratchet-snapshot.php`.
2. **Ikon hanya Phosphor**, satu bentuk di Blade, JS, dan PHP: `<i class="ph ph-trash" aria-hidden="true"></i>`. Bobot: `ph` (biasa), `ph-bold` (aksi), `ph-fill` (aktif/terisi), `ph-duotone` (ilustrasi kecil). Tombol berisi ikon saja wajib `aria-label`.
3. **Jangan taruh dua utility dari kelompok yang sama di satu elemen** (`bg-white` + `bg-emerald-50`, `font-medium` + `font-bold`). Pemenangnya ditentukan urutan CSS, bukan urutan atribut. Untuk state aktif/tidak aktif, tulis keduanya di `:class` yang eksklusif: `:class="on ? 'border-teal' : 'border-gray-200'"`. Dijaga oleh `tests/js/class-conflicts-repo.test.cjs`; cek manual dengan `node scripts/ui-migrate/class-conflicts.mjs <path>`.
4. **Tulis nama class lengkap**, jangan disusun lewat konkatenasi (`'bg-' + warna`); scanner Tailwind tidak melihatnya. Pakai peta: `{ success: 'erp-badge--success' }`.
5. Warna memakai token (`bg-teal`, `text-teal`, `bg-lime`, `bg-canvas`), bukan hex (`bg-[#0b595b]`). Ukuran font px ad-hoc (`text-[11px]`) masih boleh.
6. HTML kolom DataTables dari controller dibuat dengan komponen (`x-erp.row-actions`, `x-erp.cell-title`, `x-erp.badge`, `x-erp.avatar`), bukan string PHP. Komponen meng-escape isinya.

### Ukuran teks dasar

`html` 16px (rem Tailwind dan referensi tetap akurat), `body.erp-app` (hanya `layouts.erp`) **13px / 1.5** lewat `text-body`. Login dan landing tetap 16px. `text-sm` = 14px, `text-xs` = 12px.

- Hanya teks yang **mewarisi** ukuran dari body yang berubah. Beri class ukuran eksplisit pada judul dan isi yang penting; jangan mengandalkan warisan.
- Ikon Phosphor tanpa ukuran eksplisit ikut mengecil jadi 13px. Beri `text-[16px]` (bukan `text-base`: itu juga menetapkan `line-height: 1.5rem` dan mengubah kotak ikon).
- Wadah yang hanya berisi `<span>` inline bertinggi baris dari font wadahnya (strut). Bila tinggi barisnya harus 24px, beri wadah `text-[16px]`.
- Input, select, textarea selalu 16px (anti-zoom iOS, aturan di `layouts.erp`).
- Halaman yang dipindah dari `template.root` memakai `codemod --convert-rem` agar ukuran px tetap sama (root lama 13px).

### Komponen

| Komponen | Pemakaian |
|---|---|
| `x-erp.dialog` | `<x-erp.dialog id="edit" title="Ubah" size="md\|lg\|xl\|full" variant="center\|sheet" static>` + slot `footer`. Buka: `data-erp-open="#edit"` atau `erp.dialog.open('edit')`. Tutup: `data-erp-close` atau `erp.dialog.close('edit')`. `<dialog>` native: fokus trap, Esc, dan pemulihan fokus ditangani browser. `static` = tidak tertutup klik latar/Esc. Event: `erp:dialog-open`, `erp:dialog-close`. |
| `x-erp.drawer` | Sama seperti dialog, panel dari kanan (di mobile penuh). |
| `x-erp.dropdown` | `label` (aria), `align="start\|end"`, slot `trigger`, isi berupa item `role="menuitem"` class `erp-menu__item`. Panel di-teleport ke `<body>` (tidak terpotong `overflow` tabel). Navigasi panah, Esc, klik luar. |
| `x-erp.row-actions` | `:actions="[['label'=>'Ubah','icon'=>'pencil-simple','onclick'=>'edit(1)','can'=>check_access('x.edit')], ...]"`. Item `can=false` tidak dirender; bila semua tersembunyi, tidak merender apa pun. `tone => 'danger'`, `href`, `data`. |
| `x-erp.tabs` + `x-erp.tab-panel` | `:tabs="[['name'=>'a','label'=>'Umum']]"`, panel `<x-erp.tab-panel name="a">`. Navigasi panah. |
| `x-erp.collapse` | `title` atau slot `trigger`, `open`. |
| `x-erp.check-all` | `target="#tabel"`; isi tabel memakai `<input class="erp-check" data-erp-check>`. Status `indeterminate` otomatis. |
| `x-erp.tooltip` | `text="..."`, membungkus pemicu yang bisa fokus. |
| `x-erp.panel` | `title`, slot `toolbar`, `footer`. |
| `x-erp.badge` | `tone="success\|warning\|danger\|info\|primary\|neutral"`. |
| `x-erp.avatar` | `name`, `src`, `tone`, `size="sm\|md\|lg"`. Tanpa `tone`, warna stabil dari nama. |
| `x-erp.cell-title` | `title`, `subtitle`, `href`. |
| `erp.toast({type, message})` | `success\|error\|warning\|info`; menggantikan toastr. |

Catatan Blade: di template inline, slot bernama (`<x-slot:x>`) yang langsung diikuti teks tanpa baris baru membocorkan output buffer; beri baris baru.

### Class form dan data

`erp-label` (+ `--required`), `erp-help`, `erp-error` (hubungkan lewat `aria-describedby`; set `aria-invalid="true"` pada kontrol), `erp-input`, `erp-select`, `erp-textarea`, `erp-check`, `erp-radio`, `erp-input-group` (+ `__addon`), `erp-table`, `erp-panel`, `erp-badge--*`, `erp-avatar--*`. Kontrol 44px, radius 12px, teks 16px di bawah 768px (mencegah zoom iOS). Tombol di Blade memakai `<x-erp.button variant="primary|danger" icon="plus" bold :href="…">teks</x-erp.button>` (`href` menghasilkan `<a>`, tanpa `href` menjadi `<button type=…>`; atribut lain diteruskan). Kelas `erp-action` (+ `--primary`, `--danger`) tetap dipakai untuk HTML yang dibangun JS/PHP.

### Alat bantu migrasi (`scripts/ui-migrate`)

- `codemod.mjs`: laporan dulu, `--write` untuk menulis, `--convert-rem` untuk halaman yang di-extend dari `template.root` (root 13px ke 16px). Hanya mengganti pasangan aman; bentrok nama (`collapse`, `card`, `btn`, `rounded`) dan komponen dilaporkan untuk dikerjakan manual. Ikon tanpa padanan membuat atribut dibiarkan utuh dan dilaporkan.
- `class-conflicts.mjs`: detektor tabrakan class.
- `ratchet-snapshot.php`: tulis ulang batas `tests/fixtures/legacy-ui-allowlist.json`.

### Pola halaman master sederhana dan sel DataTables

- Halaman daftar + dialog tambah/ubah yang bentuknya sama memakai partial `master::partials.simple-crud` (lihat `location/index.blade.php` sebagai contoh pemakaian). Dialog memakai `data-erp-create` supaya tombol + di navigasi mobile membukanya.
- HTML sel DataTables dari controller dirender lewat `App\Support\DataTableCells` (`title`, `rowActions`, `editDeleteMenu`), bukan string Bootstrap. `rowActions` selalu menyertakan salinan tersembunyi untuk daftar kartu mobile (`erp-collections.js`).
- SweetAlert di halaman yang memakai `x-erp.dialog` otomatis dirender di dalam dialog yang sedang terbuka (`resources/js/vendor.js`); tidak perlu opsi khusus. Tombolnya memakai `customClass: { confirmButton: 'erp-action erp-action--primary', cancelButton: 'erp-action' }` dengan `buttonsStyling: false`.
- Pemilih (select) dengan pencarian ajax: `<select class="erp-select">` + `ErpPicker.attach(select, { url, map, creatable?, ... })`; jangan memakai select2. `creatable: true` memungkinkan mengetik nilai baru. Overlay-nya ikut berada di dalam `<dialog>` yang sedang terbuka.
- Pemilih tanggal: `$(el).flatpickr({...})` (tersedia dari `vendor.js`); muat CSS-nya dengan `@push('styles') @vite('resources/css/flatpickr.css') @endpush`.
