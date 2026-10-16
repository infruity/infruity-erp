CREATE OR REPLACE VIEW product_hpp AS
WITH RECURSIVE ordered_trx AS (
    SELECT
        ROW_NUMBER() OVER (
            PARTITION BY product_id
            ORDER BY created_at, type DESC
        ) AS rn,
        product_id,
        created_at,
        qty,
        harga_satuan,
        total_belanja,
        total_non_belanja,
        type,
        remarks,
        trx_id,
        trx_type
    FROM (
        -- ================= PENGADAAN =================
        SELECT
            COALESCE(pc.parent_id, p.product_id) AS product_id,
            w.created_at,
            p.quantity AS qty,
            p.price AS harga_satuan,
            CASE
                WHEN p.total_price IS NULL OR p.total_price = 0
                THEN p.quantity * p.price
                ELSE p.total_price
            END AS total_belanja,
            0 AS total_non_belanja,
            '+' AS type,
            'PENGADAAN' AS remarks,
            p.wholesale_id AS trx_id,
            'pengadaan' AS trx_type
        FROM wholesale_product p
        JOIN wholesale w ON w.id = p.wholesale_id
        LEFT JOIN product_child pc ON pc.product_id = p.product_id
        WHERE w.status IN ('posting', 'complete')
          AND p.product_id != 0

        UNION ALL

        -- ================= BARANG BUANG =================
        SELECT
            COALESCE(pc.parent_id, b.product_id),
            b.created_at,
            -b.quantity,
            NULL,
            0,
            0,
            '-',
            'BARANG BUANG',
            b.sortir_id AS trx_id,
            'barang_buang' AS trx_type
        FROM sortir_transaction_detail b
        JOIN sortir_transaction s ON s.id = b.sortir_id
        LEFT JOIN product_child pc ON pc.product_id = b.product_id
        WHERE s.status IN ('paid', 'debt')

        UNION ALL

        -- ================= PENJUALAN =================
        SELECT
            COALESCE(pc.parent_id, d.product_id),
            d.created_at,
            -d.quantity,
            NULL,
            0,
            0,
            '-',
            'PENJUALAN',
            d.pos_id AS trx_id,
            'penjualan' AS trx_type
        FROM pos_transaction_detail d
        JOIN pos_transaction pos ON d.pos_id = pos.id
        LEFT JOIN product_child pc ON pc.product_id = d.product_id
        WHERE pos.deleted_at IS NULL
        AND d.deleted_at IS NULL
        AND pos.status IN ('paid', 'debt')

        UNION ALL

        -- ================= PRODUKSI DETAIL=================
        SELECT
            COALESCE(pc.parent_id, d.product_id),
            d.created_at,
            -d.quantity,
            NULL,
            0,
            0,
            '-',
            'BAHAN PRODUKSI',
            d.production_id AS trx_id,
            'bahan_produksi' AS trx_type
        FROM production_detail d
        JOIN production pr ON pr.id = d.production_id
        LEFT JOIN product_child pc ON pc.product_id = d.product_id
        WHERE pr.status IN ('posting', 'complete')

        UNION ALL

        -- ================= PRODUKSI =================
        SELECT
                COALESCE(pc.parent_id, d.product_id),
                d.created_at,
                d.quantity,
                p.hpp,
                d.quantity * p.hpp AS total_belanja,
                0,
                '+',
                'PRODUKSI',
                d.id AS trx_id,
                'produksi' AS trx_type
        FROM production d
        LEFT JOIN products as p ON d.product_id = p.id
        LEFT JOIN product_child pc ON pc.product_id = d.product_id
        WHERE d.status IN ('posting', 'complete')

        UNION ALL

        -- ================= OPNAME =================
        SELECT
            COALESCE(pc.parent_id, o.product_id),
            o.created_at,
            o.difference,
            o.avg_price,
            0,
            0,
            '~',
            'OPNAME',
            o.id AS trx_id,
            'opname' AS trx_type
        FROM stock_opname o
        LEFT JOIN product_child pc ON pc.product_id = o.product_id
        WHERE o.status = 'selesai'
    ) x
),

running AS (

    -- BARIS PERTAMA
    SELECT
        rn,
        product_id,
        created_at,
        qty,
        harga_satuan,
        total_belanja,
        total_non_belanja,
        type,
        remarks,
        qty AS qty_berjalan_raw,
        qty AS qty_berjalan,
        CASE WHEN qty > 0 THEN total_belanja ELSE 0 END AS total_aset_berjalan,
        trx_id,
        trx_type
    FROM ordered_trx
    WHERE rn = 1

    UNION ALL

    -- BARIS SELANJUTNYA
    SELECT
        t.rn,
        t.product_id,
        t.created_at,
        t.qty,
        t.harga_satuan,
        t.total_belanja,

        -- total_non_belanja (COGS dasar)
        CASE
            WHEN t.qty < 0 AND r.qty_berjalan != 0 THEN
                ABS(t.qty) * (r.total_aset_berjalan / NULLIF(r.qty_berjalan,0))
            ELSE 0
        END,

        t.type,
        t.remarks,

        r.qty_berjalan_raw + t.qty,
        r.qty_berjalan + t.qty,

        -- total aset berjalan
        GREATEST(0,
            CASE
                WHEN t.type = '~'
                THEN (r.qty_berjalan + t.qty) * t.harga_satuan

                WHEN t.qty > 0
                     AND r.qty_berjalan_raw < 0
                     AND (r.qty_berjalan_raw + t.qty) >= 0
                THEN (r.qty_berjalan_raw + t.qty) * t.harga_satuan

                WHEN t.qty > 0
                THEN r.total_aset_berjalan + t.total_belanja

                WHEN r.qty_berjalan != 0
                THEN r.total_aset_berjalan
                     - (ABS(t.qty) * (r.total_aset_berjalan / NULLIF(r.qty_berjalan,0)))

                ELSE r.total_aset_berjalan
            END
        ),

        t.trx_id,
        t.trx_type

    FROM running r
    JOIN ordered_trx t
      ON t.product_id = r.product_id
     AND t.rn = r.rn + 1
),

