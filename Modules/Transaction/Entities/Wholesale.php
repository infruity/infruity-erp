<?php

namespace Modules\Transaction\Entities;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Master\Entities\Branch;
use Modules\Master\Entities\UserBranch;

class Wholesale extends Model
{
    use HasFactory;

    protected $table = 'wholesale';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_number',
        'branch_id',
        'supplier_id',
        'order_date',
        'status',
        'total_amount',
        'description',
        'created_by',
        'updated_by',
    ];

    protected static function newFactory()
    {
        return \Modules\Transaction\Database\factories\WholesaleFactory::new ();
    }

    public function supplier()
    {
        return $this->belongsTo('Modules\Supplier\Entities\Supplier', 'supplier_id');
    }
    public function products()
    {
        return $this->hasMany('Modules\Transaction\Entities\WholesaleProduct', 'wholesale_id');
    }
    // Alias for products (to match the detail variable name used in views)
    public function getDetailAttribute()
    {
        return $this->products;
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public static function getData($request)
    {
        $query = DB::table('wholesale')
            ->leftJoin('branch', 'branch.id', '=', 'wholesale.branch_id')
            ->leftJoin('users', 'users.id_user', '=', 'wholesale.created_by')
            ->leftJoinSub(
                DB::table('wholesale_product')
                    ->leftJoin('products as wholesale_products', 'wholesale_products.id', '=', 'wholesale_product.product_id')
                    ->leftJoin('supplier as wholesale_suppliers', 'wholesale_suppliers.id', '=', 'wholesale_product.supplier_id')
                    ->select('wholesale_product.wholesale_id')
                    ->selectRaw('COUNT(DISTINCT wholesale_product.product_id) AS total_product')
                    ->selectRaw('SUM(wholesale_product.total_price) AS total_amount')
                    ->selectRaw("GROUP_CONCAT(DISTINCT wholesale_products.name ORDER BY wholesale_products.name SEPARATOR ', ') AS item_names")
                    ->selectRaw("GROUP_CONCAT(DISTINCT wholesale_suppliers.name ORDER BY wholesale_suppliers.name SEPARATOR ', ') AS supplier_names")
                    ->groupBy('wholesale_product.wholesale_id'),
                'wholesale_totals',
                'wholesale_totals.wholesale_id',
                '=',
                'wholesale.id'
            )
            ->select(
                'wholesale.id',
                'wholesale.branch_id',
                'wholesale.order_number',
                'wholesale.order_date',
                'wholesale.status',
                'wholesale.created_at',
                'wholesale.updated_at',
                'branch.name as branch_name',
                'users.nm_user as created_by'
            )
            ->addSelect(DB::raw('COALESCE(wholesale_totals.total_product, 0) AS total_product'))
            ->addSelect(DB::raw('COALESCE(wholesale_totals.total_amount, 0) AS total_amount'))
            ->addSelect('wholesale_totals.item_names', 'wholesale_totals.supplier_names')
            ->whereIn('wholesale.branch_id', UserBranch::getUserBranch())
            ->orderBy('wholesale.created_at', 'desc');

        if ($request->filled('start_date')) {
            $query->whereDate('wholesale.order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('wholesale.order_date', '<=', $request->end_date);
        }
        if ($request->status && $request->status != 'all') {
            $query->where('wholesale.status', $request->status);
        }
        if ($request->cabang_filter && $request->cabang_filter != 'all') {
            $query->where('wholesale.branch_id', $request->cabang_filter);
        }
        return $query;
    }
    public function createdBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo('App\Models\User', 'updated_by');
    }
    // public function scopeFilter($query, $filters)
    // {
    //     if ($filters['search'] ?? false) {
    //         $query->where('order_date', 'like', '%' . $filters['search'] . '%')
    //             ->orWhereHas('supplier', function ($q) use ($filters) {
    //                 $q->where('name', 'like', '%' . $filters['search'] . '%');
    //             });
    //     }
    //     if ($filters['date_from'] ?? false) {
    //         $query->whereDate('order_date', '>=', $filters['date_from']);
    //     }
    //     if ($filters['date_to'] ?? false) {
    //         $query->whereDate('order_date', '<=', $filters['date_to']);
    //     }
    // }
    // public function scopeWithSupplier($query)
    // {
    //     return $query->with('supplier');
    // }
    // public function scopeWithProducts($query)
    // {
    //     return $query->with('products');
    // }
    // public function scopeWithCreatedBy($query)
    // {
    //     return $query->with('createdBy');
    // }

    public static function getOrderNumber()
    {
        $orderData = self::select(DB::raw('CAST(RIGHT(order_number, 3) AS UNSIGNED) + 1 AS order_number'))
            ->whereRaw('MONTH(created_at) = MONTH(NOW())')
            ->whereRaw('YEAR(created_at) = YEAR(NOW())')
            ->whereRaw('SUBSTRING(order_number, 1, 2) = ?', ['PO'])
            ->orderByRaw('CAST(RIGHT(order_number, 3) AS UNSIGNED) DESC')
            ->limit(1)
            ->first();
        $orderPad = '001';
        if ($orderData && $orderData->order_number) {
            $orderPad = str_pad($orderData->order_number, 3, '0', STR_PAD_LEFT);
        }

        $prefix = 'PO' . now()->format('Ym');
        $newCode = $prefix . $orderPad;

        return $newCode;
    }
}
