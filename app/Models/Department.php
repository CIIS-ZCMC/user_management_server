<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\HeadDepartmentResource;
use App\Http\Resources\OICDepartmentResouce;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departments';

    public $fillable = [
        'area_id',
        'name',
        'code',
        'department_attachment_url',
        'division_id',
        'head_attachment_url',
        'head_effective_at',
        'head_employee_profile_id',
        'training_officer_attachment_url',
        'training_officer_effective_at',
        'training_officer_employee_profile_id',
        'oic_attachment_url',
        'oic_effective_at',
        'oic_end_at',
        'oic_employee_profile_id'
    ];

    public $timestamps = TRUE;

    protected $casts = ['deleted_at' => 'datetime'];

    public function assignArea()
    {
        return $this->hasMany(AssignArea::class);
    }

    public function assignAreaTrails()
    {
        return $this->hasMany(AssignAreaTrail::class, 'department_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function head()
    {
        return $this->belongsTo(EmployeeProfile::class, 'head_employee_profile_id');
    }

    public function trainingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'training_officer_employee_profile_id');
    }

    public function oic()
    {
        return $this->belongsTo(EmployeeProfile::class, 'oic_employee_profile_id');
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

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function plantillaAssignAreas()
    {
        return $this->hasMany(PlantillaAssignedArea::class);
    }
}
