<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\SystemRole;

class System extends Model
{
    use HasFactory;

    protected $table = 'systems';

    protected $fillable = [
        "name",
        "code",
        "domain",
        "api_key",
        "key_deactivated_at",
        "status",
        "created_at",
        "updated_at",
        "deleted_at"
    ];
    
    public $timestamps = TRUE;

    public function systemRoles()
    {
        return $this->hasMany(SystemRole::class);
    }  

    public function generateApiKey()
    {
        $apiKey = Hash::make(Str::random(32));

        $this->api_key = $apiKey;

        return $apiKey;
    }
}
