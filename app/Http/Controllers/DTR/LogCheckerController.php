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
class LogCheckerController extends Controller
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
       
        try {
            $content = $this->bioms->operatingDevice()->getContent();
            $this->devices = $content !== null ? json_decode($content, true)['data'] : [];
        } catch (\Throwable $th) {
            
        }
    }
    public function index(){
       
        return view("Checklogs");
    }
    public function getLogs(Request $request){
        $employee_id = $request->employee_ID;
        $datenow = date('Y-m-d');
        $ep = EmployeeProfile::where('employee_id',$employee_id)->first();
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
                $Employee_Info  = $this->helper->getEmployee($user_Inf);

                $Employee_Attendance = $this->helper->getEmployeeAttendance(
                    $attendance_Logs,
                    $Employee_Info
                );
                $check_Records = array_filter($Employee_Attendance, function ($attd) use ($datenow,$biometric_id) {
                    return date('Y-m-d', strtotime($attd['date_time'])) == $datenow and $attd['biometric_id'] ==  $biometric_id ;
                });  
                
                $biologs = $check_Records;
            }
        }
        $biologs = array_values($biologs);
        
  
        // return [
        //     'dtr'=>$dtr,
        //     'dtrlogs'=>$dtrlogs,
        //     'biologs'=>$biologs

        // ];

        return view('Retrievedlogs',compact('dtr','dtrlogs','biologs','name','employee_id'));

    }
}
