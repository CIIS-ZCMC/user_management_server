<?php

namespace App\Helpers;

use App\Models\SystemLogs;
use App\Models\Permission;
use App\Models\TimeShift;
use Str;


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


}