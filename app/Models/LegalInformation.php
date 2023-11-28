<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalInformation extends Model
{
    use HasFactory;

    protected $table = 'legal_informations';

    public $fillable = [
        'legal_iq_id',
        'personal_information_id',
        'answer',
        'details'
    ];

    public $timestamps = TRUE;

    public function legalInformationQuestion()
    {
        return $this->belongsTo(LegalInformationQuestion::class, 'legal_iq_id');
    }

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }
}
