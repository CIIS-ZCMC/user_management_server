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
        'inclusive_from',
        'inclusive_to',
        'hours',
        'type_of_ld',
        'conducted_by',
        'total_hours',
        'personal_information_id',
        'attachment',
        'is_request',
        'approved_at',
        'attachment'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }
}
