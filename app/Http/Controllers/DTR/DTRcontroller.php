<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\daily_time_records;
use App\Methods\Helpers;
use App\Methods\Bio_contr;
use App\Models\dtr_anomalies;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Http\Controllers\DTR\BioMSController;
use App\Models\holiday_list;
use App\Http\Controllers\Controller;

class DTRcontroller extends Controller
{
    protected $helper;
    protected $Device;
    protected $ip;
    protected $bioms;
    protected $devices;
    public function __construct()
    {
        $this->helper = new Helpers();
        $this->Device = new Bio_contr();
        $this->bioms = new BioMSController();
        $this->devices = json_decode($this->bioms->operating_device()->getContent(), true)['data'];
    }

    public function Fetch_DTR_from_Device(Request $request)
    {

        try {

            foreach ($this->devices as $device) {


                if ($tad = $this->Device->BIO($device)) { //Checking if connected to device

                    $logs = $tad->get_att_log();
                    $all_user_info = $tad->get_all_user_info();
                    $attendance = simplexml_load_string($logs);
                    $userInf = simplexml_load_string($all_user_info);
                    $attendanceLogs =  $this->helper->Get_Attendance($attendance);
                    $EmployeeInfo  = $this->helper->Get_Employee($userInf);
                    $EmployeeAttendance = $this->helper->Get_Employee_attendance(
                        $attendanceLogs,
                        $EmployeeInfo
                    );
                    $dateandtimeD = simplexml_load_string($tad->get_date());
                    if ($this->helper->Validated_DeviceDT($dateandtimeD)) { //Validating Time of server and time of device
                        $datenow = date('Y-m-d');

                        $checkRecords = array_filter($EmployeeAttendance, function ($attd) use ($datenow) {
                            return date('Y-m-d', strtotime($attd['date_time'])) == $datenow;
                        });


                        // Add Validation here based on DT of user based on server Time and the Interval of pull
                        if (count($checkRecords) >= 1) {

                            foreach ($checkRecords as $key => $value) {

                                $biometric_id =  $value['biometric_id'];

                                if ($this->helper->isEmployee($biometric_id)) {
                                    $validate = daily_time_records::whereDate('created_at', $datenow)->where('biometric_id', $biometric_id)->get();
                                    $datenow = date('Y-m-d');

                                    if (count($validate) >= 1) {
                                        /* Updating All existing  Records */

                                        $f1 = $validate[0]->first_in;
                                        $f2 =  $validate[0]->first_out;
                                        $f3 = $validate[0]->second_in;
                                        $f4 = $validate[0]->second_out;
                                        $rwm = $validate[0]->required_working_minutes;
                                        $oallmin = $validate[0]->total_working_minutes;

                                        /* -------------    -----------------------------------------Replace this values-------------------------------------------------------------------- */

                                        /* GET THE DATA BASED ON EMPLOYEE SCHEDULE */
                                        $timestampsreq = $this->helper->Get_Schedule($biometric_id, null); //biometricID

                                        /* ---------------------------------------------------------------------------------------------------------------------------------------------- */

                                        if ($f1 && !$f2 && !$f3 && !$f4) {


                                            if ($value['status'] == 255) {
                                                if ($this->helper->Within_Interval($f1, $this->helper->sequence(0, [$value]))) {
                                                    return   $this->helper->SaveTotalWorkingHours(
                                                        $validate,
                                                        $value,
                                                        $this->helper->sequence(0, [$value]),
                                                        $timestampsreq,
                                                        false
                                                    );
                                                }
                                            }

                                            if ($value['status'] == 1) {
                                                $this->helper->SaveTotalWorkingHours(
                                                    $validate,
                                                    $value,
                                                    $this->helper->sequence(0, [$value]),
                                                    $timestampsreq,
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
                                            $percentTrendered = floor($rwm * 0.6); //60% of Time rendered. then considered as 1 entry

                                            if ($oallmin <= $percentTrendered) { // if allmins rendered is less than the 60% time req . then accept a second entry

                                                if ($value['status'] == 255) {
                                                    if ($this->helper->Within_Interval($f2, $this->helper->sequence(0, [$value]))) {
                                                        $this->helper->SaveIntervalValidation(
                                                            $this->helper->sequence(0, [$value]),
                                                            $validate
                                                        );
                                                    }
                                                }

                                                if ($value['status'] == 0) {

                                                    $this->helper->SaveIntervalValidation(
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
                                                if ($this->helper->Within_Interval($f3, $this->helper->sequence(0, [$value]))) {
                                                    $this->helper->SaveTotalWorkingHours(
                                                        $validate,
                                                        $value,
                                                        $this->helper->sequence(0, [$value]),
                                                        $timestampsreq,
                                                        false
                                                    );
                                                }
                                            }


                                            if ($value['status'] == 1) {
                                                $this->helper->SaveTotalWorkingHours(
                                                    $validate,
                                                    $value,
                                                    $this->helper->sequence(0, [$value]),
                                                    $timestampsreq,
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
                                                if ($this->helper->Within_Interval($f3, $this->helper->sequence(0, [$value]))) {
                                                    $this->helper->SaveTotalWorkingHours(
                                                        $validate,
                                                        $value,
                                                        $this->helper->sequence(0, [$value]),
                                                        $timestampsreq,
                                                        false
                                                    );
                                                }
                                            }

                                            if ($value['status'] == 1) {
                                                $this->helper->SaveTotalWorkingHours(
                                                    $validate,
                                                    $value,
                                                    $this->helper->sequence(0, [$value]),
                                                    $timestampsreq,
                                                    false
                                                );
                                            }
                                        }
                                    } else {

                                        $yesterdate = date('Y-m-d', strtotime('-1 day'));
                                        $timestampsreq = $this->helper->Get_Schedule($biometric_id, null);

                                        $checkyesterdayRecords = daily_time_records::whereDate('created_at', $yesterdate)->where('biometric_id', $biometric_id)->get();
                                        $proceed_new = false;
                                        if (count($checkyesterdayRecords) >= 1) {
                                            foreach ($checkyesterdayRecords as $key => $rcrd) {
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



                                                    foreach ($checkRecords as $key => $chrc) {
                                                        if ($chrc['biometric_id'] == $emp_ID) {

                                                            if ($chrc['status'] == 255) {
                                                                if ($this->helper->Within_Interval($f_1, $this->helper->sequence(0, [$chrc]))) {
                                                                    $this->helper->SaveTotalWorkingHours(
                                                                        $checkyesterdayRecords,
                                                                        $chrc,
                                                                        $this->helper->sequence(0, [$chrc]),
                                                                        $timestampsreq,
                                                                        false
                                                                    );
                                                                }
                                                            }
                                                            if ($chrc['status'] == 1) {
                                                                //employeeID

                                                                $this->helper->SaveTotalWorkingHours(
                                                                    $checkyesterdayRecords,
                                                                    $chrc,
                                                                    $this->helper->sequence(0, [$chrc]),
                                                                    $timestampsreq,
                                                                    false
                                                                );
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        /* Save new records */
                                        if ($value['status'] == 0 || $value['status'] == 255) {

                                            $breakTimeReq = $this->helper->Get_BreakSchedule($biometric_id, $timestampsreq); // Put employee ID
                                            $this->helper->SaveFirstEntry(
                                                $this->helper->sequence(0, [$value]),
                                                $breakTimeReq,
                                                $biometric_id,
                                                $checkRecords
                                            );
                                        }
                                    }
                                }
                            }
                            /* Save DTR Logs */
                            $this->helper->SaveDTRLogs($checkRecords, 1, $device);
                            /* Clear device data */
                            $tad->delete_data(['value' => 3]);
                        }
                    } else {
                        //Save anomaly entries
                        foreach ($EmployeeAttendance as $key => $value) {
                            dtr_anomalies::create([
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
            return response()->json(['message' => 'Unable to connect to device'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function FormatDate($date)
    {
        if ($date === null) {
            return null;
        }
        //return date('Y-m-d H:i:s', $date);
        return $date;
    }


    public function FetchUser_DTR(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            $monthof = $request->monthof;
            $yearof = $request->yearof;
            $dtr = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where(function ($query) use ($biometric_id, $monthof, $yearof) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $monthof)
                        ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $yearof);
                })
                ->orWhere(function ($query) use ($biometric_id, $monthof, $yearof) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $monthof)
                        ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $yearof);
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

    private function ArrivalDeparture($timestampsreq, $yearof, $monthof)
    {
        $f1 = strtoupper(date('h:ia', strtotime($yearof . '-' . $monthof . '-1 ' . $timestampsreq['first_entry'])));
        $f2 = strtoupper(date('h:ia', strtotime($yearof . '-' . $monthof . '-1 ' . $timestampsreq['second_entry'])));
        $f3 = strtoupper(date('h:ia', strtotime($yearof . '-' . $monthof . '-1 ' . $timestampsreq['third_entry'])));
        $f4 = strtoupper(date('h:ia', strtotime($yearof . '-' . $monthof . '-1 ' . $timestampsreq['last_entry'])));

        if (!$timestampsreq['first_entry'] && !$timestampsreq['second_entry'] && !$timestampsreq['third_entry'] && !$timestampsreq['last_entry']) {
            return "NO SCHEDULE";
        } elseif ($timestampsreq['first_entry'] && $timestampsreq['second_entry'] && !$timestampsreq['third_entry'] && !$timestampsreq['last_entry']) {
            return $f1 . '-' . $f2;
        } else {
            return $f1 . '-' . $f2 . '/' . $f3 . '-' . $f4;
        }
    }

    private function isHalfEntrySchedule($timestampsreq)
    {
        if (!$timestampsreq['first_entry'] && !$timestampsreq['second_entry'] && !$timestampsreq['third_entry'] && !$timestampsreq['last_entry']) {
            return false;
        } elseif ($timestampsreq['first_entry'] && $timestampsreq['second_entry'] && !$timestampsreq['third_entry'] && !$timestampsreq['last_entry']) {
            return true;
        }
        return false;
    }

    private function WithinScheduleRange($entry, $schedule)
    {
        $startTimestamp = $this->helper->ForceToStrtimeFormat($entry);
        $endTimestamp = $this->helper->ForceToStrtimeFormat($schedule);
        $secondsDifference = $endTimestamp - $startTimestamp;
        $hours = $secondsDifference / 3600;
        $acceptedEntryRange = 3; // 3 Hours is considered entry

        if ($hours <= $acceptedEntryRange) {
            return true;
        }
        return false;
    }
    /* ----------------------------------------------------------------GENERATION OF DAILY TIME RECORDS----------------------------------------------------------------------------------------------------------------------------- */
    public function Generate_DTR(Request $request)
    {
        try {
            $biometric_id =  $request->biometric_id;
            $monthof = $request->monthof;
            $yearof = $request->yearof;
            $view = $request->view;

            $dtr = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where(function ($query) use ($biometric_id, $monthof, $yearof) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $monthof)
                        ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $yearof);
                })
                ->orWhere(function ($query) use ($biometric_id, $monthof, $yearof) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $monthof)
                        ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $yearof);
                })
                ->get();

            $arrivalDeparture = '';

            foreach ($dtr as $val) {
                $timestampsreq = $this->helper->Get_Schedule($biometric_id, $val->first_in); //biometricID
                $arrivalDeparture = $this->ArrivalDeparture($timestampsreq, $yearof, $monthof);

                if (count($timestampsreq) >= 1) {
                    $validate = [
                        (object)[
                            'id' => $val->id,
                            'first_in' => $val->first_in,
                            'first_out' => $val->first_out,
                            'second_in' => $val->second_in,
                            'second_out' => $val->second_out
                        ],
                    ];
                    $this->helper->SaveTotalWorkingHours(
                        $validate,
                        $val,
                        $val,
                        $timestampsreq,
                        true
                    );
                }
            }
            $ohf = isset($timestampsreq) ? $timestampsreq['total_hours'] . ' HOURS' : null;
            $empDetails = [
                'OHF' => $ohf,
                'Arrival_Departure' => $arrivalDeparture,
                'Employee_Name' => 'Reenjay M. Caimor',
                'DTRFile_Name' => 'Caimor,Reenjay M.'
            ];
            return $this->Print_Dtr($monthof, $yearof, $biometric_id, $empDetails, $view);
        } catch (\Throwable $th) {
            return $th;
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    /* 
    *    This is either view or print as PDF
    *
    */

    public function Print_Dtr($monthof, $yearof, $biometric_id, $empDetails, $view)
    {
        try {
            $dtr = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where(function ($query) use ($biometric_id, $monthof, $yearof) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $monthof)
                        ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $yearof);
                })
                ->orWhere(function ($query) use ($biometric_id, $monthof, $yearof) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $monthof)
                        ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $yearof);
                })
                ->get();

            $dtrecords = [];
            $NoscheduleDTR = [];
            $isHalfSchedule = false;
            foreach ($dtr as $val) {
                /* Validating DTR with its Matching Schedules */
                /* 
                *   if no matching schedule then
                *   it will not display the daily time record
                */
                $schedule = $this->helper->Get_Schedule($val->biometric_id, $val->first_in);
                $isHalfSchedule = $this->isHalfEntrySchedule($schedule);

                if (isset($schedule['date_start']) && isset($schedule['date_end'])) {
                    $datestart =  $schedule['date_start'];
                    $dateend =  $schedule['date_end'];
                    $entry = '';
                    if (isset($val->first_in)) {
                        $entry = $val->first_in;
                    } else {
                        if (isset($val->second_in)) {
                            $entry = $val->second_in;
                        }
                    }


                    if ($entry >= $datestart && $entry <= $dateend) {
                        $dateentry = date('Y-m-d H:i', strtotime($entry));
                        $schedulefEntry = date('Y-m-d H:i', strtotime(date('Y-m-d', strtotime($dateentry)) . ' ' . $schedule['first_entry']));
                        //return $this->WithinScheduleRange($dateentry, $schedulefEntry);
                        if ($this->WithinScheduleRange($dateentry, $schedulefEntry)) {
                            $dtrecords[] = [
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
                    $NoscheduleDTR[] = [
                        'first_in' => $val->first_in,
                        'first_out' => $val->first_out,
                        'second_in' => $val->second_in,
                        'second_out' => $val->second_out,
                        'undertime_minutes' => $val->undertime_minutes,
                        'created' => $val->created_at
                    ];
                }
            }
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthof, $yearof);
            $firstin = array_map(function ($res) {
                return [
                    'first_in' => $res['first_in']
                ];
            }, $dtrecords);

            $firstout = array_map(function ($res) {
                return [
                    'first_out' => $res['first_out']
                ];
            }, $dtrecords);

            $secondin = array_map(function ($res) {
                return [
                    'second_in' => $res['second_in']
                ];
            }, $dtrecords);

            $secondout = array_map(function ($res) {
                return [
                    'second_out' => $res['second_out']
                ];
            }, $dtrecords);

            $ut =  array_map(function ($res) {
                return [
                    'created' => $res['created'],
                    'undertime' => $res['undertime_minutes']
                ];
            }, $dtrecords);

            $holidays = DB::table('holiday_lists')->get();



            if ($view) {
                return view('generate_dtr.printDTR_PDF',  [
                    'daysInMonth' => $daysInMonth,
                    'year' => $yearof,
                    'month' => $monthof,
                    'firstin' => $firstin,
                    'firstout' => $firstout,
                    'secondin' => $secondin,
                    'secondout' => $secondout,
                    'undertime' => $ut,
                    'OHF' => $empDetails['OHF'],
                    'Arrival_Departure' => $empDetails['Arrival_Departure'],
                    'Employee_Name' => $empDetails['Employee_Name'],
                    'dtrRecords' => $dtrecords,
                    'holidays' => $holidays,
                    'print_view' => true,
                    'halfsched' => $isHalfSchedule,
                ]);
            } else {
                $options = new Options();
                $options->set('isPhpEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);
                $dompdf = new Dompdf($options);
                $dompdf->getOptions()->setChroot([base_path() . '\public\storage']);
                $dompdf->loadHtml(view('generate_dtr.printDTR_PDF',  [
                    'daysInMonth' => $daysInMonth,
                    'year' => $yearof,
                    'month' => $monthof,
                    'firstin' => $firstin,
                    'firstout' => $firstout,
                    'secondin' => $secondin,
                    'secondout' => $secondout,
                    'undertime' => $ut,
                    'OHF' => $empDetails['OHF'],
                    'Arrival_Departure' => $empDetails['Arrival_Departure'],
                    'Employee_Name' => $empDetails['Employee_Name'],
                    'dtrRecords' => $dtrecords,
                    'holidays' => $holidays,
                    'print_view' => false,
                    'halfsched' => $isHalfSchedule,
                ]));

                $dompdf->setPaper('Letter', 'portrait');
                $dompdf->render();
                $monthName = date('F', strtotime($yearof . '-' . sprintf('%02d', $monthof) . '-1'));
                $filename = $empDetails['DTRFile_Name'] . ' (DTR ' . $monthName . '-' . $yearof . ').pdf';

                /* Downloads as PDF */
                $dompdf->stream($filename);
            }
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    /* ----------------------------------------------------------------END OF GENERATION OF DAILY TIME RECORDS----------------------------------------------------------------------------------------------------------------------------- */


    public function Get_Holidays()
    {
        try {
            return response()->json(['data' => holiday_list::all()]);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function Set_Holidays(Request $request)
    {
        try {
            $description = $request->description;
            $month = $request->month;
            $day = $request->day;
            $isspecial = $request->isspecial;
            $effectiveDate = $request->effectiveDate;

            holiday_list::create([
                'description' => $description,
                'month_day' => $month . '-' . $day,
                'isspecial' => $isspecial,
                'effectiveDate' => $effectiveDate,
            ]);
            return response()->json(['message' =>  "Holiday Set Successfully!"]);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage()]);
        }
    }

    public function Modify_Holidays(Request $request)
    {
        try {
            $holiday_id = $request->holiday_id;
            $description = $request->description;
            $month = $request->month;
            $day = $request->day;
            $isspecial = $request->isspecial;
            $effectiveDate = $request->effectiveDate;

            holiday_list::where('id', $holiday_id)->update([
                'description' => $description,
                'month_day' => $month . '-' . $day,
                'isspecial' => $isspecial,
                'effectiveDate' => $effectiveDate,
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
        $holiday_list = holiday_list::where('effectiveDate', date('Y-m-d', strtotime($date)))->get();
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
    public function DTR_UTOT_Report(Request $request)
    {
        try {
            $biometric_id = $request->biometric_id;
            $monthof = $request->monthof;
            $yearof = $request->yearof;
            $is15thdays = $request->is15thdays;
            $dtrecords = [];
            $isHalfSchedule = false;
            if ($is15thdays) {
                $firsthalf = $request->firsthalf;
                $secondhalf = $request->secondhalf;
                if ($firsthalf) {
                    $dtrecords = $this->GenerateFirstHalf($monthof, $yearof, $biometric_id);
                } else {
                    if ($secondhalf) {
                        $dtrecords = $this->GenerateSecondHalf($monthof, $yearof, $biometric_id);
                    }
                }
            } else {
                $dtrecords = $this->GenerateMonthly($monthof, $yearof, $biometric_id);
            }
            $mdtr = [];
            $noScheddtr = [];
            $numberOfDays = 0;
            $numberOfallDayspast = 0;
            $dateranges = [];
            foreach ($dtrecords as $key => $value) {
                $schedule = $this->helper->Get_Schedule($value->biometric_id, $value->first_in);
                $isHalfSchedule = $this->isHalfEntrySchedule($schedule);

                // if ($isHalfSchedule) {
                //     $f1 = $value->first_in;
                //     $f2 = $value->first_out;
                //     return $f1 . $f2;
                // }


                if (isset($schedule['date_start']) && isset($schedule['date_end'])) {
                    $datenow = date('Y-m-d');
                    $datestart =  $schedule['date_start'];
                    $dateend =  $schedule['date_end'];
                    $dateRange = array();
                    $currentDate = strtotime($datestart);
                    $endDate = strtotime($dateend);

                    while ($currentDate <= $endDate) {
                        $dateRange[] = date('Y-m-d', $currentDate);
                        $currentDate = strtotime('+1 day', $currentDate);
                    }
                    $dateranges = $dateRange;
                    $numberOfDays = $this->getDifferenceDate($datestart, $dateend) + 1;
                    if ($dateend < $datenow) {
                        $numberOfallDayspast = $this->getDifferenceDate($datestart, $dateend) + 1;
                    }
                    $entry = '';
                    if (isset($value->first_in)) {
                        $entry = $value->first_in;
                    }
                    if (isset($value->second_in)) {
                        $entry = $value->second_in;
                    }

                    if ($entry >= $datestart && $entry <= $dateend) {
                        $mdtr[] = $this->MDTR($value);
                    }
                } else {
                    $noScheddtr[] = $this->MDTR($value);
                }
            }
            $RecordswithOvertime = array_values(array_filter($mdtr, function ($res) {
                return $res['overtime_minutes'] >= 1;
            }));
            $RecordswithUndertime = array_values(array_filter($mdtr, function ($res) {
                return $res['undertime_minutes'] >= 1;
            }));
            $TimeRecords = array_values(array_filter($mdtr, function ($res) {
                return $res['total_working_minutes'] >= 1;
            }));
            $overtimeSum = 0;
            foreach ($RecordswithOvertime as $record) {
                $overtimeSum += $record['overtime_minutes'];
            }
            $undertimeSum = 0;
            foreach ($RecordswithUndertime as $record) {
                $undertimeSum += $record['undertime_minutes'];
            }
            $totalHoursofDuty = 0;
            foreach ($TimeRecords as $record) {
                $totalHoursofDuty += floor($record['total_working_minutes'] - $record['undertime_minutes']);
            }
            $totalHoursofDuty = floor($totalHoursofDuty / 60);
            $totalminutesofDuty = floor($totalHoursofDuty * 60);
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthof, $yearof);
            $daysofduty = 0;
            $daysRendered = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $count = array_filter($mdtr, function ($res) use ($i) {
                    return date('d', strtotime($res['first_in'])) == $i;
                });
                $daysofduty += count($count);
                $daysRendered[] = $count;
            }
            $days = array_filter($daysRendered);
            $presentdays = [];
            foreach ($days as $entry) {
                if (is_array($entry)) {
                    foreach ($entry as $nestedEntry) {
                        $presentdays[] = $nestedEntry;
                    }
                }
            }

            $daysabsences = [];
            $dayspresent = [];
            foreach ($dateranges as $key => $value) {
                $dayEntries = date('j', strtotime($value));
                $count = array_filter($presentdays, function ($res) use ($dayEntries) {
                    return $res['day'] == $dayEntries;
                });

                if (count($count) == 0) {
                    if (date('Y-m-d') > date('Y-m-d', strtotime($value))) {
                        $daysabsences[] = $value;
                    }
                } else {
                    $dayspresent[] = $value;
                }
            }

            $numeric_days = array_map(function ($res) {
                $timestamp = strtotime($res);
                $formatted_date = date('Y-m-d', $timestamp);
                $numerical_value = date('j', $timestamp);
                return $numerical_value + 1;
            }, $dayspresent);

            /**
             * IF Two schedules only
             */
            $absences = floor($numberOfallDayspast - $daysofduty);
            if ($isHalfSchedule) {
                $newDaysAbsences = [];
                foreach ($daysabsences as $value) {
                    $dayEntries = date('j', strtotime($value));
                    $cnt = array_filter($numeric_days, function ($res) use ($dayEntries) {
                        return $res == $dayEntries;
                    });
                    if (count($cnt) == 0) {
                        if (date('Y-m-d') > date('Y-m-d', strtotime($value))) {
                            $newDaysAbsences[] = $value;
                        }
                    }
                }
                $daysabsences = $newDaysAbsences;
                $absences = count($daysabsences);
            }


            $dtr = [
                'biometric_ID' => $biometric_id,
                'employeeName' => 'Reenjay Caimor',
                'Total_Undertime' =>  $undertimeSum,
                'Total_Overtime' =>   $overtimeSum,
                'TotalHoursofDuty' => $totalHoursofDuty,
                'TotalMinutesofDuty' => $totalminutesofDuty,
                'TotalScheduleDays' => $numberOfDays,
                'TotalDaysRendered' => $daysofduty,
                'TotalAbsences' => $absences >= 1 ? $absences : 0,
                'TotalDayswLate' => count($RecordswithUndertime),
                'TotalDutywNosched' => count($noScheddtr),
                'fortheMonth' => date('F', strtotime($yearof . '-' . $monthof . '-1')),
                'fortheYear' => $yearof,
                'Absences' => $daysabsences,
                'AllRecords' => $mdtr,
                'RecordsWithOvertime' => $RecordswithOvertime,
                'RecordsWithUndertime' => $RecordswithUndertime,
                'NoschedDTR' => $noScheddtr
            ];

            return $dtr;
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Invalid Month']);
        }
    }


    private function GenerateMonthly($monthof, $yearof, $biometric_id)
    {

        return  DB::table('daily_time_records')
            ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
            ->where(function ($query) use ($biometric_id, $monthof, $yearof) {
                $query->where('biometric_id', $biometric_id)
                    ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $monthof)
                    ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $yearof);
            })
            ->orWhere(function ($query) use ($biometric_id, $monthof, $yearof) {
                $query->where('biometric_id', $biometric_id)
                    ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $monthof)
                    ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $yearof);
            })
            ->get();
    }

    private function GenerateFirstHalf($monthof, $yearof, $biometric_id)
    {
        $startDate = date('Y-m-d', strtotime($yearof . '-' . $monthof . '-1'));
        $endDate = date('Y-m-d', strtotime($yearof . '-' . $monthof . '-15'));
        $dtrecords = DB::table('daily_time_records')
            ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
            ->where('biometric_id',  $biometric_id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('first_in', '>=', $startDate)
                    ->where('first_in', '<=', $endDate);
            })
            ->orWhere(function ($query) use ($startDate, $endDate) {
                $query->where('second_in', '>=', $startDate)
                    ->where('second_in', '<=', $endDate);
            })
            ->get();
        return $dtrecords;
    }

    private function GenerateSecondHalf($monthof, $yearof, $biometric_id)
    {
        $startDate = date('Y-m-d', strtotime($yearof . '-' . $monthof . '-16'));
        $endDate = date('Y-m-d', strtotime($yearof . '-' . $monthof . '-31'));
        $dtrecords = DB::table('daily_time_records')
            ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
            ->where('biometric_id', $biometric_id)
            ->where(function ($query) use ($startDate, $endDate, $monthof, $yearof) {
                $query->whereMonth('first_in', '=', $monthof)
                    ->whereYear('first_in', '=', $yearof)
                    ->where('first_in', '>=', $startDate)
                    ->where('first_in', '<=', $endDate);
            })
            ->orWhere(function ($query) use ($startDate, $endDate,  $monthof, $yearof) {
                $query->whereMonth('second_in', '=', $monthof)
                    ->whereYear('second_in', '=', $yearof)
                    ->where('second_in', '>=', $startDate)
                    ->where('second_in', '<=', $endDate);
            })
            ->get();
        return $dtrecords;
    }

    private function getDifferenceDate($datestart, $dateend)
    {
        $startTimestamp = strtotime($datestart);
        $endTimestamp = strtotime($dateend);
        $secondsDifference = $endTimestamp - $startTimestamp;
        $numberOfDays = floor($secondsDifference / (60 * 60 * 24));
        return $numberOfDays;
    }

    private function MDTR($value)
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
            'day' => $value->day,
            'created_at' => $value->created_at,
            'weekStatus' => $this->getWeekdayStatus($value->created_at),
            'isHoliday' => $this->isHoliday($value->created_at)
        ];
    }

    public function test()
    {
        /* 
        **
        * test on how to access request function on another controller for instance
        */
        $request = new Request([
            'biometric_id' => 5180,
            'monthof' => 10,
            'yearof' => 2023,
            'is15thdays' => 0,
        ]);
        return $this->DTR_UTOT_Report($request);
    }
}
