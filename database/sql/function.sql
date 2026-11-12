DROP PROCEDURE IF EXISTS get_customer_report;
CREATE PROCEDURE get_customer_report ( IN start_date DATE, IN end_date DATE ) BEGIN-- hitung total omset & hpp untuk prosentase
	WITH
	transaksi AS (
		SELECT customer_id, SUM( total ) AS total_omset FROM pos_transaction
		WHERE `date` BETWEEN start_date AND end_date
        AND deleted_at IS NULL
        GROUP BY customer_id ),
	hpp AS (
		SELECT
			B.customer_id,
			SUM( A.hpp ) AS hpp
		FROM
			pos_transaction_detail A
			JOIN pos_transaction B ON A.pos_id = B.id
		WHERE B.`date` BETWEEN start_date AND end_date
		AND B.deleted_at IS NULL
		AND B.status IN ('paid', 'debt')
		GROUP BY
			B.customer_id
		)
		SELECT
		t.customer_id,
		C.name,
		( SELECT COUNT(*) FROM pos_transaction WHERE customer_id = t.customer_id ) AS total_transaction,
		t.total_omset,
		h.hpp,
		( t.total_omset - h.hpp ) AS profit,
		ROUND( t.total_omset * 100.0 / ( SELECT SUM( total ) FROM pos_transaction ), 2 ) AS prosentase_omset,
		ROUND( h.hpp * 100.0 / ( SELECT SUM( hpp ) FROM pos_transaction_detail JOIN pos_transaction ON pos_transaction_detail.pos_id = pos_transaction.id ), 2 ) AS prosentase_hpp,
		ROUND((
				t.total_omset - h.hpp
				) * 100.0 / ((
				SELECT
					SUM( total )
				FROM
					pos_transaction
				) - ( SELECT SUM( hpp ) FROM pos_transaction_detail JOIN pos_transaction ON pos_transaction_detail.pos_id = pos_transaction.id )),
			2
		) AS prosentase_profit
	FROM
		transaksi t
		JOIN hpp h ON t.customer_id = h.customer_id
		JOIN customer AS C ON t.customer_id = C.id;

END;

-- Branch Report
DROP PROCEDURE IF EXISTS get_branch_report;
CREATE PROCEDURE get_branch_report(IN start_date DATE, IN end_date DATE)
BEGIN
    WITH transaksi AS (
        SELECT
            B.branch_id,
            SUM(B.total) AS total_omset,
            COUNT(B.id) AS total_transaksi
        FROM pos_payment AS A
        JOIN pos_transaction AS B ON A.pos_id = B.id
        WHERE B.`date` BETWEEN start_date AND end_date
        AND A.deleted_at IS NULL
		AND B.deleted_at IS NULL
		AND B.status IN ('paid', 'debt')
        GROUP BY B.branch_id
    ),
    hpp AS (
        SELECT
            A.branch_id,
            SUM(B.hpp) AS hpp
        FROM pos_transaction AS A
        JOIN pos_transaction_detail AS B ON A.id = B.pos_id
        JOIN pos_payment AS C ON A.id = C.pos_id
        WHERE A.`date` BETWEEN start_date AND end_date
        AND A.deleted_at IS NULL
		AND A.status IN ('paid', 'debt')
        GROUP BY A.branch_id
    )
    SELECT
        A.branch_id,
        C.`name`,
        A.total_omset,
		A.total_transaksi AS total_transaction,
        B.hpp,
        (A.total_omset - B.hpp) AS profit,
        ROUND(A.total_omset * 100.0 / (SELECT SUM(total_omset) FROM transaksi), 2) AS prosentase_omset,
        ROUND(B.hpp * 100.0 / (SELECT SUM(hpp) FROM hpp), 2) AS prosentase_profit
    FROM transaksi AS A
    JOIN hpp AS B ON A.branch_id = B.branch_id
    JOIN branch AS C ON C.id = A.branch_id
    ORDER BY A.total_omset DESC;
END;

-- Report Branch Product
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
		LEFT JOIN pos_payment AS pp ON pp.pos_id = A.id
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
