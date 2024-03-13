<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class FamilyBackground extends Model
{
    use HasFactory;

    protected $table = 'family_backgrounds';

    public $fillable = [
        'spouse',
        'address',
        'zip_code',
        'date_of_birth',
        'occupation',
        'employer',
        'business_address',
        'telephone_no',
        'tin_no',
        'rdo_no',
        'father_first_name',
        'father_middle_name',
        'father_last_name',
        'father_ext_name',
        'mother_first_name',
        'mother_middle_name',
        'mother_last_name',
        'mother_maiden_name',
        'personal_information_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }

    public function fatherName()
    {
        if ($this->father_middle_name === NULL) {
            return $this->father_last_name . ', ' . $this->father_first_name;
        }

        return $this->father_last_name . ', ' . $this->father_first_name . ' ' . $this->father_middle_name;
    }

    public function motherName()
    {
        if ($this->mother_middle_name === NULL) {
            return $this->mother_last_name . ', ' . $this->mother_first_name;
        }

        return $this->mother_last_name . ', ' . $this->mother_first_name . ' ' . $this->mother_middle_name;
    }

    public function decryptData($toEncrypt)
    {
        $encryptedData = null;

        if ($toEncrypt === 'tin_no') {
            $encryptedData = $this->tin_no === null ? $this->tin_no : Crypt::decrypt($this->tin_no);
        } else {
            $encryptedData = $this->rdo_no === null ? $this->rdo_no : Crypt::decrypt($this->rdo_no);
        }

        return $encryptedData;
    }
}
