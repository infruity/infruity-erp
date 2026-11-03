CREATE OR REPLACE VIEW product_stock AS
WITH parent_ts AS (
    SELECT
        ts.branch_id,
        ts.product_id,
        SUM(ts.quantity) AS parent_stock,
        AVG(ts.avg_price) AS avg_hpp
    -- Same rows as transaction_stock_show, without its running-balance window
    -- functions (they sort the whole ledger and are not needed for a SUM).
    FROM transaction_stock_show_base ts
    LEFT JOIN product_child pc_ts ON pc_ts.product_id = ts.product_id
    GROUP BY
        ts.branch_id,
        ts.product_id
),
child_agg AS (
    SELECT
        pc.parent_id,
        ts.branch_id,
        SUM(
            CASE
                WHEN ts.quantity < 0 THEN -ts.quantity
                ELSE ts.quantity
            END
        ) AS child_consumed,
        GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS child
    FROM product_child pc
    LEFT JOIN transaction_stock_base ts
        ON ts.product_id = pc.product_id
    JOIN products p
        ON p.id = pc.product_id
    GROUP BY
        pc.parent_id,
        ts.branch_id
),
product_stock_parent AS (
    SELECT
        p.id AS product_id,
        COALESCE(pc.parent_id, p.id) AS stock_parent_id
    FROM products p
    LEFT JOIN product_child pc ON pc.product_id = p.id
)
SELECT
    A.id,
    parent_ts.branch_id,

    A.name,
    A.sku,
    A.limit,
    A.hpp,
    A.price,
    A.tipe,
    A.category_id,
    A.product_unit,
    A.is_variant,

    C.abbreviation AS unit,

    COALESCE(parent_ts.parent_stock, 0) AS parent_stock,
    COALESCE(child_agg.child_consumed, 0) AS child_consumed,

    COALESCE(parent_ts.parent_stock, 0)
        - COALESCE(child_agg.child_consumed, 0) AS stock_available,

    COALESCE(parent_ts.avg_hpp, 0) AS avg_hpp,
    child_agg.child,

    CASE
        WHEN COALESCE(parent_ts.parent_stock, 0) < 0 THEN 'danger'
        WHEN COALESCE(parent_ts.parent_stock, 0) = 0 THEN ''
        WHEN COALESCE(parent_ts.parent_stock, 0) <= A.`limit` THEN 'warning'
        ELSE 'success'
    END AS stock_status

FROM products A
JOIN product_stock_parent psp
    ON psp.product_id = A.id
LEFT JOIN parent_ts
    ON parent_ts.product_id = psp.stock_parent_id
LEFT JOIN child_agg
    ON child_agg.parent_id = psp.stock_parent_id
   AND child_agg.branch_id = parent_ts.branch_id
LEFT JOIN product_units C
    ON C.id = A.product_unit
WHERE A.tipe != 'parcel';
-- AND A.is_variant IS NULL
-- AND LOWER(A.name) NOT LIKE '%jus%';
