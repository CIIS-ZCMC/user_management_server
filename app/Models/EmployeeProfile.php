<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $table = 'employee_profiles';

    public $fillable = [
        'employee_id',
        'profile_url',
        'date_hired',
        'job_type',
        'password',
        'password_created_date',
        'password_expiration_date',
        'department_id',
        'employment_position_id',
        'personal_information_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }

    public function employeeStation()
    {
        return $this->hasMany(EmployeeStation::class);
    }
}
