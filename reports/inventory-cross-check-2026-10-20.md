# Cross-check Inventaris: frontend, backend, dan database

## Kesimpulan

**BELUM SELESAI END-TO-END.** Daftar Sortir sudah terhubung dan UI telah diperbarui, tetapi alur tulis, kontrol akses record, struktur DB lokal, serta beberapa alur Transfer/Stock Opname belum layak ditutup sebagai selesai.

Scope: audit utama Sortir (list/create/edit/show/save/delete dan sumber stok), ditambah pemeriksaan terarah Transfer, Stock Opname, dan Stok Barang. Ini bukan audit menyeluruh seluruh ERP.

Metode: inspeksi source dan route; SELECT/SHOW dalam transaksi READ ONLY pada database aplikasi `infruity_clone`; GET terautentikasi menggunakan akun utama; inspeksi state frontend; tes otomatis pada SQLite in-memory. Tidak ada POST simpan transaksi, DELETE, migration, perbaikan schema, atau perubahan data bisnis dilakukan. File laporan ini adalah deliverable audit, bukan implementasi perbaikan.

## Temuan prioritas

### P1 — Struktur database Sortir tidak memiliki primary key/auto-increment

Bukti runtime DB:
- `SHOW INDEX FROM sortir_transaction` dan `sortir_transaction_detail` keduanya kosong.
- Kolom `id` kedua tabel: `Key=''`, `Extra=''`; tidak ada AUTO_INCREMENT.
- Tidak ada foreign key pada kedua tabel.
- ID existing masih unik: 110/110 header dan 815/815 detail; belum ditemukan ID nol.

Dampak: asumsi Eloquent tentang ID hasil insert tidak didukung schema. Insert baru berisiko gagal atau menghasilkan identitas salah, bergantung SQL mode. Tidak diuji dengan insert pada data utama. Perbaikan harus diawali backup dan preflight keunikan ID, bukan langsung mengubah schema.

### P1 — Produk hilang menyebabkan edit Sortir parsial dan keranjang duplikat

Bukti DB: 12 detail mengacu ke produk yang tidak ditemukan, tersebar pada 12 header.
Contoh runtime `/sortir/29/edit`, invoice `SOR202607013`:
- DB memiliki 9 detail, total header Rp442.060,08.
- Produk ID 706 tidak ditemukan.
- State frontend hanya memuat tiga produk pertama lalu mengulangnya: `[834,764,780,834,764,780]`; total tampilan Rp769.132,68.
- Pilihan cabang tetap kosong.

Source: `Modules/Transaction/Resources/views/sortir/js/js-product.blade.php:856–882` membaca `item.product.name` dan `item.product.unit.abbreviation` tanpa null guard, serta melakukan push sebelum seluruh data selesai. `init():58–66` mengeset `_loaded` setelah pemuatan selesai; template `sortir/create.blade.php:101` juga memanggil `init()` melalui `x-init` selain lifecycle Alpine. Jika pemuatan melempar error, guard belum disetel dan pemuatan ulang menduplikasi bagian awal. Inisialisasi cabang berada setelah loop sehingga tidak tercapai.

Dampak: jika disimpan, detail yang tidak berhasil dimuat berisiko terhapus oleh mekanisme `whereNotIn` backend. Tidak dilakukan penyimpanan untuk membuktikan perubahan destruktif.

### P1 — Tanggal transaksi existing tidak dipulihkan pada edit

`sortir/create.blade.php:124` selalu memakai tanggal saat render; `sortir/js/js-product.blade.php:856–890` tidak mengisi kembali tanggal existing. Save membaca input itu (`:668–675,724–735`), lalu backend memperbarui `date` (`SortirController.php:193`).

Risiko: menyimpan edit transaksi lama dapat mengganti tanggal bisnisnya.

### P1 — Respons gagal save dianggap jalur selesai oleh frontend

`sortir/js/js-product.blade.php:692–701,749–752` memanggil `res.json()` kemudian langsung redirect tanpa memeriksa `res.ok` maupun payload `success`.

Bukti source: JSON validation error 422 maupun JSON failure 500 tetap melewati redirect. Tidak diuji dengan submit data utama. Harus mempertahankan input, menampilkan error validasi, dan mengembalikan state loading.

### P1 — Otorisasi record/cabang dan finalisasi Sortir belum ditegakkan di server

`Modules/Transaction/Http/Controllers/SortirController.php`:
- `show:95`, `edit:113`, `destroy:147` memuat ID tanpa scope cabang pengguna.
- `saveTransaction:170–178` hanya memvalidasi keberadaan branch, bukan hak pengguna atas branch tersebut.
- `:188–197` meng-update berdasar invoice tanpa memeriksa status final existing.
- `:145–150` tidak menerapkan aturan umur/status yang ditampilkan action list (`:462–483`).

