<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arus_kas_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number', 40)->unique();
            $table->unsignedBigInteger('branch_id')->index();
            $table->date('date');
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('arus_kas_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arus_kas_transaction_id')
                ->constrained('arus_kas_transactions')
                ->cascadeOnDelete();
            $table->string('direction', 8);
            $table->string('description');
            $table->string('unit', 60)->nullable();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arus_kas_items');
        Schema::dropIfExists('arus_kas_transactions');
    }
};
