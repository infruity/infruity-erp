DROP VIEW IF EXISTS transaction_stock_show;
CREATE VIEW transaction_stock_show AS
WITH base AS (

    -- STOCK IN
    SELECT
        0 AS branch_id,
        stock_in.product_id,
        stock_in.quantity,
        stock_in.avg_price,
        stock_in.created_at AS date,
        stock_in.updated_at,
        stock_in.code AS reff,
        'stock-in' AS url,
        stock_in.id AS id,
        stock_in.id AS detail_id,
        'stock_in' AS source_table,
        u1.nm_user AS created_by
    FROM stock_in
    LEFT JOIN users u1
        ON u1.id_user = stock_in.created_by

    UNION ALL

    -- STOCK OUT
    SELECT
        0 AS branch_id,
        stock_out.product_id,
        -stock_out.quantity AS quantity,
        stock_out.avg_price,
        stock_out.created_at AS date,
        stock_out.updated_at,
        stock_out.code AS reff,
        'stock-out' AS url,
        stock_out.id AS id,
        stock_out.id AS detail_id,
        'stock_out' AS source_table,
        u2.nm_user AS created_by
    FROM stock_out
    LEFT JOIN users u2
        ON u2.id_user = stock_out.created_by

    UNION ALL

    -- WHOLESALE
    SELECT
        wholesale.branch_id,
        wholesale_product.product_id,
        wholesale_product.quantity,
        wholesale_product.price AS avg_price,
        wholesale.created_at AS date,
        wholesale.updated_at,
        'pengadaan' AS reff,
        'wholesale' AS url,
        wholesale.id AS id,
        wholesale_product.id AS detail_id,
        'wholesale' AS source_table,
        u3.nm_user AS created_by
    FROM wholesale_product
    JOIN wholesale
        ON wholesale_product.wholesale_id = wholesale.id
    LEFT JOIN users u3
        ON u3.id_user = wholesale.created_by
    WHERE wholesale.status = 'posting'
        AND wholesale_product.product_id != 0

    UNION ALL

    -- STOCK OUT TRANSACTION
    SELECT
        0 AS branch_id,
        stock_out_transaction.product_id,
        -stock_out_transaction.quantity AS quantity,
        stock_out_transaction.avg_price,
        stock_out_transaction.created_at AS date,
        stock_out_transaction.updated_at,
        'stock-out' AS reff,
        'stock-out-transaction' AS url,
        stock_out_transaction.id AS id,
        stock_out_transaction.id AS detail_id,
        'stock_out_transaction' AS source_table,
        u4.nm_user AS created_by
    FROM stock_out_transaction
    LEFT JOIN users u4
        ON u4.id_user = stock_out_transaction.created_by

    UNION ALL

    -- STOCK OPNAME
    SELECT
        stock_opname.branch_id,
        stock_opname.product_id,
        stock_opname.difference AS quantity,
        stock_opname.avg_price,
        stock_opname.created_at AS date,
        stock_opname.updated_at,
        'stock-opname' AS reff,
        'stock-opname' AS url,
        stock_opname.id AS id,
        stock_opname.id AS detail_id,
        'stock_opname' AS source_table,
        u5.nm_user AS created_by
    FROM stock_opname
    LEFT JOIN users u5
        ON u5.id_user = stock_opname.created_by
    WHERE stock_opname.status = 'selesai'

    UNION ALL

    -- PRODUCTION (+)
    SELECT
        production.branch_id,
        production.product_id,
        production.quantity,
        NULL AS avg_price,
        production.created_at AS date,
        production.updated_at,
        'produksi' AS reff,
        'production' AS url,
        production.id AS id,
        production.id AS detail_id,
        'production' AS source_table,
        u6.nm_user AS created_by
    FROM production
    LEFT JOIN users u6
        ON u6.id_user = production.created_by
    WHERE production.status IN ('posting', 'complete')

    UNION ALL

    -- PRODUCTION DETAIL (-)
    SELECT
        production.branch_id,
        production_detail.product_id,
        -production_detail.quantity AS quantity,
        NULL AS avg_price,
        production.created_at AS date,
        production.updated_at,
        'produksi-detail' AS reff,
        'production' AS url,
        production.id AS id,
        production_detail.id AS detail_id,
        'production_detail' AS source_table,
        u7.nm_user AS created_by
    FROM production_detail
    JOIN production
        ON production.id = production_detail.production_id
    LEFT JOIN users u7
        ON u7.id_user = production.created_by
    WHERE production.status IN ('posting', 'complete')

    UNION ALL

    -- POS
    SELECT
        pos_transaction.branch_id,
        pos_transaction_detail.product_id,
        -pos_transaction_detail.quantity AS quantity,
        pos_transaction_detail.price AS avg_price,
        pos_transaction.created_at AS date,
        pos_transaction.updated_at,
        'pos' AS reff,
        'pos' AS url,
        pos_transaction.id AS id,
        pos_transaction_detail.id AS detail_id,
        'pos_transaction' AS source_table,
        u8.nm_user AS created_by
    FROM pos_transaction_detail
    JOIN pos_transaction
        ON pos_transaction.id = pos_transaction_detail.pos_id
    LEFT JOIN users u8
        ON u8.id_user = pos_transaction.created_by
    WHERE pos_transaction_detail.deleted_at IS NULL
        AND pos_transaction.status IN ('paid', 'debt')
        AND pos_transaction.deleted_at IS NULL

    UNION ALL

    -- SORTIR
    SELECT
        sortir_transaction.branch_id,
        sortir_transaction_detail.product_id,
        -sortir_transaction_detail.quantity AS quantity,
        sortir_transaction_detail.price AS avg_price,
        sortir_transaction_detail.created_at AS date,
        sortir_transaction_detail.updated_at,
        'sortir' AS reff,
        'sortir' AS url,
        sortir_transaction.id AS id,
        sortir_transaction_detail.id AS detail_id,
        'sortir_transaction' AS source_table,
        u9.nm_user AS created_by
    FROM sortir_transaction_detail
    JOIN sortir_transaction
        ON sortir_transaction_detail.sortir_id = sortir_transaction.id
    LEFT JOIN users u9
        ON u9.id_user = sortir_transaction.created_by
    WHERE sortir_transaction.status IN ('paid', 'debt')

    UNION ALL

    -- TRANSFER OUT
    SELECT
        transfer.branch_id,
        transfer_detail.product_id,
        -transfer_detail.quantity AS quantity,
        transfer_detail.price AS avg_price,
        transfer.created_at AS date,
        transfer.updated_at,
        'mengirim' AS reff,
        'transfer' AS url,
        transfer.id AS id,
        transfer_detail.id AS detail_id,
        'transfer_out' AS source_table,
        u10.nm_user AS created_by
    FROM transfer
    JOIN transfer_detail
        ON transfer_detail.transfer_id = transfer.id
    LEFT JOIN users u10
        ON u10.id_user = transfer.created_by
    WHERE transfer.status = 'selesai'

    UNION ALL

    -- TRANSFER IN
    SELECT
        transfer.branch_destination_id AS branch_id,
        transfer_detail.product_id,
        transfer_detail.quantity,
        transfer_detail.price AS avg_price,
        transfer.created_at AS date,
        transfer.updated_at,
        'menerima' AS reff,
        'transfer' AS url,
        transfer.id AS id,
        transfer_detail.id AS detail_id,
        'transfer_in' AS source_table,
        u11.nm_user AS created_by
    FROM transfer
    JOIN transfer_detail
        ON transfer_detail.transfer_id = transfer.id
    LEFT JOIN users u11
        ON u11.id_user = transfer.created_by
    WHERE transfer.status = 'selesai'

    UNION ALL

    -- POS PARCEL
    SELECT
        pos_transaction.branch_id,
        production_parcel_detail.product_id,
        -production_parcel_detail.quantity AS quantity,
        production_parcel_detail.price AS avg_price,
        pos_transaction.created_at AS date,
        pos_transaction.updated_at,
        'pos-parcel' AS reff,
        'pos' AS url,
        pos_transaction.id AS id,
        production_parcel_detail.id AS detail_id,
        'pos_parcel' AS source_table,
        u12.nm_user AS created_by
    FROM production_parcel_detail
    JOIN pos_transaction
        ON pos_transaction.id = production_parcel_detail.pos_id
    LEFT JOIN users u12
        ON u12.id_user = pos_transaction.created_by
    WHERE pos_transaction.status IN ('paid', 'debt')
        AND pos_transaction.deleted_at IS NULL

    UNION ALL

    -- POS PARCEL (KEMASAN)
    SELECT
        pos_transaction.branch_id,
        pos_transaction_detail.parcel_id AS product_id,
        -pos_transaction_detail.quantity AS quantity,
        pos_transaction_detail.price AS avg_price,
        pos_transaction.created_at AS date,
        pos_transaction.updated_at,
        'pos-parcel (kemasan)' AS reff,
        'pos' AS url,
        pos_transaction.id AS id,
        pos_transaction_detail.id AS detail_id,
        'pos_transaction' AS source_table,
        u13.nm_user AS created_by
    FROM pos_transaction_detail
    JOIN pos_transaction
        ON pos_transaction.id = pos_transaction_detail.pos_id
    LEFT JOIN users u13
        ON u13.id_user = pos_transaction.created_by
    WHERE pos_transaction_detail.deleted_at IS NULL
        AND pos_transaction.status IN ('paid', 'debt')
        AND pos_transaction.deleted_at IS NULL
)

