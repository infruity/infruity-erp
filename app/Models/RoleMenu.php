<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\Role;
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

    public static function currentRoleId(): ?int
    {
        $roleId = data_get(Session("role"), "id_role");

        return $roleId === null ? null : (int) $roleId;
    }

    public static function checkAccess($permission)
    {
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
