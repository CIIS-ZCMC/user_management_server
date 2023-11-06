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
        return $this->hasMany(AssignedArea::class);
    }

    public function assignedAreaTrails()
    {
        return $this->hasMany(AssignedAreaTrail::class);
    }

    public function headTrails()
    {
        $head_trails = HeadToSupervisorTrail::where('sector_code', $this->code)->get();

        return HeadUnitTrailResource::collection($head_trails);
    }

    public function oicTrails()
    {
        $oic_trails = OfficerInChargeTrail::where('sector_code', $this->code)->get();
        
        return OICUnitTrailResource::collection($oic_trails);
    }
}
