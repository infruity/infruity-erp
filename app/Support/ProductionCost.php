<?php

namespace App\Support;

use Modules\Master\Entities\Product;
use Modules\Transaction\Entities\Production;
use Modules\Transaction\Entities\ProductionDetail;

/**
 * Freezes the HPP of a production run when it is posted.
 *
 * HPP per unit = (sum of ingredient moving HPP x qty used) / qty produced
 *              + service cost per unit.
 *
 * The value is stored on production.hpp_per_unit and product_hpp reads it from
 * there, so later price changes of the ingredients never change it.
 */
class ProductionCost
{
    public static function freeze(Production $production, float $serviceCostPerUnit = 0.0): float
    {
        $details = ProductionDetail::where('production_id', $production->id)
            ->get(['product_id', 'quantity']);

        $unitHpp = ProductHpp::current($details->pluck('product_id'));

        $ingredientCost = 0.0;
        foreach ($details as $detail) {
            $ingredientCost += (float) $detail->quantity * ($unitHpp[(int) $detail->product_id] ?? 0.0);
        }

        $quantity = (float) $production->quantity;
        $hppPerUnit = $quantity > 0 ? ($ingredientCost / $quantity) + $serviceCostPerUnit : 0.0;

        $production->hpp_per_unit = $hppPerUnit;
        $production->save();

        // Catalog copy: this run's cost, then the moving average once the run
        // is in the ledger (posted runs are).
        Product::where('id', $production->product_id)->update(['hpp' => $hppPerUnit]);
        ProductHpp::syncCatalog([$production->product_id]);

        return $hppPerUnit;
    }
}
