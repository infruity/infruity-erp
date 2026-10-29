<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class RouteIntegrityTest extends TestCase
{
    public function test_uncalled_unsupported_routes_are_not_registered(): void
    {
        foreach ([
            ['GET', '/department/export', 'export-department-data'],
            ['GET', '/position/export', 'export-position-data'],
            ['POST', '/transfer/save-stock', 'transfer.save-stock'],
            ['GET', '/role-menu/123/edit', 'role-menu.edit'],
        ] as [$method, $uri, $name]) {
            $this->assertFalse(Route::has($name), $name.' must remain retired.');
            try {
                Route::getRoutes()->match(\Illuminate\Http\Request::create($uri, $method));
                $this->fail($method.' '.$uri.' must not match a registered route.');
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException | \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $exception) {
                // Resource update/delete routes can share the URI, but not this HTTP method.
                $this->assertContains($exception->getStatusCode(), [404, 405]);
            }
        }
    }

    public function test_existing_ui_edit_and_transfer_save_actions_remain_registered_and_authenticated(): void
    {
        foreach ([
            ['GET', '/department/123/edit', 'department.edit', \Modules\Master\Http\Controllers\DepartmentController::class.'@edit'],
            ['GET', '/position/123/edit', 'position.edit', \Modules\Master\Http\Controllers\PositionController::class.'@edit'],
            ['GET', '/roles/123/edit', 'roles.edit', \App\Http\Controllers\P_role::class.'@edit'],
            ['PUT', '/role-menu/123', 'role-menu.update', \App\Http\Controllers\P_rolemenu::class.'@update'],
            ['POST', '/transfer/save-transaction', 'transfer.save-transaction', \Modules\Transaction\Http\Controllers\TransferController::class.'@saveTransaction'],
            ['POST', '/transfer/save-correction', 'transfer.save-correction', \Modules\Transaction\Http\Controllers\TransferController::class.'@saveCorrection'],
        ] as [$method, $uri, $name, $action]) {
            $route = Route::getRoutes()->match(\Illuminate\Http\Request::create($uri, $method));
            $this->assertSame($name, $route->getName());
            $this->assertSame($action, $route->getActionName());
            $this->assertContains('auth', $route->gatherMiddleware());
        }
    }

    public function test_registered_controller_actions_have_public_concrete_target_methods(): void
    {
        $invalid = [];
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction('uses');
            if ($action instanceof Closure || $action === null) {
                continue;
            }

            $checked++;
            [$controller, $method] = array_pad(explode('@', $action, 2), 2, '__invoke');
            $label = implode('|', $route->methods()).' '.$route->uri().' -> '.$action;
            if (!class_exists($controller)) {
                $invalid[] = $label.' (controller missing)';
                continue;
            }

            // Reflection only: never instantiate a controller or run a business handler.
            $reflection = new ReflectionClass($controller);
            if (!$reflection->hasMethod($method)) {
                $invalid[] = $label.' (method missing)';
                continue;
            }

            $target = $reflection->getMethod($method);
            if (!$target->isPublic() || $target->isAbstract()) {
                $invalid[] = $label.' (method not public/concrete)';
            }
        }

        $this->assertGreaterThan(0, $checked, 'No controller routes were inspected.');
        $this->assertSame([], $invalid, $checked." controller routes inspected:\n".implode("\n", $invalid));
    }
}
