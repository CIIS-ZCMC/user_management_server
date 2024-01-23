<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeaveCreditLogs extends Model
{
    use HasFactory;

    private $table = "employee_leave_credit_logs";

    public $fillable = [
        'employee_leave_credit_id',
        'previous_credit',
        'leave_credits',
        'reason'
    ];

    public $timestamps = true;

    public function leaveTypeCredit()
    {
        return $this->belongsTo(EmployeeLeaveCredit::class);
    }
}
