<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class InventoryMySqlWorkerGuardTest extends TestCase
{
    public function test_worker_rejects_other_schemas_with_the_test_prefix_before_booting_laravel(): void
    {
        $root = dirname(__DIR__, 2);
        $process = new Process([
            PHP_BINARY,
            $root.'/tests/Support/mysql-inventory-worker.php',
            $root,
            '0',
            'production',
        ], $root, [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DATABASE_URL' => '',
            'INFRUITY_TEST_MYSQL_DATABASE' => 'infruity_test_accounts',
            'INFRUITY_TEST_MYSQL_SOCKET' => '',
            'INFRUITY_TEST_MYSQL_HOST' => '',
        ], null, 20);

        $process->run();
        $output = $process->getOutput().$process->getErrorOutput();

        $this->assertNotSame(0, $process->getExitCode(), $output);
        $this->assertStringContainsString(
            'Only a generated infruity_test_inventory_<16 hex> schema is allowed.',
            $output
        );
    }
}