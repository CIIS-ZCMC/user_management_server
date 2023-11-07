<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployeeProfile extends Authenticatable
{
    use HasFactory;

    protected $table = 'employee_profiles';

    public $fillable = [
        'personal_information_id',
        'employee_verified_at',
        'employee_id',
        'profile_url',
        'date_hired',
        'password_encrypted',
        'password_created_at',
        'password_expiration_at',
        'biometric_id',
        'otp',
        'otp_expiration',
        'deactivated_at',
        'agency_employee_no',
        'allow_time_adjustment',
        'employee_type_id'
    ];

    public $timestamps = TRUE;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_encrypted',
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

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }

    public function assignedArea()
    {
        return $this->belongsTo(AssignedArea::class);
    }

    public function assignedAreaTrail()
    {
        return $this->belongsTo(AssignedAreaTrail::class);
    }

    public function accessToken()
    {
        return $this->hasMany(AccessToken::class);
    }

    public function specialAccessRole(){
        return $this->hasMany(SpecialAccessRole::class);
    }

    public function loginTrails()
    {
        return $this->hasMany(LoginTrails::class);
    }

    public function isDeactivated()
    {
        return $this->deactivated_at === null;
    }

    public function isEmailVerified()
    {
        return $this->email_verified_at === null;
    }
    
    public function createToken()
    {
        Log::channel('custom-info')->info('PASSED');
        // $publicKeyString
        AccessToken::where('employee_profile_id', $this->uuid)->delete();


        $token  = hash('sha256', Str::random(40));
        $token_exp = Carbon::now()->addHour();

        $accessToken = AccessToken::create([
            'employee_profile_id' => $this->uuid,
            'public_key' => 'NONE',
            'token' => $token,
            'token_exp' => $token_exp
        ]);

        $encryptedToken = openssl_encrypt($token, env("ENCRYPT_DECRYPT_ALGORITHM"), env("APP_KEY"), 0, substr(md5(env("APP_KEY")), 0, 16));

        return $encryptedToken;
    }

    public function name()
    {
        $personal_information = $this->personalInformation;
        $fullName = $personal_information['first_name'].' '.$personal_information['last_name'];

        return $fullName;
    }
}
