<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pos_transaction', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->decimal('discount', 15, 2)->nullable();
            $table->decimal('ongkir', 15, 2)->nullable()->default(0);
            $table->decimal('ongkir_discount', 15, 2)->nullable()->default(0);
            $table->enum('ongkir_status', ['draft', 'delivered'])->default('draft');
            $table->date('ongkir_date')->nullable();
            $table->time('ongkir_time')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('ongkir_address')->nullable();
            $table->integer('courier_id')->nullable();
            $table->decimal('paid', 15, 2)->nullable();
            $table->decimal('return', 15, 2)->nullable();
            $table->integer('payment_method')->nullable();
            $table->enum('status', ['draft', 'paid', 'debt', 'temp', 'canceled', 'pending'])->default('draft');
            $table->enum('process_status', ['none','pending', 'done'])->default('none');
            $table->timestamp('process_date')->nullable();
            $table->text('note')->nullable();
            $table->decimal('voucher', 15, 2)->nullable()->default(0);
            $table->integer('voucher_qty')->nullable()->default(0);
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('branch_process_id')->nullable();
            $table->unsignedBigInteger('deposito_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('pos_transaction_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pos_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('parcel_id')->nullable();
            $table->unsignedBigInteger('production_id')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('price', 15, 2);
            $table->decimal('discount', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->integer('exp');
            $table->decimal('exp_value');
            $table->decimal('hpp', 15, 2)->nullable()->default(0);
            $table->decimal('subtotal_hpp', 15, 3)->nullable()->default(0);
            $table->decimal('price_after_discount', 15, 2)->nullable()->default(0);
            $table->decimal('kemasan_price', 15, 2)->nullable()->default(0);
            $table->decimal('debt_quantity', 15, 2)->nullable()->default(0);
            $table->enum('type', ['product', 'parcel'])->default('product');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('pos_payment', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('nota_number')->nullable();
            $table->unsignedBigInteger('pos_id');
            $table->decimal('total', 15, 2);
            $table->decimal('remaining', 15, 2)->nullable()->default(0);
            $table->decimal('return', 15, 2)->nullable()->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_method_id')->nullable();
            $table->string('payment_amount')->nullable();
            $table->integer('branch_id')->nullable();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $transactions = [];
        $details = [];
        $payments = [];

        for ($i = 1; $i <= 25; $i++) {
            $date = '2025-07-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $hour   = str_pad(rand(0, 23), 2, '0', STR_PAD_LEFT);
            $minute = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
            $second = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
            // gabungkan jadi timestamp penuh
            $timestamp = $date . ' ' . $hour . ':' . $minute . ':' . $second;

            // Buat detail transaksi (2–3 item per transaksi)
            $itemCount = rand(2, 3);
            $total = 0;
            for ($j = 1; $j <= $itemCount; $j++) {
                $qty = rand(1, 5);
                $price = rand(10000, 50000);
                $hpp = rand((int)($price * 0.5), $price);
                $subtotal = $qty * $price;
                $discount = rand(0, 5000);
                $subtotalAfterDiscount = $subtotal - $discount;

                $total += $subtotalAfterDiscount;

                $details[] = [
                    'pos_id' => $i,
                    'product_id' => rand(1, 10),
                    'quantity' => $qty,
                    'price' => $price,
                    'hpp' => $hpp,
                    'subtotal' => $subtotalAfterDiscount,
                    'discount' => $discount,
                    'exp_value' => $subtotalAfterDiscount * 0.2,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            // Buat transaksi utama
            $paid = $total + rand(0, 20000); // bisa lebih besar dari total
            $return = max(0, $paid - $total);

            $transactions[] = [
                'uuid' => Str::uuid(),
                'customer_id' => rand(1, 6),
                'date' => $date,
                'invoice_number' => 'INV202507' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'total' => $total,
                'paid' => $paid,
                'return' => 0,
                'payment_method' => rand(1, 2),
                'status' => 'paid',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            // Buat pembayaran
            $payments[] = [
                'uuid' => Str::uuid(),
                'nota_number' => 'NOTA-202507' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'pos_id' => $i,
                'total' => $total,
                'remaining' => 0,
                'return' => 0,
                'payment_method' => rand(1, 2),
                'branch_id' => rand(1, 4),
                'date' => $date,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        // Insert data
        // DB::table('pos_transaction')->insert($transactions);
        // DB::table('pos_transaction_detail')->insert($details);
        // DB::table('pos_payment')->insert($payments);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_transaction');
        Schema::dropIfExists('pos_transaction_detail');
        Schema::dropIfExists('pos_payment');
    }
};
