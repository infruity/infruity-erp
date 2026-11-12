<?php

namespace Modules\Master\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductUnit extends Model
{
    use HasFactory;

    protected $table = 'product_units';
    protected $fillable = [];
    
    public function updater()
    {
        return $this->belongsTo(\App\User::class, 'updated_by', 'id_user');
    }

    protected static function newFactory()
    {
        return \Modules\Master\Database\factories\ProductUnitFactory::new();
    }
}
