<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Every path that takes stock holds the branch's stock lock from before its transaction begins. Without it, two sales
 * of the last stock paid at the same moment both succeeded (stock 10, two notes of 7, result -4), as did a sale made
 * during a sorting, transfer, or production. PHPUnit runs one request at a time; the parallel run is done in QA.
 */
class StockLockContractTest extends TestCase
{
    public static function stockPaths(): array
    {
        $pos = 'Modules\\Pos\\Http\\Controllers\\';
        $stock = 'Modules\\Transaction\\Http\\Controllers\\';

        return [
            'sale payment' => [$pos . 'PosController', 'savePayment'],
            'delivery settlement' => [$pos . 'DeliveryOrderController', 'setSelesai'],
            'sorting' => [$stock . 'SortirController', 'saveTransaction'],
            'transfer send' => [$stock . 'TransferController', 'saveTransaction'],
            'transfer receive' => [$stock . 'TransferController', 'set_selesai'],
            'production' => [$stock . 'ProductionController', 'store'],
            'production completion' => [$stock . 'ProductionController', 'saveCompletion'],
            'parcel' => [$stock . 'ProductionParcelController', 'set_selesai'],
            'stock count' => [$stock . 'StockOpnameController', 'finalize'],
            'purchase deletion' => [$stock . 'WholesaleController', 'destroy'],
        ];
    }

    #[DataProvider('stockPaths')]
    public function test_the_stock_lock_is_taken_before_the_transaction(string $class, string $method): void
    {
        $reflection = new ReflectionMethod($class, $method);
        $source = implode('', array_slice(file($reflection->getFileName()), $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1));

        $lock = preg_match('/StockLock::(lock|during)\(/', $source, $match, PREG_OFFSET_CAPTURE) ? $match[0][1] : null;
        $this->assertNotNull($lock, "{$class}::{$method} takes stock without the stock lock");
        preg_match('/DB::(beginTransaction|transaction)\(/', $source, $begin, PREG_OFFSET_CAPTURE);
        $this->assertLessThan($begin[0][1], $lock, 'the lock must come before the transaction');
        if (str_contains($source, 'StockLock::lock(')) {
            $this->assertMatchesRegularExpression('/finally \{\s+StockLock::release\(\);/', $source);
        }
    }
}
