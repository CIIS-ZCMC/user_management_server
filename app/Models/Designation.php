<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $table = 'designations';

    public $fillable = [
        'name',
        'code',
        'probation',
        'effective_at',
        'salary_grade_id',
        'position_type'
    ];

    public $timestamps = TRUE;

    public function salaryGrade()
    {
        return $this->belongsTo(SalaryGrade::class);
    }

    public function plantilla()
    {
        return $this->hasMany(Plantilla::class);
    }

    public function positionSystemRoles()
    {
        return $this->hasMany(PositionSystemRole::class);
    }

    public function assignAreas()
    {
        return $this->hasMany(AssignArea::class);
    }
}
