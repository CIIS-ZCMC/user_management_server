<?php

namespace App\Helpers;

use App\Models\SystemLogs;
use App\Models\Permission;
use App\Models\TimeShift;

use DateTime;
use DateInterval;
use DatePeriod;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class Helpers {

    public static function registerSystemLogs($request, $moduleID, $status, $remarks)
    {
        $ip = $request->ip();
        $user = $request->user;
        $permission = $request->permission;
        $action = Permission::where('name', $permission)->first();
        // list($action, $module) = explode(' ', $permission);

        SystemLogs::create([
            'employee_profile_id' => $user,
            'module_id' => $moduleID,
            'action' => $action,
            'status' => $status,
            'remarks' => $remarks,
            'ip_address' => $ip
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
        $start  = new DateTime("{$year}-{$month}-01");
        $end    = new DateTime("{$year}-{$month}-" . $start->format('t'));

        $interval   = new DateInterval('P1D');
        $period     = new DatePeriod($start, $interval, $end->modify('+1 day'));

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

                default:
                    $dates[] = $date->format('Y-m-d D');
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
            $fileName = Hash::make(time()) . '.' . $file->getClientOriginalExtension();

            $file->move(public_path($FILE_URL), $fileName);
        }
        
        return $fileName;
    }

    public static function infoLog($controller, $module, $message)
    {
        Log::channel('custom-info')->info($controller.' Controller ['.$module.']: message: '.$message);
    }
    
    public static function errorLog($controller, $module, $errorMessage)
    {
        Log::channel('custom-error')->error($controller.' Controller ['.$module.']: message: '.$errorMessage);
    }
}