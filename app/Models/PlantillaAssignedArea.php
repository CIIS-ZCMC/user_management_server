<?php

namespace App\Models;

use App\Http\Resources\AssignAreaDepartmentResource;
use App\Http\Resources\AssignAreaDivisionResource;
use App\Http\Resources\AssignAreaSectionResource;
use App\Http\Resources\AssignAreaUnitResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantillaAssignedArea extends Model
{
    use HasFactory;

    protected $table = 'plantilla_assigned_areas';

    public $fillable = [
        'plantilla_number_id',
        'division_id',
        'department_id',
        'section_id',
        'unit_id',
        'effective_at'
    ];

#0 {main}

    public $timestamps = TRUE;

    public function plantillaNumber()
    {
        return $this->belongsTo(PlantillaNumber::class);
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

    public function plantilla()
    {
        return $this->belongsTo(PlantillaNumber::class, Plantilla::class);
    }
    public function area()
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
