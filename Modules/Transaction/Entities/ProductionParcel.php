<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductionParcel extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $table = 'production_parcel';

    public static function getOrderNumber()
    {
        return \App\Support\DocumentNumber::next(self::class, 'production_number', 'PRC' . now()->format('Ym'));
    }

    public function products()
    {
        return $this->belongsTo('Modules\Master\Entities\Product', 'product_id', 'id');
    }

    public function staff()
    {
        return $this->belongsTo('Modules\Master\Entities\Staff', 'staff_id', 'id');
    }
}