SELECT *
FROM (

    SELECT
        base.*,

        COALESCE(pc.parent_id, base.product_id) AS stock_parent_id,

        COALESCE(
            SUM(base.quantity) OVER (
                PARTITION BY
                    COALESCE(pc.parent_id, base.product_id),
                    base.branch_id
                ORDER BY
                    base.date ASC,
                    base.source_table ASC,
                    base.id ASC,
                    base.detail_id ASC
                ROWS BETWEEN UNBOUNDED PRECEDING
                AND 1 PRECEDING
            ),
            0
        ) AS stock_awal,

        COALESCE(
            SUM(base.quantity) OVER (
                PARTITION BY
                    COALESCE(pc.parent_id, base.product_id),
                    base.branch_id
                ORDER BY
                    base.date ASC,
                    base.source_table ASC,
                    base.id ASC,
                    base.detail_id ASC
                ROWS UNBOUNDED PRECEDING
            ),
            0
        ) AS stock_akhir

    FROM base

    LEFT JOIN product_child pc
        ON pc.product_id = base.product_id

) x

-- WHERE
--     (
--         x.product_id = 834
--         OR x.stock_parent_id = 834
--     )
--     AND x.branch_id = 1

ORDER BY
    x.date DESC,
    x.source_table DESC,
    x.id DESC,
    x.detail_id DESC;