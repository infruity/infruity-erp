DROP PROCEDURE IF EXISTS get_customer_report;
CREATE PROCEDURE get_customer_report(IN start_date DATE, IN end_date DATE)
BEGIN
    -- Only paid or owed sales of the period count: drafts did, and HPP was not multiplied by the quantity.
    WITH sales AS (
        SELECT id, customer_id, total
        FROM pos_transaction
        WHERE `date` BETWEEN start_date AND end_date
          AND deleted_at IS NULL
          AND status IN ('paid', 'debt')
    ),
    transaksi AS (
        SELECT customer_id, SUM(total) AS total_omset, COUNT(*) AS total_transaksi
        FROM sales
        GROUP BY customer_id
    ),
    hpp AS (
        SELECT S.customer_id, SUM(COALESCE(D.subtotal_hpp, D.hpp * D.quantity, 0)) AS hpp
        FROM sales AS S
        JOIN pos_transaction_detail AS D ON D.pos_id = S.id AND D.deleted_at IS NULL
        GROUP BY S.customer_id
    ),
    totals AS (
        SELECT (SELECT SUM(total_omset) FROM transaksi) AS omset, (SELECT SUM(hpp) FROM hpp) AS hpp
    )
    SELECT
        t.customer_id,
        C.name,
        t.total_transaksi AS total_transaction,
        t.total_omset,
        COALESCE(h.hpp, 0) AS hpp,
        (t.total_omset - COALESCE(h.hpp, 0)) AS profit,
        ROUND(t.total_omset * 100.0 / x.omset, 2) AS prosentase_omset,
        ROUND(COALESCE(h.hpp, 0) * 100.0 / x.hpp, 2) AS prosentase_hpp,
        ROUND((t.total_omset - COALESCE(h.hpp, 0)) * 100.0 / (x.omset - COALESCE(x.hpp, 0)), 2) AS prosentase_profit
    FROM transaksi t
    LEFT JOIN hpp h ON t.customer_id = h.customer_id
    CROSS JOIN totals x
    JOIN customer AS C ON t.customer_id = C.id
    ORDER BY t.total_omset DESC;
END;

DROP PROCEDURE IF EXISTS get_branch_report;
CREATE PROCEDURE get_branch_report(IN start_date DATE, IN end_date DATE)
BEGIN
    -- One row per paid or owed sale: joining the payments counted a sale paid in instalments once per payment.
    WITH sales AS (
        SELECT id, branch_id, total
        FROM pos_transaction
        WHERE `date` BETWEEN start_date AND end_date
          AND deleted_at IS NULL
          AND status IN ('paid', 'debt')
    ),
    transaksi AS (
        SELECT branch_id, SUM(total) AS total_omset, COUNT(*) AS total_transaksi
        FROM sales
        GROUP BY branch_id
    ),
    hpp AS (
        SELECT S.branch_id, SUM(COALESCE(D.subtotal_hpp, D.hpp * D.quantity, 0)) AS hpp
        FROM sales AS S
        JOIN pos_transaction_detail AS D ON D.pos_id = S.id AND D.deleted_at IS NULL
        GROUP BY S.branch_id
    )
    SELECT
        A.branch_id,
        C.`name`,
        A.total_omset,
        A.total_transaksi AS total_transaction,
        COALESCE(B.hpp, 0) AS hpp,
        (A.total_omset - COALESCE(B.hpp, 0)) AS profit,
        ROUND(A.total_omset * 100.0 / (SELECT SUM(total_omset) FROM transaksi), 2) AS prosentase_omset,
        ROUND(COALESCE(B.hpp, 0) * 100.0 / (SELECT SUM(hpp) FROM hpp), 2) AS prosentase_profit
    FROM transaksi AS A
    LEFT JOIN hpp AS B ON A.branch_id = B.branch_id
    JOIN branch AS C ON C.id = A.branch_id
    ORDER BY A.total_omset DESC;
END;

DROP PROCEDURE IF EXISTS get_branch_product_report;
CREATE PROCEDURE get_branch_product_report(IN start_date DATE, IN end_date DATE)
BEGIN
	SELECT
		pos.branch_id,
		D.NAME AS branch,
		A.product_id,
		C.NAME AS product,
		SUM( A.quantity ) AS quantity,
		E.abbreviation AS satuan,
		COUNT(pos.id) AS jumlah_transaksi,
		GROUP_CONCAT(pos.id) AS list_pos_id,
		GROUP_CONCAT(pos.invoice_number) AS list
	FROM
		pos_transaction_detail AS A
		LEFT JOIN pos_payment AS B ON A.pos_id = B.pos_id
		JOIN pos_transaction AS pos ON A.pos_id = pos.id
		LEFT JOIN products AS C ON A.product_id = C.id
		LEFT JOIN branch AS D ON pos.branch_id = D.id
		LEFT JOIN product_units AS E ON C.product_unit = E.id
		WHERE pos.date BETWEEN start_date AND end_date
        AND pos.deleted_at IS NULL
		AND pos.status IN ('paid', 'debt')
	GROUP BY
		pos.branch_id,
		D.NAME,
		A.product_id,
		C.NAME
	ORDER BY
		pos.branch_id;
END;

-- Report Transaction Branch - Product - Customer
DROP PROCEDURE IF EXISTS get_customer_product_transaction;
CREATE PROCEDURE get_customer_product_transaction(IN start_date DATE, IN end_date DATE)
BEGIN
	SELECT
		A.created_at,
		C.NAME AS nama,
		C.gender,
		TIMESTAMPDIFF(
			YEAR,
			C.birth_of_date,
		CURDATE()) AS age,
		kabupaten.NAME AS city_name,
		kecamatan.NAME AS district_name,
		kelurahan.NAME AS village_name,
		D.id AS branch_id,
		D.NAME AS branch,
		P.NAME AS product,
		B.quantity,
		PC.abbreviation
	FROM
		pos_transaction AS A
		JOIN pos_transaction_detail AS B ON A.id = B.pos_id
		LEFT JOIN products AS P ON B.product_id = P.id
		LEFT JOIN product_units AS PC ON P.product_unit = PC.id
		LEFT JOIN customer AS C ON A.customer_id = C.id
		LEFT JOIN branch AS D ON D.id = A.branch_id
		LEFT JOIN reg_regencies AS kabupaten ON kabupaten.id = CAST(C.city AS CHAR(4))
		LEFT JOIN reg_districts AS kecamatan ON kecamatan.id = CAST(C.district AS CHAR(6))
		LEFT JOIN reg_villages AS kelurahan ON kelurahan.id = CAST(C.village AS CHAR(10))
	WHERE
		A.date BETWEEN start_date AND end_date
        AND A.deleted_at IS NULL
		AND A.status IN ('paid', 'debt');
END;
