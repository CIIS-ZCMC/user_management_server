<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoluntaryWork extends Model
{
    use HasFactory;

    protected $table = 'voluntary_works';

    public $fillable = [
        'personal_information_id',
        'name_address_organization',
        'inclusive_from',
        'inclusive_to',
        'hours',
        'position'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }
}
