<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DTR\DTRcontroller;

use App\Http\Controllers\PayrollHooks\GenerateReportController;

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
    protected $genPayroll;
    /**
     * The console command description.
     *
     * @var string
     */

    public function __construct(DTRcontroller $dtrController)
    {
        parent::__construct();
        $this->dtrController = $dtrController;
        $this->genPayroll = new GenerateReportController();
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

        //9am - 11am - 3pm - 7:30pm - 9pm - 12am - 3am - 5:30am vice versa
        $DeletionList = [
            '09:00',
            '11:00',
            '15:00',
            '19:30',
            '21:00',
            '00:00',
            '03:00',
            '05:30'
        ];
        
       
        $datenow = date("H:i");
        $GenerateEvry = date("i");
        if (in_array($datenow,$DeletionList)){
            //Pull first before clearing devices.
                if($this->dtrController->fetchDTRFromDevice()){
                     $this->dtrController->deleteDeviceLogs();
                     Log::channel("custom-dtr-log")->info('DEVICE SUCCESSFULLY CLEARED @ '.$datenow);
                  }
       
        }
        //Run every 10 minutes of every hour.
        if($GenerateEvry == "10"){
            Log::channel("custom-dtr-log")->info('PAYROLL LIST GENERATED '.$datenow);
            // $this->genPayroll->AsyncrounousRun_GenerateDataReport(new request([
            //     'month_of'=>3,
            //     'year_of'=>,
            //     'whole_month'=>,
            // ]));
        }


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
