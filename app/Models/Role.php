<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SystemRole;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "created_at",
        "updated_at",
        "deleted"
    ];

    public function systemRole()
    {
        return $this -> belongsToMany(SystemRole::class);
    }
}
