<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-apply stock & HPP views after aligning their status filters:
 * - pengadaan berstatus complete tetap dihitung sebagai stok masuk
 * - product_hpp hanya menghitung transaksi sah (sama seperti view stok)
 */
return new class extends Migration
{
    private array $files = [
        'sql/transaction_stock.sql',
        'sql/transaction_stock_show.sql',
        'sql/product_stock.sql',
        'sql/product_hpp.sql',
        'sql/report_total_belanja.sql',
    ];

    public function up(): void
    {
        foreach ($this->files as $file) {
            $sql = file_get_contents(database_path($file));

            foreach (explode(';', $sql) as $query) {
                // Lewati potongan yang hanya berisi komentar (mis. baris "-- ...;" di akhir file).
                $withoutComments = preg_replace('/^\s*--.*$/m', '', $query);
                if (trim($withoutComments) === '') {
                    continue;
                }

                DB::statement(trim($query));
            }
        }
    }

    public function down(): void
    {
        // View lama tidak disimpan; jalankan ulang file SQL versi sebelumnya bila perlu rollback.
    }
};
