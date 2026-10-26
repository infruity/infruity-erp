<?php

namespace App\Support;

use App\Models\RoleMenu;
use Illuminate\Http\Request;

/**
 * Resolves what the floating mobile dock's centre action does on the current
 * screen: open the shared create modal, navigate to a create page, or fall back
 * to opening the navigation drawer.
 *
 * Kept out of the Blade component so the route/permission matrix is testable
 * and the markup only deals with presentation.
 */
class ErpMobileAction
{
    /** List routes whose create action is a page, with the route it points to. */
    private const CREATE_PAGES = [
        [['product-stock', 'product-stock.*'], 'products.create', 'products.create'],
        [['sortir.index'], 'sortir.create', 'sortir.create'],
        [['transfer-pengirim.index'], 'transfer-pengirim.create', 'transfer-pengirim.create'],
        [['transfer-penerima.index'], 'transfer-pengirim.create', 'transfer-pengirim.create'],
        [['stock-opname.index'], null, 'stock-opname.create'],
        [['production.index'], 'production.create', 'production.create'],
        [['pos.index'], 'pos.create', 'pos.create'],
        [['order-book.index'], 'order-book.create', 'order-book.create'],
        [['expenditure.index'], 'expenditure.create', 'expenditure.create'],
        [['wholesale.index'], 'wholesale.create', 'wholesale.create'],
    ];

    /** List routes whose create action lives in a shared modal on the page. */
    private const MODAL_CREATE = [
        'stock-opname.index' => 'stock-opname.create',
        'campaign.index' => 'campaign.create',
        'tier.index' => 'tier.create',
        'deposito.index' => 'deposito.create',
        'stock-out.index' => 'stock-out.create',
        'stock-out-type.index' => 'stock-out-type.create',
        'location.index' => 'location.create',
        'handling.index' => 'handling.create',
        'department.index' => 'department.create',
    ];

    /** List routes that can open their create form from the dock. */
    private const LIST_CREATE_ROUTES = [
        'receipt.index',
        'roles.index',
        'unit.*',
        'category.*',
        'supplier.*',
        'customers.*',
        'branch.*',
        'user.*',
        'position.*',
        'staff.*',
        'kurir.*',
        'payment-method.*',
    ];

    /**
     * @return array{url: ?string, canCreate: bool, opensModal: bool, showPosShortcut: bool}
     */
    public static function resolve(Request $request): array
    {
        $url = self::createPageUrl($request);
        $opensModal = self::opensCreateModal($request);
        $canCreate = (self::supportsListCreate($request) && self::canCreateFromList($request)) || $opensModal;

        return [
            'url' => $url,
            'canCreate' => $canCreate,
            'opensModal' => $opensModal,
            'showPosShortcut' => check_access('pos.create'),
        ];
    }

    /** URL of the create page for the current list route, when allowed. */
    private static function createPageUrl(Request $request): ?string
    {
        foreach (self::CREATE_PAGES as [$patterns, $route, $permission]) {
            if (! $request->routeIs($patterns)) {
                continue;
            }

            return $route && check_access($permission) ? route($route) : null;
        }

        return null;
    }

    private static function opensCreateModal(Request $request): bool
    {
        $name = $request->route()?->getName();

        return $name !== null
            && isset(self::MODAL_CREATE[$name])
            && check_access(self::MODAL_CREATE[$name]);
    }

    private static function supportsListCreate(Request $request): bool
    {
        return $request->routeIs(self::LIST_CREATE_ROUTES) || $request->is('products');
    }

    private static function canCreateFromList(Request $request): bool
    {
        if ($request->routeIs('receipt.index')) {
            return check_access('product-receipt.create');
        }

        if ($request->routeIs('roles.index')) {
            return check_access('role.store');
        }

        if (self::supportsListCreate($request) && ! $request->routeIs('products.index')) {
            $name = $request->route()?->getName();

            return $name !== null && RoleMenu::checkAccess(explode('.', $name)[0] . '.create');
        }

        return check_access('products.create');
    }
}
