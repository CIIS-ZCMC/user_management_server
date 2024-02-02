<?php

namespace App\Console\Commands;

use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class EmployeeOvertimeCreditResetMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:employee-overtime-credit-reset-monthly';

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
        $employees = EmployeeProfile::all();

        foreach ($employees as $employee) {
            EmployeeOvertimeCredit::where('employee_profile_id', $employee->id)->first()
                ->update([
                    'used_credit_by_hour_annual' => DB::raw('used_credit_by_hour_annual + earn_credit_by_hour'),
                    'earn_credit_by_hour' => 0
                ]);
        }
    }
}
