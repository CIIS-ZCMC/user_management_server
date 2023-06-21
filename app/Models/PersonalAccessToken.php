<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\System;
use App\Models\UserSystemRole;

class PersonalAccessToken extends Model
{
    use HasFactory;

    protected $fillable = [
        "accessToken",
        "abilities",
        "last_use_at",
        "expires_at",
        "deleted"
    ];
    
    public function revoke()
    {
        // Revoke the token by updating the "revoked" column
        $this->update(['revoked' => true]);

        // Return true to indicate the token was successfully revoked
        return true;
    }

    public function user()
    {
        return $this -> belongsToMany(User::class);
    }

    public function system()
    {
        return $this -> belongsTo(System::class);
    }

    public function token()
    {
        return $this -> belongsTo(UserSystemRole::class);
    }
}
