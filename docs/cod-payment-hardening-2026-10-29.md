# Hardening pembayaran COD/POS — 29 Oktober 2026

Tindak lanjut **A03** pada [audit baseline](audit-2026-10-28.md), setelah [hardening cabang](branch-hardening-2026-10-29.md). Fokusnya kontrol akses pengiriman, satu kali penerimaan, dan satu implementasi pembayaran POS/COD. Proteksi source dan retry berurutan telah diuji; acceptance concurrency MySQL **belum dianggap selesai**.

## Kontrak endpoint pengiriman

Route tetap `PUT /delivery-order/set-selesai/{id}`, permission tetap `delivery-order.set-selesai`, dan sukses tetap JSON `{"success":true}`. Client tidak perlu mengirim tanggal, metode, cabang, atau idempotency key baru.

1. Permission/override diperiksa sebelum operasi; tanpa izin **403**.
2. Invoice diambil melalui cabang dari `UserBranch::getUserBranch()`, `ongkir > 0` seperti daftar delivery, dan `lockForUpdate()` dalam transaksi. Target hilang, soft-deleted, non-delivery, atau lintas cabang menghasilkan **404**.
3. `ongkir_status=delivered` menghasilkan **409**, tanpa menambah pembayaran, bahkan jika nominal retry berbeda. Ini **terminal-operation guard**, bukan replay hasil lama dengan request key.
4. Invoice canceled/state di luar draft/temp/pending/debt/paid ditolak **409**. Invoice paid hanya boleh diterima tanpa pembayaran positif; percobaan menarik uang lagi ditolak **409**.
5. Invoice belum lunas memerlukan nominal positif, kecuali permintaan eksplisit `nominal:0` dan voucher yang diperiksa server menutup seluruh sisa tagihan. Draft/temp/pending tetap didukung—jangan membatasi COD hanya ke debt karena caller dan daftar yang ada memang menyediakan alur draft.
6. Pembayaran, voucher, finalisasi produksi, status invoice, dan status diterima berada dalam transaksi yang sama. Pengecualian otorisasi/validasi/not-found diteruskan; kegagalan internal menghasilkan **500 + success:false**, bukan HTTP 200 dengan pesan DB mentah.

### Nominal dan metode

- `nominal` menerima rupiah bulat seperti `10000` atau `10.000` (termasuk JSON integer). Pemisah desimal, scientific notation, angka negatif, format grouping salah, array, prefix mata uang, serta nilai di luar batas penyimpanan ditolak **422**; tidak dibersihkan menjadi angka lain lewat stripping sembarang karakter.
- Kosong tidak boleh menutup invoice unpaid. Nol hanya dapat menyelesaikan invoice unpaid bila voucher menutup seluruh sisa tagihan; tanpa cakupan itu tetap 422 dan rollback. Untuk penerimaan invoice paid, nominal boleh tidak dikirim/nol.
- Batas COD 9.999.999.999.999 rupiah mengikuti kapasitas nominal bulat pada decimal(15,2). Ini bukan perubahan seluruh kontrak nominal POS.
- Untuk nominal positif, Cash dicari server berdasarkan nama `Cash` case-insensitive, bukan diasumsikan ID 1; konfigurasi tanpa cash menghasilkan **422**. Penyelesaian voucher-only COD tidak memerlukan metode Cash. Cabang ledger selalu mengikuti invoice, bukan input client atau hardcode cabang 1.
- Tanggal pembayaran COD berasal dari server. POS tetap memakai tanggal dan rincian metode tervalidasi pada adapter-nya.

## Satu action bisnis, bukan controller memanggil controller

`Modules/Pos/Services/CompletePayment.php` menerima model invoice yang **sudah diotorisasi dan dikunci**, baris pembayaran numerik ternormalisasi, tanggal, serta ID aktor. Pemanggil memiliki transaksi DB dan menangani response HTTP.

Action dipakai oleh `PosController::savePayment()` dan `DeliveryOrderController::setSelesai()` untuk:

