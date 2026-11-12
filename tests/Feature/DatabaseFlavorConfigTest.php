<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Production (Hostinger shared hosting) runs MariaDB, local development runs MySQL 8. Both name the recursive-CTE
 * depth limit differently and a connection that sets an unknown session variable fails, so the name must follow
 * DB_SERVER. This reads config/database.php under each setting without needing either server.
 */
class DatabaseFlavorConfigTest extends TestCase
{
    private ?string $previous = null;

    protected function setUp(): void
    {
        parent::setUp();
        $value = getenv('DB_SERVER');
        $this->previous = $value === false ? null : $value;
    }

    protected function tearDown(): void
    {
        $this->previous === null ? putenv('DB_SERVER') : putenv('DB_SERVER=' . $this->previous);
        parent::tearDown();
    }

    #[DataProvider('flavors')]
    public function test_the_cte_depth_variable_follows_the_database_server(?string $server, string $variable): void
    {
        $server === null ? putenv('DB_SERVER') : putenv('DB_SERVER=' . $server);
        $config = require config_path('database.php');

        $this->assertSame($variable, $config['cte_recursion_variable']);

        if (extension_loaded('pdo_mysql')) {
            $init = $config['connections']['mysql']['options'][\Pdo\Mysql::ATTR_INIT_COMMAND];
            $this->assertStringStartsWith("SET SESSION {$variable} = ", $init);
        }
    }

    public static function flavors(): array
    {
        return [
            'unset keeps MySQL 8' => [null, 'cte_max_recursion_depth'],
            'mysql' => ['mysql', 'cte_max_recursion_depth'],
            'mariadb' => ['mariadb', 'max_recursive_iterations'],
            'case is ignored' => ['MariaDB', 'max_recursive_iterations'],
        ];
    }
}
