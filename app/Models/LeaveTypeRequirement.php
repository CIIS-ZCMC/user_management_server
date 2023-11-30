<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveTypeRequirement extends Model
{
    protected $table = 'leave_type_requirements';
    
    // public function leaveType()
    // {
    //     return $this->belongsTo(LeaveType::class, 'leave_type_id');
    // }

    // public function requirement()
    // {
    //     return $this->hasMany(Requirement::class, 'requirement_id');
    // }
}
