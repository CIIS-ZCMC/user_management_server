<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalInformation extends Model
{
    use HasFactory;

    protected $table = 'legal_informations';

    public $fillable = [
        'employee_profile_id',
        'details',
        'answer',
        'legal_iq_id'
    ];

    public $timestamps = TRUE;

    public function legalInformationQuestion()
    {
        return $this->belongsTo(LegalInformationQuestion::class);
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
