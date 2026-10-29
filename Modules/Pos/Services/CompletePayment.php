<?php

namespace Modules\Pos\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Crm\Entities\Deposito;
use Modules\Master\Entities\Product;
use Modules\Pos\Entities\Payment;
use Modules\Pos\Entities\PosDetailModel;
use Modules\Pos\Entities\PosModel;
use Modules\Transaction\Entities\Production;
use Modules\Transaction\Entities\ProductionParcelDetail;
use Modules\Transaction\Entities\ProductStock;

/** Shared POS/COD settlement. Caller must own the transaction and authorized invoice row lock. */
class CompletePayment
{
    /** @param array<int, array{payment_id: int, amount: float|int}> $payments */
    public function handle(PosModel $pos, array $payments, string $date, int $actorId): Payment
    {
        $paymentAmounts = collect($payments)->pluck('amount')->map(fn ($amount) => (float) $amount);
        $methods = DB::table('payment_method')->whereIn('id', collect($payments)->pluck('payment_id'))
            ->pluck('name', 'id');
        $paymentNames   = collect($payments)->map(fn ($item) => $methods[$item['payment_id']])->toArray();
        $paymentIds     = collect($payments)->pluck('payment_id')->toArray();
        $paymentAmounts = $paymentAmounts->toArray();

        $existingPaid = (float) Payment::where('pos_id', $pos->id)->sum('total')
            - (float) Payment::where('pos_id', $pos->id)->sum('return');
        $voucher = (float) ($pos->voucher ?? 0);
        $deposito = null;
        if (! $pos->deposito_id && ! $voucher && $pos->customer_id) {
            $deposito = Deposito::where('customer_id', $pos->customer_id)->lockForUpdate()->first();
            if ($deposito && $deposito->voucher_qty > 0) {
                // A new voucher cannot refund cash already recorded in the net ledger.
                $voucher = max(0, min((float) $deposito->voucher, max(0, (float) $pos->total - $existingPaid)));
            }
        }
        $due = max(0, (float) $pos->total - $voucher - $existingPaid);
        $amount = array_sum($paymentAmounts);
        $voucherCoversDebt = $voucher > 0 && (float) $pos->total > $existingPaid && $due <= 0
            && (($deposito && $deposito->voucher_qty > 0)
                || ! Payment::where('pos_id', $pos->id)->exists());
        if ($amount < 0 || (($amount <= 0 || $due <= 0) && ! $voucherCoversDebt)) {
            throw ValidationException::withMessages(['payments' => 'Tidak ada sisa tagihan yang dapat dibayar.']);
        }
        $change = max(0, $amount - $due);
        if ($change > 0 && collect($paymentNames)->contains(fn ($name) => strtolower($name) !== 'cash')) {
            throw ValidationException::withMessages(['payments' => 'Kembalian hanya dapat diberikan untuk pembayaran tunai.']);
        }

        $payment = new Payment([
            'uuid'              => Str::uuid(),
            'date'              => $date,
            'nota_number'       => date('YmdHis') . Str::random(4),
            'pos_id'            => $pos->id,
            'branch_id'         => $pos->branch_id,
            'payment_method'    => json_encode($paymentNames),
            'payment_method_id' => json_encode($paymentIds),
            'payment_amount'    => json_encode($paymentAmounts),
            'total'             => $amount,
            'created_by'        => $actorId,
        ]);
        $payment->save();

        // Pengecekan stok seperti pada saveTransaction (hanya untuk transaksi yang belum terpotong stoknya)
        if (in_array($pos->status, ['draft', 'temp', 'pending'])) {
            $posDetails = PosDetailModel::where('pos_id', $pos->id)->get();
            $parcelDetails = ProductionParcelDetail::where('pos_id', $pos->id)->get();
            $productions = Production::with('productionDetails')->where('pos_id', $pos->id)->get();

            $allProductIdsToLock = [];
            $requiredStocks = [];

            $addRequiredStock = function($productId, $qty) use (&$requiredStocks, &$allProductIdsToLock) {
                if (!$productId) return;

                if (!in_array($productId, $allProductIdsToLock)) {
                    $allProductIdsToLock[] = $productId;
                }

                $product = Product::find($productId);
                $stockProductId = $productId;
                if ($product) {
                    $parentId = $product->getParentId();
                    $stockProductId = $parentId ?? $product->id;
                    if ($parentId && !in_array($parentId, $allProductIdsToLock)) {
                        $allProductIdsToLock[] = $parentId;
                    }
                }

                if (!isset($requiredStocks[$stockProductId])) {
                    $requiredStocks[$stockProductId] = 0;
                }
                $requiredStocks[$stockProductId] += (float)$qty;
            };

            // Allocate each produced unit once across all sale rows for that product.
            $remainingProduction = $productions->groupBy('product_id')
                ->map(fn ($rows) => max(0, (float) $rows->sum('quantity')))->all();
            foreach ($posDetails as $detail) {
                if ($detail->type === 'product') {
                    $addRequiredStock($detail->product_id, $detail->quantity);
                } elseif ($detail->type === 'parcel') {
                    if ($detail->parcel_id) {
                        $addRequiredStock($detail->parcel_id, $detail->quantity);
                    }
                } elseif ($detail->type === 'jus') {
                    $productionQty = min((float) $detail->quantity, $remainingProduction[$detail->product_id] ?? 0);
                    $remainingProduction[$detail->product_id] = ($remainingProduction[$detail->product_id] ?? 0) - $productionQty;
                    $consumeStockQty = (float) $detail->quantity - $productionQty;
                    if ($consumeStockQty > 0) {
                        $addRequiredStock($detail->product_id, $consumeStockQty);
                    }
                }
            }

            foreach ($parcelDetails as $pDetail) {
                $addRequiredStock($pDetail->product_id, $pDetail->quantity);
            }

            foreach ($productions as $production) {
                foreach ($production->productionDetails as $pDetail) {
                    $addRequiredStock($pDetail->product_id, $pDetail->quantity);
                }
            }

            $allProductIdsToLock = array_unique($allProductIdsToLock);
            sort($allProductIdsToLock);

            // Keep the existing ordered product locks; other writers still need a concurrency audit.
            foreach ($allProductIdsToLock as $lockId) {
                Product::where('id', $lockId)->lockForUpdate()->first();
            }

            // Validasi stok akumulatif dari semua sumber
            foreach ($requiredStocks as $stockProductId => $requiredQty) {
                $productStock = ProductStock::where('id', $stockProductId)
                    ->where('branch_id', $pos->branch_id)
                    ->first();
                $currentStock = $productStock ? (float)$productStock->stock_available : 0;

                if ($currentStock < $requiredQty) {
                    $product     = Product::find($stockProductId);
                    $productName = $product ? $product->name : 'Produk #' . $stockProductId;
                    throw new \Exception("Stok \"{$productName}\" tidak mencukupi. Stok tersedia: {$currentStock}, dibutuhkan: {$requiredQty}");
                }
            }
        }

        if ($deposito && $voucher > 0 && $deposito->voucher_qty > 0) {
                $pos->voucher = $voucher;
                $pos->voucher_qty = 1;
                $pos->deposito_id = $deposito->id;
                $deposito->decrement('voucher_qty');
        }

        $pos->paid = $existingPaid + $amount - $change;
        $payment->return = $change;
        $payment->remaining = max(0, (float) $pos->total - (float) $pos->voucher - (float) $pos->paid);
        $payment->save();
        $status = $payment->remaining <= 0.01 ? 'paid' : 'debt';
        $pos->status = $status;
        $pos->save();

        if (in_array($status, ['paid', 'debt'])) {
            Production::where('pos_id', $pos->id)
                ->where('status', 'draft')
                ->update(['status' => 'complete']);
        }

        return $payment;
    }
}
