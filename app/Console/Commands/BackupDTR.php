<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Methods\Helpers;

class BackupDTR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-d-t-r';
       
    protected $backitup;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(Helpers $helpers)
    {
        parent::__construct();
        $this->backitup = $helpers;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->backitup->backUpTable('daily_time_records');
        $this->backitup->backUpTable('daily_time_record_logs');
    }
}
