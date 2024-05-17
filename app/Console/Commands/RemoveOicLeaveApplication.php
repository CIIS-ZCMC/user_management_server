<?php

namespace App\Console\Commands;

use App\Models\CtoApplication;
use App\Models\LeaveApplication;
use App\Models\MonetizationApplication;
use App\Models\OfficialBusiness;
use App\Models\OfficialTime;
use App\Models\OvertimeApplication;
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
            $employee_id = $application->employee_profile_id;
            $employee_oic = $application->employee_oic_id;

            $employeeApplications = LeaveApplication::where('recommending_officer', $application->employee_oic_id)
                ->orwhere('approving_officer', $application->employee_oic_id)
                ->orwhere('hrmo_officer', $application->employee_oic_id)
                ->get();
            foreach ($employeeApplications as $empapplication) {
                if ($empapplication->status === 'for recommending approval') {
                    $this->updateOfficers($empapplication, $employee_id, $employee_oic);
                } elseif ($empapplication->status === 'for approving approval') {
                    $this->updateApprovingOfficer($empapplication, $employee_id, $employee_oic);
                }
                elseif ($empapplication->status === 'applied') {
                    $this->updateHrmoOfficer($empapplication, $employee_id, $employee_oic);
                }
            }

            $officialBusinessRecords = OfficialBusiness::where('recommending_officer', $application->employee_profile_id)
            ->orWhere('approving_officer', $application->employee_profile_id)
            ->get();

            foreach ($officialBusinessRecords as $businessRecord)
            {
                if ($businessRecord->status === 'for recommending approval') {
                    $this->updateOfficers($businessRecord, $employee_id, $employee_oic);
                } elseif ($businessRecord->status === 'for approving approval') {
                    $this->updateApprovingOfficer($businessRecord, $employee_id, $employee_oic);
                }
            }

            $officialTimeRecords = OfficialTime::where('recommending_officer', $application->employee_profile_id)
            ->orWhere('approving_officer', $application->employee_profile_id)
            ->get();

            foreach ($officialTimeRecords as $timeRecord)
            {
                if ($timeRecord->status === 'for recommending approval') {
                    $this->updateOfficers($timeRecord, $employee_id, $employee_oic);
                } elseif ($timeRecord->status === 'for approving approval') {
                    $this->updateApprovingOfficer($timeRecord, $employee_id, $employee_oic);
                }
            }

            $overtimeRecords = OvertimeApplication::where('recommending_officer', $application->employee_profile_id)
            ->orWhere('approving_officer', $application->employee_profile_id)
            ->get();

            foreach ($overtimeRecords as $overtimeRecord)
            {
                if ($overtimeRecord->status === 'for recommending approval') {
                    $this->updateOfficers($overtimeRecord, $employee_id, $employee_oic);
                } elseif ($overtimeRecord->status === 'for approving approval') {
                    $this->updateApprovingOfficer($overtimeRecord, $employee_id, $employee_oic);
                }
            }

            $ctoRecords = CtoApplication::where('recommending_officer', $application->employee_profile_id)
            ->orWhere('approving_officer', $application->employee_profile_id)
            ->get();

            foreach ($ctoRecords as $ctoRecord)
            {
                if ($ctoRecord->status === 'for recommending approval') {
                    $this->updateOfficers($ctoRecord, $employee_id, $employee_oic);
                } elseif ($ctoRecord->status === 'for approving approval') {
                    $this->updateApprovingOfficer($ctoRecord, $employee_id, $employee_oic);
                }
            }

            $moneRecords = MonetizationApplication::where('recommending_officer', $application->employee_profile_id)
            ->orWhere('approving_officer', $application->employee_profile_id)
            ->get();

            foreach ($moneRecords as $moneRecord)
            {
                if ($moneRecord->status === 'for recommending approval') {
                    $this->updateOfficers($moneRecord, $employee_id, $employee_oic);
                } elseif ($moneRecord->status === 'for approving approval') {
                    $this->updateApprovingOfficer($moneRecord, $employee_id, $employee_oic);
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

    private function updateHrmoOfficer($application, $employee_id, $employee_oic)
    {
        // Update recommending officer
        if ($application->hrmo_officer == $employee_oic) {
            $application->hrmo_officer = $employee_id;
        }
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

}
