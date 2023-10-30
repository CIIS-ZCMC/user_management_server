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
        'deactivated'
    ];

    public function module()
    {
        return $this->belongsTo(SystemModule::class);
    }
    
    public function permissions()
    {
        return $this->belongsTo(Permission::class);
    }

    public function roleModulePermission()
    {
        return $this->hasMany(RoleModulePermission::class);
    }
}
