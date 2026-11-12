# QA Manual Fase 3 — Form Pengadaan (Penerimaan Barang)

Bagian dari [pre-deploy-test-plan.md](pre-deploy-test-plan.md). Fokus: form `pengadaan` (Draft / Final / autosave)
setelah validasi item dan guard "stok tidak boleh negatif" ditambahkan (temuan W1–W2).
Test otomatis sudah membuktikan sisi server; daftar ini membuktikan **form di HP benar-benar bisa dipakai**.


## Hasil eksekusi (2026-11-12)

**Lingkungan:** aplikasi lokal (`localhost:8123`) terhadap database salinan baru `infruity_qa_manual` (skema dari `migrate:fresh`, 4 cabang, akun uji + produk "QA Apel/Jeruk/Mangga"); database dev `infruity_clone` tidak disentuh. Tampilan HP diuji dengan **emulasi viewport 375×812 di browser aplikasi**, bukan perangkat Android/iPhone sungguhan (butir F2–F7 dan uji sentuh nyata masih harus dilakukan). Penjualan di bagian D disiapkan lewat SQL, bukan lewat kasir.

**Ringkas:** 18 butir ✅, 2 temuan bug aplikasi (diperbaiki), butir bertanda ⬜ belum dijalankan manual (sebagian sudah dicakup test otomatis).

**Bug yang ditemukan manual (keduanya sudah diperbaiki + test regresi):**

| # | Severity | Temuan | Perbaikan |
|---|---|---|---|
| W5 | **High** | Penyimpanan pertama dari form pengadaan **selalu ditolak** ("Anda tidak memiliki akses ke transaksi ini") dan autosave diam-diam gagal. Membuka form menyimpan draf `temp` tanpa cabang (`branch_id` NOT NULL → 0 di MySQL non-strict); aturan akses menolak draf itu. Sudah ada di `HEAD` sebelum perubahan Fase 1. Test otomatis lama tidak menangkapnya karena tidak pernah membuka form dulu. | Draf tanpa cabang milik user sendiri dianggap miliknya; form memakai ulang draf itu. 5 test alur-form. |
| W6 | **High** | Di `/wholesale/{id}/edit` (HP), modal edit item **tergambar di bawah backdrop**: kolom qty dan tombol Simpan tidak bisa disentuh, jadi nota tidak bisa diedit dari UI. Wrapper halaman masih punya `transform` dari animasi masuk → stacking context menjebak modal (z 1055) di bawah backdrop (z 1050). Mode create sudah memakai `animation:none`, mode edit tidak. | `animation:none` pada aturan mode edit di `create2.blade.php`. Test kontrak `WholesaleEditModalStackingTest`. |

**Catatan lain dari sesi ini:**
- Halaman memuat Tailwind dari `cdn.tailwindcss.com` (peringatan konsol "should not be used in production") → harus di-build lokal sebelum production.
- Pesan validasi qty 0 berbahasa Inggris (bawaan browser) — kosmetik.
- Label status daftar: `temp`=Draft, `draft`=Pending, `posting`=Dalam Pengiriman. Tombol form: **"Simpan sebagai Draf"** (create) / **"Simpan Perubahan"** (edit) dan **"Selesaikan Transaksi"** (di dokumen ini disebut Draft/Final).
- Backdrop modal sempat tertinggal sekali setelah urutan klik yang tidak lazim (dialog error + tombol Enter); tidak bisa direproduksi pada alur bersih (Batal dan Simpan menghapus backdrop).

## Persiapan

**Perangkat:** 1 Android (Chrome) + 1 iPhone (Safari), lebar 360–390px. Desktop hanya sebagai pembanding.
**Lingkungan:** staging (bukan data asli), memakai data hasil impor Fase 2 bila sudah ada.

**Akun & data uji**

| Kebutuhan | Isi |
|---|---|
| Akun **Gudang A** | role dengan izin pengadaan, ditugaskan hanya ke **Cabang A** |
| Akun **Gudang B** | role yang sama, hanya **Cabang B** |
| Akun **Sales** | tanpa izin pengadaan |
| Akun **Admin** | super admin |
| Produk **Apel**, **Jeruk**, **Mangga** | stok awal **0** di Cabang A (bagian A–C memakai Apel/Jeruk, bagian D memakai Mangga) |

Cara memeriksa stok & HPP: halaman **Stok Produk** (`/product-stock`) per cabang; HPP di detail stok produk.
Cara memeriksa angka kulak: daftar **Pengadaan** (kolom status + total).

**Cara mencatat:** isi kolom *Hasil* dengan ✅ / ❌, tempel screenshot untuk setiap ❌ atau bila pesan error ikut diuji.
Severity ❌: **Critical** (stok/uang salah), **High** (alur tidak bisa diselesaikan), **Medium** (pesan/tampilan menyesatkan), **Low** (kosmetik).

