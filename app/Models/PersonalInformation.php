<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalInformation extends Model
{
    use HasFactory;

    protected $table = 'personal_informations';

    public $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'name_extension',
        'years_of_service',
        'name_title',
        'sex',
        'date_of_birth',
        'palce_of_birth',
        'civil_status',
        'date_of_marriage',
        'citizenship',
        'height',
        'weight',
        'agency_employee_no'
    ];

    public $timestamps = TRUE;

    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    public function familyBackground()
    {
        return $this->hasOne(FamilyBackground::class);
    }

    public function identificationNumber()
    {
        return $this->hasOne(IdentificationNumber::class);
    }

    public function workExperience()
    {
        return $this->hasMany(WorkExperiences::class);
    }

    public function training()
    {
        return $this->hasMany(Training::class);
    }

    public function otherInformation()
    {
        return $this->hasMany(OtherInformation::class);
    }

    public function civilServiceEligibility()
    {
        return $this->hasMany(CivilServiceEligibility::class);
    }

    public function references()
    {
        return $this->hasMany(References::class);
    }

    public function employeeProfile()
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function passwordTrail()
    {
        return $this->hasMany(PasswordTrail::class);
    }
}
