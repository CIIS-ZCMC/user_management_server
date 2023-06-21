<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\UserSystemRole;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'deactivated',
        'status',
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

    
    public function createToken($abilities)
    {
        $userID = $this -> id;
        $accessToken  = hash('sha256', Str::random(40));
        $expiration = Carbon::now()->addHour();

        $token = new PersonalAccessToken;
        $token -> FK_user_ID = $id;
        $token -> accessToken = $accessToken;
        $token -> abilities = $abilities;
        $token -> last_use_at = now();
        $token -> expires_at = $expiration;
        $token -> save();

        $encryptToken = $encryptedToken = openssl_encrypt($accessToken, env("ENCRYPT_DECRYPT_ALGORITHM"), env("KEY"), 0, substr(md5(env("KEY")), 0, 16));

        return $encryptToken;
    }

    public function getSystemRole($request)
    {
        $domain = $request -> getHost();
        $userID = $this -> id;

        $abilities = DB::table('user_system_role as usr')
                            -> select('sr.abilities') 
                            -> join('system as s', 's.domain', $domain)
                            -> join('system_role as sr', 'sr.id', 's.id')
                            -> where('usr.FK_system_role_ID', 'sr.id')
                            -> where('usr.FK_user_ID', $userID)
                            -> first();
        
        return $abilities;
    }

    public function can($request, $abilities)
    {
        $domain = $request -> getHost();

        $systemAbilities = $this -> getAbilities($domain);

        if(!$systemAbilities)
        {
            return false;
        }
        
        return $this -> validateAbilities($abilities, $systemAbilities);
    }

    public function getAbilities($domain)
    {
        $userID = $this -> id;

        /**
         * Get Abilities of User base on domain
         */
        $systemRole = DB::table('user_system_role as usr')
            -> select('sr.abilities')
            -> join('system_role as sr', 'sr.id', 'usr.FK_system_role_ID')
            -> where('usr.FK_system_ID', 'sr.FK_system_ID')
            -> where('usr.FK_role_ID', 'sr.FK_role_ID')
            -> where('usr.FK_user_ID', $id) -> get();

        $abilities = json_decode($systemRole['abilities']);

        return $abilities;
    }

    public function hasAccess($request)
    {
        $domain = $request -> getHost();

        /**
         * Get Abilities of User base on domain
         */
        $systemRole = DB::table('user_system_role as usr')
            -> select('s.id')
            -> join('system_role as sr', 'sr.id', 'usr.FK_system_role_ID')
            -> join('system as s', 's.id', 'usr.FK_system_ID')
            -> where('usr.FK_system_ID', 'sr.FK_system_ID')
            -> where('usr.FK_role_ID', 'sr.FK_role_ID')
            -> where('usr.FK_user_ID', $id) -> get();


        return !$systemRole;
    }

    public function validateAbilities($abilities, $systemAbilities)
    {
        
        /**
         * Validate User Abilities
         */
        foreach ($abilities as $ability) {
            foreach ($systemAbilities as $systemAbility) {
                if ($ability === $systemAbility) {
                    return true;
                }
            }
        }

        return false;
    }

    public function profile()
    {
        return $this -> hasOne(Profile::class);
    }

    public function transactions()
    {
        return $this -> belongsToMany(Transaction::class);
    }

    public function userSystemRoles()
    {
        return $this -> belongsToMany(UserSystemRole::class);
    }
}
