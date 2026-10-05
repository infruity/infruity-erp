<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'product_id',
        'description',
        'created_by',
    ];
    protected $table = 'receipt';
    protected $primaryKey = 'id';

    protected static function newFactory()
    {
        return \Modules\Transaction\Database\factories\ReceiptFactory::new();
    }

    public static function getOrderNumber()
    {
        $orderData = self::select(DB::raw('CAST(RIGHT(code, 3) AS UNSIGNED) + 1 AS code'))
            ->whereRaw('MONTH(created_at) = MONTH(NOW())')
            ->whereRaw('YEAR(created_at) = YEAR(NOW())')
            ->whereRaw('SUBSTRING(code, 1, 3) = ?', ['RCP'])
            ->orderByRaw('CAST(RIGHT(code, 3) AS UNSIGNED) DESC')
            ->limit(1)
            ->first();
        $orderPad = '001';
        if ($orderData && $orderData->code) {
            $orderPad = str_pad($orderData->code, 3, '0', STR_PAD_LEFT);
        }

        $prefix = 'RCP' . now()->format('Ym');
        $newCode = $prefix . $orderPad;

        return $newCode;
    }

    public function products()
    {
        return $this->belongsTo('Modules\Master\Entities\Product', 'product_id', 'id');
    }

    public function recipeIngredients()
    {
        return $this->hasMany(ProductReceipt::class, 'receipt_id', 'id');
    }
}
