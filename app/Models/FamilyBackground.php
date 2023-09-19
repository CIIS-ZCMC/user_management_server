<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyBackground extends Model
{
    use HasFactory;

    protected $table = 'family_backgrounds';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

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
        return $this->belongsTo(PersonalInformation::class, 'uuid');
    }
}
