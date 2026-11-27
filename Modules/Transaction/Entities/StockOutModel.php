<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Master\Entities\Product;
use Modules\Transaction\Entities\StockOutType;

class StockOutModel extends Model
{
    use HasFactory;

    protected $table = 'stock_out_transaction';
    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Transaction\Database\factories\StockOutModelFactory::new();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function type()
    {
        return $this->belongsTo(StockOutType::class);
    }

    public static function getOrderNumber()
    {
        return \App\Support\DocumentNumber::next(self::class, 'code', 'SO' . now()->format('ym'));
    }
}
