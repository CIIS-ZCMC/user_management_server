<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'mother_ext_name',
        'personal_information_id'
    ];

    public $timestamps = TRUE;
    
    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }

    public function fatherName()
    {
        $extName = $this->father_ext_name===null?'':$this->father_ext_name;
        return $this->father_first_name.' '.$this->father_last_name.' '.$extName;
    }

    public function motherName()
    {
        $extName = $this->mother_ext_name===null?'':$this->mother_ext_name;
        return $this->mother_first_name.' '.$this->mother_last_name.' '.$extName;
    }

    public function decryptData($toEncrypt)
    {
        $encryptedData = null;

        if($toEncrypt === 'tin_no'){
            $encryptedData = $this->tin_no;
        }else{
            $encryptedData = $this->rdo_no;
        }

        return openssl_decrypt($encryptedData, env("ENCRYPT_DECRYPT_ALGORITHM"), env("DATA_KEY_ENCRYPTION"), 0, substr(md5(env("DATA_KEY_ENCRYPTION")), 0, 16));
    }
}
