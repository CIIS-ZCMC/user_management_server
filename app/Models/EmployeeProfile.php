<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $table = 'employee_profiles';

    public $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'sex',
        'dob',
        'nationality',
        'religion',
        'dialect'
    ];

    protected $timestamps = TRUE;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
