<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    use HasFactory;

    protected $table = 'childs';

    public $fillable = [
        'personal_information_id',
        'last_name',
        'first_name',
        'middle_name',
        'gender',
        'birthdate'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }

    public function name()
    {
        return $this->middle_name === null?$this->first_name.' '.$this->last_name:$this->first_name.' '.$this->middle_name.' '.$this->last_name;
    }
}
