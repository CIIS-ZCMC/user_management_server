<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalInformationQuestion extends Model
{
    use HasFactory;

    protected $table = 'legal_information_questions';

    public $fillable = [
        'order_by',
        'content_question',
        'has_detail',
        'has_yes_no',
        'has_date',
        'has_sub_question',
        'legal_iq_id'
    ];

    public $timestamps = TRUE;

    public function legalInformation()
    {
        return $this->hasMany(LegalInformation::class);
    }

    public function subQuestions()
    {
        return $this->hasMany(LegalInformationQuestion::class, 'legal_iq_id');
    }
}
