<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveCredit extends Model
{
    use HasFactory;

    protected $table = 'employee_leave_credits';

    public $fillable = [
        'employee_profile_id',
        'leave_type_id',
        'total_leave_credits'
    ];

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function employeeLeaveCreditLogs()
    {
        return $this->hasMany(EmployeeLeaveCreditLogs::class);
    }
}
