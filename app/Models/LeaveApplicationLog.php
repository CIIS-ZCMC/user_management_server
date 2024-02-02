<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplicationLog extends Model
{
    use HasFactory;

    protected $table = 'leave_application_logs';

    public $fillable = [
        'action_by',
        'leave_application_id',
        'action',

    ];

    public function leaveApplications()
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'action_by');
    }
}
