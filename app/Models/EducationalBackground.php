<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalBackground extends Model
{
    use HasFactory;

    protected $table = 'educational_backgrounds';

    public $fillable = [
        'personal_information_id',
        'level',
        'name',
        'degree_course',
        'year_graduated',
        'highest_grade',
        'inclusive_from',
        'inclusive_to',
        'academic_honors',
        'attachment',
        'is_request',
        'approved_at',
        'attachment'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class)->where('is_request', 0);
    }
}
