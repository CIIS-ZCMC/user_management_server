<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginTrail extends Model
{
    use HasFactory;

    protected $table = 'login_trails';

    public $fillable = [
        'signin_at',
        'ip_address',
        'device',
        'platform',
        'browser',
        'employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
