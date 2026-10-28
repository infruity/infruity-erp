<?php

namespace App\Support;

use App\Models\RoleMenu;
use Illuminate\Support\Facades\Auth;

final class RoleAssignmentGuard
{
    public static function authorize(array $requestedRoles, array $currentRoles, ?int $accountId = null): void
    {
        abort_if($accountId !== null && $accountId === (int) Auth::id(), 403, 'Hak akses akun sendiri tidak dapat diubah.');
        abort_unless(RoleMenu::checkAccess('staff.assign-roles'), 403, 'Anda tidak memiliki izin untuk menetapkan Hak Akses karyawan.');

        $roleIds = array_map('intval', array_merge($requestedRoles, $currentRoles));
        abort_if(
            RoleMenu::currentRoleId() !== 1 && in_array(1, $roleIds, true),
            403,
            'Role Administrator hanya dapat ditetapkan atau diubah oleh Administrator.'
        );
    }
}