final AS (
    SELECT
        *,
        CASE
            WHEN type = '~' THEN harga_satuan
            WHEN qty_berjalan != 0
            THEN total_aset_berjalan / NULLIF(qty_berjalan,0)
            ELSE NULL
        END AS hpp_real
    FROM running
)

SELECT
    f.product_id,
    f.type,
    f.remarks,
    ABS(f.qty) AS qty,
    f.qty_berjalan_raw,
    f.qty_berjalan,
    f.harga_satuan,
    f.total_belanja,
    f.total_non_belanja,

    -- ================= COVERED QTY =================
    CASE
        WHEN f.qty < 0 THEN
            LEAST(
                ABS(f.qty),
                GREATEST(
                    LAG(f.qty_berjalan_raw)
                        OVER (PARTITION BY f.product_id ORDER BY f.rn),
                    0
                )
            )
        ELSE 0
    END AS covered_qty,

    -- ================= COGS NORMAL =================
    CASE
        WHEN f.qty < 0 THEN
            LEAST(
                ABS(f.qty),
                GREATEST(
                    LAG(f.qty_berjalan_raw)
                        OVER (PARTITION BY f.product_id ORDER BY f.rn),
                    0
                )
            )
            *
            LAG(f.hpp_real)
                OVER (PARTITION BY f.product_id ORDER BY f.rn)

        WHEN f.type='~' THEN
            -(
                (f.qty_berjalan_raw
                - LAG(f.qty_berjalan_raw)
                    OVER (PARTITION BY f.product_id ORDER BY f.rn))
                *
                LAG(f.hpp_real)
                    OVER (PARTITION BY f.product_id ORDER BY f.rn)
            )

        ELSE 0
    END AS cogs,

    -- ================= OPNAME VALUE =================
    CASE
        WHEN f.type='~' THEN
            (f.qty_berjalan_raw
            - LAG(f.qty_berjalan_raw)
                OVER (PARTITION BY f.product_id ORDER BY f.rn))
            *
            LAG(f.hpp_real)
                OVER (PARTITION BY f.product_id ORDER BY f.rn)
        ELSE 0
    END AS opname,

    -- ================= RECOVERED COGS =================
    CASE
        WHEN f.qty < 0 THEN
            GREATEST(
                ABS(f.qty)
                -
                LEAST(
                    ABS(f.qty),
                    GREATEST(
                        LAG(f.qty_berjalan_raw)
                            OVER (PARTITION BY f.product_id ORDER BY f.rn),
                        0
                    )
                ),
                0
            )
            *
            CASE
                WHEN f.qty_berjalan_raw < 0 THEN
                    (
                        SELECT f2.hpp_real
                        FROM final f2
                        WHERE f2.product_id = f.product_id
                          AND f2.rn > f.rn
                          AND f2.qty_berjalan_raw >= 0
                        ORDER BY f2.rn
                        LIMIT 1
                    )
                ELSE 0
            END
        ELSE 0
    END AS recovered_cogs,

    CASE
        WHEN f.qty_berjalan_raw >= 0
        THEN f.hpp_real
        ELSE (
            SELECT f2.hpp_real
            FROM final f2
            WHERE f2.product_id = f.product_id
              AND f2.rn > f.rn
              AND f2.qty_berjalan_raw >= 0
            ORDER BY f2.rn
            LIMIT 1
        )
    END AS hpp_berjalan,
    f.total_aset_berjalan,
    f.created_at,
    f.trx_id,
    f.trx_type,

    CASE f.trx_type
        WHEN 'pengadaan' THEN CONCAT('/wholesale/', f.trx_id, '/show')
        WHEN 'barang_buang' THEN CONCAT('/sortir/show/', f.trx_id)
        WHEN 'penjualan' THEN CONCAT('/pos/show/', f.trx_id)
        WHEN 'bahan_produksi' THEN CONCAT('/production/', f.trx_id, '/detail')
        WHEN 'produksi' THEN CONCAT('/production/', f.trx_id, '/detail')
        WHEN 'opname' THEN CONCAT('/stock-opname/', f.trx_id, '/edit')
        ELSE NULL
    END AS url

FROM final f
ORDER BY f.product_id, f.rn;
