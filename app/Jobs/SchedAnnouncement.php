<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Models\Announcements;
use App\Models\EmployeeProfile;
use App\Models\Notifications;
use App\Models\UserNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SchedAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $announcements;
    /**
     * Create a new job instance.
     */
    public function __construct($announcements)
    {
        //
        $this->announcements = $announcements;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $this->announcements->posted = 1;
        $this->announcements->save();
        if($this->announcements){
            $notification = Notifications::create([
                'title'=>"Announcement!",
                'description'=>$this->announcements->title,
                'module_path'=>"/announcement/".$this->announcements->id,
            ]);
            $employeeId = EmployeeProfile::all();
            foreach($employeeId as $id){
                UserNotifications::create([
                    'seen'=>0,
                    'notification_id'=>$notification->id,
                    'employee_profile_id'=>$id->id
                ]);
            }
        }
        Helpers::infoLog("Announcement Posted", "Announcement",$this->announcements->title);
    }
}


//the view for supervisors only check if supervisors