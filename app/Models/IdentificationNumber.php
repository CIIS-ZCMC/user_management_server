<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdentificationNumber extends Model
{
    use HasFactory;

    protected $table = 'identification_numbers';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'gsis_id_no',
        'pag_ibig_id_no',
        'philhealth_id_no',
        'sss_id_no',
        'prc_id_no',
        'tin_id_no',
        'rdo_no',
        'bank_account_no',
        'personal_information_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'uuid');
    }
}
