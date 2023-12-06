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

    public function chief()
    {
        return $this->belongsTo(EmployeeProfile::class, 'chief_employee_profile_id');
    }

    public function oic()
    {
        return $this->belongsTo(EmployeeProfile::class, 'id', 'oic_employee_profile_id');
    }

    public function chiefTrails()
    {
        return $this->hasMany(HeadToSupervisorTrail::class);
    }

    public function oicTrails()
    {
        return $this->hasMany(OfficerInChargeTrail::class);
    }
   
    public function divisionHead()
    {
        return $this->belongsTo(EmployeeProfile::class, 'chief_employee_profile_id');
    }
}
