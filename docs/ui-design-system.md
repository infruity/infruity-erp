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
- Kontrol utama 44px; radius input/button 12px; panel 16px. Button pill pada Master tetap mengikuti referensi. Padding panel legacy 24px desktop / 20px mobile; gap 8/12/16px.
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
