<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        return \App\Support\DocumentNumber::next(self::class, 'code', 'RCP' . now()->format('Ym'));
    }

    public function products()
    {
        return $this->belongsTo('Modules\Master\Entities\Product', 'product_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by', 'id_user');
    }

    public function updater()
    {
        return $this->belongsTo(\App\User::class, 'updated_by', 'id_user');
    }

    public function recipeIngredients()
    {
        return $this->hasMany(ProductReceipt::class, 'receipt_id', 'id');
    }
}
