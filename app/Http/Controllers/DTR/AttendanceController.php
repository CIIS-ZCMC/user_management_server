<?php

namespace App\Http\Controllers\DTR;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Methods\BioControl;
use App\Models\Devices;
use App\Methods\Helpers;
use App\Models\Biometrics;
use App\Http\Controllers\DTR\DTRcontroller;
use App\Models\Attendance;
use App\Models\Attendance_Information;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceController extends Controller
{
    protected $device;
    protected $helper;

    protected $dtr;
    public function __construct() {
        $this->device = new BioControl();
        $this->helper = new Helpers();
        $this->dtr = new DTRcontroller();
    }
    public function fetchAttendance(Request $request){
        $devices =  Devices::whereNotNull("for_attendance")->where("for_attendance",">=",1)->get();
        $title = $request->title ?? "";

        $AttendanceData = [];
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
                    $nameEntries = [];
                foreach ($filtered as $att) {
                    $profileName = $profile->name();

                    if (!isset($nameEntries[$profileName])) {
                        $nameEntries[$profileName] = [];
                    }
                    $nameEntries[$profileName][] = $att;
                }
                foreach ($nameEntries as $name => $entries) {  
                    usort($entries, function ($a, $b) {
                        return strtotime($a['date_time']) <=> strtotime($b['date_time']);
                    });   
                    $first = $entries[0];
                    $last = end($entries);
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
                $attendance = [];
                
           foreach ($UserAttendance as $row) {
                $title_key = $row['title']."-".date("Ymd",strtotime($row['date_time']));
                $row['first_entry'] = $row['date_time'];
                unset($row['title'],$row['date_time']);
                $attendance = Attendance::firstOrCreate(["title"=>$title_key]);
                $row['attendances_id'] = $attendance->id;
                $attendanceInfo = Attendance_Information::where('biometric_id', $row['biometric_id'])
                ->whereDate('created_at', date('Y-m-d', strtotime($row['first_entry'])))
                ->where("attendances_id",$attendance->id)
                ->first();
                  if ($attendanceInfo) {
                    $row['last_entry'] = $row['first_entry'];
                    $row = array_intersect_key($row, array_flip(['last_entry']));
                    if(!$attendanceInfo->last_entry){
                        $attendanceInfo->update($row);
                    }
                    } else {
                        $attendanceInfo = Attendance_Information::create($row);
                    }             
            }

            if($request->clear_device){
                $tad->delete_data(['value'=>3]);
            }
            $AttendanceData[] = $this->GenerateData($attendance); 
            }else {
                // return response()->json([
                //     "message"=>"offline"
                // ]);
            }
        }

        $attd_data =  array_merge(...$AttendanceData) ;

        return $this->GenerateToExcel($attd_data,$title);
    }

    public function GenerateData($attendance){
        $title = substr($attendance->title, 0, strrpos($attendance->title, '-'));
        
      return  $data = array_map(function($row){
            return [
                "Name"=>$row['name'],
                "Area"=>$row['area'],
                "Area-Code"=>$row['areacode'],
                "Sector"=>$row['sector'],
                "First_entry"=>$row['first_entry'],
                "Last_entry"=>$row['last_entry']
            ];
        },$attendance->logs->toArray()) ;
    
    }

    public function GenerateToExcel($data,$title){
            $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = array_keys($data[0]);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        $row = 2;
        foreach ($data as $record) {
            $col = 'A';
            foreach ($record as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }
        $writer = new Xlsx($spreadsheet);
        $filename = $title . '_attendance.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
