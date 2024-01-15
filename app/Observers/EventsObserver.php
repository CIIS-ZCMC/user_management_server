<?php

namespace App\Observers;

use App\Models\Events;
use Illuminate\Support\Facades\Cache;

class EventsObserver
{
    private $CACHE_PATH = 'news';
    /**
     * Handle the Events "created" event.
     */
    public function created(Events $events): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the Events "updated" event.
     */
    public function updated(Events $events): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the Events "deleted" event.
     */
    public function deleted(Events $events): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the Events "restored" event.
     */
    public function restored(Events $events): void
    {
        //
    }

    /**
     * Handle the Events "force deleted" event.
     */
    public function forceDeleted(Events $events): void
    {
        //
    }
}
