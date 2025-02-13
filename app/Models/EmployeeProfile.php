<?php

namespace App\Models;

use App\Helpers\Helpers;
use App\Http\Resources\EmployeeHeadResource;
use App\Http\Resources\OfficialBusinessApplication;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\AssignedArea;

use App\Models\Schedule;

class EmployeeProfile extends Authenticatable
{
    use HasFactory;

    protected $table = 'employee_profiles';

    public $fillable = [
        'personal_information_id',
        'email_verified_at',
        'employee_id',
        'profile_url',
        'date_hired',
        'user_form_link',
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
        'solo_parent',
        'renewal',
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

    public function employeeRedcapModules()
    {
        return $this->hasMany(EmployeeRedcapModules::class);
    }

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

    public function failedLoginTrails()
    {
        return $this->hasMany(FailedLoginTrail::class);
    }

    public function loginTrails()
    {
        return $this->hasMany(LoginTrail::class);
    }

    public function isUnderProbation()
    {
        $area = $this->assignedArea;
        $probation_period = $area->designation->probation;

        $hireDate = Carbon::parse($this->date_hired);
        $currentDate = Carbon::now();

        return $hireDate->diffInMonths($currentDate) <= $probation_period;
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

        $encryptedToken = openssl_encrypt($token, config('app.encrypt_decrypt_algorithm'), config('app.app_key'), 0, substr(md5(config('app.app_key')), 0, 16));

        return $encryptedToken;
    }

    public function name()
    {
        $personal_information = $this->personalInformation;
        $fullName = $personal_information['first_name'] . ' ' . $personal_information['last_name'];

        return $fullName;
    }

    public function lastNameTofirstName()
    {
        $personal_information = $this->personalInformation;
        $fullName = $personal_information['last_name'] . ', ' . $personal_information['first_name'];

        return $fullName;
    }

    public function employeeLeaveCredits()
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

    public function ctoApplications()
    {
        return $this->hasMany(CtoApplication::class);
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


    public function removeRecords()
    {
        PasswordTrail::where('employee_profile_id', $this->id)->delete();
        LoginTrail::where('employee_profile_id', $this->id)->delete();
        AccessToken::where('employee_profile_id', $this->id)->delete();
        SpecialAccessRole::where('employee_profile_id', $this->id)->delete();
        AssignArea::where('employee_profile_id', $this->id)->delete();
    }

    public function findDesignation()
    {
        $assign_area = $this->assignedArea;
        $designation = !isset($assign_area->plantilla_id) ? $assign_area->designation ?? "" : $assign_area->plantilla->designation ?? "";
        return $designation;
    }

    public function getBiometricLog($date)
    {
        $dtr = DailyTimeRecords::where('biometric_id', $this->biometric_id)->where('dtr_date', date('Y-m-d', strtotime($date)))->first();

        if ($dtr) {
            return $dtr;
        }
        return [];
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

        /** for HR ADMIN */
        $assign_area = AssignArea::where('employee_profile_id', $this->id)->first();

        if ($assign_area->section_id !== null) {
            $hr_employee = Section::find($assign_area->section_id);

            if ($hr_employee->code === 'HRMO') {
                $role = Role::where('code', "HR-ADMIN")->first();
                $system_role = SystemRole::where('role_id', $role->id)->first();
                $special_access_role = SpecialAccessRole::where('employee_profile_id', $this->id)
                    ->where('system_role_id', $system_role->id)->first();

                if ($special_access_role) {
                    return [
                        'position' => "HR Staff",
                        'area' => $hr_employee
                    ];
                }
            }
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

    public function retrieveEmployees($employees, $key, $id, $myId)
    {

        $assign_areas = AssignArea::where($key, $id)
            ->whereNotIn('employee_profile_id', $myId)->get();

        $new_employee_list = $assign_areas->map(function ($assign_area) {
            return $assign_area->employeeProfile;
        })->flatten()->all();

        return [...$new_employee_list];
        // return [...$employees, ...$new_employee_list];
    }

    public function myEmployees($assign_area, $user)
    {
        $employees = [];
        $division_heads = [];
        $division_employees = [];
        $department_employees = [];
        $section_employees = [];
        $unit_employees = [];

        // $employees = $this->retrieveEmployees($employees, Str::lower($assign_area['sector']) . "_id", $assign_area['details']->id, [$user->id, 1]);

        switch ($assign_area['sector']) {
            case 'Division':
                $divisions = Division::where('id', $assign_area['details']->id)->get();
                foreach ($divisions as $division) {
                    if ($user->id !== $division->chief->id) {
                        $division_employees = $this->retrieveEmployees($employees, 'division_id', $division->id, [1, $division->chief->id]);
                    } else {
                        $all_division = Division::all();
                        foreach ($all_division as $head) {
                            $division_heads[] = $head->chief;
                        }

                        $division_employees = $this->retrieveEmployees($employees, 'division_id', $division->id, [1]);
                    }
                }

                $departments = Department::where('division_id', $assign_area['details']->id)->get();
                foreach ($departments as $department) {
                    $department_employees[] = $department->head;
                }

                $sections = Section::where('division_id', $assign_area['details']->id)->get();
                foreach ($sections as $section) {
                    $section_employees[] = $section->supervisor;
                }

                $employees = array_merge($division_heads, $division_employees, $department_employees, $section_employees);
                break;

            case 'Department':
                $sections = Section::where('department_id', $assign_area['details']->id)->get();
                foreach ($sections as $section) {
                    $my_employees = $this->retrieveEmployees($employees, 'department_id', $section->department_id, [$user->id, 1]);
                    $employees = array_merge($my_employees, (array) $section->supervisor);
                }
                break;

            case 'Section':
                $sections = Section::where('id', $assign_area['details']->id)->get();
                foreach ($sections as $section) {
                    $section_employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1]);
                }

                $units = Unit::where('section_id', $assign_area['details']->id)->get();
                foreach ($units as $unit) {
                    $unit_employees[] = $unit->head;
                }

                $employees = array_merge($section_employees, $unit_employees);
                break;

            case 'Unit':
                $units = Unit::where('id', $assign_area['details']->id)->get();
                foreach ($units as $unit) {
                    $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1]);
                }
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

    public function myColleague($assign_area, $myId)
    {
        $employees = [];
        $assign_areas = AssignArea::where(strtolower($assign_area['sector'] . "_id"), $assign_area['details']->id)->whereNotIn('employee_profile_id', $myId)->get();
        foreach ($assign_areas as $area) {
            $employees = $area->employeeProfile;
        }

        return $employees;
    }

    public function employeeHead($assigned_area)
    {
        $assigned_area = $this->assignedArea->findDetails();
        
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

    public function employeeHeadOfficer()
    {
        $assigned_area = $this->assignedArea->findDetails();
        
        $model = "App\\Models\\$assigned_area[sector]";
        $sector_head = $model::where('id', $assigned_area['details']->id)->first();

        switch ($assigned_area['sector']) {
            case 'Division':
                return $sector_head->chief_employee_profile_id !== null? new EmployeeHeadResource($sector_head->divisionHead) : null;
            case 'Department':
                return $sector_head->head_employee_profile_id !== null? new EmployeeHeadResource($sector_head->departmentHead) : null;
            case 'Section':
                return $sector_head->supervisor_id !== null? new EmployeeHeadResource($sector_head->supervisor) : null;
            case 'Unit':
                return  $sector_head->head_employee_profile_id !== null? new EmployeeHeadResource($sector_head->head) : null;
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

    public function salaryGrade()
    {
        return $this->belongsTo(SalaryGrade::class);
    }

    public function monthlyWorkHours()
    {
        return $this->belongsTo(MonthlyWorkHours::class);
    }

    public function assignedAreas()
    {
        return $this->hasMany(AssignArea::class);
    }

    public function dailyTimeRecords()
    {
        return $this->hasMany(DailyTimeRecords::class, 'biometric_id', 'biometric_id');
    }
}
