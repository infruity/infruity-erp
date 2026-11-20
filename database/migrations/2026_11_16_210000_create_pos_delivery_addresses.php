<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_delivery_addresses', function (Blueprint $table) {
            $table->id();
            // Registered customers share their address book; guests use the transaction's book.
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('pos_id')->nullable()->index();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->text('address');
            $table->boolean('is_default')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::table('pos_transaction', function (Blueprint $table) {
            $table->json('delivery_address_snapshot')->nullable();
        });

        // Copy legacy addresses without changing the master customer records.
        DB::table('customer')->orderBy('id')->chunkById(200, function ($customers) {
            foreach ($customers as $customer) {
                $addresses = DB::table('customer_address')->where('customer_id', $customer->id)->pluck('address')->all();
                array_unshift($addresses, $customer->address);
                $addresses = array_values(array_unique(array_filter(array_map(fn ($a) => trim((string) $a), $addresses), fn ($a) => $a !== '' && $a !== '-')));
                foreach ($addresses as $index => $address) {
                    DB::table('pos_delivery_addresses')->insert([
                        'customer_id' => $customer->id, 'name' => $customer->name,
                        'phone' => $customer->whatsapp, 'address' => $address, 'is_default' => $index === 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
        DB::table('pos_transaction')->whereNull('customer_id')->whereNotNull('ongkir_address')->orderBy('id')->chunkById(200, function ($transactions) {
            foreach ($transactions as $pos) {
                $address = trim((string) $pos->ongkir_address);
                if ($address === '' || $address === '-') continue;
                DB::table('pos_delivery_addresses')->insert([
                    'pos_id' => $pos->id, 'name' => 'Pelanggan Umum', 'address' => $address,
                    'is_default' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_transaction', fn (Blueprint $table) => $table->dropColumn('delivery_address_snapshot'));
        Schema::dropIfExists('pos_delivery_addresses');
    }
};
