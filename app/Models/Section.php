<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $table = 'sections';

    public $fillable = [
        'name',
        'code',
        'section_atachment_url',
        'job_specification',
        'supervisor_attachment_url',
        'supervisor_effective_at',
        'oic_attachment_url',
        'oic_effective_at',
        'oic_end_at',
        'division_id',
        'department_id',
        'supervisor_employee_profile_id',
        'oic_employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(EmployeeProfile::class, 'id', 'supervisor_employee_profile_id');
    }

    public function supervisorJobSpecification()
    {
        return Designation::where('code', $this->job_specification)->first();
    }

    public function oic()
    {
        return $this->belongsTo(EmployeeProfile::class, 'id', 'oic_employee_profile_id');
    }
}
