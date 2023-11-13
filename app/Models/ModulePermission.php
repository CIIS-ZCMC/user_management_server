<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModulePermission extends Model
{
    use HasFactory;

    protected $table = 'module_permissions';

    public $fillable = [
        'system_module_id',
        'permission_id',
        'code',
        'role_module_permission_id',
        'active'
    ];

    public function module()
    {
        return $this->belongsTo(SystemModule::class,'system_module_id' ,'id');
    }
    
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function roleModulePermission()
    {
        return $this->hasMany(RoleModulePermission::class);
    }
}
