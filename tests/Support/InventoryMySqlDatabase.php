<?php

namespace Tests\Support;

use PDO;
use RuntimeException;

/** Explicit opt-in target; never reads the application's DB_* credentials. */
final class InventoryMySqlDatabase
{
    public static function connection(): array
    {
        $socket = getenv('INFRUITY_TEST_MYSQL_SOCKET') ?: '';
        $host = getenv('INFRUITY_TEST_MYSQL_HOST') ?: '';
        if ($socket === '' && $host === '') {
            throw new RuntimeException('Set an explicit isolated INFRUITY_TEST_MYSQL_SOCKET or HOST.');
        }
        return [
            'driver' => 'mysql', 'unix_socket' => $socket, 'host' => $host,
            'port' => getenv('INFRUITY_TEST_MYSQL_PORT') ?: '3306',
            'username' => getenv('INFRUITY_TEST_MYSQL_USERNAME') ?: 'root',
            'password' => getenv('INFRUITY_TEST_MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '', 'strict' => true,
        ];
    }

    public static function administrator(array $connection): PDO
    {
        $target = $connection['unix_socket'] !== ''
            ? 'unix_socket='.$connection['unix_socket']
            : 'host='.$connection['host'].';port='.$connection['port'];
        return new PDO('mysql:'.$target.';charset=utf8mb4', $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
}
