<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DTR\DTRcontroller;
class FetchForRegistration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-for-registration';

    /**
     * The console command description.
     *
     * @var string
     */

     protected $dtrController;

     public function __construct() {
        parent::__construct();
        $this->dtrController = new DTRcontroller();
     }
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dtrController->throwAllNonReg($this);
        $this->info("Data fetched successfully");
    }
}
