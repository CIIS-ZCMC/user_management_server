<?php

namespace App\Helpers;

use App\Models\Department;
use App\Models\Division;
use App\Models\ExchangeDutyLog;
use App\Models\PullOut;
use App\Models\PullOutLog;
use App\Models\Section;
use App\Models\SystemLogs;
use App\Models\TimeShift;

use App\Models\Unit;
use DateTime;
use DateInterval;
use DatePeriod;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class Helpers
{
    public static function registerSystemLogs($request, $moduleID, $status, $remarks)
    {
        $ip = $request->ip();
        $user = $request->user;
        $permission = $request->permission;
        list($module, $action) = explode(' ', $permission);

        SystemLogs::create([
            'employee_profile_id' => $user->id,
            'module_id' => $moduleID,
            'action' => $action,
            'module' => $module,
            'status' => $status,
            'remarks' => $remarks,
            'ip_address' => $ip
        ]);
    }
    public static function registerExchangeDutyLogs($data_id, $user_id, $action)
    {
        ExchangeDutyLog::create([
            'exchange_duty_id' => $data_id,
            'action_by' => $user_id,
            'action' => $action
        ]);
    }

    public static function registerPullOutLogs($data_id, $user_id, $action)
    {
        PullOutLog::create([
            'pull_out_id' => $data_id,
            'action_by' => $user_id,
            'action' => $action
        ]);
    }

    public static function randomHexColor()
    {
        // Generate a random RGB color
        $red = mt_rand(0, 255);
        $green = mt_rand(0, 255);
        $blue = mt_rand(0, 255);

        // Convert RGB to hex
        $hexColor = sprintf("#%02x%02x%02x", $red, $green, $blue);

        $query = TimeShift::where('color', $hexColor)->exists();

        if (!$query) {
            return $hexColor;
        }
    }

    public static function getDatesInMonth($year, $month, $value)
    {
        $start = new DateTime("{$year}-{$month}-01");
        $end = new DateTime("{$year}-{$month}-" . $start->format('t'));

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

        $dates = [];

        foreach ($period as $date) {
            switch ($value) {
                case 'Day':
                    $dates[] = $date->format('d');
                    break;

                case 'Week':
                    $dates[] = $date->format('D');
                    break;

                case 'Month':
                    $dates[] = $date->format('m');
                    break;

                case 'Year':
                    $dates[] = $date->format('Y');
                    break;

                case 'Days of Week':
                    $dates[] = $date->format('Y-m-d D');
                    break;

                default:
                    $dates[] = $date->format('Y-m-d');
                    break;
            }
        }

        return $dates;
    }

    public static function checkSaveFile($attachment, $FILE_URL)
    {
        $fileName = '';

        if ($attachment->isValid()) {
            $file = $attachment;
            $filePath = $file->getRealPath();

            $finfo = new \finfo(FILEINFO_MIME);
            $mime = $finfo->file($filePath);
            $mime = explode(';', $mime)[0];

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

            if (!in_array($mime, $allowedMimeTypes)) {
                return response()->json(['message' => 'Invalid file type'], Response::HTTP_BAD_REQUEST);
            }

            // Check for potential malicious content
            $fileContent = file_get_contents($filePath);

            if (preg_match('/<\s*script|eval|javascript|vbscript|onload|onerror/i', $fileContent)) {
                return response()->json(['message' => 'File contains potential malicious content'], Response::HTTP_BAD_REQUEST);
            }

            $file = $attachment;
            
            $fileName = base64_encode(Hash::make(time())) . '.' . $file->getClientOriginalExtension();

            $file->move(public_path($FILE_URL), $fileName);
        }

        return $fileName;
    }

    public static function infoLog($controller, $module, $message)
    {
        Log::channel('custom-info')->info($controller . ' Controller [' . $module . ']: message: ' . $message);
    }

    public static function errorLog($controller, $module, $errorMessage)
    {
        Log::channel('custom-error')->error($controller . ' Controller [' . $module . ']: message: ' . $errorMessage);
    }

    public static function ExchangeDutyApproval($assigned_area, $employee_profile_id)
    {
        switch ($assigned_area['sector']) {
            case 'Division':
                $division_head = Division::find($assigned_area['details']['id'])->chief_employee_profile_id;
                return ["approve_by" => $division_head];

            case 'Department':
                $department_head = Department::find($assigned_area['details']['id'])->head_employee_profile_id;
                return ["approve_by" => $department_head];

            case 'Section':
                $section = Section::find($assigned_area['details']['id']);
                if ($section->division !== null) {
                    return ["approve_by" => $section->supervisor_employee_profile_id];
                }

                $department = $section->department;
                return ["approve_by" => $department->head_employee_profile_id];

            case 'Unit':
                $section = Unit::find($assigned_area['details']['id'])->section;
                if ($section->department_id !== null) {
                    $department = $section->department;
                    return ["approve_by" => $department->head_employee_profile_id];
                }

                return ["approve_by" => $section->supervisor_employee_profile_id];

            default:
                return null;
        }
    }
}