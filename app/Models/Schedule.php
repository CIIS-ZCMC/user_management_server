<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use App\Models\TimeShift;
use App\Models\Holiday;
use App\Models\EmployeeProfile;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'schedules';

    protected $primaryKey = 'id';

    protected $fillable = [
        'date',
        'is_weekend',
        'status',
        'remarks',
        'time_shift_id',
        'holiday_id',
    ];

    protected $softDelete = true;

    public $timestamps = true;

    public function timeShift()
    {
        return $this->belongsTo(TimeShift::class);
    }

    public function holiday()
    {
        return $this->belongsTo(Holiday::class);
    }

    public function employee()
    {
        return $this->belongsToMany(EmployeeProfile::class, 'employee_profile_schedule')->withPivot('employee_profile_id');
    }

    public function isOnCall()
    {
        return $this->belongsToMany(EmployeeProfile::class, 'employee_profile_schedule');
    }

    public function employeeSchedule()
    {
        return $this->belongsTo(EmployeeSchedule::class);
    }
}
