<?php

namespace App\Http\Controllers\DTR;

use App\Models\Biometrics;
use Illuminate\Http\Request;
use App\Models\DailyTimeRecords;
use App\Methods\Helpers;
use App\Helpers\Helpers as Helpersv2;
use App\Methods\BioControl;
use App\Models\DtrAnomalies;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Http\Controllers\DTR\BioMSController;
use App\Models\Holidaylist;
use App\Models\EmployeeProfile;
use App\Http\Controllers\Controller;
use App\Models\DailyTimeRecordLogs;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Section;
use App\Models\Schedule;
use App\Helpers\Helpers as Help;
use App\Methods\DTRPull;


class DTRcontroller extends Controller
{
    protected $helper;
    protected $device;
    protected $ip;
    protected $bioms;
    protected $devices;

    protected $emp;

    protected $DTR;

    private $CONTROLLER_NAME = "DTRcontroller";

    public function __construct()
    {
        $this->helper = new Helpers();
        $this->device = new BioControl();
        $this->bioms = new BioMSController();
        $this->DTR = new DTRPull();
        try {
            $content = $this->bioms->operatingDevice()->getContent();
            $this->devices = $content !== null ? json_decode($content, true)['data'] : [];
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, '__construct', $th->getMessage());
            Log::channel("custom-dtr-log-error")->error($th->getMessage());
        }
    }


    public function pullDTRuser(Request $request)
    {
        try {
            $user = $request->user;
            $today = date('Y-m-d');
            $biometric_id = $user->biometric_id;
            $selfRecord = DailyTimeRecords::where('biometric_id', $biometric_id)->where('dtr_date', $today)->first();
            $sched = $this->helper->getSchedule($biometric_id, null);

            if ($selfRecord) {
                if ($sched['third_entry'] == NULL && $sched['last_entry']  == NULL) {
                    return [
                        'dtr_date' => $selfRecord->dtr_date,
                        'first_in' => $selfRecord->first_in ? date('H:i', strtotime($selfRecord->first_in)) : ' --:--',
                        'first_out' => ' --:--',
                        'second_in' =>  ' --:--',
                        'second_out' => $selfRecord->first_out ? date('H:i', strtotime($selfRecord->first_out)) : ' --:--',
                    ];
                }

                return [
                    'dtr_date' => $selfRecord->dtr_date,
                    'first_in' => $selfRecord->first_in ? date('H:i', strtotime($selfRecord->first_in)) : ' --:--',
                    'first_out' => $selfRecord->first_out ? date('H:i', strtotime($selfRecord->first_out)) : ' --:--',
                    'second_in' => $selfRecord->second_in ? date('H:i', strtotime($selfRecord->second_in)) : ' --:--',
                    'second_out' => $selfRecord->second_out ? date('H:i', strtotime($selfRecord->second_out)) : ' --:--',
                ];
            } else {
                return [
                    'dtr_date' => $today,
                    'first_in' => ' --:--',
                    'first_out' => ' --:--',
                    'second_in' => ' --:--',
                    'second_out' => ' --:--',
                ];
            }
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'pullDTRuser', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 401);
        }
    }


    public function monthDayRecordsSelf(Request $request)
    {
        try {
            $user = $request->user;
            $biometric_id = $user->biometric_id;
            $selfRecord = DailyTimeRecords::where('biometric_id', $biometric_id)->get();
            foreach ($selfRecord as $dtr) {
                $records[date('F', strtotime($dtr->dtr_date))][] = date('Y', strtotime($dtr->dtr_date));
            }
            $result = [];
            foreach ($records as $month => $years) {
                $result[] = [
                    'month' => $month,
                    'year' => array_values(array_unique($years))
                ];
            }
            return $result;
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'monthDayRecordsSelf', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 401);
        }
    }

    public function fetchDTRFromDevice()
    {

        try {

            foreach ($this->devices as $device) {
                if ($tad = $this->device->bIO($device)) { //Checking if connected to device
                    $logs = $tad->get_att_log();
                    $all_user_info = $tad->get_all_user_info();
                    $attendance = simplexml_load_string($logs);
                    $user_Inf = simplexml_load_string($all_user_info);
                    $attendance_Logs =  $this->helper->getAttendance($attendance);
                    $Employee_Info  = $this->helper->getEmployee($user_Inf);
                    $Employee_Attendance = $this->helper->getEmployeeAttendance(
                        $attendance_Logs,
                        $Employee_Info
                    );




                    $date_and_timeD = simplexml_load_string($tad->get_date());
                    if ($this->helper->validatedDeviceDT($date_and_timeD)) { //Validating Time of server and time of device
                        $date_now = date('Y-m-d');

                        $check_Records = array_filter($Employee_Attendance, function ($attd) use ($date_now) {
                            return date('Y-m-d', strtotime($attd['date_time'])) == $date_now;
                        });




                        if (count($check_Records) >= 1) {
                            foreach ($check_Records as $bioEntry) {
                                $biometric_id = $bioEntry['biometric_id'];

                                $Schedule = $this->helper->CurrentSchedule($biometric_id, $bioEntry, false);
                                $DaySchedule = $Schedule['daySchedule'];
                                $BreakTime = $Schedule['break_Time_Req'];




                                if (count($DaySchedule) >= 1) {
                                    if (count($BreakTime) >= 1) {
                                        /**
                                         * With Schedule
                                         * 4 sets of sched
                                         */
                                        $this->DTR->HasBreaktimePull($DaySchedule, $BreakTime, $bioEntry, $biometric_id);
                                    } else {
                                        /**
                                         * With Schedule
                                         * 2 sets of sched
                                         */

                                        $this->DTR->NoBreaktimePull($DaySchedule, $bioEntry, $biometric_id);
                                    }
                                } else {

                                    /**
                                     * No Schedule Pulling
                                     */
                                    $this->DTR->NoSchedulePull($bioEntry, $biometric_id);
                                }
                            }
                            //$this->helper->saveDTRRecords($check_Records, false);
                            /* Save DTR Logs */
                            $this->helper->saveDTRLogs($check_Records, 1, $device, 0);
                            /* Clear device data */
                            $tad->delete_data(['value' => 3]);
                        } else {
                            //yesterday Time
                            // Save the past 24 hours records
                            $datenow = date('Y-m-d');
                            $late_Records = array_filter($Employee_Attendance, function ($attd) use ($datenow) {
                                return date('Y-m-d', strtotime($attd['date_time'])) < $datenow;
                            });



                            return;
                            // $this->helper->saveDTRRecords($late_Records, true);
                            // /* Save DTR Logs */
                            $this->helper->saveDTRLogs($late_Records, 1, $device, 1);
                            // /* Clear device data */
                            $tad->delete_data(['value' => 3]);
                        }
                    } else {
                        //Save anomaly entries
                        /**
                         * Here we saved all entries that the device date and time and server
                         * does not match..
                         *
                         */
                        foreach ($Employee_Attendance as $key => $value) {
                            DtrAnomalies::create([
                                'biometric_id' => $value['biometric_id'],
                                'name' => $value['name'],
                                'dtr_entry' => $value['date_time'],
                                'status' => $value['status'],
                                'status_desc' => $value['status_description']
                            ]);
                        }
                        $tad->delete_data(['value' => 3]);
                    }
                    // End of Validation of Time
                } // End Checking if Connected to Device
            }
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'fetchDTRFromDevice', $th->getMessage());
            return $th;
            // Log::channel("custom-dtr-log-error")->error($th->getMessage());
            // return response()->json(['message' => 'Unable to connect to device', 'Throw error' => $th->getMessage()]);
        }
    }

    public function formatDate($date)
    {
        if ($date === null) {
            return null;
        }
        //return date('Y-m-d H:i:s', $date);
        return $date;
    }


    public function fetchUserDTR(Request $request)
    {
        try {
            $bio = json_decode($request->biometric_id);
            $month_of = $request->monthof;
            $year_of = $request->yearof;
            $udata = [];

            if (count($bio) >= 1) {
                foreach ($bio as $biometric_id) {
                    if ($this->helper->isEmployee($biometric_id)) {
                        $dtr = DB::table('daily_time_records')
                            ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                            ->where(function ($query) use ($biometric_id, $month_of, $year_of) {
                                $query->where('biometric_id', $biometric_id)
                                    ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                                    ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                            })
                            ->orWhere(function ($query) use ($biometric_id, $month_of, $year_of) {
                                $query->where('biometric_id', $biometric_id)
                                    ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                                    ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                            })
                            ->get();
                        $mdtr = [];
                        foreach ($dtr as $key => $value) {
                            $mdtr[] =   [
                                'first_in' => $this->FormatDate($value->first_in),
                                'first_out' => $this->FormatDate($value->first_out),
                                'second_in' => $this->FormatDate($value->second_in),
                                'second_out' => $this->FormatDate($value->second_out),
                                'interval_req' => $value->interval_req,
                                'required_working_hours' => $value->required_working_hours,
                                'required_working_minutes' => $value->required_working_minutes,
                                'total_working_hours' => $value->total_working_hours,
                                'total_working_minutes' => $value->total_working_minutes,
                                'overtime' => $value->overtime,
                                'overtime_minutes' => $value->overtime_minutes,
                                'undertime' => $value->undertime,
                                'undertime_minutes' => $value->undertime_minutes,
                                'overall_minutes_rendered' => $value->overall_minutes_rendered,
                                'total_minutes_reg' => $value->total_minutes_reg,
                                'day' => $value->day,
                                'created_at' => $value->created_at,
                            ];
                        }

                        $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();
                        $emp_name = $employee->name();

                        $udata[] = [
                            'biometric_id' => $biometric_id,
                            'employee_id' => $employee->id, // replace with employee details
                            'employeeName' =>  $emp_name,
                            'sex' => $employee->GetPersonalInfo()->sex,
                            'dateofbirth' => $employee->GetPersonalInfo()->date_of_birth,
                            'date_hired' => $employee->date_hired,
                            'dtr_records' => $mdtr
                        ];
                    }
                }
            } else {
                $all_biometric = Biometrics::all();
                foreach ($all_biometric as $bio) {
                    $biometric_id = $bio->biometric_id;
                    if ($this->helper->isEmployee($biometric_id)) {
                        $dtr = DB::table('daily_time_records')
                            ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                            ->where(function ($query) use ($biometric_id, $month_of, $year_of) {
                                $query->where('biometric_id', $biometric_id)
                                    ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                                    ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                            })
                            ->orWhere(function ($query) use ($biometric_id, $month_of, $year_of) {
                                $query->where('biometric_id', $biometric_id)
                                    ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                                    ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                            })
                            ->get();
                        $mdtr = [];
                        foreach ($dtr as $key => $value) {
                            $mdtr[] =   [
                                'first_in' => $this->FormatDate($value->first_in),
                                'first_out' => $this->FormatDate($value->first_out),
                                'second_in' => $this->FormatDate($value->second_in),
                                'second_out' => $this->FormatDate($value->second_out),
                                'interval_req' => $value->interval_req,
                                'required_working_hours' => $value->required_working_hours,
                                'required_working_minutes' => $value->required_working_minutes,
                                'total_working_hours' => $value->total_working_hours,
                                'total_working_minutes' => $value->total_working_minutes,
                                'overtime' => $value->overtime,
                                'overtime_minutes' => $value->overtime_minutes,
                                'undertime' => $value->undertime,
                                'undertime_minutes' => $value->undertime_minutes,
                                'overall_minutes_rendered' => $value->overall_minutes_rendered,
                                'total_minutes_reg' => $value->total_minutes_reg,
                                'day' => $value->day,
                                'created_at' => $value->created_at,
                            ];
                        }

                        $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();
                        $emp_name = $employee->name();

                        $udata[] = [
                            'biometric_id' => $biometric_id,
                            'employee_id' => $employee->id, // replace with employee details
                            'employeeName' =>  $emp_name,
                            'sex' => $employee->GetPersonalInfo()->sex,
                            'dateofbirth' => $employee->GetPersonalInfo()->date_of_birth,
                            'date_hired' => $employee->date_hired,
                            'dtr_records' => $mdtr
                        ];
                    }
                }
            }



            return response()->json(['data' => $udata]);
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'fetchUserDTR', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    private function arrivalDeparture($time_stamps_req, $year_of, $month_of)
    {
        if (count($time_stamps_req) >= 1) {
            if (!$time_stamps_req['first_entry'] && !$time_stamps_req['second_entry'] && !$time_stamps_req['third_entry'] && !$time_stamps_req['last_entry']) {
                return "NO SCHEDULE";
            }

            $f1 = strtoupper(date('h:ia', strtotime($year_of . '-' . $month_of . '-1 ' . $time_stamps_req['first_entry'])));
            $f2 = strtoupper(date('h:ia', strtotime($year_of . '-' . $month_of . '-1 ' . $time_stamps_req['second_entry'])));
            $f3 = strtoupper(date('h:ia', strtotime($year_of . '-' . $month_of . '-1 ' . $time_stamps_req['third_entry'])));
            $f4 = strtoupper(date('h:ia', strtotime($year_of . '-' . $month_of . '-1 ' . $time_stamps_req['last_entry'])));



            if ($time_stamps_req['first_entry'] && $time_stamps_req['second_entry'] && !$time_stamps_req['third_entry'] && !$time_stamps_req['last_entry']) {
                return $f1 . '-' . $f2;
            } else {
                return $f1 . '-' . $f2 . '/' . $f3 . '-' . $f4;
            }
        }
    }

    private function isHalfEntrySchedule($time_stamps_req)
    {
        if (!$time_stamps_req['first_entry'] && !$time_stamps_req['second_entry'] && !$time_stamps_req['third_entry'] && !$time_stamps_req['last_entry']) {
            return false;
        } elseif ($time_stamps_req['first_entry'] && $time_stamps_req['second_entry'] && !$time_stamps_req['third_entry'] && !$time_stamps_req['last_entry']) {
            return true;
        }



        return false;
    }

    private function withinScheduleRange($entry, $schedule)
    {
        $start_Time_stamp = $this->helper->forceToStrtimeFormat($entry);
        $end_Time_stamp = $this->helper->forceToStrtimeFormat($schedule);
        $seconds_Difference = $end_Time_stamp - $start_Time_stamp;
        $hours = $seconds_Difference / 3600;
        $accepted_Entry_Range = 3; // 3 Hours is considered entry

        if ($hours <= $accepted_Entry_Range) {
            return true;
        }
        return false;
    }

    /* ----------------------------------------------------------------GENERATION OF DAILY TIME RECORDS----------------------------------------------------------------------------------------------------------------------------- */

    /***
     * Table Requirements
     * time-shifts | schedules | biometrics | employee_profiles | employee_profile_schedule
     * Each of these tables should contain data linked to the biometric_id below in order to generate records

     */
    public function generateDTR(Request $request)
    {
        try {
            $biometric_id =  $request->biometric_id;
            $month_of = $request->monthof;
            $year_of = $request->yearof;
            $view = $request->view;
            $FrontDisplay = $request->frontview;

            /*
            Multiple IDS for Multiple PDF generation
            */
            $id = json_decode($biometric_id);

            if (count($id) == 0) {
                return response()->json([
                    'message' => 'Failed to Generate: No Employee data found'
                ]);
            }

            if (count($id) >= 2) {
                return $this->GenerateMultiple($id, $month_of, $year_of, $view);
            }

            $emp_name = '';
            $biometric_id = $id[0];

            if ($this->helper->isEmployee($id[0])) {
                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();
                $emp_name = $employee->name();
            } else {

                if ($FrontDisplay) {
                    return view("dtr.notfound");
                }

                return response()->json([
                    'message' => 'Failed to Generate: No biometric data found'
                ]);
            }
            $dtr = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where(function ($query) use ($biometric_id, $month_of, $year_of) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                        ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                })
                ->orWhere(function ($query) use ($biometric_id, $month_of, $year_of) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                        ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                })
                ->get();


            $arrival_Departure = [];
            $time_stamps_req = [
                'total_hours' => 8
            ];



            foreach ($dtr as $val) {
                $bioEntry = [
                    'first_entry' => $val->first_in ?? $val->second_in,
                    'date_time' => $val->first_in ?? $val->second_in
                ];

                $Schedule = $this->helper->CurrentSchedule($biometric_id, $bioEntry, false);
                $DaySchedule = $Schedule['daySchedule'];
                $BreakTime = $Schedule['break_Time_Req'];


                $arrival_Departure[] = $this->arrivalDeparture($DaySchedule, $year_of, $month_of);

                if (count($DaySchedule) >= 1) {
                    $validate = [
                        (object)[
                            'id' => $val->id,
                            'first_in' => $val->first_in,
                            'first_out' => $val->first_out,
                            'second_in' => $val->second_in,
                            'second_out' => $val->second_out
                        ],
                    ];

                    $this->helper->saveTotalWorkingHours(
                        $validate,
                        $val,
                        $val,
                        $DaySchedule,
                        true
                    );
                }
            }






            $ohf = isset($time_stamps_req) ? $time_stamps_req['total_hours'] . ' HOURS' : null;

            $emp_Details = [
                'OHF' => $ohf,
                'Arrival_Departure' => $arrival_Departure[0] ?? 'NO SCHEDULE',
                'Employee_Name' => $emp_name,
                'DTRFile_Name' => $emp_name,
                'biometric_ID' => $biometric_id
            ];

            return $this->PrintDtr($month_of, $year_of, $biometric_id, $emp_Details, $view, $FrontDisplay);
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'generateDTR', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }


    /*
    *    This is either view or print as PDF
    *
    */

    public function printDtr($month_of, $year_of, $biometric_id, $emp_Details, $view, $FrontDisplay)
    {
        try {
            $dtr = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where(function ($query) use ($biometric_id, $month_of, $year_of) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                        ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                })
                ->orWhere(function ($query) use ($biometric_id, $month_of, $year_of) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                        ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                })
                ->get();

            $dt_records = [];
            $No_schedule_DTR = [];
            $is_Half_Schedule = false;
            $day = [];
            $entry = '';

            $sctest = [];
            foreach ($dtr as $val) {
                /* Validating DTR with its Matching Schedules */
                /*
                *   if no matching schedule then
                *   it will not display the daily time record
                */
                if (isset($val->first_in) && $val->first_in !== NULL) {
                    $entry = $val->first_in;
                } else {
                    if (isset($val->second_in) && $val->second_in !== NULL) {
                        $entry = $val->second_in;
                    }
                }
                $day[] = $val->dtr_date;

                $yearSched = date('Y', strtotime($entry));
                $monthSched = date('m', strtotime($entry));
                $schedule = $this->helper->getSchedule($val->biometric_id, "all-{$yearSched}-{$monthSched}")['schedule'];
                //GET THE SCHEDULE

                if ($schedule) {
                    $value = [
                        'date_time' => date('Y-m-d H:i:s', strtotime($entry)),
                        'first_in' => $entry
                    ];
                    $daySched = $this->helper->CurrentSchedule($val->biometric_id,  $value, false)['daySchedule'];
                    $is_Half_Schedule = count($this->helper->CurrentSchedule($val->biometric_id,  $value, false)['break_Time_Req']);
                }


                // $sctest[] = $daySched;


                if (isset($daySched['scheduleDate'])) {
                    // $sctest[] = $daySched['scheduleDate'];
                    $sdate =  $daySched['scheduleDate'];



                    if (date('Y-m-d', strtotime($entry)) ==   $sdate) {
                        //   echo $entry;
                        $date_entry = date('Y-m-d H:i', strtotime($entry));
                        $schedule_fEntry = date('Y-m-d H:i', strtotime(date('Y-m-d', strtotime($date_entry)) . ' ' . $sdate));
                        //return $this->WithinScheduleRange($dateentry, $schedulefEntry);

                        if ($this->withinScheduleRange($date_entry, $schedule_fEntry)) {
                            $dt_records[] = [
                                'biometric_ID' => $val->biometric_id,
                                'first_in' => $val->first_in,
                                'first_out' => $val->first_out,
                                'second_in' => $val->second_in,
                                'second_out' => $val->second_out,
                                'undertime_minutes' => $val->undertime_minutes,
                                'created' => $val->dtr_date
                            ];
                        }
                    }
                } else {
                    $No_schedule_DTR[] = [
                        'biometric_ID' => $val->biometric_id,
                        'first_in' => $val->first_in,
                        'first_out' => $val->first_out,
                        'second_in' => $val->second_in,
                        'second_out' => $val->second_out,
                        'undertime_minutes' => $val->undertime_minutes,
                        'created' => $val->dtr_date
                    ];
                    //  echo $val->first_in;
                }
            }

            // return $sctest;

            $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
            $second_in = [];
            $second_out = [];
            $first_in = array_map(function ($res) {
                return [
                    'dtr_date' => $res['created'],
                    'first_in' => $res['first_in'],
                    'biometric_ID' => $res['biometric_ID']

                ];
            }, $dt_records);

            $first_out = array_map(function ($res) {
                return [
                    'dtr_date' => $res['created'],
                    'first_out' => $res['first_out'],
                    'biometric_ID' => $res['biometric_ID']
                ];
            }, $dt_records);

            $second_in = array_map(function ($res) {
                return [
                    'dtr_date' => $res['created'],
                    'second_in' => $res['second_in'],
                    'biometric_ID' => $res['biometric_ID']
                ];
            }, $dt_records);




            $second_out = array_map(function ($res) {
                return  [
                    'dtr_date' => $res['created'],
                    'second_out' => $res['second_out'],
                    'biometric_ID' => $res['biometric_ID']
                ];
            }, $dt_records);

            $ut =  array_map(function ($res) {
                return [
                    'created' => $res['created'],
                    'undertime' => $res['undertime_minutes'],
                    'biometric_ID' => $res['biometric_ID']
                ];
            }, $dt_records);

            $holidays = DB::table('holidays')->get();

            $employeeSched = DB::table('schedules')
                ->select('date as schedule')
                ->selectRaw('(CASE WHEN time_shift_id THEN (SELECT first_in FROM `time_shifts` WHERE id = time_shift_id) ELSE NULL END) as first_in')
                ->selectRaw('(CASE WHEN time_shift_id THEN (SELECT first_out FROM `time_shifts` WHERE id = time_shift_id) ELSE NULL END) as first_out')
                ->selectRaw('(CASE WHEN time_shift_id THEN (SELECT second_in FROM `time_shifts` WHERE id = time_shift_id) ELSE NULL END) as second_in')
                ->selectRaw('(CASE WHEN time_shift_id THEN (SELECT second_out FROM `time_shifts` WHERE id = time_shift_id) ELSE NULL END) as second_out')
                ->selectRaw('(CASE WHEN date = (SELECT dtr_date FROM `daily_time_records` WHERE dtr_date = schedules.date AND biometric_id = ' . $biometric_id . ' LIMIT 1) THEN 1 ELSE 0 END) as attendance_status')
                ->whereIn('id', function ($query) use ($biometric_id) {
                    $query->select('schedule_id')
                        ->from('employee_profile_schedule')
                        ->whereIn('employee_profile_id', function ($innerQuery) use ($biometric_id) {
                            $innerQuery->select('id')
                                ->from('employee_profiles')
                                ->where('biometric_id', $biometric_id);
                        });
                })
                ->get();

            $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();

            //Leave Applications
            $leaveapp  = $employee->leaveApplications->filter(function ($row) {
                return $row['status'] == "approved";
            });

            $leavedata = [];
            foreach ($leaveapp as $rows) {
                $leavedata[] = [
                    'country' => $rows['country'],
                    'city' => $rows['city'],
                    'from' => $rows['date_from'],
                    'to' => $rows['date_to'],
                    'without_pay' => $rows['without_pay'],
                    'dates_covered' => $this->helper->getDateIntervals($rows['date_from'], $rows['date_to'])
                ];
            }

            //Official business
            $officialBusiness = array_values($employee->officialBusinessApplications->filter(function ($row) {
                return $row['status'] == "approved";
            })->toarray());
            $obData = [];
            foreach ($officialBusiness as $rows) {
                $obData[] = [
                    'purpose' => $rows['purpose'],
                    'time_from' => $rows['time_from'],
                    'time_to' => $rows['time_to'],
                    'date_from' => $rows['date_from'],
                    'date_to' => $rows['date_to'],
                    'dates_covered' => $this->helper->getDateIntervals($rows['date_from'], $rows['date_to']),
                ];
            }

            //Official Time
            $officialTime = $employee->officialTimeApplications->filter(function ($row) {
                return $row['status'] == "approved";
            });
            $otData = [];
            foreach ($officialTime as $rows) {
                $otData[] = [
                    'date_from' => $rows['date_from'],
                    'date_to' => $rows['date_to'],
                    'purpose' => $rows['purpose'],
                    'dates_covered' => $this->helper->getDateIntervals($rows['date_from'], $rows['date_to'])
                ];
            }




            $schedules = $this->helper->getSchedule($biometric_id, "all-{$year_of}-{$month_of}");

            if ($FrontDisplay) {
                return view('dtr.PrintDTRPDF',  [
                    'daysInMonth' => $days_In_Month,
                    'year' => $year_of,
                    'month' => $month_of,
                    'firstin' => $first_in,
                    'firstout' => $first_out,
                    'secondin' => $second_in,
                    'secondout' => $second_out,
                    'undertime' => $ut,
                    'OHF' => $emp_Details['OHF'],
                    'Arrival_Departure' => $schedules['arrival_departure'],
                    'Employee_Name' => $emp_Details['Employee_Name'],
                    'dtrRecords' => $dt_records,
                    'holidays' => $holidays,
                    'print_view' => true,
                    'halfsched' => $is_Half_Schedule,
                    'biometric_ID' => $biometric_id,
                    'schedule' => $employeeSched,
                    'leaveapp' => $leavedata,
                    'obApp' => $obData,
                    'otApp' => $otData

                ]);
            }
            $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();
            $approvingDTR = Help::getApprovingDTR($employee->assignedArea, $employee);
            $approver = isset($approvingDTR['name']) ? $approvingDTR['name'] : null;

            if ($view) {
                return view('generate_dtr.PrintDTRPDF',  [
                    'daysInMonth' => $days_In_Month,
                    'year' => $year_of,
                    'month' => $month_of,
                    'firstin' => $first_in,
                    'firstout' => $first_out,
                    'secondin' => $second_in,
                    'secondout' => $second_out,
                    'undertime' => $ut,
                    'OHF' => $emp_Details['OHF'],
                    'Arrival_Departure' => $schedules['arrival_departure'],
                    'Employee_Name' => $emp_Details['Employee_Name'],
                    'dtrRecords' => $dt_records,
                    'holidays' => $holidays,
                    'print_view' => true,
                    'halfsched' => $is_Half_Schedule,
                    'biometric_ID' => $biometric_id,
                    'schedule' => $employeeSched,
                    'Incharge' => $approver,
                    'leaveapp' => $leavedata,
                    'obApp' => $obData,
                    'otApp' => $otData
                ]);
            } else {
                $options = new Options();
                $options->set('isPhpEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf($options);
                $dompdf->getOptions()->setChroot([base_path() . '\public\storage']);
                $dompdf->loadHtml(view('generate_dtr.PrintDTRPDF',  [
                    'daysInMonth' => $days_In_Month,
                    'year' => $year_of,
                    'month' => $month_of,
                    'firstin' => $first_in,
                    'firstout' => $first_out,
                    'secondin' => $second_in,
                    'secondout' => $second_out,
                    'undertime' => $ut,
                    'OHF' => $emp_Details['OHF'],
                    'Arrival_Departure' => $schedules['arrival_departure'],
                    'Employee_Name' => $emp_Details['Employee_Name'],
                    'dtrRecords' => $dt_records,
                    'holidays' => $holidays,
                    'print_view' => false,
                    'halfsched' => $is_Half_Schedule,
                    'biometric_ID' => $biometric_id,
                    'schedule' => $employeeSched,
                    'Incharge' => $approver,
                    'leaveapp' => $leavedata,
                    'obApp' => $obData,
                    'otApp' => $otData
                ]));

                $dompdf->setPaper('Letter', 'portrait');
                $dompdf->render();
                $monthName = date('F', strtotime($year_of . '-' . sprintf('%02d', $month_of) . '-1'));
                $filename = $emp_Details['DTRFile_Name'] . ' (DTR ' . $monthName . '-' . $year_of . ').pdf';

                /* Downloads as PDF */
                $dompdf->stream($filename);
            }
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'printDtr', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function GenerateMultiple($id, $month_of, $year_of, $view)
    {
        $data = [];
        $emp_Details = [];
        foreach ($id as $key => $biometric_id) {

            if ($this->helper->isEmployee($biometric_id)) {
                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();
                $emp_name = $employee->name();



                try {
                    $dtr = DB::table('daily_time_records')
                        ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                        ->where(function ($query) use ($biometric_id, $month_of, $year_of) {
                            $query->where('biometric_id', $biometric_id)
                                ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                                ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                        })
                        ->orWhere(function ($query) use ($biometric_id, $month_of, $year_of) {
                            $query->where('biometric_id', $biometric_id)
                                ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                                ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                        })
                        ->get();



                    $arrival_Departure = [];
                    $time_stamps_req = [
                        'total_hours' => 8
                    ];


                    foreach ($dtr as $val) {
                        /* Validating DTR with its Matching Schedules */
                        /*
                        *   if no matching schedule then
                        *   it will not display the daily time record
                        */
                        $bioEntry = [
                            'first_entry' => $val->first_in,
                            'date_time' => $val->first_in
                        ];
                        $Schedule = $this->helper->CurrentSchedule($biometric_id, $bioEntry, false);
                        $DaySchedule = $Schedule['daySchedule'];
                        $BreakTime = $Schedule['break_Time_Req'];

                        $arrival_Departure[] = $this->arrivalDeparture($DaySchedule, $year_of, $month_of);

                        if (count($time_stamps_req) >= 1) {
                            $validate = [
                                (object)[
                                    'id' => $val->id,
                                    'first_in' => $val->first_in,
                                    'first_out' => $val->first_out,
                                    'second_in' => $val->second_in,
                                    'second_out' => $val->second_out
                                ],
                            ];
                            $this->helper->saveTotalWorkingHours(
                                $validate,
                                $val,
                                $val,
                                $time_stamps_req,
                                true
                            );
                        }
                    }
                } catch (\Throwable $th) {
                    return response()->json(['message' =>  $th->getMessage()]);
                }

                $ohf[] = isset($DaySchedule) ? $DaySchedule['total_hours'] . ' HOURS' : null;

                $emp_Details[] = [
                    'OHF' => $ohf,
                    'Arrival_Departure' => $arrival_Departure[0] ?? 'NO SCHEDULE',
                    'Employee_Name' => $emp_name,
                    'DTRFile_Name' => $emp_name,
                    'biometric_ID' => $biometric_id
                ];
            }
        }



        return $this->MultiplePrintOrView($id, $month_of, $year_of, $view, $emp_Details);
    }


    public function MultiplePrintOrView($id, $month_of, $year_of, $view, $emp_Details)
    {

        $data = [];
        $dt_records = [];

        foreach ($id as $key => $biometric_id) {

            if ($this->helper->isEmployee($biometric_id)) {
                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();
                $emp_name = $employee->name();

                $dtr = DB::table('daily_time_records')
                    ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                    ->where(function ($query) use ($biometric_id, $month_of, $year_of) {
                        $query->where('biometric_id', $biometric_id)
                            ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                            ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                    })
                    ->orWhere(function ($query) use ($biometric_id, $month_of, $year_of) {
                        $query->where('biometric_id', $biometric_id)
                            ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                            ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                    })
                    ->get();


                $No_schedule_DTR = [];
                $is_Half_Schedule = false;
                $day = [];
                $entry = '';

                foreach ($dtr as $val) {
                    /* Validating DTR with its Matching Schedules */
                    /*
                *   if no matching schedule then
                *   it will not display the daily time record
                */
                    if (isset($val->first_in) && $val->first_in !== NULL) {
                        $entry = $val->first_in;
                    } else {
                        if (isset($val->second_in) && $val->second_in !== NULL) {
                            $entry = $val->second_in;
                        }
                    }

                    $yearSched = date('Y', strtotime($entry));
                    $monthSched = date('m', strtotime($entry));
                    $schedule = $this->helper->getSchedule($val->biometric_id, "all-{$yearSched}-{$monthSched}")['schedule'];
                    //GET THE SCHEDULE

                    if ($schedule) {
                        $value = [
                            'date_time' => date('Y-m-d H:i:s', strtotime($entry)),
                            'first_in' => $entry
                        ];
                        $daySched = $this->helper->CurrentSchedule($val->biometric_id,  $value, false)['daySchedule'];
                        $is_Half_Schedule = count($this->helper->CurrentSchedule($val->biometric_id,  $value, false)['break_Time_Req']);
                    }




                    if (isset($daySched['scheduleDate'])) {

                        $sdate =  $daySched['scheduleDate'];



                        if (date('Y-m-d', strtotime($entry)) ==   $sdate) {
                            //   echo $entry;
                            $date_entry = date('Y-m-d H:i', strtotime($entry));
                            $schedule_fEntry = date('Y-m-d H:i', strtotime(date('Y-m-d', strtotime($date_entry)) . ' ' . $sdate));
                            //return $this->WithinScheduleRange($dateentry, $schedulefEntry);

                            if ($this->withinScheduleRange($date_entry, $schedule_fEntry)) {
                                $dt_records[] = [
                                    'biometric_ID' => $val->biometric_id,
                                    'first_in' => $val->first_in,
                                    'first_out' => $val->first_out,
                                    'second_in' => $val->second_in,
                                    'second_out' => $val->second_out,
                                    'undertime_minutes' => $val->undertime_minutes,
                                    'created' => $val->dtr_date
                                ];
                            }
                        }
                    } else {
                        $No_schedule_DTR[] = [
                            'biometric_ID' => $val->biometric_id,
                            'first_in' => $val->first_in,
                            'first_out' => $val->first_out,
                            'second_in' => $val->second_in,
                            'second_out' => $val->second_out,
                            'undertime_minutes' => $val->undertime_minutes,
                            'created' => $val->dtr_date
                        ];
                        //  echo $val->first_in;
                    }
                }

                $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);

                $first_in = array_map(function ($res) {
                    return [
                        'dtr_date' => $res['created'],
                        'first_in' => $res['first_in'],
                        'biometric_ID' => $res['biometric_ID']

                    ];
                }, $dt_records);


                $first_out = array_map(function ($res) {
                    return [
                        'dtr_date' => $res['created'],
                        'first_out' => $res['first_out'],
                        'biometric_ID' => $res['biometric_ID']
                    ];
                }, $dt_records);

                $second_in = array_map(function ($res) {
                    return [
                        'dtr_date' => $res['created'],
                        'second_in' => $res['second_in'],
                        'biometric_ID' => $res['biometric_ID']
                    ];
                }, $dt_records);




                $second_out = array_map(function ($res) {
                    return  [
                        'dtr_date' => $res['created'],
                        'second_out' => $res['second_out'],
                        'biometric_ID' => $res['biometric_ID']
                    ];
                }, $dt_records);

                $ut =  array_map(function ($res) {
                    return [
                        'created' => $res['created'],
                        'undertime' => $res['undertime_minutes'],
                        'biometric_ID' => $res['biometric_ID']
                    ];
                }, $dt_records);

                $holidays = DB::table('holidays')->get();

                $employeeSched = DB::table('schedules')
                    ->select('date as schedule')
                    ->selectRaw('(CASE WHEN time_shift_id THEN (SELECT first_in FROM `time_shifts` WHERE id = time_shift_id) ELSE NULL END) as first_in')
                    ->selectRaw('(CASE WHEN time_shift_id THEN (SELECT first_out FROM `time_shifts` WHERE id = time_shift_id) ELSE NULL END) as first_out')
                    ->selectRaw('(CASE WHEN time_shift_id THEN (SELECT second_in FROM `time_shifts` WHERE id = time_shift_id) ELSE NULL END) as second_in')
                    ->selectRaw('(CASE WHEN time_shift_id THEN (SELECT second_out FROM `time_shifts` WHERE id = time_shift_id) ELSE NULL END) as second_out')
                    ->selectRaw('(CASE WHEN date = (SELECT dtr_date FROM `daily_time_records` WHERE dtr_date = schedules.date AND biometric_id = ' . $biometric_id . ' LIMIT 1) THEN 1 ELSE 0 END) as attendance_status')
                    ->whereIn('id', function ($query) use ($biometric_id) {
                        $query->select('schedule_id')
                            ->from('employee_profile_schedule')
                            ->whereIn('employee_profile_id', function ($innerQuery) use ($biometric_id) {
                                $innerQuery->select('id')
                                    ->from('employee_profiles')
                                    ->where('biometric_id', $biometric_id);
                            });
                    })
                    ->get();



                $schedules = $this->helper->getSchedule($biometric_id, "all-{$year_of}-{$month_of}");


                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();
                $approvingDTR = Help::getApprovingDTR($employee->assignedArea, $employee);
                $approver = isset($approvingDTR['name']) ? $approvingDTR['name'] : null;
                $data[] = [
                    'daysInMonth' => $days_In_Month,
                    'year' => $year_of,
                    'month' => $month_of,
                    'firstin' => $first_in,
                    'firstout' => $first_out,
                    'secondin' => $second_in,
                    'secondout' => $second_out,
                    'undertime' => $ut,
                    'dtrRecords' => $dt_records,
                    'holidays' => $holidays,
                    'print_view' => true,
                    'halfsched' => $is_Half_Schedule,
                    'biometric_ID' => $biometric_id,
                    'schedule' => $employeeSched,
                    'Incharge' => $approver,
                    'emp_Details' => $emp_Details

                ];
            }
        }





        //$view

        return view('generate_dtr.PrintDTRPDF',  [
            'data' => $data
        ]);
    }
    /* ----------------------------------------------------------------END OF GENERATION OF DAILY TIME RECORDS----------------------------------------------------------------------------------------------------------------------------- */


    public function getHolidays()
    {
        try {
            return response()->json(['data' => Holidaylist::all()]);
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'getHolidays', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function setHolidays(Request $request)
    {
        try {
            $description = $request->description;
            $month = $request->month;
            $day = $request->day;
            $is_special = $request->isspecial;
            $effective_Date = $request->effectiveDate;

            Holidaylist::create([
                'description' => $description,
                'month_day' => $month . '-' . $day,
                'isspecial' => $is_special,
                'effectiveDate' => $effective_Date,
            ]);
            return response()->json(['message' =>  "Holiday Set Successfully!"]);
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'setHolidays', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function modifyHolidays(Request $request)
    {
        try {
            $holiday_id = $request->holiday_id;
            $description = $request->description;
            $month = $request->month;
            $day = $request->day;
            $is_special = $request->isspecial;
            $effective_Date = $request->effectiveDate;

            Holidaylist::where('id', $holiday_id)->update([
                'description' => $description,
                'month_day' => $month . '-' . $day,
                'isspecial' => $is_special,
                'effectiveDate' => $effective_Date,
            ]);

            return response()->json(['message' =>  "Item Updated Successfully!"]);
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'modifyHolidays', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    private function getWeekdayStatus($date)
    {
        if (date('D', strtotime($date)) == "Sun" || date('D', strtotime($date)) == "Sat") {
            return "Weekend";
        }
        return "Weekdays";
    }

    private function isHoliday($date)
    {
        $holiday_list = Holidaylist::where('effectiveDate', date('Y-m-d', strtotime($date)))->get();
        if (count($holiday_list) >= 1) {
            return true;
        }
        return false;
    }

    /*
    *
    *
     Report Generation
     Undertime, Overtime , present dates and its absences
    **
    */
    public function dtrUTOTReport(Request $request)
    {
        try {
            $bio = json_decode($request->biometric_id);
            $month_of = $request->monthof;
            $year_of = $request->yearof;




            $is_15th_days = $request->is15thdays;
            $dt_records = [];
            $is_Half_Schedule = false;
            $dtr = [];
            $mdtr = [];
            if (count($bio) >= 1) {

                foreach ($bio as $biometric_id) {

                    if ($this->helper->isEmployee($biometric_id)) {

                        $data = new Request([
                            'biometric_id' => json_encode([$biometric_id]),
                            'monthof' => $month_of,
                            'yearof' => $year_of,
                            'view' => 1
                        ]);
                        $this->generateDTR($data);

                        if ($is_15th_days) {
                            $first_half = $request->firsthalf;
                            $second_half = $request->secondhalf;
                            if ($first_half) {
                                $dt_records = $this->GenerateFirstHalf($month_of, $year_of, $biometric_id);
                            } else {
                                if ($second_half) {
                                    $dt_records = $this->GenerateSecondHalf($month_of, $year_of, $biometric_id);
                                }
                            }
                        } else {
                            $dt_records = $this->generateMonthly($month_of, $year_of, $biometric_id);
                        }

                        $no_Sched_dtr = [];
                        $number_Of_Days = 0;
                        $number_Of_all_Days_past = 0;
                        $date_ranges = [];
                        $entryf = '';


                        foreach ($dt_records as $key => $value) {
                            $schedule = $this->helper->getSchedule($value->biometric_id, $value->first_in);

                            $is_Half_Schedule = $this->isHalfEntrySchedule($schedule);



                            if (isset($schedule['date'])) {

                                $date_now = date('Y-m-d');
                                $sdate =  $schedule['date'];
                                $date_Range = array();
                                $current_Date = strtotime($sdate);

                                $date_Range[] = date('Y-m-d', $current_Date);
                                $current_Date = strtotime('+1 day', $current_Date);


                                $date_ranges = $date_Range;
                                $number_Of_Days += count($date_Range);
                                if ($sdate < $date_now) {
                                    $number_Of_all_Days_past = count($date_Range);
                                }
                                if (isset($value->first_in) && $value->first_in != NULL) {
                                    $entryf = $value->first_in;
                                }

                                if (isset($value->second_in) && $value->second_in != NULL) {
                                    $entryf = $value->second_in;
                                }



                                if (date('Y-m-d', strtotime($entryf)) == $sdate) {
                                    $mdtr[] = $this->mDTR($value);
                                }
                            } else {
                                $no_Sched_dtr[] = $this->mDTR($value);
                            }
                        }


                        $Records_with_Overtime = array_values(array_filter($mdtr, function ($res) {
                            return $res['overtime_minutes'] >= 1;
                        }));
                        $Records_with_Undertime = array_values(array_filter($mdtr, function ($res) {
                            return $res['undertime_minutes'] >= 1;
                        }));
                        $Time_Records = array_values(array_filter($mdtr, function ($res) {
                            return $res['total_working_minutes'] >= 1;
                        }));
                        $overtime_Sum = 0;
                        foreach ($Records_with_Overtime as $record) {
                            $overtime_Sum += $record['overtime_minutes'];
                        }
                        $undertime_Sum = 0;
                        foreach ($Records_with_Undertime as $record) {
                            $undertime_Sum += $record['undertime_minutes'];
                        }
                        $total_Hours_of_Duty = 0;
                        foreach ($Time_Records as $record) {
                            $total_Hours_of_Duty += floor($record['total_working_minutes'] - $record['undertime_minutes']); ////
                        }
                        $total_Hours_of_Duty = floor($total_Hours_of_Duty / 60);
                        $total_minutes_of_Duty = floor($total_Hours_of_Duty * 60);
                        $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
                        $days_of_duty = 0;
                        $days_Rendered = [];

                        for ($i = 1; $i <= $days_In_Month; $i++) {
                            $count = array_filter($mdtr, function ($res) use ($i) {
                                if (!is_null($res['first_in'])) {
                                    return date('d', strtotime($res['first_in'])) == $i;
                                } elseif (!is_null($res['second_in'])) {
                                    return date('d', strtotime($res['second_in'])) == $i;
                                }
                            });
                            $days_of_duty += count($count);
                            $days_Rendered[] = array_values($count);
                        }


                        $days = $days_Rendered;
                        $present_days = [];
                        foreach ($days as $entry) {

                            if (is_array($entry)) {

                                foreach ($entry as $nested_Entry) {
                                    $present_days[] = $nested_Entry;
                                }
                            }
                        }


                        $days_absences = [];
                        $days_present = [];

                        foreach ($date_ranges as $key => $value) {
                            $day_Entries = date('j', strtotime($value));
                            $count = array_filter($present_days, function ($res) use ($day_Entries) {
                                return $res['day'] == $day_Entries;
                            });

                            if (count($count) == 0) {
                                if (date('Y-m-d') > date('Y-m-d', strtotime($value))) {
                                    $days_absences[] = $value;
                                }
                            } else {
                                $days_present[] = $value;
                            }
                        }


                        $numeric_days = array_map(function ($res) {
                            $timestamp = strtotime($res);
                            $formatted_date = date('Y-m-d', $timestamp);
                            $numerical_value = date('j', $timestamp);
                            return $numerical_value + 1;
                        }, $days_present);


                        /**
                         * IF Two schedules only
                         */
                        $absences = floor($days_of_duty - $number_Of_all_Days_past);
                        if ($is_Half_Schedule) {
                            $new_Days_Absences = [];
                            foreach ($days_absences as $value) {
                                $day_Entries = date('j', strtotime($value));
                                $cnt = array_filter($numeric_days, function ($res) use ($day_Entries) {
                                    return $res == $day_Entries;
                                });
                                if (count($cnt) == 0) {
                                    if (date('Y-m-d') > date('Y-m-d', strtotime($value))) {
                                        $new_Days_Absences[] = $value;
                                    }
                                }
                            }
                            $days_absences = $new_Days_Absences;
                            $absences = count($days_absences);
                        }

                        $dtr[] = [
                            'biometric_ID' => $biometric_id,
                            'employeeName' => EmployeeProfile::where('biometric_id', $biometric_id)->first()->name(),
                            'Total_Undertime' =>  $undertime_Sum,
                            'Total_Overtime' =>   $overtime_Sum,
                            'TotalHoursofDuty' => $total_Hours_of_Duty,
                            'TotalMinutesofDuty' => $total_minutes_of_Duty,
                            'TotalScheduleDays' => $number_Of_Days,
                            'TotalDaysRendered' => $days_of_duty,
                            'TotalAbsences' => count($days_absences),
                            'TotalDayswLate' => count($Records_with_Undertime),
                            'TotalDutywNosched' => count($no_Sched_dtr),
                            'fortheMonth' => date('F', strtotime($year_of . '-' . $month_of . '-1')),
                            'fortheYear' => $year_of,
                            'Absences' => $days_absences,
                            'AllRecords' => $mdtr,
                            'RecordsWithOvertime' => $Records_with_Overtime,
                            'RecordsWithUndertime' => $Records_with_Undertime,
                            'NoschedDTR' => $no_Sched_dtr
                        ];
                    }
                }
            } else {
                $all_biometric = Biometrics::all();
                foreach ($all_biometric as $bio) {
                    $biometric_id = $bio->biometric_id;
                    if ($this->helper->isEmployee($biometric_id)) {

                        if ($is_15th_days) {
                            $first_half = $request->firsthalf;
                            $second_half = $request->secondhalf;
                            if ($first_half) {
                                $dt_records = $this->GenerateFirstHalf($month_of, $year_of, $biometric_id);
                            } else {
                                if ($second_half) {
                                    $dt_records = $this->GenerateSecondHalf($month_of, $year_of, $biometric_id);
                                }
                            }
                        } else {
                            $dt_records = $this->generateMonthly($month_of, $year_of, $biometric_id);
                        }


                        $mdtr = [];
                        $no_Sched_dtr = [];
                        $number_Of_Days = 0;
                        $number_Of_all_Days_past = 0;
                        $date_ranges = [];
                        $entryf = '';
                        foreach ($dt_records as $key => $value) {
                            $schedule = $this->helper->getSchedule($value->biometric_id, $value->first_in);
                            $is_Half_Schedule = $this->isHalfEntrySchedule($schedule);

                            if (isset($schedule['date'])) {
                                $date_now = date('Y-m-d');
                                $sdate =  $schedule['date'];

                                $date_Range = array();
                                $current_Date = strtotime($sdate);


                                $date_Range[] = date('Y-m-d', $current_Date);
                                $current_Date = strtotime('+1 day', $current_Date);
                                $date_ranges = $date_Range;
                                $number_Of_Days = count($date_Range);
                                if ($sdate < $date_now) {
                                    $number_Of_all_Days_past = count($date_Range);
                                }

                                if (isset($value->first_in)  && $value->first_in != NULL) {
                                    $entryf = $value->first_in;
                                } else if (isset($value->second_in) && $value->second_in != NULL) {
                                    $entryf = $value->second_in;
                                }



                                if (date('Y-m-d', strtotime($entryf)) == $sdate) {
                                    $mdtr[] = $this->mDTR($value);
                                }
                            } else {
                                $no_Sched_dtr[] = $this->mDTR($value);
                            }
                        }


                        $Records_with_Overtime = array_values(array_filter($mdtr, function ($res) {
                            return $res['overtime_minutes'] >= 1;
                        }));
                        $Records_with_Undertime = array_values(array_filter($mdtr, function ($res) {
                            return $res['undertime_minutes'] >= 1;
                        }));
                        $Time_Records = array_values(array_filter($mdtr, function ($res) {
                            return $res['total_working_minutes'] >= 1;
                        }));
                        $overtime_Sum = 0;
                        foreach ($Records_with_Overtime as $record) {
                            $overtime_Sum += $record['overtime_minutes'];
                        }
                        $undertime_Sum = 0;
                        foreach ($Records_with_Undertime as $record) {
                            $undertime_Sum += $record['undertime_minutes'];
                        }
                        $total_Hours_of_Duty = 0;
                        foreach ($Time_Records as $record) {
                            $total_Hours_of_Duty += floor($record['total_working_minutes'] - $record['undertime_minutes']);
                        }
                        $total_Hours_of_Duty = floor($total_Hours_of_Duty / 60);
                        $total_minutes_of_Duty = floor($total_Hours_of_Duty * 60);
                        $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
                        $days_of_duty = 0;
                        $days_Rendered = [];

                        for ($i = 1; $i <= $days_In_Month; $i++) {
                            $count = array_filter($mdtr, function ($res) use ($i) {
                                return date('d', strtotime($res['first_in'])) == $i;
                            });

                            $days_of_duty += count($count);
                            $days_Rendered[] = array_values($count);
                        }


                        $days = $days_Rendered;

                        $present_days = [];
                        foreach ($days as $entry) {

                            if (is_array($entry)) {

                                foreach ($entry as $nested_Entry) {
                                    $present_days[] = $nested_Entry;
                                }
                            }
                        }


                        $days_absences = [];
                        $days_present = [];

                        foreach ($date_ranges as $key => $value) {
                            $day_Entries = date('j', strtotime($value));
                            $count = array_filter($present_days, function ($res) use ($day_Entries) {
                                return $res['day'] == $day_Entries;
                            });

                            if (count($count) == 0) {
                                if (date('Y-m-d') > date('Y-m-d', strtotime($value))) {
                                    $days_absences[] = $value;
                                }
                            } else {
                                $days_present[] = $value;
                            }
                        }

                        $numeric_days = array_map(function ($res) {
                            $timestamp = strtotime($res);
                            $formatted_date = date('Y-m-d', $timestamp);
                            $numerical_value = date('j', $timestamp);
                            return $numerical_value + 1;
                        }, $days_present);


                        /**
                         * IF Two schedules only
                         */
                        $absences = floor($days_of_duty - $number_Of_all_Days_past);
                        if ($is_Half_Schedule) {
                            $new_Days_Absences = [];
                            foreach ($days_absences as $value) {
                                $day_Entries = date('j', strtotime($value));
                                $cnt = array_filter($numeric_days, function ($res) use ($day_Entries) {
                                    return $res == $day_Entries;
                                });
                                if (count($cnt) == 0) {
                                    if (date('Y-m-d') > date('Y-m-d', strtotime($value))) {
                                        $new_Days_Absences[] = $value;
                                    }
                                }
                            }
                            $days_absences = $new_Days_Absences;
                            $absences = count($days_absences);
                        }

                        $dtr[] = [
                            'biometric_ID' => $biometric_id,
                            'employeeName' => EmployeeProfile::where('biometric_id', $biometric_id)->first()->name(),
                            'Total_Undertime' =>  $undertime_Sum,
                            'Total_Overtime' =>   $overtime_Sum,
                            'TotalHoursofDuty' => $total_Hours_of_Duty,
                            'TotalMinutesofDuty' => $total_minutes_of_Duty,
                            'TotalScheduleDays' => $number_Of_Days,
                            'TotalDaysRendered' => $days_of_duty,
                            'TotalAbsences' => count($days_absences),
                            'TotalDayswLate' => count($Records_with_Undertime),
                            'TotalDutywNosched' => count($no_Sched_dtr),
                            'fortheMonth' => date('F', strtotime($year_of . '-' . $month_of . '-1')),
                            'fortheYear' => $year_of,
                            'Absences' => $days_absences,
                            'AllRecords' => $mdtr,
                            'RecordsWithOvertime' => $Records_with_Overtime,
                            'RecordsWithUndertime' => $Records_with_Undertime,
                            'NoschedDTR' => $no_Sched_dtr
                        ];
                    }
                }
            }



            $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
            $mdt = [];
            for ($i = 1; $i <= $days_In_Month; $i++) {
                $found = false;
                foreach ($mdtr as $d) {
                    if (!is_null($d['first_in'])) {
                        if (date('d', strtotime($d['first_in'])) == $i) {
                            $d['date'] = Carbon::create("$year_of-$month_of-$i")->format('Y-m-d');
                            $mdt[] = $d;  // Use the day from $mdtr
                            $found = true;
                        }
                    } else if (!is_null($d['second_in'])) {
                        if (date('d', strtotime($d['second_in'])) == $i) {
                            $d['date'] = Carbon::create("$year_of-$month_of-$i")->format('Y-m-d');
                            $mdt[] = $d;  // Use the day from $mdtr
                            $found = true;
                        }
                    }
                }
                if (!$found) {
                    $mdt[] =  [
                        'dtr_ID' => '',
                        'first_in' => '',
                        'first_out' => '',
                        'second_in' => '',
                        'second_out' => '',
                        'interval_req' => '',
                        'required_working_hours' => '',
                        'required_working_minutes' => '',
                        'total_working_hours' => '',
                        'total_working_minutes' => '',
                        'overtime' => '',
                        'overtime_minutes' => '',
                        'undertime' => '',
                        'undertime_minutes' => '',
                        'overall_minutes_rendered' => '',
                        'total_minutes_reg' => '',
                        'day' => $i,
                        'created_at' => '',
                        'weekStatus' => '',
                        'isHoliday' => '',
                        'date' => Carbon::create("$year_of-$month_of-$i")->format('Y-m-d')
                    ];
                }
            }

            $dtr[0]['AllRecords'] = $mdt;

            return $dtr;
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'dtrUTOTReport', $th->getMessage());
            return response()->json(['message' => $th->getMessage()]);
        }
    }


    private function generateMonthly($month_of, $year_of, $biometric_id)
    {

        return  DB::table('daily_time_records')
            ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
            ->where(function ($query) use ($biometric_id, $month_of, $year_of) {
                $query->where('biometric_id', $biometric_id)
                    ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                    ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
            })
            ->orWhere(function ($query) use ($biometric_id, $month_of, $year_of) {
                $query->where('biometric_id', $biometric_id)
                    ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                    ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
            })
            ->get();
    }

    private function generateFirstHalf($month_of, $year_of, $biometric_id)
    {
        $start_Date = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-1'));
        $end_Date = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-15'));
        $dt_records = DB::table('daily_time_records')
            ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
            ->where('biometric_id',  $biometric_id)
            ->where(function ($query) use ($start_Date, $end_Date) {
                $query->where('first_in', '>=', $start_Date)
                    ->where('first_in', '<=', $end_Date);
            })
            ->orWhere(function ($query) use ($start_Date, $end_Date) {
                $query->where('second_in', '>=', $start_Date)
                    ->where('second_in', '<=', $end_Date);
            })
            ->get();
        return $dt_records;
    }

    private function generateSecondHalf($month_of, $year_of, $biometric_id)
    {
        $start_Date = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-16'));
        $end_Date = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-31'));
        $dt_records = DB::table('daily_time_records')
            ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
            ->where('biometric_id', $biometric_id)
            ->where(function ($query) use ($start_Date, $end_Date, $month_of, $year_of) {
                $query->whereMonth('first_in', '=', $month_of)
                    ->whereYear('first_in', '=', $year_of)
                    ->where('first_in', '>=', $start_Date)
                    ->where('first_in', '<=', $end_Date);
            })
            ->orWhere(function ($query) use ($start_Date, $end_Date,  $month_of, $year_of) {
                $query->whereMonth('second_in', '=', $month_of)
                    ->whereYear('second_in', '=', $year_of)
                    ->where('second_in', '>=', $start_Date)
                    ->where('second_in', '<=', $end_Date);
            })
            ->get();
        return $dt_records;
    }

    // private function getDifferenceDate($date_start, $date_end)
    // {
    //     $start_Time_stamp = strtotime($date_start);
    //     $end_Time_stamp = strtotime($date_end);
    //     $seconds_Difference = $end_Time_stamp - $start_Time_stamp;
    //     $number_Of_Days = floor($seconds_Difference / (60 * 60 * 24));
    //     return $number_Of_Days;
    // }

    private function mDTR($value)
    {

        $sched = $this->helper->getSchedule($value->biometric_id, null);
        if ($sched['third_entry'] == NULL && $sched['last_entry']  == NULL) {
            return   [
                'dtr_ID' => $value->id,
                'first_in' => $this->FormatDate($value->first_in),
                'first_out' => null,
                'second_in' => null,
                'second_out' => $this->FormatDate($value->first_out),
                'interval_req' => $value->interval_req,
                'required_working_hours' => $value->required_working_hours,
                'required_working_minutes' => $value->required_working_minutes,
                'total_working_hours' => $value->total_working_hours,
                'total_working_minutes' => $value->total_working_minutes,
                'overtime' => $value->overtime,
                'overtime_minutes' => $value->overtime_minutes,
                'undertime' => $value->undertime,
                'undertime_minutes' => $value->undertime_minutes,
                'overall_minutes_rendered' => $value->overall_minutes_rendered,
                'total_minutes_reg' => $value->total_minutes_reg,
                'day' => $this->getDAy($value),
                'created_at' => $value->created_at,
                'weekStatus' => $this->getWeekdayStatus($value->created_at),
                'isHoliday' => $this->isHoliday($value->created_at)
            ];
        }

        return   [
            'dtr_ID' => $value->id,
            'first_in' => $this->FormatDate($value->first_in),
            'first_out' => $this->FormatDate($value->first_out),
            'second_in' => $this->FormatDate($value->second_in),
            'second_out' => $this->FormatDate($value->second_out),
            'interval_req' => $value->interval_req,
            'required_working_hours' => $value->required_working_hours,
            'required_working_minutes' => $value->required_working_minutes,
            'total_working_hours' => $value->total_working_hours,
            'total_working_minutes' => $value->total_working_minutes,
            'overtime' => $value->overtime,
            'overtime_minutes' => $value->overtime_minutes,
            'undertime' => $value->undertime,
            'undertime_minutes' => $value->undertime_minutes,
            'overall_minutes_rendered' => $value->overall_minutes_rendered,
            'total_minutes_reg' => $value->total_minutes_reg,
            'day' => $this->getDAy($value),
            'created_at' => $value->created_at,
            'weekStatus' => $this->getWeekdayStatus($value->created_at),
            'isHoliday' => $this->isHoliday($value->created_at)
        ];
    }

    public function getDAy($value)
    {
        if ($value->first_in) {
            return date('d', strtotime($value->first_in));
        }
        if (!$value->first_in && $value->second_in) {
            return date('d', strtotime($value->second_in));
        }
    }

    public function getUsersLogs(Request $request)
    {
        try {
            $biometric_ids = DB::select('SELECT biometric_id FROM `employee_profiles` WHERE biometric_id in (SELECT biometric_id FROM `biometrics`) and personal_information_id in (select id from personal_informations)');
            $data = [];


            foreach ($biometric_ids as $ids) {
                $emp =  EmployeeProfile::where('biometric_id', $ids->biometric_id)->first();
                $dtrlogs =  DailyTimeRecordLogs::where('biometric_id', $ids->biometric_id)->get();

                $latestDate = null;
                $logs = [];
                $recentlog = '';
                $date = '';

                $dtrstatus = '';
                foreach ($dtrlogs as $dtr) {
                    $date = $dtr->dtr_date;

                    $jlogs = json_decode($dtr->json_logs);
                    if ($latestDate === null || $date > $latestDate) {
                        $latestDate = $date;
                        $dtrstatus = $jlogs[count($jlogs) - 1]->status_description->description;
                        $recentlog = $jlogs[count($jlogs) - 1];
                    }
                    $employeeProfileId = $emp->id;
                    $sections = Section::select('name', 'code as sectcode')
                        ->whereIn('id', function ($query) use ($employeeProfileId) {
                            $query->select('section_id')
                                ->from('assigned_areas')
                                ->where('employee_profile_id', $employeeProfileId);
                        })
                        ->first();
                    $logs[] = [
                        'DTR_date' => $date,
                        'Logs' => $jlogs,
                    ];
                }
                /**
                 * Just Returning Records with logs
                 */
                if (count($logs) >= 1) {
                    usort($logs, function ($a, $b) {
                        return strtotime($a['DTR_date']) - strtotime($b['DTR_date']);
                    });
                    $data[] = [
                        'id' => $emp->id,
                        'biometric_id' => $ids->biometric_id,
                        'name' =>  $emp->name(),
                        'position' => $emp->findDesignation()->name,
                        'designation' =>  $sections,
                        'recentDTRdates' => $recentlog->date_time,
                        'device' => $recentlog->device_name,
                        'status' => $dtrstatus,
                        'Records' => $logs,
                        // 'recentBiometricLog'=>
                    ];
                }
            }
            return $data;
        } catch (\Throwable $th) {
            Helpersv2::errorLog($this->CONTROLLER_NAME, 'getUsersLogs', $th->getMessage());
            //throw $th;
        }
    }

    public function adjustDTR(Request $request)
    {
        try {
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function test()
    {
        /*
        **
        * test on how to access request function on another controller for instance
        */
        // $dtrentry = "2024-03-20 8:41:00";
        // $schedule = "2024-03-20 8:00:00";
        // $isoncall = 1;
        // $alloted_mins_Oncall = 0.5; // 30 minutes

        // $dtr_date = date('Y-m-d', strtotime($dtrentry));
        // $max_allowed_entry_for_oncall = env('MAX_ALLOWED_ENTRY_ONCALL');

        // /* With Schedule Entry */
        // $in_Entry = $schedule;

        // $time_stamp = strtotime($in_Entry);


        // $new_Time_stamp = $time_stamp - (12 * 3600);
        // $Calculated_allotedHours = date('Y-m-d H:i:s', $new_Time_stamp);

        // $newEntry = $schedule;
        // if ($isoncall) {
        //     //// Logic for ONCALL

        //     $schedEntry = $time_stamp + ($alloted_mins_Oncall * 1800); // 30 mins

        //     $calIn = date("Y-m-d H:i:s", $schedEntry);
        //     $dtrentry = date("Y-m-d H:i:s", strtotime($dtrentry));
        //     if ($calIn <= $dtrentry) {

        //         //within 30 mins.
        //         // then accept as 8am straight
        //         $newEntry = date("Y-m-d H:i:s", strtotime($dtrentry . "-30 minutes"));
        //     }

        //     return $newEntry;
        // } else {
        //     // if ($Calculated_allotedHours <= $dtrentry['date_time']) { //within alloted hours to timein
        //     //     DailyTimeRecords::create([
        //     //         'biometric_id' => $biometric_id,
        //     //         'dtr_date' => $dtr_date,
        //     //         'first_in' => $dtrentry['date_time'],
        //     //         'is_biometric' => 1,
        //     //     ]);
        //     // }
        // }

        for ($i = 1; $i <= 30; $i++) {

            $date = date('Y-m-d', strtotime('2024-02-' . $i));

            if (date('D', strtotime($date)) != 'Sun') {
                $firstin = date('H:i:s', strtotime('today') + rand(25200, 30600));
                $firstout =  date('H:i:s', strtotime('today') + rand(42600, 47400));
                $secondin =  date('H:i:s', strtotime('today') + rand(45000, 49800));
                $secondout = date('H:i:s', strtotime('today') + rand(59400, 77400));

                DailyTimeRecords::create([
                    'biometric_id' => 1,
                    'dtr_date' => $date,
                    'first_in' => date('Y-m-d H:i:s', strtotime($date . ' ' . $firstin)),
                    'first_out' => date('Y-m-d H:i:s', strtotime($date . ' ' . $firstout)),
                    'second_in' => date('Y-m-d H:i:s', strtotime($date . ' ' . $secondin)),
                    'second_out' => date('Y-m-d H:i:s', strtotime($date . ' ' . $secondout)),
                    'interval_req' => null,
                    'required_working_hours' => null,
                    'required_working_minutes' => null,
                    'total_working_hours' => null,
                    'total_working_minutes' => null,
                    'overtime' => null,
                    'overtime_minutes' => null,
                    'undertime' => null,
                    'undertime_minutes' => null,
                    'overall_minutes_rendered' => null,
                    'total_minutes_reg' => null,
                    'is_biometric' => 1,
                    'created_at' => date('Y-m-d H:i:s', strtotime($date . ' ' . $firstin))
                ]);
            }
        }
    }
}
