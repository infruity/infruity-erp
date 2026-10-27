<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\Role;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

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

    public static function checkAccess($permission)
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

        if (Session("role")["id_role"] == 1) {
            return true;
        } else {
            $data = self::where("id_role", Session("role")["id_role"])
                ->where("permission", $permission)->get()->first();
            if (isset($data)) {
                return true;
            } else {
                return false;
            }
        }
    }
}
