<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkExperience extends Model
{
    use HasFactory;

    protected $table = 'work_experiences';

    public $fillable = [
        'date_from',
        'date_to',
        'position_title',
        'appointment_status',
        'salary',
        'salary_grade_and_step',
        'company',
        'government_office',
        'is_voluntary_work',
        'personal_information_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'uuid');
    }
}
