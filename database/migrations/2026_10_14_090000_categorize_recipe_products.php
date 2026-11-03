<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $categories = [
        'Jus & Smoothie' => [
            'description' => 'Jus buah, smoothie, dan minuman buah sejenis.',
            'patterns' => ['Jus %', 'Smoothie %'],
        ],
        'Salad Buah' => [
            'description' => 'Salad buah dalam berbagai ukuran kemasan.',
            'patterns' => ['Salad Buah%'],
        ],
        'Buah Potong' => [
            'description' => 'Buah segar yang disajikan dalam bentuk potongan.',
            'patterns' => ['Buah Potong%'],
        ],
        'Rujak Buah' => [
            'description' => 'Rujak buah dan variasi campuran rujak.',
            'patterns' => ['Rujak%'],
        ],
        'Asinan Buah' => [
            'description' => 'Asinan berbahan dasar buah.',
            'patterns' => ['Asinan%'],
        ],
        'Es & Minuman' => [
            'description' => 'Es buah, alpukat kocok, dan minuman olahan lainnya.',
            'patterns' => ['Es %', 'Alpukat Kocok%'],
        ],
        'Dessert & Olahan Lainnya' => [
            'description' => 'Hidangan penutup dan produk olahan yang belum masuk kategori lain.',
            'patterns' => [],
        ],
    ];

    public function up(): void
    {
        $categoryIds = [];
        foreach ($this->categories as $name => $definition) {
            $category = DB::table('products_category')->where('name', $name)->first();
            if (! $category) {
                $id = ((int) DB::table('products_category')->max('id')) + 1;
                DB::table('products_category')->insert([
                    'id' => $id,
                    'name' => $name,
                    'description' => $definition['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $id = $category->id;
                if (blank($category->description)) {
                    DB::table('products_category')->where('id', $id)->update([
                        'description' => $definition['description'],
                        'updated_at' => now(),
                    ]);
                }
            }

            $categoryIds[$name] = $id;
        }

        $genericCategoryId = DB::table('products_category')->where('name', 'Produk Olahan')->value('id');
        if (! $genericCategoryId) {
            return;
        }

        DB::table('products_category')
            ->where('id', $genericCategoryId)
            ->where(function ($query) {
                $query->whereNull('description')->orWhere('description', '')->orWhere('description', 'Kategori produk');
            })
            ->update([
                'description' => 'Kategori umum untuk produk olahan yang belum memiliki kategori khusus.',
                'updated_at' => now(),
            ]);

        foreach ($this->categories as $name => $definition) {
            if (! $definition['patterns']) {
                continue;
            }

            DB::table('products')
                ->whereIn('id', DB::table('receipt')->select('product_id'))
                ->where('category_id', $genericCategoryId)
                ->where(function ($query) use ($definition) {
                    foreach ($definition['patterns'] as $pattern) {
                        $query->orWhere('name', 'like', $pattern);
                    }
                })
                ->update(['category_id' => $categoryIds[$name], 'updated_at' => now()]);
        }

        DB::table('products')
            ->whereIn('id', DB::table('receipt')->select('product_id'))
            ->where('category_id', $genericCategoryId)
            ->update([
                'category_id' => $categoryIds['Dessert & Olahan Lainnya'],
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $genericCategoryId = DB::table('products_category')->where('name', 'Produk Olahan')->value('id');
        if (! $genericCategoryId) {
            return;
        }

        DB::table('products_category')
            ->where('id', $genericCategoryId)
            ->where('description', 'Kategori umum untuk produk olahan yang belum memiliki kategori khusus.')
            ->update(['description' => 'Kategori produk', 'updated_at' => now()]);

        $categoryIds = DB::table('products_category')
            ->whereIn('name', array_keys($this->categories))
            ->pluck('id');

        DB::table('products')
            ->whereIn('id', DB::table('receipt')->select('product_id'))
            ->whereIn('category_id', $categoryIds)
            ->update(['category_id' => $genericCategoryId, 'updated_at' => now()]);
    }
};
