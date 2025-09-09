<?php

namespace App\Repositories\Schedule;

use Illuminate\Database\Eloquent\Collection;

use App\Contracts\Schedule\ScheduleRepositoryInterface;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function index($user): Collection
    {
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $monthPattern = str_pad($currentMonth, 2, '0', STR_PAD_LEFT);
        
        return EmployeeSchedule::with(['schedule' => function($query) use ($currentYear, $monthPattern) {
                $query->where('date', 'LIKE', $currentYear . '-' . $monthPattern . '-%')
                      ->with('timeShift');
            }])
            ->where('employee_profile_id', $user->id)
            ->whereHas('schedule', function($query) use ($currentYear, $monthPattern) {
                $query->where('date', 'LIKE', $currentYear . '-' . $monthPattern . '-%');
            })
            ->get()
            ->sortByDesc(function($employeeSchedule) {
                return $employeeSchedule->schedule->date;
            })
            ->values();
    }
}
