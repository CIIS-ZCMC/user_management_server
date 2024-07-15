<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\DeviceLogs;
use App\Models\EmployeeProfile;
use App\Models\DailyTimeRecords;
use App\Methods\Helpers;
use Illuminate\Support\Facades\DB;

class DeviceLogsController extends Controller
{

    protected $helper;


    public function __construct()
    {
        $this->helper = new Helpers();

    }

    public function CheckDTR($biometric_id) {
        $data = DB::table('device_logs')
                  ->where('biometric_id', $biometric_id)
                  ->whereNotIn('dtr_date', function($query) use ($biometric_id) {
                      $query->select('dtr_date')
                            ->from('daily_time_records')
                            ->where('biometric_id', $biometric_id);
                  })
                  ->orderBy('id', 'asc')
                  ->get();

        // Convert the collection to an array of associative arrays
        return $data->map(function($item) {
            return (array) $item;
        })->toArray();
    }


    public function Save($attendancelog, $device){

        foreach ($attendancelog as $key => $row) {

            $validate = DeviceLogs::where('biometric_id',$row['biometric_id'])
            ->where('date_time',$row['date_time'])
            ->where('name',$row['name'])
            ->exists();

            if(!$validate){
                $employee = EmployeeProfile::where('biometric_id', $row['biometric_id'])->first();

                //$employee->shifting;
                $bioEntry = [
                    'first_entry' =>$row['date_time'],
                    'date_time' => $row['date_time']
                ];
                $Schedule = $this->helper->CurrentSchedule($row['biometric_id'], $bioEntry, false);
                $DaySchedule = $Schedule['daySchedule'];

                DeviceLogs::create([
                    'biometric_id' =>$row['biometric_id'],
                    'name'  =>$row['name'],
                    'dtr_date'=>date("Y-m-d",strtotime($row['date_time'])),
                    'date_time'=>$row['date_time'],
                    'status'=>$row['status'],
                    'is_Shifting'=>$employee->shifting,
                    'schedule'=>json_encode($DaySchedule),
                    'active'=>$this->helper->isEmployee($row['biometric_id'])
                ]);
            }
        }
    }

