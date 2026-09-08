<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Master\Entities\Branch;
use Modules\Transaction\Entities\TransferDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;


class Transfer extends Model
{
    use HasFactory;

    protected $table = 'transfer';
    protected $fillable = [
        'uuid',
        'date',
        'invoice_number',
        'total',
        'status',
        'type',
        'branch_id',
        'branch_destination_id',
        'created_by',
        'pic_penerima',
        'tanggal_diterima',
    ];
    public function detail()
    {
        return $this->hasMany(TransferDetail::class, 'transfer_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function branchDestination()
    {
        return $this->belongsTo(Branch::class, 'branch_destination_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'id_user');
    }

    public function picPenerima()
    {
        return $this->belongsTo(\App\Models\User::class, 'pic_penerima', 'id_user');
    }

    public function corrections()
    {
        return $this->hasManyThrough(TransferDetailCorrection::class, TransferDetail::class, 'transfer_id', 'transfer_detail_id');
    }

    public static function getOrderNumber()
    {
        $prefix = 'TRF' . now()->format('Ym');
        $prefixLen = strlen($prefix);

        $orderData = self::where('invoice_number', 'LIKE', $prefix . '%')
            ->lockForUpdate()
            ->select(DB::raw("MAX(CAST(SUBSTRING(invoice_number, " . ($prefixLen + 1) . ") AS UNSIGNED)) as max_order"))
            ->first();

        $nextNumber = 1;
        if ($orderData && $orderData->max_order) {
            $nextNumber = (int) $orderData->max_order + 1;
        }

        $orderPad = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $newCode = $prefix . $orderPad;

        while (self::where('invoice_number', $newCode)->exists()) {
            $nextNumber++;
            $orderPad = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $newCode = $prefix . $orderPad;
        }

        return $newCode;
    }
}
