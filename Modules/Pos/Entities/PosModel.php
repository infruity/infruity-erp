<?php

namespace Modules\Pos\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Master\Entities\Customer;
use Modules\Master\Entities\Kurir;
use Modules\Master\Entities\PaymentMethod;
use Modules\Master\Entities\Branch;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;


class PosModel extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'uuid',
        'customer_id',
        'invoice_number',
        'date',
        'total',
        'discount',
        'ongkir',
        'ongkir_discount',
        'ongkir_date',
        'ongkir_time',
        'status',
        'process_status',
        'process_date',
        'note',
        'courier_id',
        'courier_type',
        'ongkir_address',
        'branch_id',
        'branch_process_id',
        'created_by'
    ];
    protected $table = 'pos_transaction';

    public function details(): HasMany
    {
        return $this->hasMany(PosDetailModel::class, 'pos_id', 'id');
    }

    public function paymentDetails(): HasMany
    {
        return $this->hasMany(Payment::class, 'pos_id', 'id');
    }

    // Total Quantity
    public function getTotalQuantityAttribute()
    {
        return $this->details->sum('quantity');
    }

    // Total Price
    public function getTotalPriceAttribute()
    {
        return $this->details->sum('price');
    }

    // Total Discount
    public function getTotalDiscountAttribute()
    {
        return $this->details->sum('discount');
    }

    // Total Payment
    public function getTotalPaymentAttribute()
    {
        return $this->paymentDetails->sum('total');
    }

    // Total Due
    public function getTotalDueAttribute()
    {
        $totalDue = $this->total - $this->getTotalPaymentAttribute();
        return $totalDue > 0 ? $totalDue : 0;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function payment()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method');
    }

    public function courier()
    {
        return $this->belongsTo(Kurir::class, 'courier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function branch_proses()
    {
        return $this->belongsTo(Branch::class, 'branch_process_id');
    }

    public function productions()
    {
        return $this->hasMany(\Modules\Transaction\Entities\Production::class, 'pos_id', 'id');
    }

    /**
     * Next invoice number of today: INV + Ymd + running number (at least 3 digits).
     *
     * Deleted notes keep their number, so a number printed on a receipt never
     * points to another sale later. Only abandoned "temp" drafts give their
     * number back. Call it through reserveInvoiceNumber() so two cashiers
     * cannot get the same number.
     */
    public static function getOrderNumber(): string
    {
        $prefix = 'INV' . now()->format('Ymd');

        $last = self::withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('status', '!=', 'temp'))
            ->selectRaw('MAX(CAST(SUBSTRING(invoice_number, ?) AS UNSIGNED)) AS last_number', [strlen($prefix) + 1])
            ->value('last_number');

        return $prefix . str_pad((string) (((int) $last) + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Runs $create with a fresh invoice number while holding a database lock,
     * so concurrent "new sale" requests cannot pick the same number. $create
     * must save the row (outside an open transaction, so it is committed
     * before the lock is released).
     */
    public static function reserveInvoiceNumber(callable $create)
    {
        $useLock = DB::getDriverName() === 'mysql';

        if ($useLock && (int) DB::selectOne("SELECT GET_LOCK('pos_invoice_number', 10) AS locked")->locked !== 1) {
            throw new \RuntimeException('Nomor nota sedang dibuat oleh kasir lain. Coba lagi.');
        }

        try {
            return $create(self::getOrderNumber());
        } finally {
            if ($useLock) {
                DB::selectOne("SELECT RELEASE_LOCK('pos_invoice_number') AS released");
            }
        }
    }

    /** Whether another note (also a deleted one, except abandoned temp drafts) already uses this number. */
    public static function invoiceNumberTaken(string $invoiceNumber): bool
    {
        return self::withTrashed()
            ->where('invoice_number', $invoiceNumber)
            ->where(fn ($query) => $query->whereNull('deleted_at')->orWhere('status', '!=', 'temp'))
            ->exists();
    }
}
