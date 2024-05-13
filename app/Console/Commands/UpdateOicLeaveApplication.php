<?php

namespace App\Console\Commands;

use App\Models\LeaveApplication;
use Illuminate\Console\Command;

class UpdateOicLeaveApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-oic-leave-application';

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
        $date=$currentDateTime->toDateString();
        
            $is_effective = LeaveApplication::where('date_from', $date)
            ->whereNotNull('employee_oic_id')
            ->where('status', 'received')
            ->get();
            foreach ($is_effective as $effective) {
                $effective->is_effective = 1;
                $effective->save();
            }
          $leaveApplications = LeaveApplication::where('date_from', $date)
         ->whereNotNull('employee_oic_id')
         ->where('status', 'received')
         ->get();

        foreach ($leaveApplications as $application) {
            $employeeApplications = LeaveApplication::where('recommending_officer',$application->employee_profile_id)
            ->orwhere('approving_officer',$application->employee_profile_id)
            ->get();
            $employee_id=$application->employee_profile_id;
            $employee_oic=$application->employee_oic_id;
            foreach ($employeeApplications as $empapplication)
            {
                if ($empapplication->status === 'for recommending approval') {
                    $this->updateOfficers($empapplication,$employee_id,$employee_oic);
                } elseif ($empapplication->status === 'for approving approval') {
                    $this->updateApprovingOfficer($empapplication,$employee_id,$employee_oic);
                }
            }

        }
    }

    private function updateOfficers($application,$employee_id,$employee_oic)
    {
        // Update recommending officer
        if ($application->recommending_officer == $employee_id) {
            $application->recommending_officer = $employee_oic;
        }

        // Update approving officer
        if ($application->approving_officer == $employee_id) {
            $application->approving_officer = $employee_oic;
        }

        $application->save();
    }

    private function updateApprovingOfficer($application,$employee_id,$employee_oic)
    {
        // Update approving officer
        if ($application->approving_officer == $employee_id) {
            $application->approving_officer = $employee_oic;
            $application->save();
        }
    }
}
