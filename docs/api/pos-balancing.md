# API Balancing POS

Balancing mengoreksi **omzet** sebuah nota. Isi nota, total, pembayaran, kas, stok, HPP, struk, dan laporan per produk
tidak berubah. Laba di Dashboard dan Laporan Cabang/Customer ikut berubah karena omzetnya berubah.
Omzet dicatat pada **tanggal nota**; `created_at` adalah jam balancing dilakukan.

Semua endpoint memakai sesi login web. Kirim `Accept: application/json`, dan untuk POST sertakan header `X-CSRF-TOKEN`.

## Hak akses

| Hak | Kegunaan |
|---|---|
| `pos.receipt` | membuat balancing nota hari ini, membatalkan buatan sendiri, melihat riwayat satu nota |
| `pos-balancing.backdate` | balancing nota hari sebelumnya, membatalkan buatan orang lain (nota hari ini atau lama) |
| `pos-balancing.index` | daftar semua balancing dan ringkasan |

Administrator memiliki semuanya. Akun lain diberi dua hak `pos-balancing.*` lewat Akun Pengguna > Hak Akses.

## POST `/pos-balancing`

| Field | Aturan |
|---|---|
| `pos_id` | wajib; id nota berstatus `paid` atau `debt` di cabang yang boleh diakses |
| `amount` | wajib; angka biasa (tanpa titik ribuan), maksimal 2 desimal, tidak boleh 0. `5000` = lebih, `-2000` = minus |
| `note` | wajib; alasan, maksimal 1000 karakter |
| `idempotency_key` | wajib; maksimal 64 karakter. Buat satu UUID saat form dibuka, pakai ulang saat mencoba lagi, buat baru setelah berhasil |

`201` tersimpan; `200` kiriman ulang dengan kunci dan data yang sama (tidak digandakan).

```json
{ "success": true,
  "data": { "id": 1, "pos_id": 833, "amount": 5000, "note": "lapak pagi", "reversal_of_id": null,
            "created_by": 12, "idempotency_key": "…", "created_at": "…" },
  "revenue": 82000 }
```

`revenue` = omzet nota (tanpa ongkir) setelah semua balancing. Nilainya tidak boleh kurang dari 0.

Galat: `422` data tidak valid / nota belum lunas atau utang / nota bukan hari ini, `403` tanpa hak, `404` nota tidak
ditemukan di cabang Anda. Pesan ada di `message` dan `errors`.

## POST `/pos-balancing/{id}/cancel`

Body: `note`, `idempotency_key`. Membuat baris baru bernilai kebalikan (`reversal_of_id` = `{id}`); baris lama tidak
diubah atau dihapus. Satu baris hanya bisa dibatalkan sekali dan baris pembatalan tidak bisa dibatalkan.
Respons sama dengan di atas.

## GET `/pos-balancing`

Filter opsional: `from`, `to` (`Y-m-d`, **tanggal balancing dilakukan**), `branch_id`, `cashier_id`, `pos_id`,
`per_page` (default 25, maksimal 200), `page`. Dengan `pos_id`, cukup hak `pos.receipt`.

Format paginator Laravel (`data`, `total`, `current_page`, …). Tiap baris: `id`, `pos_id`, `invoice_number`,
`sale_date`, `branch_id`, `branch_name`, `amount`, `note`, `reversal_of_id`, `cancelled_by_id`, `created_by`,
`created_by_name`, `created_at`. `amount` di daftar ini berupa string desimal.

## GET `/pos-balancing/summary`

Filter sama (tanpa `per_page`).

- `cashiers[]`: `user_id`, `name`, `balancing_count`, `cancelled_count`, `surplus_total`, `minus_total`, `net_total`,
  `balanced_sale_count`, `sale_count`
- `per_hour[]`: `hour`, `total`
- `per_date[]`: `date`, `total`

Baris pembatalan tidak dihitung sebagai kejadian. Balancing yang dibatalkan tetap masuk `balancing_count` dan
`cancelled_count`, tetapi tidak masuk nominal.

`sale_count` adalah jumlah nota lunas/utang **buatan kasir tersebut** dengan tanggal nota dalam rentang yang sama, di
cabang yang difilter (filter `pos_id` tidak berpengaruh). Untuk persentase nota yang di-balancing, bandingkan secara
hati-hati: `balanced_sale_count` dihitung menurut tanggal balancing dan bisa memuat nota buatan kasir lain.

## Deploy

1. Unggah kode **termasuk** `database/sql/function.sql`.
2. `php artisan migrate` (membuat tabel `pos_balancing`, lalu membuat ulang `get_branch_report` dan
   `get_customer_report`). Jalankan segera: Dashboard CRM error selama tabel belum ada.
3. Bersihkan cache config dan route.
