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
        'place_of_birth',
        'civil_status',
        'religion',
        'citizenship',
        'country',
        'height',
        'weight',
        'blood_type',
        'religion'
    ];

    public $timestamps = TRUE;

    public function employeeName()
    {
        $nameExtension = $this->name_extension === NULL || $this->name_extension === "" ? ' ' : " " . $this->name_extension;
        $nameTitle = $this->name_title === NULL || $this->name_title === "" ? ' ' : ', ' . $this->name_title;
        $middleName = $this->middle_name === NULL || $this->middle_name === '' ? '' : $this->middle_name[0] . '. ';


        $name = $this->first_name . ' ' . $middleName . $this->last_name . $nameExtension . $nameTitle;

        return $name;
    }
    public function fullName()
    {
        $nameExtension = $this->name_extension === NULL || $this->name_extension === "" ? ' ' : " " . $this->name_extension;
        $middleName = $this->middle_name === NULL || $this->middle_name === '' ? '' : $this->middle_name[0] . '. ';


        $name = $this->first_name . ' ' . $middleName . $this->last_name . $nameExtension;

        return $name;
    }

    public function nameWithSurnameFirst()
    {
    
        $nameExtension = $this->name_extension === NULL ? '' : $this->name_extension;
        if ($this->middle_name === NULL) {
            return $this->last_name . ', ' . $this->first_name. ','. $nameExtension;
        }

        return $this->last_name . ', ' . $this->first_name . ' ' . $this->middle_name. ','. $nameExtension;
    }

    public function name()
    {
        $nameExtension = $this->name_extension === NULL ? '' : $this->name_extension;

        if ($this->middle_name === NULL) {
            return $this->last_name . ', ' . $this->first_name. ','. $nameExtension;
        }

        $name = $this->last_name . ', ' . $this->first_name.' ' . $this->middle_name. ','. $nameExtension;

        return $name;
    }

    public function familyBackground()
    {
        return $this->hasOne(FamilyBackground::class);
    }

    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    public function children()
    {
        return $this->hasMany(Child::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function educationalBackground()
    {
        return $this->hasMany(EducationalBackground::class);
    }

    public function voluntaryWork()
    {
        return $this->hasMany(VoluntaryWork::class);
    }

    public function workExperience()
    {
        return $this->hasMany(WorkExperience::class);
    }

    public function training()
    {
        return $this->hasMany(Training::class);
    }

    public function civilServiceEligibility()
    {
        return $this->hasMany(CivilServiceEligibility::class);
    }

    public function identificationNumber()
    {
        return $this->hasOne(IdentificationNumber::class);
    }

    public function legalInformation()
    {
        return $this->hasMany(LegalInformation::class);
    }

    public function otherInformation()
    {
        return $this->hasMany(OtherInformation::class);
    }

    public function references()
    {
        return $this->hasMany(Reference::class);
    }

    public function employeeProfile()
    {
        return $this->hasOne(EmployeeProfile::class);
    }
    public function religion()
    {
        return $this->belongsTo(Religion::class);
    }
}