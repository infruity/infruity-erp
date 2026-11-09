<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\Pos\Http\Controllers\PosController;
use Tests\TestCase;

/**
 * Kasir / POS list endpoint: the status labels, amounts and filter chips of the reference list.
 * Runs against an in-memory SQLite database seeded with one row per state, never business data.
 */
final class PosListFiltersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('sqlite');
        $this->createSchema();
        $this->seedRecords();

        $user = new User();
        $user->id_user = 7;
        $user->nm_user = 'QA User';
        $this->actingAs($user);
        $this->withSession(['role' => ['id_role' => 2]]); // the row action menu reads the session role
        Route::get('/_test/pos-data', [PosController::class, 'get_data']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        DB::purge('sqlite');
        parent::tearDown();
    }

    private function createSchema(): void
    {
        Schema::create('role', fn (Blueprint $t) => $t->integer('id_role')->primary());
        Schema::create('role_user', function (Blueprint $t) {
            $t->increments('id_ru'); $t->integer('id_user'); $t->integer('id_role');
        });
        Schema::create('role_menu', function (Blueprint $t) {
            $t->increments('id_rm'); $t->integer('id_role'); $t->string('permission');
        });
        Schema::create('user_permissions', function (Blueprint $t) {
            $t->increments('id'); $t->integer('id_user'); $t->string('permission'); $t->boolean('is_allowed');
        });
        Schema::create('users', function (Blueprint $t) {
            $t->integer('id_user')->primary(); $t->string('nm_user');
        });
        Schema::create('branch', function (Blueprint $t) {
            $t->increments('id'); $t->string('name');
        });
        Schema::create('user_branch', function (Blueprint $t) {
            $t->integer('user_id'); $t->integer('branch_id');
        });
        Schema::create('customer', function (Blueprint $t) {
            $t->increments('id'); $t->string('name'); $t->string('whatsapp')->nullable();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->increments('id'); $t->string('name');
        });
        Schema::create('pos_transaction', function (Blueprint $t) {
            $t->increments('id'); $t->integer('customer_id')->nullable(); $t->date('date'); $t->string('invoice_number');
            $t->decimal('total', 15, 2)->default(0); $t->decimal('voucher', 15, 2)->nullable(); $t->string('status');
            $t->integer('branch_id')->nullable(); $t->integer('created_by')->nullable();
            $t->softDeletes(); $t->timestamps();
        });
        Schema::create('pos_transaction_detail', function (Blueprint $t) {
            $t->increments('id'); $t->integer('pos_id'); $t->integer('product_id')->nullable(); $t->integer('parcel_id')->nullable();
            $t->string('type')->default('product'); $t->decimal('quantity', 10, 2)->default(1); $t->decimal('price', 15, 2)->default(0);
            $t->softDeletes(); $t->timestamps();
        });
        Schema::create('pos_payment', function (Blueprint $t) {
            $t->increments('id'); $t->integer('pos_id'); $t->decimal('total', 15, 2)->default(0); $t->softDeletes(); $t->timestamps();
        });
    }

    private function seedRecords(): void
    {
        DB::table('role')->insert(['id_role' => 2]);
        DB::table('role_user')->insert(['id_user' => 7, 'id_role' => 2]);
        DB::table('role_menu')->insert(['id_role' => 2, 'permission' => 'pos.index']);
        DB::table('users')->insert([['id_user' => 7, 'nm_user' => 'QA User'], ['id_user' => 8, 'nm_user' => 'Other']]);
        DB::table('branch')->insert([['id' => 1, 'name' => 'GKB'], ['id' => 2, 'name' => 'GBA']]);
        DB::table('user_branch')->insert(['user_id' => 7, 'branch_id' => 1]);
        DB::table('customer')->insert([
            ['id' => 1, 'name' => 'Noorman', 'whatsapp' => '6281230607050'],
            ['id' => 2, 'name' => 'Ainun', 'whatsapp' => '6281234568219'],
        ]);
        DB::table('products')->insert(['id' => 11, 'name' => 'Paket Hampers']);

        $this->insertPos(1, 'paid', 100000, 1);
        $this->insertPos(2, 'debt', 100000, 2);
        $this->insertPos(3, 'debt', 100000, 1);
        $this->insertPos(4, 'draft', 55000, null);
        $this->insertPos(5, 'temp', 0, null, 7);
        $this->insertPos(6, 'temp', 0, null, 8); // someone else's draft: never listed
        $this->insertPos(7, 'canceled', 20000, null);
        $this->insertPos(8, 'paid', 10000, null);
        DB::table('pos_transaction')->where('id', 8)->update(['deleted_at' => '2026-11-03 00:00:00']);
        $this->insertPos(9, 'paid', 10000, null, 7, 2); // branch the user cannot see

        DB::table('pos_payment')->insert([
            ['pos_id' => 1, 'total' => 100000, 'deleted_at' => null],
            ['pos_id' => 3, 'total' => 25000, 'deleted_at' => null],
            ['pos_id' => 3, 'total' => 15000, 'deleted_at' => null],
            ['pos_id' => 3, 'total' => 999999, 'deleted_at' => '2026-11-03 00:00:00'], // a removed payment never counts
        ]);
        DB::table('pos_transaction_detail')->insert(['pos_id' => 1, 'product_id' => 11, 'type' => 'parcel', 'quantity' => 1, 'price' => 100000]);
    }

    private function insertPos(int $id, string $status, float $total, ?int $customerId, int $by = 7, int $branch = 1): void
    {
        DB::table('pos_transaction')->insert([
            'id' => $id, 'customer_id' => $customerId, 'date' => '2026-10-27', 'invoice_number' => 'INV-QA-' . $id,
            'total' => $total, 'status' => $status, 'branch_id' => $branch, 'created_by' => $by,
            'created_at' => '2026-10-27 09:05:00', 'updated_at' => '2026-10-27 09:05:00',
        ]);
    }

    private function fetch(array $filters = []): array
    {
        $query = http_build_query(array_merge(['draw' => 1, 'start' => 0, 'length' => 100], $filters));
        $response = $this->getJson('/_test/pos-data?' . $query);
        $response->assertOk();

        return $response->json('data');
    }

    /** Row ids come from the detail link in the name cell. */
    private function ids(array $filters = []): array
    {
        return collect($this->fetch($filters))
            ->map(function (array $row) {
                preg_match('#/show/(\d+)#', $row['name'], $match);

                return (int) $match[1];
            })->sort()->values()->all();
    }

    private function cell(int $id): array
    {
        foreach ($this->fetch() as $row) {
            if (str_contains($row['name'], '/show/' . $id . '"')) {
                return ['name' => $row['name'], 'status' => $row['date']];
            }
        }
        $this->fail("Row {$id} is not listed");
    }

    public function test_status_chips_match_the_labels_the_rows_render(): void
    {
        $this->assertSame([1, 2, 3, 4, 5, 7], $this->ids());
        $this->assertSame([1], $this->ids(['status_filter' => 'paid']));
        $this->assertSame([2], $this->ids(['status_filter' => 'unpaid']));
        $this->assertSame([3], $this->ids(['status_filter' => 'partial']));
        $this->assertSame([2, 3], $this->ids(['status_filter' => 'debt']));
        $this->assertSame([4, 5], $this->ids(['status_filter' => 'draft'])); // "Pending" renders for draft and temp
        $this->assertSame([7], $this->ids(['status_filter' => 'canceled']));
        $this->assertSame([1, 2, 3, 4, 5, 7], $this->ids(['status_filter' => 'all']));
    }

    public function test_status_labels_follow_the_reference(): void
    {
        $this->assertMatchesRegularExpression('/is-paid[^>]*>Lunas</', $this->cell(1)['status']);
        $this->assertMatchesRegularExpression('/is-unpaid[^>]*>Belum Bayar</', $this->cell(2)['status']);
        $this->assertMatchesRegularExpression('/is-partial[^>]*>Kurang Bayar</', $this->cell(3)['status']);
        $this->assertMatchesRegularExpression('/is-pending[^>]*>Pending</', $this->cell(4)['status']);
        $this->assertMatchesRegularExpression('/is-pending[^>]*>Pending</', $this->cell(5)['status']);
        $this->assertMatchesRegularExpression('/is-canceled[^>]*>Dihapus</', $this->cell(7)['status']);
    }

    public function test_amount_line_is_total_when_paid_and_paid_over_total_otherwise(): void
    {
        // The gaps around "/" come from CSS, so normalise them before comparing the visible text.
        $text = fn (int $id) => trim(preg_replace('/\s*\/\s*/', ' / ', preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($this->cell($id)['status'])))));

        $this->assertStringContainsString('Lunas Rp 100.000', $text(1));
        $this->assertStringContainsString('Belum Bayar Rp 0 / 100.000', $text(2));
        $this->assertStringContainsString('Kurang Bayar Rp 40.000 / 100.000', $text(3)); // the removed payment is ignored
        $this->assertStringContainsString('Pending Rp 0 / 55.000', $text(4));
        $this->assertStringNotContainsString('Sisa', $text(3));
    }

    public function test_date_line_uses_indonesian_month_names_without_a_leading_zero(): void
    {
        $this->assertStringContainsString('27 Okt 2026, 09:05', strip_tags($this->cell(1)['name']));
    }
}
