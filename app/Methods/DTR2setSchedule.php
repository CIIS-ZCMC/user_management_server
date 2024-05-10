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

class DTR2setSchedule
{

    protected $helper;
    public function __construct()
    {
        $this->helper = new Helpers();
    }

    public function New($DaySchedule, $entrydate, $entry, $biometric_id, $data, $status)
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

            /* this entry only */
            if ($f_1 && !$f_2) {

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
                   // if ($this->helper->EntryisAm($this->helper->sequence(0, [$data])[0]['date_time'])) {
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
                     //   }
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
                //  if ($this->helper->EntryisAm($this->helper->sequence(0, [$data])[0]['date_time'])) {
                if ($status == 0 || $status == 255) {
                    $this->helper->SaveFirstEntry(
                        $this->helper->sequence(0, [$data])[0],
                        [],
                        $biometric_id,
                        false,
                        $DaySchedule,
                        'AM'
                    );
                }
                //  }


            }
        } else {

            //   if ($this->helper->EntryisAm($this->helper->sequence(0, [$data])[0]['date_time'])) {
            if ($status == 0 || $status == 255) {
                $this->helper->SaveFirstEntry(
                    $this->helper->sequence(0, [$data])[0],
                    [],
                    $biometric_id,
                    false,
                    $DaySchedule,
                    'AM'
                );
            }
            //   }


        }
    }


    public function Update($validate, $DaySchedule,  $entrydate, $entry, $biometric_id, $data, $status)
    {




        $f1 = $validate->first_in;
        $f2 =  $validate->first_out;
        $f3 = $validate->second_in;
        $f4 = $validate->second_out;
        $rwm = $validate->required_working_minutes;
        $o_all_min = $validate->total_working_minutes;

        // echo "f1:" . $f1 . "\n";
        // echo "f2:" . $f2 . "\n";
        // echo "f3:" . $f3 . "\n";
        // echo "f4:" . $f4 . "\n";

        // return;
        if ($f1 && !$f2) {

            $Outexpiration = date('Y-m-d H:i:s', strtotime($entrydate . ' ' . $DaySchedule['second_entry'] . '+4 hours'));
            $isValidEntry = True; // By default all entry in NoSchedule is Valid
            if ($entry > $Outexpiration) {
                /**
                 * If this entry has schedule
                 *  we are Checking if entry is within the alloted time requirement
                 *if not then we set to false .
                 * so that only logs will be saved.
                 */
                $isValidEntry = false;
            }
            /* Add validation for timeout... add expiration function */
            if ($isValidEntry) {
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
        } else {

            //if ($this->helper->EntryisAm($this->helper->sequence(0, [$data])[0]['date_time'])) {
            if ($status == 0 || $status == 255) {
                $this->helper->SaveFirstEntry(
                    $this->helper->sequence(0, [$data])[0],
                    [],
                    $biometric_id,
                    false,
                    $DaySchedule,
                    'AM'
                );
            }

        }
    }
}
