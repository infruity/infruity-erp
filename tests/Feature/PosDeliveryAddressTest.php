<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class PosDeliveryAddressTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'address_test', 'database.connections.address_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('users', fn (Blueprint $t) => [$t->integer('id_user')->primary(), $t->string('nm_user'), $t->string('email')->nullable()]);
        Schema::create('role', fn (Blueprint $t) => $t->integer('id_role')->primary());
        Schema::create('role_user', fn (Blueprint $t) => [$t->increments('id_ru'), $t->integer('id_user'), $t->integer('id_role'), $t->timestamps()]);
        Schema::create('role_menu', fn (Blueprint $t) => [$t->increments('id_rm'), $t->integer('id_role'), $t->string('permission')]);
        Schema::create('user_permissions', fn (Blueprint $t) => [$t->increments('id'), $t->integer('id_user'), $t->string('permission'), $t->boolean('is_allowed'), $t->timestamps()]);
        Schema::create('user_branch', fn (Blueprint $t) => [$t->integer('user_id'), $t->integer('branch_id')]);
        Schema::create('branch', fn (Blueprint $t) => [$t->integer('id')->primary(), $t->string('name')]);
        Schema::create('customer', fn (Blueprint $t) => [$t->integer('id')->primary(), $t->string('name'), $t->text('address')->nullable(), $t->string('whatsapp')->nullable()]);
        Schema::create('customer_address', fn (Blueprint $t) => [$t->increments('id'), $t->integer('customer_id'), $t->text('address')]);
        Schema::create('pos_transaction', fn (Blueprint $t) => [$t->increments('id'), $t->integer('branch_id'), $t->integer('customer_id')->nullable(), $t->string('status'), $t->text('ongkir_address')->nullable(), $t->timestamps(), $t->softDeletes()]);
        (require database_path('migrations/2026_11_16_210000_create_pos_delivery_addresses.php'))->up();
        DB::table('users')->insert(['id_user' => 7, 'nm_user' => 'Kasir', 'email' => 'master@infruity.com']);
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'pos.payment']);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('branch')->insert([['id' => 1, 'name' => 'Cabang'], ['id' => 2, 'name' => 'Lain']]);
        DB::table('customer')->insert(['id' => 10, 'name' => 'Pelanggan']);
        DB::table('pos_transaction')->insert([
            ['id' => 1, 'branch_id' => 1, 'customer_id' => null, 'status' => 'draft'],
            ['id' => 2, 'branch_id' => 1, 'customer_id' => null, 'status' => 'draft'],
            ['id' => 3, 'branch_id' => 2, 'customer_id' => null, 'status' => 'draft'],
            ['id' => 4, 'branch_id' => 1, 'customer_id' => 10, 'status' => 'draft'],
            ['id' => 5, 'branch_id' => 1, 'customer_id' => 10, 'status' => 'draft'],
        ]);
        $this->actingAs(User::findOrFail(7));
    }

    private function values(string $name = 'Penerima', bool $default = false): array
    {
        return ['name' => $name, 'phone' => '81234567890', 'address' => 'Jalan Contoh 1', 'is_default' => $default];
    }

    public function test_guest_crud_selection_and_reload_preserve_recipient_details(): void
    {
        $first = $this->postJson('/pos/payment/1/addresses', $this->values())->assertOk()->json('selected_id');
        $this->getJson('/pos/payment/1/addresses')->assertOk()->assertJsonPath('selected.name', 'Penerima')->assertJsonPath('selected.phone', '081234567890')->assertJsonPath('addresses.0.is_default', true);
        $second = $this->postJson('/pos/payment/1/addresses', $this->values('Kedua', true))->assertOk();
        $second->assertJsonPath('selected_id', $first)->assertJsonPath('addresses.0.is_default', false)->assertJsonPath('addresses.1.is_default', true);
        $id = $second->json('addresses.1.id');
        $this->postJson("/pos/payment/1/addresses/$id/select")->assertOk()->assertJsonPath('selected.name', 'Kedua');
        $this->putJson("/pos/payment/1/addresses/$id", $this->values('Diubah'))->assertOk()->assertJsonPath('selected.name', 'Diubah');
        $this->deleteJson("/pos/payment/1/addresses/$id")->assertOk()->assertJsonPath('selected_id', $first);
        $this->deleteJson("/pos/payment/1/addresses/$first")->assertOk()->assertJsonPath('selected_id', null);
        $this->assertNull(DB::table('pos_transaction')->where('id', 1)->value('ongkir_address'));
        $this->assertNotNull(DB::table('pos_delivery_addresses')->where('id', $id)->value('deleted_at'));
    }

    public function test_addresses_cannot_cross_guests_branches_or_permissions(): void
    {
        $id = $this->postJson('/pos/payment/1/addresses', $this->values())->assertOk()->json('selected_id');
        $this->getJson('/pos/payment/2/addresses')->assertOk()->assertJsonCount(0, 'addresses');
        $this->postJson("/pos/payment/2/addresses/$id/select")->assertNotFound();
        $this->putJson("/pos/payment/2/addresses/$id", $this->values())->assertNotFound();
        $this->deleteJson("/pos/payment/2/addresses/$id")->assertNotFound();
        $this->getJson('/pos/payment/3/addresses')->assertNotFound();
        DB::table('role_menu')->delete();
        $this->getJson('/pos/payment/1/addresses')->assertForbidden();
    }

    public function test_customer_address_book_is_shared_but_paid_transaction_snapshot_is_immutable(): void
    {
        $id = $this->postJson('/pos/payment/4/addresses', $this->values())->assertOk()->json('selected_id');
        $this->getJson('/pos/payment/5/addresses')->assertOk()->assertJsonCount(1, 'addresses');
        $this->postJson("/pos/payment/5/addresses/$id/select")->assertOk();
        DB::table('pos_transaction')->where('id', 5)->update(['status' => 'paid']);
        $this->putJson("/pos/payment/4/addresses/$id", $this->values('Nama baru'))->assertOk();
        $snapshot = json_decode(DB::table('pos_transaction')->where('id', 5)->value('delivery_address_snapshot'), true);
        $this->assertSame('Penerima', $snapshot['name']);
        $this->postJson("/pos/payment/5/addresses/$id/select")->assertUnprocessable();
        $this->deleteJson("/pos/payment/5/addresses/$id")->assertUnprocessable();
    }

    public function test_invalid_input_does_not_change_address_or_transaction(): void
    {
        $this->postJson('/pos/payment/1/addresses', $this->values() + [])->assertOk();
        $this->postJson('/pos/payment/1/addresses', ['name' => '', 'address' => '', 'phone' => '<script>', 'is_default' => true])->assertUnprocessable();
        $this->assertSame(1, DB::table('pos_delivery_addresses')->count());
    }

    public function test_legacy_addresses_are_available_and_deleting_all_does_not_restore_them(): void
    {
        DB::table('customer')->where('id', 10)->update(['address' => 'Alamat lama', 'whatsapp' => '081234567890']);
        DB::table('customer_address')->insert(['customer_id' => 10, 'address' => 'Alamat kedua']);
        $response = $this->getJson('/pos/payment/4/addresses')->assertOk()->assertJsonCount(2, 'addresses');
        $this->assertSame('Alamat lama', $response->json('addresses.0.address'));
        foreach ($response->json('addresses') as $address) {
            $this->deleteJson('/pos/payment/4/addresses/'.$address['id'])->assertOk();
        }
        $this->getJson('/pos/payment/4/addresses')->assertOk()->assertJsonCount(0, 'addresses');
        $this->assertSame('Alamat lama', DB::table('customer')->where('id', 10)->value('address'));
    }

    public function test_deletion_keeps_the_existing_master_account_restriction(): void
    {
        $id = $this->postJson('/pos/payment/1/addresses', $this->values())->assertOk()->json('selected_id');
        $user = User::findOrFail(7);
        $user->email = 'kasir@example.test';
        $this->actingAs($user);
        $this->deleteJson("/pos/payment/1/addresses/$id")->assertForbidden();
        $this->assertNull(DB::table('pos_delivery_addresses')->where('id', $id)->value('deleted_at'));
    }

    public function test_legacy_dash_is_an_empty_address_not_a_saved_address(): void
    {
        DB::table('pos_transaction')->where('id', 1)->update(['ongkir_address' => '-']);
        $this->getJson('/pos/payment/1/addresses')->assertOk()->assertJsonCount(0, 'addresses')
            ->assertJsonPath('current_address', 'Belum ada alamat pengiriman. Klik untuk mengatur alamat.');
    }

    public function test_each_required_address_field_must_be_filled_before_saving(): void
    {
        foreach (['name', 'phone', 'address'] as $field) {
            foreach ([null, '', '   '] as $missing) {
                $data = $this->values();
                $data[$field] = $missing;
                $this->postJson('/pos/payment/1/addresses', $data)
                    ->assertUnprocessable()->assertJsonValidationErrors($field);
            }
        }
        $this->assertSame(0, DB::table('pos_delivery_addresses')->count());
        $this->assertNull(DB::table('pos_transaction')->where('id', 1)->value('delivery_address_snapshot'));
    }
}
