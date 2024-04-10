<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Models\TaskSchedules;
use Illuminate\Console\Command;

class TaskScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:task-scheduler';

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
        $currentDate = now()->toDateString();
        $today_tasks = TaskSchedules::whereDate('effective_at', $currentDate)->get();

        if(count($today_tasks) > 0){
            foreach($today_tasks as $task){
                if($task->task_run === false){
                    if($task->start()){
                        $task->update(['task_run' => true]);
                    }
                }else{
                    if($task->end()){
                        $task->update(['task_complete' => true]);
                    }
                }
            }
        }else{
            Helpers::infoLog("TaskScheduler", "hanlde", "NO TASK AVAILABLE");
        }
    }
}
