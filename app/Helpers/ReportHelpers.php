<?php

namespace App\Helpers;

use App\Models\DailyTimeRecords;
use App\Models\DailyTimeRecordlogs;
use DateTime;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Biometrics;
use App\Models\EmployeeProfile;
use App\Models\Devices;
use App\Models\TimeShift;
use PHPUnit\Framework\MockObject\Stub\ReturnArgument;
use PHPUnit\Framework\MockObject\Stub\ReturnSelf;

class ReportHelpers
{

    public static function ToHours($minutes)
    {
        $hours = $minutes / 60;
        return $hours;
    }
    public static function getDateIntervals($from, $to)
    {
        $dates_Interval = [];
        $from = strtotime($from);
        $to = strtotime($to);
        while ($from <= $to) {
            $dates_Interval[] = date('Y-m-d', $from);
            $from = strtotime('+1 day', $from);
        }

        return $dates_Interval;
    }
    public static function validatedDeviceDT($deviceDT)
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

    public static function EntryisAm($entry)
    {

        if (date('A', strtotime($entry)) === "AM") {
            return true;
        }

        return false;
    }
    public static function EntryisPM($entry)
    {
        if (date('A', strtotime($entry)) === "PM") {
            return true;
        }

        return false;
    }


    public static function withinInterval($last_entry, $bio_entry)
    {

        $With_Interval = date('Y-m-d H:i:s', strtotime($last_entry) + floor(config("app.alloted_dtr_interval") * 60));



        if ($With_Interval <= $bio_entry[0]['date_time']) {
            return true;
        }
        return false;
    }

    public static function isEmployee($biometric_id)
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

