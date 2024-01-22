<?php

namespace App\Models;

use App\Http\Resources\LeaveTypeLog;
use App\Models\LeaveTypeLog as ModelsLeaveTypeLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $table = 'leave_types';

    protected $casts = [
        'is_special' => 'boolean',
        'is_active' => 'boolean',
        'is_country' => 'boolean',
        'is_illness' => 'boolean',
        'is_days_recommended' => 'boolean'
    ];

    public $fillable = [
        'name',
        'code',
        'description',
        'period',
        'file_date',
        'month_value',
        'annual_credit',
        'is_special',
        'is_active',
        'is_country',
        'is_illness',
        'is_days_recommended'
    ];

    public function leaveTypeRequirements()
    {
        return $this->hasmany(LeaveTypeRequirement::class);
    }

    public function employeeLeaveCredits()
    {
        return $this->hasMany(EmployeeLeaveCredit::class);
    }

    public function leaveApplications()
    {
        $this->hasMany(LeaveApplication::class);
    }

    public function leaveTypeAttachments()
    {
        return $this->hasMany(LeaveAttachment::class);
    }

    public function logs()
    {
        return $this->hasMany(ModelsLeaveTypeLog::class);
    }
}
