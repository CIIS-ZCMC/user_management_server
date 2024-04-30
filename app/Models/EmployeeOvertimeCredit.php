<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertimeCredit extends Model
{
    use HasFactory;
    protected $table = 'employee_overtime_credits';

    public $fillable = [
        'employee_profile_id',
        'earned_credit_by_hour',
        'used_credit_by_hour',
        'max_credit_monthly',
        'max_credit_annual',
        'valid_until',
    ];
    protected $casts = [
        'earned_credit_by_hour' => 'float', // Cast the attribute to a float

    ];

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    
    public function logs()
    {
        return $this->hasMany(EmployeeOvertimeCreditLog::class, 'employee_ot_credit_id');
    }
}
