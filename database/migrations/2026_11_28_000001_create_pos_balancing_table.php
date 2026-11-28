<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * POS balancing: a signed correction of a sale's revenue. One row is one event, so how often and when a cashier
 * balances can be counted. Rows are never edited or deleted; a mistake is cancelled by an opposite row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_balancing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pos_id');
            $table->decimal('amount', 15, 2);
            $table->text('note');
            $table->unsignedBigInteger('reversal_of_id')->nullable()->unique();
            $table->unsignedBigInteger('created_by');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('created_at')->useCurrent();

            // No cascade: the history must not disappear together with a sale.
            $table->foreign('pos_id')->references('id')->on('pos_transaction');
            $table->foreign('reversal_of_id')->references('id')->on('pos_balancing');
            $table->index('pos_id');
            $table->index(['created_by', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_balancing');
    }
};
