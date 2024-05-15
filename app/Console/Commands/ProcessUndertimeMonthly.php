<?php

namespace App\Console\Commands;

use App\Models\DailyTimeRecords;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\LeaveType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessUndertimeMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-undertime-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the first day of last month
        $firstDayOfLastMonth = now()->subMonth()->startOfMonth();
        // Get the first day of current month
        $firstDayOfCurrentMonth = now()->startOfMonth();

        $undertimeLastMonth = DailyTimeRecords::select('employee_profiles.id', DB::raw('SUM(undertime_minutes) as total_undertime_last_month'))
            ->join('employee_profiles', 'daily_time_records.biometric_id', '=', 'employee_profiles.biometric_id')
            ->where('dtr_date', '>=', $firstDayOfLastMonth)
            ->where('dtr_date', '<', $firstDayOfCurrentMonth)
            ->where('undertime_minutes', '>', 0)
            ->where('employee_profiles.employment_type_id', '!=', 5)
            ->groupBy('employee_profiles.id')
            ->get();

        $undertimeByEmployee = [];

        foreach ($undertimeLastMonth as $undertime) {
            $undertimeByEmployee[$undertime->id] = $undertime->total_undertime_last_month;
            $undertimeMinutes = $undertime->total_undertime_last_month;
            $employeeProfileId = $undertime->id;
            $deduction = $undertimeMinutes / 480;

            $vacationLeaveType = LeaveType::where('name', 'Vacation Leave')->first();
            $vlLeaveTypeId = $vacationLeaveType->id;
            $employee_credit_vl = EmployeeLeaveCredit::where('employee_profile_id', $employeeProfileId)
                ->where('leave_type_id', $vlLeaveTypeId)->first();

            EmployeeLeaveCredit::where('leave_type_id', $vlLeaveTypeId)
                ->where('employee_profile_id', $employeeProfileId)
                ->decrement('total_leave_credits', $deduction);

            $previous_credit_vl = $employee_credit_vl->total_leave_credits;

            EmployeeLeaveCreditLogs::create([
                'previous_credit' => $previous_credit_vl,
                'leave_credits' => $deduction,
                'reason' => 'undertime',
                'action' => 'deduct'
            ]);
        }
    }
}
