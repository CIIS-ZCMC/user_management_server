<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Methods\BioControl;
use App\Models\Devices;
use App\Methods\Helpers;
use App\Models\Biometrics;
class AttendanceController extends Controller
{
    protected $device;
    protected $helper;
    public function __construct() {
        $this->device = new BioControl();
        $this->helper = new Helpers();
    }
    public function fetchAttendance(Request $request){
        $devices =  Devices::whereNotNull("for_attendance")->where("for_attendance",">=",1)->get();
        
        $title = $request->title ?? "";
        foreach ($devices as $device) {
            if ($tad = $this->device->bIO($device)) { 
                $logs = $tad->get_att_log();
                $all_user_info = $tad->get_all_user_info();
                $attendance = simplexml_load_string($logs);
                $user_Inf = simplexml_load_string($all_user_info);
                $attendance_Logs = $this->helper->getAttendance($attendance);
                $Employee_Info = $this->helper->getEmployee($user_Inf);
                $UserAttendance = [];
                foreach ($Employee_Info as $emp) {
                    $biometric_id = $emp['biometric_id'];
                    $user = Biometrics::join('employee_profiles', 'biometrics.biometric_id', '=', 'employee_profiles.biometric_id')
                   ->where('biometrics.biometric_id', $biometric_id)
                   ->select('biometrics.*')
                   ->first();
                   if($user){
                    $details = null;
                    $sector = null;
                    $profile = $user->employeeProfile ?? null;
                    $assignedArea = $profile?->assignedArea ?? null;
                    $info = $assignedArea?->findDetails() ?? null;
                    if($info){
                        $details = $info['details']->toArray(request()) ?? "";
                        $sector =  $info['sector'] ?? "";
                     
                    }
                    $filtered = array_filter($attendance_Logs, function ($row) use ($biometric_id) {
                        return $row['biometric_id'] == $biometric_id;
                    });
                    // foreach ($filtered as $att) {  
                    //     $UserAttendance[] = array_merge(
                    //         [...$emp],
                    //         ["area"=> isset($details) ? $details['name'] : '',
                    //         "areacode"=>isset($details)? $details['code'] :''
                    //         ],
                    //         ["name"=>$profile->name()],
                    //         ['sector' => $sector],
                    //         ['date_time' => $att['date_time']],
                    //         ['title'=>$title]
                    //     );
                        
                    // }

                    $nameEntries = [];

                // Group entries by profile name
                foreach ($filtered as $att) {
                    $profileName = $profile->name();

                    if (!isset($nameEntries[$profileName])) {
                        $nameEntries[$profileName] = [];
                    }
                    $nameEntries[$profileName][] = $att;
                }
                // For each name, sort by date_time and get first & last
                foreach ($nameEntries as $name => $entries) {
                    // Sort by 'date_time' ascending
                    usort($entries, function ($a, $b) {
                        return strtotime($a['date_time']) <=> strtotime($b['date_time']);
                    });

                    // Get first and last after sort
                    $first = $entries[0];
                    $last = end($entries);

                    // Prevent duplication if only one entry exists
                    $selected = [$first];
                    if ($first !== $last) {
                        $selected[] = $last;
                    }

                    foreach ($selected as $att) {
                        $UserAttendance[] = array_merge(
                            [...$emp],
                            [
                                "area" => isset($details) ? $details['name'] : '',
                                "areacode" => isset($details) ? $details['code'] : ''
                            ],
                            ["name" => $name],
                            ['sector' => $sector],
                            ['date_time' => $att['date_time']],
                            ['title' => $title]
                        );
                    }
                }

                   }
                }        
              
                return $UserAttendance;
            }else {
                return response()->json([
                    "message"=>"offline"
                ]);
            }
        

          
        }
    }
}
