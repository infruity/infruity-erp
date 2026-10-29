<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpSettingsValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'exp_settings_validation_test', 'database.connections.exp_settings_validation_test' => [
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
            ['id_role' => 2, 'permission' => 'setting-exp.update'],
            ['id_role' => 2, 'permission' => 'point-schedule.update'],
        ]);
        $user = new User();
        $user->id_user = 7;
        $this->actingAs($user);
        session(['role' => ['id_role' => 2]]);

        foreach (['2025_07_08_111701_create_setting_exp_table.php', '2025_07_20_182612_create_crm_point_schedule_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('crm_tier', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('level');
            $table->integer('exp');
        });
        Schema::create('vw_customer_tier', function (Blueprint $table) {
            $table->integer('customer_id');
            $table->integer('tier_level');
            $table->integer('customer_exp');
        });
        DB::table('crm_point_schedule')->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    }

    private function payload(string $route, array $changes = []): array
    {
        $payload = ['skala' => '0.1', 'value' => '2'];
        if ($route === 'point-schedule.update') {
            $payload += ['start_date' => '2026-02-01', 'end_date' => '2026-05-01', 'frequency' => 3, 'break' => 1];
        }
        return array_replace($payload, $changes);
    }

    public static function invalidExpValues(): array
    {
        $cases = [];
        foreach (['setting-exp.update', 'point-schedule.update'] as $route) {
            foreach ([['skala', 0], ['skala', -0.1], ['skala', 'bad'], ['skala', null], ['skala', []], ['value', -1], ['value', 'bad'], ['value', null], ['value', []]] as [$field, $value]) {
                $cases[$route.' '.$field.' '.json_encode($value)] = [$route, $field, $value];
            }
        }
        return $cases;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidExpValues')]
    public function test_invalid_exp_values_do_not_partially_change_settings_or_schedule(string $route, string $field, mixed $value): void
    {
        $exp = (array) DB::table('setting_exp')->first();
        $schedule = (array) DB::table('crm_point_schedule')->first();
        $this->putJson(route($route, 1), $this->payload($route, [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($exp, (array) DB::table('setting_exp')->first());
        $this->assertSame($schedule, (array) DB::table('crm_point_schedule')->first());
        $this->assertSame(0, DB::transactionLevel());
    }

    public static function validDecimals(): array
    {
        return [
            ['setting-exp.update', '0.1', '2.5', 0.25],
            ['point-schedule.update', '0.1', '2.5', 0.25],
            ['setting-exp.update', '0.001', '0', 0.0],
            ['point-schedule.update', '0.001', '0', 0.0],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validDecimals')]
    public function test_valid_decimal_contract_is_preserved(string $route, string $scale, string $value, float $product): void
    {
        $this->put(route($route, 1), $this->payload($route, ['skala' => $scale, 'value' => $value]))
            ->assertRedirect(route(str_replace('.update', '.index', $route)))->assertSessionHasNoErrors()->assertSessionHas('success');
        $exp = DB::table('setting_exp')->first();
        $this->assertEqualsWithDelta((float) $scale, (float) $exp->skala, 0.000001);
        $this->assertEqualsWithDelta((float) $value, (float) $exp->value, 0.000001);
        $this->assertEqualsWithDelta($product, (float) $exp->value_exp, 0.000001);
        $this->assertSame(7, $exp->updated_by);
        if ($route === 'point-schedule.update') {
            $this->assertDatabaseHas('crm_point_schedule', ['start_date' => '2026-02-01', 'end_date' => '2026-05-01', 'updated_by' => 7]);
        }
    }

    public static function updateRoutes(): array
    {
        return [['setting-exp.update'], ['point-schedule.update']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('updateRoutes')]
    public function test_database_failure_does_not_partially_update_or_expose_sql(string $route): void
    {
        $exp = (array) DB::table('setting_exp')->first();
        $schedule = (array) DB::table('crm_point_schedule')->first();
        DB::unprepared("CREATE TRIGGER fail_exp AFTER UPDATE ON setting_exp BEGIN SELECT RAISE(ABORT, 'sensitive EXP SQL fixture'); END");
        $this->put(route($route, 1), $this->payload($route))->assertRedirect()->assertSessionHas('error');
        $this->assertStringNotContainsString('sensitive EXP SQL fixture', session('error'));
        $this->assertStringNotContainsString('SQLSTATE', session('error'));
        $this->assertSame($exp, (array) DB::table('setting_exp')->first());
        $this->assertSame($schedule, (array) DB::table('crm_point_schedule')->first());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_missing_exp_config_rolls_back_schedule_change(): void
    {
        DB::table('setting_exp')->delete();
        $schedule = (array) DB::table('crm_point_schedule')->first();
        $this->put(route('point-schedule.update', 1), $this->payload('point-schedule.update'))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame($schedule, (array) DB::table('crm_point_schedule')->first());
        $this->assertDatabaseCount('setting_exp', 0);
        $this->assertSame(0, DB::transactionLevel());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('updateRoutes')]
    public function test_missing_permission_denies_changes_before_validation(string $route): void
    {
        DB::table('role_menu')->delete();
        $exp = (array) DB::table('setting_exp')->first();
        $schedule = (array) DB::table('crm_point_schedule')->first();
        $this->putJson(route($route, 1), [])->assertRedirect()->assertSessionHas('error');
        $this->assertSame($exp, (array) DB::table('setting_exp')->first());
        $this->assertSame($schedule, (array) DB::table('crm_point_schedule')->first());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('updateRoutes')]
    public function test_explicit_permission_deny_prevents_exp_and_schedule_changes(string $route): void
    {
        DB::table('user_permissions')->insert(['id_user' => 7, 'permission' => $route, 'is_allowed' => false]);
        $exp = (array) DB::table('setting_exp')->first();
        $schedule = (array) DB::table('crm_point_schedule')->first();
        $this->putJson(route($route, 1), $this->payload($route))->assertRedirect()->assertSessionHas('error');
        $this->assertSame($exp, (array) DB::table('setting_exp')->first());
        $this->assertSame($schedule, (array) DB::table('crm_point_schedule')->first());
    }

    public static function invalidScheduleValues(): array
    {
        return [['start_date', 'bad'], ['end_date', '2025-01-01'], ['frequency', 999], ['break', 3]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidScheduleValues')]
    public function test_invalid_schedule_does_not_partially_change_exp(string $field, mixed $value): void
    {
        $exp = (array) DB::table('setting_exp')->first();
        $schedule = (array) DB::table('crm_point_schedule')->first();
        $this->putJson(route('point-schedule.update', 1), $this->payload('point-schedule.update', [$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($exp, (array) DB::table('setting_exp')->first());
        $this->assertSame($schedule, (array) DB::table('crm_point_schedule')->first());
    }

    public static function nonFiniteExpValues(): array
    {
        $cases = [];
        foreach (['setting-exp.update', 'point-schedule.update'] as $route) {
            $cases[] = [$route, ['skala' => '1e309'], 'skala'];
            $cases[] = [$route, ['value' => '1e309'], 'value'];
            $cases[] = [$route, ['skala' => '1e200', 'value' => '1e200'], 'value'];
        }
        return $cases;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonFiniteExpValues')]
    public function test_non_finite_exp_is_rejected_without_changes(string $route, array $changes, string $field): void
    {
        $exp = (array) DB::table('setting_exp')->first();
        $schedule = (array) DB::table('crm_point_schedule')->first();
        $this->putJson(route($route, 1), $this->payload($route, $changes))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($exp, (array) DB::table('setting_exp')->first());
        $this->assertSame($schedule, (array) DB::table('crm_point_schedule')->first());
    }

    public function test_negative_scale_is_rejected_before_settings_are_changed(): void
    {
        $before = (array) DB::table('setting_exp')->first();
        $this->putJson(route('setting-exp.update', 1), ['skala' => '-0.1', 'value' => '2'])
            ->assertUnprocessable()->assertJsonValidationErrors('skala');
        $this->assertSame($before, (array) DB::table('setting_exp')->first());
        $this->assertSame(0, DB::transactionLevel());
    }
}
