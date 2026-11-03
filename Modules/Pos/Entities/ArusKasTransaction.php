<?php

namespace Modules\Pos\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Master\Entities\Branch;

class ArusKasTransaction extends Model
{
    protected $table = 'arus_kas_transactions';

    protected $fillable = [
        'uuid',
        'invoice_number',
        'branch_id',
        'date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(ArusKasItem::class, 'arus_kas_transaction_id');
    }
}
