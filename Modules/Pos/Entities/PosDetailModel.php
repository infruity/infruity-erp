<?php

namespace Modules\Pos\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Master\Entities\Product;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosDetailModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'pos_id',
        'product_id',
        'parcel_id',
        'price',
        'quantity',
        'debt_quantity',
        'discount',
        'diskon_global',
        'subtotal',
        'price_after_discount',
        'hpp',
        'subtotal_hpp',
        'exp',
        'exp_value',
        'type',
        'created_by',
    ];
    protected $table = 'pos_transaction_detail';

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function parcel()
    {
        return $this->belongsTo(Product::class, 'parcel_id');
    }

    public function pos()
    {
        return $this->belongsTo(PosModel::class, 'pos_id');
    }

}
