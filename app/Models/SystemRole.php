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
        "code",
        "effective_at",
        "system_id"
    ];

    public $timestamps = TRUE;

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function roleModulePermission()
    {
        return $this->hasManyThrough(RoleModulePermission::class, ModulePermission::class);
    }

    public function roleModulePermissions()
    {
        return $this->hasMany(RoleModulePermission::class);
    }

    public function positionSystemRole()
    {
        return $this->hasManyThrough(PositionSystemRole::class, Designation::class);
    }
}
    