<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Master\Entities\Product;
use Illuminate\Support\Facades\DB;


class StockOpname extends Model
{
    use HasFactory;

    protected $table = 'stock_opname';
    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Transaction\Database\factories\StockOpnameFactory::new();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** The products that share one stock, the parent first: a variant counts the same shelf as its parent. */
    public static function stockFamily($productId): array
    {
        $parent = (int) (DB::table('product_child')->where('product_id', $productId)->value('parent_id') ?: $productId);

        return array_values(array_unique(array_merge(
            [$parent],
            DB::table('product_child')->where('parent_id', $parent)->pluck('product_id')->map(fn ($id) => (int) $id)->all()
        )));
    }

    /**
     * Another count of the same stock was finalized after this one was taken, so this one's difference
     * was measured against stock that did not have that correction yet; applying it would correct twice.
     */
    public function isSuperseded(array $family): bool
    {
        return self::whereIn('product_id', $family)
            ->where('branch_id', $this->branch_id)
            ->whereKeyNot($this->id)
            ->where('status', 'selesai')
            ->where('updated_at', '>', $this->created_at)
            ->exists();
    }

    public static function getOrderNumber()
    {
        return \App\Support\DocumentNumber::next(self::class, 'code', 'SO' . now()->format('ym'));
    }
}
