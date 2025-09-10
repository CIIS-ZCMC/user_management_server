<?php

namespace App\Console\Commands;

use App\Models\CTOCreditEarnLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CTOExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:c-t-o-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expired_cto = CTOCreditEarnLog::whereDate('expiration', Carbon::today())->get();

        foreach($expired_cto as $cto){
            $employee_leave_credit = $cto->credit;
            $employee_leave_credit->update(['total_leave_credits' => $employee_leave_credit->total_leave_credits - $cto->credit]);
        }
    }
}
