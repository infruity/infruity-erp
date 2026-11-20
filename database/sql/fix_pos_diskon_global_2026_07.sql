-- Koreksi data: pos_transaction_detail.diskon_global yang rusak pada nota 21-28 Juli 2026.
--
-- Penyebab (sudah ditambal di kode, commit 44be3932): PosController::saveTransaction membagi dengan
-- subtotal kiriman klien (bernilai 100), sehingga diskon pro-rata tiap baris menjadi ribuan kali
-- subtotal baris. Laporan Penjualan dan Laba memakai (subtotal - diskon_global), jadi pendapatan
-- per produk muncul negatif ratusan juta.
--
-- Cara pakai (JALANKAN MANUAL, satu transaksi, tanpa artisan):
--   1. Backup dulu: bagian BACKUP di bawah.
--   2. Jalankan PREVIEW, pastikan 9 nota dan nilai baru masuk akal
--      (jumlah diskon_global baru per nota = pos_transaction.discount).
--   3. Jalankan UPDATE, lalu VERIFIKASI harus mengembalikan 0 baris.
--
-- Rumus: diskon_global baris = subtotal baris / total subtotal nota * nilai diskon nota.
-- Nilai diskon nota = pos_transaction.discount jika > 100 (nominal), selain itu persen dari subtotal.
-- Hanya nota yang punya baris dengan diskon_global > subtotal yang disentuh.

-- ===== BACKUP =====
CREATE TABLE IF NOT EXISTS pos_transaction_detail_diskon_backup_2026_07 AS
SELECT d.id, d.pos_id, d.diskon_global, NOW() AS backed_up_at
FROM pos_transaction_detail d
WHERE d.deleted_at IS NULL
  AND d.pos_id IN (
      SELECT pos_id FROM (
          SELECT pos_id FROM pos_transaction_detail
          WHERE deleted_at IS NULL AND diskon_global > subtotal + 0.01
      ) x
  );

-- ===== PREVIEW =====
SELECT d.id, d.pos_id, d.subtotal, d.diskon_global AS lama,
       ROUND(d.subtotal / s.sum_sub *
             CASE WHEN t.discount > 100 THEN t.discount ELSE s.sum_sub * t.discount / 100 END, 4) AS baru
FROM pos_transaction_detail d
JOIN pos_transaction t ON t.id = d.pos_id
JOIN (SELECT pos_id, SUM(subtotal) AS sum_sub
      FROM pos_transaction_detail WHERE deleted_at IS NULL GROUP BY pos_id) s ON s.pos_id = d.pos_id
WHERE d.deleted_at IS NULL
  AND d.pos_id IN (SELECT pos_id FROM pos_transaction_detail_diskon_backup_2026_07)
  AND s.sum_sub > 0
ORDER BY d.pos_id, d.id;

-- ===== UPDATE =====
UPDATE pos_transaction_detail d
JOIN pos_transaction t ON t.id = d.pos_id
JOIN (SELECT pos_id, SUM(subtotal) AS sum_sub
      FROM pos_transaction_detail WHERE deleted_at IS NULL GROUP BY pos_id) s ON s.pos_id = d.pos_id
SET d.diskon_global = ROUND(d.subtotal / s.sum_sub *
        CASE WHEN t.discount > 100 THEN t.discount ELSE s.sum_sub * t.discount / 100 END, 4)
WHERE d.deleted_at IS NULL
  AND d.pos_id IN (SELECT pos_id FROM pos_transaction_detail_diskon_backup_2026_07)
  AND s.sum_sub > 0;

-- ===== VERIFIKASI (harus 0 baris) =====
SELECT d.id, d.pos_id, d.subtotal, d.diskon_global
FROM pos_transaction_detail d
WHERE d.deleted_at IS NULL AND d.diskon_global > d.subtotal + 0.01;

-- ===== ROLLBACK (bila perlu) =====
-- UPDATE pos_transaction_detail d
-- JOIN pos_transaction_detail_diskon_backup_2026_07 b ON b.id = d.id
-- SET d.diskon_global = b.diskon_global;
