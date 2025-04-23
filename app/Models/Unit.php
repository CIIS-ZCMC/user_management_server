<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\HeadUnitTrailResource;
use App\Http\Resources\OICUnitTrailResource;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'units';

    public $fillable = [
        'area_id',
        'name',
        'code',
        'unit_attachment_url',
        'head_attachment_url',
        'head_effective_at',
        'oic_employee_profile_id',
        'oic_attachment_url',
        'oic_effective_at',
        'oic_end_at',
        'section_id',
        'head_employee_profile_id',
        'oid_employee_profile_id'
    ];

    public $timestamps = TRUE;
    
    protected $casts = ['deleted_at' => 'datetime'];

    public function assignArea()
    {
        return $this->hasMany(AssignArea::class);
    }

    public function head()
    {
        return $this->belongsTo(EmployeeProfile::class, 'head_employee_profile_id');
    }

    public function oic()
    {
        return $this->belongsTo(EmployeeProfile::class, 'oic_employee_profile_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function assignedAreas()
    {
        return $this->hasMany(AssignArea::class);
    }

    public function assignedAreaTrails()
    {
        return $this->hasMany(AssignAreaTrail::class);
    }

    public function headTrails()
    {
        return $this->hasMany(HeadToSupervisorTrail::class);
    }

    public function oicTrails()
    {
        return $this->hasMany(OfficerInChargeTrail::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function plantillaAssignAreas()
    {
        return $this->hasMany(PlantillaAssignedArea::class);
    }
}
