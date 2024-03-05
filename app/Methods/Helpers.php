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

        $parts = explode('-', $date_now);
        // $parts[1] will contain "2024"
        // $parts[2] will contain "2"
        if (count($parts) >= 3) {
            $check = $parts[0];
            $year = $parts[1];
            $month = $parts[2];
            if ($check === "all") {
                return $this->Allschedule($biometric_id, $month, $year);
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
 END AS date,

  CASE
     WHEN s.id IS NOT NULL THEN
          (SELECT is_on_call
          FROM employee_profile_schedule
          WHERE schedule_id = s.id limit 1)
     ELSE 'NONE'
 END AS is_on_call
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



        if ($this->isNurseOrDoctor($biometric_id)) {
            /* Check if Available Schedule */
            return $this->getEmployeeSched($get_Sched, $f1, $f2, $f3, $f4, true);
        }
        return $this->getEmployeeSched($get_Sched, $f1, $f2, $f3, $f4, false);
    }

    public function Allschedule($biometric_id, $month, $year)
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
                'esc.is_on_call'
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


            $scheds[] = [
                'scheduleDate' => $row->date,
                'first_entry' => $row->first_in,
                'second_entry' => $row->first_out,
                'third_entry' => $row->second_in,
                'last_entry' => $row->second_out,
                'total_hours' => $row->total_hours,
                'is_on_call' => $row->is_on_call,
                'arrival_departure' => $dp
            ];
        }
        $Arrival_departure =  array_values(array_unique($arrival_d));

        return [
            'schedule' => $scheds,
            'arrival_departure' => Implode("&", $Arrival_departure)
        ];
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
                'date' => $get_Sched[0]->date,
                'is_on_call' => $get_Sched[0]->is_on_call,
            ];
        }
        return [
            'first_entry' => null,
            'second_entry' => null,
            'third_entry' => null,
            'last_entry' => null,
            'total_hours' => env('REQUIRED_WORKING_HOURS'),
            'date' => null,
            'date_end' => null,
            'is_on_call' => null,
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

    public function CurrentSchedule($biometric_id, $value, $yesterdayRecord)
    {

        $entrydateYear = date('Y', strtotime($value['date_time']));
        $entrydateMonth = date('m', strtotime($value['date_time']));
        $schedule = $this->getSchedule($biometric_id, "all-{$entrydateYear}-{$entrydateMonth}");
        // Put employee ID

        $dsched = [];
        $entry = date('Y-m-d', strtotime($value['date_time']));
        $entryTime = date('H:i', strtotime($value['date_time']));
        if ($yesterdayRecord) {
            $entrydateYear = date('Y', strtotime($value['first_in']));
            $entrydateMonth = date('m', strtotime($value['first_in']));
            $schedule = $this->getSchedule($biometric_id, "all-{$entrydateYear}-{$entrydateMonth}");

            $entry = date('Y-m-d', strtotime($value['first_in']));
            $entryTime = date('H:i', strtotime($value['first_in']));
        }

        $daySchedule = array_values(array_filter($schedule['schedule'], function ($row) use ($entry, $entryTime) {
            return date('Y-m-d', strtotime($row['scheduleDate'])) === $entry &&
                date('Y-m-d H:i', strtotime($entry . ' ' . $entryTime)) <= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['first_entry'] . ' +4 hours')) ||
                date('Y-m-d H:i', strtotime($entry . ' ' . $entryTime)) <= date('Y-m-d H:i', strtotime($row['scheduleDate'] . ' ' . $row['second_entry'] . ' +4 hours'));
        }));
        $break_Time_Req = $this->getBreakSchedule($biometric_id, $daySchedule);

        if (count($daySchedule) >= 1) {
            $dsched = $daySchedule[0];
        }

        return [
            'daySchedule' => $dsched,
            'break_Time_Req' => $break_Time_Req,

        ];
    }



    public  function inEntry($biometric_id, $alloted_hours, $sc, $sched, $delay)
    {


        $first_Entry = $sched['first_entry'];

        $time_stamp = strtotime($first_Entry);

        $new_Time_stamp = $time_stamp - ($alloted_hours * 3600);
        $Calculated_allotedHours = date('Y-m-d H:i:s', $new_Time_stamp);

        $employee_In = date('Y-m-d H:i:s', strtotime($sc['date_time']));
        $dtr_date = date('Y-m-d', strtotime($sc['date_time']));

        if ($delay) {
            DailyTimeRecords::create([
                'biometric_id' => $biometric_id,
                // 'first_in' => strtotime($sc['date_time']),
                'dtr_date' => $dtr_date,
                'first_in' => $sc['date_time'],
                'is_biometric' => 1,
            ]);
        } else {
            if ($Calculated_allotedHours <= $employee_In) {
                DailyTimeRecords::create([
                    'biometric_id' => $biometric_id,
                    // 'first_in' => strtotime($sc['date_time']),
                    'dtr_date' => $dtr_date,
                    'first_in' => $sc['date_time'],
                    'is_biometric' => 1,
                ]);
            }

            // if ($first_Entry == null) {
            //     return $sc['date_time'];
            // }
        }
    }


    public function saveDTRRecords($check_Records, $delay)
    {
        try {
            if (count($check_Records) >= 1) {

                foreach ($check_Records as $key => $value) {

                    $biometric_id =  $value['biometric_id'];

                    if ($delay) {
                        $entrydate = date('Y-m-d', strtotime($value['date_time']));
                    } else {
                        $entrydate = date('Y-m-d');
                    }

                    $break_Time_Req = $this->CurrentSchedule($biometric_id, $value, false)['break_Time_Req'];
                    $daySchedule = $this->CurrentSchedule($biometric_id, $value, false)['daySchedule'];


                    if ($this->isEmployee($biometric_id)) { // Validating if User is an employee with Biometric data and employee data


                        $validate = DailyTimeRecords::whereDate('dtr_date', $entrydate)->where('biometric_id', $biometric_id)->latest()->first();


                        if ($validate !== null) {
                            /* Updating All existing  Records */

                            $f1 = $validate->first_in;
                            $f2 =  $validate->first_out;
                            $f3 = $validate->second_in;
                            $f4 = $validate->second_out;
                            $rwm = $validate->required_working_minutes;
                            $o_all_min = $validate->total_working_minutes;

                            /* -------------    -----------------------------------------Replace this values-------------------------------------------------------------------- */

                            /* GET THE DATA BASED ON EMPLOYEE SCHEDULE */
                            $time_stamps_req = $this->getSchedule($biometric_id,  $check_Records[0]['date_time']); //biometricID

                            /* ---------------------------------------------------------------------------------------------------------------------------------------------- */

                            if ($f1 && !$f2 && !$f3 && !$f4) {

                                if ($value['status'] == 255) {
                                    if ($this->withinInterval($f1, $this->sequence(0, [$value]))) {
                                        $this->saveTotalWorkingHours(
                                            $validate,
                                            $value,
                                            $this->sequence(0, [$value]),
                                            $time_stamps_req,
                                            false
                                        );
                                    }
                                }
                                if ($value['status'] == 1) {
                                    $this->saveTotalWorkingHours(
                                        $validate,
                                        $value,
                                        $this->sequence(0, [$value]),
                                        $time_stamps_req,
                                        false
                                    );
                                }
                            }

                            /* check In_am and out_am and not set in_pm */
                            /*
                   -here we are validating the Out and In interval between second Entry to third entry
                   -if the Time of IN is within the interval Requirements. We mark status as OK. else
                    Invalid 3rd Entry
                   */

                            if ($f1 && $f2 && !$f3 && !$f4) {

                                $percent_Trendered = floor($rwm * 0.6); //60% of Time rendered. then considered as 1 entry
                                if (count($break_Time_Req) >= 1) {
                                    if ($o_all_min <= $percent_Trendered) { // if allmins rendered is less than the 60% time req . then accept a second entry

                                        if ($value['status'] == 255) {
                                            if ($this->withinInterval($f2, $this->sequence(0, [$value]))) {
                                                $this->saveIntervalValidation(
                                                    $this->sequence(0, [$value]),
                                                    $validate
                                                );
                                            }
                                        }
                                        if ($value['status'] == 0) {

                                            $this->saveIntervalValidation(
                                                $this->sequence(0, [$value]),
                                                $validate
                                            );
                                        }
                                    }
                                } else {
                                    //Save new DTR|

                                    $daySchedule = $this->CurrentSchedule($biometric_id, $check_Records[0], false)['daySchedule'];


                                    if ($value['status'] == 0 || $value['status'] == 255) {

                                        $scheduleEntry = null;
                                        if (isset($daySchedule['is_on_call']) && $daySchedule['is_on_call']) {
                                            // $scheduleEntry = date('Y-m-d H:i:s', strtotime($time_stamps_req['date_start'] . ' ' . $time_stamps_req['first_entry'] . '+' . $max_allowed_entry_for_oncall . ' minutes'));
                                            $scheduleEntry = $daySchedule['first_entry'];
                                        }
                                        $this->SaveFirstEntry(
                                            $this->sequence(0, [$value]),
                                            $break_Time_Req,
                                            $biometric_id,
                                            $delay,
                                            $scheduleEntry
                                        );
                                    }
                                }
                            }
                            /* check In_am and out_am and  in_pm and not set out_pm */
                            /*
                   We have set the last entry,
                   assuming that the first, second, and third entries have also been established.
                   Overtime and undertime, as well as working hours, have already been calculated.
                */
                            if ($f1 && $f2 && $f3 && !$f4) {

                                if (count($break_Time_Req) >= 1) {
                                    if ($value['status'] == 255) {
                                        if ($this->withinInterval($f3, $this->sequence(0, [$value]))) {
                                            $this->saveTotalWorkingHours(
                                                $validate,
                                                $value,
                                                $this->sequence(0, [$value]),
                                                $time_stamps_req,
                                                false
                                            );
                                        }
                                    }


                                    if ($value['status'] == 1) {
                                        $this->saveTotalWorkingHours(
                                            $validate,
                                            $value,
                                            $this->sequence(0, [$value]),
                                            $time_stamps_req,
                                            false
                                        );
                                    }
                                }
                            }
                            /*Check notset in_am and notset out_pm and  check In_pm and not set out_pm */
                            /*
                    Here we are setting the Last entry of Second half. with no First half of Entries.
                    Overtime and undertime, as well as working hours, have already been calculated.
                */
                            if (!$f1 && !$f2 && $f3 && !$f4) {

                                if (count($break_Time_Req) >= 1) {

                                    if ($value['status'] == 255) {
                                        if ($this->withinInterval($f3, $this->sequence(0, [$value]))) {
                                            $this->saveTotalWorkingHours(
                                                $validate,
                                                $value,
                                                $this->sequence(0, [$value]),
                                                $time_stamps_req,
                                                false
                                            );
                                        }
                                    }

                                    if ($value['status'] == 1) {
                                        $this->saveTotalWorkingHours(
                                            $validate,
                                            $value,
                                            $this->sequence(0, [$value]),
                                            $time_stamps_req,
                                            false
                                        );
                                    }
                                }
                            }
                        } else {


                            if ($delay) {
                                /* Save new records */
                                if ($value['status'] == 0 || $value['status'] == 255) {

                                    $scheduleEntry = null;
                                    if (isset($daySchedule['is_on_call']) && $daySchedule['is_on_call']) {
                                        // $scheduleEntry = date('Y-m-d H:i:s', strtotime($time_stamps_req['date_start'] . ' ' . $time_stamps_req['first_entry'] . '+' . $max_allowed_entry_for_oncall . ' minutes'));
                                        $scheduleEntry = $daySchedule['first_entry'];
                                    }
                                    $this->SaveFirstEntry(
                                        $this->sequence(0, [$value]),
                                        $break_Time_Req,
                                        $biometric_id,
                                        $delay,
                                        $scheduleEntry
                                    );
                                }
                            } else {


                                /**
                                 * Here we are checking if theres an existing first entry this is  for nursing and doctors
                                 * which has two entries for schedule only.
                                 * if data not found. then we save into first entry
                                 */
                                $yester_date = date('Y-m-d', strtotime('-1 day'));
                                $time_stamps_req = $this->getSchedule($biometric_id, date('Y-m-d H:i:s', strtotime($yester_date . ' ' . date('H:i:s', strtotime($check_Records[0]['date_time'])))));

                                /*
                                first_entry
                                third_entry
                                */



                                $check_yesterday_Records = DailyTimeRecords::whereDate('first_in', $yester_date)->where('biometric_id', $biometric_id)->latest()->first();
                                $daySchedule = $this->CurrentSchedule($biometric_id, $check_yesterday_Records, true)['daySchedule'];



                                if ($check_yesterday_Records !== null) {


                                    $f_1 = $check_yesterday_Records->first_in;
                                    $f_2 = $check_yesterday_Records->first_out;
                                    $bio_ID = $check_yesterday_Records->biometric_id;


                                    /* this entry only */
                                    if ($f_1 && !$f_2) {

                                        if (count($this->CurrentSchedule($biometric_id, $check_yesterday_Records, true)['break_Time_Req']) === 0) {

                                            /* Validation add expiry. */
                                            $TimeAllowance_ =  date('Y-m-d H:i:s', strtotime(date('Y-m-d ' . $time_stamps_req['second_entry']) . " +5 hours")); // 5 hours allowance

                                            foreach ($check_Records as $key => $chrc) {
                                                if ($chrc['biometric_id'] == $bio_ID) {
                                                    if ($TimeAllowance_ > $chrc['date_time']) { // Validation to Ignore Yesterday entry. 5 hours

                                                        if ($chrc['status'] == 255) {
                                                            if ($this->withinInterval($f_1, $this->sequence(0, [$chrc]))) {
                                                                $this->saveTotalWorkingHours(
                                                                    $check_yesterday_Records,
                                                                    $chrc,
                                                                    $this->sequence(0, [$chrc]),
                                                                    $time_stamps_req,
                                                                    false
                                                                );
                                                            }
                                                        }
                                                        if ($chrc['status'] == 1) {
                                                            //employeeID
                                                            $this->SaveTotalWorkingHours(
                                                                $check_yesterday_Records,
                                                                $chrc,
                                                                $this->sequence(0, [$chrc]),
                                                                $time_stamps_req,
                                                                false
                                                            );
                                                        }
                                                    }
                                                }
                                            }
                                        } else {
                                            if ($value['status'] == 0 || $value['status'] == 255) {

                                                $scheduleEntry = null;
                                                if (isset($daySchedule['is_on_call']) && $daySchedule['is_on_call']) {
                                                    // $scheduleEntry = date('Y-m-d H:i:s', strtotime($time_stamps_req['date_start'] . ' ' . $time_stamps_req['first_entry'] . '+' . $max_allowed_entry_for_oncall . ' minutes'));
                                                    $scheduleEntry = $daySchedule['first_entry'];
                                                }
                                                $this->SaveFirstEntry(
                                                    $this->sequence(0, [$value]),
                                                    $break_Time_Req,
                                                    $biometric_id,
                                                    $delay,
                                                    $scheduleEntry
                                                );
                                            }
                                        }
                                    } else {

                                        /* Save new records */
                                        if ($value['status'] == 0 || $value['status'] == 255) {

                                            $scheduleEntry = null;
                                            if (isset($daySchedule['is_on_call']) && $daySchedule['is_on_call']) {
                                                // $scheduleEntry = date('Y-m-d H:i:s', strtotime($time_stamps_req['date_start'] . ' ' . $time_stamps_req['first_entry'] . '+' . $max_allowed_entry_for_oncall . ' minutes'));
                                                $scheduleEntry = $daySchedule['first_entry'];
                                            }
                                            $this->SaveFirstEntry(
                                                $this->sequence(0, [$value]),
                                                $break_Time_Req,
                                                $biometric_id,
                                                $delay,
                                                $scheduleEntry
                                            );
                                        }
                                    }
                                } else {

                                    /* Save new records */
                                    if ($value['status'] == 0 || $value['status'] == 255) {
                                        $scheduleEntry = null;
                                        if (isset($daySchedule['is_on_call']) && $daySchedule['is_on_call']) {
                                            // $scheduleEntry = date('Y-m-d H:i:s', strtotime($time_stamps_req['date_start'] . ' ' . $time_stamps_req['first_entry'] . '+' . $max_allowed_entry_for_oncall . ' minutes'));
                                            $scheduleEntry = $daySchedule['first_entry'];
                                        }
                                        $this->SaveFirstEntry(
                                            $this->sequence(0, [$value]),
                                            $break_Time_Req,
                                            $biometric_id,
                                            $delay,
                                            $scheduleEntry
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function SaveFirstEntry($sequence, $break_Time_Req, $biometric_id, $delay, $scheduleEntry)
    {
        try {

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
                            'dtr_date' => date('Y-m-d', strtotime($sc['date_time'])),
                            'second_in' => $sc['date_time'],
                            'is_biometric' => 1,
                        ]);
                    } else {
                        /* Adjust time in - if on call based on allowed entry */
                        /* FIRST IN ENTRY */
                        $this->inEntry($biometric_id, $alloted_hours, $this->calculatedEntry($scheduleEntry, $sc), $sched, $delay);
                    }
                } else {

                    /* Adjust time in - if on call based on allowed entry */
                    $this->inEntry($biometric_id, $alloted_hours, $this->calculatedEntry($scheduleEntry, $sc), $sched, $delay);
                }
            }
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function calculatedEntry($scheduleEntry, $sc)
    {
        $entry = $sc;

        if ($scheduleEntry) {

            $max_allowed_entry_for_oncall = env('MAX_ALLOWED_ENTRY_ONCALL'); // Max entry for on call
            $sc_Entry = date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($sc['date_time'])) . ' ' . $scheduleEntry . '+' . $max_allowed_entry_for_oncall . ' minutes'));

            if (strtotime($sc_Entry) > strtotime($sc['date_time'])) {
                /* Check if the entry is greater than 8am if not then return 8:00 exact. if its less than ,. return the orig time */
                if (strtotime($sc['date_time']) > strtotime($sc_Entry . ' - ' . $max_allowed_entry_for_oncall . ' minutes')) {
                    /* Greater */
                    $entry = [
                        "date_time" => date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($sc['date_time'])) . ' ' . $scheduleEntry))
                    ];
                } else {
                    /* Lesser */
                    $entry = [
                        "date_time" => $sc['date_time']
                    ];
                }
            } else {
                //Exceed the allowance time .. we are returning the difference of 8:30 and its entry;
                $fixentry = strtotime($sc['date_time']) - strtotime($sc_Entry);
                $calculated_entry = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($sc['date_time'])) . ' ' . $scheduleEntry))) + $fixentry;
                $entry = [
                    "date_time" => date('Y-m-d H:i:s', $calculated_entry)
                ];
            }
        }
        return $entry;
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
            $total_rendered = floor(($oah * 60) + $oam);
        }
        return $total_rendered >= 1 ? $total_rendered : 0;
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
        //return $this->toWordsMinutes(59.71);

        foreach ($sequence as $sc) {
            /* Entries */
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
                }


                $ot = round($overtime, 2);
                $ut = round($undertime, 2);


                $Schedule_Minutes  = $this->getTotalTimeRegistered(
                    $s1,
                    $s2,
                    $s3,
                    $s4
                );

                /* Overtime */
                $overTime_inWords = $this->toWordsMinutes($ot)['InWords'];
                $overTime_Minutes =  $this->toWordsMinutes($ot)['InMinutes'];

                /* Undertime  */
                $underTime_inWords = $this->toWordsMinutes($ut)['InWords'];
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
                $total_WH_words = $this->toWordsMinutes($tWH)['InWords'];
                $total_WH_minutes = $this->toWordsMinutes($tWH)['InMinutes'];
            } else {
                $tWH = floor($Schedule_Minutes - $underTime_Minutes);
                $total_WH_words = $this->toWordsMinutes($tWH)['InWords'];
                $total_WH_minutes = $this->toWordsMinutes($tWH)['InMinutes'];
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
        $attr = [
            'total_WH_words' => $total_WH_words,
            'required_WH' => $required_WH,
            'required_WH_Minutes' => $required_WH_Minutes,
            'total_WH_minutes' => $total_WH_minutes,
            'over_all_minutes_Rendered' => $over_all_minutes_Rendered,
            'Registered_minutes' => $Registered_minutes,
            'underTime_inWords' => $underTime_inWords,
            'underTime_Minutes' => $underTime_Minutes,
            'overTime_inWords' => $overTime_inWords,
            'overTime_Minutes' => $overTime_Minutes
        ];

        if (isset($f3_entry) && isset($f4_entry)) {
            $this->SaveToDTR($check_for_generate, $validate, $attr, $sc, 'second_out');
        } else {
            if ($f1_entry && $f2_entry && $f3_entry && !$f4_entry) {
                $this->SaveToDTR($check_for_generate, $validate, $attr, $sc, 'second_out');
            } else {
                $this->SaveToDTR($check_for_generate, $validate, $attr, $sc, 'first_out');
            }
        }
    }

    public function SaveToDTR($check_for_generate, $validate, $attr, $sc, $out)
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
                'overtime_minutes' => $attr['overTime_Minutes']
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
                'overtime_minutes' => $attr['overTime_Minutes']
            ]);
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
                'Status' => $Interval_Status,
                'alloted_dtr_interval' => env('ALLOTED_DTR_INTERVAL'),
                'minutes' => $minutes,
                'seconds' => $seconds,
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

    public function statusDescription($attendance_Log, $key)
    {
        $status_description = '';
        $Status_Entry = '';
        $active_entry = 'f1';
        $biometric_id = 0;
        $on_Active_Status = date('Y-m-d H:i:s', strtotime($attendance_Log['date_time'] . '-5 minutes'));
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
            0 => ['date_time' => $attendance_Log['date_time']],
        ];
        if (!$this->withinInterval($on_Active_Status, $dt)) {
            $Within_interval = "NO";
        } else {
            $Within_interval = "YES";
        }

        return [
            'description' => $status_description,
            'within_interval' => $Within_interval,
            'isEmployee' => $this->isEmployee($biometric_id)
        ];
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

    public function saveDTRLogs($check_Records, $validate, $device, $yesterdate)
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

            $check_DTR_Logs = DailyTimeRecordlogs::whereDate('dtr_date', $date)->where('biometric_id', $id)->where('validated', 1);

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

                $chec_kDTR = DailyTimeRecords::whereDate('first_in', $date)->where('biometric_id', $id);
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
                    'status_description' => $this->statusDescription($attendance_Log, $key)

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




    public function toWordsMinutes($totalMinutes)
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
    public function backUpTable($table)
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