Permission endpoint tetap ada untuk metode-metode tersebut; masalahnya pembatasan record/cabang dan lifecycle, bukan akses anonim. Tidak dilakukan eksploitasi dengan akun terbatas. Query daftar masih memiliki filter cabang.

### P1 — Identitas update dan validasi payload Sortir tidak aman

`SortirController.php:188–189` memakai invoice sebagai satu-satunya kunci updateOrCreate. Generator `Entities/Sortir.php:42–59` tidak mereservasi nomor dengan lock. Dua form create bisa membawa preview invoice sama; request berikutnya dapat memperbarui transaksi pertama. Risiko concurrency belum diuji dengan request paralel.

Validasi `SortirController.php:170–178` tidak memiliki `items.*`; ID hanya diuji numeric di loop (`:216`). Quantity/harga/subtotal langsung diterima dari client (`:228–232`), total header tidak direkalkulasi. Quantity negatif pada final berisiko membalik pengurangan menjadi penambahan stok. Data existing yang diperiksa tidak memiliki quantity nonpositif/harga negatif/subtotal negatif, dan total headernya konsisten; ini tidak meniadakan celah validasi request baru.

Update juga mengganti UUID, created_by, dan created_at (`:191,196,204`), bukan mempertahankan metadata pembuatan asli.

### P1 — State Transfer FE tidak sejalan dengan lifecycle backend

- Submit `transfer/js/js-product.blade.php:820–830` mengirim `pending`.
- Tombol penerima Diterima hanya muncul bila status `proses` pada `transfer/segment/ringkasan.blade.php:11–28`.

Ini mismatch source yang perlu diuji dan diperbaiki sebelum menyatakan alur kirim-terima selesai. Tidak membuat transfer baru pada DB utama.

### P1 — Tujuan tambah Stock Opname mobile menghasilkan HTTP 500

GET terautentikasi `/stock-opname/create` menghasilkan HTTP **500**, dengan pesan `View [create] not found`.

Navbar menunjuk route create; handler `StockOpnameController.php:57–63` meminta `transaction::create` yang tidak tersedia. Jalur tambah di list menggunakan modal; shortcut mobile belum diarahkan ke alur yang sesuai.

### P2 — Migration ledger tidak sesuai schema existing

`php artisan migrate:status` menampilkan migration `2025_10_06_215554_create_sortir_transaction` sebagai Pending, sementara tabel header/detail nyata sudah tersedia. Hal serupa teramati untuk tabel transfer/user_branch dan migration pembuatannya.

Jangan menjalankan `migrate` secara membabi buta atau menandai migration sebagai ran tanpa membandingkan schema. Perlu rekonsiliasi ledger/schema dan prosedur repeatable untuk clone/fresh installation.

### P2 — Tanggal list dan tanggal filter berbeda makna

Backend memfilter kolom `date` (`SortirController.php:413–414`) tetapi list menampilkan `created_at` (`:444`). Di DB ada 77 header dengan `date <> DATE(created_at)`. Pengguna dapat melihat tanggal yang tidak sesuai rentang filter yang dipilih. Putuskan label/tanggal bisnis yang konsisten.

### P2 — List permission dan navigasi detail belum lengkap

`get_data():407–409` tidak memanggil `requireAccess`, sedangkan route AJAX hanya menggunakan auth. User login tanpa izin menu berpotensi tetap membaca daftar cabang yang dimilikinya; belum diuji dengan role terbatas.

Link invoice selalu menuju edit (`SortirController.php:438`), bukan show; menu tidak menyediakan Lihat. Create/edit/show masih memakai `template.root`, berbeda dari list yang sudah memakai `layouts.erp-tailwind`. Ini pekerjaan FE tersisa, bukan seluruh modul yang sudah konsisten.

## Temuan lanjutan dari review source, belum uji runtime khusus

