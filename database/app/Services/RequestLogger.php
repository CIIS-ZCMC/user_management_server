<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

use App\Models\SystemLogs;

class RequestLogger {

    public function infoLog($controller, $module, $message)
    {
        Log::channel('custom-info')->info($controller.' Controller ['.$module.']: message: '.$message);
    }
    
    public function errorLog($controller, $module, $errorMessage)
    {
        Log::channel('custom-error')->error($controller.' Controller ['.$module.']: message: '.$errorMessage);
    }

    public function registerSystemLogs($request, $moduleID, $status, $remarks)
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
}


