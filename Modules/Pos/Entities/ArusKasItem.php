<?php

namespace Modules\Pos\Entities;

use Illuminate\Database\Eloquent\Model;

class ArusKasItem extends Model
{
    protected $table = 'arus_kas_items';

    protected $fillable = [
        'arus_kas_transaction_id',
        'direction',
        'description',
        'unit',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];
}
