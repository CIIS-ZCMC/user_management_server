<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DTR\DTRcontroller;

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
        Log::channel("custom-dtr-log")->info('DTR pulled from all device successfully.');
    }
}
