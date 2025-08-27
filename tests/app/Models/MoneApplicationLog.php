<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoneApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'mone_application_logs';
    public $fillable = [
        'monetization_application_id',
        'action_by_id',
        'action',


    ];
    public function leaveApplications()
    {
        return $this->belongsTo(LeaveApplication::class, 'leave_application_id');
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'action_by_id');
    }
}
