<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginTrail extends Model
{
    use HasFactory;

    protected $table = 'login_trails';

    public $fillable = [
        'uuid',
        'signin_datetime',
        'ip_address',
        'employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id', 'uuid');
    }
}
