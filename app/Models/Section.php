<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\SupervisorSectionResource;
use App\Http\Resources\OICSectionResource;

class Section extends Model
{
    use HasFactory;

    protected $table = 'sections';

    public $fillable = [
        'area_id',
        'name',
        'code',
        'section_attachment_url',
        'supervisor_attachment_url',
        'supervisor_effective_at',
        'oic_attachment_url',
        'oic_effective_at',
        'oic_end_at',
        'division_id',
        'department_id',
        'supervisor_employee_profile_id',
        'oic_employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function employees()
    {
        return $this->belongsToMany(AssignArea::class, EmployeeProfile::class, 'employee_profile_id', 'id', 'section_id', 'id');
    }

    public function assignArea()
    {
        return $this->hasMany(AssignArea::class);
    }

    public function assignAreaTrails()
    {
        return $this->hasMany(AssignAreaTrail::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(EmployeeProfile::class, 'supervisor_employee_profile_id');
    }

    public function supervisorJobSpecification()
    {
        return Designation::where('code', $this->job_specification)->first();
    }

    public function oic()
    {
        return $this->belongsTo(EmployeeProfile::class, 'oic_employee_profile_id');
    }

    public function supervisorTrails()
    {
        return $this->hasMany(HeadToSupervisorTrail::class);
    }

    public function oicTrails()
    {
        return $this->hasMany(OfficerInChargeTrail::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function plantillaAssignAreas()
    {
        return $this->hasMany(PlantillaAssignedArea::class);
    }
}