    private function  ensureArray($data) {
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


    public function getEntryLineup($dvlog) {

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
                    $entry['timing'] =0;
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

    private function HasBreakTime($sched){
        if (isset($sched->third_entry)) {
            return true;
        }
        return false;
    }
    private function CheckEntry($data,$count,$entity){
        if (isset($data[$count]) && isset($data[$count][$entity])){
            return true;
        }
        return false;
    }
    private function isPM($datetime){
       if( date('A',strtotime($datetime)) === "PM"){
        return true;
       }
       return false;
    }
    private function first_in($datetime,$is_shifter){
        if($this->CheckEntry($datetime,0,'date_time')){
            $allowed = [
                'AM'
            ];

            if($is_shifter){

                //compare to sched
                return $datetime[0]['date_time'];
            }

            if(in_array(date('A',strtotime($datetime[0]['date_time'])),$allowed)){
               return $datetime[0]['date_time'];
            }
        }

        return null;
    }

    private function first_out($datetime,$is_shifter){

        if($this->CheckEntry($datetime,1,'date_time')){
            $allowed = [
                'AM',
                'PM'
            ];

            if($is_shifter){


                //compare to sched
                return $datetime[1]['date_time'];
            }

            if(in_array(date('A',strtotime($datetime[1]['date_time'])),$allowed)){
                //Check if first in is PM, then if PM dont display
                if($this->isPM($datetime[0]['date_time'])){
                    return;
                }

               return $datetime[1]['date_time'];
            }
           }

           return null;
    }

    private function second_in($datetime,$hasbreak){
        $allowed = [
            'PM'
        ];
        if ($this->CheckEntry($datetime,2,'date_time')){

            if(in_array(date('A',strtotime($datetime[2]['date_time'])),$allowed)){
                return $datetime[2]['date_time'];
             }
           }else {
          if($hasbreak){
            if($this->CheckEntry($datetime,0,'date_time')){
            if(in_array(date('A',strtotime($datetime[0]['date_time'])),$allowed)){
                return $datetime[0]['date_time'];
            }
        }
         }
           }

           return null;

    }

    private function second_out($datetime,$hasbreak){
        $allowed = [
            'PM'
        ];
        if($this->CheckEntry($datetime,3,'date_time')){

            if(in_array(date('A',strtotime($datetime[3]['date_time'])),$allowed)){
                return $datetime[3]['date_time'];
             }
           }else {
            if($hasbreak){
                if($this->CheckEntry($datetime,1,'date_time')){
                       if($this->isPM($datetime[0]['date_time'])){
                    if(in_array(date('A',strtotime($datetime[1]['date_time'])),$allowed)){
                        return $datetime[1]['date_time'];
                     }
                }
                }

            }
           }

           return null;
    }
    public function RegenerateEntry($deviceLogs,$biometric_id){
        $Entry = $this->getEntryLineup($deviceLogs);

        $bioEntry = $Entry[0]['date_time'] ?? $Entry[2]['date_time'];
        $Schedule = $this->helper->CurrentSchedule($biometric_id, $bioEntry, false);
        $DaySchedule = $Schedule['daySchedule'];
        $BreakTime = $Schedule['break_Time_Req'];
        $schedule = json_decode($Entry[0]['schedule']);
        $dtr = ['dtr_date'=>$Entry[0]['dtr_date']];

        if(!isset($schedule)){
            return;
        }

        if($BreakTime){
            //Add here if its lunch
          $dtr = [
            'first_in'=>$this->first_in($Entry  ?? null,false),
            'first_out'=>$this->first_out($Entry ?? null,false),
            'second_in'=>$this->second_in($Entry ?? null,true),
            'second_out'=>$this->second_out($Entry ?? null,true),
            'is_generated'=>1
          ];
        }else {
           $dtr = [
            'first_in'=>$this->first_in($Entry ?? null,true),
            'first_out'=>$this->first_out($Entry ?? null,true),
            'is_generated'=>1
          ];
        }
        DailyTimeRecords::where('biometric_id',$biometric_id)
        ->where('dtr_date',$Entry[0]['dtr_date'])
        ->where('is_generated',0)
        ->update($dtr);


    }
    public function GenerateEntry($deviceLogs,$dtrdate,$generateEntry){
        /**
         * TO DO..
         * disallow pulling of DTR if no schedule attached..
         *
         */
        $Entry = $this->getEntryLineup($deviceLogs);
        if($generateEntry){
            foreach ($Entry as $attendance_Log) {
                $dtrDates[] = $attendance_Log['dtr_date'];
            }
            $dtrDates = array_values(array_unique($dtrDates));
            foreach($dtrDates as $dates){
               $ent = array_values(array_filter($Entry,function($x) use($dates){
                    return $x['dtr_date'] === $dates;
                }));
                DailyTimeRecords::create([
                    'dtr_date'=>$dates,
                    'biometric_id'=>$ent[0]['biometric_id'] ?? null,
                    'first_in'=>$this->first_in($Entry  ?? null,false),
                    'first_out'=>$this->first_out($Entry ?? null,false),
                    'second_in'=>$this->second_in($Entry ?? null,true),
                    'second_out'=>$this->second_out($Entry ?? null,true),
                    'is_generated'=>1
                ]);
            }
            return;
        }
        DailyTimeRecords::create([
            'dtr_date'=>$dtrdate,
            'biometric_id'=>$Entry[0]['biometric_id'] ?? null,
            'first_in'=>$this->first_in($Entry  ?? null,false),
            'first_out'=>$this->first_out($Entry ?? null,false),
            'second_in'=>$this->second_in($Entry ?? null,true),
            'second_out'=>$this->second_out($Entry ?? null,true),
            'is_generated'=>1
        ]);
    }
}
