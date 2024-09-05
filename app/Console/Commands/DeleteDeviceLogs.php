<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DTR\DeviceLogsController;

class DeleteDeviceLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-device-logs';
    protected $deviceLogs;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Database Device logs';
    public function __construct(DeviceLogsController $deviceLogs)
    {
        parent::__construct();
        $this->deviceLogs = $deviceLogs;

    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->deviceLogs->ClearDeviceLogs(null);
    }
}
