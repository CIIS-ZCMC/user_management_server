<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function is24HoursSchedule()
    {
        $timeShift = $this->timeShift; // Assuming 'timeShift' is the relationship to your TimeShift model
        if (!$timeShift) {
            return false; // If no time shift is associated, return false
        }

        // Parse the time shift values
        $firstIn = Carbon::parse($timeShift->first_in);
        $firstOut = Carbon::parse($timeShift->first_out);
        $secondIn = $timeShift->second_in ? Carbon::parse($timeShift->second_in) : null;
        $secondOut = $timeShift->second_out ? Carbon::parse($timeShift->second_out) : null;

        // Calculate the durations of the first segment (if second segment exists)
        $duration1 = $firstOut->diffInMinutes($firstIn);
        // Calculate the durations of the second segment (if exists)
        $duration2 = ($secondIn && $secondOut) ? $secondOut->diffInMinutes($secondIn) : 0;

        // Total duration should be 24 hours (1440 minutes) or more
        return ($duration1 + $duration2) >= 1440;
    }

    public function countWeekEnd($year, $month)
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $weekendCount = 0;

        // Loop through each day of the month and count weekends
        while ($startOfMonth <= $endOfMonth) {
            if ($startOfMonth->isWeekend()) {
                $weekendCount++;
            }
            $startOfMonth->addDay(); // Move to the next day
        }

        return $weekendCount;
    }
}
