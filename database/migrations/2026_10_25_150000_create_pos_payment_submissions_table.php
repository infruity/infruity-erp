<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_payment_submissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Legacy installations do not consistently have compatible foreign keys.
            $table->unsignedBigInteger('pos_id');
            $table->uuid('request_key');
            $table->unsignedBigInteger('user_id');
            $table->char('request_hash', 64);
            $table->unsignedBigInteger('payment_id');
            $table->longText('response');
            $table->timestamps();
            $table->unique(['pos_id', 'request_key'], 'pos_payment_submission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_payment_submissions');
    }
};