- Legacy `sortir/save-stock` aktif, membaca product_stock tanpa cabang, dan menulis StockIn/StockOut terpisah dari header Sortir. View memberi posting legacy branch_id 0. Perlu keputusan apakah endpoint ditutup atau dimigrasikan; jangan menyamakan perilakunya dengan saveTransaction.
- Review menemukan sink HTML tanpa escaping pada warning stok Sortir/Stock Opname dan detail-name Stock Opname. Perlu perbaikan DOM-safe dan pengujian XSS pada fixture terisolasi, bukan payload pada data utama. Referensi: `sortir/index2.blade.php:225,240–241`; `stock-opname/index.blade.php:1868–1874,2407–2417`.
- Filter Transfer memakai paid/Final sedangkan state backend menggunakan pending/proses/selesai; beberapa aksi FE juga ditampilkan walaupun backend menolaknya. Referensi `transfer/index2.blade.php:93–97`, `TransferController.php:559–564,602–613`.
- Stock Opname selesai masih menampilkan kontrol perubahan yang ditolak server; label Simpan Final tidak sama dengan status pending yang dibuat. Review source, belum diuji melalui transaksi baru.
- Permission shortcut mobile Transfer memakai transfer.create, handler memakai transfer-pengirim.create; perlu role-based test, terutama non-admin.
- Clear rentang tanggal Transfer/Stock Opname perlu menyelaraskan nilai input dan instance Flatpickr, bukan hanya mengosongkan teks.

## Hasil yang sudah baik

- DB nyata: 110 header (semuanya paid), 815 detail.
- Tidak ada detail tanpa header, header tanpa pembuat, atau header tanpa cabang.
- Tidak ada invoice duplikat, pasangan sortir/product duplikat, header tanpa detail, atau selisih total header dengan subtotal detail pada pemeriksaan saat audit.
- Endpoint GET list/create/show/edit Sortir, Stok Barang, list Stock Opname, transfer-pengirim, dan transfer-penerima menghasilkan 200 saat diperiksa dengan akun utama. HTTP 200 tidak menjamin script/data edit bebas error, sebagaimana contoh ID 29.
- `/transfer` menghasilkan 404, tetapi ini bukan bug navigasi yang terkonfirmasi: route sah adalah `/transfer-pengirim` dan `/transfer-penerima`.
- Daftar Sortir telah diuji dengan data nyata, pagination, parameter filter, dan viewport mobile tanpa overflow pada pekerjaan UI sebelumnya.
- Stok Sortir memang dihitung dinamis dari transaction_stock/transaction_stock_show untuk paid/debt. Tidak adanya insert StockOut di saveTransaction bukan bug dengan sendirinya. Mengubah status final/hapus akan mengubah kontribusi stok, sehingga lifecycle guard penting.

## Pengujian otomatis

- `php artisan test --filter=ExampleTest`: 2 passed, 2 assertions. Ini smoke test dasar, bukan tes transaksi inventaris.
- `php artisan test --filter=TransferConcurrencyTest`: 4 failed, 0 assertions karena setup SQLite in-memory tidak memiliki tabel users. Bukan bukti keempat aturan bisnis salah; harness belum siap mengujinya.
- PHPUnit juga memberi warning schema XML deprecated.
- Belum ada test Sortir khusus yang ditemukan dalam direktori tests.
- Tidak dilakukan create/finalize/update/delete transaksi nyata untuk menghindari perubahan saldo stok dan data bisnis. Pengujian mutasi perlu database testing ber-schema benar dan fixture yang terisolasi.

## Urutan penanganan yang disarankan

1. Backup DB dan rekonsiliasi schema/ID/auto-increment/index serta ledger migration. Jangan mengubah ledger tanpa preflight.
2. Perbaiki edit Sortir: single initialization, null-safe historical products, tanggal/cabang existing, dan proteksi terhadap save parsial.
3. Tegakkan branch scope, permission endpoint data, lifecycle final, validasi nested items dan rekalkulasi total di backend.
4. Ganti identitas update menjadi ID transaksi eksplisit; generate invoice hanya di server saat create dengan strategi concurrency/unique constraint yang aman.
5. Perbaiki error handling save FE; jangan redirect pada 422/500.
6. Selaraskan status/action Transfer dan route tambah Stock Opname mobile; tutup sink HTML tidak aman.
7. Selesaikan konsistensi create/edit/show, filter, dan permission shortcut.
8. Siapkan DB testing yang benar, jalankan regression tests role/cabang, concurrency, create-draft-final, edit/delete, dan rekonsiliasi stok sebelum sign-off.

## Status penutupan

- UI list Sortir: diimplementasikan dan diverifikasi untuk skenario yang tercatat.
- FE seluruh Inventaris: BELUM SELESAI.
- Backend Sortir: PERLU PERBAIKAN prioritas tinggi.
- Database lokal: PERLU REKONSILIASI schema dan historical product references.
- End-to-end write/regression sign-off: BELUM LOLOS / BELUM DILAKUKAN secara aman.

Tidak ada temuan audit ini yang boleh dianggap sudah diperbaiki hanya karena sudah didokumentasikan.
