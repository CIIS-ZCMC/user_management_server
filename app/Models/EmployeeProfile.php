<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\Schedule;

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
        'is_2fa',
        'employee_type_id',
        'employment_type_id'
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
        return $this->hasOne(AssignArea::class);
    }

    public function assignedAreaTrail()
    {
        return $this->belongsTo(AssignAreaTrail::class);
    }

    public function accessToken()
    {
        return $this->hasMany(AccessToken::class);
    }

    public function specialAccessRole()
    {
        return $this->hasMany(SpecialAccessRole::class);
    }

    public function loginTrails()
    {
        return $this->hasMany(LoginTrail::class);
    }

    public function isDeactivated()
    {
        return $this->deactivated_at === null;
    }

    public function isEmailVerified()
    {
        return $this->email_verified_at === null;
    }

    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function createToken()
    {
        Log::channel('custom-info')->info('PASSED');
        // $publicKeyString
        AccessToken::where('employee_profile_id', $this->id)->delete();


        $token  = hash('sha256', Str::random(40));
        $token_exp = Carbon::now()->addHour();

        $accessToken = AccessToken::create([
            'employee_profile_id' => $this->id,
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
        $fullName = $personal_information['first_name'] . ' ' . $personal_information['last_name'];

        return $fullName;
    }

    public function leaveCredits()
    {
        return $this->hasMany(EmployeeLeaveCredit::class);
    }
    public function leaveLogs() {
        return $this->hasMany(LeaveTypeLog::class);
    }

    public function overtimeCredits()
    {
        return $this->hasMany(EmployeeOvertimeCredit::class);
    }

    public function leaveApplications() {
        return $this->hasMany(LeaveApplication::class);
    }

    public function leaveApplicationLogs() {
        return $this->hasMany(LeaveApplicationLog::class);
    }

    public function obApplications() {
        return $this->hasMany(ObApplication::class);
    }

    public function obApplicationLogs() {
        return $this->hasMany(ObApplicationLog::class);
    }

    public function otApplications() {
        return $this->hasMany(OfficialTimeApplication::class);
    }

    public function otApplicationLogs() {
        return $this->hasMany(OvtApplicationLog::class);
    }

    public function overtimeApplication() {
        return $this->hasMany(OvertimeApplication::class);
    }

    public function ovtApplicationLogs() {
        return $this->hasMany(OvtApplicationLog::class);
    }



    public function findDesignation()
    {
        $assign_area = $this->assignedArea;

        $designation = $assign_area->plantilla_id  === null?$assign_area->designation:$assign_area->plantilla->designation;

        return $designation;
    }

    public function issuanceInformation()
    {
        return $this->hasOne(IssuanceInformation::class);
    }

    public function position()
    {
        /** Division Chief */
        $chief = Division::where('chief_employee_profile_id', $this->id)->first();

        if($chief){
            return [
                'position' => 'Chief',
                'area' => $chief
            ];
        }

        /** Division Officer in Charge */
        $division_oic = Division::where('oic_employee_profile_id', $this->id)->first();

        if($division_oic){
            return [
                'position' => 'Division OIC',
                'area' => $division_oic
            ];
        }

        /** Department Chief */
        $head = Department::where('head_employee_profile_id', $this->id)->first();

        if($head){
            return [
                'position' => 'Chief',
                'area' => $head
            ];
        }

        /** Training Officer */
        $training_officer = Department::where('training_officer_employee_profile_id', $this->id)->first();

        if($head){
            return [
                'position' => 'Training Officer',
                'area' => $training_officer
            ];
        }

        /** Department Officer in Charge */
        $department_oic = Department::where('oic_employee_profile_id', $this->id)->first();

        if($department_oic){
            return [
                'position' => 'Department OIC',
                'area' => $department_oic
            ];
        }

        /** Section Supervisor */
        $supervisor = Section::where('supervisor_employee_profile_id', $this->id)->first();

        if($supervisor){
            return [
                'position' => 'Supervisor',
                'area' => $supervisor
            ];
        }

        /** Section Officer in Charge */
        $section_oic = Section::where('oic_employee_profile_id', $this->id)->first();

        if($section_oic){
            return [
                'position' => 'Section OIC',
                'area' => $section_oic
            ];
        }

        /** Unit Head */
        $head = Unit::where('head_employee_profile_id', $this->id)->first();

        if($head){
            return [
                'position' => 'Unit Head',
                'area' => $supervisor
            ];
        }

        /** Unit Officer in Charge */
        $unit_oic = Unit::where('oic_employee_profile_id', $this->id)->first();

        if($unit_oic){
            return [
                'position' => 'Unit OIC',
                'area' => $unit_oic
            ];
        }

        return null;
    }
    
    public function schedule() {
        return $this->belongsToMany(Schedule::class, 'employee_profile_schedule')->withPivot('employee_profile_id');
    }
    public function GetPersonalInfo()
    {
        return $this->personalInformation;
    }
}
