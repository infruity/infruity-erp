<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $dates = ['deleted_at'];
    protected $primaryKey = 'id_user';

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roleAssignments()
    {
        return $this->hasMany('App\Models\RoleUser', 'id_user', 'id_user');
    }

    public function roleUser()
    {
        return $this->roleAssignments();
    }

    public static function getDataPetugasSurvey()
    {
        return self::join('role_user', 'users.id_user', '=', 'role_user.id_user')
            ->where('role_user.id_role', 9)
            ->where('bidang', '=', 'P2BMD')
            ->orderBy('users.id_user', 'DESC')
            ->get();
    }
}
