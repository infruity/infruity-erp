<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    private const VIEW_FILES = [
        'transaction_stock' => 'sql/transaction_stock.sql',
        'transaction_stock_show' => 'sql/transaction_stock_show.sql',
        'product_hpp' => 'sql/product_hpp.sql',
        'product_stock' => 'sql/product_stock.sql',
    ];

    public function up(): void
    {
        $objects = [];
        foreach (self::VIEW_FILES as $name => $file) {
            $object = DB::selectOne(
                'SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$name]
            );
            $objects[$name] = $object?->TABLE_TYPE;

            if ($object?->TABLE_TYPE === 'BASE TABLE') {
                $rows = DB::table($name)->count();
                if ($rows > 0) {
                    throw new RuntimeException("Cannot replace {$name}: expected an empty table, found {$rows} rows.");
                }
            }
        }

        foreach (self::VIEW_FILES as $name => $file) {
            if (($objects[$name] ?? null) === 'BASE TABLE') {
                DB::statement("DROP TABLE `{$name}`");
            }
        }

        foreach (self::VIEW_FILES as $name => $file) {
            $sql = file_get_contents(database_path($file));
            if ($sql === false) {
                throw new RuntimeException("Unable to read SQL definition for {$name}.");
            }

            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                DB::unprepared($statement);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys(self::VIEW_FILES)) as $name) {
            DB::statement("DROP VIEW IF EXISTS `{$name}`");
        }
    }
};
