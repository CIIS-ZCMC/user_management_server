<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserSystemRole;

class SystemRole extends Model
{
    use HasFactory;

    protected $table = 'system_roles';

    protected $fillable = [
        "name",
        "description",
        "updated_at",
        "system_id"
    ];

    public $timestamps = TRUE;

    public function system()
    {
        return $this->belongsTo(System::class, 'uuid');
    }

    public function permissions()
    {
        return $this->hasMany(SystemRolePermission::class, 'system_role_id');
    }

    public function hasPermission($routePermission)
    {
        $permissions = $this->permissions;

        return $permissions->contains(function ($permission) {
            return $permission->validate($routePermission);
        });
    }
}
    