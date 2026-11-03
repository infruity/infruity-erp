# Penyelarasan struktur UI Infruity — tahap lanjutan

26 Oktober 2026 · Implementasi dan pemeriksaan 25–26 Oktober 2026 · Target infruity-main, referensi infruity-frontend.

## Kesimpulan

Halaman operasional legacy dimigrasikan pada tingkat markup dan komponen, melampaui adapter warna tahap sebelumnya. Pekerjaan mencakup daftar transaksi/CRM/laporan, form dan ringkasan, benefit tier, modal/sheet, nota, detail Master, serta konten dinamis. Tidak dinyatakan bahwa seluruh UI dan seluruh state telah selaras: layout dokumen cetak tertentu masih memakai struktur lama, sejumlah route tidak mempunyai template, dua edit transfer fixture mengembalikan 404, dan Permohonan tidak dapat dirender karena tabel modul lama tidak tersedia.

Klasifikasi pada matriks berlaku untuk state yang dibuktikan. Capture awal, empty, fixture dan scroll akhir tidak mensertifikasi seluruh hasil submit, role, permission, dataset atau perangkat nyata. Angka HTTP200 dan jumlah screenshot merupakan inventaris cakupan, bukan penilaian desain.

## Acuan dan pembandingan

Referensi dibuka langsung di server lokal dan dibandingkan dengan main. Main menggunakan database disposable infruity_mobile_audit_20261025 dengan akun Master dan data fixture, bukan transaksi bisnis produksi. Baseline aplikasi sebelum migrasi struktur disalin ke server terpisah localhost:8092; main di :8091, referensi di :8089. Perubahan pengguna dipertahankan; tidak ada commit/push/deploy.

- Viewport: **360×800, 390×844, 430×932, 844×390, 1440×900**.
- Plus Jakarta Sans, Poppins, Material Symbols Rounded, dan enam varian Phosphor kini dilayani lokal. Sumber serta lisensi disertakan. Harness menunggu font tersedia sebelum capture dan menunggu transisi dialog pada bukti dialog final.
- Pada referensi, font/ikon dengan keluarga yang sama dipasang melalui harness dari aset lokal supaya penilaian tidak bergantung kegagalan CDN. HTML referensi tidak diubah. Ini bukan pengujian referensi dalam kondisi jaringan asli.
- Pembandingan menilai komposisi, kelompok informasi, toolbar/aksi, daftar/tabel, bentuk dan urutan field, ringkasan, sheet, hierarchy type, padding, radius, dan peralihan viewport. Data referensi demo berbeda dari fixture main, sehingga tidak memakai persentase kemiripan piksel.
- Dokumen WIP pada POS, Pesanan, Pengiriman, Produksi dan sebagian inventaris bukan contoh CRUD fungsional. Untuk area itu, pola daftar/drawer Master, form Pengaturan serta form transaksi baru yang sudah tersedia menjadi acuan struktur. Referensi langsung dan analog dicantumkan pada tautan pembandingan; keduanya tidak dianggap padanan layar identik.
- Komposisi mobile setiap URL yang berhasil ditinjau melalui screenshot/board; desktop dan landscape diperiksa lintas komponen utama. Seluruh pasangan viewport dicatat, tetapi tidak diklaim setiap pasangan telah diperiksa piksel demi piksel atau semua state interaksi telah dieksekusi.

## Perubahan struktur

