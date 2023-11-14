<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveTypeLog extends Model
{
    use HasFactory;
    public $fillable = [
        'action',
        'action_by',
       
       
    ];
    public function leaveType() {
        return $this->belongsTo(LeaveType::class);
    }
    public function employeeProfile() {
        return $this->belongsTo(EmployeeProfile::class);
    }

}
