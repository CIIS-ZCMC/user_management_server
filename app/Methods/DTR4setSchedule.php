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

class DTR4setSchedule
{

    protected $helper;
    public function __construct()
    {
        $this->helper = new Helpers();
    }

    public function New($DaySchedule, $BreakTime, $entrydate, $entry, $biometric_id, $data, $status)
    {
       
        /**
         * Here we are checking if theres an existing first entry this is  for nursing and doctors
         * which has two entries for schedule only.
         * if data not found. then we save into first entry
         */
        $yester_date = date('Y-m-d', strtotime($entrydate . '-1 day'));
       
        $check_yesterday_Records = DailyTimeRecords::whereDate('first_in', $yester_date)->where('biometric_id', $biometric_id)->latest()->first();
    
        if ($check_yesterday_Records !== null) {

            $f_1 = $check_yesterday_Records->first_in;
            $f_2 = $check_yesterday_Records->first_out;
            $f_3 = $check_yesterday_Records->second_in;
            $f_4 = $check_yesterday_Records->second_out;

          
            if ($f_1 && !$f_2 && !$f_3 && !$f_4 ) {

                /* Validation add expiry. */
                $TimeAllowance_ =  date('Y-m-d H:i:s', strtotime($entrydate . ' ' . $DaySchedule['second_entry'] . " +5 hours")); // 5 hours allowance
                /* Update */

                if ($DaySchedule['second_entry'] !== null) {

                    if ($TimeAllowance_ > $entry) { // Validation to Ignore Yesterday entry. 5 hours

                        if ($status == 255) {


                            if ($this->helper->withinInterval($f_1, $this->helper->sequence(0, [$data]))) {
                                $this->helper->saveTotalWorkingHours(
                                    $check_yesterday_Records,
                                    $data,
                                    $this->helper->sequence(0, [$data]),
                                    $DaySchedule,
                                    false
                                );
                            }
                        }
                        if ($status == 1) {
                            //employeeID
                            $this->helper->SaveTotalWorkingHours(
                                $check_yesterday_Records,
                                $data,
                                $this->helper->sequence(0, [$data]),
                                $DaySchedule,
                                false
                            );
                        }
                    } else {

                        //No DTR saved caused it does not comply the time required
                    }
                } else {
                    /////Save DTR as it does not have sched ,
                    /**
                     * As long as yesterday records does not have timeout at 2nd entry. it will fill the second entry..
                     * Soon add validation here to handle if employee is  nurse or admin
                     */
                    if ($this->helper->EntryisAm($this->helper->sequence(0, [$data])[0]['date_time'])) {
                        if ($status == 255) {
                            if ($this->helper->withinInterval($f_1, $this->helper->sequence(0, [$data]))) {
                                $this->helper->saveTotalWorkingHours(
                                    $check_yesterday_Records,
                                    $data,
                                    $this->helper->sequence(0, [$data]),
                                    $DaySchedule,
                                    false
                                );
                            }
                        }
                        if ($status == 1) {
                            //employeeID
                            $this->helper->SaveTotalWorkingHours(
                                $check_yesterday_Records,
                                $data,
                                $this->helper->sequence(0, [$data]),
                                $DaySchedule,
                                false
                            );
                        }
                    }
                }
                //////////////////////////////////////////////////////////////////////////////////

            } else {
                /* Save */
                if ($this->helper->EntryisAm($this->helper->sequence(0, [$data])[0]['date_time'])) {
                    if ($status == 0 || $status == 255) {
                    
                        $this->helper->SaveFirstEntry(
                            $this->helper->sequence(0, [$data])[0],
                            $BreakTime,
                            $biometric_id,
                            false,
                            $DaySchedule,
                            'AM'
                        );
                    }
                }


                if ($this->helper->EntryisPm($this->helper->sequence(0, [$data])[0]['date_time'])) {

                    if ($status == 0 || $status == 255) {
                      
                        $this->helper->SaveFirstEntry(
                            $this->helper->sequence(0, [$data])[0],
                            $BreakTime,
                            $biometric_id,
                            false,
                            $DaySchedule,
                            'PM'
                        );
                    }
                }
            }
        } else {
           
           
            if ($this->helper->EntryisAm($this->helper->sequence(0, [$data])[0]['date_time'])) {
                if ($status == 0 || $status == 255) {
                  
                    $this->helper->SaveFirstEntry(
                        $this->helper->sequence(0, [$data])[0],
                        $BreakTime,
                        $biometric_id,
                        false,
                        $DaySchedule,
                        'AM'
                    );
                }
            }


            if ($this->helper->EntryisPm($this->helper->sequence(0, [$data])[0]['date_time'])) {
             
                if ($status == 0 || $status == 255) {
                    $scheduleEntry = null;


                    $this->helper->SaveFirstEntry(
                        $this->helper->sequence(0, [$data])[0],
                        $BreakTime,
                        $biometric_id,
                        false,
                        $DaySchedule,
                        'PM'
                    );
                }

            }
        }
    }


