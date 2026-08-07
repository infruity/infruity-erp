<?php

$resourceActions = static function (string $label, array $extra = [], array $except = []): array {
    $actions = [
        'index' => "Lihat {$label}",
        'create' => "Buka Form Tambah {$label}",
        'store' => "Simpan {$label}",
        'show' => "Detail {$label}",
        'edit' => "Buka Form Ubah {$label}",
        'update' => "Simpan Perubahan {$label}",
        'delete' => "Aksi Hapus {$label}",
        'destroy' => "Hapus {$label}",
    ];

    foreach ($except as $action) {
        unset($actions[$action]);
    }

    return array_merge($actions, $extra);
};

$indexActions = static function (string $label, array $extra = []): array {
    return array_merge([
        'index' => "Lihat {$label}",
    ], $extra);
};

return [
    'permissions' => [
        // Administrator
        'user' => [
            'label' => 'User',
            'actions' => $resourceActions('User', [], ['show']),
        ],
        'role' => [
            'label' => 'Role',
            'actions' => $resourceActions('Role', [
                'duplicate' => 'Duplikat Role',
            ]),
        ],
        'role-menu' => [
            'label' => 'Role Menu',
            'actions' => $resourceActions('Role Menu'),
        ],

        // CRM
        'crm-dashboard' => [
            'label' => 'CRM Dashboard',
            'actions' => $indexActions('CRM Dashboard', [
                'top-distribution' => 'Lihat Top Distribusi CRM',
                'top-tier' => 'Lihat Top Tier CRM',
                'tier-graphic' => 'Lihat Grafik Tier CRM',
                'gender-distribution' => 'Lihat Distribusi Gender CRM',
                'customer-distribution' => 'Lihat Distribusi Customer CRM',
            ]),
        ],
        'customer-report' => [
            'label' => 'Laporan Customer',
            'actions' => $indexActions('Laporan Customer'),
        ],
        'tier' => [
            'label' => 'Tier',
            'actions' => $resourceActions('Tier', [
                'save_detail' => 'Simpan Detail Tier',
                'get-gift' => 'Lihat Hadiah Tier',
                'list-tier' => 'Lihat Daftar Tier',
            ], ['show']),
        ],
        'setting-exp' => [
            'label' => 'Setting Exp',
            'actions' => $resourceActions('Setting Exp', [], ['show']),
        ],
        'point-schedule' => [
            'label' => 'Point Schedule',
            'actions' => $resourceActions('Point Schedule', [], ['show']),
        ],
        'deposito' => [
            'label' => 'Deposito',
            'actions' => $resourceActions('Deposito', [], ['show']),
        ],
        'customer-deposito' => [
            'label' => 'Customer Deposito',
            'actions' => $indexActions('Customer Deposito', [
                'show' => 'Detail Customer Deposito',
            ]),
        ],
        'campaign' => [
            'label' => 'Campaign',
            'actions' => $resourceActions('Campaign', [
                'get-near-event' => 'Lihat Campaign Terdekat',
            ], ['show']),
        ],

        // Master
        'products' => [
            'label' => 'Produk',
            'actions' => $resourceActions('Produk', [
                'store-variant' => 'Simpan Varian Produk',
                'update-variant' => 'Simpan Perubahan Varian Produk',
                'destroy-variant' => 'Hapus Varian Produk',
                'get-receipt' => 'Lihat Resep untuk Produksi',
                'get-product-receipt' => 'Lihat Resep Produk',
                'generate-branch-price' => 'Generate Harga Cabang',
            ]),
        ],
        'category' => [
            'label' => 'Kategori',
            'actions' => $resourceActions('Kategori', [
                'export' => 'Export Kategori',
            ], ['show']),
        ],
        'unit' => [
            'label' => 'Unit',
            'actions' => $resourceActions('Unit', [], ['show']),
        ],
        'location' => [
            'label' => 'Lokasi',
            'actions' => $resourceActions('Lokasi', [], ['show']),
        ],
        'handling' => [
            'label' => 'Penanganan',
            'actions' => $resourceActions('Penanganan', [], ['show']),
        ],
        'department' => [
            'label' => 'Department',
            'actions' => $resourceActions('Department', [
                'export' => 'Export Department',
            ], ['show']),
        ],
        'position' => [
            'label' => 'Posisi',
            'actions' => $resourceActions('Posisi', [
                'export' => 'Export Posisi',
            ], ['show']),
        ],
        'supplier' => [
            'label' => 'Supplier',
            'actions' => $resourceActions('Supplier', [], ['show']),
        ],
        'customers' => [
            'label' => 'Pelanggan',
            'actions' => $resourceActions('Pelanggan'),
        ],
        'staff' => [
            'label' => 'Staff',
            'actions' => $resourceActions('Staff'),
        ],
        'branch' => [
            'label' => 'Cabang',
            'actions' => $resourceActions('Cabang', [], ['show']),
        ],
        'payment-method' => [
            'label' => 'Metode Pembayaran',
            'actions' => $resourceActions('Metode Pembayaran', [], ['show']),
        ],
        'account' => [
            'label' => 'Akun',
            'actions' => $resourceActions('Akun', [], ['show']),
        ],
        'product-stock' => [
            'label' => 'Stok Produk',
            'actions' => $indexActions('Stok Produk', [
                'show' => 'Detail Stok Produk',
            ]),
        ],

        // Transaction
        'wholesale' => [
            'label' => 'Kulak',
            'actions' => $resourceActions('Kulak', [
                'receive_product' => 'Proses Penerimaan Kulak',
                'receive_process' => 'Lihat Proses Penerimaan Kulak',
                'save-receive' => 'Simpan Penerimaan Kulak',
                'get-product' => 'Lihat Produk Kulak',
                'edit-product' => 'Buka Form Ubah Produk Kulak',
                'update-product' => 'Simpan Perubahan Produk Kulak',
                'save-product' => 'Simpan Produk Kulak',
                'save-transaction' => 'Simpan Transaksi Kulak',
                'delete_product' => 'Hapus Produk Kulak',
                'update_receive_product' => 'Simpan Penerimaan Produk Kulak',
                'set_selesai' => 'Selesaikan Kulak',
                'reset' => 'Reset Transaksi Kulak',
            ]),
        ],
        'sortir' => [
            'label' => 'Sortir',
            'actions' => $resourceActions('Sortir', [
                'save-stock' => 'Simpan Stok Sortir',
                'save-transaction' => 'Simpan Transaksi Sortir',
            ]),
        ],
        'transfer' => [
            'label' => 'Transfer',
            'actions' => $resourceActions('Transfer', [
                'save-stock' => 'Simpan Stok Transfer',
                'save-transaction' => 'Simpan Transaksi Transfer',
            ]),
        ],
        'product-receipt' => [
            'label' => 'Resep Produk',
            'actions' => $resourceActions('Resep Produk', [], ['show']),
        ],
        'stock-out' => [
            'label' => 'Stok Keluar',
            'actions' => $resourceActions('Stok Keluar', [], ['show']),
        ],
        'stock-opname' => [
            'label' => 'Penyesuaian Stok',
            'actions' => $resourceActions('Penyesuaian Stok', [], ['show']),
        ],
        'stock-out-type' => [
            'label' => 'Tipe Stok Keluar',
            'actions' => $resourceActions('Tipe Stok Keluar', [], ['show']),
        ],
        'production' => [
            'label' => 'Produksi',
            'actions' => $resourceActions('Produksi', [
                'payment' => 'Pembayaran Produksi',
                'save-completion' => 'Simpan Penyelesaian Produksi',
                'completion-notification' => 'Lihat Notifikasi Penyelesaian Produksi',
                'detail' => 'Detail Produksi',
                'print' => 'Cetak Produksi',
                'get-receipt' => 'Lihat Resep Produksi',
                'get-recipe-data' => 'Lihat Data Resep Produksi',
                'delete_detail' => 'Hapus Detail Produksi',
                'update_product_id' => 'Simpan Perubahan Produk Produksi',
                'save-ajax' => 'Simpan Bahan Tambahan Produksi',
                'edit-ajax' => 'Buka Form Ubah Bahan Tambahan Produksi',
                'delete-ajax' => 'Hapus Bahan Tambahan Produksi',
            ], ['show']),
        ],
        'receipt' => [
            'label' => 'Resep',
            'actions' => $resourceActions('Resep', [
                'save-ajax' => 'Simpan Bahan Tambahan Resep',
                'edit-ajax' => 'Buka Form Ubah Bahan Tambahan Resep',
                'delete-ajax' => 'Hapus Bahan Tambahan Resep',
            ], ['show']),
        ],
        'parcel' => [
            'label' => 'Parcel',
            'actions' => $resourceActions('Parcel', [
                'process' => 'Proses Parcel',
                'get-product' => 'Lihat Produk Parcel',
                'edit-product' => 'Buka Form Ubah Produk Parcel',
                'update-product' => 'Simpan Perubahan Produk Parcel',
                'save-product' => 'Simpan Produk Parcel',
                'delete_product' => 'Hapus Produk Parcel',
                'set_selesai' => 'Selesaikan Parcel',
            ]),
        ],

        // POS
        'pos' => [
            'label' => 'POS',
            'actions' => $resourceActions('Transaksi POS', [
                'receipt' => 'Lihat Receipt POS',
                'payment' => 'Pembayaran POS',
                'savePayment' => 'Simpan Pembayaran POS',
                'paymentNotification' => 'Lihat Notifikasi Pembayaran POS',
                'listPayment' => 'Lihat Daftar Pembayaran POS',
                'printPayment' => 'Cetak Pembayaran POS',
                'printDraftPayment' => 'Cetak Draft Pembayaran POS',
                'printNota' => 'Cetak Nota POS',
                'cek-nota' => 'Cek Nota POS',
                'cek-nota.draft' => 'Cek Nota Draft POS',
            ]),
        ],
        'setting-nota' => [
            'label' => 'Setting Nota',
            'actions' => $resourceActions('Setting Nota', [
                'view-receipt' => 'Lihat Preview Nota',
            ], ['show']),
        ],
        'delivery-order' => [
            'label' => 'Delivery Order',
            'actions' => $indexActions('Delivery Order', [
                'get-courier' => 'Lihat Kurir Delivery Order',
                'update-courier' => 'Simpan Kurir Delivery Order',
                'set-selesai' => 'Selesaikan Delivery Order',
            ]),
        ],
        'other-book' => [
            'label' => 'Other Book',
            'actions' => $indexActions('Other Book', [
                'set-selesai' => 'Selesaikan Other Book',
            ]),
        ],
        'order-book' => [
            'label' => 'Order Book',
            'actions' => $resourceActions('Order Book', [
                'save-transaction' => 'Simpan Transaksi Order Book',
                'order' => 'Proses Order Book',
            ], ['show']),
        ],
        'expenditure' => [
            'label' => 'Pengeluaran',
            'actions' => $resourceActions('Pengeluaran', [
                'save-transaction' => 'Simpan Transaksi Pengeluaran',
            ]),
        ],

        // Report
        'report' => [
            'label' => 'Laporan',
            'actions' => [
                'transaction' => 'Lihat Laporan Transaksi Penjualan',
                'customer.transaction' => 'Lihat Laporan Penjualan Per Pelanggan',
                'branch.transaction' => 'Lihat Laporan Penjualan Per Cabang',
                'product.sales' => 'Lihat Laporan Penjualan Per Produk',
                'branch.product' => 'Lihat Laporan Penjualan Per Channel',
                'customer.product' => 'Lihat Laporan Transaksi Penjualan per Produk',
                'product.buang' => 'Lihat Laporan Produk Buang',
                'shipping.cost' => 'Lihat Laporan Ongkos Kirim',
                'total.aset' => 'Lihat Laporan Total Aset',
            ],
        ],
    ],
];
