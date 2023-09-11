<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\AccessToken;
use App\Models\EmployeeProfile;

use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'deactivated',
        'approved',
        'otp',
        'created_at',
        'updated_at',
        'deleted'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public $timestamps = TRUE;

    public function createToken()
    {
        // $publicKeyString
        AccessToken::where('user_id', $this->id)->delete();

        $token  = hash('sha256', Str::random(40));
        $token_exp = Carbon::now()->addHour();

        $accessToken = new AccessToken;
        $accessToken->user_id = $this->id;
        $accessToken->public_key = 'NONE';
        $accessToken->token = $token;
        $accessToken->token_exp = $token_exp;
        $accessToken->save();

        $encryptToken = $encryptedToken = openssl_encrypt($token, env("ENCRYPT_DECRYPT_ALGORITHM"), env("APP_KEY"), 0, substr(md5(env("APP_KEY")), 0, 16));

        return $encryptToken;
    }

    public function accessToken()
    {
        return $this->hasMany(AccessToken::class);
    }

    public function isAprroved()
    {
        return $this->approved !== null && $this->deactived===null;
    }

    public function isDeactivated()
    {
        return $this->deactivated === null;
    }

    public function isEmailVerified()
    {
        return $this->email_verified_at === null;
    }

    public function employeeProfile()
    {
        return $this->hasOne(EmployeeProfile::class);
    }
}
