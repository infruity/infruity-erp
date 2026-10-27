<?php
namespace Modules\Master\Entities;

use App\Models\User;
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

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function account()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }
}
