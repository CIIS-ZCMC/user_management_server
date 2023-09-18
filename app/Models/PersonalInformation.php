<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalInformation extends Model
{
    use HasFactory;

    protected $table = 'personal_informations';

    public $fillable = [
        'uuid',
        'first_name',
        'middle_name',
        'last_name',
        'name_extension',
        'years_of_service',
        'name_title',
        'sex',
        'date_of_birth',
        'place_of_birth',
        'civil_status',
        'date_of_marriage',
        'citizenship',
        'height',
        'weight',
        'agency_employee_no'
    ];

    public $timestamps = TRUE;

    public function employeeName()
    {
        $nameExtension = $this->name_extension===NULL?'':' '.$this->name_extion.' ';
        $nameTitle = $this->name_title===NULL?'': ' '.$this->name_title;

        $name = $this->first_name.' '.$this->last_name.$nameExtension.$nameTitle;

        return $name;
    }

    public function contact()
    {
        return $this->hasOne(Contact::class, 'personal_information_id');
    }

    public function familyBackground()
    {
        return $this->hasOne(FamilyBackground::class, 'personal_information_id');
    }

    public function identificationNumber()
    {
        return $this->hasOne(IdentificationNumber::class, 'personal_information_id');
    }

    public function workExperience()
    {
        return $this->hasMany(WorkExperiences::class, 'personal_information_id');
    }

    public function training()
    {
        return $this->hasMany(Training::class, 'personal_information_id');
    }

    public function otherInformation()
    {
        return $this->hasMany(OtherInformation::class, 'personal_information_id');
    }

    public function civilServiceEligibility()
    {
        return $this->hasMany(CivilServiceEligibility::class, 'personal_information_id');
    }

    public function references()
    {
        return $this->hasMany(References::class, 'personal_information_id');
    }

    public function employeeProfile()
    {
        return $this->hasOne(EmployeeProfile::class, 'personal_information_id');
    }

    public function passwordTrail()
    {
        return $this->hasMany(PasswordTrail::class, 'personal_information_id');
    }
}
