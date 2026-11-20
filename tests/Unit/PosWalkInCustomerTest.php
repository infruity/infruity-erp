<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Modules\Pos\Http\Controllers\PosController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PosWalkInCustomerTest extends TestCase
{
    private function normalized(array $input): mixed
    {
        $request = Request::create('/pos', 'POST', $input);
        $method = new ReflectionMethod(PosController::class, 'normalizeWalkInCustomer');
        $method->invoke((new \ReflectionClass(PosController::class))->newInstanceWithoutConstructor(), $request);

        return $request->input('customer_id');
    }

    public function test_walk_in_customer_values_become_null(): void
    {
        $this->assertNull($this->normalized(['customer_id' => '0']));
        $this->assertNull($this->normalized(['customer_id' => 0]));
        $this->assertNull($this->normalized(['customer_id' => '']));
        $this->assertNull($this->normalized([]));
    }

    public function test_a_real_customer_id_is_left_alone(): void
    {
        $this->assertSame('12', $this->normalized(['customer_id' => '12']));
    }
}
