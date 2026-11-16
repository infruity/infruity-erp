<?php

namespace App\Traits;

use App\Models\RoleMenu;

trait HasAccessControl
{
    /**
     * Check access and abort if not authorized
     * Use in controller methods for clean access control
     *
     * @param string $permission Permission name (e.g., 'do.index', 'do.create')
     * @param string|null $redirectTo Custom redirect URL (default: back or 'do')
     * @return \Illuminate\Http\RedirectResponse|null Returns redirect if no access, null if authorized
     */
    protected function checkAccess(string $permission, ?string $redirectTo = null)
    {
        if (RoleMenu::checkAccess($permission)) {
            return null;
        }

        $redirect = $redirectTo ?? $this->safeDeniedRedirect();

        if ($redirect === null) {
            abort(403, "Anda tidak memiliki izin untuk mengakses halaman {$permission}.");
        }

        return redirect($redirect)
            ->with('error', "Anda tidak memiliki izin untuk mengakses halaman {$permission}. Hubungi Admin untuk Aktivasi Kembali");
    }

    /**
     * Where a denied visitor goes. The page they came from, unless that page (or this one) sends signed-in users straight
     * back here (login, "/", the dashboard): then every denial bounced forever, e.g. an account without the dashboard
     * permission could never open the app. Those visitors go to the first page they may open instead; null = none at all.
     */
    protected function safeDeniedRedirect(): ?string
    {
        $previous = url()->previous();
        $path = trim((string) parse_url($previous, PHP_URL_PATH), '/');
        $loops = $previous === '' || $previous === url()->current() || in_array($path, ['', 'login', 'dashboard'], true);

        return $loops ? $this->firstAccessibleUrl() : $previous;
    }

    protected function firstAccessibleUrl(): ?string
    {
        foreach (['production.index', 'pos.index', 'wholesale.index', 'products.index', 'transfer-pengirim.index', 'sortir.index', 'stock-opname.index', 'order-book.index', 'delivery-order.index', 'settings.index'] as $name) {
            if (\Illuminate\Support\Facades\Route::has($name) && RoleMenu::checkAccess($name)) {
                return route($name);
            }
        }

        return null;
    }

    /**
     * Check access and return boolean
     *
     * @param string $permission
     * @return bool
     */
    protected function hasAccess(string $permission): bool
    {
        return (bool) RoleMenu::checkAccess($permission);
    }

    /**
     * Require access - returns redirect response if not authorized
     * Usage: if ($denied = $this->requireAccess('do.index')) return $denied;
     *
     * @param string $permission
     * @param string|null $redirectTo
     * @return \Illuminate\Http\RedirectResponse|null
     */
    protected function requireAccess(string $permission, ?string $redirectTo = null)
    {
        return $this->checkAccess($permission, $redirectTo);
    }

    /**
     * Abort with 403 if no access (for API or strict access control)
     *
     * @param string $permission
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeAccess(string $permission): void
    {
        if (!RoleMenu::checkAccess($permission)) {
            abort(403, "Anda tidak memiliki izin untuk mengakses {$permission}");
        }
    }
}
