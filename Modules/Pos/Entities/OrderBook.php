<?php

namespace Modules\Pos\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        return \App\Support\DocumentNumber::next(self::class, 'invoice_number', 'ORD' . now()->format('Ym'));
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
