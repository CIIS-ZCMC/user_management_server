<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveTypeRequirementLog extends Model
{
    use HasFactory;
    protected $table = 'leave_requirement_logs';

    protected $primaryKey = 'id';

    protected $fillable = [
        'leave_requirement_id',
        'action_by',
        'action',
    ];

    public $timestamps = TRUE;

    public function leaveTypeRequirements() {
        return $this->belongsTo(RequirementLog::class, 'leave_requirement_id');
    }

    public function employee() {
        return $this->belongsTo(EmployeeProfile::class, 'action_by');
    }
}
