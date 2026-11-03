<?php

namespace Modules\Master\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Master\Entities\Branch;
use App\Models\RoleMenu;

class UserBranch extends Model
{
    use HasFactory;

    protected $table = 'user_branch';
    protected $fillable = [
        'user_id',
        'branch_id',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public static function getUserBranch()
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        if (RoleMenu::currentRoleId() === 1) {
            return Branch::query()
                ->withoutGlobalScope('userBranch')
                ->pluck('id')
                ->toArray();
        }

        return self::where('user_id', $user->id_user)
            ->pluck('branch_id')
            ->toArray();
    }
}