    public function Update($validate, $DaySchedule, $BreakTime, $entrydate, $entry, $biometric_id, $data, $status)
    {

      
        $f1 = $validate->first_in;
        $f2 =  $validate->first_out;
        $f3 = $validate->second_in;
        $f4 = $validate->second_out;
        $rwm = $validate->required_working_minutes;
        $o_all_min = $validate->total_working_minutes;
        if ($f1 && !$f2 && !$f3 && !$f4) {
            
            if ($status == 255) {
          
                if ($this->helper->withinInterval($f1, $this->helper->sequence(0, [$data]))) {
                    $this->helper->saveTotalWorkingHours(
                        $validate,
                        $data,
                        $this->helper->sequence(0, [$data]),
                        $DaySchedule,
                        false
                    );
                }
                
            }
         
            if ($status == 1) {
                $this->helper->saveTotalWorkingHours(
                    $validate,
                    $data,
                    $this->helper->sequence(0, [$data]),
                    $DaySchedule,
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
            if ($this->helper->EntryisPm($this->helper->sequence(0, [$data])[0]['date_time'])) {
                $percent_Trendered = floor($rwm * 0.6);

                if ($o_all_min <= $percent_Trendered) { // if allmins rendered is less than the 60% time req . then accept a second entry

                    if ($status == 255) {
                        if ($this->helper->withinInterval($f2, $this->helper->sequence(0, [$data]))) {
                            $this->helper->saveIntervalValidation(
                                $this->helper->sequence(0, [$data]),
                                $validate
                            );
                        }
                    }
                    if ($status == 0) {
                        $this->helper->saveIntervalValidation(
                            $this->helper->sequence(0, [$data]),
                            $validate
                        );
                    }
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
           
            if ($this->helper->EntryisPm($this->helper->sequence(0, [$data])[0]['date_time'])) {
                if ($status == 255) {
                    if ($this->helper->withinInterval($f3, $this->helper->sequence(0, [$data]))) {
                        $this->helper->saveTotalWorkingHours(
                            $validate,
                            $data,
                            $this->helper->sequence(0, [$data]),
                            $DaySchedule,
                            false
                        );
                    }
                }
                if ($status == 1) {
                    $this->helper->saveTotalWorkingHours(
                        $validate,
                        $data,
                        $this->helper->sequence(0, [$data]),
                        $DaySchedule,
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
           
            if ($this->helper->EntryisPm($this->helper->sequence(0, [$data])[0]['date_time'])) {
                if ($status == 255) {
                    if ($this->helper->withinInterval($f3, $this->helper->sequence(0, [$data]))) {
                        $this->helper->saveTotalWorkingHours(
                            $validate,
                            $data,
                            $this->helper->sequence(0, [$data]),
                            $DaySchedule,
                            false
                        );
                    }
                }
                if ($status == 1) {
                    $this->helper->saveTotalWorkingHours(
                        $validate,
                        $data,
                        $this->helper->sequence(0, [$data]),
                        $DaySchedule,
                        false
                    );
                }
            }
        }
    }
}
