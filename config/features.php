<?php

/*
| Pages that exist in the code but are not in use. They answer 404 while their flag is off; set the env
| variable to true to bring one back.
*/
return [
    // Pengeluaran (/expenditure)
    'expenditure_pages' => (bool) env('FEATURE_EXPENDITURE_PAGES', false),
    // Pemasukan (/other-book list and data; "set-selesai" stays because the order list still calls it)
    'other_book_pages' => (bool) env('FEATURE_OTHER_BOOK_PAGES', false),
    // Stok Keluar (/stock-out). The stock ledger is not affected.
    'stock_out_pages' => (bool) env('FEATURE_STOCK_OUT_PAGES', false),
    // Jenis Stok Keluar (/stock-out-type)
    'stock_out_type_pages' => (bool) env('FEATURE_STOCK_OUT_TYPE_PAGES', false),
    // Loyalty Tier (/tier). /tier/list-tier stays on because the deposito and role pages read the tier names from it.
    'tier_pages' => (bool) env('FEATURE_TIER_PAGES', false),
];
