<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignAreaTrail extends Model
{
    use HasFactory;

    protected $table = 'assigned_area_trails';

    public $fillable = [
        'salary_grade_step',
        'salary_grade_id',
        'employee_profile_id',
        'division_id',
        'department_id',
        'section_id',
        'unit_id',
        'designation_id',
        'plantilla_id',
        'plantilla_number_id',
        'started_at',
        'end_at'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    
    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function plantilla()
    {
        return $this->belongsTo(Plantilla::class);
    }

    public function plantillaNumber()
    {
        return $this->belongsTo(PlantillaNumber::class);
    }
}
