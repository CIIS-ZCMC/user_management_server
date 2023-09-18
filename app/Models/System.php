<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SystemRole;

class System extends Model
{
    use HasFactory;

    protected $table = 'systems';

    protected $fillable = [
        "name",
        "domain",
        "server-maintainance",
        "server-down",
        "server-active",
        "created_at",
        "updated_at",
        "deleted"
    ];
    
    public $timestamps = TRUE;

    public function systemRoles()
    {
        return $this->hasMany(SystemRole::class, 'system_id');
    }
}
