<?php

namespace App\Models;

use App\Helpers\Helpers;
use App\Http\Resources\OfficialBusinessApplication;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
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
        
        $encryptedToken = openssl_encrypt($token, config('app.encrypt_decrypt_algorithm'), config('app.app_key'), 0, substr(md5(config('app.app_key')), 0, 16));

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

    public function CTOApplication()
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
        IssuanceInformation::where('employee_profile_id', $this->id)->delete();
        PasswordTrail::where('employee_profile_id', $this->id)->delete();
        LoginTrail::where('employee_profile_id', $this->id)->delete();
        AccessToken::where('employee_profile_id', $this->id)->delete();
        SpecialAccessRole::where('employee_profile_id', $this->id)->delete();
        AssignArea::where('employee_profile_id', $this->id)->delete();
        
        $employee_leave_credits = $this->leaveCredit;
        foreach($employee_leave_credits as $employee_leave_credit){
            EmployeeLeaveCreditLogs::where('employee_leave_credit_id', $employee_leave_credit->id)->delete();
            $employee_leave_credit->delete();
        }

        $leave_applications = $this->leaveApplications;
        foreach($leave_applications as $leave_application){
            LeaveApplicationLog::where('leave_application_id', $leave_application->id)->delete();
            LeaveApplicationRequirement::where('leave_application_id', $leave_application->id)->delete();
            $leave_application->delete();
        }

        $official_business_applications = $this->officialBusinessApplications;
        foreach($official_business_applications as $official_business_application){
            OfficialBusinessLog::where('official_business_id', $official_business_application->id)->delete();
            $official_business_application->delete();
        }

        $offial_time_applications = $this->officialTimeApplications;
        foreach($offial_time_applications as $offial_time_application){

            OtApplicationLog::where('official_time_application_id', $offial_time_application->id)->delete();   
            $offial_time_application->delete();
        }

        OvtApplicationEmployee::where('employee_profile_id', $this->id)->delete();
        $overtime_applications = $this->overtimeApplication;
        foreach($overtime_applications as $overtime_application){
            OvtApplicationLog::where('overtime_application_id', $overtime_application->id)->delete();

            $overtime_application->delete();
        }

        $employee_ot_credit = EmployeeOvertimeCredit::where('employee_profile_id', $this->id)->first();
        EmployeeOvertimeCreditLog::where('employee_ot_credit_id', $employee_ot_credit->id)->delete();
        $employee_ot_credit->delete();

        $cto_applications = CtoApplication::where('employee_profile_id', $this->id)->get();
        foreach($cto_applications as $cto_application){
            CtoApplicationLog::where('cto_application_id', $cto_application->id)->delete();
            $cto_application->delete();
        }

        EmployeeSchedule::where('employee_profile_id', $this->id)->delete();

        $pull_outs = PullOut::where('employee_profile_id', $this->id)->get();
        foreach($pull_outs as $pull_out){
            PullOutLog::where('pull_out_id', $pull_out->id)->delete();
            $pull_out->delete();
        }

        $on_calls = OnCall::where('employee_profile_id', $this->id)->get();
        foreach($on_calls as $on_call){
            OnCallLog::where('on_call_id', $on_call->id)->delete();
            $on_call->delete();
        }

        $time_adjustments = TimeAdjusment::where('employee_profile_id', $this->id)->get();
        foreach($time_adjustments as $time_adjustment){
            TimeAdjustmentLog::where('employee_profile_id', $time_adjustment->id)->delete();
            $time_adjustment->delete();
        }

        $exchange_duties = ExchangeDuty::where('reliever_employee_id', $this->id)->get();
        foreach($exchange_duties as $exchange_duty){
            ExchangeDutyLog::where('exchange_duty_id', $exchange_duty->id)->delete();
            $exchange_duty->delete();
        }

        $exchange_duties = ExchangeDuty::where('requested_employee_id', $this->id)->get();
        foreach($exchange_duties as $exchange_duty){
            ExchangeDutyLog::where('exchange_duty_id', $exchange_duty->id)->delete();
            $exchange_duty->delete();
        }
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
        $nurse_service = Division::where('code', 'NURSING')->first();

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
        if($assign_area->section_id !== null){
            $hr_employee = Section::find($assign_area->section_id);

            if($hr_employee->code === 'HRMO'){
                $role = Role::where('code', "HR-ADMIN")->first();
                $system_role = SystemRole::where('role_id', $role->id)->first();
                $special_access_role = SpecialAccessRole::where('employee_profile_id', $this->id)
                    ->where('system_role_id', $system_role->id)->first();

                if($special_access_role){
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

        return [...$employees, ...$new_employee_list];
    }

    public function myEmployees($assign_area, $user)
    {
        $employees = [];

        $employees = $this->retrieveEmployees($employees, Str::lower($assign_area['sector']) . "_id", $assign_area['details']->id, [$user->id, 1]);

        switch ($assign_area['sector']) {
            case 'Division':
                $departments = Department::where('division_id', $assign_area['details']->id)->get();

                foreach ($departments as $department) {
                    $employees = $this->retrieveEmployees($employees, 'department_id', $department->id, [$user->id, 1]);
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1]);
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1]);
                        }
                    }
                }

                $sections = Section::where('division_id', $assign_area['details']->id)->get();
                foreach ($sections as $section) {
                    $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1]);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1]);
                    }
                }
                break;

            case 'Department':
                $sections = Section::where('department_id', $assign_area['details']->id)->get();
                foreach ($sections as $section) {
                    $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1]);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1]);
                    }
                }
                break;

            case 'Section':
                if ($assign_area['details']->code === "HRMO") {
                    $employees = AssignArea::whereNotIn('employee_profile_id', [$user->id, 1])->get();
                } else {
                    $units = Unit::where('section_id', $assign_area['details']->id)->get();
                    foreach ($units as $unit) {
                        $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1]);
                    }
                }
                break;
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

    public function salaryGrade()
    {
        return $this->belongsTo(SalaryGrade::class);
    }
}
