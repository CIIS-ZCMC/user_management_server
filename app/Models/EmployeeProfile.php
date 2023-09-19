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
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'employee_id',
        'employee_verified_at',
        'profile_url',
        'date_hired',
        'job_type',
        'department_id',
        'station_id',
        'employment_position_id',
        'job_position_id',
        'plantilla_id',
        'personal_information_id',
        'password_encrypted',
        'password_created_date',
        'password_expiration_date',
        'otp',
        'otp_expiration_date',
        'station_id',
        'approved',
        'deactivated',
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
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id', 'uuid');
    }

    public function employeeStation()
    {
        return $this->belongsTo(Station::class, 'station_id', 'uuid');
    }

    public function position()
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id', 'uuid');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'uuid');
    }

    public function createToken()
    {
        Log::channel('custom-info')->info('PASSED');
        // $publicKeyString
        AccessToken::where('employee_profile_id', $this->uuid)->delete();


        $token  = hash('sha256', Str::random(40));
        $token_exp = Carbon::now()->addHour();

        $accessToken = AccessToken::create([
            'uuid' => Str::uuid(),
            'employee_profile_id' => $this->uuid,
            'public_key' => 'NONE',
            'token' => $token,
            'token_exp' => $token_exp
        ]);

        $encryptedToken = openssl_encrypt($token, env("ENCRYPT_DECRYPT_ALGORITHM"), env("APP_KEY"), 0, substr(md5(env("APP_KEY")), 0, 16));

        return $encryptedToken;
    }

    public function accessToken()
    {
        return $this->hasMany(AccessToken::class, 'uuid', 'employee_profile_id');
    }

    public function loginTrails()
    {
        return $this->hasMany(LoginTrails::class, 'uuid', 'employee_profile_id');
    }

    public function isAprroved()
    {
        return $this->approved !== null;
    }

    public function isDeactivated()
    {
        return $this->deactivated === null;
    }

    public function isEmailVerified()
    {
        return $this->email_verified_at === null;
    }
}
