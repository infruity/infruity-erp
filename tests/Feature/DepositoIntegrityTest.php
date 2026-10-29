<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Crm\Entities\Deposito;
use Tests\TestCase;

class DepositoIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'deposito_integrity_test', 'database.connections.deposito_integrity_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('role', function (Blueprint $table) {
            $table->integer('id_role')->primary();
            $table->string('nm_role');
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id_ru');
            $table->integer('id_user');
            $table->integer('id_role');
        });
        Schema::create('role_menu', function (Blueprint $table) {
            $table->increments('id_rm');
            $table->integer('id_role');
            $table->string('permission');
        });
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_user');
            $table->string('permission');
            $table->boolean('is_allowed');
        });
        DB::table('role')->insert(['id_role' => 2, 'nm_role' => 'CRM Operator']);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert([
            ['id_role' => 2, 'permission' => 'deposito.store'],
            ['id_role' => 2, 'permission' => 'deposito.update'],
        ]);
        $user = new User();
        $user->id_user = 7;
        $this->actingAs($user);
        session(['role' => ['id_role' => 2]]);

        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        DB::table('customer')->insert(['id' => 1, 'name' => 'CRM Fixture']);
        foreach (['2025_07_01_095927_create_tier_table.php', '2025_07_20_182612_create_crm_point_schedule_table.php', '2025_08_15_042020_create_deposito_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('deposito')->delete();
        DB::table('crm_point_schedule')->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['customer_id' => 1, 'tier_id' => 3, 'date' => '2026-10-29', 'deposito' => '2500000'], $changes);
    }

    public static function invalidAmounts(): array
    {
        return [
            'zero free tier' => ['0', 2],
            'negative' => ['-2500000', 3],
            'broken groups' => ['2.50.0000', 3],
            'decimal disguised as grouping' => ['250000.0', 3],
            'repeated separators' => ['2..500.000', 3],
            'comma grouping is not the UI format' => ['2,500,000', 3],
            'mixed separators' => ['2.500,000', 3],
            'trailing separator' => ['2500000.', 3],
            'scientific' => ['2.5e6', 3],
            'missing' => [null, 3],
            'array' => [['2500000'], 3],
            'overflow' => ['9223372036854775808', 3],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidAmounts')]
    public function test_invalid_amount_cannot_create_benefits(mixed $amount, int $tier): void
    {
        $this->postJson(route('deposito.store'), $this->payload(['deposito' => $amount, 'tier_id' => $tier]))
            ->assertUnprocessable()->assertJsonValidationErrors('deposito');
        $this->assertDatabaseCount('deposito', 0);
    }

    public function test_formatted_ui_amount_creates_authoritative_tier_benefits(): void
    {
        $this->postJson(route('deposito.store'), $this->payload(['deposito' => '2.500.000', 'voucher' => 1, 'voucher_qty' => 999, 'exp' => 999999]))
            ->assertCreated()->assertJsonPath('data.deposito', '2500000');
        $this->assertDatabaseHas('deposito', [
            'customer_id' => 1, 'tier_id' => 3, 'deposito' => 2500000,
            'voucher' => 100000, 'voucher_qty' => 25, 'exp' => 50000,
            'start_period' => '2026-01-01', 'end_period' => '2026-12-31',
        ]);
        $this->assertDatabaseCount('deposito', 1);
    }

    public static function invalidTierConfiguration(): array
    {
        return [
            'missing amount' => ['deposito', null],
            'zero amount' => ['deposito', 0],
            'negative amount' => ['deposito', -2500000],
            'negative voucher' => ['voucher', -100000],
            'missing voucher' => ['voucher', null],
            'negative EXP' => ['exp', -1],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidTierConfiguration')]
    public function test_invalid_tier_configuration_cannot_create_benefits(string $field, mixed $value): void
    {
        DB::table('crm_tier')->where('id', 3)->update([$field => $value]);
        $this->postJson(route('deposito.store'), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('deposito', 0);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_missing_schedule_does_not_create_benefits(): void
    {
        DB::table('crm_point_schedule')->delete();
        $this->postJson(route('deposito.store'), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('deposito', 0);
    }

    public function test_invalid_schedule_does_not_create_benefits(): void
    {
        DB::table('crm_point_schedule')->update(['end_date' => '2025-12-31']);
        $this->postJson(route('deposito.store'), $this->payload())->assertUnprocessable();
        $this->assertDatabaseCount('deposito', 0);
    }

    public function test_configuration_is_read_inside_the_write_transaction(): void
    {
        $levels = [];
        DB::connection()->beforeExecuting(function ($query) use (&$levels) {
            if (preg_match('/^select .* from "(crm_tier|crm_point_schedule)"/i', $query) && !str_contains($query, 'count(*)')) {
                $levels[] = DB::transactionLevel();
            }
        });
        $this->postJson(route('deposito.store'), $this->payload())->assertCreated();
        $this->assertCount(2, $levels);
        $this->assertSame([1, 1], $levels);
    }

    public function test_database_failure_rolls_back_without_exposing_sql(): void
    {
        DB::unprepared("CREATE TRIGGER fail_deposito AFTER INSERT ON deposito BEGIN SELECT RAISE(ABORT, 'sensitive customer SQL fixture'); END");
        $response = $this->postJson(route('deposito.store'), $this->payload());
        $this->assertGreaterThanOrEqual(400, $response->status());
        $this->assertStringNotContainsString('sensitive customer SQL fixture', $response->getContent());
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertDatabaseCount('deposito', 0);
        $this->assertSame(0, DB::transactionLevel());
    }

    private function existingDeposit(): int
    {
        return DB::table('deposito')->insertGetId([
            'customer_id' => 1, 'deposito_date' => '2026-10-01', 'tier_id' => 3,
            'start_period' => '2026-01-01', 'end_period' => '2026-12-31',
            'deposito' => 2500000, 'voucher' => 100000, 'voucher_qty' => 25, 'exp' => 50000,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidAmounts')]
    public function test_invalid_amount_cannot_change_existing_benefits(mixed $amount, int $tier): void
    {
        $id = $this->existingDeposit();
        $before = (array) DB::table('deposito')->find($id);
        $this->putJson(route('deposito.update', $id), $this->payload(['deposito' => $amount]))
            ->assertUnprocessable()->assertJsonValidationErrors('deposito');
        $this->assertSame($before, (array) DB::table('deposito')->find($id));
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_update_cannot_reduce_deposit_while_keeping_tier_benefits(): void
    {
        $id = $this->existingDeposit();
        $before = (array) DB::table('deposito')->find($id);
        $this->putJson(route('deposito.update', $id), $this->payload(['deposito' => '1']))
            ->assertUnprocessable()->assertJsonValidationErrors('deposito');
        $this->assertSame($before, (array) DB::table('deposito')->find($id));
    }

    public function test_valid_update_keeps_the_original_amount_and_benefit_snapshot(): void
    {
        $id = $this->existingDeposit();
        DB::table('crm_tier')->where('id', 3)->update(['deposito' => 5000000]);
        $this->putJson(route('deposito.update', $id), $this->payload(['deposito' => '2.500.000', 'tier_id' => 4]))
            ->assertCreated();
        $this->assertDatabaseHas('deposito', [
            'id' => $id, 'deposito_date' => '2026-10-29', 'tier_id' => 3,
            'deposito' => 2500000, 'voucher' => 100000, 'voucher_qty' => 25, 'exp' => 50000,
        ]);
    }

    public function test_update_failure_rolls_back_without_exposing_sql(): void
    {
        $id = $this->existingDeposit();
        $before = (array) DB::table('deposito')->find($id);
        DB::unprepared("CREATE TRIGGER fail_deposito_update AFTER UPDATE ON deposito BEGIN SELECT RAISE(ABORT, 'sensitive update fixture'); END");
        $response = $this->putJson(route('deposito.update', $id), $this->payload());
        $this->assertGreaterThanOrEqual(400, $response->status());
        $this->assertStringNotContainsString('sensitive update fixture', $response->getContent());
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertSame($before, (array) DB::table('deposito')->find($id));
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function guardedWrites(): array
    {
        return [
            ['postJson', 'deposito.store', 'deposito.store'],
            ['putJson', 'deposito.update', 'deposito.update'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('guardedWrites')]
    public function test_permissions_are_checked_before_validation_and_writes(string $method, string $route, string $permission): void
    {
        $id = $this->existingDeposit();
        $before = (array) DB::table('deposito')->find($id);
        DB::table('role_menu')->delete();
        $this->{$method}(route($route, $route === 'deposito.update' ? $id : []), [])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame($before, (array) DB::table('deposito')->find($id));
        $this->assertDatabaseCount('deposito', 1);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('guardedWrites')]
    public function test_explicit_permission_deny_prevents_writes(string $method, string $route, string $permission): void
    {
        $id = $this->existingDeposit();
        $before = (array) DB::table('deposito')->find($id);
        DB::table('user_permissions')->insert(['id_user' => 7, 'permission' => $permission, 'is_allowed' => false]);
        $this->{$method}(route($route, $route === 'deposito.update' ? $id : []), $this->payload())
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame($before, (array) DB::table('deposito')->find($id));
        $this->assertDatabaseCount('deposito', 1);
    }

    public function test_foreign_customer_and_tier_ids_are_rejected_without_writes(): void
    {
        foreach ([['customer_id' => 999], ['tier_id' => 999]] as $change) {
            $this->postJson(route('deposito.store'), $this->payload($change))
                ->assertUnprocessable()->assertJsonValidationErrors(array_keys($change));
        }
        $this->assertDatabaseCount('deposito', 0);
    }

    public function test_zero_voucher_tier_keeps_zero_quantity_without_dividing_by_zero(): void
    {
        DB::table('crm_tier')->where('id', 3)->update(['voucher' => 0]);
        $this->postJson(route('deposito.store'), $this->payload())->assertCreated();
        $this->assertDatabaseHas('deposito', ['deposito' => 2500000, 'voucher' => 0, 'voucher_qty' => 0, 'exp' => 50000]);
    }

    public function test_delete_failure_does_not_expose_sql(): void
    {
        $id = $this->existingDeposit();
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'deposito.destroy']);
        DB::unprepared("CREATE TRIGGER fail_deposito_delete AFTER DELETE ON deposito BEGIN SELECT RAISE(ABORT, 'sensitive delete fixture'); END");
        $response = $this->deleteJson(route('deposito.destroy', $id))->assertServerError();
        $this->assertStringNotContainsString('sensitive delete fixture', $response->getContent());
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertDatabaseCount('deposito', 1);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_mismatched_amount_cannot_issue_tier_benefits(): void
    {
        $this->postJson(route('deposito.store'), $this->payload(['deposito' => '1']))
            ->assertUnprocessable()->assertJsonValidationErrors('deposito');

        $this->assertDatabaseCount('deposito', 0);
    }
}
