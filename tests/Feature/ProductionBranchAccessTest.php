<?php

namespace Tests\Feature;

use App\Support\InventorySnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\QaFixtures;
use Tests\TestCase;

/**
 * A production run can only be written by someone who works in its branch, and a posted run is final.
 * Run: php vendor/bin/phpunit -c phpunit.mysql.xml
 */
class ProductionBranchAccessTest extends TestCase
{
    use QaFixtures;

    private int $mine;
    private int $other;
    private int $fruit;
    private int $output;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireMysqlTestDatabase();
        // The endpoints may disconnect the database, so DatabaseTransactions cannot isolate these tests.
        $this->rememberCommittedBaseline();

        $this->mine = $this->makeBranch('MILIK');
        $this->other = $this->makeBranch('LAIN');
        $this->fruit = $this->makeProduct('Buah');
        $this->output = $this->makeProduct('Olahan');
        $this->giveStock($this->fruit, $this->mine, 10);
        $this->giveStock($this->fruit, $this->other, 10);
    }

    protected function tearDown(): void
    {
        $this->cleanCommittedRows();
        parent::tearDown();
    }

    private function actAsAdmin(): void
    {
        $this->actAsRole($this->makeUser($this->superAdminRoleId()), 1);
    }

    private function actAsCrew(): void
    {
        $role = $this->makeRole('Crew');
        foreach (['production.store', 'production.save-completion'] as $permission) {
            DB::table('role_menu')->insert(['id_role' => $role, 'permission' => $permission]);
        }
        $this->actAsRole($this->makeUser($role, [], [$this->mine]), $role);
    }

    private function store(string $number, string $type, int $branch, array $extra = [])
    {
        return $this->postJson(route('production.store'), $extra + [
            'product_id' => $this->output, 'production_number' => $number, 'submit_type' => $type,
            'production_date' => date('Y-m-d'), 'quantity' => 2, 'branch_id' => $branch, 'service_cost' => '0',
            'ingredients' => [['id' => $this->fruit, 'quantity' => 4, 'hpp' => 10000]],
        ]);
    }

    /** @return array{0:string,1:int} number and id of a run saved by the admin */
    private function adminRun(string $type, int $branch): array
    {
        $this->actAsAdmin();
        $number = 'PRDQA' . strtoupper(Str::random(8));
        $this->store($number, $type, $branch)->assertOk();

        return [$number, (int) DB::table('production')->where('production_number', $number)->value('id')];
    }

    private function fruitStock(int $branch): float
    {
        return InventorySnapshot::stockAvailable([$this->fruit], $branch)[$this->fruit];
    }

    public function test_a_posted_run_of_another_branch_cannot_be_overwritten(): void
    {
        [$number, $id] = $this->adminRun('posting', $this->other);
        $this->actAsCrew();

        $this->store($number, 'temp', $this->mine, ['id' => $id])->assertStatus(422);

        $this->assertDatabaseHas('production', ['id' => $id, 'status' => 'posting', 'branch_id' => $this->other]);
        $this->assertSame(6.0, $this->fruitStock($this->other));
    }

    public function test_a_posted_run_cannot_be_overwritten_even_by_its_own_branch_or_an_admin(): void
    {
        [$number, $id] = $this->adminRun('posting', $this->mine);

        $this->store($number, 'temp', $this->mine, ['id' => $id])->assertStatus(422);
        $this->actAsCrew();
        $this->store($number, 'draft', $this->mine, ['id' => $id])->assertStatus(422);

        $this->assertDatabaseHas('production', ['id' => $id, 'status' => 'posting']);
        $this->assertSame(6.0, $this->fruitStock($this->mine));
    }

    public function test_crew_cannot_post_into_another_branch(): void
    {
        $this->actAsCrew();
        $number = 'PRDQA' . strtoupper(Str::random(8));

        $this->store($number, 'posting', $this->other)->assertStatus(403);

        $this->assertDatabaseMissing('production', ['production_number' => $number]);
        $this->assertSame(10.0, $this->fruitStock($this->other));
    }

    public function test_crew_cannot_pull_a_draft_of_another_branch_into_their_own(): void
    {
        [$number, $id] = $this->adminRun('draft', $this->other);
        $this->actAsCrew();

        $this->store($number, 'draft', $this->mine, ['id' => $id])->assertStatus(422);

        $this->assertDatabaseHas('production', ['id' => $id, 'branch_id' => $this->other]);
    }

    public function test_crew_can_still_draft_and_post_in_their_own_branch(): void
    {
        $this->actAsCrew();
        $number = 'PRDQA' . strtoupper(Str::random(8));
        $this->store($number, 'draft', $this->mine)->assertOk();
        $id = (int) DB::table('production')->where('production_number', $number)->value('id');

        $this->store($number, 'posting', $this->mine, ['id' => $id])->assertOk();

        $this->assertDatabaseHas('production', ['id' => $id, 'status' => 'posting']);
        $this->assertSame(6.0, $this->fruitStock($this->mine));
    }

    public function test_completion_is_refused_for_another_branch_and_for_a_finished_run(): void
    {
        [, $foreign] = $this->adminRun('draft', $this->other);
        [, $posted] = $this->adminRun('posting', $this->mine);
        $this->actAsCrew();
        $complete = fn (int $id) => $this->postJson('/production/save-completion', ['production_id' => $id, 'completion_date' => date('Y-m-d')]);

        $complete($foreign)->assertStatus(403);
        $complete($posted)->assertStatus(422);

        $this->assertDatabaseHas('production', ['id' => $foreign, 'status' => 'draft']);
        $this->assertSame(10.0, $this->fruitStock($this->other));
    }
}
