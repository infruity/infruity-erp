<?php

namespace Modules\Pos\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Master\Entities\Branch;
use Modules\Pos\Entities\ExpenditurePayment;
use Modules\Pos\Entities\User;
use Modules\Master\Entities\PaymentMethod;

class Expenditure extends Model
{
    use HasFactory;

    protected $table = 'expenditure';
    protected $fillable = [
        'uuid',
        'branch_id',
        'invoice_number',
        'date',
        'paid',
        'payment_method',
        'total',
        'status',
        'type',
        'created_by'
    ];

    public static function getOrderNumber()
    {
        return \App\Support\DocumentNumber::next(self::class, 'invoice_number', 'EXP' . now()->format('Ym'));
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function payment()
    {
        return $this->hasMany(ExpenditurePayment::class, 'expenditure_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'id');
    }
}
