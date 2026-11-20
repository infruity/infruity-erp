# Pemeriksaan end-to-end Kasir / POS mobile

Acuan: `003. ERP/006. Penjualan/001. Kasir - POS/index.html` pada Plan Design. Bandingkan Laravel pada viewport dan state yang sama; mulai dari 400 × 900, lalu periksa layar pendek dan safe area. Data contoh boleh berbeda, tetapi struktur, ukuran, jarak, tipografi, warna, ikon, state, animasi, navigasi, dan respons interaksi harus cocok. Jangan menyatakan suatu langkah 1:1 hanya berdasarkan markup atau satu screenshot.

Untuk setiap state: siapkan kondisi yang setara, ambil screenshot Plan dan Live, ukur perbedaan, perbaiki Live, lalu bandingkan ulang. Catat perbedaan yang berkaitan dengan aturan bisnis dan uji fungsi sebelum mengubahnya. Gunakan data uji yang aman; jangan menyelesaikan pembayaran sungguhan saat audit visual.

## Matriks alur

- [ ] **Daftar:** loading, data, kosong, pencarian cocok/tidak cocok, setiap status, avatar dan indikator parcel, scroll, navbar dan navigasi.
- [ ] **Filter:** buka/tutup, cari dan pilih cabang, semua status dan masing-masing status, rentang tanggal, pilihan cepat, reset dan terapkan, termasuk layar pendek.
- [ ] **Detail transaksi:** pending, belum bayar, kurang bayar, lunas; isi, total, item, parcel/jus, menu dan aksi kembali/edit/bayar/cetak yang tersedia untuk setiap status.
- [ ] **Form tambah dan edit:** cabang, pelanggan umum dan pelanggan tersimpan, pencarian, tambah pelanggan, keranjang kosong/berisi, edit dan hapus item, draf serta validasi.
- [ ] **Buah & Olahan:** daftar, pencarian, stok tersedia/habis, produk terpilih, kuantitas, harga, diskon persen/nominal, total, batas dan pesan kesalahan, tambah/edit/hapus.
- [ ] **Parcel:** sebelum/sesudah budget, dropdown dan pencarian, stok, komposisi kosong/berisi, kuantitas, harga, jasa, total, tambah/edit/hapus dan validasi.
- [ ] **Jus:** sebelum/sesudah pilih produk, pencarian, loading/kosong/resep berisi, bahan, kuantitas, diskon, total, tambah/edit/hapus dan validasi.
- [ ] **Total Belanja:** terbuka/tertutup dengan ketuk dan swipe, backdrop, transisi, diskon, ongkir, nilai nol/non-nol, fokus, posisi dan ukuran card di belakangnya.
- [ ] **Pembayaran:** pelanggan, alamat, Pick Up dan kurir, dropdown metode, split mati/aktif, tambah/hapus komposisi, jumlah pas/kurang/lebih, tombol dan pesan validasi.
- [ ] **Konfirmasi dan hasil pembayaran:** buka/batal, loading, berhasil/gagal, state ulang, navigasi serta pencegahan pengiriman ganda; pengujian penyelesaian hanya dengan fixture khusus.
- [ ] **Nota Transaksi:** akses dari daftar, pembayaran dan hasil pembayaran; draf/lunas/kurang bayar, pelanggan umum/tersimpan, item, diskon, ongkir, komposisi, total, status, teks, scroll, kembali, WhatsApp dan cetak; verifikasi hasil cetak secara terpisah.
- [ ] **Aksesibilitas dan regresi:** keyboard/fokus, label kontrol, reduced motion, layar kecil, safe area, state kosong dan loading; jalankan tes POS yang relevan dan bandingkan ulang setelah perubahan.

## Temuan yang sudah teridentifikasi

- State kosong Parcel dan urutan field Jus telah disesuaikan, tetapi seluruh state keduanya belum lulus matriks di atas.
- Diskon nota: Plan menyediakan pilihan manual persen/rupiah; Laravel saat ini menafsirkan nilai 1–100 sebagai persen pada frontend dan backend. Penyamaan memerlukan desain representasi dan uji perhitungan, penyimpanan, edit, nota, dan kompatibilitas transaksi lama.
- Nota dari pembayaran kini membuka pratinjau mobile `receipt.blade.php` melalui `pos/printDraftPayment/{id}?preview=mobile`; jalur cetak lama tetap tersedia. Nota dari daftar menggunakan template yang sama. Pada 400 × 900, header, card dan footer draf satu item telah diukur sama dengan Plan; layar 360 × 640 juga diperiksa agar konten tetap dapat di-scroll. Skenario item banyak, status lain dan hasil cetak fisik masih perlu diuji. Aksi WhatsApp kini menyiapkan PNG dan membuka WhatsApp dengan fallback tautan, tetapi clipboard, unduhan dan pengiriman belum diverifikasi end-to-end.
- Saat memeriksa nota lama, nilai bruto item pernah tidak sama dengan harga × kuantitas yang tercetak. Nilai bruto di nota kini memakai harga × kuantitas seperti Plan, dengan regression test; transaksi yang tersimpan tidak diubah. Data lama yang memiliki diskon/subtotal tidak konsisten tetap perlu diaudit terpisah.

Status: **belum selesai**. Tidak ada kotak yang boleh dicentang sampai seluruh state pada butir tersebut diperiksa dan perbedaannya ditutup atau dicatat sebagai keputusan pengguna.
