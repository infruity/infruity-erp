-- Moving-average HPP ledger per product (variants roll up to their parent).
--
-- Rows come from transaction_stock_base, so the running qty here is the same
-- stock the rest of the app sees (same sources, same status filters).
--
-- Rules, applied row by row in time order:
--   * Pengadaan in:  aset += qty x harga beli, HPP = aset / saldo.
--   * Produksi in:   valued at production.hpp_per_unit, frozen when posted.
--   * Other in (opname +, transfer masuk, stok masuk): valued at the current
--     HPP, so the HPP does not move.
--   * Any out: valued at the current HPP. Penjualan is COGS, barang buang and
--     stok keluar are losses, bahan produksi moves into the finished product.
--   * At saldo 0 the HPP keeps its last value. It is never negative or NULL.
--   * Same timestamp: pengadaan first, then other inflows, opname, outflows.
--     Remaining ties are broken by transaction, variant and qty, so the
--     order never changes between queries.
--
-- Note: no semicolons inside this file except the final one, the migrations
-- split the file on them.
CREATE OR REPLACE VIEW product_hpp AS
WITH RECURSIVE ledger AS (
    SELECT
        COALESCE(pc.parent_id, b.product_id) AS product_id,
        b.date AS created_at,
        CAST(b.quantity AS DECIMAL(30, 6)) AS qty_signed,
        CAST(
            CASE
                WHEN b.url = 'production' AND b.reff = 'produksi'
                    THEN COALESCE(pr.hpp_per_unit, pp.hpp, 0)
                ELSE COALESCE(b.avg_price, 0)
            END
        AS DECIMAL(30, 6)) AS price,
        b.id AS trx_id,
        CASE
            WHEN b.url = 'wholesale' THEN 'pengadaan'
            WHEN b.url = 'production' AND b.reff = 'produksi' THEN 'produksi'
            WHEN b.url = 'production' THEN 'bahan_produksi'
            WHEN b.url = 'pos' THEN 'penjualan'
            WHEN b.url = 'sortir' THEN 'barang_buang'
            WHEN b.url = 'stock-out' AND b.reff = 'buang' THEN 'barang_buang'
            WHEN b.url IN ('stock-out', 'stock-out-transaction') THEN 'stok_keluar'
            WHEN b.url = 'stock-in' THEN 'stok_masuk'
            WHEN b.url = 'stock-opname' THEN 'opname'
            WHEN b.url = 'transfer' THEN 'transfer'
            ELSE b.url
        END AS trx_type,
        b.url AS source_url,
        b.reff AS source_reff,
        b.product_id AS source_product_id
    FROM transaction_stock_base b
    LEFT JOIN product_child pc ON pc.product_id = b.product_id
    LEFT JOIN production pr
        ON b.url = 'production' AND b.reff = 'produksi' AND pr.id = b.id
    LEFT JOIN products pp ON pp.id = pr.product_id
    -- App\Support\LedgerSql puts a product filter at the marker below.
    WHERE /*:ledger_filter*/ b.product_id IS NOT NULL
        AND b.product_id <> 0
        AND b.quantity <> 0
),

ordered AS (
    SELECT
        l.*,
        ROW_NUMBER() OVER (
            PARTITION BY l.product_id
            ORDER BY
                l.created_at,
                CASE
                    WHEN l.trx_type = 'pengadaan' THEN 1
                    WHEN l.qty_signed > 0 AND l.trx_type <> 'opname' THEN 2
                    WHEN l.trx_type = 'opname' THEN 3
                    ELSE 4
                END,
                l.trx_id,
                l.source_url,
                l.source_reff,
                -- Variants of one note share all keys above, so finish on the
                -- variant and qty to keep the order the same on every query.
                l.source_product_id,
                l.qty_signed
        ) AS rn
    FROM ledger l
),

