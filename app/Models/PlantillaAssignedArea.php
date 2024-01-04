<?php

namespace App\Models;

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

    public function area(){
        if($this->division_id !== null){
            return Division::find($this->division_id);
        }
        
        if($this->department_id !== null){
            return Department::find($this->department_id);
        }

        if($this->section_id !== null){
            return Section::find($this->section_id);
        }

        return Unit::find($this->unit_id);
    }
}
