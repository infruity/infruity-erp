<?php
namespace Modules\Master\Entities;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Master\Entities\Department;
use Modules\Master\Entities\Position;

class Staff extends Model
{
    use HasFactory;

    protected $table    = 'staff';
    protected $fillable = ['image'];

    protected static function newFactory()
    {
        return \Modules\Master\Database\factories\StaffFactory::new ();
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }

    /** Every position the employee holds (staff.position_id remains the first one). */
    public function positions()
    {
        return $this->belongsToMany(Position::class, 'staff_position', 'staff_id', 'position_id')->orderBy('position.name');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function pendingRoles()
    {
        return $this->belongsToMany(Role::class, 'staff_role', 'staff_id', 'id_role', 'id', 'id_role');
    }

    public function account()
    {
        return $this->hasOne(\App\User::class, 'staff_id', 'id')->withoutGlobalScope('is_aktif');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }
}
