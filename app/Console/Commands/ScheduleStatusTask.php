<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ScheduleStatusTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:schedule-status-task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the status of schedule';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $date_now = Carbon::now();
        $data = Schedule::whereDate('date', '<', $date_now->format('Y-m-d'))->get();

        if (!$data->isEmpty()) { // Check if the collection is not empty
            foreach ($data as $schedule) {
                $schedule->status = false;
                $schedule->save(); // or $schedule->update(['status' => false]);
            }
        }
    }
}
