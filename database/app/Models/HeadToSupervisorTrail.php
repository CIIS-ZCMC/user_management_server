<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeadToSupervisorTrail extends Model
{
    use HasFactory;

    protected $table = 'h_to_s_trails';

    public $fillable = [
        'employee_profile_id',
        'division_id',
        'department_id',
        'section_id',
        'unit_id',
        'position_title',
        'sector_code',
        'attachment_url',
        'started_at',
        'ended_at'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function sector()
    {
        if($this->position_title === 'chief')
        {
            return Division::where('code', $this->sector_code)->first();
        }

        if($this->position_title === 'head department' || $this->position_title === 'training officer')
        {
            return Department::where('code', $this->sector_code)->first();
        }

        return Unit::where('code', $this->sector_code)->first();
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
}
