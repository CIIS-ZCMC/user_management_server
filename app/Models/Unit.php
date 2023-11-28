<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\HeadUnitTrailResource;
use App\Http\Resources\OICUnitTrailResource;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    public $fillable = [
        'name',
        'code',
        'unit_attachment_url',
        'head_attachment_url',
        'job_specification',
        'head_effective_at',
        'oic_attachment_url',
        'oic_effective_at',
        'oic_end_at',
        'section_id',
        'head_employee_profile_id',
        'oid_employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function head()
    {
        return $this->belongsTo(EmployeeProfile::class, 'id', 'head_employee_profile_id');
    }

    public function oic()
    {
        return $this->belongsTo(EmployeeProfile::class, 'id', 'oic_employee_profile_id');
    }

    public function headJobSpecification()
    {
        return Designation::where('code', $this->job_specification)->first();
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
}