| Area | Sebelum | Implementasi baru |
|---|---|---|
| Daftar legacy | Card Metronic, toolbar bertumpuk, tabel lebar untuk mobile | x-erp.workspace: heading mobile, search/filter/aksi dalam toolbar, area konten dan footer. Desktop memakai tabel; mobile memakai baris identitas + detail + aksi, seperti pola Master |
| DataTables | Kontrol tabel menjadi satu-satunya akses data; isi sel HTML tercampur | x-erp.collection dan erp-collections.js membaca halaman API DataTables yang sama. Urutan, arah, jumlah per halaman, pagination, input/select dan aksi tetap tersedia. Identitas dipisahkan dari total/metadata |
| POS/pengeluaran/pesanan | Card vertikal dan dua pohon keranjang desktop/mobile | Grid informasi + item pada area utama dan ringkasan di sisi desktop; ditumpuk di mobile. Satu tombol baris per item, nama panjang terbungkus, edit tetap ke dialog asli |
| Pengeluaran | Draft/Submit di menu melayang yang bersaing dengan dock | Kedua aksi terlihat langsung di ringkasan form, dengan busy state terpisah dan callback lama |
| Pembayaran | Panel lama dan action bar menutupi komposisi | Fieldset metode pembayaran di area utama, ringkasan tagihan dan aksi pada aside/flow mobile. Busy/retry dan seluruh kontrak idempotensi sebelumnya dipertahankan |
| CRM | Modal/form Bootstrap generik, benefit di child-row tabel | Daftar dan sheet baru; editor benefit tier dipindah ke sheet yang dapat discroll, dengan endpoint/form save-detail yang sama. Error AJAX di dalam form, create mengembalikan mode form baru |
| Produksi/parcel | Card/nested-card vertikal, kart bahan berbeda pada dua breakpoint | Area informasi, bahan dan ringkasan; baris bahan terpadu, sheet bahan/resep, section detail produksi dan parcel. Label form diterjemahkan ke bahasa produk |
| Laporan legacy/HPP | Tabel boxed dan rekonsiliasi bertumpuk | Workspace, search/reload, daftar mobile, tab pergerakan stok/HPP dan ringkasan rekonsiliasi berbentuk definition list |
| Nota & cetak | Form logo/header/footer lama, Quill opsional | Identitas logo + field terkelompok, cabang, preview, toggle, upload/cancel, error dan busy. Nama field/API tetap |
| Detail pelanggan/karyawan | Profil Metronic dengan avatar contoh/field tidak tahan null | Header identitas dan definition grid, data nullable tampil sebagai tanda kosong; foto karyawan tetap dapat dibuka |
| Success/struk | Partikel dekoratif dan struk statis toko/pelanggan contoh | Konfirmasi ringkas, rincian pembayaran dan aksi. Struk thermal menggunakan invoice/customer/cashier/item/model totals yang tersedia; tidak lagi mencetak TOKO MAJU JAYA/Budi Santoso |
| Shell/aset | Font/ikon bergantung CDN | Font/ikon lokal, nama halaman pada header dan primitif layout bersama; vendor default tidak direplace massal |
| Pendukung | Chat sample dan daftar permission lama | Chat disusun ulang dan ditandai sebagai pratinjau data contoh; tidak dibuat layanan kirim pesan. Permission mengarahkan ke editor role aktif, menggantikan affordance akun yang tidak cocok dengan endpoint |

Perlindungan pembayaran sebelumnya sempat terhapus oleh agen yang membaca konteks lama. Root memulihkan service, migration dan controller yang terintegrasi, kemudian menjalankan enam regresi pembayaran (27 assertion). Tidak ada perluasan alur atau perhitungan pembayaran backend pada tahap ini.

## Bug frontend yang ditemukan saat verifikasi

- Potongan opsi DataTables pada script proses parcel berada di luar objek dan menyebabkan SyntaxError. Diperbaiki; angka nullable dihydrasi sebagai JSON numerik.
- Proses pengadaan mengakses search element yang tidak ada. Search sekarang hadir dan mengendalikan tabel produk yang benar.
- Tanggal nullable membuat detail produksi dan nota gagal render. Tampilan kini memakai fallback tanda kosong.
- Nama/total HTML pada sel transaksi sempat tercampur menjadi satu judul daftar. Judul kini diambil dari identitas sel; metadata tetap di detail.
- Dropdown urutan laporan kosong ketika DataTables memindahkan thead. Label kini diambil dari metadata kolom DataTables, termasuk aturan visible/orderable.
- Card stok opname melebar di dalam container tabel meskipun dokumen tidak overflow. Table layout dibatasi dan kelompok aksi dapat membungkus; tombol mengambang tambah duplikat dihapus.
- Lebar konfirmasi pembayaran pada mobile diperbaiki melalui box sizing dan grid yang dibatasi.

## Matriks halaman dan state

[Matriks lengkap 154 deklarasi GET→view](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/page-matrix.csv>) · [Matriks URL/viewport dan screenshot](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/viewport-matrix.csv>) · [Matriks interaksi/state](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/state-matrix.csv>)

154 baris bukan 154 layar unik: satu method dapat memilih beberapa view/redirect dan route alias dapat memakai template yang sama. Kategori “sudah mengikuti desain baru” berlaku pada struktur dan state awal/fixture yang ditinjau, dengan batas di kolom state. “Belum dapat diverifikasi” tidak diubah menjadi lulus karena source memakai token atau komponen baru.

