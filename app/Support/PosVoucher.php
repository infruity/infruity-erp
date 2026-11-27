<?php

namespace App\Support;

use Modules\Crm\Entities\Deposito;
use Modules\Pos\Entities\PosModel;

/**
 * The deposit voucher of a sale. The payment page and the payment itself ask here, so the amount shown is the amount
 * the server expects.
 */
class PosVoucher
{
    /**
     * @return array{0: float, 1: ?Deposito} the voucher value of this sale, and the deposit it would be taken from
     *                                       (null when the sale already holds its voucher, or gets none)
     */
    public static function for(PosModel $pos, bool $lock = false): array
    {
        if ($pos->deposito_id || ! $pos->customer_id) {
            return [(float) ($pos->voucher ?? 0), null];
        }

        $query = Deposito::where('customer_id', $pos->customer_id);
        $deposit = ($lock ? $query->lockForUpdate() : $query)->first();
        if (! $deposit || $deposit->voucher_qty <= 0) {
            return [(float) ($pos->voucher ?? 0), null];
        }

        return [min((float) $deposit->voucher, (float) $pos->total), $deposit];
    }
}
