<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\HeadDepartmentResource;
use App\Http\Resources\OICDepartmentResouce;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    public $fillable = [
        'name',
        'code',
        'department_attachment_url',
        'division_id',
        'head_attachment_url',
        'head_job_specification',
        'head_effective_at',
        'head_employee_profile_id',
        'training_officer_attachment_url',
        'training_officer_effective_at',
        'training_officer_job_specification',
        'training_officer_employee_profile_id',
        'oic_attachment_url',
        'oic_effective_at',
        'oic_end_at',
        'oic_employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function head()
    {
        return $this->belongsTo(EmployeeProfile::class,'head_employee_profile_id');
    }

    public function headJobSpecification()
    {
        return Designation::where('code', $this->head_job_specification)->first();
    }

    public function trainingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class,'training_officer_employee_profile_id');
    }

    public function trainingOfficerJobSpecification()
    {
        return Designation::where('code', $this->training_officer_job_specification)->first();
    }

    public function oic()
    {
        return $this->belongsTo(EmployeeProfile::class, 'id', 'oic_employee_profile_id');
    }

    public function headTrails()
    {
        return $this->hasMany(HeadToSupervisorTrail::class);
    }

    public function oicTrails()
    {
        return $this->hasMany(OfficerInChargeTrail::class);
    }

    public function departmentHead()
    {
        return $this->belongsTo(EmployeeProfile::class, 'head_employee_profile_id');
    }
}
