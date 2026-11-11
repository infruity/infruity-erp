/* Kasir / POS "tambah item" view helpers. Presentation only: stock, price and totals stay with posApp (js-buah). */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.PosAddItem = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    // Same fields the select2 flow reads from /ajax/listProduct, with the raw stock kept separate
    // so the view can subtract the cart live.
    function mapProductRow(item) {
        return {
            id: item.id,
            text: item.name,
            unit: item.unit,
            price: item.price,
            hpp: item.hpp,
            receipt: item.product_receipt,
            original_stock: item.get_stock?.stock_available ?? 0,
            parent_id: item.parent_product ? item.parent_product.parent_id : item.id,
        };
    }

    function availableStock(row, usedInCart) {
        return (Number(row.original_stock) || 0) - (Number(usedInCart) || 0);
    }

    function stockLabel(stock, unit) {
        if (!(stock > 0)) return 'Habis';
        const value = String(Math.round(stock * 100) / 100).replace('.', ',');
        return unit ? `${value} ${unit}` : value;
    }

    // Existing rule of the form: above 100 is rupiah, 100 or less is a percentage.
    function discountPrefix(nominal) {
        return Number(nominal) > 100 ? 'Rp' : '%';
    }

    function priceLabel(unit) {
        return unit ? `HARGA JUAL /${String(unit).toUpperCase()}` : 'HARGA JUAL';
    }

    function quantityLabel(unit) {
        return unit ? `KUANTITAS (${String(unit).toUpperCase()})` : 'KUANTITAS';
    }

    // The unit part alone, so the view can render it lighter than the label ("HARGA JUAL" + "/KG").
    function priceUnit(unit) {
        return unit ? `/${String(unit).toUpperCase()}` : '';
    }

    function quantityUnit(unit) {
        return unit ? `(${String(unit).toUpperCase()})` : '';
    }

    function listQuery(input) {
        return {term: String(input.term || '').trim(), branch: input.branch || '', status: 'aktif', limit: 50};
    }

    function createRequestGuard() {
        let latest = 0;
        return {
            next() { latest += 1; return latest; },
            isCurrent(id) { return id === latest; },
        };
    }

    return {mapProductRow, availableStock, stockLabel, discountPrefix, priceLabel, quantityLabel, priceUnit, quantityUnit, listQuery, createRequestGuard};
});
