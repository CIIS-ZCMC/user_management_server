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
        'active'
    ];
    
    public function modulePermission(){
        return $this->hasMany(ModulePermission::class);
    }
}
