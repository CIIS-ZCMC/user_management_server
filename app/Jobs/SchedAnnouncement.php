<?php

namespace App\Jobs;

use App\Helpers\Helpers;
use App\Models\Announcements;
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
        Helpers::infoLog("Announcement Posted", "Announcement",$this->announcements->title);
    }
}
