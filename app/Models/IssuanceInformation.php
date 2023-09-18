<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssuanceInformation extends Model
{
    use HasFactory;

    protected $table = 'issuance_informations';

    public $fillable = [
        'license_no',
        'govt_issued_id',
        'ctct_issued_date',
        'ctc_issued_at',
        'personal_information_id'
    ];

    public $timestamps = TRUE;
    
    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'uuid');
    }
}
