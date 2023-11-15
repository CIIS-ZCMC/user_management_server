<?php

namespace App\Methods;

use App\Models\DailyTimeRecords;
use App\Models\DailyTimeRecordlogs;
use DateTime;
use Illuminate\Support\Facades\DB;
use App\Models\Biometrics;
use App\Models\EmployeeProfile;
use App\Models\Devices;

class Helpers
{
    public function validatedDeviceDT($deviceDT)
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


    public function withinInterval($last_entry, $bio_entry)
    {
        $With_Interval = date('Y-m-d H:i:s', strtotime($last_entry) + floor(env('ALLOTED_DTR_INTERVAL') * 60));
        if ($With_Interval <= $bio_entry[0]['date_time']) {
            return true;
        }
        return false;
    }

    public function isEmployee($biometric_id)
    {
        $biometric = Biometrics::where('biometric_id', $biometric_id)->get();
        if (count($biometric) >= 1) {
            $is_employee = EmployeeProfile::where('biometric_id', $biometric_id)->get();
            if (count($is_employee) >= 1) {
                return true;
            }
        }
        return false;
    }

    public function getSchedule($biometric_id, $date_now)
    {
        $f1 = env('FIRSTIN');
        $f2 = env('FIRSTOUT');
        $f3 = env('SECONDIN');
        $f4 = env('SECONDOUT');
        if (!isset($date_now)) {
            $date_now = date('Y-m-d');
        }
        $get_Sched = DB::select("
    SELECT s.*, 
       CASE 
           WHEN s.id IS NOT NULL THEN 
               (SELECT date_start
                FROM schedules 
                WHERE '$date_now' BETWEEN date_start AND date_end 
                AND status = 1 
                AND shift_id = s.id
                LIMIT 1)
           ELSE 'NONE'
       END AS date_start,
       CASE 
           WHEN s.id IS NOT NULL THEN 
               (SELECT date_end
                FROM schedules 
                WHERE '$date_now' BETWEEN date_start AND date_end 
                AND status = 1 
                AND shift_id = s.id
                LIMIT 1)
           ELSE 'NONE'
       END AS date_end
FROM shifts s
WHERE s.id IN (
    SELECT shift_id 
    FROM schedules 
    WHERE '$date_now' BETWEEN date_start AND date_end 
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
        if ($this->isNurseOrDoctor($biometric_id)) {
            /* Check if Available Schedule */
            return $this->getEmployeeSched($get_Sched, $f1, $f2, $f3, $f4, true);
        }
        return $this->getEmployeeSched($get_Sched, $f1, $f2, $f3, $f4, false);
    }

    public function getEmployeeSched($get_Sched, $f1, $f2, $f3, $f4, $is_Nurse_or_Doctor)
    {
        if (count($get_Sched) >= 1) {
            return [
                'first_entry' => $get_Sched[0]->first_in,
                'second_entry' => $get_Sched[0]->first_out,
                'third_entry' => $get_Sched[0]->second_in,
                'last_entry' => $get_Sched[0]->second_out,
                'total_hours' => $get_Sched[0]->total_hours,
                'date_start' => $get_Sched[0]->date_start,
                'date_end' => $get_Sched[0]->date_end,
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


    private function extractBreakSchedule($time_String, $opposite)
    {
        $time_Parts = explode(':', $time_String, 3);
        $extracted_Time = $time_Parts[0] . ':' . $time_Parts[1];
        if ($opposite) {
            $timeParts = explode(':', $time_String);
            $hours = (int)$time_Parts[0];
            $minutes = (int)$time_Parts[1];
            if ($hours === 12 && $minutes === 0) {
                $time_String = '00:00:00';
            } else
            if ($hours >= 12) {
                if ($hours > 12) {
                    $hours -= 12;
                }
                $time_String = sprintf("%02d:%02d", $hours, $minutes);
            } else {
                $hours += 12;
                $time_String = sprintf("%02d:%02d", $hours, $minutes);
            }
            $time_Parts = explode(':', $time_String, 3);
            $extracted_Time = $time_Parts[0] . ':' . $time_Parts[1];
            return $extracted_Time;
        }
        return $extracted_Time;
    }



    public function getBreakSchedule($biometric_id, $schedule)
    {
        if ($this->isNurseOrDoctor($biometric_id)) {
            return [];
        }
        if (isset($schedule['third_entry'])) {
            return  [
                'break1' => $this->extractBreakSchedule($schedule['second_entry'], false),
                'break2' => $this->extractBreakSchedule($schedule['second_entry'], true),
                'otherout' => $this->extractBreakSchedule($schedule['last_entry'], true),
                'adminOut' => $this->extractBreakSchedule($schedule['last_entry'], false),
            ];
        }
        return [];
    }

    public function isNurseOrDoctor($biometric_id)
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


    public  function inEntry($biometric_id, $alloted_hours, $sc, $sched)
    {
        $first_Entry = $sched['first_entry'];
        $time_stamp = strtotime($first_Entry);
        $new_Time_stamp = $time_stamp - ($alloted_hours * 3600);
        $Calculated_allotedHours = date('Y-m-d H:i:s', $new_Time_stamp);
        $employee_In = date('Y-m-d H:i:s', strtotime($sc['date_time']));
        if ($Calculated_allotedHours <= $employee_In) {
            DailyTimeRecords::create([
                'biometric_id' => $biometric_id,
                // 'first_in' => strtotime($sc['date_time']),
                'first_in' => $sc['date_time'],
                'is_biometric' => 1,
            ]);
        }
    }

    public function saveFirstEntry($sequence, $break_Time_Req, $biometric_id, $checkRecords)
    {
        $alloted_hours = env('ALLOTED_VALID_TIME_FOR_FIRSTENTRY');
        $sched = $this->getSchedule($biometric_id, null);
        foreach ($sequence as $sc) {
            $in = date('H:i', strtotime($sc['date_time']));  // From Bio
            if (count($break_Time_Req) >= 1) {
                if ($in >= $break_Time_Req['break1'] && $in < $break_Time_Req['adminOut'] || $in >= $break_Time_Req['break2'] && $in <  $break_Time_Req['otherout']) {
                    /* SECOND IN ENTRY */
                    $save =  DailyTimeRecords::create([
                        'biometric_id' => $biometric_id,
                        //  'second_in' => strtotime($sc['date_time']), //USE THIS
                        'second_in' => $sc['date_time'],
                        'is_biometric' => 1,
                    ]);
                } else {
                    /* FIRST IN ENTRY */
                    $this->inEntry($biometric_id, $alloted_hours, $sc, $sched);
                }
            } else {
                $this->inEntry($biometric_id, $alloted_hours, $sc, $sched);
            }
        }
    }

    public function getTotalTimeRegistered($f1, $f2, $f3, $f4)
    {
        $total_rendered = 0;
        $time = $this->forceToStrTimeFormat($f2) - $this->forceToStrTimeFormat($f1);
        $hours = floor($time / 3600);
        $minutes = floor(($time % 3600) / 60);
        $total_rendered = floor(($hours * 60) + $minutes);
        if ($f3 && $f4) {
            $time1 = $this->forceToStrTimeFormat($f4) - $this->forceToStrTimeFormat($f3);
            $hours1 = floor($time1 / 3600);
            $minutes1 = floor(($time1 % 3600) / 60);
            $oah = $hours + $hours1;
            $oam = $minutes + $minutes1;
            $totalrendered = floor(($oah * 60) + $oam);
        }
        return $total_rendered;
    }

    public function settingDateSchedule($entry, $sched)
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

    public function validateSchedule($time_stamps_req)
    {
        if (
            isset($time_stamps_req['first_entry']) ||
            isset($time_stamps_req['second_entry']) ||
            isset($time_stamps_req['third_entry']) ||
            isset($time_stamps_req['last_entry'])
        ) {
            return true;
        }
        return false;
    }


    public function saveTotalWorkingHours($validate, $value, $sequence, $time_stamps_req, $check_for_generate)
    {
        foreach ($sequence as $sc) {
            /* Entries */
            $f1_entry = $validate[0]->first_in;
            $f2_entry = $validate[0]->first_out;
            $f3_entry = null;
            $f4_entry = null;

            $f3_entry_Time_stamp = 0;
            $f4_entry_Time_stamp = 0;
            $s3_Time_stamp = 0;
            $s4_Time_stamp = 0;

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
            if (!$check_for_generate) {
                if (!isset($f2entry) && !isset($f3entry)) {
                    $f2entry = $sc['date_time'];
                } else {
                    $f4entry = $sc['date_time'];
                }
            }
            $required_WH = $time_stamps_req['total_hours'];
            $required_WH_Minutes = $required_WH * 60;

            if ($this->validateSchedule($time_stamps_req)) {
                /* Schedule */
                $s1 = $this->settingDateSchedule($f1_entry, $time_stamps_req['first_entry']);
                $s2 = $this->settingDateSchedule($f2_entry, $time_stamps_req['second_entry']);
                $s3 = $this->settingDateSchedule($f3_entry, $time_stamps_req['third_entry']);
                $s4 = $this->settingDateSchedule($f4_entry, $time_stamps_req['last_entry']);
                /* End Schedule */
                $undertime_3rd_entry = 0;
                $undertime_Minutes_4th_entry = 0;
                $f1_entry_Time_stamp = strtotime($f1_entry);
                $s1_Time_stamp = strtotime($s1);
                $f2_entry_Time_stamp = strtotime($f2_entry);
                $s2_Time_stamp = strtotime($s2);
                if ($f3_entry && $f4_entry) {
                    if (isset($s3) && isset($s4)) {
                        $f3entryTimestamp = strtotime($f3entry);
                        $s3Timestamp = strtotime($s3);
                        $f4entryTimestamp = strtotime($f4entry);
                        $s4Timestamp = strtotime($s4);
                    }
                }
                $undertime_1st_entry = max(0, $f1_entry_Time_stamp - $s1_Time_stamp);
                $undertime_2nd_entry = max(0, $s2_Time_stamp - $f2_entry_Time_stamp);
                $overtime_2nd_entry = max(0, $f2_entry_Time_stamp - $s2_Time_stamp);
                if ($f3_entry && $f4_entry) {
                    $undert_3rd_entry = max(0, $f3_entry_Time_stamp - $s3_Time_stamp);
                    $undertime_4th_entry = max(0, $s4Timestamp - $f4_entry_Time_stamp);
                    $overtime_4th_entry = max(0, $f4_entry_Time_stamp - $s4_Time_stamp);
                }
                $undertime_Minutes_1st_entry = $undertime_1st_entry / 60;
                $undertime_Minutes_2nd_entry = $undertime_2nd_entry / 60;
                $overtime_2nd_entry = $overtime_2nd_entry / 60;
                if ($f3_entry && $f4_entry) {
                    $undertime_3rd_entry = $undert_3rd_entry / 60;
                    $undertime_Minutes_4th_entry = $undertime_4th_entry / 60;
                    $overtime_4th_entry = $overtime_4th_entry / 60;
                }
                $undertime = floor($undertime_Minutes_1st_entry + $undertime_Minutes_2nd_entry + $undertime_3rd_entry + $undertime_Minutes_4th_entry);
                if ($f3_entry && $f4_entry) {
                    $overtime = $overtime_4th_entry;
                } else {
                    $overtime = $overtime_2nd_entry;
                }
                $ot = round($overtime);
                $ut = round($undertime);
                $Schedule_Minutes  = $this->getTotalTimeRegistered(
                    $s1,
                    $s2,
                    $s3,
                    $s4
                );
                /* Overtime */
                $overTime_inWords = $this->toWordsMinutes($ot)['Inwords'];
                $overTime_Minutes =  $this->toWordsMinutes($ot)['InMinutes'];
                /* Undertime  */
                $underTime_inWords = $this->toWordsMinutes($ut)['Inwords'];
                $underTime_Minutes =  $this->toWordsMinutes($ut)['InMinutes'];
            }
            $Registered_minutes = $this->getTotalTimeRegistered(
                $f1_entry,
                $f2_entry,
                $f3_entry,
                $f4_entry
            );
            /* Required Working Hours */
            //$requiredWH         | required_working_hours
            //$requiredWH_Minutes | required_working_minutes
            /* Total Working Hours */
            $tWH = floor($Registered_minutes - $ut);

            if ($Schedule_Minutes <= $tWH) {
                $tWH = floor($Schedule_Minutes - $underTime_Minutes);
                $total_WH_words = $this->toWordsMinutes($tWH)['Inwords'];
                $total_WH_minutes = $this->toWordsMinutes($tWH)['InMinutes'];
            } else {
                $tWH = floor($Schedule_Minutes - $underTime_Minutes);
                $total_WH_words = $this->toWordsMinutes($tWH)['Inwords'];
                $total_WH_minutes = $this->toWordsMinutes($tWH)['InMinutes'];
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
        $over_all_minutes_Rendered = floor(($total_WH_minutes + $overTime_Minutes) - $underTime_Minutes);

        //  echo "Overall Minutes Rendered :" . $overallminutesRendered . "\n";
        if (isset($f3_entry) && isset($f4_entry)) {
            if ($check_for_generate) {
                DailyTimeRecords::find($validate[0]->id)->update([
                    'total_working_hours' => $total_WH_words,
                    'required_working_hours' => $required_WH,
                    'required_working_minutes' => $required_WH_Minutes,
                    'total_working_minutes' => $total_WH_minutes,
                    'overall_minutes_rendered' => $over_all_minutes_Rendered,
                    'total_minutes_reg' => $Registered_minutes,
                    'undertime' => $underTime_inWords,
                    'undertime_minutes' => $underTime_Minutes,
                    'overtime' => $overTime_inWords,
                    'overtime_minutes' => $overTime_Minutes
                ]);
            } else {
                DailyTimeRecords::find($validate[0]->id)->update([
                    //   'first_out' => strtotime($sc['date_time']),
                    'second_out' => $sc['date_time'],
                    'total_working_hours' => $total_WH_words,
                    'required_working_hours' => $required_WH,
                    'required_working_minutes' => $required_WH_Minutes,
                    'total_working_minutes' => $total_WH_minutes,
                    'overall_minutes_rendered' => $over_all_minutes_Rendered,
                    'total_minutes_reg' => $Registered_minutes,
                    'undertime' => $underTime_inWords,
                    'undertime_minutes' => $underTime_Minutes,
                    'overtime' => $overTime_inWords,
                    'overtime_minutes' => $overTime_Minutes
                ]);
            }
        } else {
            if ($check_for_generate) {
                DailyTimeRecords::find($validate[0]->id)->update([
                    'total_working_hours' => $total_WH_words,
                    'required_working_hours' => $required_WH,
                    'required_working_minutes' => $required_WH_Minutes,
                    'total_working_minutes' => $total_WH_minutes,
                    'overall_minutes_rendered' => $over_all_minutes_Rendered,
                    'total_minutes_reg' => $Registered_minutes,
                    'undertime' => $underTime_inWords,
                    'undertime_minutes' => $underTime_Minutes,
                    'overtime' => $overTime_inWords,
                    'overtime_minutes' => $overTime_Minutes
                ]);
            } else {

                DailyTimeRecords::find($validate[0]->id)->update([
                    //   'first_out' => strtotime($sc['date_time']),
                    'first_out' => $sc['date_time'],
                    'total_working_hours' => $total_WH_words,
                    'required_working_hours' => $required_WH,
                    'required_working_minutes' => $required_WH_Minutes,
                    'total_working_minutes' => $total_WH_minutes,
                    'overall_minutes_rendered' => $over_all_minutes_Rendered,
                    'total_minutes_reg' => $Registered_minutes,
                    'undertime' => $underTime_inWords,
                    'undertime_minutes' => $underTime_Minutes,
                    'overtime' => $overTime_inWords,
                    'overtime_minutes' => $overTime_Minutes
                ]);
            }
        }
    }


    public function saveIntervalValidation($sequence, $validate)
    {
        foreach ($sequence as $sc) {
            $time_out = new DateTime(date('Y-m-d H:i:s', strtotime($validate[0]->first_out)));
            $time_in = new DateTime($sc['date_time']);
            $interval =  $time_out->diff($time_in);
            $minutes = $interval->i; // Minutes
            $seconds = $interval->s; // Seconds
            $time_interval = '';
            $IntervalStatus = '';
            if ($minutes < env('ALLOTED_DTR_INTERVAL')) {
                /* Calculate the time interval */
                $Interval_Status = 'Invalid';
            } else {
                $Interval_Status = 'OK';
            }
            $time_interval = [
                'minutes' => $minutes,
                'seconds' => $seconds,
                'Status' => $Interval_Status
            ];
            DailyTimeRecords::find($validate[0]->id)->update([
                // 'second_in' => strtotime($sc['date_time']),
                'second_in' => $sc['date_time'],
                'interval_req' => json_encode($time_interval),
            ]);
        }
    }

    public function sequence($sched, $attdData)
    {
        $sequences = array();
        $temp_Sequence = array();
        $prev_Timing = null;
        foreach ($attdData as $value) {
            $timing = $value['timing'];
            if ($prev_Timing !== null && $timing !== ($prev_Timing + 1)) {
                if (!empty($temp_Sequence)) {
                    $sequences[] = $temp_Sequence;
                }
                $temp_Sequence = array();
            }
            $temp_Sequence[] = $value;
            $prevTiming = $timing;
        }
        if (!empty($temp_Sequence)) {
            $sequences[] = $temp_Sequence;
        }
        return $sequences[$sched];
    }

    public function statusDescription($attendance_Log)
    {
        $status_description = '';
        switch ($attendance_Log['status']) {
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
                $biometric_id = $attendance_Log['biometric_id'];
                $date_now = date('Y-m-d');
                $Records = DailyTimeRecords::where('biometric_id', $biometric_id)
                    ->whereDate('created_at', $date_now)->get();
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

    public function checkIfFingerPrintExist($tad, $userPin)
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
        $data = Devices::where('id', $deviceid)->get();
        if (count($data) >= 1) {
            return $data[0]->device_name;
        }
    }

    public function saveDTRLogs($check_Records, $validate, $device)
    {
        $new_timing = 0;
        $unique_Employee_IDs = [];
        $date_now = date('Y-m-d');
        foreach ($check_Records as $record) {
            $employee_ID = $record['biometric_id'];
            if (!in_array($employee_ID, $unique_Employee_IDs)) {
                $unique_Employee_IDs[] = $employee_ID;
            }
        }
        foreach ($unique_Employee_IDs as $id) {
            $employee_Records = array_filter($check_Records, function ($att) use ($id) {
                return $att['biometric_id'] == $id;
            });
            foreach ($employee_Records as $kk => $new) {
                $new_Rec[] = [
                    'timing' => $new_timing,
                    'biometric_id' => $new['biometric_id'],
                    'name' => $new['name'],
                    'date_time' => $new['date_time'],
                    'status' => $new['status'],
                    'status_description' => $new['status_description'],
                ];
                $new_timing++;
            }
            // /* Checking if DTR logs for the day is generated */
            $check_DTR_Logs = DailyTimeRecordlogs::whereDate('created_at', $date_now)->where('biometric_id', $id)->where('validated', 1);
            if (count($check_DTR_Logs->get()) >= 1) {
                // /* Counting logs data */
                $log_Data = count($check_DTR_Logs->get()) >= 1 ? $check_DTR_Logs->get()[0]->json_logs : '';
                $log_data_Array = json_decode($log_Data, true);
                // /* Saving individually to user-attendance jsonLogs */
                $log_data_Array = array_merge($log_data_Array, $new_Rec);
                $ndata = [];
                foreach ($log_data_Array as $n) {
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
                $check_DTR_Logs->update([
                    'json_logs' => json_encode($nr)
                ]);
            } else {
                $ndata = [];
                foreach ($new_Rec as $n) {
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
                $chec_kDTR = DailyTimeRecords::whereDate('created_at', $date_now)->where('biometric_id', $id);
                if (count($chec_kDTR->get()) >= 1) {
                    DailyTimeRecordlogs::create([
                        'biometric_id' => $id,
                        'dtr_id' => $chec_kDTR->get()[0]->id,
                        'json_logs' => json_encode($nr),
                        'validated' => $validate
                    ]);
                } else {
                    $check_DTR_Logs_Invalid = DailyTimeRecordlogs::whereDate('created_at', $date_now)->where('biometric_id', $id)->where('validated', 0)->get();
                    if (count($check_DTR_Logs_Invalid) == 0) {
                        DailyTimeRecordlogs::create([
                            'biometric_id' => $id,
                            'dtr_id' => 0,
                            'json_logs' => json_encode($nr),
                            'validated' => $validate
                        ]);
                    } else {
                        if ($validate == 0) {

                            $log_Inv = count($check_DTR_Logs_Invalid) >= 1 ? $check_DTR_Logs_Invalid[0]->json_logs : '';
                            $log_data_Array_inv = json_decode($log_Inv, true);
                            // /* Saving individually to user-attendance jsonLogs */
                            $log_data_Array_inv = array_merge($log_data_Array_inv, $nr);
                            DailyTimeRecordlogs::where('id', $check_DTR_Logs_Invalid[0]->id)->update([
                                'json_logs' => json_encode($log_data_Array_inv),
                            ]);
                        }
                    }
                }
            }
        }
    }


    public function getAttendance($attendance)
    {
        $attendance_Logs = [];
        foreach ($attendance->Row as $row) {
            $result = [
                'biometric_id' => (string) $row->PIN,
                'date_time' => (string) $row->DateTime,
                'verified' => (string) $row->Verified,
                'status' => (string) $row->Status,
                'workcode' => (string) $row->WorkCode,
            ];
            $attendance_Logs[] = $result;
        }
        return $attendance_Logs;
    }

    public function getEmployee($user_Inf)
    {
        $Employee_Info = [];
        foreach ($user_Inf->Row as $row) {
            $result = [
                'biometric_id' => (string) $row->PIN2,
                'name' => (string) $row->Name,
            ];
            $Employee_Info[] = $result;
        }
        return $Employee_Info;
    }

    public function getEmployeeAttendance($attendance_Logs, $Employee_Info)
    {
        $Employee_Attendance = [];
        foreach ($attendance_Logs as $key =>  $attendance_Log) {
            $employee_ID = $attendance_Log['biometric_id'];
            $employee_Name = '';
            $count = 0;
            foreach ($Employee_Info as  $k => $info) {
                if ($info['biometric_id'] === $employee_ID) {
                    $employee_Name = $info['name'];
                    $count++;
                    break;
                }
            }
            if (!empty($employee_Name)) {
                $Employee_Attendance[] = [
                    'timing' => $key,
                    'biometric_id' => $employee_ID,
                    'name' => $employee_Name,
                    'date_time' => $attendance_Log['date_time'],
                    'status' => $attendance_Log['status'],
                    'status_description' => $this->statusDescription($attendance_Log),
                ];
            }
        }
        return $Employee_Attendance;
    }


    public function forceToStrTimeFormat($date_Or_Timestamp)
    {
        if (is_numeric($date_Or_Timestamp) && (int)$date_Or_Timestamp == $date_Or_Timestamp) {
            return $date_Or_Timestamp;
        } elseif (strtotime($date_Or_Timestamp) !== false || DateTime::createFromFormat('Y-m-d', $date_Or_Timestamp) instanceof DateTime) {
            return strtotime($date_Or_Timestamp);
        } else {
            return null;
        }
    }

    public function toWordsMinutes($minutes)
    {
        $in_Words = '';
        $entry = $minutes;
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $minutes = $minutes % 60;

            if ($hours > 0) {
                $in_Words = $hours . ' hour';
                if ($hours > 1) {
                    $in_Words .= 's';
                }
                if ($minutes > 0) {
                    $in_Words .= ' and ' . $minutes . ' minute';
                    if ($minutes > 1) {
                        $in_Words .= 's';
                    }
                }
            } else {
                $in_Words = $minutes . ' minute';
                if ($minutes > 1) {
                    $in_Words .= 's';
                }
            }
            $undertime = $in_Words;
            $uh = $hours;
            $um = $minutes;
        } else {
            $in_Words = $minutes . ' minute';
            if ($minutes > 1) {
                $in_Words .= 's';
            }
        }
        return [
            'Inwords' => $in_Words,
            'InMinutes' => $entry
        ];
    }
}
