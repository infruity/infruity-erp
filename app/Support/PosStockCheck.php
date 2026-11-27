<?php

namespace App\Support;

use Modules\Master\Entities\Product;
use Modules\Pos\Entities\PosDetailModel;
use Modules\Pos\Entities\PosModel;
use Modules\Transaction\Entities\Production;
use Modules\Transaction\Entities\ProductionParcelDetail;

/**
 * The first payment of a sale takes its stock, wherever it is paid (payment page or delivery order), so both ask here
 * first. Call it inside the payment's transaction, while the sale is still unpaid.
 */
class PosStockCheck
{
    /** Locks the products involved, then throws when the branch does not have what the sale needs. */
    public static function assertAvailable(PosModel $pos): void
    {
        $lines = PosDetailModel::where('pos_id', $pos->id)->get();
        $parcelRows = ProductionParcelDetail::where('pos_id', $pos->id)->get();
        $productions = Production::with('productionDetails')->where('pos_id', $pos->id)->get();

        $locks = [];
        $required = [];
        $need = function ($productId, $quantity) use (&$locks, &$required): void {
            if (! $productId) {
                return;
            }
            $locks[$productId] = $productId;
            $stockProductId = $productId;
            if ($product = Product::find($productId)) {
                $parentId = $product->getParentId();
                $stockProductId = $parentId ?? $product->id;
                if ($parentId) {
                    $locks[$parentId] = $parentId;
                }
            }
            $required[$stockProductId] = ($required[$stockProductId] ?? 0) + (float) $quantity;
        };

        foreach ($lines as $line) {
            if ($line->type === 'product') {
                $need($line->product_id, $line->quantity);
            } elseif ($line->type === 'parcel' && $line->parcel_id) {
                $need($line->parcel_id, $line->quantity);
            }
        }

        // Juice is taken from stock only for what was not made for this sale. Both sides are added up per product
        // first: comparing line by line would subtract the same production from every line of that juice.
        foreach ($lines->where('type', 'jus')->groupBy('product_id') as $productId => $juiceLines) {
            $fromStock = (float) $juiceLines->sum('quantity') - (float) $productions->where('product_id', $productId)->sum('quantity');
            if ($fromStock > 0.00001) {
                $need($productId, $fromStock);
            }
        }

        foreach ($parcelRows as $row) {
            $need($row->product_id, $row->quantity);
        }
        foreach ($productions as $production) {
            foreach ($production->productionDetails as $detail) {
                $need($detail->product_id, $detail->quantity);
            }
        }

        // Always in the same order, so two checkouts cannot wait for each other.
        sort($locks);
        foreach ($locks as $lockId) {
            Product::where('id', $lockId)->lockForUpdate()->first();
        }

        $required = array_diff_key($required, array_flip(Product::serviceIds(array_keys($required))));
        $stock = InventorySnapshot::stockAvailable(array_keys($required), $pos->branch_id);
        foreach ($required as $productId => $quantity) {
            $available = $stock[(int) $productId] ?? 0.0;
            if ($available < $quantity) {
                $name = Product::find($productId)->name ?? 'Produk #' . $productId;
                throw new \Exception("Stok \"{$name}\" tidak mencukupi. Stok tersedia: {$available}, dibutuhkan: {$quantity}");
            }
        }
    }
}
