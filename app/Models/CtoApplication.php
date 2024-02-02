<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CtoApplication extends Model
{
    use HasFactory;

    protected $table = 'cto_applications';

    public $fillable = [
        'employee_profile_id',
        'date',
        'applied_credits',
        'purpose',
        'remarks',
        'status',
        'recommending_officer',
        'approving_officer'
    ];

    // public function dates()
    // {
    //     return $this->hasMany(CtoApplicationDate::class);
    // }
    public function logs()
    {
        return $this->hasMany(CtoApplicationLog::class);
    }
    public function employeeProfile() {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function employeeCredit() {
        return $this->belongsTo(EmployeeOvertimeCredit::class, 'employee_overtime_credits');
    }

    public function CtoApplicationLogs()
    {
        return $this->hasMany(CtoApplicationLog::class);
    }



    // public function hrmoOfficer()
    // {
    //     return $this->belongsTo(EmployeeProfile::class, 'hrmo_officer');
    // }

    // /** Must pass an argument of division code which must be HRMO for HR head and OMCC for Chief */
    // public function isApprovedByChief()
    // {
    //     $division_head = Division::where('code', 'OMCC')->first()->chief();

    //     /**
    //      * Validate if Logs has record for hrmo approving the leave application by
    //      * looking for specification of Chief division head employee id and action Approved
    //      * does if nothing returns it will considered as false;
    //      */
    //     if(!LeaveApplicationLog::where('action_by', $division_head->id)->where('action', 'Approved')->first()){
    //         return false;
    //     }

    //     return true;
    // }

    // /** Must pass an argument of section code which must be HRMO for HR head and OMCC for Chief */
    // public function isApprovedByHrmo()
    // {
    //     $section_supervisor = Section::where('code', 'HRMO')->first()->chief();

    //     /**
    //      * Validate if Logs has record for hrmo approving the leave application by
    //      * looking for specification of HRMO division head employee id and action Approved
    //      * does if nothing returns it will considered as false;
    //      */
    //     if(!LeaveApplicationLog::where('action_by', $section_supervisor->id)->where('action', 'Approved')->first()){
    //         return false;
    //     }

    //     return true;
    // }


    public function recommendingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'recommending_officer');
    }

    public function approvingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }
}
