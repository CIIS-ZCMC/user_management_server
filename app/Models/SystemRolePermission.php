<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemRolePermission extends Model
{
    use HasFactory;

    protected $table = 'system_role_permissions';

    public $fillable = [
        'action',
        'module',
        'active',
        'system_role_id'
    ];

    public $timestamps = TRUE;


    public function systemRole()
    {
        return $this->belongsTo(SystemRole::class);
    }

    public function permissions(){
        return $this->hasMany(Permission::class);
    }
 
    public function validate($routePermission)
    {
        list($action, $module) = explode(' ', $routePermission);

        return $this->action === $action && $this->module===$module;
    }

    public function positionSystemRole()
    {
        return $this->hasMany(PositionSystemRole::class);
    }
}
