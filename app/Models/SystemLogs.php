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
        "module",
        "status",
        "remarks",
        "employee_profile_id"
    ];
    
    public $timestamps = TRUE;

    public function employee(){
        return $this->belongsTo(EmployeeProfile::class);
    }
}
