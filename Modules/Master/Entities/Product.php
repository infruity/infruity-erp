<?php

namespace Modules\Master\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Master\Entities\ProductCategory;
use Modules\Master\Entities\ProductUnit;
use Modules\Transaction\Entities\ProductStock;
use Modules\Transaction\Entities\Receipt;
use Modules\Transaction\Entities\ProductReceipt;
use Modules\Transaction\Entities\ProductionParcelDetail;
use Modules\Master\Entities\ProductBranch;
use DB;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'product_unit',
        'status',
        'hpp',
        'fee',
        'created_by',
        'tipe'
    ];
    protected $table = 'products';

    /** Products in this category are services: no stock limit, never purchased, HPP 0. */
    public const SERVICE_CATEGORY = 'Jasa / Layanan';

    /** The stock a service reports to the screens, which all gate on stock_available. */
    public const SERVICE_STOCK = 999999999;

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->isDirty('category_id')) {
                $service = $product->category_id
                    && ProductCategory::whereKey($product->category_id)->value('name') === self::SERVICE_CATEGORY;
                // Only write the flag when it changes anything, so ordinary products are left alone.
                if ($service || $product->is_service) {
                    $product->is_service = $service;
                }
            }
            if ($product->is_service) {
                $product->hpp = 0;
            }
        });
    }

    /** @return list<int> the service products among the given ids (all of them when no ids are given) */
    public static function serviceIds(?iterable $ids = null): array
    {
        return self::query()->where('is_service', true)
            ->when($ids !== null, fn ($query) => $query->whereIn('id', collect($ids)->all()))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function relationsToArray()
    {
        $relations = parent::relationsToArray();
        if ($this->is_service && array_key_exists('get_stock', $relations)) {
            $relations['get_stock'] = array_merge((array) $relations['get_stock'], ['stock_available' => self::SERVICE_STOCK]);
        }

        return $relations;
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by', 'id_user');
    }

    public function updater()
    {
        return $this->belongsTo(\App\User::class, 'updated_by', 'id_user');
    }
    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit');
    }

    public function productStock()
    {
        return $this->belongsTo(ProductStock::class, 'product_id', 'id');
    }

    public function get_stock()
    {
        return $this->belongsTo('Modules\Transaction\Entities\ProductStock', 'id', 'id');
    }

    public function receipt()
    {
        return $this->belongsTo('Modules\Transaction\Entities\Receipt', 'product_id', 'id');
    }

    public function productReceipt()
    {
        return $this->hasMany(ProductReceipt::class, 'product_id', 'id');
    }

    public function productionParcelDetails()
    {
        return $this->hasMany(
            ProductionParcelDetail::class,
            'production_id', // FK di tabel production_parcel_detail
            'id'             // PK di tabel products
        );
    }

    public function productBranches()
    {
        return $this->hasMany(ProductBranch::class, 'product_id', 'id');
    }

    /**
     * Get all child products (variants) of this product
     */
    public function children()
    {
        return $this->hasMany(ProductChild::class, 'parent_id', 'id');
    }

    /**
     * Get all child products with their Product data
     */
    public function childProducts()
    {
        return $this->hasMany(ProductChild::class, 'parent_id', 'id')->with('product');
    }

    /**
     * Get the parent product if this product is a child
     */
    public function parent()
    {
        return $this->belongsTo(ProductChild::class, 'id', 'product_id');
    }

    /**
     * Get parent product info if this product has a parent
     */
    public function parentProduct()
    {
        return $this->hasOne(ProductChild::class, 'product_id', 'id')->with('product');
    }

    /**
     * Check if this product has children (variants)
     */
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    /**
     * Check if this product is a child of another product
     */
    public function hasParent()
    {
        return ProductChild::where('product_id', $this->id)->exists();
    }

    /**
     * Get the parent product ID if this product is a child
     */
    public function getParentId()
    {
        $child = ProductChild::where('product_id', $this->id)->first();
        return $child ? $child->parent_id : null;
    }

    public static function generateProductName($baseName)
    {
        // Ambil semua produk yang nama depannya sama persis
        $existing = self::where('name', 'LIKE', $baseName . ' - %')
            ->pluck('name');

        // Cari nomor urut tertinggi
        $maxNumber = 0;
        foreach ($existing as $name) {
            if (preg_match('/^' . preg_quote($baseName, '/') . ' - (\d+)$/', $name, $matches)) {
                $number = intval($matches[1]);
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        // Buat nama baru dengan urutan berikutnya
        $newNumber = $maxNumber + 1;
        return $baseName . ' - ' . $newNumber;
    }
}
