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
        'system_id',
        'description',
        'deactivated'
    ];

    public $timestamps = TRUE;

    public function system(){
        return $this->belongsTo(System::class);
    }

    public function permissions(){
        return $this->belongsToMany(Permission::class);
    }
}
