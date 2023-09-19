<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class References extends Model
{
    use HasFactory;

    protected $table = 'references';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'name',
        'address',
        'contact_no',
        'personal_information_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class, 'uuid');
    }
}
