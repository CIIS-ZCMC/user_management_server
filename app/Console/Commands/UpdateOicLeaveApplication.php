<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Http\Resources\NotificationResource;
use App\Models\CtoApplication;
use App\Models\EmployeeProfile;
use App\Models\LeaveApplication;
use App\Models\MonetizationApplication;
use App\Models\Notifications;
use App\Models\OfficialBusiness;
use App\Models\OfficialTime;
use App\Models\Overtime;
use App\Models\OvertimeApplication;
use App\Models\UserNotifications;
use Carbon\Carbon;
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

        foreach ($leaveApplications as $application)
        {
            $employeeApplications = LeaveApplication::where('recommending_officer',$application->employee_profile_id)
            ->orwhere('approving_officer',$application->employee_profile_id)
            ->orwhere('hrmo_officer', $application->employee_oic_id)
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
            $employee_profile = EmployeeProfile::find($employee_id);
            $from = Carbon::parse($application->date_from)->format('F d, Y');
            $to = Carbon::parse($application->date_to)->format('F d, Y');
            $title = "OIC in effect";
            $description = 'You are now the Officer-in-Charge from '. $from. ' to '. $to. ' .';
    
    
            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/calendar',
            ]);
    
            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $employee_oic,
            ]);
    
            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($employee_oic),
                "data" => new NotificationResource($user_notification)
            ]);


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

    private function updateHrmoOfficer($application, $employee_id, $employee_oic)
    {
        // Update hrmo officer
        if ($application->hrmo_officer == $employee_id) {
            $application->hrmo_officer = $employee_oic;
        }
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
}