- Mengambil nama metode otoritatif, membuat ledger dengan identitas/cabang invoice, dan mempertahankan format JSON rincian metode/nominal.
- Menghitung `existingPaid = SUM(total) − SUM(return)` dari pembayaran non-soft-deleted; header paid adalah **neto**, bukan gross.
- Menghitung tagihan setelah voucher dan paid existing; kelebihan hanya menjadi return pada pembayaran saat ini. Return historis tidak diberikan lagi. Pembayaran campuran/noncash tidak dapat membiayai kembalian tunai.
- Menyimpan remaining serta transisi paid/debt, dan menyelesaikan production draft milik invoice setelah pembayaran berhasil.
- Mengambil voucher deposito baru hanya bila `deposito_id` dan voucher invoice sama-sama kosong. Voucher invoice nonzero tetap dipertahankan tanpa menimpa nilainya atau mengambil voucher tambahan. Voucher deposito baru dikunci dan dibatasi antara nol dan sisa total setelah paid neto existing (nilai deposito negatif tidak boleh menambah tagihan tunai), kemudian ditandai `deposito_id/voucher_qty` dan dikurangi jumlahnya sekali; tidak mengembalikan pembayaran historis ketika voucher baru menutup sisa debt.
- Menjalankan pemeriksaan stok awal untuk draft/temp/pending, tetapi tidak mengulang pemeriksaan konsumsi awal pada penagihan debt.

Permission, lookup/lock invoice, guard COD, serta replay/remember POS tetap tanggung jawab adapter; action tidak menerima `Request`, memanggil controller lain, atau bergantung pada `PaymentSubmission`.

### Voucher menutup seluruh tagihan

Voucher sama dengan/melebihi total, atau yang menutup sisa debt, tetap melewati pemeriksaan stok awal dan finalisasi produksi yang relevan. POS menerima payload client existing berupa metode terpilih dengan `amount:"0"` dan `total_payment:0`; COD menerima `nominal:"0"`. Receipt ledger bernilai nol menjaga jejak settlement tanpa berpura-pura menerima uang. Jika client lama mengirim nominal tunai positif ketika voucher menutup tagihan, nominal itu dikembalikan seluruhnya pada receipt saat ini: paid neto tidak bertambah dan ledger historis tidak diubah.

Pada settlement pertama tanpa ledger pembayaran, voucher invoice yang sudah tersimpan tetap dapat menutup tagihan, baik `deposito_id` sudah terisi maupun masih kosong. Voucher yang sudah ditautkan tidak dikonsumsi lagi; metadata dan nominal yang ada dipertahankan. Invoice dengan ledger yang sudah settled tidak menerima pembayaran baru. Ini menjaga perilaku diskon tersimpan, bukan backfill atau rekonsiliasi data historis.

### Stok dan temuan tambahan saat ekstraksi

Pemeriksaan POS yang sudah ada dipertahankan untuk akumulasi produk/parent variant, kemasan dan bahan parcel, sisa jus dari stok jadi, serta bahan produksi. Product lock tetap diurutkan dan stok dibaca untuk cabang invoice.

Regression tambahan menemukan bahwa kuantitas produksi jus yang sama dikurangkan lagi pada setiap baris penjualan. Contoh fixture: 3 + 3 jus dijual, 3 diproduksi, stok jadi 0; kode sebelumnya menerima pembayaran padahal masih membutuhkan 3 jus jadi. Action sekarang menjumlah produksi per produk dan mengalokasikan setiap unit hanya sekali lintas baris penjualan. Kasus kekurangan ditolak/rollback, sedangkan dua produksi 3 + 3 untuk penjualan 3 + 3 tetap berhasil.

**Batas:** `product_stock` merupakan view pada aplikasi; fixture menggantikannya dengan tabel stok terkontrol untuk memeriksa perhitungan. Ini tidak memverifikasi SQL view, pengurangan stok fisik, maupun konsistensi seluruh writer stok. Urutan invoice → deposito/produk pada pembayaran juga belum menyelesaikan urutan berbeda di `saveTransaction()` dan writer lain. Jangan menyatakan semua deadlock/race sudah hilang.

## Perubahan UI

Pada Blade daftar delivery, kedua aksi lama (`setSelesai`, `setBayar`) tetap tersedia, dengan helper konfirmasi/submit bersama:

- Nominal divalidasi sebelum request, sesuai format rupiah bulat server. Input 0 dan petunjuk voucher-only tersedia; keputusan kecukupan voucher tetap di server, bukan dari perhitungan browser.
- Guard in-flight mencegah pengiriman ganda untuk ID yang sama dan dilepas pada callback complete.
- Response HTTP 200 dengan `success:false` tetap error, bukan toast sukses.
- Error jaringan/HTTP tetap ditampilkan; **409** me-refresh daftar scoped agar pengguna melihat status yang sudah tersimpan, bukan membayar ulang.
- Sisa debt setelah penerimaan parsial ditagih melalui pembayaran POS, bukan mengulang aksi penerimaan COD.

Tidak ada perubahan susunan field utama atau asset pipeline; hanya petunjuk pembayaran voucher ditambahkan pada konfirmasi. Test JS mengeksekusi blok fungsi asli dengan stub Swal/jQuery/DOM; **bukan browser E2E**.

