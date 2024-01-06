<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\AssignAreaDivisionResource;
use App\Http\Resources\AssignAreaDepartmentResource;
use App\Http\Resources\AssignAreaSectionResource;
use App\Http\Resources\AssignAreaUnitResource;

class AssignArea extends Model
{
    use HasFactory;

    protected $table = 'assigned_areas';

    public $fillable = [ 
        'salary_grade_step',
        'employee_profile_id',
        'division_id',
        'department_id',
        'section_id',
        'unit_id',
        'designation_id',
        'plantilla_id',
        'plantilla_number_id',
        'effective_at'
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

    public function findDetails()
    {
        if($this->division_id !== null)
        {
            return [
                'details' => new AssignAreaDivisionResource($this->division),
                'sector' => 'Division'
            ];
        }

        if($this->department_id !== null)
        {
            return [
                'details' => new AssignAreaDepartmentResource($this->department),
                'sector' => 'Department'
            ];
        }

        if($this->section_id !== null)
        {
            return [
                'details' => new AssignAreaSectionResource($this->section),
                'sector' => 'Section'
            ];
        }

        return [
            'details' => new AssignAreaUnitResource($this->unit),
            'sector' => 'Unit'
        ]; 
    }
}
