<?php

namespace App\Methods;

use App\Models\DailyTimeRecords;
use App\Models\DailyTimeRecordlogs;
use DateTime;
use Illuminate\Support\Facades\DB;
use App\Models\Biometrics;
use App\Models\EmployeeProfile;
use App\Models\Devices;
use App\Models\TimeShift;
use App\Methods\Helpers;
use App\Methods\DTRNoSchedule;
use App\Methods\DTR4setSchedule;
use App\Methods\DTR2setSchedule;

class DTRPull
{

    protected $helper;

    protected $NoSchedule;

    protected $With_4Set_Schedule;

    protected $With_2Set_Schedule;

    public function __construct()
    {
        $this->helper = new Helpers();
        $this->NoSchedule = new DTRNoSchedule();
        $this->With_4Set_Schedule = new DTR4setSchedule();
        $this->With_2Set_Schedule = new DTR2setSchedule();
    }

    public function HasBreaktimePull($DaySchedule, $BreakTime, $bioEntry, $biometric_id)
    {
        if ($this->helper->isEmployee($biometric_id)) {

            $entrydate = date("Y-m-d", strtotime($bioEntry['date_time']));
            $entry = $bioEntry['date_time'];
            $status = $bioEntry['status'];



            $validate = DailyTimeRecords::whereDate('dtr_date', $entrydate)->where('biometric_id', $biometric_id)->latest()->first();

            if ($validate !== null) {
                $this->With_4Set_Schedule->Update($validate, $DaySchedule, $BreakTime, $entrydate, $entry, $biometric_id, $bioEntry, $status);
            } else {
                $this->With_4Set_Schedule->New($DaySchedule, $BreakTime, $entrydate, $entry, $biometric_id, $bioEntry, $status);
            }
        }
    }


    public function NoBreaktimePull($DaySchedule, $bioEntry, $biometric_id)
    {

        if ($this->helper->isEmployee($biometric_id)) {
            $entrydate = date("Y-m-d", strtotime($bioEntry['date_time']));
            $entry = $bioEntry['date_time'];
            $status = $bioEntry['status'];
            $validate = DailyTimeRecords::whereDate('dtr_date', $entrydate)->where('biometric_id', $biometric_id)->latest()->first();

            if ($validate !== null) {
                $this->With_2Set_Schedule->Update($validate, $DaySchedule, $entrydate, $entry, $biometric_id, $bioEntry, $status);
            } else {
                $this->With_2Set_Schedule->New($DaySchedule, $entrydate, $entry, $biometric_id, $bioEntry, $status);
            }
        }
    }

    public function NoSchedulePull($bioEntry, $biometric_id)
    {

        if ($this->helper->isEmployee($biometric_id)) {
            $entrydate = date("Y-m-d", strtotime($bioEntry['date_time']));
            $entry = $bioEntry['date_time'];
            $status = $bioEntry['status'];
            $validate = DailyTimeRecords::whereDate('dtr_date', $entrydate)->where('biometric_id', $biometric_id)->latest()->first();

            if ($validate !== null) {
                $this->NoSchedule->Update($validate, $biometric_id, $entry, $entrydate, $bioEntry, $status);
            } else {

                $this->NoSchedule->New($entrydate, $entry, $biometric_id, $bioEntry, $status);
            }
        }
    }
}