running AS (
    -- Start state per product: nothing in stock, no HPP yet.
    SELECT
        p.product_id,
        0 AS rn,
        CAST(0 AS DECIMAL(30, 6)) AS saldo,
        CAST(0 AS DECIMAL(30, 6)) AS aset,
        CAST(0 AS DECIMAL(30, 6)) AS hpp,
        CAST(0 AS DECIMAL(30, 6)) AS harga_satuan,
        CAST(0 AS DECIMAL(30, 6)) AS nilai_masuk,
        CAST(0 AS DECIMAL(30, 6)) AS nilai_keluar,
        CAST(0 AS DECIMAL(30, 6)) AS covered_qty
    FROM (SELECT DISTINCT product_id FROM ordered) p

    UNION ALL

    SELECT
        t.product_id,
        t.rn,

        -- saldo
        CAST(r.saldo + t.qty_signed AS DECIMAL(30, 6)),

        -- aset
        CAST(
            CASE
                WHEN r.saldo + t.qty_signed <= 0 THEN 0
                WHEN t.qty_signed > 0 AND r.saldo < 0
                    THEN (r.saldo + t.qty_signed) * (
                        CASE
                            WHEN t.trx_type IN ('pengadaan', 'produksi') THEN t.price
                            WHEN r.hpp > 0 THEN r.hpp
                            ELSE t.price
                        END)
                WHEN t.qty_signed > 0
                    THEN r.aset + t.qty_signed * (
                        CASE
                            WHEN t.trx_type IN ('pengadaan', 'produksi') THEN t.price
                            WHEN r.hpp > 0 THEN r.hpp
                            ELSE t.price
                        END)
                ELSE GREATEST(0, r.aset + t.qty_signed * r.hpp)
            END
        AS DECIMAL(30, 6)),

        -- hpp
        CAST(
            CASE
                WHEN t.qty_signed < 0 THEN r.hpp
                WHEN r.saldo + t.qty_signed > 0 AND r.saldo < 0
                    THEN (
                        CASE
                            WHEN t.trx_type IN ('pengadaan', 'produksi') THEN t.price
                            WHEN r.hpp > 0 THEN r.hpp
                            ELSE t.price
                        END)
                WHEN r.saldo + t.qty_signed > 0
                    THEN (r.aset + t.qty_signed * (
                        CASE
                            WHEN t.trx_type IN ('pengadaan', 'produksi') THEN t.price
                            WHEN r.hpp > 0 THEN r.hpp
                            ELSE t.price
                        END)) / (r.saldo + t.qty_signed)
                WHEN t.trx_type IN ('pengadaan', 'produksi') THEN t.price
                ELSE r.hpp
            END
        AS DECIMAL(30, 6)),

        -- harga_satuan: unit value this row moved stock at
        CAST(
            CASE
                WHEN t.qty_signed < 0 THEN r.hpp
                WHEN t.trx_type IN ('pengadaan', 'produksi') THEN t.price
                WHEN r.hpp > 0 THEN r.hpp
                ELSE t.price
            END
        AS DECIMAL(30, 6)),

        -- nilai_masuk
        CAST(
            CASE
                WHEN t.qty_signed > 0 THEN t.qty_signed * (
                    CASE
                        WHEN t.trx_type IN ('pengadaan', 'produksi') THEN t.price
                        WHEN r.hpp > 0 THEN r.hpp
                        ELSE t.price
                    END)
                ELSE 0
            END
        AS DECIMAL(30, 6)),

        -- nilai_keluar: the last units out take the whole remaining aset, so
        -- rounding never leaves value behind at saldo 0.
        CAST(
            CASE
                WHEN t.qty_signed >= 0 THEN 0
                WHEN r.saldo > 0 AND r.saldo + t.qty_signed = 0 THEN r.aset
                ELSE -t.qty_signed * r.hpp
            END
        AS DECIMAL(30, 6)),

        -- covered_qty: outgoing qty that was actually in stock
        CAST(
            CASE
                WHEN t.qty_signed < 0 THEN LEAST(-t.qty_signed, GREATEST(r.saldo, 0))
                ELSE 0
            END
        AS DECIMAL(30, 6))

    FROM running r
    JOIN ordered t
        ON t.product_id = r.product_id
        AND t.rn = r.rn + 1
)

SELECT
    o.product_id,
    o.rn AS seq,
    CASE WHEN o.qty_signed > 0 THEN '+' ELSE '-' END AS type,
    CASE o.trx_type
        WHEN 'pengadaan' THEN 'PENGADAAN'
        WHEN 'produksi' THEN 'PRODUKSI'
        WHEN 'bahan_produksi' THEN 'BAHAN PRODUKSI'
        WHEN 'penjualan' THEN 'PENJUALAN'
        WHEN 'barang_buang' THEN 'BARANG BUANG'
        WHEN 'stok_keluar' THEN 'STOK KELUAR'
        WHEN 'stok_masuk' THEN 'STOK MASUK'
        WHEN 'opname' THEN 'OPNAME'
        WHEN 'transfer' THEN IF(o.qty_signed > 0, 'TRANSFER MASUK', 'TRANSFER KELUAR')
        ELSE UPPER(o.trx_type)
    END AS remarks,
    o.trx_type,
    ABS(o.qty_signed) AS qty,
    r.saldo AS qty_berjalan_raw,
    r.saldo AS qty_berjalan,
    r.harga_satuan,
    CASE WHEN o.trx_type = 'pengadaan' THEN r.nilai_masuk ELSE 0 END AS total_belanja,
    r.nilai_masuk,
    r.nilai_keluar,
    r.nilai_keluar AS total_non_belanja,
    r.covered_qty,
    CASE WHEN o.trx_type = 'penjualan' THEN r.nilai_keluar ELSE 0 END AS cogs,
    CASE WHEN o.trx_type = 'opname' THEN r.nilai_masuk - r.nilai_keluar ELSE 0 END AS opname,
    r.hpp AS hpp_berjalan,
    r.aset AS total_aset_berjalan,
    o.created_at,
    o.trx_id,
    CASE o.trx_type
        WHEN 'pengadaan' THEN CONCAT('/wholesale/', o.trx_id, '/show')
        WHEN 'penjualan' THEN CONCAT('/pos/show/', o.trx_id)
        WHEN 'produksi' THEN CONCAT('/production/', o.trx_id, '/detail')
        WHEN 'bahan_produksi' THEN CONCAT('/production/', o.trx_id, '/detail')
        WHEN 'opname' THEN CONCAT('/stock-opname/', o.trx_id, '/edit')
        WHEN 'barang_buang' THEN IF(o.source_url = 'sortir', CONCAT('/sortir/show/', o.trx_id), NULL)
        ELSE NULL
    END AS url
FROM running r
JOIN ordered o
    ON o.product_id = r.product_id
    AND o.rn = r.rn
WHERE r.rn > 0
ORDER BY o.product_id, o.rn;
