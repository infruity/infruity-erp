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

    public static function checkAccess($permission): bool
    {
        $user = Auth::user();
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
