<?php

namespace App\Models;

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
        'is_study' => 'boolean',
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
        'is_study',
        'is_days_recommended'
    ];

    public function leaveTypeRequirements()
    {
        return $this->hasmany(LeaveTypeRequirement::class);
    }

    public function leaveRequirements() {
        return $this->belongsTo(Requirement::class);
    }

    public function requirements() {
        return $this->belongsToMany(Requirement::class, 'leave_type_requirements', 'leave_type_id', 'leave_requirement_id');
    }

    public function attachments() {
        return $this->belongsToMany(LeaveAttachment::class, 'leave_attachments', 'leave_type_id', 'id');
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
        return $this->hasMany(LeaveTypeLog::class);
    }
}
