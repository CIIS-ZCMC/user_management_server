<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Models\DailyTimeRecords;
use App\Methods\Helpers;
use App\Methods\BioControl;
use App\Models\DtrAnomalies;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Http\Controllers\DTR\BioMSController;
use App\Models\Holidaylist;
use App\Models\EmployeeProfile;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class DTRcontroller extends Controller
{
    protected $helper;
    protected $device;
    protected $ip;
    protected $bioms;
    protected $devices;
    public function __construct()
    {
        $this->helper = new Helpers();
        $this->device = new BioControl();
        $this->bioms = new BioMSController();
        $this->devices = json_decode($this->bioms->operatingDevice()->getContent(), true)['data'];
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



                        // Add Validation here based on DT of user based on server Time and the Interval of pull
                        if (count($check_Records) >= 1) {

                            foreach ($check_Records as $key => $value) {
                                $biometric_id =  $value['biometric_id'];
                                if ($this->helper->isEmployee($biometric_id)) { // Validating if User is an employee with Biometric data and employee data
                                    $validate = DailyTimeRecords::whereDate('created_at', $date_now)->where('biometric_id', $biometric_id)->get();
                                    $date_now = date('Y-m-d');

                                    if (count($validate) >= 1) {
                                        /* Updating All existing  Records */

                                        $f1 = $validate[0]->first_in;
                                        $f2 =  $validate[0]->first_out;
                                        $f3 = $validate[0]->second_in;
                                        $f4 = $validate[0]->second_out;
                                        $rwm = $validate[0]->required_working_minutes;
                                        $o_all_min = $validate[0]->total_working_minutes;

                                        /* -------------    -----------------------------------------Replace this values-------------------------------------------------------------------- */

                                        /* GET THE DATA BASED ON EMPLOYEE SCHEDULE */
                                        $time_stamps_req = $this->helper->getSchedule($biometric_id, null); //biometricID

                                        /* ---------------------------------------------------------------------------------------------------------------------------------------------- */

                                        if ($f1 && !$f2 && !$f3 && !$f4) {
                                            if ($value['status'] == 255) {
                                                if ($this->helper->withinInterval($f1, $this->helper->sequence(0, [$value]))) {
                                                    $this->helper->saveTotalWorkingHours(
                                                        $validate,
                                                        $value,
                                                        $this->helper->sequence(0, [$value]),
                                                        $time_stamps_req,
                                                        false
                                                    );
                                                }
                                            }
                                            if ($value['status'] == 1) {
                                                $this->helper->saveTotalWorkingHours(
                                                    $validate,
                                                    $value,
                                                    $this->helper->sequence(0, [$value]),
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

                                            if ($o_all_min <= $percent_Trendered) { // if allmins rendered is less than the 60% time req . then accept a second entry

                                                if ($value['status'] == 255) {
                                                    if ($this->helper->withinInterval($f2, $this->helper->sequence(0, [$value]))) {
                                                        $this->helper->saveIntervalValidation(
                                                            $this->helper->sequence(0, [$value]),
                                                            $validate
                                                        );
                                                    }
                                                }
                                                if ($value['status'] == 0) {

                                                    $this->helper->saveIntervalValidation(
                                                        $this->helper->sequence(0, [$value]),
                                                        $validate
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


                                            if ($value['status'] == 255) {
                                                if ($this->helper->withinInterval($f3, $this->helper->sequence(0, [$value]))) {
                                                    $this->helper->saveTotalWorkingHours(
                                                        $validate,
                                                        $value,
                                                        $this->helper->sequence(0, [$value]),
                                                        $time_stamps_req,
                                                        false
                                                    );
                                                }
                                            }


                                            if ($value['status'] == 1) {
                                                $this->helper->saveTotalWorkingHours(
                                                    $validate,
                                                    $value,
                                                    $this->helper->sequence(0, [$value]),
                                                    $time_stamps_req,
                                                    false
                                                );
                                            }
                                        }
                                        /*Check notset in_am and notset out_pm and  check In_pm and not set out_pm */
                                        /* 
                                        Here we are setting the Last entry of Second half. with no First half of Entries.
                                        Overtime and undertime, as well as working hours, have already been calculated.
                                    */
                                        if (!$f1 && !$f2 && $f3 && !$f4) {

                                            if ($value['status'] == 255) {
                                                if ($this->helper->withinInterval($f3, $this->helper->sequence(0, [$value]))) {
                                                    $this->helper->saveTotalWorkingHours(
                                                        $validate,
                                                        $value,
                                                        $this->helper->sequence(0, [$value]),
                                                        $time_stamps_req,
                                                        false
                                                    );
                                                }
                                            }

                                            if ($value['status'] == 1) {
                                                $this->helper->saveTotalWorkingHours(
                                                    $validate,
                                                    $value,
                                                    $this->helper->sequence(0, [$value]),
                                                    $time_stamps_req,
                                                    false
                                                );
                                            }
                                        }
                                    } else {
                                        /**
                                         * Here we are checking if theres an existing first entry this is usually for nursing and doctors
                                         * which has two entries for schedule only.
                                         * if data not found. then we save into first entry
                                         */


                                        $yester_date = date('Y-m-d', strtotime('-1 day'));
                                        $time_stamps_req = $this->helper->getSchedule($biometric_id, null);

                                        $check_yesterday_Records = DailyTimeRecords::whereDate('created_at', $yester_date)->where('biometric_id', $biometric_id)->get();
                                        $proceed_new = false;
                                        if (count($check_yesterday_Records) >= 1) {
                                            foreach ($check_yesterday_Records as $key => $rcrd) {
                                                $f_1 = $rcrd['first_in'];
                                                $f_2 = $rcrd['first_out'];
                                                $f_3 = $rcrd['second_in'];
                                                $f_4 = $rcrd['second_out'];
                                                $id  = $rcrd['dtr_id'];
                                                $emp_ID = $rcrd['biometric_id'];



                                                if ($f_1 && $f_2) {
                                                    $proceed_new = true;
                                                }

                                                if (!$f_1 && !$f_2 && $f_3 && $f_4) {
                                                    $proceed_new = true;
                                                }

                                                if ($f_1 && $f_2 && $f_3 && $f_4) {
                                                    $proceed_new = true;
                                                }

                                                /* this entry only */
                                                if ($f_1 && !$f_2) {



                                                    foreach ($check_Records as $key => $chrc) {
                                                        if ($chrc['biometric_id'] == $emp_ID) {

                                                            if ($chrc['status'] == 255) {
                                                                if ($this->helper->withinInterval($f_1, $this->helper->sequence(0, [$chrc]))) {
                                                                    $this->helper->saveTotalWorkingHours(
                                                                        $check_yesterday_Records,
                                                                        $chrc,
                                                                        $this->helper->sequence(0, [$chrc]),
                                                                        $time_stamps_req,
                                                                        false
                                                                    );
                                                                }
                                                            }
                                                            if ($chrc['status'] == 1) {
                                                                //employeeID

                                                                $this->helper->SaveTotalWorkingHours(
                                                                    $check_yesterday_Records,
                                                                    $chrc,
                                                                    $this->helper->sequence(0, [$chrc]),
                                                                    $time_stamps_req,
                                                                    false
                                                                );
                                                            }
                                                        }
                                                    }
                                                } else {

                                                    if ($value['status'] == 0 || $value['status'] == 255) {
                                                        $break_Time_Req = $this->helper->getBreakSchedule($biometric_id, $time_stamps_req); // Put employee ID
                                                        $this->helper->SaveFirstEntry(
                                                            $this->helper->sequence(0, [$value]),
                                                            $break_Time_Req,
                                                            $biometric_id,
                                                            $check_Records
                                                        );
                                                    }
                                                }
                                            }
                                        } else {

                                            /* Save new records */
                                            if ($value['status'] == 0 || $value['status'] == 255) {

                                                $break_Time_Req = $this->helper->getBreakSchedule($biometric_id, $time_stamps_req); // Put employee ID
                                                $this->helper->SaveFirstEntry(
                                                    $this->helper->sequence(0, [$value]),
                                                    $break_Time_Req,
                                                    $biometric_id,
                                                    $check_Records
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                            /* Save DTR Logs */
                            $this->helper->saveDTRLogs($check_Records, 1, $device);
                            /* Clear device data */
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
            Log::channel("custom-dtr-log-error")->error($th->getMessage());
            return response()->json(['message' => 'Unable to connect to device', 'Throw error' => $th->getMessage()]);
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
            $biometric_id = $request->biometric_id;
            $month_of = $request->monthof;
            $year_of = $request->yearof;
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
            $udata = [
                'biometric_id' => $biometric_id,
                'employee_id' => 0, // replace with employee details
                'employeeName' => 'jhon legend',
                'sex' => 'male',
                'dateofbirth' => '05/05/2004',
                'department' => 'MMS',
                'date_hired' => '02/02/2023',
                'dtr_records' => $mdtr
            ];

            return response()->json(['data' => $udata]);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    private function arrivalDeparture($time_stamps_req, $year_of, $month_of)
    {
        $f1 = strtoupper(date('h:ia', strtotime($year_of . '-' . $month_of . '-1 ' . $time_stamps_req['first_entry'])));
        $f2 = strtoupper(date('h:ia', strtotime($year_of . '-' . $month_of . '-1 ' . $time_stamps_req['second_entry'])));
        $f3 = strtoupper(date('h:ia', strtotime($year_of . '-' . $month_of . '-1 ' . $time_stamps_req['third_entry'])));
        $f4 = strtoupper(date('h:ia', strtotime($year_of . '-' . $month_of . '-1 ' . $time_stamps_req['last_entry'])));

        if (!$time_stamps_req['first_entry'] && !$time_stamps_req['second_entry'] && !$time_stamps_req['third_entry'] && !$time_stamps_req['last_entry']) {
            return "NO SCHEDULE";
        } elseif ($time_stamps_req['first_entry'] && $time_stamps_req['second_entry'] && !$time_stamps_req['third_entry'] && !$time_stamps_req['last_entry']) {
            return $f1 . '-' . $f2;
        } else {
            return $f1 . '-' . $f2 . '/' . $f3 . '-' . $f4;
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
    public function generateDTR(Request $request)
    {
        try {
            $biometric_id =  $request->biometric_id;
            $month_of = $request->monthof;
            $year_of = $request->yearof;
            $view = $request->view;
            $emp_name = '';
            if ($this->helper->isEmployee($biometric_id)) {
                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();
                $emp_name = $employee->name();
            } else {
                return response()->json([
                    'message' => 'Failed to Generate: Data not found'
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
            $arrival_Departure = '';
            foreach ($dtr as $val) {
                $time_stamps_req = $this->helper->getSchedule($biometric_id, $val->first_in); //biometricID
                $arrival_Departure = $this->arrivalDeparture($time_stamps_req, $year_of, $month_of);
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
            $ohf = isset($time_stamps_req) ? $time_stamps_req['total_hours'] . ' HOURS' : null;
            $emp_Details = [
                'OHF' => $ohf,
                'Arrival_Departure' => $arrival_Departure,
                'Employee_Name' => $emp_name,
                'DTRFile_Name' => $emp_name
            ];
            return $this->PrintDtr($month_of, $year_of, $biometric_id, $emp_Details, $view);
        } catch (\Throwable $th) {

            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    /* 
    *    This is either view or print as PDF
    *
    */

    public function printDtr($month_of, $year_of, $biometric_id, $emp_Details, $view)
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
            foreach ($dtr as $val) {
                /* Validating DTR with its Matching Schedules */
                /* 
                *   if no matching schedule then
                *   it will not display the daily time record
                */
                $schedule = $this->helper->getSchedule($val->biometric_id, $val->first_in);
                $is_Half_Schedule = $this->isHalfEntrySchedule($schedule);

                if (isset($schedule['date_start']) && isset($schedule['date_end'])) {
                    $date_start =  $schedule['date_start'];
                    $date_end =  $schedule['date_end'];
                    $entry = '';
                    if (isset($val->first_in)) {
                        $entry = $val->first_in;
                    } else {
                        if (isset($val->second_in)) {
                            $entry = $val->second_in;
                        }
                    }


                    if ($entry >= $date_start && $entry <= $date_end) {
                        $date_entry = date('Y-m-d H:i', strtotime($entry));
                        $schedule_fEntry = date('Y-m-d H:i', strtotime(date('Y-m-d', strtotime($date_entry)) . ' ' . $schedule['first_entry']));
                        //return $this->WithinScheduleRange($dateentry, $schedulefEntry);
                        if ($this->WithinScheduleRange($date_entry, $schedule_fEntry)) {
                            $dt_records[] = [
                                'first_in' => $val->first_in,
                                'first_out' => $val->first_out,
                                'second_in' => $val->second_in,
                                'second_out' => $val->second_out,
                                'undertime_minutes' => $val->undertime_minutes,
                                'created' => $val->created_at
                            ];
                        }
                    }
                } else {
                    $No_schedule_DTR[] = [
                        'first_in' => $val->first_in,
                        'first_out' => $val->first_out,
                        'second_in' => $val->second_in,
                        'second_out' => $val->second_out,
                        'undertime_minutes' => $val->undertime_minutes,
                        'created' => $val->created_at
                    ];
                }
            }
            $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
            $first_in = array_map(function ($res) {
                return [
                    'first_in' => $res['first_in']
                ];
            }, $dt_records);

            $first_out = array_map(function ($res) {
                return [
                    'first_out' => $res['first_out']
                ];
            }, $dt_records);

            $second_in = array_map(function ($res) {
                return [
                    'second_in' => $res['second_in']
                ];
            }, $dt_records);

            $second_out = array_map(function ($res) {
                return [
                    'second_out' => $res['second_out']
                ];
            }, $dt_records);

            $ut =  array_map(function ($res) {
                return [
                    'created' => $res['created'],
                    'undertime' => $res['undertime_minutes']
                ];
            }, $dt_records);

            $holidays = DB::table('holidays')->get();



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
                    'Arrival_Departure' => $emp_Details['Arrival_Departure'],
                    'Employee_Name' => $emp_Details['Employee_Name'],
                    'dtrRecords' => $dt_records,
                    'holidays' => $holidays,
                    'print_view' => true,
                    'halfsched' => $is_Half_Schedule,
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
                    'Arrival_Departure' => $emp_Details['Arrival_Departure'],
                    'Employee_Name' => $emp_Details['Employee_Name'],
                    'dtrRecords' => $dt_records,
                    'holidays' => $holidays,
                    'print_view' => false,
                    'halfsched' => $is_Half_Schedule,
                ]));

                $dompdf->setPaper('Letter', 'portrait');
                $dompdf->render();
                $monthName = date('F', strtotime($year_of . '-' . sprintf('%02d', $month_of) . '-1'));
                $filename = $emp_Details['DTRFile_Name'] . ' (DTR ' . $monthName . '-' . $year_of . ').pdf';

                /* Downloads as PDF */
                $dompdf->stream($filename);
            }
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    /* ----------------------------------------------------------------END OF GENERATION OF DAILY TIME RECORDS----------------------------------------------------------------------------------------------------------------------------- */


    public function getHolidays()
    {
        try {
            return response()->json(['data' => Holidaylist::all()]);
        } catch (\Throwable $th) {
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
            $biometric_id = $request->biometric_id;
            $month_of = $request->monthof;
            $year_of = $request->yearof;
            $is_15th_days = $request->is15thdays;
            $dt_records = [];
            $is_Half_Schedule = false;
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

                if (isset($schedule['date_start']) && isset($schedule['date_end'])) {
                    $date_now = date('Y-m-d');
                    $date_start =  $schedule['date_start'];
                    $date_end =  $schedule['date_end'];
                    $date_Range = array();
                    $current_Date = strtotime($date_start);
                    $end_Date = strtotime($date_end);

                    while ($current_Date <= $end_Date) {
                        $date_Range[] = date('Y-m-d', $current_Date);
                        $current_Date = strtotime('+1 day', $current_Date);
                    }
                    $date_ranges = $date_Range;
                    $number_Of_Days = $this->getDifferenceDate($date_start, $date_end) + 1;
                    if ($date_end < $date_now) {
                        $number_Of_all_Days_past = $this->getDifferenceDate($date_start, $date_end) + 1;
                    }
                    if (isset($value->first_in)) {
                        $entryf = $value->first_in;
                    } else
                    if (isset($value->second_in)) {
                        $entryf = $value->second_in;
                    }
                    if ($entryf >= $date_start && $entryf <= $date_end) {
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

            $dtr = [
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

            return $dtr;
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Invalid Month']);
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

    private function getDifferenceDate($date_start, $date_end)
    {
        $start_Time_stamp = strtotime($date_start);
        $end_Time_stamp = strtotime($date_end);
        $seconds_Difference = $end_Time_stamp - $start_Time_stamp;
        $number_Of_Days = floor($seconds_Difference / (60 * 60 * 24));
        return $number_Of_Days;
    }

    private function mDTR($value)
    {
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
    public function test()
    {
        /* 
        **
        * test on how to access request function on another controller for instance
        */
        for ($i = 1; $i < 30; $i++) {

            $date = date('Y-m-d', strtotime('2023-11-' . $i));
            $firstin = date('H:i:s', strtotime('today') + rand(25200, 30600));
            $firstout =  date('H:i:s', strtotime('today') + rand(42600, 47400));
            $secondin =  date('H:i:s', strtotime('today') + rand(45000, 49800));
            $secondout = date('H:i:s', strtotime('today') + rand(59400, 77400));

            DailyTimeRecords::create([
                'biometric_id' => 5181,
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
