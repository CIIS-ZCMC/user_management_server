<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;

    protected $table = 'trainings';

    public $fillable = [
        'title',
        'inclusive_date',
        'hours',
        'type_of_ld',
        'conducted_by',
        'total_hours',
        'personal_information_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }
}
