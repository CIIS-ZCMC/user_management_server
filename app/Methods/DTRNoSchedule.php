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

class DTRNoSchedule
{
    protected $helper;
    public function __construct()
    {
        $this->helper = new Helpers();
    }
    /**
     * This Method Saves the DTR with no Schedule detected
     *
     *
     */
    public function New($entrydate, $entry, $biometric_id, $bioEntry, $status)
    {
        /**
         * Feautures **
         * Save First Entry
         * Also handles the OUT entry for nursing..
         */
        //Check yesterday Record
        $yester_date = date('Y-m-d', strtotime($entrydate . '-1 day'));
        $check_yesterday_Records = DailyTimeRecords::whereDate('first_in', $yester_date)->where('biometric_id', $biometric_id)->latest()->first();
        if ($check_yesterday_Records !== null) {


            $f_1 = $check_yesterday_Records->first_in;
            $f_2 = $check_yesterday_Records->first_out;
            if ($f_1 && !$f_2) {
                //CHECKOUT
                /**
                 * Here we validate OUT for nursing or Doctor with out entry
                 *
                 */
                $yesterday_Entry = [
                    'first_in' => $f_1,
                    'date_time' => $f_1
                ];

                /* Get the yesterdaySchedule */
                $Schedule = $this->helper->CurrentSchedule($biometric_id, $yesterday_Entry, false);
                $Schedule = $Schedule['daySchedule'];
                $Outexpiration = date('Y-m-d H:i:s', strtotime($entrydate . ' ' . $Schedule['second_entry'] . '+4 hours'));
                $isValidEntry = True; // By default all entry in NoSchedule is Valid
                if (count($Schedule) >= 1) {
                    if ($entry > $Outexpiration) {
                        /**
                         * If this entry has schedule
                         *  we are Checking if entry is within the alloted time requirement
                         *if not then we set to false .
                         * so that only logs will be saved.
                         */
                        $isValidEntry = false;
                    }
                }
                if ($isValidEntry) {
                    if ($status == 255) { //Global State
                        if ($this->helper->withinInterval($f_1, $this->helper->sequence(0, [$bioEntry]))) {
                            $this->helper->saveTotalWorkingHours(
                                $check_yesterday_Records,
                                $bioEntry,
                                $this->helper->sequence(0, [$bioEntry]),
                                [],
                                false
                            );
                        }
                    }
                    if ($status == 1) { // Check out State
                        //employeeID
                        $this->helper->saveTotalWorkingHours(
                            $check_yesterday_Records,
                            $bioEntry,
                            $this->helper->sequence(0, [$bioEntry]),
                            [],
                            false
                        );
                    }
                }
            } else {
                //CHECKIN
                /**
                 * Save New
                 */
                if ($status == 0 || $status == 255) { // Both Global and Check in State
                    $this->helper->SaveFirstEntry(
                        $this->helper->sequence(0, [$bioEntry]),
                        [],
                        $biometric_id,
                        false,
                        []
                    );
                }
            }
        } else {
            //CHECKIN
            /**
             * Save New
             */
            if ($status == 0 || $status == 255) { // Both Global and Check in State
                $this->helper->SaveFirstEntry(
                    $this->helper->sequence(0, [$bioEntry]),
                    [],
                    $biometric_id,
                    false,
                    []
                );
            }
        }
    }

    public function Update($validate, $biometric_id, $entry, $data, $status)
    {
        /* Updating All existing  Records */

        $f1 = $validate->first_in;
        $f2 =  $validate->first_out;
        $f3 = $validate->second_in;
        $f4 = $validate->second_out;
        if ($f1 && !$f2 && !$f3 && !$f4) {

            if ($status == 255) {

                if ($this->helper->withinInterval($f1, $this->helper->sequence(0, [$data]))) {
                    $this->helper->saveTotalWorkingHours(
                        $validate,
                        $data,
                        $this->helper->sequence(0, [$data]),
                        [],
                        false
                    );
                }
            }
            if ($status == 1) {
                $this->helper->saveTotalWorkingHours(
                    $validate,
                    $data,
                    $this->helper->sequence(0, [$data]),
                    [],
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
        /* check In_am and out_am and  in_pm and not set out_pm */
        /*
           We have set the last entry,
           assuming that the first, second, and third entries have also been established.
           Overtime and undertime, as well as working hours, have already been calculated.
        */
        if ($f1 && $f2 && $f3 && !$f4) {
            if ($status == 255) {
                if ($this->helper->withinInterval($f3, $this->helper->sequence(0, [$data]))) {
                    $this->helper->saveTotalWorkingHours(
                        $validate,
                        $data,
                        $this->helper->sequence(0, [$data]),
                        [],
                        false
                    );
                }
            }
            if ($status == 1) {
                $this->helper->saveTotalWorkingHours(
                    $validate,
                    $data,
                    $this->helper->sequence(0, [$data]),
                    [],
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
            if ($status == 255) {
                if ($this->helper->withinInterval($f3, $this->helper->sequence(0, [$data]))) {
                    $this->helper->saveTotalWorkingHours(
                        $validate,
                        $data,
                        $this->helper->sequence(0, [$data]),
                        [],
                        false
                    );
                }
            }
            if ($status == 1) {
                $this->helper->saveTotalWorkingHours(
                    $validate,
                    $data,
                    $this->helper->sequence(0, [$data]),
                    [],
                    false
                );
            }
        }
    }
}
