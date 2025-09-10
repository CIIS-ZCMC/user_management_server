<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DeviceLogs;
use App\Models\EmployeeProfile;
use App\Models\DailyTimeRecords;
use App\Methods\Helpers;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use Carbon\CarbonInterval;

class DeviceLogsController extends Controller
{

    protected $helper;


    public function __construct()
    {
        $this->helper = new Helpers();
    }

    public function ClearDeviceLogs($startDate)
    {
        if (!$startDate) {
            $startDate = date('Y-m-d');
        }
        /**
         * Deletion of Device logs stored from Database
         * Every 3 months from current Date
         */
        $startDateTime = Carbon::parse($startDate);
        $currentDateTime = Carbon::now();
        $diffInMonths = $currentDateTime->diffInMonths($startDateTime, false);
        if ($diffInMonths <= 6 && $diffInMonths >= 0) {
            $threeMonthsFromStartDate = $startDateTime->copy()->sub("9 days");

            $date = $threeMonthsFromStartDate->format('Y-m-d');
            DeviceLogs::where('dtr_date', "<=", $date)->delete();
            Log::channel("custom-dtr-log")->info('DEVICE LOGS CLEARED FROM  ' . $date . ' and LATE on.. ' . date('Y-m-d H:i'));
        } else {
            return null;
        }
    }

    public function CheckDTR($biometric_id)
    {
        $data = DB::table('device_logs')
            ->where('biometric_id', $biometric_id)
            ->whereNotIn('dtr_date', function ($query) use ($biometric_id) {
                $query->select('dtr_date')
                    ->from('daily_time_records')
                    ->where('biometric_id', $biometric_id);
            })
            ->orderBy('id', 'asc')
            ->get();

        // Convert the collection to an array of associative arrays
        return $data->map(function ($item) {
            return (array) $item;
        })->toArray();
    }


    public function Save($attendancelog, $device)
    {

        foreach ($attendancelog as $key => $row) {

            $validate = DeviceLogs::where('biometric_id', $row['biometric_id'])
                ->where('date_time', $row['date_time'])
                ->where('name', $row['name'])
                ->exists();

            if (!$validate) {
                $employee = EmployeeProfile::where('biometric_id', $row['biometric_id'])->first();

                //$employee->shifting;
                $bioEntry = [
                    'first_entry' => $row['date_time'],
                    'date_time' => $row['date_time']
                ];
                $Schedule = $this->helper->CurrentSchedule($row['biometric_id'], $bioEntry, false);
                $DaySchedule = $Schedule['daySchedule'];

                DeviceLogs::create([
                    'biometric_id' => $row['biometric_id'],
                    'name'  => $row['name'],
                    'dtr_date' => date("Y-m-d", strtotime($row['date_time'])),
                    'date_time' => $row['date_time'],
                    'status' => $row['status'],
                    'is_Shifting' => $employee->shifting ?? 0,
                    'schedule' => json_encode($DaySchedule),
                    'active' => $this->helper->isEmployee($row['biometric_id'])
                ]);
            }
        }
    }

    private function  ensureArray($data)
    {
        if (is_array($data)) {

            return $data;
        } elseif ($data instanceof \Illuminate\Support\Collection) {

            return $data->toArray();
        } elseif (is_object($data)) {

            return (array) $data;
        } else {

            return [];
        }
    }


