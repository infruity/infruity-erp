<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\UserPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RoleMenu extends Model
{
    protected $table = 'role_menu';
    protected $primaryKey = 'id_rm';
    protected $fillable = ['id_role', 'permission'];

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function permissionDetail()
    {
        return $this->belongsTo(Permission::class, 'permission', 'name');
    }

    public static function currentRole(): ?Role
    {
        if (! Auth::check()) {
            return null;
        }

        $selectedRoleId = session('role.id_role');

        // Called for every access check on a page; resolve it once per request.
        $attributes = request()->attributes;
        $cacheKey = 'current_role.' . Auth::id() . '.' . (is_numeric($selectedRoleId) ? (int) $selectedRoleId : '-');
        if ($attributes->has($cacheKey)) {
            return $attributes->get($cacheKey);
        }

        $assignments = RoleUser::with('role')->where('id_user', Auth::id());
        $role = null;

        if (is_numeric($selectedRoleId)) {
            $role = (clone $assignments)
                ->where('id_role', (int) $selectedRoleId)
                ->first()?->role;
        }

        $role ??= $assignments->orderBy('id_ru')->first()?->role;
        $attributes->set($cacheKey, $role);

        return $role;
    }

    public static function currentRoleId(): ?int
    {
        return self::currentRole()?->id_role;
    }

    /** The only account that may delete data; everybody else is view/add/edit only. */
    public const MASTER_EMAIL = 'master@infruity.com';

    public static function isMasterAccount(): bool
    {
        $email = Auth::user()?->email;

        return is_string($email) && mb_strtolower(trim($email)) === self::MASTER_EMAIL;
    }

    public const ADMINISTRATOR_ROLE_ID = 1;

    /** Whether the signed-in user is acting as an Administrator (or is the master account). */
    public static function actingAsAdministrator(): bool
    {
        return self::isMasterAccount() || (int) self::currentRoleId() === self::ADMINISTRATOR_ROLE_ID;
    }

    /** An account only an Administrator may change: one holding the Administrator role, or the master account. */
    public static function isPrivilegedAccount(int $userId): bool
    {
        if (RoleUser::where('id_user', $userId)->where('id_role', self::ADMINISTRATOR_ROLE_ID)->exists()) {
            return true;
        }
        $email = \Illuminate\Support\Facades\DB::table('users')->where('id_user', $userId)->value('email');

        return is_string($email) && mb_strtolower(trim($email)) === self::MASTER_EMAIL;
    }

    /** "x.destroy", "x.delete", "x.delete_product", "x.delete-ajax", "x.destroy-variant" ... */
    public static function isDeletePermission(string $permission): bool
    {
        return (bool) preg_match('/\.(destroy|delete)([-_][a-z0-9_-]+)?$/i', trim($permission));
    }

    public static function checkAccess($permission): bool
    {
        $user = Auth::user();
        // Deleting is reserved for the master account, whatever the role or a personal override says.
        if ($user && self::isDeletePermission((string) $permission) && ! self::isMasterAccount()) {
            return false;
        }
        if ($user) {
            $request = request();
            $cacheKey = 'user_permission_overrides.' . $user->getAuthIdentifier();
            if (! $request->attributes->has($cacheKey)) {
                $overrides = UserPermission::where('id_user', $user->getAuthIdentifier())
                    ->pluck('is_allowed', 'permission')
                    ->map(fn ($allowed) => (bool) $allowed)
                    ->all();
                $request->attributes->set($cacheKey, $overrides);
            }

            $overrides = $request->attributes->get($cacheKey, []);
            if (array_key_exists($permission, $overrides)) {
                return (bool) $overrides[$permission];
            }
        }

        $roleId = self::currentRoleId();

        if ($roleId === 1) {
            return true;
        }

        if ($roleId === null) {
            return false;
        }

        return isset(self::rolePermissions($roleId)[self::permissionKey((string) $permission)]);
    }

    /**
     * All permissions of a role, loaded once per request instead of one query per check.
     */
    protected static function rolePermissions(int $roleId): array
    {
        $attributes = request()->attributes;
        $cacheKey = 'role_permissions.' . $roleId;

        if (! $attributes->has($cacheKey)) {
            $attributes->set($cacheKey, self::where('id_role', $roleId)
                ->pluck('permission')
                ->mapWithKeys(fn ($name) => [self::permissionKey((string) $name) => true])
                ->all());
        }

        return $attributes->get($cacheKey);
    }

    // MySQL compared permissions case-insensitively and ignoring trailing spaces; keep that.
    protected static function permissionKey(string $permission): string
    {
        return mb_strtolower(rtrim($permission));
    }
}