---

## A. Alur normal

| ID | Langkah (login Gudang A, Cabang A) | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| A1 | Buka form pengadaan baru. Pilih cabang, tambah **Apel**: qty 10, harga 10.000. Tekan **Final**. | Berhasil, kembali ke daftar. Status **posting**, total Rp 100.000. Stok Apel Cabang A = **10**. | ✅ (setelah perbaikan W5; sebelumnya ❌ 403) |
| A2 | Ulangi dengan **qty desimal 2,5** (Jeruk, harga 8.000). Final. | Tersimpan; qty 2,5 (bukan 2 atau 25); total Rp 20.000; stok Jeruk = **2,5**. | ✅ "2,5" → 2,50; total 20.000 |
| A3 | Produk dengan **harga berformat ribuan** (ketik 12.500 / 12500). Final. | Harga tersimpan 12.500, bukan 12 atau 12,5. | ✅ "12.500" → 12.500; total 50.000 |
| A4 | Isi **diskon rupiah**, lalu coba **diskon persen**. Final. | Total = qty × (harga − diskon) sama dengan yang tampil di ringkasan; angka di daftar pengadaan sama. | ⬜ tidak ada field diskon di form saat ini (hanya margin%) |
| A5 | Isi **harga jual cabang** (mis. 15.000). Final. | Harga jual Apel di Cabang A di master produk / POS = 15.000. | ✅ harga jual cabang tersimpan (15.000 / 25.000) |
| A6 | Tambah **dua produk berbeda** dalam satu nota. Final. | Kedua stok bertambah sesuai qty masing-masing. | ✅ 2 produk dalam 1 nota, stok keduanya naik |
| A7 | Posting Apel 10 @ 10.000, lalu posting lagi Apel 10 @ 20.000. | HPP Apel = **15.000** (rata-rata bergerak); stok = 20. | ⬜ tidak dijalankan manual; otomatis ✅ (10@10.000 + 10@20.000 → 15.000) |

## B. Draft dan autosave

| ID | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| B1 | Tambah produk, tekan **Draft**. | Muncul di daftar dengan status draft. **Stok tidak berubah.** | ⬜ belum |
| B2 | Buka draft tadi, ubah qty, tekan **Final**. | Status jadi posting; stok bertambah sesuai qty **terakhir**. | ⬜ belum |
| B3 | Tambah produk, **jangan** simpan. Tunggu beberapa detik, lalu tutup tab dan buka form lagi. | Isi keranjang terpulihkan dari autosave. Tidak ada pesan error. | ✅ keranjang pulih setelah muat ulang |
| B4 | Saat mengetik qty di modal produk, kosongkan kolom qty sebentar (belum diisi) lalu biarkan autosave jalan. | **Tidak ada error / dialog gagal** muncul (autosave sengaja longgar). | ⬜ belum di UI; server otomatis ✅ |
| B5 | Setelah autosave, cek daftar pengadaan. | Tidak ada nota ganda; stok tidak berubah. | ✅ tetap 1 draf, tidak menumpuk |

## C. Validasi di form

| ID | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| C1 | Tekan **Final** dengan keranjang kosong. | Dialog "Keranjang kosong"; tidak ada nota. | ✅ "Keranjang kosong" |
| C2 | Tekan **Final** tanpa memilih cabang. | Dialog "Cabang belum dipilih". | ⬜ tidak bisa dipicu: cabang terisi otomatis dengan cabang pertama |
| C3 | Di modal produk isi qty **0**, lalu **-5**. | "Kuantitas tidak valid" / tidak bisa disimpan; tidak masuk keranjang. | ✅ diblokir validasi browser (pesan Inggris: kosmetik) |
| C4 | Simpan produk tanpa harga (kosong atau 0). | Perilaku jelas: boleh (harga 0) atau ditolak dengan pesan — **catat mana yang terjadi**; jangan error 500. | ⬜ belum |

## D. Edit dan hapus nota yang sudah posting (guard stok negatif)

Siapkan: posting nota **Mangga qty 10** di Cabang A, lalu **jual 8 Mangga lewat POS** (bayar lunas). Stok Mangga sekarang **2** (10 − 8). Keadaan ini dipakai ulang di D2–D6; bila sebuah langkah mengubah stok, kembalikan dulu ke kondisi ini.

