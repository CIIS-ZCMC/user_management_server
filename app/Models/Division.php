<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Division extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'divisions';

    public $fillable = [
        'area_id',
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

    protected $casts = ['deleted_at' => 'datetime'];

    public function employees()
    {
        return $this->belongsToMany(AssignArea::class, EmployeeProfile::class, 'employee_profile_id', 'id', 'division_id', 'id');
    }

    public function assignArea()
    {
        return $this->hasMany(AssignArea::class);
    }

    public function assignAreaTrails()
    {
        return $this->hasMany(related: AssignAreaTrail::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function chief()
    {
        return $this->belongsTo(EmployeeProfile::class, 'chief_employee_profile_id');
    }

    public function oic()
    {
        return $this->belongsTo(EmployeeProfile::class, 'oic_employee_profile_id');
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

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function plantillaAssignAreas()
    {
        return $this->hasMany(PlantillaAssignedArea::class);
    }
}
