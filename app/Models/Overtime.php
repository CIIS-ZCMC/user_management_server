<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;
    protected $table = 'overtime_applications';

    public $fillable = [
        'employee_profile_id',
        'reference_number',
        'status',
        'purpose',
        'overtime_letter_of_request',
        'path',
        'date',
        'time'

    ];

    public function activities()
    {
        return $this->hasMany(OvtApplicationActivity::class);
    }
    public function logs()
    {
            return $this->hasMany(OvtApplicationLog::class);
    }
    public function employeeProfile() {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function directDates() {
        return $this->hasMany(OvtApplicationDatetime::class);
    }

    public function oic(){
        return $this->belongsTo(EmployeeProfile::class, 'employee_oic_id');
    }

    public function employeeOvertimeCredit()
    {

        return $this->belongsTo(EmployeeOvertimeCredit::class, 'employee_profile_id','id');
    }

    public function hrmoOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'hrmo_officer');
    }

    public function recommendingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'recommending_officer');
    }

    public function approvingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }

      /** Must pass an argument of division code which must be HRMO for HR head and OMCC for Chief */
      public function isApprovedByChief()
      {
          $division_head = Division::where('area_id', 'OMCC-DI-001')->first()->chief();

          /**
           * Validate if Logs has record for hrmo approving the leave application by
           * looking for specification of Chief division head employee id and action Approved
           * does if nothing returns it will considered as false;
           */
          if(!OvtApplicationLog::where('action_by', $division_head->id)->where('action', 'Approved')->first()){
              return false;
          }

          return true;
      }

      /** Must pass an argument of section code which must be HRMO for HR head and OMCC for Chief */
      public function isApprovedByHrmo()
      {
          $section_supervisor = Section::where('area_id', 'HOPPS-HRMO-DE-001')->first()->chief();

          /**
           * Validate if Logs has record for hrmo approving the leave application by
           * looking for specification of HRMO division head employee id and action Approved
           * does if nothing returns it will considered as false;
           */
          if(!OvtApplicationLog::where('action_by', $section_supervisor->id)->where('action', 'Approved')->first()){
              return false;
          }

          return true;
      }

}