| ID | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| D1 | Buka nota itu, naikkan qty ke 12, Final. | Berhasil; stok Mangga jadi **4** (12 − 8). | ⬜ belum manual; otomatis ✅ |
| D2 | Turunkan qty ke **jumlah di bawah yang sudah terjual** (mis. 3 → stok akan −5), Final. | **Ditolak** dengan dialog "Gagal menyimpan transaksi" berisi pesan stok akan negatif dan nama produk. Nota **tidak berubah**, stok sama seperti sebelumnya. | ✅ dialog jelas, nota & stok tidak berubah (setelah perbaikan W6) |
| D3 | Turunkan qty ke **8** persis (stok akhir tepat 0). | Berhasil (0 tidak negatif); stok Mangga = 0. | ✅ qty 8 → stok tepat 0 |
| D4 | Ubah nota posting itu kembali menjadi **Draft**. | Ditolak bila barangnya sudah terjual; nota tetap posting. | ⬜ otomatis ✅ |
| D5 | Ganti produk di nota posting yang barangnya sudah terjual. | Ditolak dengan alasan sama. | ⬜ otomatis ✅ |
| D6 | Hapus nota yang barangnya **sudah terjual**. | Ditolak, nota masih ada, stok tidak berubah. | ⬜ otomatis ✅ |
| D7 | Hapus nota posting yang **belum** terpakai. | Berhasil; stok kembali turun sesuai qty nota itu. | ⬜ otomatis ✅ |
| D8 | Pesan penolakan (D2/D4/D6) | Terbaca jelas di HP, tidak terpotong, berbahasa Indonesia. | ✅ terbaca penuh di 375px |

## E. Akses dan cabang

| ID | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| E1 | Login **Gudang B**, buka form pengadaan, cari Cabang A. | Cabang A tidak tersedia / tidak bisa dipilih. | ✅ hanya "Cabang Surabaya" |
| E2 | Gudang B melihat daftar pengadaan. | Hanya nota Cabang B. | ✅ tidak melihat nota cabang lain (daftar 0 baris) |
| E3 | Gudang B membuka langsung URL edit/show nota Cabang A. | Ditolak / 404. | ✅ 404 untuk edit & show nota cabang lain |
| E4 | Login **Sales** (tanpa izin) buka menu pengadaan. | Menu tidak tampil atau pesan tidak punya izin. | ⬜ otomatis ✅ |
| E5 | Login **Admin**: nota semua cabang terlihat dan bisa diproses. | Sesuai. | ✅ admin melihat/mengedit semua cabang |

## F. Perilaku di HP

| ID | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| F1 | Tekan **Final** dua kali cepat. | Hanya **satu** nota; tombol menunjukkan loading dan tidak bisa ditekan lagi. | ✅ klik ganda → 1 nota, stok tidak dobel |
| F2 | Aktifkan throttle jaringan lambat (3G) lalu **Final**. | Ada indikator loading; tidak ada nota ganda; hasil akhir benar. | ⬜ belum |
| F3 | Matikan koneksi sebelum **Final**. | Dialog gagal yang jelas; keranjang **tidak hilang**; bisa coba lagi. | ⬜ belum |
| F4 | Keyboard HP terbuka saat mengisi qty/harga. | Tombol Simpan modal tetap terjangkau; kolom tidak tertutup. | ⬜ belum |
| F5 | Keranjang dengan 10+ produk. | Daftar bisa discroll; ringkasan total tetap terbaca; tombol Draft/Final terjangkau. | ⬜ belum |
| F6 | Putar layar (portrait ↔ landscape). | Tidak ada isi hilang; modal tetap berfungsi. | ⬜ belum |
| F7 | Sesi habis (tunggu/logout di tab lain) lalu **Final**. | Diarahkan login atau pesan jelas; data tidak hilang diam-diam. | ⬜ belum |

## G. Halaman lama (jangan sampai menghapus stok)

Endpoint lama sudah dimatikan (W3). Pastikan tidak ada jalur UI yang masih memakainya.

| ID | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| G1 | Dari daftar pengadaan & menu, cari tombol/tautan "Terima", "Proses", atau "Selesai" untuk pengadaan. | **Tidak ada**, atau bila ada: ditolak dengan pesan, **stok tidak berubah**. | ✅ tidak ada tombol/tautan UI ke endpoint lama |
| G2 | Buka `/wholesale/process/{id}` langsung (nota posting) lalu tekan tombol selesai/terima. | Pesan "tidak dipakai lagi"; status nota tetap **posting**; stok tetap. | ⬜ otomatis ✅ |
| G3 | Halaman **Produksi** → aksi "terima produk" (memanggil rute lama). | Ditolak dengan pesan; stok tidak berubah. Catat bila tombol ini masih tampil. | ⬜ otomatis ✅ |

## H. Setelah semua selesai

- [ ] Stok akhir tiap produk uji sama dengan hitungan manual (tulis perhitungannya di kolom catatan).
- [ ] Laporan **Total Aset** dan laporan pembelian mencerminkan nota yang diposting.
- [ ] Tidak ada error baru di `storage/logs/laravel.log` selama sesi uji.
- [ ] Semua ❌ Critical/High sudah punya tiket; Medium/Low dicatat.

**Penguji:** ______________  **Tanggal:** ______________  **Versi/commit:** ______________  **Perangkat:** ______________
