<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requirement extends Model
{
    use HasFactory;
    
    protected $table = 'leave_requirements';
    protected $fillable = [
        'name',
        'description'
    ];
    public function leaveTypeRequirements()
    {
        return $this->hasMany(LeaveTypeRequirement::class, 'leave_requirement_id');
    }
  
    public function logs(){ 
        return $this->hasMany(RequirementLog::class, 'leave_requirement_id');
    }
   
    public function leaveTypes() {
        return $this->belongsToMany(LeaveType::class);
    }

}
