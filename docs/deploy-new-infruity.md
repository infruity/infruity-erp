# Deploy `new.infruity.com` (Hostinger shared hosting) dan sinkron data akhir

Alamat final aplikasi baru: **https://new.infruity.com**. Aplikasi lama (`infruity.com`) tetap berjalan terpisah sampai
pengguna pindah; folder, database, dan `.env`-nya tidak boleh disentuh.

## Susunan di server

| Hal | Nilai |
|---|---|
| Akun | `u101806666`, SSH `ssh -p 65002 u101806666@46.202.186.80` |
| Folder situs | `~/domains/new.infruity.com/public_html` (aplikasi seluruhnya di sini; `.htaccess` di root mengarahkan semua ke `public/`) |
| PHP CLI | `/opt/alt/php85/usr/bin/php` (CLI default 8.2 terlalu lama) |
| Database | `u101806666_new_infruity`, **MariaDB 11.8** → `.env` wajib `DB_SERVER=mariadb` |
| `public/storage` | symlink ke `../storage/app/public` (`symlink()` diblokir, buat dengan `ln -s` lewat SSH) |
| Kunci Firebase | `~/secrets/infruity-firebase-admin.json` (di luar web root; `GOOGLE_APPLICATION_CREDENTIALS` menunjuk ke sini) |
| Backup | cron harian 02:30 → `~/backups/new_infruity_*.sql.gz` (14 hari). Hanya tabel; view dibangun ulang oleh migrasi |
| `TRUSTED_PROXIES` | **kosong, jangan diisi.** `REMOTE_ADDR` sudah IP asli pengunjung; mempercayai `X-Forwarded-For` membuat IP bisa dipalsukan (pembatas login) |
| Scheduler | tidak ada cron: satu-satunya jadwal di kode memanggil `tarik:absensi`/`insert:absensi` yang tidak ada |

Jangan memakai `deploy-static-site-archive` Hostinger untuk memperbarui: itu menimpa seluruh situs termasuk `.env` dan
`storage`. Unggah file yang berubah satu per satu (TUS, `override=true`).

## Kenapa impor lewat komputer lokal

`legacy:import` membaca database sumber dan menulis database target dengan **satu user**. Di shared hosting satu user
hanya punya satu database, jadi impor tidak bisa jalan di server. Alurnya: dump lama → impor dan rekonsiliasi di
MariaDB 11.8 lokal → dump data-only → muat ke database aplikasi di server.

## Sinkron data akhir (saat pengguna pindah)

Perkiraan 15 menit. Selama langkah 1–6 transaksi baru di aplikasi lama tidak boleh masuk.

1. **Bekukan aplikasi lama** (minta semua kasir berhenti input).
2. **Dump database lama** di server (hanya baca):
   ```bash
   cd ~/domains/infruity.com/public_html
   export MYSQL_USER=$(grep -m1 ^DB_USERNAME= .env | cut -d= -f2-) MYSQL_HOST=localhost
   export MYSQL_PWD=$(grep -m1 ^DB_PASSWORD= .env | cut -d= -f2- | sed 's/^"//;s/"$//')
   bash ~/domains/new.infruity.com/public_html/scripts/legacy/dump-legacy.sh "$(grep -m1 ^DB_DATABASE= .env | cut -d= -f2-)" ~/legacy_dump/legacy.sql.gz
   ```
3. **Unduh** ke komputer lokal: `scp -P 65002 u101806666@46.202.186.80:legacy_dump/legacy.sql.gz .`
4. **Lokal** (MariaDB 11.8, jangan database dev utama): buat database target, jalankan `php artisan migrate`, lalu
   ```bash
   scripts/legacy/load-legacy.sh legacy_src legacy.sql.gz --replace
   php artisan legacy:import legacy_src --dry-run          # harus 0 masalah
   php artisan legacy:import legacy_src --confirm-target=<db_target>
   php artisan legacy:reconcile legacy_src                 # harus 0 FAIL (WARN lama boleh)
   ```
   Env lokal: `DB_SERVER=mariadb DB_HOST=127.0.0.1 DB_PORT=<port> DB_USERNAME=root DB_PASSWORD=`.
5. **Dump data-only** dan muat ke server (database aplikasi sudah dimigrasi):
   ```bash
   scripts/legacy/dump-imported-data.sh <db_target> imported.sql.gz
   scp -P 65002 imported.sql.gz u101806666@46.202.186.80:legacy_dump/
   # di server, dalam folder situs baru (DB_USERNAME, DB_DATABASE, MYSQL_PWD diambil dari .env seperti di langkah 2):
   gunzip -c ~/legacy_dump/imported.sql.gz | mysql -h127.0.0.1 -u"$DB_USERNAME" "$DB_DATABASE"
   ```
   Backup dulu: `~/bin/backup_new_infruity.sh`.
6. **Verifikasi:** jumlah baris tiap tabel di server sama dengan database target lokal; `product_stock`/`product_hpp`
   terisi; `/login` 200.
7. **Bersihkan:** hapus `~/legacy_dump/*` di server, serta database dan dump lokal yang berisi data produksi.

## Catatan pindah alamat

- Notifikasi web push terikat ke domain: pengguna harus mengizinkan notifikasi lagi di `new.infruity.com`. Tautan dan
  ikon notifikasi mengikuti `APP_URL`.
- Token di `user_devices` yang berasal dari aplikasi lama tetap berlaku untuk project Firebase yang sama
  (`FCM_PROJECT_ID`); token mati dibersihkan otomatis saat kirim pertama.
- Akun dan karyawan hasil impor belum ditautkan (`staff.user_id` kosong), sama seperti aplikasi lama.

## Masalah lama yang diketahui

- `report-customer-product/data` timeout 120 dtk: join `customer.city/district/village` (BIGINT) ke `reg_*.id` (CHAR)
  tidak memakai indeks. Ada juga di database lama.
- 18 rute GET mati (view/method tidak ada) dan fitur dengan flag mati (`expenditure`, `other-book`, `stock-out`).
- Email belum dikonfigurasi (sama seperti aplikasi lama); reset password lewat email belum berfungsi.
