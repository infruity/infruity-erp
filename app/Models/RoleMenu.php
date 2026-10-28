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

        $assignments = RoleUser::with('role')->where('id_user', Auth::id());
        $selectedRoleId = session('role.id_role');

        if (is_numeric($selectedRoleId)) {
            $selectedRole = (clone $assignments)
                ->where('id_role', (int) $selectedRoleId)
                ->first()?->role;

            if ($selectedRole) {
                return $selectedRole;
            }
        }

        return $assignments->orderBy('id_ru')->first()?->role;
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

        return $roleId === 1 || ($roleId !== null && self::where('id_role', $roleId)
            ->where('permission', $permission)
            ->exists());
    }
}
