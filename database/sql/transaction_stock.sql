DROP VIEW IF EXISTS transaction_stock;
CREATE VIEW transaction_stock AS
WITH base AS (
	-- 	STOCK IN
	SELECT
		0 AS branch_id,
		product_id,
		quantity,
		avg_price,
		stock_in.created_at AS date,
		stock_in.`code` AS reff,
		'stock-in' AS url,
		stock_in.id AS id,
		u1.nm_user AS created_by
	FROM stock_in
	LEFT JOIN users u1 ON u1.id_user = stock_in.created_by

	UNION ALL

	-- STOCK OUT
	SELECT
		0 AS branch_id,
		product_id,
		- stock_out.quantity,
		stock_out.avg_price,
		stock_out.created_at,
		stock_out.`code`,
		'stock-out',
		stock_out.id,
		u2.nm_user
	FROM stock_out
	LEFT JOIN users u2 ON u2.id_user = stock_out.created_by

	UNION ALL

	-- WHOLESALE
	SELECT
		wholesale.branch_id,
		wholesale_product.product_id,
		wholesale_product.quantity,
		wholesale_product.price,
		wholesale.created_at,
		'pengadaan' AS reff,
		'wholesale' AS url,
		wholesale.id AS id,
		u3.nm_user
	FROM wholesale_product
	JOIN wholesale ON wholesale_product.wholesale_id = wholesale.id
	LEFT JOIN users u3 ON u3.id_user = wholesale.created_by
	WHERE wholesale.`status` IN ('posting', 'complete')
		AND wholesale_product.product_id != 0

	UNION ALL

	-- STOCK OUT TRANSACTION
	SELECT
		0 AS branch_id,
		product_id,
		- stock_out_transaction.quantity,
		stock_out_transaction.avg_price,
		stock_out_transaction.created_at,
		'stock-out' AS reff,
		'stock-out-transaction' AS url,
		stock_out_transaction.id,
		u4.nm_user
	FROM stock_out_transaction
	LEFT JOIN users u4 ON u4.id_user = stock_out_transaction.created_by

	UNION ALL

	-- STOCK OPNAME
	SELECT
		stock_opname.branch_id,
		stock_opname.product_id,
		stock_opname.difference,
		stock_opname.avg_price,
		stock_opname.created_at,
		'stock-opname' AS reff,
		'stock-opname' AS url,
		stock_opname.id,
		u5.nm_user
	FROM stock_opname
	LEFT JOIN users u5 ON u5.id_user = stock_opname.created_by
	WHERE stock_opname.status = 'selesai'

	UNION ALL

	-- PRODUCTION (+)
	SELECT
		production.branch_id,
		production.product_id,
		production.quantity,
		NULL,
		production.created_at,
		'produksi' AS reff,
		'production' AS url,
		production.id,
		u6.nm_user
	FROM production
	LEFT JOIN users u6 ON u6.id_user = production.created_by
	WHERE production.`status` IN ('posting', 'complete')

	UNION ALL

	-- PRODUCTION DETAIL (-)
	SELECT
		production.branch_id,
		production_detail.product_id,
		- production_detail.quantity,
		NULL,
		production.created_at,
		'produksi-detail' AS reff,
		'production' AS url,
		production.id AS id,
		u7.nm_user
	FROM production_detail
	JOIN production ON production.id = production_detail.production_id
	LEFT JOIN users u7 ON u7.id_user = production.created_by
	WHERE production.`status` IN ('posting', 'complete')

	UNION ALL

	-- POS
	SELECT
		pos_transaction.branch_id,
		pos_transaction_detail.product_id,
		- pos_transaction_detail.quantity,
		pos_transaction_detail.price,
		pos_transaction.created_at,
		'pos' AS reff,
		'pos' AS url,
		pos_transaction.id AS id,
		u8.nm_user
	FROM pos_transaction_detail
	JOIN pos_transaction ON pos_transaction.id = pos_transaction_detail.pos_id
	LEFT JOIN users u8 ON u8.id_user = pos_transaction.created_by
	WHERE pos_transaction_detail.deleted_at IS NULL
		AND pos_transaction.`status` IN ('paid', 'debt')
		AND pos_transaction.deleted_at IS NULL

	UNION ALL

	-- SORTIR
	SELECT
		sortir_transaction.branch_id,
		sortir_transaction_detail.product_id,
		- sortir_transaction_detail.quantity,
		sortir_transaction_detail.price,
		sortir_transaction_detail.created_at,
		'sortir' AS reff,
		'sortir' AS url,
		sortir_transaction.id AS id,
		u9.nm_user
	FROM sortir_transaction_detail
	JOIN sortir_transaction ON sortir_transaction_detail.sortir_id = sortir_transaction.id
	LEFT JOIN users u9 ON u9.id_user = sortir_transaction.created_by
	WHERE sortir_transaction.`status` IN ('paid', 'debt')

	UNION ALL

	-- TRANSFER (ASAL)
	SELECT
		transfer.branch_id,
		transfer_detail.product_id,
		- transfer_detail.quantity,
		transfer_detail.price,
		transfer.created_at,
		'mengirim' AS reff,
		'transfer' AS url,
		transfer.id AS id,
		u10.nm_user
	FROM transfer
	JOIN transfer_detail ON transfer_detail.transfer_id = transfer.id
	LEFT JOIN users u10 ON u10.id_user = transfer.created_by
	WHERE transfer.`status` = 'selesai'

	UNION ALL

	-- TRANSFER (TUJUAN)
	SELECT
		transfer.branch_destination_id,
		transfer_detail.product_id,
		transfer_detail.quantity,
		transfer_detail.price,
		transfer.created_at,
		'menerima' AS reff,
		'transfer' AS url,
		transfer.id AS id,
		u11.nm_user
	FROM transfer
	JOIN transfer_detail ON transfer_detail.transfer_id = transfer.id
	LEFT JOIN users u11 ON u11.id_user = transfer.created_by
	WHERE transfer.`status` = 'selesai'

	UNION ALL

	-- POS PARCEL
	SELECT
		pos_transaction.branch_id,
		production_parcel_detail.product_id,
		- production_parcel_detail.quantity,
		production_parcel_detail.price,
		pos_transaction.created_at,
		'pos-parcel' AS reff,
		'pos' AS url,
		pos_transaction.id AS id,
		u12.nm_user
	FROM production_parcel_detail
	JOIN pos_transaction ON pos_transaction.id = production_parcel_detail.pos_id
	LEFT JOIN users u12 ON u12.id_user = pos_transaction.created_by
	WHERE pos_transaction.`status` IN ('paid', 'debt')
	AND pos_transaction.deleted_at IS NULL

	UNION ALL

	-- POS
	SELECT
		pos_transaction.branch_id,
		pos_transaction_detail.parcel_id AS product_id,
		- pos_transaction_detail.quantity,
		pos_transaction_detail.price,
		pos_transaction.created_at,
		'pos-parcel (kemasan)' AS reff,
		'pos' AS url,
		pos_transaction.id AS id,
		u8.nm_user
	FROM pos_transaction_detail
	JOIN pos_transaction ON pos_transaction.id = pos_transaction_detail.pos_id
	LEFT JOIN users u8 ON u8.id_user = pos_transaction.created_by
	WHERE pos_transaction_detail.deleted_at IS NULL
		AND pos_transaction.`status` IN ('paid', 'debt')
		AND pos_transaction.deleted_at IS NULL
)
SELECT *,
	COALESCE(SUM(quantity) OVER (
		PARTITION BY product_id, branch_id
		ORDER BY date, id, url, reff
		ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING
	), 0) AS stock_awal,
	COALESCE(SUM(quantity) OVER (
		PARTITION BY product_id, branch_id
		ORDER BY date, id, url, reff
		ROWS UNBOUNDED PRECEDING
	), 0) AS stock_akhir
FROM base;
