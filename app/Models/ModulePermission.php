<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModulePermission extends Model
{
    use HasFactory;

    protected $table = 'module_permissions';

    public $fillable = [
        'name',
        'system_id',
        'code',
        'description',
        'deactivated'
    ];

    public function modules()
    {
        return $this->belongsToMany(Module::class);
    }
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}
