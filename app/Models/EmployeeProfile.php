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
        'authorization_pin',
        'pin_created_at',
        'biometric_id',
        'otp',
        'otp_expiration',
        'deactivated_at',
        'agency_employee_no',
        'allow_time_adjustment',
        'shifting',
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
        return $this->hasOne(AssignArea::class)->latest();
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

    public function passwordTrail()
    {
        return $this->hasMany(PasswordTrail::class);
    }

    public function createToken()
    {
        Log::channel('custom-info')->info('PASSED');
        // $publicKeyString
        AccessToken::where('employee_profile_id', $this->id)->delete();


        $token = hash('sha256', Str::random(40));
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

    public function leaveCredit()
    {
        return $this->hasMany(EmployeeLeaveCredit::class);
    }
    public function leaveLogs()
    {
        return $this->hasMany(LeaveTypeLog::class);
    }

    public function overtimeCredits()
    {
        return $this->hasMany(EmployeeOvertimeCredit::class);
    }

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'employee_profile_id');
    }

    public function leaveApplicationLogs()
    {
        return $this->hasMany(LeaveApplicationLog::class);
    }

    public function obApplications()
    {
        return $this->hasMany(ObApplication::class);
    }

    public function obApplicationLogs()
    {
        return $this->hasMany(ObApplicationLog::class);
    }


    public function officialBusinessApplications()
    {
        return $this->hasMany(OfficialBusiness::class);
    }

    public function officialTimeApplications()
    {
        return $this->hasMany(OfficialTime::class);
    }


    public function otApplications()
    {
        return $this->hasMany(OfficialTimeApplication::class);
    }

    public function otApplicationLogs()
    {
        return $this->hasMany(OvtApplicationLog::class);
    }

    public function overtimeApplication()
    {
        return $this->hasMany(OvertimeApplication::class);
    }

    public function ovtApplicationLogs()
    {
        return $this->hasMany(OvtApplicationLog::class);
    }



    public function findDesignation()
    {
        $assign_area = $this->assignedArea;

        $designation = $assign_area->plantilla_id === null ? $assign_area->designation : $assign_area->plantilla->designation;

        return $designation;
    }

    public function issuanceInformation()
    {
        return $this->hasOne(IssuanceInformation::class);
    }

    public function position()
    {
        /** Division Chief */
        $chief = Division::where('chief_employee_profile_id', $this->id)->where('code', 'OMCC')->first();

        if ($chief) {
            return [
                'position' => 'Medical Center Chief',
                'area' => $chief
            ];
        }

        /** Chief Nurse */
        $chief_nurse = Division::where('chief_employee_profile_id', $this->id)->where('code', 'NS')->first();

        if ($chief_nurse) {
            return [
                'position' => 'Chief Nurse',
                'area' => $chief_nurse
            ];
        }

        /** Division Head */
        $division_head = Division::where('chief_employee_profile_id', $this->id)->first();

        if ($division_head) {
            return [
                'position' => 'Division Head',
                'area' => $division_head
            ];
        }

        /** Division Officer in Charge */
        $division_oic = Division::where('oic_employee_profile_id', $this->id)->first();

        if ($division_oic) {
            return [
                'position' => 'Division OIC',
                'area' => $division_oic
            ];
        }

        /** Department Chief */
        $head = Department::where('head_employee_profile_id', $this->id)->first();
        $nurse_service = Division::where('code', 'NS')->first();

        if ($head) {
            if ($head->department_id === $nurse_service->id) {
                return [
                    'position' => 'Nurse Manager',
                    'area' => $head
                ];
            }
        }

        if ($head) {
            if ($head->department_id === $nurse_service->id) {
                return [
                    'position' => 'Nurse Manager',
                    'area' => $head
                ];
            }

            return [
                'position' => 'Department Head',
                'area' => $head
            ];
        }

        /** Training Officer */
        $training_officer = Department::where('training_officer_employee_profile_id', $this->id)->first();

        if ($head) {
            return [
                'position' => 'Training Officer',
                'area' => $training_officer
            ];
        }

        /** Department Officer in Charge */
        $department_oic = Department::where('oic_employee_profile_id', $this->id)->first();

        if ($department_oic) {
            return [
                'position' => 'Department OIC',
                'area' => $department_oic
            ];
        }

        /** Section Supervisor */
        $supervisor = Section::where('supervisor_employee_profile_id', $this->id)->first();

        if ($supervisor) {
            return [
                'position' => 'Supervisor',
                'area' => $supervisor
            ];
        }

        /** Section Officer in Charge */
        $section_oic = Section::where('oic_employee_profile_id', $this->id)->first();

        if ($section_oic) {
            return [
                'position' => 'Section OIC',
                'area' => $section_oic
            ];
        }

        /** Unit Head */
        $head = Unit::where('head_employee_profile_id', $this->id)->first();

        if ($head) {
            return [
                'position' => 'Unit Head',
                'area' => $supervisor
            ];
        }

        /** Unit Officer in Charge */
        $unit_oic = Unit::where('oic_employee_profile_id', $this->id)->first();

        if ($unit_oic) {
            return [
                'position' => 'Unit OIC',
                'area' => $unit_oic
            ];
        }

        return null;
    }

    public function schedule()
    {
        return $this->belongsToMany(Schedule::class, 'employee_profile_schedule')->withPivot('id', 'employee_profile_id');
    }

    public function biometric()
    {
        return $this->belongsTo(Biometrics::class);
    }

    public function GetPersonalInfo()
    {
        return $this->personalInformation;
    }

    public function areaEmployee($assigned_area)
    {
        $key = null;

        if (Division::where('chief_employee_profile_id', $this->id)->first()) {
            $key = 'division_id';
        }

        if (Department::where('head_employee_profile_id', $this->id)->first()) {
            $key = 'department_id';
        }

        if (Section::where('supervisor_employee_profile_id', $this->id)->first()) {
            $key = 'section_id';
        }

        if (Unit::where('head_employee_profile_id', $this->id)->first()) {
            $key = 'unit_id';
        }

        if ($key === null)
            return null;

        $assigned_areas = AssignArea::where($key, $assigned_area['details']->id)->get();

        $employees = [];
        foreach ($assigned_areas as $assigned_area) {
            $employees[] = $assigned_area->employeeProfile;
        }

        return $employees;
    }

    public function sectorHeads()
    {

        /** Division Chief */
        $chief = Division::where('chief_employee_profile_id', $this->id)->first();

        if ($chief) {
            $departments = Department::where('division_id', $chief->id)->get();
            $employees = [];
            foreach ($departments as $department) {
                $employees[] = $department->head;
            }

            return $employees;
        }

        /** Department Chief */
        $head = Department::where('head_employee_profile_id', $this->id)->first();
        if ($head) {
            $sections = Section::where('department_id', $head->id)->get();

            $employees = [];
            foreach ($sections as $key => $section) {
                $employees[$key] = $section->supervisor;
            }

            return $employees;
        }

        /** Section Chief */
        $supervisor = Section::where('supervisor_employee_profile_id', $this->id)->first();
        if ($supervisor) {
            $units = Unit::where('section_id', $supervisor->id)->get();

            $employees = [];
            foreach ($units as $unit) {
                $employees[] = $unit->head;
            }

            return $employees;
        }

        return [];
    }

    public function employeeHead($assigned_area)
    {
        $model = "App\\Models\\$assigned_area[sector]";
        $sector_head = $model::where('id', $assigned_area['details']->id)->first();

        switch ($assigned_area['sector']) {
            case 'Division':
                return $sector_head->chief_employee_profile_id;
            case 'Department':
                return $sector_head->head_employee_profile_id;
            case 'Section':
                return $sector_head->supervisor_employee_profile_id;
            case 'Unit':
                return $sector_head->head_employee_profile_id;
            default:
                return null;
        }
    }

    public function employeeAreaList($assigned_area)
    {
        $assigned_areas = AssignArea::where(Str::lower($assigned_area['sector'] . "_id"), $assigned_area['details']->id)->get();

        $employees = [];
        foreach ($assigned_areas as $area) {
            $employees[] = $area->employeeProfile; //fetch all excepy employeeHead
        }

        return $employees;
    }
}
