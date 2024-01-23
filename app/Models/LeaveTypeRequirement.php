<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveTypeRequirement extends Model
{
    protected $table = 'leave_type_requirement';

    public $fillable = [
        'leave_type_id',
        'leave_requirement_id'
    ];

    public $timestamps = true;

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveRequirement()
    {
        return $this->belongsTo(LeaveTypeRequirement::class);
    }
}
