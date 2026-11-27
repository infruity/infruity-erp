<?php

namespace App\Support;

use App\Models\RoleMenu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Master\Entities\UserBranch;

/**
 * What someone who is not an Administrator may hand out or take over when managing staff and accounts: nothing they do
 * not hold themselves. Otherwise "manage accounts" is every permission: grant it to a second account, or reset the
 * password of an account that has it.
 */
class Delegation
{
    /** The permissions among these that the signed-in user does not hold. Deleting is the master's alone. */
    public static function beyondActor(array $permissions): array
    {
        return array_values(array_filter(array_unique($permissions), fn ($permission) => ! RoleMenu::isDeletePermission((string) $permission)
            && ! RoleMenu::checkAccess($permission)));
    }

    public static function permissionsOfRoles(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        return DB::table('role_menu')->whereIn('id_role', $roleIds)->pluck('permission')->map(fn ($name) => (string) $name)->all();
    }

    /** What an account can do: its roles, with its personal overrides applied. */
    public static function permissionsOfAccount(int $userId): array
    {
        $fromRoles = self::permissionsOfRoles(DB::table('role_user')->where('id_user', $userId)->pluck('id_role')->all());
        $overrides = DB::table('user_permissions')->where('id_user', $userId)->pluck('is_allowed', 'permission')->all();

        return array_values(array_unique(array_merge(
            array_filter($fromRoles, fn ($name) => ! array_key_exists($name, $overrides) || $overrides[$name]),
            array_keys(array_filter($overrides))
        )));
    }

    /** Branches among these that the signed-in user does not work in. */
    public static function branchesBeyondActor(array $branchIds): array
    {
        return array_values(array_diff(
            array_map('intval', array_filter($branchIds)),
            array_map('intval', UserBranch::getUserBranch())
        ));
    }

    /** Whether another account can do, or see, more than the signed-in user. */
    public static function accountExceedsActor(int $userId): bool
    {
        if ($userId === (int) Auth::id()) {
            return false;
        }

        return self::beyondActor(self::permissionsOfAccount($userId)) !== []
            || self::branchesBeyondActor(DB::table('user_branch')->where('user_id', $userId)->pluck('branch_id')->all()) !== [];
    }
}
