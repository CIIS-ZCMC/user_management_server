<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherInformation extends Model
{
    use HasFactory;

    protected $table = 'other_informations';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    
    public $fillable =  [
        'uuid',
        'hobbies',
        'recognition',
        'organization',
        'personal_information_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'personal_information_id','uuid');
    }
}
