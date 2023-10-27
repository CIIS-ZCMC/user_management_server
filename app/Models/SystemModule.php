<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemModule extends Model
{
    use HasFactory;

    protected $table = 'system_modules';

    public $fillable = [
        'name',
        'description',
        'deactivated',
        'system_id',
        'created_at',
        'updated_at'
    ];

    public $timestamps = TRUE;

    public function system(){
        return $this->belongsTo(System::class);
    }

    public function modulePermissions(){
        return $this->hasMany(ModulePermission::class);
    }
}
