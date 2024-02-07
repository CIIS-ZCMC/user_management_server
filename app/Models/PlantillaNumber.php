<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantillaNumber extends Model
{
    use HasFactory;

    protected $table = 'plantilla_numbers';

    public $fillable = [
        'number',
        'is_vacant',
        'assigned_at',
        'is_dissolve',
        'plantilla_id',
        'employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function plantilla()
    {
        return $this->belongsTo(Plantilla::class);
    }

    public function assignedArea()
    {
        return $this->hasOne(PlantillaAssignedArea::class);
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
