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
        "uuid",
        "name",
        "domain",
        "code",
        "server-maintainance",
        "server-down",
        "server-active",
        "created_at",
        "updated_at"
    ];
    
    public $timestamps = TRUE;

    public function systemRoles()
    {
        return $this->hasMany(SystemRole::class);
    }
}
