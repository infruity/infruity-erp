<?php

namespace Modules\Pos\Entities;

use Illuminate\Database\Eloquent\Model;

/** Append-only: see the migration. */
class PosBalancing extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'pos_balancing';

    protected $fillable = ['pos_id', 'amount', 'note', 'reversal_of_id', 'created_by', 'idempotency_key'];

    protected $casts = [
        'pos_id' => 'integer',
        'amount' => 'float',
        'reversal_of_id' => 'integer',
        'created_by' => 'integer',
    ];
}
