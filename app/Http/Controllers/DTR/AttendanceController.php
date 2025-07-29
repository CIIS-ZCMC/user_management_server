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
    public function fetchAttendance(Request $request)
{
    $devices = Devices::whereNotNull("for_attendance")->where("for_attendance", ">=", 1)->get();
    $title = $request->title ?? "";
    set_time_limit(0); 
    
    $mergedAttendance = []; 

    foreach ($devices as $device) {
        try {
            // Connect to device
            if ($tad = $this->device->bIO($device)) {
                // Get raw attendance logs
                $logs = $tad->get_att_log();
                $attendance = simplexml_load_string($logs);
                
                if (!$attendance) {
                    \Log::error("Failed to parse XML from device: " . $device->ip_address);
                    continue;
                }

                $attendanceLogs = $this->helper->getAttendance($attendance);
                $userInfo = $this->device->getUserInformation($attendanceLogs, $tad);
                $employeeInfo = $this->helper->getEmployee($userInfo);

                foreach ($employeeInfo as $employee) {
                    $biometricId = $employee['biometric_id'];
                  
                 $user = Biometrics::join('employee_profiles', 'biometrics.biometric_id', '=', 'employee_profiles.biometric_id')
                                   ->where('biometrics.biometric_id', $biometricId)
                                   ->select('biometrics.*')
                                   ->first();
                    if (!$user) continue;

                  
                    $profile = $user->employeeProfile;
                    $area = $profile->assignedArea->details ?? null;
                    $details = null;
                                    $sector = null;
                                    $profile = $user->employeeProfile ?? null;
                                    $assignedArea = $profile?->assignedArea ?? null;
                                    $info = $assignedArea?->findDetails() ?? null;
                                    if($info){
                                      $details = $info['details']->toArray(request()) ?? "";
                                      $sector =  $info['sector'] ?? "";
                                                         
                                  }
                                  $datenow = date("Y-m-d");
                       $employeeLogs = array_values(array_filter($attendanceLogs, function($log) use ($datenow, $biometricId) {
                                      return $log['biometric_id'] == $biometricId  && date("Y-m-d", strtotime($log['date_time'])) == $datenow;
                          }));        
                          if(!$employeeLogs){continue;}
                                  
                    usort($employeeLogs, fn($a, $b) => 
                        strtotime(datetime: $a['date_time']) <=> strtotime($b['date_time'])
                    );     
                    $dateKey = date('Y-m-d', strtotime($employeeLogs[0]['date_time']));
                    $mergeKey = "{$biometricId}_{$dateKey}";

                    $firstEntry = $employeeLogs[0]['date_time'];
                    $lastEntry = end($employeeLogs)['date_time'];

                    if (!isset($mergedAttendance[$mergeKey])) {
                        $mergedAttendance[$mergeKey] = [
                            'name' => $profile->name(),
                            'biometric_id' => $biometricId,
                            "area" => isset($details) ? $details['name'] : '',
                             "areacode" => isset($details) ? $details['code'] : '',
                            'sector' => $sector ?? '',
                            'first_entry' => $firstEntry,
                            'last_entry' => $lastEntry,
                            'title' => $title,
                            'deviceIP' => $device->ip_address
                        ];
                    } else { 
                        $mergedAttendance[$mergeKey]['first_entry'] = min(
                            $mergedAttendance[$mergeKey]['first_entry'],
                            $firstEntry
                        );
                        $mergedAttendance[$mergeKey]['last_entry'] = max(
                            $mergedAttendance[$mergeKey]['last_entry'],
                            $lastEntry
                        );
                    }
                }

           
                if ($request->clear_device == 1) {
                    $tad->delete_data(['value' => 3]);
                }
            }
        } catch (\Exception $e) {
            \Log::error("Device {$device->ip_address} failed: " . $e->getMessage());
            continue;
        }
    }


     $data = array_values($mergedAttendance);
       usort($data, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
     $this->SavetoDb($data);
   
   return $this->GenerateToExcel($data, $title);
}
  
    
    public function SavetoDb($data){
    foreach ($data as $row) {
      
                $title_key = $row['title'];
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
