<?php

namespace App\Helpers;

use App\Models\SystemLogs;
use App\Models\Permission;

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

}