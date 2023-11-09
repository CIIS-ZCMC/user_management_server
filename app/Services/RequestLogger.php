<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RequestLogger {

    public function infoLog($controller, $module, $message)
    {
        Log::channel('custom-info')->info($controller.' Controller ['.$module.']: message: '.$message);
    }
    
    public function errorLog($controller, $module, $errorMessage)
    {
        Log::channel('custom-error')->error($controller.' Controller ['.$module.']: message: '.$errorMessage);
    }
}


