<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Master\Entities\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sortir extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'date',
        'invoice_number',
        'subtotal',
        'total',
        'status',
        'type',
        'branch_id',
        'created_by',
    ];
    protected $table = 'sortir_transaction';

    public function detail()
    {
        return $this->hasMany(SortirDetail::class, 'sortir_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id_user');
    }

    public static function getOrderNumber()
    {
        $prefix = 'SOR' . now()->format('Ym');
        $lastNumber = self::where('invoice_number', 'like', $prefix . '%')->pluck('invoice_number')
            ->map(function ($invoice) use ($prefix) {
                $suffix = substr($invoice, strlen($prefix));
                return ctype_digit($suffix) ? (int) $suffix : 0;
            })->max() ?? 0;

        return $prefix . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }
}