| Kategori setelah implementasi | Deklarasi GET→view |
|---|---:|
| Belum dapat diverifikasi | 36 |
| Sudah mengikuti desain baru | 114 |
| Baru berganti token/warna; struktur legacy | 4 |
| Masih memakai desain lama tanpa adapter | 0 teridentifikasi pada route yang berhasil dirender; view tidak aktif tidak disertifikasi |

## Hasil pemeriksaan

- 114 URL konkret, 570 pasangan viewport final yang digabung dari pass utama dan pemeriksaan terarah. 555 HTTP200; lima HTTP500 pada Permohonan, sepuluh HTTP404 pada dua edit transfer fixture. Ini bukan jumlah desain yang otomatis dinyatakan cocok.
- 36 dokumen referensi ×5 = 180 pasangan. Wrapper redirect dipisahkan dari layar karena navigasi otomatis mengganggu capture; rekaman gagal awal tidak digunakan sebagai bukti keselarasan.
- 65 pemeriksaan perilaku lulus: 40 alur utama, 10 dialog final, 15 kontrol daftar/aksi form/card bounds. Pembayaran browser diintercept; aksi simpan pengeluaran pada pemeriksaan reachability memakai spy callback. Tidak membukukan transaksi bisnis.
- 59 tes JavaScript lulus. PHPUnit terkait: **36 tes, 251 assertion lulus**. Regresi baru memastikan struk memakai invoice aktif dan tidak mengandung data contoh statis.
- Suite PHPUnit penuh: **40 tes, 251 assertion, 4 error** pada TransferConcurrencyTest karena fixture SQLite tidak memiliki tabel users; masih belum hijau. Satu deprecation konfigurasi PHPUnit.
- Compile 279 Blade + lint hasil compile: 0 kegagalan. JS baru lolos node --check. Whitespace check lolos dengan pengakuan CRLF yang sudah ada.
- view:cache sebelumnya terhalang provider yang menunjuk resources/views/modules/chat yang tidak tersedia. Tidak mengubah provider untuk menyembunyikan masalah; compile individual digunakan.
- Satu record pembayaran berlabel UI-FIXTURE-PAYMENT-9001 ditambahkan langsung hanya ke database disposable untuk merender success/struk. Tidak menggunakan endpoint pembayaran, tidak mengubah data bisnis utama.

## Tampilan legacy atau belum terbukti yang tersisa

| Tampilan/state | Status dan alasan |
|---|---|
| pos.print2 untuk cetak draft/nota | Struktur dokumen cetak lama dipertahankan. Font/token/fallback brand diperbaiki; komposisi fisik, QR, ekspor PNG dan tombol WA belum dimigrasikan total atau diuji pada printer nyata |
| setting-nota/view-receipt | Preview contoh format cetak lama masih ada; bukan invoice transaksi aktual. Font/aset dan form pengaturannya sudah baru |
| stock-opname-preview | Layout tabel laporan khusus cetak masih dipertahankan, dengan wrapper horizontal, token dan tombol baru. Bukan salinan komposisi Master |
| /list-permohonan | Markup baru tersedia, tetapi runtime gagal karena tabel t_permohonan/struktur modul lama tidak ada di DB fixture; render isi belum terbukti |
| edit transfer pengirim/penerima ID fixture | HTTP404; create/detail tersedia, state edit untuk record tersebut belum terbukti |
| route legacy yang merujuk crm::create/edit/show, master::create, pos::create/edit/show, transaction::create dan sebagian production payment/completion/print | Template tidak tersedia atau jalur berpindah ke modal/index. Tidak membuat alur backend baru untuk menebak perilaku bisnis |
| Semua success/error server untuk setiap kombinasi form/peran | Tidak dieksekusi menyeluruh. CRUD submit, upload campaign, settlement, print printer, role/cabang terbatas dan session expiry nyata tetap perlu verifikasi lingkungan |
| iOS/Android/Safari fisik | Belum diuji; viewport Chromium dan keyboard/focus browser bukan bukti keyboard virtual atau safe-area perangkat nyata |

Karena sisa tersebut, laporan ini **tidak menyatakan seluruh UI telah sepenuhnya selaras**. Warna info, avatar, status shipping, seri data dan layout monospaced thermal dipertahankan sesuai makna/fungsinya. Tidak ada penggantian biru secara global.

## Screenshot referensi, sebelum, sesudah

