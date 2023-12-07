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
        "role_id",
        "system_id",
        "effective_at"
    ];

    public $timestamps = TRUE;

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    // public function roleModulePermission()
    // {
    //     return $this->hasOneThrough(
    //         RoleModulePermission::class,
    //         ModulePermission::class,
    //         'module_permission_id',  
    //         'id',                
    //         'id',                    
    //         'system_role_id',       
    //     );
    // }

    public function roleModulePermissions()
    {
        return $this->hasMany(RoleModulePermission::class);
    }

    public function positionSystemRole()
    {
        return $this->hasManyThrough(PositionSystemRole::class, Designation::class);
    }
}
    