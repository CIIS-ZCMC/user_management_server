<?php

namespace App\Observers;

use App\Models\Announcements;
use Illuminate\Support\Facades\Cache;

class AnnouncementsObserver
{
    private $CACHE_PATH = "announcements";

    /**
     * Handle the Announcements "created" event.
     */
    public function created(Announcements $announcements): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the Announcements "updated" event.
     */
    public function updated(Announcements $announcements): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the Announcements "deleted" event.
     */
    public function deleted(Announcements $announcements): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the Announcements "restored" event.
     */
    public function restored(Announcements $announcements): void
    {
        //
    }

    /**
     * Handle the Announcements "force deleted" event.
     */
    public function forceDeleted(Announcements $announcements): void
    {
        //
    }
}
