<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'leave_application_logs';

    public $fillable = [
        'action_by_id',
        'leave_application_id',
        'action',
        'date',
        'time',

    ];
        public function leave_application(){
            return $this->belongsTo(LeaveApplication::class);
        }
        public function employeeProfile() {
            return $this->belongsTo(EmployeeProfile::class, 'action_by_id');
        }
    }
