<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_profile_schedule';

    protected $primaryKey = 'id';

    protected $fillable = [
        'employee_profile_id',
        'schedule_id'
    ];

    public $timestamps = false;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function employeeProfile()
    {
        return $this->belongsToMany(EmployeeProfile::class);
    }
}
