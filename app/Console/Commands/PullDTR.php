<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DTR\DTRcontroller;
use Illuminate\Support\Facades\Storage;

class PullDTR extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pull-d-t-r';
    protected $dtrController;
    /**
     * The console command description.
     *
     * @var string
     */

    public function __construct(DTRcontroller $dtrController)
    {
        parent::__construct();
        $this->dtrController = $dtrController;
    }

    protected $description = 'Pulling DTR from device';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dtrController->fetchDTRFromDevice();
        $logFilePath = storage_path('logs/daily_time_record.log');
        /**
         * Whenever log file reaches 5 MB, it will clear the log file.
         */
        if (file_exists($logFilePath)) {
            $fileSize = filesize($logFilePath);
            $fileSizeMB = $fileSize / (1024 * 1024);
            if ($fileSizeMB > 5) { // checking if file reaches 5mb
                $fileHandle = fopen($logFilePath, 'w');
                if ($fileHandle !== false) {
                    ftruncate($fileHandle, 0);
                    fclose($fileHandle);
                    Log::channel("custom-dtr-log")->info("Log file Cleared");
                } else {
                    Log::channel("custom-dtr-log")->error("Error opening file: $logFilePath");
                }
            }
        }
        Log::channel("custom-dtr-log")->info('DTR pulled from all device successfully.');
    }
}