Tautan per halaman memakai state dan viewport yang tersedia. Referensi WIP diberi analog Master/Laporan; tidak diklaim sebagai data/state identik. Beberapa bukti sebelum adalah HTTP500 atau capture intermediate sebelum halaman itu dimigrasikan; hal tersebut tetap dicatat, tidak diganti menjadi gambar contoh.

| Halaman | Referensi | Sebelum | Sesudah |
|---|---|---|---|
| /chat | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before-extra/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-extra/002-390x844-top.png>) |
| /customer-report | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/020-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-extra/003-390x844-top.png>) |
| /customers/9001/show | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/006-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before-extra/000-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/012-390x844-top.png>) |
| /delivery-order | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/023-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/005-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/014-390x844-top.png>) |
| /department | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/027-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/015-390x844-top.png>) |
| /expenditure | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/025-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/007-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-list/002-390x844-top.png>) |
| /expenditure/9001/edit | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/025-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/019-390x844-top.png>) |
| /expenditure/create | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/025-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/008-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-final/001-390x844-top.png>) |
| /handling | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/026-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/020-390x844-top.png>) |
| /list-permohonan | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before-extra/005-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/022-390x844-top.png>) |
| /location | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/025-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/023-390x844-top.png>) |
| /order-book | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/022-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/003-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-list/001-390x844-top.png>) |
| /order-book/9001/edit | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/022-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/027-390x844-top.png>) |
| /order-book/9001/order | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/022-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before-extra/002-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/026-390x844-top.png>) |
| /order-book/create | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/022-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/025-390x844-top.png>) |
| /other-book | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/024-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/006-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/028-390x844-top.png>) |
| /parcel | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/020-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/011-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/029-390x844-top.png>) |
| /parcel/9001/edit | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/020-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/033-390x844-top.png>) |
| /parcel/create | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/020-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/012-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/030-390x844-top.png>) |
| /parcel/process/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/020-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-receipt/004-390x844-top.png>) |
| /parcel/show/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/020-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/032-390x844-top.png>) |
| /pos | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/000-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-final/000-390x844-top.png>) |
| /pos/9001/edit | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/045-390x844-top.png>) |
| /pos/9001/receipt | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/044-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-receipt/001-390x844-top.png>) |
| /pos/create | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/001-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/037-390x844-top.png>) |
| /pos/payment-notification/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before-extra/003-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-detail/002-390x844-top.png>) |
| /pos/payment/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/002-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/039-390x844-top.png>) |
| /pos/printDraftPayment/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/040-390x844-top.png>) |
| /pos/printNota/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-receipt/000-390x844-top.png>) |
| /pos/printPayment/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/042-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-print/003-390x844-top.png>) |
| /pos/show/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/021-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/043-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-print/001-390x844-top.png>) |
| /product-transaction/9001/show | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/032-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/052-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-receipt/002-390x844-top.png>) |
| /production | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/020-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/009-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-list/003-390x844-top.png>) |
| /production/9001/detail | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/020-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before-process/000-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-detail/000-390x844-top.png>) |
| /production/9001/edit | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/020-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/056-390x844-top.png>) |
| /production/create | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/020-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/010-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-final/006-390x844-top.png>) |
| /report-branch-product | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/031-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-final/003-390x844-top.png>) |
| /report-branch-transaction | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/030-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/065-390x844-top.png>) |
| /report-customer-product | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/032-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-final/005-390x844-top.png>) |
| /report-customer-transaction | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/029-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-final/004-390x844-top.png>) |
| /report-transaction | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/028-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/074-390x844-top.png>) |
| /role-menu | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/033-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-extra/004-390x844-top.png>) |
| /setting-nota | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/033-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/023-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/080-390x844-top.png>) |
| /setting-nota/view-receipt | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/033-390x844-top.png>) | Tidak tersedia pada baseline capture | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/081-390x844-top.png>) |
| /staff/show/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/010-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before-extra/001-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/089-390x844-top.png>) |
| /stock-opname | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/019-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/013-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-final/002-390x844-top.png>) |
| /stock-out | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/014-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/093-390x844-top.png>) |
| /stock-out-type | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/004-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/before/015-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/094-390x844-top.png>) |
| /wholesale/process/9001 | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/reference/015-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/confirmed/109-390x844-top.png>) | [lihat](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/after-receipt/003-390x844-top.png>) |

## Indeks teknis