    public static function getSchedule($biometric_id, $date_now)
    {
        $f1 = config("app.firstin");
        $f2 = config("app.firstout");
        $f3 = config("app.secondin");
        $f4 = config("app.secondout");

        $parts = explode('-', $date_now);
        // $parts[1] will contain "2024"
        // $parts[2] will contain "2"
        if (count($parts) >= 3) {
            $check = $parts[0];
            $year = $parts[1];
            $month = $parts[2];
            if ($check === "all") {
                return self::Allschedule($biometric_id, $month, $year, $f1, $f2, $f3, $f4);
            }
        }


        if (!isset($date_now)) {
            $date_now = date('Y-m-d');
        }

        $date_now = date('Y-m-d', strtotime($date_now));
        $get_Sched = DB::select("
                SELECT s.*,
                CASE
                    WHEN s.id IS NOT NULL THEN
                        (SELECT date
                        FROM schedules
                        WHERE '$date_now' = date
                        AND status = 1
                        AND time_shift_id = s.id
                        LIMIT 1)
                    ELSE 'NONE'
                END AS date
                FROM time_shifts s
                WHERE s.id IN (
                SELECT time_shift_id
                FROM schedules
                WHERE '$date_now' = date
                AND status = 1
                AND id IN (
                SELECT schedule_id
                FROM employee_profile_schedule
                WHERE employee_profile_id IN (
                    SELECT id
                    FROM employee_profiles
                    WHERE biometric_id = '$biometric_id'
                )
                )
                );

        ");



        if (self::isNurseOrDoctor($biometric_id)) {
            /* Check if Available Schedule */
            return self::getEmployeeSched($get_Sched, $f1, $f2, $f3, $f4, true);
        }
        return self::getEmployeeSched($get_Sched, $f1, $f2, $f3, $f4, false);
    }

    public static function Allschedule($biometric_id, $month, $year, $f1, $f2, $f3, $f4)
    {
        $timeShifts = DB::table('time_shifts as ts')
            ->select(
                'sc.id as scheduleID',
                'ts.first_in',
                'ts.first_out',
                'ts.second_in',
                'ts.second_out',
                'ts.total_hours',
                'ep.biometric_id',
                'sc.date',
                'sc.is_weekend',
                'sc.status',
                'sc.remarks',
            )
            ->join('schedules as sc', 'sc.time_shift_id', '=', 'ts.id')
            ->join('employee_profile_schedule as esc', 'esc.schedule_id', '=', 'sc.id')
            ->join('employee_profiles as ep', 'ep.id', '=', 'esc.employee_profile_id')
            ->where('ep.biometric_id', $biometric_id)
            ->whereYear('sc.date', $year)
            ->whereMonth('sc.date', $month)
            ->get();

        $scheds = [];
        $arrival_d = [];
        $dp = '';
        foreach ($timeShifts as $row) {
            $firstin = date('gA', strtotime($year . '-' . $month . '-1 ' . $row->first_in));
            $firstout = date('gA', strtotime($year . '-' . $month . '-1 ' . $row->first_out));
            $secondin = date('gA', strtotime($year . '-' . $month . '-1 ' . $row->second_in));
            $secondout = date('gA', strtotime($year . '-' . $month . '-1 ' . $row->second_out));
            if ($row->second_in !== null && $row->second_out !== null) {
                $dp =  $firstin . '-' . $firstout . '|' . $secondin . '-' . $secondout;
            } else {
                $dp =  $firstin . '-' . $firstout;
            }
            $arrival_d[] = $dp;
            $total_hours = 8;
            if (isset($row->total_hours) && $row->total_hours) {
                $total_hours = $row->total_hours;
            } else {
                $total_hours = config("app.required_working_hours");
            }

            $scheds[] = [
                'scheduleDate' => $row->date ?? date('Y-m-d'),
                'first_entry' => $row->first_in ?? null,
                'second_entry' => $row->first_out ?? null,
                'third_entry' => $row->second_in ?? null,
                'last_entry' => $row->second_out ?? null,
                'total_hours' => $total_hours,
                'arrival_departure' => $dp ?? ""
            ];
        }
        $Arrival_departure =  array_values(array_unique($arrival_d));

        return [
            'schedule' => $scheds,
            'arrival_departure' => Implode("&", $Arrival_departure)
        ];
    }

    public static function getEmployeeSched($get_Sched, $f1, $f2, $f3, $f4, $is_Nurse_or_Doctor)
    {
        if (count($get_Sched) >= 1) {
            return [
                'first_entry' => $get_Sched[0]->first_in,
                'second_entry' => $get_Sched[0]->first_out,
                'third_entry' => $get_Sched[0]->second_in,
                'last_entry' => $get_Sched[0]->second_out,
                'total_hours' => $get_Sched[0]->total_hours,
                'date' => $get_Sched[0]->date

            ];
        }
        return [
            'first_entry' => null,
            'second_entry' => null,
            'third_entry' => null,
            'last_entry' => null,
            'total_hours' => config("app.required_working_hours") ?? 8,
            'date' => null,
            'date_end' => null,
        ];
    }


    private static function extractBreakSchedule($time_String, $opposite)
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



    public static function getBreakSchedule($biometric_id, $schedule)
    {

        if (isset($schedule[0]['third_entry'])) {
            return  [
                'break1' => self::extractBreakSchedule($schedule[0]['second_entry'], false),
                'break2' => self::extractBreakSchedule($schedule[0]['second_entry'], true),
                'otherout' => self::extractBreakSchedule($schedule[0]['last_entry'], true),
                'adminOut' => self::extractBreakSchedule($schedule[0]['last_entry'], false),
            ];
        }
        return [];
    }

    public static function isNurseOrDoctor($biometric_id)
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

    public static function CurrentSchedule($biometric_id, $value, $yesterdayRecord)
    {
        if (!isset($value['date_time'])) {
            return [
                'daySchedule' => [],
                'break_Time_Req' => [],
            ];
        }
        $entrydateYear = date('Y', strtotime($value['date_time']));
        $entrydateMonth = date('m', strtotime($value['date_time']));
        $schedule = self::getSchedule($biometric_id, "all-{$entrydateYear}-{$entrydateMonth}");
        // Put employee ID

        $dsched = [];
        $entry = date('Y-m-d', strtotime($value['date_time']));
        $entryTime = date('H:i', strtotime($value['date_time']));
        if ($yesterdayRecord) {
            $entrydateYear = date('Y', strtotime($value['first_in']));
            $entrydateMonth = date('m', strtotime($value['first_in']));
            $schedule = self::getSchedule($biometric_id, "all-{$entrydateYear}-{$entrydateMonth}");

            $entry = date('Y-m-d', strtotime($value['first_in']));
            $entryTime = date('H:i', strtotime($value['first_in']));
        }

        // $daySchedule = array_values(array_filter($schedule['schedule'], function ($row) use ($entry, $entryTime) {
        //     return date('Y-m-d', strtotime($row['scheduleDate'])) === $entry &&
        //         date('Y-m-d H:i', strtotime($entry . ' ' . $entryTime)) <= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['first_entry'] . ' +4 hours')) ||
        //         date('Y-m-d', strtotime($row['scheduleDate'])) === $entry  &&
        //         date('Y-m-d H:i', strtotime($entry . ' ' . $entryTime)) <= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['second_entry'] . ' +4 hours'));
        // }));

        $daySchedule = array_values(array_filter($schedule['schedule'], function ($row) use ($entry, $entryTime) {
            $entryDateTime = strtotime($entry . ' ' . $entryTime);

            return date('Y-m-d', strtotime($row['scheduleDate'])) === $entry &&
                (
                    (date('Y-m-d H:i', $entryDateTime) <= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['first_entry'] . ' +4 hours')) &&
                        date('Y-m-d H:i', $entryDateTime) >= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['first_entry'] . ' -4 hours'))) ||
                    (date('Y-m-d H:i', $entryDateTime) <= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['second_entry'] . ' +4 hours')) &&
                        date('Y-m-d H:i', $entryDateTime) >= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['second_entry'] . ' -4 hours'))) ||
                    (date('Y-m-d H:i', $entryDateTime) <= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['third_entry'] . ' +4 hours')) &&
                        date('Y-m-d H:i', $entryDateTime) >= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['third_entry'] . ' -4 hours'))) ||
                    (date('Y-m-d H:i', $entryDateTime) <= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['last_entry'] . ' +4 hours')) &&
                        date('Y-m-d H:i', $entryDateTime) >= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['last_entry'] . ' -4 hours')))
                );
        }));




        $break_Time_Req = self::getBreakSchedule($biometric_id, $daySchedule);

        if (count($daySchedule) >= 1) {
            $dsched = $daySchedule[0];
        }

        return [
            'daySchedule' => $dsched,
            'break_Time_Req' => $break_Time_Req,

        ];
    }





    public static function SaveFirstEntry($dtrentry, $break_Time_Req, $biometric_id, $delay, $scheduleEntry, $InType)
    {
        $alloted_hours = config("app.alloted_valid_time_for_firstentry");


        switch ($InType) {
            case "AM":
                self::inEntryAM($biometric_id, $alloted_hours, $scheduleEntry, $dtrentry);
                break;
            case "PM":
                self::inEntryPM($biometric_id, $alloted_hours, $scheduleEntry, $dtrentry);
                break;
        }
    }

    public static function inEntryAM($biometric_id, $alloted_hours, $scheduleEntry, $dtrentry)
    {

        $dtr_date = date('Y-m-d', strtotime($dtrentry['date_time']));
        $max_allowed_entry_for_oncall = config("app.max_allowed_entry_oncall");

        $dtrentry = $dtrentry['date_time'];
        $schedule = $scheduleEntry['first_entry'] ?? config("app.firstin");




        $alloted_mins_Oncall = 0.5; // 30 minutes
        if (count($scheduleEntry) >= 1) {
            /* With Schedule Entry */
            $in_Entry = $schedule;


            // $time_stamp = strtotime($in_Entry);
            // $new_Time_stamp = $time_stamp - ($alloted_hours * 3600);
            // $Calculated_allotedHours = date('Y-m-d H:i:s', $new_Time_stamp);
            // if ($isoncall) {
            //     $schedEntry = $time_stamp + ($alloted_mins_Oncall * 1800); // 30 mins

            //     $calIn = date("Y-m-d H:i:s", $schedEntry);
            //     $dtrentry = date("Y-m-d H:i:s", strtotime($dtrentry));
            //     $newentry = date("Y-m-d H:i:s", $schedEntry);
            //     if ($calIn <= $dtrentry) {

            //         //Not within 30 mins.
            //         // minus 30 mins then save as new Entry
            //         $newentry = date("Y-m-d H:i:s", strtotime($dtrentry . "-30 minutes"));
            //     }

            //     DailyTimeRecords::create([
            //         'biometric_id' => $biometric_id,
            //         'dtr_date' => $dtr_date,
            //         'first_in' =>  $newentry,
            //         'is_biometric' => 1,
            //     ]);
            // } else {


            //       if ($Calculated_allotedHours <=  $dtrentry) { //within alloted hours to timein


            DailyTimeRecords::create([
                'biometric_id' => $biometric_id,
                'dtr_date' => $dtr_date,
                'first_in' =>  $dtrentry,
                'is_biometric' => 1,
                'is_time_adjustment' => 0
            ]);
            //  }


            //     }
        } else {
            /* No schedule Entry */
            DailyTimeRecords::create([
                'biometric_id' => $biometric_id,
                'dtr_date' => $dtr_date,
                'first_in' => $dtrentry,
                'is_biometric' => 1,
                'is_time_adjustment' => 0
            ]);
        }
    }


    public  function inEntryPM($biometric_id, $alloted_hours, $scheduleEntry, $dtrentry)
    {

        $dtr_date = date('Y-m-d', strtotime($dtrentry['date_time']));
        $max_allowed_entry_for_oncall = config("app.max_allowed_entry_oncall");

        $dtrentry = $dtrentry['date_time'];
        $schedule = $scheduleEntry['first_entry'] ?? config("app.firstin");

        $alloted_mins_Oncall = 0.5; // 30 minutes
        if (count($scheduleEntry) >= 1) {
            /* With Schedule Entry */
            $in_Entry = $schedule;

            // $time_stamp = strtotime($in_Entry);
            // $new_Time_stamp = $time_stamp - ($alloted_hours * 3600);
            // $Calculated_allotedHours = date('Y-m-d H:i:s', $new_Time_stamp);
            // if ($isoncall) {
            //     $schedEntry = $time_stamp + ($alloted_mins_Oncall * 1800); // 30 mins

            //     $calIn = date("Y-m-d H:i:s", $schedEntry);
            //     $dtrentry = date("Y-m-d H:i:s", strtotime($dtrentry));
            //     $newentry = date("Y-m-d H:i:s", $schedEntry);
            //     if ($calIn <= $dtrentry) {

            //         //Not within 30 mins.
            //         // minus 30 mins then save as new Entry
            //         $newentry = date("Y-m-d H:i:s", strtotime($dtrentry . "-30 minutes"));
            //     }


            //     DailyTimeRecords::create([
            //         'biometric_id' => $biometric_id,
            //         'dtr_date' => $dtr_date,
            //         'second_in' =>  $newentry,
            //         'is_biometric' => 1,
            //     ]);
            // } else {
            //    if ($Calculated_allotedHours <=  $dtrentry) { //within alloted hours to timein
            DailyTimeRecords::create([
                'biometric_id' => $biometric_id,
                'dtr_date' => $dtr_date,
                'second_in' =>  $dtrentry,
                'is_biometric' => 1,
                'is_time_adjustment' => 0
            ]);
            //   }
            // }
        } else {
            /* No schedule Entry */
            DailyTimeRecords::create([
                'biometric_id' => $biometric_id,
                'dtr_date' => $dtr_date,
                'second_in' => $dtrentry,
                'is_biometric' => 1,
                'is_time_adjustment' => 0
            ]);
        }
    }



    public static function getTotalTimeRegistered($f1, $f2, $f3, $f4)
    {
        $total_rendered = 0;
        $time = self::forceToStrTimeFormat($f2) - self::forceToStrTimeFormat($f1);
        $hours = floor($time / 3600);
        $minutes = floor(($time % 3600) / 60);
        $total_rendered = floor(($hours * 60) + $minutes);
        if ($f3 && $f4) {
            $time1 = self::forceToStrTimeFormat($f4) - self::forceToStrTimeFormat($f3);
            $hours1 = floor($time1 / 3600);
            $minutes1 = floor(($time1 % 3600) / 60);
            $oah = $hours + $hours1;
            $oam = $minutes + $minutes1;
            $total_rendered = floor(($oah * 60) + $oam);
        }
        return $total_rendered >= 1 ? $total_rendered : 0;
    }

    public static function settingDateSchedule($entry, $sched)
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

    public static function validateSchedule($time_stamps_req)
    {
        if (count($time_stamps_req) >= 1) {
            if (
                isset($time_stamps_req['first_entry']) ||
                isset($time_stamps_req['second_entry']) ||
                isset($time_stamps_req['third_entry']) ||
                isset($time_stamps_req['last_entry'])
            ) {
                return true;
            }
        }

        return false;
    }


    public static function saveTotalWorkingHours($data, $value, $sequence, $time_stamps_req, $check_for_generate)
    {
        //return self::toWordsMinutes(59.71);

        // Ensure $fent is initialized
        $fent = '';

        foreach ($sequence as $sc) {
            /* Entries */
            $validate = $data;
            if (!empty($data) && (is_object($data) || (is_array($data) && count($data) > 0))) {
                // $data is either an object or an array with at least one element
                // You can access $data as needed
                $validate = is_array($data) ? $data[0] : $data;
            }
            $noHalfEntry = 0;
            $noHalfEntryfirst  = 0;

            $f1_entry = $validate->first_in;
            $f2_entry = $validate->first_out;
            $f3_entry =  $validate->second_in;
            $f4_entry = $validate->second_out;


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


            if (!$check_for_generate) {
                if ($f1_entry && !$f2_entry) {
                    $f2_entry = $sc['date_time'];
                } else {

                    if (!$f1_entry  && !$f2_entry && $f3_entry) {
                        $f4_entry = $sc['date_time'];
                    }
                }
            }
            if (isset($validate->second_in) || isset($validate->second_out)) {
                $f3entry = $validate->second_in;
                $f4entry = $validate->second_out;
            }
            if (!$check_for_generate) {
                if (!isset($f2entry) && !isset($f3entry)) {
                    $f2entry = $sc['date_time'];
                } else {
                    $f4entry = $sc['date_time'];
                }
            }
            $required_WH = isset($time_stamps_req['total_hours']) && $time_stamps_req['total_hours'] ? $time_stamps_req['total_hours'] : 8;
            $required_WH_Minutes = $required_WH * 60;


            if (self::validateSchedule($time_stamps_req)) {
                /* Schedule */

                $s1 = self::settingDateSchedule($f1_entry, $time_stamps_req['first_entry']);
                $s2 = self::settingDateSchedule($f2_entry, $time_stamps_req['second_entry']);
                $s3 = self::settingDateSchedule($f3_entry, $time_stamps_req['third_entry']);
                $s4 = self::settingDateSchedule($f4_entry, $time_stamps_req['last_entry']);
                /* End Schedule */
                $undertime_3rd_entry = 0;
                $undertime_Minutes_4th_entry = 0;
                $f1_entry_Time_stamp = strtotime($f1_entry);
                $s1_Time_stamp = strtotime($s1);
                $f2_entry_Time_stamp = strtotime($f2_entry);
                $s2_Time_stamp = strtotime($s2);
                if ($f3_entry && $f4_entry) {
                    if (isset($s3) && isset($s4)) {
                        $f3_entry_Time_stamp = strtotime($f3entry);
                        $s3_Time_stamp = strtotime($s3);
                        $f4_entry_Time_stamp = strtotime($f4entry);
                        $s4_Time_stamp = strtotime($s4);
                    }
                }
                $undertime_1st_entry = max(0, $f1_entry_Time_stamp - $s1_Time_stamp);
                $undertime_2nd_entry = max(0, $s2_Time_stamp - $f2_entry_Time_stamp);
                $overtime_2nd_entry = max(0, $f2_entry_Time_stamp - $s2_Time_stamp);
                if ($f3_entry && $f4_entry) {
                    $undert_3rd_entry = max(0, $f3_entry_Time_stamp - $s3_Time_stamp);
                    $undertime_4th_entry = max(0, $s4_Time_stamp - $f4_entry_Time_stamp);
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

                $undertime = $undertime_Minutes_1st_entry + $undertime_Minutes_2nd_entry + $undertime_3rd_entry + $undertime_Minutes_4th_entry;


                if ($f3_entry && $f4_entry) {
                    $overtime = $overtime_4th_entry;
                } else {
                    $overtime = $overtime_2nd_entry;
                    //return ;

                    if (isset($time_stamps_req['third_entry']) && isset($time_stamps_req['last_entry'])) {

                        $fent = date('Y-m-d', strtotime($f1_entry)) ?? null;
                        $second_Sched_secondin = $time_stamps_req['third_entry'];
                        $second_Sched_secondout = $time_stamps_req['last_entry'];

                        $s_3 = date("Y-m-d H:i:s", strtotime("$fent $second_Sched_secondin"));
                        $s_4 = date("Y-m-d H:i:s", strtotime("$fent $second_Sched_secondout"));

                        $s3_Time_stamp_ = strtotime($s_3);
                        $s4_Time_stamp_ = strtotime($s_4);


                        $totalHalfSEcs = $s4_Time_stamp_ - $s3_Time_stamp_;



                        $noHalfEntry = $totalHalfSEcs / 60;
                    }
                }



                $ot = floor($overtime);
                $ut = floor($undertime);
                $Schedule_Minutes  = self::getTotalTimeRegistered(
                    $s1,
                    $s2,
                    $s3,
                    $s4
                );

                /* Overtime */
                $overTime_inWords = self::toWordsMinutes($ot)['InWords'];
                $overTime_Minutes =  self::toWordsMinutes($ot)['InMinutes'];

                /* Undertime  */
                $underTime_inWords = self::toWordsMinutes($ut)['InWords'];
                $underTime_Minutes =  self::toWordsMinutes($ut)['InMinutes'];
            }



            $Registered_minutes = self::getTotalTimeRegistered(
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
                $tWH = floor($Schedule_Minutes - ($underTime_Minutes));
                $total_WH_words = self::toWordsMinutes($tWH)['InWords'];
                $total_WH_minutes = self::toWordsMinutes($tWH)['InMinutes'];
            } else {
                $tWH = floor($Schedule_Minutes - $underTime_Minutes);
                $total_WH_words = self::toWordsMinutes($tWH)['InWords'];
                $total_WH_minutes = self::toWordsMinutes($tWH)['InMinutes'];
            }


            /* Registered Minutes */
            //$Registered_minutes | total_minutes_reg

            /* ValueIn */
            // echo "First Entry:" . $f1_entry . "\n";
            // echo "Second Entry:" . $f2_entry . "\n";
            // echo "Third Entry:" . $f3_entry . "\n";
            // echo "Fourth Entry:" . $f4_entry . "\n\n\n";
            // echo "Bio Entry_ :" . $sc['date_time'] . "\n";

            if (!isset($s1) && !isset($s2)) {
                $underTime_inWords = null;
                $overTime_inWords = null;
                $totalWH_words = null;
            }
            //  Output undertime in minutes
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

        // return [
        //     //   'first_out' => strtotime($sc['date_time']),
        //     'second_out' => $sc['date_time'],
        //     'total_working_hours' => $total_WH_words,
        //     'required_working_hours' => $required_WH,
        //     'required_working_minutes' => $required_WH_Minutes,
        //     'total_working_minutes' => $total_WH_minutes,
        //     'overall_minutes_rendered' => $over_all_minutes_Rendered,
        //     'total_minutes_reg' => $Registered_minutes,
        //     'undertime' => $underTime_inWords,
        //     'undertime_minutes' => $underTime_Minutes,
        //     'overtime' => $overTime_inWords,
        //     'overtime_minutes' => $overTime_Minutes
        // ];

        if ($total_WH_minutes < 0) {
            $total_WH_words = '0 minute';
            $total_WH_minutes = 0;
            $over_all_minutes_Rendered = 0;
            $underTime_inWords = 'undefined';
            $underTime_Minutes = 0;
        }
        //  echo "Overall Minutes Rendered :" . $overallminutesRendered . "\n";

        if ($f1_entry && !$f2_entry || !$f1_entry && !$f2_entry && $f3_entry && !$f4_entry) {
            if (empty($fent)) {
                $fent = date('Y-m-d');
            }
            $first_Sched_firstin = $time_stamps_req['first_entry'];
            $first_Sched_firstout = $time_stamps_req['second_entry'];

            $s_1 = date("Y-m-d H:i:s", strtotime("$fent $first_Sched_firstin"));
            $s_2 = date("Y-m-d H:i:s", strtotime("$fent $first_Sched_firstout"));

            $s1_Time_stamp_ = strtotime($s_1);
            $s2_Time_stamp_ = strtotime($s_2);
            $totalHalfSEcsfirst = $s2_Time_stamp_ - $s1_Time_stamp_;

            $noHalfEntryfirst =  $totalHalfSEcsfirst / 60;
        }
        $attr = [
            'total_WH_words' => $total_WH_words,
            'required_WH' => $required_WH,
            'required_WH_Minutes' => $required_WH_Minutes,
            'total_WH_minutes' => $total_WH_minutes,
            'over_all_minutes_Rendered' => $over_all_minutes_Rendered,
            'Registered_minutes' => $Registered_minutes,
            'underTime_inWords' =>  self::toWordsMinutes($underTime_Minutes +  ($noHalfEntry + $noHalfEntryfirst))['InWords'],
            'underTime_Minutes' => $underTime_Minutes +  ($noHalfEntry + $noHalfEntryfirst),
            'overTime_inWords' => $overTime_inWords,
            'overTime_Minutes' => $overTime_Minutes
        ];

        if (isset($f3_entry) && isset($f4_entry)) {
            self::SaveToDTR($check_for_generate, $validate, $attr, $sc, 'second_out');
        } else {
            if ($f1_entry && $f2_entry && $f3_entry && !$f4_entry) {
                self::SaveToDTR($check_for_generate, $validate, $attr, $sc, 'second_out');
            } else {
                self::SaveToDTR($check_for_generate, $validate, $attr, $sc, 'first_out');
            }
        }
    }

    public static function SaveToDTR($check_for_generate, $validate, $attr, $sc, $out)
    {
        if ($check_for_generate) {
            DailyTimeRecords::find($validate->id)->update([
                'total_working_hours' => $attr['total_WH_words'],
                'required_working_hours' => $attr['required_WH'],
                'required_working_minutes' => $attr['required_WH_Minutes'],
                'total_working_minutes' => $attr['total_WH_minutes'],
                'overall_minutes_rendered' => $attr['over_all_minutes_Rendered'],
                'total_minutes_reg' => $attr['Registered_minutes'],
                'undertime' => $attr['underTime_inWords'],
                'undertime_minutes' => $attr['underTime_Minutes'],
                'overtime' => $attr['overTime_inWords'],
                'overtime_minutes' => $attr['overTime_Minutes'],
                'is_time_adjustment' => 0
            ]);
        } else {

            DailyTimeRecords::find($validate->id)->update([
                //   'first_out' => strtotime($sc['date_time']),
                $out => $sc['date_time'],
                'total_working_hours' => $attr['total_WH_words'],
                'required_working_hours' => $attr['required_WH'],
                'required_working_minutes' => $attr['required_WH_Minutes'],
                'total_working_minutes' => $attr['total_WH_minutes'],
                'overall_minutes_rendered' => $attr['over_all_minutes_Rendered'],
                'total_minutes_reg' => $attr['Registered_minutes'],
                'undertime' => $attr['underTime_inWords'],
                'undertime_minutes' => $attr['underTime_Minutes'],
                'overtime' => $attr['overTime_inWords'],
                'overtime_minutes' => $attr['overTime_Minutes'],
                'is_time_adjustment' => 0,
            ]);
        }
    }


    public static function saveIntervalValidation($sequence, $validate)
    {
        foreach ($sequence as $sc) {
            $time_out = new DateTime(date('Y-m-d H:i:s', strtotime($validate->first_out)));
            $time_in = new DateTime($sc['date_time']);
            $interval =  $time_out->diff($time_in);
            $minutes = $interval->i; // Minutes
            $seconds = $interval->s; // Seconds
            $time_interval = '';
            $IntervalStatus = '';
            if ($minutes < config("app.alloted_dtr_interval")) {
                /* Calculate the time interval */
                $Interval_Status = 'Invalid';
            } else {
                $Interval_Status = 'OK';
            }
            $time_interval = [
                'Status' => $Interval_Status,
                'alloted_dtr_interval' => config("app.alloted_dtr_interval"),
                'minutes' => $minutes,
                'seconds' => $seconds,
            ];
            DailyTimeRecords::find($validate->id)->update([
                // 'second_in' => strtotime($sc['date_time']),
                'second_in' => $sc['date_time'],
                'interval_req' => json_encode($time_interval),
                'is_time_adjustment' => 0
            ]);
        }
    }

    public static function sequence($sched, $attdData)
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

    public function determineEntry()
    {
    }

    public static function statusDescription($employee_ID, $lastStatus, $entry)
    {

        $on_Active_Status = date('Y-m-d H:i:s', strtotime($entry . '-5 minutes'));
        switch ($entry) {
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
                $biometric_id = $employee_ID;
                $date_now = date('Y-m-d');
                $Records = DailyTimeRecords::where('biometric_id', $biometric_id)
                    ->whereDate('created_at', $date_now)->get();
                if (count($Records) >= 1) {
                    foreach ($Records as $row) {
                        if ($row->first_in) {
                            $on_Active_Status = $row->first_in;
                            $status_description = 'CHECK-OUT';
                        }
                        if ($row->first_out) {
                            $on_Active_Status = $row->first_out;
                            $status_description = 'CHECK-IN';
                        }
                        if ($row->second_in) {
                            $on_Active_Status = $row->second_in;
                            $status_description = 'CHECK-OUT';
                        }
                    }
                } else {
                    $status_description = 'CHECK-IN';
                }
                break;
        }
        $dt = [
            0 => ['date_time' => $entry],
        ];
        if (!self::withinInterval($on_Active_Status, $dt)) {
            $Within_interval = "NO";
        } else {
            $Within_interval = "YES";
        }
        return [
            'description' => $lastStatus,
            'within_interval' => $Within_interval,
            'isEmployee' => self::isEmployee($employee_ID)
        ];
    }

    public static function checkIfFingerPrintExist($tad, $userPin)
    {
        $usertemp = $tad->get_user_template(['pin' => $userPin]);
        $utemp = simplexml_load_string($usertemp);
        if (empty($utemp->Row->Result)) {
            return false;
        }
        return true;
    }

    private static function getDeviceName($deviceid)
    {
        $data = Devices::where('id', $deviceid)->get();
        if (count($data) >= 1) {
            return $data[0]->device_name;
        }
    }

    public static function merge_unique_entries($log_data_Array, $new_Rec)
    {
        // Create an associative array to keep track of unique entries
        $unique_entries = [];

        // Add existing entries to the unique array using a unique key
        foreach ($log_data_Array as $entry) {
            $key = $entry['biometric_id'] . '-' . $entry['date_time'] . '-' . $entry['status'];
            $unique_entries[$key] = $entry;
        }

        // Add new entries to the unique array using the same unique key
        foreach ($new_Rec as $entry) {
            $key = $entry['biometric_id'] . '-' . $entry['date_time'] . '-' . $entry['status'];
            if (!isset($unique_entries[$key])) {
                $unique_entries[$key] = $entry;
            }
        }

        // Convert the unique array back to a regular array
        return array_values($unique_entries);
    }

    public static function saveDTRLogs($check_Records, $validate, $device, $yesterdate)
    {
        $new_timing = 0;
        $unique_Employee_IDs = [];
        $date = date('Y-m-d');
        foreach ($check_Records as $record) {
            $employee_ID = $record['biometric_id'];
            if (!in_array($employee_ID, $unique_Employee_IDs)) {
                $unique_Employee_IDs[] = $employee_ID;
            }
            if ($yesterdate) {
                $date = date('Y-m-d', strtotime($record['date_time']));
            }
        }
        foreach ($unique_Employee_IDs as $id) {
            $employee_Records = array_filter($check_Records, function ($att) use ($id) {
                return $att['biometric_id'] == $id;
            });
            foreach ($employee_Records as $kk => $new) {
                $rec = DailyTimeRecords::whereDate('dtr_date', date('Y-m-d', strtotime($new['date_time'])))->where('biometric_id', $new['biometric_id'])->first();
                $entry = "Logged";
                if ($rec) {
                    $f1 = $rec->first_in;
                    $f2 = $rec->first_out;
                    $f3 = $rec->second_in;
                    $f4 = $rec->second_out;

                    if ($f1 == $new['date_time'] || $f2 == $new['date_time'] || $f3 == $new['date_time'] || $f4 == $new['date_time']) {
                        $entry = "Daily Time Recorded";
                    }
                }
                $new_Rec[] = [
                    'timing' => $new_timing,
                    'biometric_id' => $new['biometric_id'],
                    'name' => $new['name'],
                    'date_time' => $new['date_time'],
                    'status' => $new['status'],
                    'status_description' => $new['status_description'],
                    'entry_status' =>  $entry,
                    'datepull' => date('Y-m-d H:i:s')
                ];
                $new_timing++;
            }
            // /* Checking if DTR logs for the day is generated */
            $check_DTR_Logs = DailyTimeRecordlogs::whereDate('dtr_date', $date)->where('biometric_id', $id)->where('validated', 1);


            if (count($check_DTR_Logs->get()) >= 1) {
                // /* Counting logs data */
                $log_Data = count($check_DTR_Logs->get()) >= 1 ? $check_DTR_Logs->get()[0]->json_logs : '';
                $log_data_Array = json_decode($log_Data, true);
                $OldRecord = json_decode($log_Data, true);
                // /* Saving individually to user-attendance jsonLogs */


                $log_data_Array =  self::merge_unique_entries($log_data_Array, $new_Rec);

                $ndata = [];
                foreach ($log_data_Array as $n) {
                    if ($n['biometric_id'] == $id) {
                        $ndata[] = $n;
                    }
                }

                $newt = 0;
                $nr = [];

                foreach ($ndata as $new) {


                    $rec = DailyTimeRecords::whereDate('dtr_date', date('Y-m-d', strtotime($new['date_time'])))->where('biometric_id', $new['biometric_id'])->first();
                    $entry = "Logged";

                    if ($rec) {
                        $f1 = $rec->first_in;
                        $f2 = $rec->first_out;
                        $f3 = $rec->second_in;
                        $f4 = $rec->second_out;

                        if ($f1 == $new['date_time'] || $f2 == $new['date_time'] || $f3 == $new['date_time'] || $f4 == $new['date_time']) {
                            $entry = "Daily Time Recorded";
                        }
                    }
                    $devID = $device['id'];
                    $devName = self::getDeviceName($device['id']);

                    $datepull =  date('Y-m-d H:i:s');
                    if (isset($new['device_id'])) {

                        $devID = $new['device_id'];
                        $devName = self::getDeviceName($new['device_id']);
                        $datepull = $new['datepull'];
                    }
                    /* extract Data here */

                    $nr[] = [
                        'timing' => $newt,
                        'biometric_id' => $new['biometric_id'],
                        'name' => $new['name'],
                        'date_time' => $new['date_time'],
                        'status' => $new['status'],
                        'status_description' => $new['status_description'],
                        'device_id' => $devID,
                        'device_name' => $devName,
                        'entry_status' =>  $entry,
                        'datepull' => $datepull
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
                    $rec = DailyTimeRecords::whereDate('dtr_date', date('Y-m-d', strtotime($new['date_time'])))->where('biometric_id', $new['biometric_id'])->first();
                    $entry = "Logged";
                    if ($rec) {
                        $f1 = $rec->first_in;
                        $f2 = $rec->first_out;
                        $f3 = $rec->second_in;
                        $f4 = $rec->second_out;

                        if ($f1 == $new['date_time'] || $f2 == $new['date_time'] || $f3 == $new['date_time'] || $f4 == $new['date_time']) {
                            $entry = "Daily Time Recorded";
                        }
                    }

                    $devID = $device['id'];
                    $devName = self::getDeviceName($device['id']);
                    $nr[] = [
                        'timing' => $newt,
                        'biometric_id' => $new['biometric_id'],
                        'name' => $new['name'],
                        'date_time' => $new['date_time'],
                        'status' => $new['status'],
                        'status_description' => $new['status_description'],
                        'device_id' => $devID,
                        'device_name' =>  $devName,
                        'entry_status' =>  $entry,
                        'datepull' => date('Y-m-d H:i:s')
                    ];
                    $newt++;
                }

                $chec_kDTR = DailyTimeRecords::whereDate('dtr_date', $date)->where('biometric_id', $id);
                if (count($chec_kDTR->get()) >= 1) {
                    DailyTimeRecordlogs::create([
                        'biometric_id' => $id,
                        'dtr_id' => $chec_kDTR->get()[0]->id,
                        'json_logs' => json_encode($nr),
                        'validated' => $validate,
                        'dtr_date' => $date
                    ]);
                } else {
                    $check_DTR_Logs_Invalid = DailyTimeRecordlogs::whereDate('dtr_date', $date)->where('biometric_id', $id)->where('validated', 0)->get();
                    if (count($check_DTR_Logs_Invalid) == 0) {
                        DailyTimeRecordlogs::create([
                            'biometric_id' => $id,
                            'dtr_id' => 0,
                            'json_logs' => json_encode($nr),
                            'validated' => $validate,
                            'dtr_date' => $date
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


    public static function getAttendance($attendance)
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

    public static function getEmployee($user_Inf)
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

    public static  function getLatestEntry($mapdtr)
    {
        //CHECK-IN
        if ($mapdtr['first_in'] && !$mapdtr['first_out'] && !$mapdtr['second_in'] && !$mapdtr['second_out']) {
            return "CHECK-OUT";
        }
        if ($mapdtr['first_in'] && $mapdtr['first_out'] && !$mapdtr['second_in'] && !$mapdtr['second_out']) {
            return "CHECK-IN";
        }
        if ($mapdtr['first_in'] && $mapdtr['first_out'] && $mapdtr['second_in'] && !$mapdtr['second_out']) {
            return "CHECK-OUT";
        }
    }
    public static function getEmployeeAttendance($attendance_Logs, $Employee_Info)
    {
        $Employee_Attendance = [];
        $processedLogs = []; // To avoid reprocessing the same log entries
        // return $attendance_Logs;
        foreach ($attendance_Logs as $key => $attendance_Log) {
            $employee_ID = $attendance_Log['biometric_id'];
            $employee_Name = '';
            foreach ($Employee_Info as $info) {
                if ($info['biometric_id'] === $employee_ID) {
                    $employee_Name = $info['name'];
                    break;
                }
            }

            $dtr = DailyTimeRecords::where('dtr_date', date('Y-m-d', strtotime($attendance_Log['date_time'])))
                ->where('biometric_id', $employee_ID)
                ->first();

            // Mapping DailyTimeRecords to a simpler array format
            $mapdtr = null;
            if ($dtr) {
                $mapdtr = [
                    'first_in' => $dtr->first_in,
                    'first_out' => $dtr->first_out,
                    'second_in' => $dtr->second_in,
                    'second_out' => $dtr->second_out
                ];
            }

            // Skip if already processed
            if (isset($processedLogs[$employee_ID])) {
                continue;
            }

            $previousTimestamp = null;
            $lastStatus = null;
            $lentry = null;


            foreach ($attendance_Logs as $index => $entry) {
                if ($entry['biometric_id'] !== $employee_ID) {
                    continue;
                }



                $currentTimestamp = strtotime($entry['date_time']);

                // Check if there is a match in $mapdtr
                if ($mapdtr) {
                    $currentDateTime = $entry['date_time'];

                    if (in_array($currentDateTime, $mapdtr)) {
                        if ($currentDateTime == $mapdtr['first_in']) {
                            $entry['entry_status'] = "CHECK-IN";
                            $lastStatus = "CHECK-IN";
                        } elseif ($currentDateTime == $mapdtr['first_out']) {
                            $entry['entry_status'] = "CHECK-OUT";
                            $lastStatus = "CHECK-OUT";
                        } elseif ($currentDateTime == $mapdtr['second_in']) {
                            $entry['entry_status'] = "CHECK-IN";
                            $lastStatus = "CHECK-IN";
                        } elseif ($currentDateTime == $mapdtr['second_out']) {
                            $entry['entry_status'] = "CHECK-OUT";
                            $lastStatus = "CHECK-OUT";
                        }
                    }
                }


                if (!isset($entry['entry_status'])) {




                    $interval = ($currentTimestamp - $previousTimestamp) / 60;

                    if ($interval <= 3) { // 3 minutes interval
                        $entry['entry_status'] = "LOGGED";
                    } else {
                        if ($lastStatus == "CHECK-IN") {
                            $entry['entry_status'] = "CHECK-OUT";
                            $lastStatus = "CHECK-OUT";
                        } else if ($lastStatus == "CHECK-OUT") {
                            $entry['entry_status'] = "CHECK-IN";
                            $lastStatus = "CHECK-IN";
                        } else {
                            if ($mapdtr) {
                                $entry['entry_status'] = self::getLatestEntry($mapdtr);
                                $lastStatus = self::getLatestEntry($mapdtr);
                            } else {
                                $entry['entry_status'] = "CHECK-IN";
                                $lastStatus = "CHECK-IN";
                            }
                        }
                    }
                }
                $entry['timing'] = $key;
                $entry['name'] = $employee_Name;
                $entry['status_description'] = self::statusDescription($employee_ID, $entry['entry_status'], $entry['date_time']);
                $Employee_Attendance[] = $entry; // Add entry to the main array

                $previousTimestamp = $currentTimestamp;
            }

            $processedLogs[$employee_ID] = true;
        }

        return $Employee_Attendance;
    }



    public static function forceToStrTimeFormat($date_Or_Timestamp)
    {
        if (is_numeric($date_Or_Timestamp) && (int)$date_Or_Timestamp == $date_Or_Timestamp) {
            return $date_Or_Timestamp;
        } elseif (strtotime($date_Or_Timestamp) !== false || DateTime::createFromFormat('Y-m-d', $date_Or_Timestamp) instanceof DateTime) {
            return strtotime($date_Or_Timestamp);
        } else {
            return null;
        }
    }




    public static function toWordsMinutes($totalMinutes)
    {
        // $totalMinutes = 40.75;
        $hours = '';
        $minutes = floor($totalMinutes);
        $seconds = fmod($totalMinutes, 1) * 100;

        //   echo 'minutes : ' . $minutes . " seconds : " . $seconds . "\n";

        if ($seconds >= 60) {
            $extmin = floor($seconds / 60); // Get the whole minutes
            $extsecs = $seconds % 60; // Get the remaining seconds

            $minutes += $extmin;
            $seconds = $extsecs;
        }
        //  echo $minutes . ' minutes and ' . round($seconds) . ' seconds' . "\n";
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $minutes %= 60;

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
        } else {
            $inWords = $minutes . ' minute';
            if ($minutes > 1) {
                $inWords .= 's';
            }
        }

        if ($seconds) {
            $inWords .= ' and ' . round($seconds) . ' second';
            if ($seconds > 1) {
                $inWords .= 's';
            }
        }


        return [
            'InWords' => $inWords,
            'InMinutes' => $totalMinutes,

        ];
    }




    /**
     * This Function backups selected table in database.
     * which will be stored in : Storage/Backup
     */
    public static function backUpTable($table)
    {
        $validateFile = storage_path('backups/' . $table . '_' . now()->format('Y_m_d') . '.sql');
        /***
         * Requirements for the system to validate file--
         */
        $pullingRequirements = "30 minutes";
        /* --------------------------------- */
        if (file_exists($validateFile)) {
            $fileContent = file_get_contents($validateFile);
            // Extract the line with the timestamp using a regular expression
            if (preg_match('/--(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $fileContent, $matches)) {
                $timestampLine = ltrim($matches[0], '-');
                $convertedDate = date('Y-m-d H:i:s', strtotime($timestampLine . ' +' . $pullingRequirements));
                if (now() < $convertedDate) {
                    return;
                } else {
                    unlink($validateFile);
                }
            }
        }
        $currentDate = now()->toDateString();
        $data = DB::table($table)->whereDate('created_at', $currentDate)->get();
        $header = "--" . now() . PHP_EOL . "-- ZCMC_CIIS@2023 " . PHP_EOL;
        $sqlDump =  $header . "-- Daily Backup of table '{$table}' "  . PHP_EOL;
        foreach ($data as $row) {
            $values = implode(', ', array_map(function ($value) {
                return "'" . addslashes($value) . "'";
            }, (array)$row));
            $sqlDump .= "INSERT INTO {$table} VALUES ({$values});" . PHP_EOL;
        }
        $directory = storage_path('backups');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $backupPath = $directory . '/' . $table . '_' . now()->format('Y_m_d') . '.sql';
        file_put_contents($backupPath, $sqlDump);
    }
}
