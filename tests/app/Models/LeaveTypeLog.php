<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveTypeLog extends Model
{
    use HasFactory;

    public $fillable = [
        'leave_type_id',
        'action_by',
        'action'
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'action_by');
    }

}
