<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InActiveEmployee extends Model
{
    use HasFactory;
    
    protected $table = 'in_active_employees';

    public $fillabe = [
        'employee_id',
        'profile_url',
        'date_hired',
        'biometric_id',
        'employment_end_at',
        'employment_type_id',
        'personal_information_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }

    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class);
    }
}
