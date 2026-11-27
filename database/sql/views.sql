DROP VIEW IF EXISTS view_wholesale;
CREATE VIEW view_wholesale AS
SELECT
    wholesale.id AS id,
    wholesale.branch_id AS branch_id,
    wholesale.order_number AS order_number,
    wholesale.status AS status,
    wholesale.order_date,
    wholesale.created_at,
    COUNT(wholesale_product.id) AS total_product,
	users.nm_user AS created_by
FROM wholesale
JOIN wholesale_product ON wholesale_product.wholesale_id = wholesale.id
LEFT JOIN users ON users.id_user = wholesale.created_by
GROUP BY
    wholesale.id,
    wholesale.status,
    wholesale.order_date,
    wholesale.created_at;

-- Sortir view
DROP VIEW IF EXISTS sortir_view;
CREATE VIEW sortir_view AS
SELECT
    A.*,
    B.product_id,
    SUM( B.quantity ) AS stock_available,
    AVG( B.avg_price ) AS avg_hpp,
    C.abbreviation AS satuan
FROM
    products AS A
    LEFT JOIN transaction_stock AS B ON A.id = B.product_id
    LEFT JOIN product_units AS C ON A.product_unit = C.id
	WHERE A.is_variant IS NULL
GROUP BY
    A.id, B.product_id, C.abbreviation
ORDER BY
    stock_available DESC;

-- Product Stock
-- DROP VIEW IF EXISTS product_stock;
-- CREATE VIEW product_stock AS
SELECT
    A.*,
    C.abbreviation AS unit,
    COALESCE(SUM(B.quantity), 0) AS stock_available,
    AVG(B.avg_price) AS avg_hpp,
    CASE
        WHEN COALESCE(SUM(B.quantity), 0) < 0 THEN 'danger'
        WHEN COALESCE(SUM(B.quantity), 0) = 0 THEN ''
        WHEN COALESCE(SUM(B.quantity), 0) <= A.limit THEN 'warning'
        ELSE 'success'
    END AS stock_status
FROM
    products AS A
    LEFT JOIN transaction_stock AS B ON A.id = B.product_id
        LEFT JOIN product_units AS C ON C.id = A.product_unit
GROUP BY
    A.id, C.abbreviation;

-- View for Customer Tier
DROP VIEW IF EXISTS vw_customer_tier;
CREATE OR REPLACE VIEW vw_customer_tier AS
WITH customer_exp AS (
    SELECT customer_id, SUM(customer_exp) AS customer_exp
	FROM (
				SELECT SUM(A.exp_value) AS customer_exp, B.customer_id
					FROM pos_transaction_detail AS A
					JOIN pos_transaction AS B ON A.pos_id = B.id
				WHERE B.status = 'paid' AND A.deleted_at IS NULL
					GROUP BY B.customer_id
				UNION
					SELECT SUM(exp), customer_id FROM deposito
					GROUP BY customer_id
				UNION
					SELECT -SUM(exp), customer_id FROM crm_point_decrement
					GROUP BY customer_id
		) AS Q
		GROUP BY Q.customer_id
),
tier_range AS (
    SELECT
        id AS tier_id,
		level AS tier_level,
        name AS tier_name,
		icon,
        exp AS min_exp,
		minimal_purchase,
		style AS tier_style,
		voucher,
		discount_transaction AS discount,
        LEAD(exp) OVER (ORDER BY `level`) AS max_exp
    FROM crm_tier
)
SELECT
    ce.customer_id,
    ce.customer_exp,
	tr.*,
	ROUND(
        IF(tr.max_exp IS NOT NULL,
            (ce.customer_exp / tr.max_exp) * 100,
            100
        ), 2
    ) AS progress_percentage
FROM customer_exp ce
JOIN tier_range tr
    ON ce.customer_exp >= tr.min_exp
    AND (ce.customer_exp < tr.max_exp OR tr.max_exp IS NULL);

-- View Report Customer
DROP VIEW IF EXISTS vw_customer_report;
CREATE OR REPLACE VIEW vw_customer_report AS
SELECT
    c.id AS id_customer,
    c.name AS nama_customer,
    c.type AS type_customer,
    COUNT(t.id) AS jumlah_transaksi,
    SUM(t.total) AS total_transaksi,
    MAX(t.date) AS transaksi_terbaru,
    DATEDIFF(CURDATE(), MAX(t.date)) AS recency
FROM customer c
JOIN pos_transaction t ON t.customer_id = c.id
WHERE t.deleted_at IS NULL AND t.status IN ('paid', 'debt')
GROUP BY c.id, c.name, c.type;

-- View Deposito
DROP VIEW IF EXISTS vw_customer_deposito;
CREATE VIEW vw_customer_deposito AS
SELECT
	A.*,
	B.voucher AS nominal_using_voucher,
	B.voucher_qty AS total_used_voucher,
	( A.voucher_qty - COALESCE ( B.voucher_qty, 0 ) ) AS quantity,
	( A.deposito - COALESCE ( B.voucher, 0 ) ) AS nominal_remaining
FROM
	`deposito` AS A
	LEFT JOIN (
		SELECT
			customer_id, deposito_id, SUM( voucher ) AS voucher, SUM( voucher_qty ) AS voucher_qty
		FROM
			pos_transaction
		GROUP BY
			customer_id, deposito_id
	) AS B ON A.id = B.deposito_id;

DROP VIEW IF EXISTS vw_customer_deposito_tansaction;
CREATE VIEW vw_customer_deposito_tansaction AS
SELECT
	*
FROM
	(
		SELECT
			customer_id, deposito_date, deposito, voucher_qty
		FROM
			deposito
		UNION
		SELECT
			customer_id, date, -voucher, -voucher_qty
		FROM
			pos_transaction
		WHERE voucher > 0
	) Q
ORDER BY
	deposito_date;
