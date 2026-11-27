<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;
use Modules\Master\Entities\Product;

/** Which products may appear on a document that counts or moves stock. */
class StockProduct
{
    /** A service has no stock. The pickers already hide it; this refuses it when sent by hand. */
    public static function assertCountable(array $productIds, string $field): void
    {
        $services = Product::serviceIds($productIds);
        if ($services !== []) {
            $name = Product::whereKey($services[0])->value('name') ?? 'Jasa';
            throw ValidationException::withMessages([$field => "\"{$name}\" adalah jasa, jadi tidak punya stok."]);
        }
    }
}
