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
    ];

    protected $timestamps = TRUE;


    public function system()
    {
        return $this->belongsTo(SystemRole::class);
    }

    public function hasPermission($permission)
    {
        list($action, $module) = explode(' ', $permission);

        return $this->action === $action && $this->module===$module;
    }
}
