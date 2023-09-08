<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserSystemRole;

class SystemRole extends Model
{
    use HasFactory;

    protected $table = 'systemroles';

    protected $fillable = [
        "abilities",
        "created_at",
        "updated_at",
        "deleted"
    ];

    public function userSystemRole()
    {
        return $this -> belongsToMany(userSystemRole::class);
    }
}
    