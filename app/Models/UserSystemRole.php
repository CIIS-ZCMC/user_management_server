<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\SystemRole;
use App\Models\PersonalAccessToken;

class UserSystemRole extends Model
{
    use HasFactory;

    protected $fillable = [
        "created_at",
        "updated_at",
        "deleted"
    ];

    public function detach()
    {
        // Revoke the token by updating the "revoked" column
        $this->update(['FK_token_ID' => NULL]);

        // Perform any additional revocation logic for related tables
        $this->user()->detach(); // Example: Detach the token from the user relation

        // Return true to indicate the token was successfully revoked
        return true;
    }

    public function user()
    {
        return $this -> belongsTo(User::class);
    }

    public function systemRole()
    {
        return $this -> belongsTo(SystemRole::class);
    }

    public function token()
    {
        return $this -> hasOne(PersonalAccessToken::class);
    }
}
