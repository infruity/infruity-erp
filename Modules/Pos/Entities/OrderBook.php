<?php

namespace Modules\Pos\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\Customer;
use App\User;

class OrderBook extends Model
{
    use HasFactory;

    protected $table = 'order_book';
    protected $fillable = [
        'branch_id',
        'customer_id',
        'date',
        'status',
        'created_by',
        'updated_by',
        'invoice_number',
        'note',
        'updated_at'
    ];

    public static function getOrderNumber()
    {
        $orderData = self::select(DB::raw('CAST(RIGHT(invoice_number, 3) AS UNSIGNED) + 1 AS order_number'))
            ->whereRaw('MONTH(created_at) = MONTH(NOW())')
            ->whereRaw('YEAR(created_at) = YEAR(NOW())')
            ->whereRaw('SUBSTRING(invoice_number, 1, 3) = ?', ['ORD'])
            ->orderByRaw('CAST(RIGHT(invoice_number, 3) AS UNSIGNED) DESC')
            ->limit(1)
            ->first();
        $orderPad = '001';
        if ($orderData && $orderData->order_number) {
            $orderPad = str_pad($orderData->order_number, 3, '0', STR_PAD_LEFT);
        }

        $prefix = 'ORD' . now()->format('Ym');
        $newCode = $prefix . $orderPad;

        return $newCode;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(OrderBookDetail::class, 'order_book_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function pos()
    {
        return $this->hasOne(PosModel::class, 'invoice_number', 'invoice_number');
    }
}
