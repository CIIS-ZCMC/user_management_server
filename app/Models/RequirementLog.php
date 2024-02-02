<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequirementLog extends Model
{
    use HasFactory;
    protected $table = 'leave_requirement_logs';
    protected $fillable = [
        'leave_requirement_id',
        'action_by',
        'action'
    ];
    public function requirement() {
        return $this->belongsTo(Requirement::class, 'leave_requirement_id');
    }

    public function employee() {
        return $this->belongsTo(EmployeeProfile::class, 'action_by');
    }
}
