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
        'description',
        'code',
        'action',
        'deactivated'
    ];

    public function systemRoles(){
        return $this->hasMany(SystemRolePermission::class);
    }
}
