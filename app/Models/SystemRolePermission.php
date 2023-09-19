<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemRolePermission extends Model
{
    use HasFactory;

    protected $table = 'system_role_permissions';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'action',
        'module',
        'active',
        'system_role_id'
    ];

    public $timestamps = TRUE;


    public function systemRole()
    {
        return $this->belongsTo(SystemRole::class, 'uuid');
    }
 
    public function validate($routePermission)
    {
        list($action, $module) = explode(' ', $routePermission);

        return $this->action === $action && $this->module===$module;
    }

    public function positionSystemRole()
    {
        return $this->hasMany(PositionSystemRole::class, 'uuid', 'system_role_id');
    }
}
