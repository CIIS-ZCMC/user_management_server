<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequirementLog extends Model
{
    use HasFactory;
    protected $table = 'requirement_logs';
    protected $fillable = [
        'action_by_id',
        'action'
    ];
    public function requirement() {
        return $this->belongsTo(Requirement::class);
    }
    public function employeeProfile() {
        return $this->belongsTo(EmployeeProfile::class, 'action_by');
    }
}
