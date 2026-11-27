<?php

namespace App\Support;

use Modules\Master\Entities\Product;
use Modules\Pos\Entities\PosDetailModel;
use Modules\Pos\Entities\PosModel;
use Modules\Crm\Entities\SettingExp;
use Modules\Transaction\Entities\Production;
use Modules\Transaction\Entities\ProductionParcelDetail;

/**
 * The moment a sale is first paid (paid or debt) is the moment its stock leaves, so its HPP is frozen here.
 *
 * The draft already carries the HPP of the moment it was saved; this replaces it with the ledger's moving HPP now. The
 * stock ledger orders a sale and its made-to-order production by their created_at, so both move to this moment too,
 * which keeps the profit on the note equal to the ledger's COGS. database/sql/product_hpp.sql is not involved: it
 * keeps computing from the same rows.
 */
class PosSettlement
{
    /** Statuses in which a sale has not taken any stock yet. */
    public const UNPAID = ['draft', 'temp', 'pending'];

    /**
     * Call while the sale is still unpaid in the database (so the ledger does not count it yet), inside the payment's
     * transaction. Sets $pos->created_at but leaves saving the sale, with its new status, to the caller.
     */
    public static function settle(PosModel $pos): void
    {
        $lines = PosDetailModel::where('pos_id', $pos->id)->get();
        $parcelRows = ProductionParcelDetail::where('pos_id', $pos->id)->get();
        $productions = Production::with('productionDetails')->where('pos_id', $pos->id)->get();

        // Every ledger read happens before the first write.
        $ids = $lines->pluck('product_id')
            ->merge($lines->pluck('parcel_id'))
            ->merge($parcelRows->pluck('product_id'))
            ->merge($productions->flatMap(fn ($production) => $production->productionDetails->pluck('product_id')))
            ->filter()->unique()->values()->all();
        $current = ProductHpp::current($ids);

        $products = Product::whereIn('id', $lines->pluck('product_id')->filter()->all())->get()->keyBy('id');
        $ledgerIdOf = $products->mapWithKeys(fn ($product) => [$product->id => (int) ($product->getParentId() ?? $product->id)]);
        $ledger = InventorySnapshot::latestHpp($ledgerIdOf->values()->all());

        $productionCost = [];
        foreach ($productions as $production) {
            $ingredients = $production->productionDetails->sum(
                fn ($detail) => (float) $detail->quantity * ($current[(int) $detail->product_id] ?? 0.0)
            );
            $quantity = (float) $production->quantity;
            $productionCost[$production->id] = $quantity > 0
                ? $ingredients / $quantity + (float) $production->service_cost : 0.0;
        }

        $expValue = (float) (SettingExp::first()->value_exp ?? 0);
        foreach ($lines as $line) {
            $productId = (int) $line->product_id;
            $unitPrice = (float) $line->price;

            if ($line->type === 'jus') {
                $hpp = self::juiceHpp($productId, $lines, $productions, $productionCost, $current);
            } elseif ($line->type === 'parcel') {
                $hpp = self::parcelHpp($line, $lines, $parcelRows, $current);
                $unitPrice = (float) ($products[$productId]->price ?? $unitPrice);
            } else {
                $product = $products[$productId] ?? null;
                // A service has no cost, whatever its ledger once held.
                $hpp = ! $product || $product->is_service
                    ? 0.0 : (float) (($ledger[$ledgerIdOf[$productId]] ?? null)?->hpp_berjalan ?? 0);
            }

            if ($hpp === null) {
                continue; // nothing to price it from: keep what the draft saved
            }

            $line->forceFill([
                'hpp' => $hpp,
                'subtotal_hpp' => $hpp * (float) $line->quantity,
                'exp' => $unitPrice - $hpp,
                'exp_value' => ($unitPrice - $hpp) * $expValue,
            ])->save();
        }

        $now = now();
        foreach ($productions as $production) {
            $production->forceFill([
                'hpp_per_unit' => $productionCost[$production->id],
                'status' => $production->status === 'draft' ? 'complete' : $production->status,
                'created_at' => $now,
            ])->save();
        }

        // The sale sorts after the production that made its juice.
        $pos->created_at = $productions->isEmpty() ? $now : $now->copy()->addSecond();

        if ($productions->isNotEmpty()) {
            ProductHpp::syncCatalog($productions->pluck('product_id'));
        }
    }

    /** One HPP for all juice lines of a product: what came from stock at the moving HPP, the rest at what making it cost. */
    private static function juiceHpp(int $productId, $lines, $productions, array $productionCost, array $current): ?float
    {
        $sold = (float) $lines->where('type', 'jus')->where('product_id', $productId)->sum('quantity');
        if ($sold <= 0) {
            return null;
        }

        $made = $productions->where('product_id', $productId);
        $madeQty = (float) $made->sum('quantity');
        $madeCost = (float) $made->sum(fn ($production) => (float) $production->quantity * $productionCost[$production->id]);

        return (max(0.0, $sold - $madeQty) * ($current[$productId] ?? 0.0) + $madeCost) / $sold;
    }

    /** Cost of one parcel set: its ingredients at the moving HPP plus one packaging. */
    private static function parcelHpp($line, $lines, $parcelRows, array $current): ?float
    {
        // Parcels of one budget share a product, so the line's own ingredients are the ones that carry its id. Rows
        // saved before they did can only be shared out over every line of that product.
        $rows = $parcelRows->where('pos_detail_id', $line->id);
        $sets = (float) $line->quantity;
        if ($rows->isEmpty()) {
            $rows = $parcelRows->where('production_id', $line->product_id)->whereNull('pos_detail_id');
            $sets = (float) $lines->where('type', 'parcel')->where('product_id', $line->product_id)->sum('quantity');
        }
        if ($rows->isEmpty() || $sets <= 0) {
            return null;
        }

        $ingredients = (float) $rows->sum(fn ($row) => (float) $row->quantity * ($current[(int) $row->product_id] ?? 0.0));

        return $ingredients / $sets + ($line->parcel_id ? ($current[(int) $line->parcel_id] ?? 0.0) : 0.0);
    }
}