## Isolasi pekerjaan lokal dan commit

`PosController.php` dan Blade delivery sudah mempunyai perubahan lokal sebelum task. Integrasi POS pada index disusun dari adapter HEAD agar refaktor action masuk tanpa ikut memasukkan seluruh pekerjaan lokal. Postimage adapter tersebut juga diuji lewat overlay autoload terisolasi, bukan hanya mengandalkan hasil working tree.

Helper `PaymentSubmission`, migration request-key POS, script payment-attempt, dan test idempotency POS lokal tetap pekerjaan pre-existing di luar commit task ini. Working tree mempertahankan penggunaan serta perilaku replay POS tersebut; **hasil test idempotency lokal bukan klaim fitur request-key itu sudah tracked/terdistribusi pada checkout bersih**. COD tidak memperoleh dependency baru pada file-file untracked tersebut dan tidak membutuhkan migration request-key.

Test voucher membedakan kontrak dua adapter secara eksplisit: default `INFRUITY_TEST_LEGACY_POS_ADAPTER=1` (atau tidak diset) mengikuti adapter tracked, sehingga pengulangan pembayaran invoice paid ditolak 422. Pada working tree audit, `INFRUITY_TEST_LEGACY_POS_ADAPTER=0` menegakkan exact replay 200 + response sama + satu submission untuk adapter lokal. Semua assertion settlement, stok, voucher, dan ledger tetap sama; flag ini hanya ada di test, bukan fitur aplikasi. Postimage diuji pada mode legacy, sedangkan full/focused working tree pada mode lokal. Schema fixture baru dinyatakan eksplisit dan tidak memerlukan migration untracked.

## Verifikasi aktual

Baseline sebelum tahap ini: HEAD `536b670d`, working tree dirty, full PHPUnit **233 test / 1.227 assertions**, 4 error + 1 failure.

| Pemeriksaan | Hasil |
|---|---|
| Dua class baru: `DeliveryOrderPaymentTest`, `CompletePaymentTest` | **62 test / 558 assertions**, 0 error/failure |
| Dua class baru + regression idempotency POS lokal | **68 test / 585 assertions**, seluruhnya lulus |
| Postimage adapter POS yang akan di-commit, overlay autoload + dua class baru | **62 test / 564 assertions**, seluruhnya lulus |
| Full PHPUnit sesudah perubahan | **295 test / 1.785 assertions**, 4 error + 1 failure; identitas sama persis dengan baseline, tidak ada failure/error baru |
| Seluruh JavaScript | **95 test lulus**, termasuk 5 test delivery baru |
| PHP lint | **5 file** produksi/test task lulus; postimage POS juga lulus lint |
| Blade delivery | Dikompilasi ke scratch lalu PHP lint: lulus |

Test mencakup permission/branch/state, format nominal, metode cash non-ID-1, historical return, write failure tanpa kebocoran DB, rollback voucher/header/produksi/ledger, soft-deleted detail/payment, produk/variant/parcel/jus, pembayaran campuran, serta alur POS → COD → pelunasan POS. Parity cases mengunci perilaku yang sudah benar; regression jus dan hardening HTTP/client dibuktikan RED → GREEN.

Empat error baseline tetap setup `TransferConcurrencyTest` karena tabel users tidak ada; satu failure tetap test daftar multi-role akun di `UserStaffLinkTest`. Deprecation schema XML PHPUnit masih ada. Full suite **belum hijau**, dan tidak ada assertion lama yang dihapus untuk menutupinya.

Pengulangan pada PHP 8.5 dengan isolasi sesuai [panduan engineering](engineering.md#4-verifikasi-yang-dapat-diulang):

```bash
INFRUITY_TEST_LEGACY_POS_ADAPTER=0 APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DATABASE_URL= \
  CACHE_DRIVER=array SESSION_DRIVER=array MAIL_DRIVER=array QUEUE_CONNECTION=sync \
  php vendor/bin/phpunit --do-not-cache-result \
  tests/Feature/DeliveryOrderPaymentTest.php tests/Feature/CompletePaymentTest.php

node --test tests/js/*.test.cjs
```

Seluruh mutasi test berlangsung dalam SQLite in-memory; tidak menjalankan pembayaran, migration, grant permission, reset, atau backfill terhadap database bisnis/aplikasi. Concurrency MySQL, request paralel nyata, browser, deployment consumer eksternal, rekonsiliasi voucher/deposito historis (A10), dan quality gate baseline tetap perlu pekerjaan tersendiri. Belum ada file/folder kandidat cleanup yang dihapus.
