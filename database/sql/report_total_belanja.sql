-- Per-product value summary of the product_hpp ledger.
--
-- Control check (total_opname and total_transfer are net, + means value in):
--   total_belanja + total_masuk_lain + total_opname + total_transfer
--   = total_cogs + total_kerugian + total_bahan_produksi + last_asset
-- selisih_control shows any difference (0 when the ledger balances).
CREATE OR REPLACE VIEW report_total_belanja AS
SELECT
    h.product_id,

    SUM(CASE WHEN h.trx_type = 'pengadaan' THEN h.nilai_masuk ELSE 0 END) AS total_belanja,
    SUM(CASE WHEN h.trx_type NOT IN ('pengadaan', 'opname', 'transfer') THEN h.nilai_masuk ELSE 0 END) AS total_masuk_lain,

    SUM(CASE WHEN h.trx_type = 'penjualan' THEN h.nilai_keluar ELSE 0 END) AS total_cogs,
    SUM(CASE WHEN h.trx_type IN ('barang_buang', 'stok_keluar') THEN h.nilai_keluar ELSE 0 END) AS total_kerugian,
    SUM(CASE WHEN h.trx_type = 'bahan_produksi' THEN h.nilai_keluar ELSE 0 END) AS total_bahan_produksi,
    SUM(CASE WHEN h.trx_type = 'opname' THEN h.nilai_masuk - h.nilai_keluar ELSE 0 END) AS total_opname,
    SUM(CASE WHEN h.trx_type = 'transfer' THEN h.nilai_masuk - h.nilai_keluar ELSE 0 END) AS total_transfer,

    MAX(CASE WHEN h.seq_desc = 1 THEN h.total_aset_berjalan END) AS last_asset,

    SUM(h.nilai_masuk) - SUM(h.nilai_keluar)
        - MAX(CASE WHEN h.seq_desc = 1 THEN h.total_aset_berjalan END) AS selisih_control

FROM (
    SELECT
        p.*,
        ROW_NUMBER() OVER (PARTITION BY p.product_id ORDER BY p.seq DESC) AS seq_desc
    FROM product_hpp p
) h
GROUP BY h.product_id;
