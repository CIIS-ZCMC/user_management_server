<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $table = 'leave_applications';

    protected $casts = [
        'with_pay' => 'boolean',
        'patient_type' => 'boolean',
        'is_outpatient' => 'boolean',
        'is_masters' => 'boolean', 
        'is_board' => 'boolean', 
    ];

    public $fillable = [
        'employee_profile_id',
        'leave_type_id',
        'date_from',
        'date_to',
        'country',
        'city',
        'is_outpatient',
        'illness',
        'is_masters',
        'is_board',
        'applied_credits',
        'status',
        'remarks',
        'without_pay',
        'reason',
        'hrmo_officer',
        'recommending_officer',
        'approving_officer'
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveApplicationRequirements()
    {
        return $this->hasMany(LeaveApplicationRequirement::class);
    }

    public function logs()
    {
        return $this->hasMany(LeaveApplicationLog::class);
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function employeeLeaveCredit()
    {
        return $this->belongsTo(EmployeeLeaveCredit::class, 'employee_profile_id', 'leave_type_id');
    }

    public function hrmoOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'hrmo_officer');
    }

    /** Must pass an argument of division code which must be HRMO for HR head and OMCC for Chief */
    public function isApprovedByChief()
    {
        $division_head = Division::where('code', 'OMCC')->first()->chief();

        /**
         * Validate if Logs has record for hrmo approving the leave application by
         * looking for specification of Chief division head employee id and action Approved
         * does if nothing returns it will considered as false;
         */
        if(!LeaveApplicationLog::where('action_by', $division_head->id)->where('action', 'Approved')->first()){
            return false;
        }

        return true;
    }

    /** Must pass an argument of section code which must be HRMO for HR head and OMCC for Chief */
    public function isApprovedByHrmo()
    {
        $section_supervisor = Section::where('code', 'HRMO')->first()->chief();

        /**
         * Validate if Logs has record for hrmo approving the leave application by
         * looking for specification of HRMO division head employee id and action Approved
         * does if nothing returns it will considered as false;
         */
        if(!LeaveApplicationLog::where('action_by', $section_supervisor->id)->where('action', 'Approved')->first()){
            return false;
        }

        return true;
    }


    public function recommendingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'recommending_officer');
    }

    public function approvingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }

    
    public function leaveApplicationLogs()
    {
        return $this->hasMany(LeaveApplicationLog::class);
    }
}
