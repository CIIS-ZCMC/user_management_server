<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalInformationQuestion extends Model
{
    use HasFactory;

    protected $table = 'legal_information_questions';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'content_question',
        'is_sub_question',
        'legal_iq_id'
    ];

    public $timestamps = TRUE;

    public function legalInformation()
    {
        return $this->hasMany(LegalInformation::class, 'legal_iq_id', 'uuid');
    }
}
