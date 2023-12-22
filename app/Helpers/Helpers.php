<?php

namespace App\Helpers;

use App\Models\SystemLogs;
use App\Models\Permission;
use App\Models\TimeShift;

use DateTime;
use DateInterval;
use DatePeriod;


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
    
}