- [Acuan token + struktur](</Users/maaullntechicloud.com/Develop/sheetup-projects/infruity-main/docs/ui-design-system.md>)
- [Source yang berubah pada snapshot tahap ini](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/structural-source-changes.json>)
- [Indeks capture final](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/final-capture-index.json>)
- [Hasil Node](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/node-tests.log>)
- [Hasil PHPUnit terkait](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/phpunit-relevant.log>)
- [Hasil PHPUnit penuh](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/phpunit-full.log>)
- [Compile Blade](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/blade-compile.json>)
- [Regresi pembayaran yang dipertahankan](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/evidence/payment-regression.log>)
- [Review board komposisi](</Users/maaullntechicloud.com/.codex/visualizations/2026/10/25/01a171bb-a6d3-7b40-84d4-24f4941a29a0/structural-alignment/review-boards>)

## Matriks ringkas per deklarasi halaman

| Route | View | Sebelum | Setelah |
|---|---|---|---|
| / | landing | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /account | master::account.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /auth/login | auth.login | Sudah mengikuti desain baru | Belum dapat diverifikasi |
| /branch | master::branch.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /campaign | crm::campaign.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /campaign/create | crm::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /cek-nota/draft/{id} | pos::pos.deleted-nota | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /cek-nota/draft/{id} | pos::pos.print2 | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /cek-nota/{id} | pos::pos.deleted-nota | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /cek-nota/{id} | pos::pos.print2 | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /cek-nota/{id} | pos::pos.deleted-nota | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /change-password | change-password | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /chat | chat::index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /crm-dashboard | crm::dashboard.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /crm-dashboard/top-distribution | crm::dashboard.top_distribution | Sudah mengikuti desain baru | Belum dapat diverifikasi |
| /crm-dashboard/top-tier | crm::dashboard.top_tier | Sudah mengikuti desain baru | Belum dapat diverifikasi |
| /customer-deposito | crm::deposito.customer-deposito | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /customer-deposito/show/{id} | crm::deposito.show | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /customer-report | crm::report.customer | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /customers | master::customer.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /customers/create | master::customer.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /customers/{customer}/edit | master::customer.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /customers/{id}/show | master::customer.show | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /dashboard | crm::dashboard.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /delivery-order | pos::delivery-order.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /delivery-order/get-courier | pos::delivery-order.list-kurir | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /department | master::department.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /department/create | master::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /deposito | crm::deposito.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /deposito/create | crm::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /expenditure | pos::expenditure.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /expenditure/create | pos::expenditure.create | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /expenditure/show/{id} | pos::show | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /expenditure/{expenditure}/edit | pos::expenditure.create | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /handling | master::handling.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /handling/create | master::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /kurir | master::kurir.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /list-permohonan | list_permohonan | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /location | master::location.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /location/create | master::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /login | auth.login | Sudah mengikuti desain baru | Belum dapat diverifikasi |
| /order-book | pos::order-book.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /order-book/create | pos::order-book.create | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /order-book/{id}/order | pos::order-book.order | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /order-book/{order_book}/edit | pos::order-book.create | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /other-book | pos::otherbook.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /parcel | transaction::production.parcel | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /parcel/create | transaction::production.create-parcel | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /parcel/process/{id} | transaction::production.process-parcel | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /parcel/show/{id} | transaction::production.create-parcel | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /parcel/{parcel}/edit | transaction::production.create-parcel | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /payment-method | master::payment-method.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /point-schedule | crm::point-schedule.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /point-schedule/create | crm::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /point-schedule/{point_schedule}/edit | crm::edit | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /pos | pos::pos.index2 | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /pos/create | pos::pos.create2 | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /pos/payment-notification/{id} | pos::pos.payment-success2 | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /pos/payment/{id} | pos::pos.payment | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /pos/printDraftPayment/{id} | pos::pos.print2 | Baru berganti token/warna; struktur legacy | Baru berganti token/warna; struktur legacy |
| /pos/printNota/{id} | pos::pos.deleted-nota | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /pos/printNota/{id} | pos::pos.deleted-nota | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /pos/printNota/{id} | pos::pos.print2 | Baru berganti token/warna; struktur legacy | Baru berganti token/warna; struktur legacy |
| /pos/printPayment/{id} | pos::pos.print-list-payment | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /pos/show/{id} | pos::pos.receipt | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /pos/{id}/receipt | pos::pos.receipt2 | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /pos/{po}/edit | pos::pos.create2 | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /position | master::position.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /product-receipt | transaction::receipt.partials.items | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /product-receipt | transaction::receipt.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /product-receipt/create | transaction::receipt.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /product-receipt/{product_receipt}/edit | transaction::receipt.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /product-stock | master::products.stock | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /product-stock/{id}/show | master::products.stock-show | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /product-transaction/{id}/show | master::products.show-transaction | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /production | transaction::production.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /production/create | transaction::production.create | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /production/{id}/completion-notification | transaction::production.completion-success | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /production/{id}/detail | transaction::production.show | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /production/{id}/payment | transaction::production.payment | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /production/{id}/print | transaction::production.print | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /production/{production}/edit | transaction::production.create | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /products | master::products.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /products/create | master::products.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /products/{id}/show | master::products.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /products/{product}/edit | master::products.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /qrcode | qrcode | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /receipt | transaction::receipt.partials.items | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /receipt | transaction::receipt.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /receipt/create | transaction::receipt.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /receipt/{receipt}/edit | transaction::receipt.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /report-branch-product | report::branch-product-rep | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /report-branch-transaction | report::branch-transaction-rep | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /report-customer-product | report::product-customer-transaction-rep | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /report-customer-transaction | report::customer-transaction-rep | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /report-product-buang | report::product-buang | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /report-product-sales | report::product-sales | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /report-profit-adjusted | report::profit-adjusted | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /report-profit-revenue | report::profit-revenue | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /report-shipping-cost | report::shipping-cost | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /report-total-aset | report::total-aset | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /report-transaction | report::customer-transaction | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /reports | report::hub | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /role-menu | role-menu | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /roles | admin.role.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /roles/detail/{id} | admin.role.edit | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /setting-exp | crm::setting-exp.index2 | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /setting-exp/create | crm::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /setting-exp/{setting_exp}/edit | crm::edit | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /setting-nota | pos::setting-nota.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /setting-nota/create | pos::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /setting-nota/view-receipt | pos::setting-nota.view | Baru berganti token/warna; struktur legacy | Baru berganti token/warna; struktur legacy |
| /setting-nota/{setting_notum}/edit | pos::edit | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /settings | settings.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /sortir | transaction::sortir.index2 | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /sortir/create | transaction::sortir.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /sortir/show/{id} | transaction::sortir.show | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /sortir/{sortir}/edit | transaction::sortir.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /srrd | chat::srrd | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /staff | master::staff.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /staff/create | master::staff.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /staff/show/{id} | master::staff.show | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /staff/{staff}/edit | master::staff.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /stock-opname | transaction::stock-opname.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /stock-opname-preview | transaction::stock-opname.exports.preview | Baru berganti token/warna; struktur legacy | Baru berganti token/warna; struktur legacy |
| /stock-opname/create | transaction::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /stock-out | transaction::stock-out.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /stock-out-type | transaction::stock-out-type.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /stock-out-type/create | transaction::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /stock-out/create | transaction::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /supplier | master::supllier.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /tier | crm::tier.index | Baru berganti token/warna; struktur legacy | Sudah mengikuti desain baru |
| /tier/create | crm::create | Baru berganti token/warna; struktur legacy | Belum dapat diverifikasi |
| /transfer-penerima | transaction::transfer.index2 | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /transfer-penerima/create | transaction::transfer.create | Sudah mengikuti desain baru | Belum dapat diverifikasi |
| /transfer-penerima/edit/{id} | transaction::transfer.create | Sudah mengikuti desain baru | Belum dapat diverifikasi |
| /transfer-penerima/show/{id} | transaction::transfer.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /transfer-pengirim | transaction::transfer.index2 | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /transfer-pengirim/create | transaction::transfer.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /transfer-pengirim/edit/{id} | transaction::transfer.create | Sudah mengikuti desain baru | Belum dapat diverifikasi |
| /transfer-pengirim/show/{id} | transaction::transfer.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /unit | master::unit.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /user | admin.user.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale | transaction::wholesale.index | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale | transaction::wholesale.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale/create | transaction::wholesale.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale/process/{id} | transaction::wholesale.process | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale/show/{id} | transaction::wholesale.show | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale/show/{id} | transaction::wholesale.create2 | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale/show/{id} | transaction::wholesale.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale/{id}/show | transaction::wholesale.show | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale/{id}/show | transaction::wholesale.create2 | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale/{id}/show | transaction::wholesale.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
| /wholesale/{wholesale}/edit | transaction::wholesale.create | Sudah mengikuti desain baru | Sudah mengikuti desain baru |
