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
        'ctc_issued_date',
        'ctc_issued_at',
        'person_administrative_oath',
        'employee_profile_id'
    ];

    public $timestamps = TRUE;
    
    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
