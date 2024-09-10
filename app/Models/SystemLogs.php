<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLogs extends Model
{
    use HasFactory;

    protected $table = 'system_logs';

    protected $fillable = [
        "action",
        "module_id",
        "status",
        "remarks",
        "ip_address",
        "employee_profile_id",
        "execution_time"
    ];
    
    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
