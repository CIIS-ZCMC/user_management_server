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
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
class AttendanceController extends Controller
{
    protected $device;
    protected $helper;
    protected $CONTROLLER_NAME = "AttendanceController";
    protected $dtr;
    public function __construct() {
        $this->device = new BioControl();
        $this->helper = new Helpers();
        $this->dtr = new DTRcontroller();
    }


    public function fetchAttendanceList(Request $request)
    {
        try {
            $data = Cache::remember('attendance_list', now()->addMinutes(5), function () {
                return Attendance::with('logs')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
            });

            if($request->search && $request->search != ""){
                $data = Attendance::with('logs')
                    ->where('title', 'like', "%{$request->search}%")
                    ->orWhereHas('logs', function ($q) use ($request) {
                        $q->where('first_entry', 'like', "%{$request->search}%");
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();

            }

            return response()->json([
                'message' => "List retrieved successfully",
                'data' => $data
            ]);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'fetchAttendanceList', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function fetchAttendanceRequest(Request $request){
        try {

            $dateRequest = $request->requestDate;
            $user = $request->user;
            $biometric_id = $user->biometric_id;
            $attendance = Attendance::whereHas('logs', function($query) use ($biometric_id, $dateRequest) {
                $query->where("biometric_id", $biometric_id)
                ->whereDate("first_entry", $dateRequest);
            })->with('logs', function($query) use ($biometric_id){
                $query->where("biometric_id", $biometric_id);
            })->get();

            return response()->json([
                'message'=>"list retrieved successfully",
                'data' =>  $attendance
            ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'fetchAttendanceRequest', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function fetchAttendanceDataFromDevice(Request $request){
        try {
            $dateRequest = $request->dateRequest;
            $device_id = $request->device_id;
            $title = $request->title;
            $device = Devices::find($device_id);
            if (!$device) {
                return response()->json(['message' => 'Device not found'], Response::HTTP_NOT_FOUND);
            }
            $attendanceController = new AttendanceController();
            $data =  $attendanceController->fetchAttendance(new Request([
                'dateRequest' => $dateRequest,
                'ip_address' => $device->ip_address,
                'title' => $title
            ]));

            $data = json_decode($data->getContent(),true)['data'];
            return response()->json([
                "message"=>"list retrieved successfully",
                'data'=>$data,

            ]);

        //
        //     if (!$device) {
        //         return response()->json(['message' => 'Device not found'], Response::HTTP_NOT_FOUND);
        //     }

        //    return $device;
           // return $this->attendanceController
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'fetchAttendanceDataFromDevice', $th->getMessage());
            return response()->json(['message' =>  $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function fetchAttendance(Request $request)
    {
    $devices = Devices::whereNotNull("for_attendance")->where("for_attendance", ">=", 1)
    ->where("ip_address",$request->ip_address)
    ->get();
    $title = $request->title ?? "";
    set_time_limit(0);
    ini_set('max_execution_time', 10600);


    $dateRequest = date("Y-m-d");


    if(isset($request->dateRequest)){
        $dateRequest = date("Y-m-d",strtotime($request->dateRequest));
    }

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
              $attendanceLogs = array_values(array_filter($attendanceLogs,function($row) use($dateRequest) {
                        return date("Y-m-d",strtotime($row['date_time'])) == $dateRequest;
                }));
                foreach ($attendanceLogs as $employee) {
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

                       $employeeLogs = array_values(array_filter($attendanceLogs, function($log) use ($dateRequest, $biometricId) {
                                      return $log['biometric_id'] == $biometricId  && date("Y-m-d", strtotime($log['date_time'])) == $dateRequest;
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
            return $e;
            \Log::error("Device {$device->ip_address} failed: " . $e->getMessage());
            continue;
        }
    }


     $data = array_values($mergedAttendance);
     usort($data, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    $this->SavetoDb($data);
    if(!count($data)) {
        return response()->json([
            'message'=>"No data found on request date : ".$dateRequest,
            'data'=>[]
        ],204);
    }

    return response()->json([
        'data'=>$data,
        'message'=>"Data found on request date : ".$dateRequest
    ]);
//    return $this->GenerateToExcel($data, $title);
}


    public function SavetoDb($data){
    foreach ($data as $row) {

                $title_key = $row['title'];
                $attendance = Attendance::firstOrCreate(["title"=>$title_key]);
                $row['attendances_id'] = $attendance->id;
                $attendanceInfo = Attendance_Information::where('biometric_id', $row['biometric_id'])
                ->whereDate('first_entry', date('Y-m-d', strtotime($row['first_entry'])))
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


    public function exportToexcel($id){
        try {
            $attendance = Attendance::find($id);
            $logs = $attendance->logs;
            $excluded = [
                'attendances_id',
                'biometric_id',
                'id',
                'created_at',
                'updated_at'
            ];
            $logs->map(function($log) use ($excluded){
                foreach ($excluded as $key) {
                    unset($log->$key);
                }
                return $log;
            });

            $logs = $attendance->logs->sortBy(function($log) {
                return strtolower($log->name);
            });

            return $this->GenerateToExcel($logs->toArray(),$attendance->title);
        } catch (\Throwable $th) {
            return $th->getMessage();
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
