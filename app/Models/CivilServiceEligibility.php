<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CivilServiceEligibility extends Model
{
    use HasFactory;

    protected $table = 'civil_service_eligibilities';

    public $fillable = [
        'career_service',
        'rating',
        'date_of_examination',
        'place_of_examination',
        'license'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'uuid');
    }
}
