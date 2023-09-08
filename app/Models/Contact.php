<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'contacts';

    public $fillable = [
        'email',
        'phone_number',
        'tele_number',
        'emergency_contact',
    ];

    protected $timestamps = TRUE;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
