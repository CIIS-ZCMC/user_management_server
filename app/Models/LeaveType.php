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
    ];
    
    public $fillable = [
        'is_special',
        'is_active',
        'name',
        'description',
        'leave_credit_id',
        'period',
        'file_date',
        
       
    ];

        public function leave_credit(){
             return $this->belongsTo(LeaveCredit::class, 'leave_credit_id', 'id');
        }
        public function requirements(){ 
            return $this->belongsToMany(Requirement::class, 'leave_type_requirements', 'leave_type_id', 'requirement_id');
        }
        public function employeeLeaveCredits()
        {
            return $this->hasMany(EmployeeLeaveCredit::class);
        }
        public function logs()
        {
            return $this->hasMany(ModelsLeaveTypeLog::class);
        }
        public function attachments()
        {
            
            return $this->hasMany(LeaveAttachment::class);
        }
}
