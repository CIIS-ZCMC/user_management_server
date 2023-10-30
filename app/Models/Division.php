<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Http\Resources\ChiefDivisionTrailResource;
use App\Http\Resources\OICDivisionTrailResource;

class Division extends Model
{
    use HasFactory;

    protected $table = 'divisions';

    public $fillable = [
        'code',
        'name',
        'division_attachment_url',
        'job_specification',
        'chief_attachment_url',
        'chief_effective_at',
        'oic_attachment_url',
        'oic_effective_at',
        'oic_end_at',
        'chief_employee_profile_id',
        'oic_employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function chiefRequirement()
    {
        return Designation::where('code', $this->job_specification)->first();
    }

    public function chief()
    {
        return $this->belongsTo(EmployeeProfile::class, 'id', 'chief_employee_profile_id');
    }

    public function oic()
    {
        return $this->belongsTo(EmployeeProfile::class, 'id', 'oic_employee_profile_id');
    }

    public function chiefTrails()
    {
        $chief_trails = HeadToSupervisorTrail::where('sector_code', $this->code)->get();

        return ChiefDivisionTrailResource::collection($chief_trails);
    }

    public function oicTrails()
    {
        $oic_trails = OfficerInChargeTrail::where('sector_code', $this->code)->get();

        return OICDivisionTrailResource::collection($oic_trails);
    }
}
