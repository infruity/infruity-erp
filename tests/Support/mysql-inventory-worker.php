<?php

// CLI-only worker: two independent HTTP kernels/connections share a test schema.
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Support\InventoryMySqlDatabase;

$root = dirname(__DIR__, 2);
$loader = require $root.'/vendor/autoload.php';
$sourceRoot = $root;
$loader->addClassMap([
    Modules\Transaction\Http\Controllers\ProductionController::class => $sourceRoot.'/Modules/Transaction/Http/Controllers/ProductionController.php',
    Modules\Transaction\Http\Controllers\ProductionParcelController::class => $sourceRoot.'/Modules/Transaction/Http/Controllers/ProductionParcelController.php',
]);
$database = getenv('INFRUITY_TEST_MYSQL_DATABASE') ?: '';
if (!preg_match('/\\Ainfruity_test_inventory_[a-f0-9]{16}\\z/D', $database)) {
    throw new RuntimeException('Only a generated infruity_test_inventory_<16 hex> schema is allowed.');
}
[$script, $directory, $id, $mode] = $argv;
$app = require $root.'/bootstrap/app.php';
$app->instance('request', Request::create('/'));
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
config([
    'database.default' => 'inventory_mysql_worker',
    'database.connections.inventory_mysql_worker' => InventoryMySqlDatabase::connection() + ['database' => $database],
    'cache.default' => 'array', 'session.driver' => 'array', 'mail.default' => 'array',
]);
Auth::guard()->setUser(App\User::findOrFail(7 + (int) $id));
$peer = $id === '0' ? '1' : '0';
$times = [];
$locked = false;
$parentMapIsLocking = false;
$wait = static function (string $file): void {
    $deadline = microtime(true) + 10;
    while (!is_file($file)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Concurrency barrier timed out: '.basename($file));
        }
        usleep(10000);
    }
};
$table = $mode === 'parcel' ? 'production_parcel' : 'products';
$target = static fn (string $sql): bool => (bool) preg_match('/\bfrom\s+[`"]?'.preg_quote($table, '/').'[`"]?(?:\s|$)/i', $sql);
DB::connection()->beforeExecuting(function ($sql) use (&$times, $directory, $id, $mode, &$parentMapIsLocking) {
    if ($mode !== 'parcel' && str_contains(strtolower($sql), 'product_child')) {
        $times['parent_map_attempt'] ??= microtime(true);
        touch($directory.'/'.$id.'.parent-map-attempt');
        $parentMapIsLocking = str_contains(strtolower($sql), 'for update');
    }
});
DB::connection()->beforeExecuting(function ($sql) use ($target, &$times, $directory, $id) {
    if ($target($sql) && str_contains(strtolower($sql), 'for update')) {
        $times['attempt'] ??= microtime(true);
        touch($directory.'/'.$id.'.attempt');
    }
});
DB::listen(function (QueryExecuted $event) use ($target, $table, $mode, &$parentMapIsLocking, &$locked, &$times, $wait, $directory, $id, $peer) {
    if ($mode !== 'parcel' && str_contains(strtolower($event->sql), 'product_child')) {
        if ($parentMapIsLocking) {
            $times['parent_map_locked'] ??= microtime(true);
            touch($directory.'/'.$id.'.parent-map-locked');
            $wait($directory.'/'.$peer.'.parent-map-attempt');
        } elseif (!isset($times['parent_map_read'])) {
            $times['parent_map_read'] = microtime(true);
            touch($directory.'/'.$id.'.parent-map-read');
            $wait($directory.'/'.$peer.'.parent-map-read');
        }
    }
    if ($target($event->sql) && str_contains(strtolower($event->sql), 'for update') && !$locked) {
        $locked = true;
        $times['locked'] = microtime(true);
        if (!$parentMapIsLocking) {
            $wait($directory.'/'.$peer.'.attempt');
        }
    }
    $unsafeRead = $table === 'products'
        ? (bool) preg_match('/\bfrom\s+[`"]?product_stock[`"]?(?:\s|$)/i', $event->sql)
        : $target($event->sql);
    if ($unsafeRead && !$locked && !isset($times['unlocked_read'])) {
        $times['unlocked_read'] = microtime(true);
        touch($directory.'/'.$id.'.read');
        $wait($directory.'/'.$peer.'.read');
    }
});
touch($directory.'/'.$id.'.ready');
$wait($directory.'/go');
$ingredientId = $mode === 'production-variant' && $id === '1' ? 13 : 10;
$payload = $mode === 'parcel' ? ['hpp' => 20] : [
    'id' => 101 + (int) $id,
    'product_id' => 11 + (int) $id, 'production_number' => 'PRO20261010'.$id,
    'submit_type' => 'posting', 'production_date' => '2026-10-29',
    'quantity' => 1, 'branch_id' => 1, 'staff_id' => null,
    'sell_price' => '12500', 'service_cost' => '0', 'notes' => 'Concurrency fixture',
    'ingredients' => [['id' => $ingredientId, 'quantity' => 4, 'hpp' => 3]],
];
$request = Request::create($mode === 'parcel' ? '/parcel/set-selesai/50' : '/production', 'POST', $payload);
$request->headers->set('Accept', 'application/json');
$response = $kernel->handle($request);
$times['finished'] = microtime(true);
file_put_contents($directory.'/'.$id.'.result.json', json_encode([
    'status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true), 'times' => $times,
    'controller' => (new ReflectionClass($mode === 'parcel' ? Modules\Transaction\Http\Controllers\ProductionParcelController::class : Modules\Transaction\Http\Controllers\ProductionController::class))->getFileName(),
], JSON_THROW_ON_ERROR));
$kernel->terminate($request, $response);
