<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeScheduleLog extends Model
{
    use HasFactory;
    
    protected $table = 'employee_schedule_logs';

    protected $primaryKey = 'id';

    protected $fillable = [
        'employee_schedule_id',
        'action_by',
        'action',
    ];
}
