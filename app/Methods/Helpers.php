<?php

namespace App\Methods;

use App\Models\daily_time_records;
use App\Models\daily_time_record_logs;
use DateTime;
use Illuminate\Support\Facades\DB;
use App\Models\biometrics;
use App\Models\EmployeeProfile;
use App\Models\devices;

class Helpers
{
    public function Validated_DeviceDT($deviceDT)
    {
        return true;
        // $datetime = '';
        // foreach ($deviceDT as $key => $value) {
        //     $datetime = date('Y-m-d H:i', strtotime($value->Date . ' ' . $value->Time));
        // }
        // if ($datetime == date('Y-m-d H:i')) {
        //     return true;
        // } else {
        //     return false;
        // }
    }


    public function Within_Interval($lastentry, $bioentry)
    {
        $WithInterval = date('Y-m-d H:i:s', strtotime($lastentry) + floor(env('ALLOTED_DTR_INTERVAL') * 60));
        if ($WithInterval <= $bioentry[0]['date_time']) {
            return true;
        }
        return false;
    }

    public function isEmployee($biometric_id)
    {
        $biometric = biometrics::where('biometric_id', $biometric_id)->get();
        if (count($biometric) >= 1) {
            $isemployee = EmployeeProfile::where('biometric_id', $biometric_id)->get();
            if (count($isemployee) >= 1) {
                return true;
            }
        }
        return false;
    }

