<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedLoginTrail extends Model
{
    use HasFactory;

    protected $table = 'failed_login_trails';

    public $fillable = [
        'employee_id',
        'employee_profile_id',
        'message'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
