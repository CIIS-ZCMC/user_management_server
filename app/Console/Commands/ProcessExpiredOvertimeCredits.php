<?php

namespace App\Console\Commands;

use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLog;
use Illuminate\Console\Command;

class ProcessExpiredOvertimeCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-expired-overtime-credits';

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
        $currentDate = date('Y-m-d');

        // Retrieve records where valid_until is past the current date
        $expiredCredits = EmployeeOvertimeCredit::where('valid_until', '<', $currentDate)->where('is_expired', false)->get();

        // Log the expired credits before deleting them
        foreach ($expiredCredits as $expiredCredit) {
            // Create a log entry for each expired credit
            EmployeeOvertimeCreditLog::create([
                'employee_ot_credit_id' => $expiredCredit->id,
                'expired_credit_by_hour' => $expiredCredit->earned_credit_by_hour,
                'action' => 'deduct',
                'reason' => 'expired',
            ]);

            $expiredCredit->update(['is_expired' => true]);
        }
    }
}
