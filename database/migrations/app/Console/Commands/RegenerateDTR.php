<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DTR\DTRcontroller;

class RegenerateDTR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:regenerate-d-t-r';
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

    protected $description = 'Regenerating DTR';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dtrController->RegenerateDTR();
    }
}
