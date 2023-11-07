<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleModulePermissions extends Model
{
    use HasFactory;

    protected $table = 'role_module_permissions';

    public $fillable = [
        'module_permission_id',
        'system_role_id'
    ];

    public $timestamps = TRUE;

    public function modulePermission(){
        return $this->belongsTo(ModulePermission::class);
    }

    public function systemRole(){
        return $this->belongsToMany(SystemRole::class);
    }
}
