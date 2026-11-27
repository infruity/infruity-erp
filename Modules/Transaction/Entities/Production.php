<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Production extends Model
{
    use HasFactory;

    protected $table = 'production';
    protected $primaryKey = 'id';
    protected $fillable = [
        'production_number',
        'product_id',
        'quantity',
        'production_date',
        'status',
        'description',
        'sell_price',
        'service_cost',
        'hpp_per_unit',
        'staff_id',
        'branch_id',
        'created_by',
        'updated_by',
        'pos_id'
    ];


    public static function getOrderNumber($productionDate = null)
    {
        return \App\Support\DocumentNumber::next(self::class, 'production_number', 'PRO' . ($productionDate ? \Carbon\Carbon::parse($productionDate) : now())->format('Ym'));
    }

    public function products()
    {
        return $this->belongsTo('Modules\Master\Entities\Product', 'product_id', 'id');
    }

    public function staff()
    {
        return $this->belongsTo('Modules\Master\Entities\Staff', 'staff_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo('Modules\Master\Entities\Branch', 'branch_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo('App\User', 'created_by', 'id_user')->withoutGlobalScope('is_aktif');
    }

    public function productionDetails()
    {
        return $this->hasMany('Modules\Transaction\Entities\ProductionDetail', 'production_id', 'id');
    }

    public function pos()
    {
        return $this->belongsTo('Modules\Pos\Entities\PosModel', 'pos_id', 'id');
    }
}
