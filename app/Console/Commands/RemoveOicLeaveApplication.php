<?php

namespace App\Console\Commands;

use App\Models\LeaveApplication;
use Illuminate\Console\Command;

class RemoveOicLeaveApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-oic-leave-application';

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

        $currentDateTime = now();
        $date = $currentDateTime->toDateString();

        $leaveApplications = LeaveApplication::where('date_to', '<', $date)
            ->whereNotNull('employee_oic_id')
            ->where('status', 'received')
            ->where('is_effective', 1)
            ->get();

        foreach ($leaveApplications as $application) {
            $application->is_effective = 0;
            $application->save();

            $employeeApplications = LeaveApplication::where('recommending_officer', $application->employee_oic_id)
                ->orwhere('approving_officer', $application->employee_oic_id)
                ->get();
            $employee_id = $application->employee_profile_id;
            $employee_oic = $application->employee_oic_id;
            foreach ($employeeApplications as $empapplication) {
                if ($empapplication->status === 'for recommending approval') {
                    $this->updateOfficers($empapplication, $employee_id, $employee_oic);
                } elseif ($empapplication->status === 'for approving approval') {
                    $this->updateApprovingOfficer($empapplication, $employee_id, $employee_oic);
                }
            }
        }
    }


    private function updateOfficers($application, $employee_id, $employee_oic)
    {
        // Update recommending officer
        if ($application->recommending_officer == $employee_oic) {
            $application->recommending_officer = $employee_id;
        }

        // Update approving officer
        if ($application->approving_officer == $employee_oic) {
            $application->approving_officer = $employee_id;
        }

        $application->save();
    }

    private function updateApprovingOfficer($application, $employee_id, $employee_oic)
    {
        // Update approving officer
        if ($application->approving_officer == $employee_oic) {
            $application->approving_officer = $employee_id;
            $application->save();
        }
    }
}
