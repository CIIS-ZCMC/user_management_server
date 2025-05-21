<?php

namespace App\Http\Controllers\PayrollHooks;

use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\Holiday;
use App\Models\InActiveEmployee;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeTimeRecordController extends Controller
{
    protected $Working_Days;
    protected $Working_Hours;

    public function __construct()
    {
        $this->Working_Days = 22;
        $this->Working_Hours = 8;
    }

    public function fetch(Request $request)
    {
        ini_set('max_execution_time', 86400); //24 hours compiling time
        $month_of = $request->month_of;
        $year_of = $request->year_of;


        $totalDaysInMonth = Carbon::createFromDate($year_of, $month_of, 1)->daysInMonth;
        $expectedMinutesPerDay = 480;

        $employee_dtr = EmployeeProfile::with([
            'personalInformation',
            'assignedArea' => function ($query) {
                $query->with(['salaryGrade']);
            },
            'dailyTimeRecords' => function ($query) use ($year_of, $month_of) {
                $query->whereYear('dtr_date', $year_of)
                    ->whereMonth('dtr_date', $month_of)
                    ->selectRaw('biometric_id, SUM(total_working_minutes) as total_working_minutes, 
                                               SUM(overtime_minutes) as total_overtime_minutes,
                                               SUM(undertime_minutes) as total_undertime_minutes')
                    ->groupBy('biometric_id');
            },
            'approvedOB' => function ($query) use ($year_of, $month_of, $expectedMinutesPerDay) {
                $query->whereYear('date_from', $year_of)
                    ->whereMonth('date_from', $month_of)
                    ->selectRaw('employee_profile_id, SUM(DATEDIFF(date_to, date_from) + 1) * ? as total_ob_minutes', [$expectedMinutesPerDay])
                    ->groupBy('employee_profile_id');
            },
            'approvedOT' => function ($query) use ($year_of, $month_of, $expectedMinutesPerDay) {
                $query->whereYear('date_from', $year_of)
                    ->whereMonth('date_from', $month_of)
                    ->selectRaw('employee_profile_id, SUM(DATEDIFF(date_to, date_from) + 1) * ? as total_ot_minutes', [$expectedMinutesPerDay])
                    ->groupBy('employee_profile_id');
            },
            'receivedLeave' => function ($query) use ($year_of, $month_of, $expectedMinutesPerDay) {
                $query->whereYear('date_from', $year_of)
                    ->whereMonth('date_from', $month_of)
                    ->selectRaw('employee_profile_id, SUM(DATEDIFF(date_to, date_from) + 1) * ? as total_leave_minutes', [$expectedMinutesPerDay])
                    ->groupBy('employee_profile_id');
            },
            'dtrInvalidEntry' => function ($query) use ($year_of, $month_of) {
                $query->whereYear('dtr_date', $year_of)
                    ->whereMonth('dtr_date', $month_of);
            },
            'schedule' => function ($query) use ($year_of, $month_of) {
                $query->whereYear('date', $year_of)
                    ->whereMonth('date', $month_of);
            },
            'employeeDtr' => function ($query) use ($year_of, $month_of) {
                $query->whereYear('dtr_date', $year_of)
                    ->whereMonth('dtr_date', $month_of);
            },
            'leaveApplications' => function ($query) use ($year_of, $month_of) {
                $query->where(function ($query) use ($year_of, $month_of) {
                    // Include records where the leave period starts or ends within the month
                    $query->whereYear('date_from', $year_of)
                        ->whereMonth('date_from', $month_of)
                        ->orWhere(function ($query) use ($year_of, $month_of) {
                        $query->whereYear('date_to', $year_of)
                            ->whereMonth('date_to', $month_of);
                    });
                })->where('status', 'received');
            },
            'nigthDuties' => function ($query) use ($year_of, $month_of) {
                $query->whereYear('dtr_date', $year_of)
                    ->whereMonth('dtr_date', $month_of);
            }
        ])->where('id', 147)->get();

        $holiday = Holiday::whereRaw("LEFT(month_day, 2) = ?", [str_pad($month_of, 2, '0', STR_PAD_LEFT)])->get();

        $data = $employee_dtr->map(function ($employee) use ($year_of, $month_of, $totalDaysInMonth, $request, $expectedMinutesPerDay, $holiday) {
            $biometric_id = $employee->biometric_id;

            $NoOfInvalidEntry = [];
            $nightDifferentials = [];

            //  Skip if employee area is not assigned
            if (!$employee->assignedArea)
                return null;


            //  Handle payroll periodsJ
            $payrollPeriodStart = 1;
            $payrollPeriodEnd = $totalDaysInMonth;

            //  Pre-fetch counts and related values
            $scheduleCount = $employee->schedule->count();
            $NoOfInvalidEntry = $employee->dtrInvalidEntry->count();
            $receivedLeave = $employee->receivedLeave;

            //  Salary details
            $salary_grade = $employee->assignedArea->salary_grade_id;
            $salary_step = $employee->assignedArea->salary_grade_step;

            //  Extract totals safely with null coalescing (CALCULATED VALUES GROUP)
            $totalWorkingMinutes = $employee->dailyTimeRecords->first()->total_working_minutes ?? 0;
            $totalOBMinutes = $employee->approvedOB->first()->total_ob_minutes ?? 0;
            $totalOTMinutes = $employee->approvedOT->first()->total_ot_minutes ?? 0;
            $totalLeaveMinutes = (int) $employee->receivedLeave->first()->total_leave_minutes ?? 0;
            $totalOvertimeMinutes = (int) $employee->dailyTimeRecords->first()->total_overtime_minutes ?? 0;
            $totalUnderTimeMinutes = (int) $employee->dailyTimeRecords->first()->total_undertime_minutes ?? 0;

            //  Total working details
            $totalMinutes = (int) $totalWorkingMinutes + $totalLeaveMinutes;
            $totalWorkingHours = $totalMinutes / 60;
            $noOfPresentDays = round($totalMinutes / $expectedMinutesPerDay, 1);

            //  Total working details with leave
            $totalWorkingMinutesWithLeave = $totalMinutes + $totalOBMinutes + $totalOTMinutes;
            $totalWorkingHoursWithLeave = $totalWorkingMinutesWithLeave / 60;
            $noOfPresentDaysWithLeave = round($totalWorkingMinutesWithLeave / $expectedMinutesPerDay, 1);

            //  Leave calculations
            $noOfLeaveWoPay = $receivedLeave->where('without_pay', true)->count();
            $noOfLeaveWPay = $receivedLeave->where('without_pay', false)->count();

            //  Absence and day-off calculations
            $noOfAbsences = $scheduleCount - $noOfPresentDays;
            $noOfDayOff = $totalDaysInMonth - $scheduleCount;

            if ($employee->employmentType->name === "Job Order") {
                if ($request->first_half) {
                    $payrollPeriodStart = 1;
                    $payrollPeriodEnd = 15;
                } elseif ($request->second_half) {
                    $payrollPeriodStart = 16;
                    $payrollPeriodEnd = $totalDaysInMonth;
                }
            }
            //  Holiday Pay(Regular Employee)
            $holidayPay = 0;
            if ($employee->employmentType->name !== "Job Order") {
                $dtrDates = $employee->employeeDtr
                    ->map(function ($dtr) {
                        return Carbon::parse($dtr->dtr_date)->format('m-d');
                    })
                    ->toArray();

                $scheduleDates = $employee->schedule
                    ->map(function ($schedule) {
                        return Carbon::parse($schedule->date)->format('m-d');
                    })
                    ->toArray();
                // Loop through holidays in the current month
                foreach ($holiday as $holidayRecord) {
                    $holidayMonthDay = $holidayRecord->month_day;

                    // Check if the employee has a schedule on the holiday
                    $isScheduledOnHoliday = in_array($holidayMonthDay, $scheduleDates);

                    // Check if there is no DTR entry on the holiday
                    $hasNoDtrOnHoliday = !in_array($holidayMonthDay, $dtrDates);

                    // If scheduled on a holiday but has no DTR, add holiday pay
                    if ($isScheduledOnHoliday && $hasNoDtrOnHoliday) {
                        $holidayPay++;
                    }
                }
            }

            //  Map Salary Grade
            $salaryGrade = $employee->assignedArea->salaryGrade;
            $mapTranch = [
                'first' => 'one',
                'second' => 'two',
                'third' => 'three',
                'fourth' => 'four',
                'fifth' => 'five',
                'sixth' => 'six',
                'seventh' => 'seven',
                'eighth' => 'eight'

            ];

            $stepKey = $mapTranch[$salaryGrade['tranch']] ?? null;
            $salary_details = [
                'salary_grade_number' => $salaryGrade['salary_grade_number'],
                'tranch' => $salaryGrade['tranch'],
                'amount' => $stepKey ? $salaryGrade[$stepKey] : null,
            ];

            //  Salary rate computations (SALARY VALUES GROUP)
            $basicSalary = $salary_details['amount'];
            $rates = $this->salaryRate($basicSalary);
            $initialNetPay = $this->netPay($basicSalary, $noOfPresentDaysWithLeave); //Initial pay, calculate only number present days
            $absentRate = $this->absentRate($noOfAbsences, $rates['Daily']);
            $undertimeRate = $this->undertimeRate($totalUnderTimeMinutes, $rates['Minutes']);

            //  Calculate Holiday Pay (in monetary terms)
            $ratePerDay = $rates['Daily'];
            $holidayPayRate = $ratePerDay * $holidayPay;

            //  Overall Net Pay (Initial Net Pay + Holiday Pay)
            $netPay = $initialNetPay + $holidayPayRate;

            //  This function Return True or False
            $salaryLimit = $employee->employmentType->name === "Job Order" ? 2500 : 5000;
            $outOfPayroll = $netPay < $salaryLimit;

            $first_in = $employee->nigthDuties->first()->first_in ?? null;
            $first_out = $employee->nigthDuties->first()->first_out ?? null;
            $nightDifferentials[] = $this->getNightDifferentialHours($first_in, $first_out, $biometric_id, [], $employee->schedule);

            //  Get Night Diff base on ID
            $nightDiff = array_values(array_filter($nightDifferentials, function ($row) use ($biometric_id) {
                return $row['biometric_id'] ?? null === $biometric_id;
            }));

            //  Get Absent dates
            $absent_date = $employee->getAbsentDates($year_of, $month_of);

            $is_inactive = InActiveEmployee::where('employee_id', $employee->employee_id)->exists();

            return [
                'id' => $employee->id,
                'employee_number' => $employee->employee_id,
                'biometric_id' => $biometric_id,
                'personal_information' => $employee->personalInformation,
                'designation' => $employee->findDesignation(),
                'employment_type' => $employee->employmentType,
                'assigned_area' => $employee->assignedArea->findDetails(),
                'hired' => $employee->date_hired,
                'salary_step' => $salary_step,
                'salary_grade' => $salary_grade,
                'is_inactive' => $is_inactive,
                'employee_leave_credits' => $employee->employeeLeaveCredits,
                'leave_applications' => $employee->leaveApplications->isNotEmpty() ? [
                    'country' => $employee->leaveApplications->first()->country ?? null,
                    'city' => $employee->leaveApplications->first()->city ?? null,
                    'from' => $employee->leaveApplications->first()->date_from ?? null,
                    'to' => $employee->leaveApplications->first()->date_to ?? null,
                    'leave_type' => LeaveType::find($employee->leaveApplications->first()->leave_type_id ?? null)->name ?? null,
                    'without_pay' => $employee->leaveApplications->first()->without_pay ?? null,
                    'dates_covered' => $this->getDateIntervals($employee->leaveApplications->first()->date_from ?? null, $employee->leaveApplications->first()->date_to ?? null),
                ] : [],

                'payroll' => [
                    'payroll_period' => "{$payrollPeriodStart} - {$payrollPeriodEnd}",
                    'from' => $payrollPeriodStart,
                    'to' => $payrollPeriodEnd,
                    'month' => $month_of,
                    'year' => $year_of,
                ],

                'time_record' => [
                    'base_salary' => $basicSalary,
                    'rates' => $rates,
                    'initial_net_pay' => $initialNetPay,
                    'net_pay' => $netPay,
                    'time_deductions' => [
                        'absent_rate' => $absentRate,
                        'undertime_rate' => $undertimeRate,
                    ],
                    'is_out' => $outOfPayroll,
                    "night_differentials" => $nightDiff,

                    'total_working_minutes' => $totalMinutes,
                    'total_working_minutes_with_leave' => $totalWorkingMinutesWithLeave,
                    'total_working_hours' => $totalWorkingHours,
                    'total_working_hours_with_leave' => $totalWorkingHoursWithLeave,
                    'total_overtime_minutes' => $totalOvertimeMinutes,
                    'total_undertime_minutes' => $totalUnderTimeMinutes,
                    'total_official_business_minutes' => $totalOBMinutes,
                    'total_official_time_minutes' => $totalOTMinutes,
                    'total_leave_minutes' => $totalLeaveMinutes,
                    'no_of_present_days' => $noOfPresentDays,
                    'no_of_present_days_with_leave' => $noOfPresentDaysWithLeave,
                    'no_of_leave_wo_pay' => $noOfLeaveWoPay,
                    'no_of_leave_w_pay' => $noOfLeaveWPay,
                    'no_of_absences' => $noOfAbsences,
                    'no_of_invalid_entry' => $NoOfInvalidEntry,
                    'no_of_day_off' => $noOfDayOff,
                    'absent_dates' => $absent_date,
                    'schedule' => $scheduleCount,
                ],
            ];
        });

        return $data->filter();
    }

    public function getDateIntervals($from, $to)
    {
        $dates_Interval = [];
        $from = strtotime($from);
        $to = strtotime($to);
        while ($from <= $to) {
            $dates_Interval[] = date('Y-m-d', $from);
            $from = strtotime('+1 day', $from);
        }

        return $dates_Interval;
    }

    public function getNightDifferentialHours($startTime, $endTime, $biometric_id, $wBreak, $DaySchedule)
    {
        if (count($wBreak) == 0 && $startTime && $endTime && count($DaySchedule)) {

            // Convert start and end times to DateTime objects
            $startTime = new \DateTime($startTime);
            $endTime = new \DateTime($endTime);

            // Ensure that the end time is after the start time
            if ($endTime <= $startTime) {
                $endTime->modify('+1 day');
            }

            $totalMinutes = 0;
            $totalHours = 0;
            $details = [];

            // Loop through each day in the range
            $current = clone $startTime;

            while ($current <= $endTime) {
                // Calculate night period overlaps for the current day
                $nightStart = (clone $current)->setTime(18, 0, 0);
                $midnight = (clone $current)->setTime(0, 0, 0)->modify('+1 day');
                $nightEnd = (clone $current)->setTime(6, 0, 0)->modify('+1 day');

                // Calculate overlap with night period for the first day (6 PM to 12 AM)
                $overlapStart = max($current, $nightStart);
                $overlapEnd = min($endTime, $midnight);

                // Calculate total minutes and hours for the first day overlap (6 PM to 12 AM)
                if ($overlapStart <= $overlapEnd) {
                    $interval = $overlapStart->diff($overlapEnd);
                    $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
                    $hours = $interval->h + ($interval->i / 60);

                    $details[] = [
                        'minutes' => $minutes,
                        'hours' => round($hours, 0),
                        'date' => $overlapStart->format('Y-m-d'),
                        'period' => '6 PM to 12 AM',
                        'biometric_id' => $biometric_id,
                    ];

                    $totalMinutes += $minutes;
                    $totalHours += $hours;
                }

                // Check if there is an overlap into the next day (12 AM to 6 AM)
                if ($endTime > $midnight) {
                    $nextDayOverlapStart = $midnight;
                    $nextDayOverlapEnd = min($endTime, $nightEnd);

                    $interval = $nextDayOverlapStart->diff($nextDayOverlapEnd);
                    $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
                    $hours = $interval->h + ($interval->i / 60);

                    $details[] = [
                        'minutes' => $minutes,
                        'hours' => round($hours, 0),
                        'date' => $nextDayOverlapStart->format('Y-m-d'),
                        'period' => '12 AM to 6 AM',
                        'biometric_id' => $biometric_id,
                    ];

                    $totalMinutes += $minutes;
                    $totalHours += $hours;
                }

                // Move to the next day
                $current->modify('+1 day')->setTime(0, 0, 0);
            }

            if ($totalMinutes && $totalHours) {
                return [
                    'biometric_id' => $biometric_id,
                    'total_minutes' => $totalMinutes,
                    'total_hours' => round($totalHours, 0),
                    'details' => $details,
                ];
            }
        }

        return null; // Return null if the conditions are not met
    }

    public function salaryRate($basic_Salary)
    {
        $per_day = $basic_Salary / $this->Working_Days;

        // Calculate the per-hour rate
        $per_hour = $per_day / $this->Working_Hours;

        // Calculate the per-minute rate
        $per_minute = $per_hour / 60;

        // Calculate the per-week rate (assuming 5 workdays in a week)
        $per_week = $per_day * 5;

        // Return rates, rounded to 3 decimal places
        return [
            'Weekly' => round($per_week, 2) ?? 0,
            'Daily' => round($per_day, 2) ?? 0,
            'Hourly' => round($per_hour, precision: 2) ?? 0,
            'Minutes' => round($per_minute, 2) ?? 0,
        ];
    }


    public function absentRate($number_of_absences, $daily_rate)
    {
        return round($daily_rate * $number_of_absences, 2);
    }

    public function undertimeRate($total_undertime, $minute_rate)
    {
        return $total_undertime * $minute_rate;
    }

    public function netPay($base_salary, $total_present_days)
    {
        $rate = $base_salary / $this->Working_Days;
        return $rate * $total_present_days;
    }
}
