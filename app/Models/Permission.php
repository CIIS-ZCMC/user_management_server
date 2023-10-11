<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    public $fillable = [
        'name',
        'action',
        'deactivated'
    ];

    public function systemRoles(){
        return $this->hasMany(SystemRolePermission::class);
    }
    
    public function module(){
        return $this->belongsToMany(Module::class);
    }
}
