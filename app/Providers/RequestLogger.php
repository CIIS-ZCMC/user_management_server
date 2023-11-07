<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class RequestLogger extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

    }

    protected function infoLog($controller, $module, $message)
    {
        Log::channel('custom-info')->info($controller.' Controller ['.$module.']: message: '.$message);
    }

    protected function errorLog($controller, $module, $errorMessage)
    {
        Log::channel('custom-error')->error($controller.' Controller ['.$module.']: message: '.$errorMessage);
    }
}