    public function Get_Schedule($biometric_id, $datenow)
    {
        $f1 = env('FIRSTIN');
        $f2 = env('FIRSTOUT');
        $f3 = env('SECONDIN');
        $f4 = env('SECONDOUT');
        if (!isset($datenow)) {
            $datenow = date('Y-m-d');
        }
        $getSched = DB::select("
    SELECT s.*, 
       CASE 
           WHEN s.id IS NOT NULL THEN 
               (SELECT date_start
                FROM schedules 
                WHERE '$datenow' BETWEEN date_start AND date_end 
                AND status = 1 
                AND shift_id = s.id
                LIMIT 1)
           ELSE 'NONE'
       END AS date_start,
       CASE 
           WHEN s.id IS NOT NULL THEN 
               (SELECT date_end
                FROM schedules 
                WHERE '$datenow' BETWEEN date_start AND date_end 
                AND status = 1 
                AND shift_id = s.id
                LIMIT 1)
           ELSE 'NONE'
       END AS date_end
FROM shifts s
WHERE s.id IN (
    SELECT shift_id 
    FROM schedules 
    WHERE '$datenow' BETWEEN date_start AND date_end 
    AND status = 1 
    AND id IN (
        SELECT schedule_id 
        FROM employee_profile_schedule 
        WHERE employee_profile_id IN (
            SELECT id 
            FROM employee_profile 
            WHERE biometric_id = '$biometric_id'
        )
    )
);
    ");
        if ($this->isNurse_Or_Doctor($biometric_id)) {
            /* Check if Available Schedule */
            return $this->GetEmployee_Sched($getSched, $f1, $f2, $f3, $f4, true);
        }
        return $this->GetEmployee_Sched($getSched, $f1, $f2, $f3, $f4, false);
    }

    public function GetEmployee_Sched($getSched, $f1, $f2, $f3, $f4, $isNurseorDoctor)
    {
        if (count($getSched) >= 1) {
            return [
                'first_entry' => $getSched[0]->first_in,
                'second_entry' => $getSched[0]->first_out,
                'third_entry' => $getSched[0]->second_in,
                'last_entry' => $getSched[0]->second_out,
                'total_hours' => $getSched[0]->total_hours,
                'date_start' => $getSched[0]->date_start,
                'date_end' => $getSched[0]->date_end,
            ];
        }
        return [
            'first_entry' => null,
            'second_entry' => null,
            'third_entry' => null,
            'last_entry' => null,
            'total_hours' => env('REQUIRED_WORKING_HOURS'),
            'date_start' => null,
            'date_end' => null
        ];
    }


    private function extract_BreakSchedule($timeString, $opposite)
    {
        $timeParts = explode(':', $timeString, 3);
        $extractedTime = $timeParts[0] . ':' . $timeParts[1];
        if ($opposite) {
            $timeParts = explode(':', $timeString);
            $hours = (int)$timeParts[0];
            $minutes = (int)$timeParts[1];
            if ($hours === 12 && $minutes === 0) {
                $timeString = '00:00:00';
            } else
            if ($hours >= 12) {
                if ($hours > 12) {
                    $hours -= 12;
                }
                $timeString = sprintf("%02d:%02d", $hours, $minutes);
            } else {
                $hours += 12;
                $timeString = sprintf("%02d:%02d", $hours, $minutes);
            }
            $timeParts = explode(':', $timeString, 3);
            $extractedTime = $timeParts[0] . ':' . $timeParts[1];
            return $extractedTime;
        }
        return $extractedTime;
    }



    public function Get_BreakSchedule($biometric_id, $schedule)
    {
        if ($this->isNurse_Or_Doctor($biometric_id)) {
            return [];
        }
        if (isset($schedule['third_entry'])) {
            return  [
                'break1' => $this->extract_BreakSchedule($schedule['second_entry'], false),
                'break2' => $this->extract_BreakSchedule($schedule['second_entry'], true),
                'otherout' => $this->extract_BreakSchedule($schedule['last_entry'], true),
                'adminOut' => $this->extract_BreakSchedule($schedule['last_entry'], false),
            ];
        }
        return [];
    }

    public function isNurse_Or_Doctor($biometric_id)
    {
        /**
         * Get the employee status
         * if whther he/shes a nurse or a doctor
         * 0  = Admin employee
         * 1  = Nurse or Doctor 
         */
        $jobposition_ID = 0;
        if ($jobposition_ID) {
            return true;
        }
        return false;
    }


    public  function In_($biometric_id, $allotedhours, $sc, $sched)
    {
        $firstEntry = $sched['first_entry'];
        $timestamp = strtotime($firstEntry);
        $newTimestamp = $timestamp - ($allotedhours * 3600);
        $Calculated_allotedHours = date('Y-m-d H:i:s', $newTimestamp);
        $employeeIn = date('Y-m-d H:i:s', strtotime($sc['date_time']));
        if ($Calculated_allotedHours <= $employeeIn) {
            daily_time_records::create([
                'biometric_id' => $biometric_id,
                // 'first_in' => strtotime($sc['date_time']),
                'first_in' => $sc['date_time'],
                'is_biometric' => 1,
            ]);
        }
    }

    public function SaveFirstEntry($sequence, $breakTimeReq, $biometric_id, $checkRecords)
    {
        $allotedhours = env('ALLOTED_VALID_TIME_FOR_FIRSTENTRY');
        $sched = $this->Get_Schedule($biometric_id, null);
        foreach ($sequence as $sc) {
            $in = date('H:i', strtotime($sc['date_time']));  // From Bio
            if (count($breakTimeReq) >= 1) {
                if ($in >= $breakTimeReq['break1'] && $in < $breakTimeReq['adminOut'] || $in >= $breakTimeReq['break2'] && $in <  $breakTimeReq['otherout']) {
                    /* SECOND IN ENTRY */
                    $save =  daily_time_records::create([
                        'biometric_id' => $biometric_id,
                        //  'second_in' => strtotime($sc['date_time']), //USE THIS
                        'second_in' => $sc['date_time'],
                        'is_biometric' => 1,
                    ]);
                } else {
                    /* FIRST IN ENTRY */
                    $this->In_($biometric_id, $allotedhours, $sc, $sched);
                }
            } else {
                $this->In_($biometric_id, $allotedhours, $sc, $sched);
            }
        }
    }

    public function Get_total_time_Registered($f1, $f2, $f3, $f4)
    {
        $totalrendered = 0;
        $time = $this->ForceToStrtimeFormat($f2) - $this->ForceToStrtimeFormat($f1);
        $hours = floor($time / 3600);
        $minutes = floor(($time % 3600) / 60);
        $totalrendered = floor(($hours * 60) + $minutes);
        if ($f3 && $f4) {
            $time1 = $this->ForceToStrtimeFormat($f4) - $this->ForceToStrtimeFormat($f3);
            $hours1 = floor($time1 / 3600);
            $minutes1 = floor(($time1 % 3600) / 60);
            $oah = $hours + $hours1;
            $oam = $minutes + $minutes1;
            $totalrendered = floor(($oah * 60) + $oam);
        }
        return $totalrendered;
    }

    public function setting_date_Schedule($entry, $sched)
    {
        $date = date('Y-m-d', strtotime($entry));
        $pattern = '/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
        if (!isset($entry)) {
            return null;
        }
        if (preg_match($pattern, $sched)) {
            return "{$date} {$sched}";
        }
        return null;
    }

    public function validate_Schedule($timestampsreq)
    {
        if (
            isset($timestampsreq['first_entry']) ||
            isset($timestampsreq['second_entry']) ||
            isset($timestampsreq['third_entry']) ||
            isset($timestampsreq['last_entry'])
        ) {
            return true;
        }
        return false;
    }


    public function SaveTotalWorkingHours($validate, $value, $sequence, $timestampsreq, $checkforgenerate)
    {
        foreach ($sequence as $sc) {
            /* Entries */
            $f1entry = $validate[0]->first_in;
            $f2entry = $validate[0]->first_out;
            $f3entry = null;
            $f4entry = null;

            $f3entryTimestamp = 0;
            $f4entryTimestamp = 0;
            $s3Timestamp = 0;
            $s4Timestamp = 0;

            $underTime_inWords = null;
            $underTime_Minutes = 0;
            $overTime_inWords = null;
            $overTime_Minutes = 0;
            $ut = 0;
            $Schedule_Minutes = 0;

            if (isset($validate[0]->second_in) || isset($validate[0]->second_out)) {
                $f3entry = $validate[0]->second_in;
                $f4entry = $validate[0]->second_out;
            }
            if (!$checkforgenerate) {
                if (!isset($f2entry) && !isset($f3entry)) {
                    $f2entry = $sc['date_time'];
                } else {
                    $f4entry = $sc['date_time'];
                }
            }
            $requiredWH = $timestampsreq['total_hours'];
            $requiredWH_Minutes = $requiredWH * 60;

            if ($this->validate_Schedule($timestampsreq)) {
                /* Schedule */
                $s1 = $this->setting_date_Schedule($f1entry, $timestampsreq['first_entry']);
                $s2 = $this->setting_date_Schedule($f2entry, $timestampsreq['second_entry']);
                $s3 = $this->setting_date_Schedule($f3entry, $timestampsreq['third_entry']);
                $s4 = $this->setting_date_Schedule($f4entry, $timestampsreq['last_entry']);
                /* End Schedule */
                $undertime3rdentry = 0;
                $undertimeMinutes4thentry = 0;
                $f1entryTimestamp = strtotime($f1entry);
                $s1Timestamp = strtotime($s1);
                $f2entryTimestamp = strtotime($f2entry);
                $s2Timestamp = strtotime($s2);
                if ($f3entry && $f4entry) {
                    if (isset($s3) && isset($s4)) {
                        $f3entryTimestamp = strtotime($f3entry);
                        $s3Timestamp = strtotime($s3);
                        $f4entryTimestamp = strtotime($f4entry);
                        $s4Timestamp = strtotime($s4);
                    }
                }
                $undertime1stentry = max(0, $f1entryTimestamp - $s1Timestamp);
                $undertime2ndentry = max(0, $s2Timestamp - $f2entryTimestamp);
                $overtime2ndentry = max(0, $f2entryTimestamp - $s2Timestamp);
                if ($f3entry && $f4entry) {
                    $undert3rdentry = max(0, $f3entryTimestamp - $s3Timestamp);
                    $undertime4thentry = max(0, $s4Timestamp - $f4entryTimestamp);
                    $overtime4thentry = max(0, $f4entryTimestamp - $s4Timestamp);
                }
                $undertimeMinutes1stentry = $undertime1stentry / 60;
                $undertimeMinutes2ndentry = $undertime2ndentry / 60;
                $overtime2ndentry = $overtime2ndentry / 60;
                if ($f3entry && $f4entry) {
                    $undertime3rdentry = $undert3rdentry / 60;
                    $undertimeMinutes4thentry = $undertime4thentry / 60;
                    $overtime4thentry = $overtime4thentry / 60;
                }
                $undertime = floor($undertimeMinutes1stentry + $undertimeMinutes2ndentry + $undertime3rdentry + $undertimeMinutes4thentry);
                if ($f3entry && $f4entry) {
                    $overtime = $overtime4thentry;
                } else {
                    $overtime = $overtime2ndentry;
                }
                $ot = round($overtime);
                $ut = round($undertime);
                $Schedule_Minutes  = $this->Get_total_time_Registered(
                    $s1,
                    $s2,
                    $s3,
                    $s4
                );
                /* Overtime */
                $overTime_inWords = $this->ToWords_Minutes($ot)['Inwords'];
                $overTime_Minutes =  $this->ToWords_Minutes($ot)['InMinutes'];
                /* Undertime  */
                $underTime_inWords = $this->ToWords_Minutes($ut)['Inwords'];
                $underTime_Minutes =  $this->ToWords_Minutes($ut)['InMinutes'];
            }
            $Registered_minutes = $this->Get_total_time_Registered(
                $f1entry,
                $f2entry,
                $f3entry,
                $f4entry
            );
            /* Required Working Hours */
            //$requiredWH         | required_working_hours
            //$requiredWH_Minutes | required_working_minutes
            /* Total Working Hours */
            $tWH = floor($Registered_minutes - $ut);

            if ($Schedule_Minutes <= $tWH) {
                $tWH = floor($Schedule_Minutes - $underTime_Minutes);
                $totalWH_words = $this->ToWords_Minutes($tWH)['Inwords'];
                $totalWH_minutes = $this->ToWords_Minutes($tWH)['InMinutes'];
            } else {
                $tWH = floor($Schedule_Minutes - $underTime_Minutes);
                $totalWH_words = $this->ToWords_Minutes($tWH)['Inwords'];
                $totalWH_minutes = $this->ToWords_Minutes($tWH)['InMinutes'];
            }
            /* Registered Minutes */
            //$Registered_minutes | total_minutes_reg

            /* ValueIn */
            // echo "First Entry:" . $f1entry . "\n";
            // echo "Second Entry:" . $f2entry . "\n";
            // echo "Third Entry:" . $f3entry . "\n";
            // echo "Fourth Entry:" . $f4entry . "\n\n\n";
            // echo "Bio Entry_ :" . $sc['date_time'] . "\n";

            // if (!isset($s1) && !isset($s2)) {
            //     $underTime_inWords = null;
            //     $overTime_inWords = null;
            //     $totalWH_words = null;
            // }
            // Output undertime in minutes
            // echo "Undertime : " . $underTime_inWords  . "\n";
            // echo "Undertime Minutes: " . $underTime_Minutes  . "\n";
            // echo "Overtime : " .  $overTime_inWords . " \n";
            // echo "Overtime Minutes : " .  $overTime_Minutes . " \n";
            // echo "Total Working Hours :" . $totalWH_words . "\n";
            // echo "Total Working Minutes :" . $totalWH_minutes . "\n";
            // echo "Registered Minutes :" . $Registered_minutes . "\n";
            // echo "Schedule Minutes :" . $Schedule_Minutes . "\n";

            // echo "Schedule :" . $s1 . " | " . $s2 . " | " . $s3 . " | " . $s4 . "\n";
        }
        $overallminutesRendered = floor(($totalWH_minutes + $overTime_Minutes) - $underTime_Minutes);

        //  echo "Overall Minutes Rendered :" . $overallminutesRendered . "\n";
        if (isset($f3entry) && isset($f4entry)) {
            if ($checkforgenerate) {
                daily_time_records::find($validate[0]->id)->update([
                    'total_working_hours' => $totalWH_words,
                    'required_working_hours' => $requiredWH,
                    'required_working_minutes' => $requiredWH_Minutes,
                    'total_working_minutes' => $totalWH_minutes,
                    'overall_minutes_rendered' => $overallminutesRendered,
                    'total_minutes_reg' => $Registered_minutes,
                    'undertime' => $underTime_inWords,
                    'undertime_minutes' => $underTime_Minutes,
                    'overtime' => $overTime_inWords,
                    'overtime_minutes' => $overTime_Minutes
                ]);
            } else {
                daily_time_records::find($validate[0]->id)->update([
                    //   'first_out' => strtotime($sc['date_time']),
                    'second_out' => $sc['date_time'],
                    'total_working_hours' => $totalWH_words,
                    'required_working_hours' => $requiredWH,
                    'required_working_minutes' => $requiredWH_Minutes,
                    'total_working_minutes' => $totalWH_minutes,
                    'overall_minutes_rendered' => $overallminutesRendered,
                    'total_minutes_reg' => $Registered_minutes,
                    'undertime' => $underTime_inWords,
                    'undertime_minutes' => $underTime_Minutes,
                    'overtime' => $overTime_inWords,
                    'overtime_minutes' => $overTime_Minutes
                ]);
            }
        } else {
            if ($checkforgenerate) {
                daily_time_records::find($validate[0]->id)->update([
                    'total_working_hours' => $totalWH_words,
                    'required_working_hours' => $requiredWH,
                    'required_working_minutes' => $requiredWH_Minutes,
                    'total_working_minutes' => $totalWH_minutes,
                    'overall_minutes_rendered' => $overallminutesRendered,
                    'total_minutes_reg' => $Registered_minutes,
                    'undertime' => $underTime_inWords,
                    'undertime_minutes' => $underTime_Minutes,
                    'overtime' => $overTime_inWords,
                    'overtime_minutes' => $overTime_Minutes
                ]);
            } else {

                daily_time_records::find($validate[0]->id)->update([
                    //   'first_out' => strtotime($sc['date_time']),
                    'first_out' => $sc['date_time'],
                    'total_working_hours' => $totalWH_words,
                    'required_working_hours' => $requiredWH,
                    'required_working_minutes' => $requiredWH_Minutes,
                    'total_working_minutes' => $totalWH_minutes,
                    'overall_minutes_rendered' => $overallminutesRendered,
                    'total_minutes_reg' => $Registered_minutes,
                    'undertime' => $underTime_inWords,
                    'undertime_minutes' => $underTime_Minutes,
                    'overtime' => $overTime_inWords,
                    'overtime_minutes' => $overTime_Minutes
                ]);
            }
        }
    }


    public function SaveIntervalValidation($sequence, $validate)
    {
        foreach ($sequence as $sc) {
            $timeout = new DateTime(date('Y-m-d H:i:s', strtotime($validate[0]->first_out)));
            $timein = new DateTime($sc['date_time']);
            $interval =  $timeout->diff($timein);
            $minutes = $interval->i; // Minutes
            $seconds = $interval->s; // Seconds
            $timeinterval = '';
            $IntervalStatus = '';
            if ($minutes < env('ALLOTED_DTR_INTERVAL')) {
                /* Calculate the time interval */
                $IntervalStatus = 'Invalid';
            } else {
                $IntervalStatus = 'OK';
            }
            $timeinterval = [
                'minutes' => $minutes,
                'seconds' => $seconds,
                'Status' => $IntervalStatus
            ];
            daily_time_records::find($validate[0]->id)->update([
                // 'second_in' => strtotime($sc['date_time']),
                'second_in' => $sc['date_time'],
                'interval_req' => json_encode($timeinterval),
            ]);
        }
    }

    public function sequence($sched, $attdData)
    {
        $sequences = array();
        $tempSequence = array();
        $prevTiming = null;
        foreach ($attdData as $value) {
            $timing = $value['timing'];
            if ($prevTiming !== null && $timing !== ($prevTiming + 1)) {
                if (!empty($tempSequence)) {
                    $sequences[] = $tempSequence;
                }
                $tempSequence = array();
            }
            $tempSequence[] = $value;
            $prevTiming = $timing;
        }
        if (!empty($tempSequence)) {
            $sequences[] = $tempSequence;
        }
        return $sequences[$sched];
    }

    public function Status_description($attendanceLog)
    {
        $status_description = '';
        switch ($attendanceLog['status']) {
            case 0:
                $status_description = 'CHECK-IN';
                break;
            case 1:
                $status_description = 'CHECK-OUT';
                break;
            case 2:
                $status_description = 'BREAK-OUT';
                break;
            case 3:
                $status_description = 'BREAK-IN';
                break;
            case 4:
                $status_description = 'OVERTIME-IN';
                break;
            case 5:
                $status_description = 'OVERTIME-OUT';
                break;
            case 255:
                $biometric_id = $attendanceLog['biometric_id'];
                $datenow = date('Y-m-d');
                $Records = daily_time_records::where('biometric_id', $biometric_id)
                    ->whereDate('created_at', $datenow)->get();
                if (count($Records) >= 1) {
                    foreach ($Records as $row) {
                        if ($row->first_in) {
                            $status_description = 'CHECK-OUT';
                        }
                        if ($row->first_out) {
                            $status_description = 'CHECK-IN';
                        }
                        if ($row->second_in) {
                            $status_description = 'CHECK-OUT';
                        }
                    }
                } else {
                    $status_description = 'CHECK-IN';
                }
                break;
        }
        return $status_description;
    }

    public function Check_if_FingerPrint_Exist($tad, $userPin)
    {
        $usertemp = $tad->get_user_template(['pin' => $userPin]);
        $utemp = simplexml_load_string($usertemp);
        if (empty($utemp->Row->Result)) {
            return false;
        }
        return true;
    }

    private function getDeviceName($deviceid)
    {
        $data = devices::where('id', $deviceid)->get();
        if (count($data) >= 1) {
            return $data[0]->device_name;
        }
    }

    public function SaveDTRLogs($checkRecords, $validate, $device)
    {
        $newtiming = 0;
        $uniqueEmployeeIDs = [];
        $datenow = date('Y-m-d');
        foreach ($checkRecords as $record) {
            $employeeID = $record['biometric_id'];
            if (!in_array($employeeID, $uniqueEmployeeIDs)) {
                $uniqueEmployeeIDs[] = $employeeID;
            }
        }
        foreach ($uniqueEmployeeIDs as $id) {
            $employeeRecords = array_filter($checkRecords, function ($att) use ($id) {
                return $att['biometric_id'] == $id;
            });
            foreach ($employeeRecords as $kk => $new) {
                $newRec[] = [
                    'timing' => $newtiming,
                    'biometric_id' => $new['biometric_id'],
                    'name' => $new['name'],
                    'date_time' => $new['date_time'],
                    'status' => $new['status'],
                    'status_description' => $new['status_description'],
                ];
                $newtiming++;
            }
            // /* Checking if DTR logs for the day is generated */
            $checkDTRLogs = daily_time_record_logs::whereDate('created_at', $datenow)->where('biometric_id', $id)->where('validated', 1);
            if (count($checkDTRLogs->get()) >= 1) {
                // /* Counting logs data */
                $logData = count($checkDTRLogs->get()) >= 1 ? $checkDTRLogs->get()[0]->json_logs : '';
                $logdataArray = json_decode($logData, true);
                // /* Saving individually to user-attendance jsonLogs */
                $logdataArray = array_merge($logdataArray, $newRec);
                $ndata = [];
                foreach ($logdataArray as $n) {
                    if ($n['biometric_id'] == $id) {
                        $ndata[] = $n;
                    }
                }
                $newt = 0;
                $nr = [];
                foreach ($ndata as $new) {
                    $nr[] = [
                        'timing' => $newt,
                        'biometric_id' => $new['biometric_id'],
                        'name' => $new['name'],
                        'date_time' => $new['date_time'],
                        'status' => $new['status'],
                        'status_description' => $new['status_description'],
                        'device_id' => $device['id'],
                        'device_name' => $this->getDeviceName($device['id'])
                    ];
                    $newt++;
                }
                $checkDTRLogs->update([
                    'json_logs' => json_encode($nr)
                ]);
            } else {
                $ndata = [];
                foreach ($newRec as $n) {
                    if ($n['biometric_id'] == $id) {
                        $ndata[] = $n;
                    }
                }
                $newt = 0;
                $nr = [];
                foreach ($ndata as $new) {
                    $nr[] = [
                        'timing' => $newt,
                        'biometric_id' => $new['biometric_id'],
                        'name' => $new['name'],
                        'date_time' => $new['date_time'],
                        'status' => $new['status'],
                        'status_description' => $new['status_description'],
                        'device_id' => $device['id'],
                        'device_name' => $this->getDeviceName($device['id'])
                    ];
                    $newt++;
                }
                $checkDTR = daily_time_records::whereDate('created_at', $datenow)->where('biometric_id', $id);
                if (count($checkDTR->get()) >= 1) {
                    daily_time_record_logs::create([
                        'biometric_id' => $id,
                        'dtr_id' => $checkDTR->get()[0]->id,
                        'json_logs' => json_encode($nr),
                        'validated' => $validate
                    ]);
                } else {
                    $checkDTRLogsInvalid = daily_time_record_logs::whereDate('created_at', $datenow)->where('biometric_id', $id)->where('validated', 0)->get();
                    if (count($checkDTRLogsInvalid) == 0) {
                        daily_time_record_logs::create([
                            'biometric_id' => $id,
                            'dtr_id' => 0,
                            'json_logs' => json_encode($nr),
                            'validated' => $validate
                        ]);
                    } else {
                        if ($validate == 0) {

                            $logInv = count($checkDTRLogsInvalid) >= 1 ? $checkDTRLogsInvalid[0]->json_logs : '';
                            $logdataArrayinv = json_decode($logInv, true);
                            // /* Saving individually to user-attendance jsonLogs */
                            $logdataArrayinv = array_merge($logdataArrayinv, $nr);
                            daily_time_record_logs::where('id', $checkDTRLogsInvalid[0]->id)->update([
                                'json_logs' => json_encode($logdataArrayinv),
                            ]);
                        }
                    }
                }
            }
        }
    }


    public function Get_Attendance($attendance)
    {
        $attendanceLogs = [];
        foreach ($attendance->Row as $row) {
            $result = [
                'biometric_id' => (string) $row->PIN,
                'date_time' => (string) $row->DateTime,
                'verified' => (string) $row->Verified,
                'status' => (string) $row->Status,
                'workcode' => (string) $row->WorkCode,
            ];
            $attendanceLogs[] = $result;
        }
        return $attendanceLogs;
    }

    public function Get_Employee($userInf)
    {
        $EmployeeInfo = [];
        foreach ($userInf->Row as $row) {
            $result = [
                'biometric_id' => (string) $row->PIN2,
                'name' => (string) $row->Name,
            ];
            $EmployeeInfo[] = $result;
        }
        return $EmployeeInfo;
    }

    public function Get_Employee_attendance($attendanceLogs, $EmployeeInfo)
    {
        $EmployeeAttendance = [];
        foreach ($attendanceLogs as $key =>  $attendanceLog) {
            $employeeID = $attendanceLog['biometric_id'];
            $employeeName = '';
            $count = 0;
            foreach ($EmployeeInfo as  $k => $info) {
                if ($info['biometric_id'] === $employeeID) {
                    $employeeName = $info['name'];
                    $count++;
                    break;
                }
            }
            if (!empty($employeeName)) {
                $EmployeeAttendance[] = [
                    'timing' => $key,
                    'biometric_id' => $employeeID,
                    'name' => $employeeName,
                    'date_time' => $attendanceLog['date_time'],
                    'status' => $attendanceLog['status'],
                    'status_description' => $this->Status_description($attendanceLog),
                ];
            }
        }
        return $EmployeeAttendance;
    }


    public function ForceToStrtimeFormat($dateOrTimestamp)
    {
        if (is_numeric($dateOrTimestamp) && (int)$dateOrTimestamp == $dateOrTimestamp) {
            return $dateOrTimestamp;
        } elseif (strtotime($dateOrTimestamp) !== false || DateTime::createFromFormat('Y-m-d', $dateOrTimestamp) instanceof DateTime) {
            return strtotime($dateOrTimestamp);
        } else {
            return null;
        }
    }

    public function ToWords_Minutes($minutes)
    {
        $inWords = '';
        $entry = $minutes;
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $minutes = $minutes % 60;

            if ($hours > 0) {
                $inWords = $hours . ' hour';
                if ($hours > 1) {
                    $inWords .= 's';
                }
                if ($minutes > 0) {
                    $inWords .= ' and ' . $minutes . ' minute';
                    if ($minutes > 1) {
                        $inWords .= 's';
                    }
                }
            } else {
                $inWords = $minutes . ' minute';
                if ($minutes > 1) {
                    $inWords .= 's';
                }
            }
            $undertime = $inWords;
            $uh = $hours;
            $um = $minutes;
        } else {
            $inWords = $minutes . ' minute';
            if ($minutes > 1) {
                $inWords .= 's';
            }
        }
        return [
            'Inwords' => $inWords,
            'InMinutes' => $entry
        ];
    }
}