    public function getEntryLineup($dvlog)
    {

        $data = $this->ensureArray($dvlog);
        usort($data, function ($a, $b) {
            return $a['id'] - $b['id'];
        });

        $Employee_Attendance = [];
        $processedLogs = [];

        $logsByDate = [];
        foreach ($data as $log) {
            $date = $log['dtr_date'];
            $biometric_id = $log['biometric_id'];
            $logsByDate[$date][$biometric_id][] = $log;
        }
        foreach ($logsByDate as $date => $logsByEmployee) {
            foreach ($logsByEmployee as $employee_ID => $employeeLogs) {
                $employee_Name = $employeeLogs[0]['name'];

                // Fetch DailyTimeRecords for this employee on this date
                $dtr = DailyTimeRecords::where('dtr_date', $date)
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
                $previousTimestamp = null;
                $lastStatus = null;
                foreach ($employeeLogs as $entry) {
                    $currentTimestamp = strtotime($entry['date_time']);

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
                        $interval = $previousTimestamp ? ($currentTimestamp - $previousTimestamp) / 60 : null;

                        if ($interval !== null && $interval <= 3) { // 3 minutes interval
                            $entry['entry_status'] = "LOGGED";
                        } else {
                            if ($lastStatus == "CHECK-IN") {
                                $entry['entry_status'] = "CHECK-OUT";
                                $lastStatus = "CHECK-OUT";
                            } elseif ($lastStatus == "CHECK-OUT") {
                                $entry['entry_status'] = "CHECK-IN";
                                $lastStatus = "CHECK-IN";
                            } else {
                                if ($mapdtr) {
                                    $entry['entry_status'] = $this->helper->getLatestEntry($mapdtr);
                                    $lastStatus = $this->helper->getLatestEntry($mapdtr);
                                } else {
                                    $entry['entry_status'] = "CHECK-IN";
                                    $lastStatus = "CHECK-IN";
                                }
                            }
                        }
                    }
                    $entry['timing'] = 0;
                    $entry['name'] = $employee_Name;
                    $entry['status_description'] = $this->helper->statusDescription($employee_ID, $entry['entry_status'], $entry['date_time']);
                    $Employee_Attendance[] = $entry;
                    // Update previousTimestamp only if entry_status is not "LOGGED"
                    if ($entry['entry_status'] !== "LOGGED") {
                        $previousTimestamp = $currentTimestamp;
                    }
                }
                $processedLogs[$employee_ID][$date] = true;
            }
        }
        return array_values(array_filter($Employee_Attendance, function ($row) {
            return $row['entry_status'] !== "LOGGED";
        }));
    }

    private function HasBreakTime($sched)
    {
        if (isset($sched->third_entry)) {
            return true;
        }
        return false;
    }
    private function CheckEntry($data, $count, $entity)
    {
        if (isset($data[$count]) && isset($data[$count][$entity])) {
            return true;
        }
        return false;
    }
    private function isPM($datetime)
    {
        if (date('A', strtotime($datetime)) === "PM") {
            return true;
        }
        return false;
    }

    private function withinSchedule($timeSched, $entryTime)
    {
        // Parse the time strings into Carbon objects
        $timeSched = Carbon::createFromFormat('H:i:s', $timeSched);
        $entryTime = Carbon::createFromFormat('H:i:s', $entryTime);

        // Define the time allowance (4 hours)
        $timeAllowance = CarbonInterval::hours(3);

        // Calculate the start and end of the allowed window
        $startWindow = $timeSched->copy()->sub($timeAllowance);
        $endWindow = $timeSched->copy()->add($timeAllowance);
        if ($startWindow->greaterThan($endWindow)) {
            return ($entryTime->greaterThanOrEqualTo($startWindow) || $entryTime->lessThanOrEqualTo($endWindow));
        } else {
            return ($entryTime->between($startWindow, $endWindow));
        }
    }

    public function ScheduleIsConflict($timeSched, $biometric_id)
    {

        $bioEntry = [
            'first_entry' => $timeSched,
            'date_time' => $timeSched
        ];
        $Schedule = $this->helper->CurrentSchedule($biometric_id, $bioEntry, false);
        $DaySchedule = $Schedule['daySchedule'];
        $empschedule[] = $DaySchedule;
        //COMPARE IF IT CONFLICTS THE SCHEDULE

        if (count($DaySchedule)) {
            $date = Carbon::parse(date('Y-m-d', strtotime($timeSched)) . " " . $DaySchedule['first_entry'])->format("Y-m-d H:i:s");
            if ($date == $timeSched) {
                return true;
            }
        }

        return false;
    }

    public function getAdvanceEntry($dateTime, $biometric_id, $timeSched)
    {
        $carbonDateTime = Carbon::parse($dateTime);
        $tomorrow = $carbonDateTime->addDay()->toDateString();




        //CHECK Devicelogs
        $dvl = DeviceLogs::where('dtr_date', $tomorrow)->where('biometric_id', $biometric_id)->get();



        if ($dvl->count()) {
            //Check if conflict


            if ($this->ScheduleIsConflict(date('Y-m-d H:i:s', strtotime($tomorrow . " " . date('H:i:s', strtotime($timeSched)))), $biometric_id)) {
                return;
            }

            //Check the schedule for today if conflict.
            //Removed the created DTR if theres one.

            //Delete DTR containing the data/
            $dtr = DailyTimeRecords::where('dtr_date', $dvl->first()->dtr_date)
                ->where('first_in', $dvl->first()->date_time);

            if ($dtr->get()->count()) {
                $dtr->delete();
            }
            foreach ($dvl as $dlogs) {
                if ($this->withinSchedule($timeSched, date('H:i:s', strtotime($dlogs->date_time)))) {
                    return $dlogs->date_time;
                }
            }
        }
    }

    public function UniqueEntry($dtr)
    {
        // Initialize the output array with the original format
        $uniqueDtr = $dtr;

        // Check and prioritize entries
        if (!empty($dtr['first_in'])) {
            $uniqueDtr['first_in'] = $dtr['first_in'];
        }

        if (!empty($dtr['first_out'])) {
            if ($dtr['first_in'] === $dtr['first_out']) {
                $uniqueDtr['first_out'] = null;
            } else {
                $uniqueDtr['first_out'] = $dtr['first_out'];
            }
        }

        if (!empty($dtr['second_in'])) {
            if ($dtr['first_in'] === $dtr['second_in'] || $dtr['first_out'] === $dtr['second_in']) {
                $uniqueDtr['second_in'] = null;
            } else {
                $uniqueDtr['second_in'] = $dtr['second_in'];
            }
        }

        if (!empty($dtr['second_out'])) {
            if (
                $dtr['first_in'] === $dtr['second_out'] ||
                $dtr['first_out'] === $dtr['second_out'] ||
                $dtr['second_in'] === $dtr['second_out']
            ) {
                $uniqueDtr['second_out'] = null;
            } else {
                $uniqueDtr['second_out'] = $dtr['second_out'];
            }
        }

        return $uniqueDtr;
    }

    public function adjustEntries($dtr)
    {
        // Check if the specific scenario exists
        if (is_null($dtr['first_in']) && !is_null($dtr['first_out']) && !is_null($dtr['second_in'])) {
            // Adjust the entries
            $dtr['first_in'] =  $dtr['second_in'];
            $dtr['first_out'] =  $dtr['first_out'];
            $dtr['second_in'] = null;
        }

        return $dtr;
    }

    public function ConsiderNightToDay($datetime)
    {
        $hour = date('H', strtotime($datetime));


        if ($hour >= 17 && $hour < 24) {
            return true;
        } else {
            return false;
        }
    }


    public function getEntries($datetime, $DaySchedule)
    {
        $firstentry = array_values(array_filter($datetime, function ($row) use ($DaySchedule) {
            if (!isset($DaySchedule['first_entry'])) {
                return null;
            }
            if ($this->withinSchedule($DaySchedule['first_entry'], date('H:i:s', strtotime($row['date_time'])))) {

                return $row['date_time'];
            }
        }));

        $secondentry = array_values(array_filter($datetime, function ($row) use ($DaySchedule) {
            if (!isset($DaySchedule['second_entry'])) {
                return null;
            }
            if ($this->withinSchedule($DaySchedule['second_entry'], date('H:i:s', strtotime($row['date_time'])))) {

                return $row['date_time'];
            }
        }));

        $thirdentry = array_values(array_filter($datetime, function ($row) use ($DaySchedule) {
            if (!isset($DaySchedule['third_entry'])) {
                return null;
            }
            if ($this->withinSchedule($DaySchedule['third_entry'], date('H:i:s', strtotime($row['date_time'])))) {

                return $row['date_time'];
            }
        }));


        $lastentry = array_values(array_filter($datetime, function ($row) use ($DaySchedule) {
            if (!isset($DaySchedule['last_entry'])) {
                return null;
            }
            if ($this->withinSchedule($DaySchedule['last_entry'], date('H:i:s', strtotime($row['date_time'])))) {

                return $row['date_time'];
            }
        }));

        return [
            'firstEntry' => $firstentry,
            'secondentry' => $secondentry,
            'thirdentry' => $thirdentry,
            'lastentry' => $lastentry
        ];
    }

    private function first_in($datetime, $is_shifter, $DaySchedule)
    {


        return $this->getEntries($datetime, $DaySchedule)['firstEntry'][0]['date_time'] ?? null;
    }

    private function first_out($datetime, $is_shifter, $DaySchedule)
    {

        if ($this->getEntries($datetime, $DaySchedule)['firstEntry']) {


            if ($this->getEntries($datetime, $DaySchedule)['firstEntry']) {

                if ($this->ConsiderNightToDay($this->getEntries($datetime, $DaySchedule)['firstEntry'][0]['date_time'])) {

                    if (count($this->getEntries($datetime, $DaySchedule)['secondentry'])  == 3) {
                        return $this->getEntries($datetime, $DaySchedule)['secondentry'][1]['date_time'];
                    } else if (count($this->getEntries($datetime, $DaySchedule)['secondentry']) == 2) {
                        return $this->getEntries($datetime, $DaySchedule)['secondentry'][0]['date_time'];
                    }
                } else {
                    //  return $this->getEntries($datetime,$DaySchedule)['secondentry'];

                    return $this->getEntries($datetime, $DaySchedule)['secondentry'][0]['date_time'] ?? null;
                }
            }


            if ($this->ConsiderNightToDay($this->getEntries($datetime, $DaySchedule)['firstEntry'][0]['date_time'])) {

                $biometric_id = $this->getEntries($datetime, $DaySchedule)['firstEntry'][0]['biometric_id'];

                return $this->getAdvanceEntry($this->getEntries($datetime, $DaySchedule)['firstEntry'][0]['date_time'], $biometric_id, $DaySchedule['second_entry']);
            }
            return $this->getEntries($datetime, $DaySchedule)['secondentry'][0]['date_time'] ?? null;
        }

        return null;
    }

    private function second_in($datetime, $hasbreak, $DaySchedule)
    {
        $entries = $this->getEntries($datetime, $DaySchedule);
        $thirdEntry = $entries['thirdentry'] ?? null;

        if ($thirdEntry === null) {
            return null;
        }

        if (isset($thirdEntry[1]['date_time'])) {
            return $thirdEntry[1]['date_time'];
        }

        if (isset($thirdEntry[0]['date_time'])) {
            return $thirdEntry[0]['date_time'];
        }

        return null;
    }


    private function second_out($datetime, $hasbreak, $DaySchedule)
    {
        $allowed = [
            'PM'
        ];

        if (!$this->getEntries($datetime, $DaySchedule)['firstEntry'] && !$this->getEntries($datetime, $DaySchedule)['thirdentry']) {
            return null;
        }

        return $this->getEntries($datetime, $DaySchedule)['lastentry'][0]['date_time'] ?? null;
    }

    public function removedDTRnoschedule($entry, $biometric_id)
    {
        $dtr = DailyTimeRecords::where('dtr_date', $entry)->where('biometric_id', $biometric_id);
        if ($dtr->exists()) {
            $dtr->delete();
        }
    }

    public function cleanEntry($dtr, $biometric_id, $dtrDate)
    {
        $count = 0;
        foreach ($dtr as $key => $value) {
            if ($key !== "is_generated") {
                if ($value === null) {
                    $count++;
                }
            }
        }

        DB::table('daily_time_records')
            ->whereRaw('DATE(dtr_date) != DATE(first_in)')
            ->delete();

        if ($count == 4) {
            DailyTimeRecords::where('biometric_id', $biometric_id)
                ->where('dtr_date', $dtrDate)
                ->delete();
            return false;
        }


        return true;
    }
    public function RegenerateEntry($deviceLogs, $biometric_id, $dtrdate, $Schedule)
    {
        try {
            $Entry = $deviceLogs;
            $DaySchedule = $Schedule['daySchedule'];
            $BreakTime = $Schedule['break_Time_Req'];
            $dtr = ['dtr_date' => $Entry[0]['dtr_date']];


            if (count($DaySchedule) == 0) {
                return $this->removedDTRnoschedule($Entry[0]['dtr_date'], $Entry[0]['biometric_id']);
            }


            if (count($BreakTime)) {
                //Add here if its lunch
                //  return $this->first_in($Entry  ?? null,false,$DaySchedule);
                $dtr = [
                    'first_in' => $this->first_in($Entry  ?? null, false, $DaySchedule),
                    'first_out' => $this->first_out($Entry ?? null, true, $DaySchedule),
                    'second_in' => $this->second_in($Entry ?? null, true, $DaySchedule),
                    'second_out' => $this->second_out($Entry ?? null, true, $DaySchedule),
                    'is_generated' => 1
                ];

                $dtr =  $this->UniqueEntry($dtr);
            } else {

                //Get Tomorrow entry matching the schedule;
                $dtr = [
                    'first_in' => $this->first_in($Entry ?? null, true, $DaySchedule),
                    'first_out' => $this->first_out($Entry ?? null, true, $DaySchedule),
                    'second_in' => null,
                    'second_out' => null,
                    'is_generated' => 1
                ];
            }

            $dtr = $this->adjustEntries($dtr);


            if ($this->cleanEntry($dtr, $biometric_id, $dtrdate)) {

                $validate =  DailyTimeRecords::where('biometric_id', $biometric_id)
                    ->where('dtr_date', $dtrdate)
                    ->where('is_time_adjustment', 0)
                    ->where('is_generated', 0);


                if ($validate->count()) {
                    $validate->update($dtr);
                } else {
                    $data = [
                        'dtr_date' => $dtrdate,
                        'biometric_id' => $biometric_id ?? null,
                    ];


                    $dtr =  array_merge($data, $dtr);

                    DailyTimeRecords::create($dtr);
                }
            }
        } catch (\Throwable $th) {
            // return $th;
            Log::channel("custom-dtr-log")->info('Error: ' . $th->getMessage());
        }
    }
}
