<?php

namespace App\Http\Controllers\DTR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\DTR\BioMSController;
use App\Methods\BioControl;
use App\Models\DailyTimeRecords;
use App\Models\DailyTimeRecordLogs;
use App\Models\EmployeeProfile;
use App\Methods\Helpers;
use App\Helpers\Helpers as Helpersv2;
use App\Http\Controllers\DTR\DTRcontroller;
class LogCheckerController extends Controller
{
    protected $helper;
    protected $device;
    protected $ip;
    protected $bioms;
    protected $devices;
    protected $dtr;
    public function __construct()
    {
        $this->helper = new Helpers();
        $this->device = new BioControl();
        $this->bioms = new BioMSController();
        $this->dtr = new DTRcontroller();

        try {
            $content = $this->bioms->operatingDevice()->getContent();
            $this->devices = $content !== null ? json_decode($content, true)['data'] : [];
        } catch (\Throwable $th) {

        }
    }
    public function index(){

        return view("Checklogs");
    }
    private  function array_flatten($array) {
        $result = [];
        foreach ($array as $element) {
            if (is_array($element)) {
                $result = array_merge($result, $element);
            } else {
                $result[] = $element;
            }
        }
        return $result;
    }
    public function getLogs(Request $request){
        $employee_id = $request->employee_ID;
        $datenow = date('Y-m-d');
        $ep = EmployeeProfile::where('employee_id',$employee_id)->first();
        if(!$ep){
            if (!$ep) {
                return redirect('/CheckLogs')->with('error', 'No employee or biometric records found.');
            }

        }
        $biometric_id = $ep->biometric_id;
        $name = $ep->personalInformation->name();

        $dtr = DailyTimeRecords::where('biometric_id',$biometric_id)->where('dtr_date',$datenow)->first();
        $dtrlogs = DailyTimeRecordLogs::where('biometric_id',$biometric_id)->where('dtr_date',$datenow)->first();
        $biologs = [];

        foreach ($this->devices as $device) {
            if ($tad = $this->device->bIO($device)) {
                $logs = $tad->get_att_log();
                $all_user_info = $tad->get_all_user_info();
                $attendance = simplexml_load_string($logs);
                $user_Inf = simplexml_load_string($all_user_info);
                $attendance_Logs =  $this->helper->getAttendance($attendance);

                if($this->dtr->isNotEmptyFields($attendance_Logs)){
                          $Employee_Info  = $this->helper->getEmployee($user_Inf);

                $Employee_Attendance = $this->helper->getEmployeeAttendance(
                    $attendance_Logs,
                    $Employee_Info
                );
                $check_Records = array_filter($Employee_Attendance, function ($attd) use ($datenow,$biometric_id) {
                    return date('Y-m-d', strtotime($attd['date_time'])) == $datenow && $attd['biometric_id'] ==  $biometric_id ;
                });


                $biologs[] = array_values($check_Records);
                }


            }
        }

        $biologs = $biologs ?? [];
        if(count($biologs)>=1){
            $biologs = $this->array_flatten($biologs);
 }

        return view('Retrievedlogs',compact('dtr','dtrlogs','biologs','name','employee_id'));

    }
}
