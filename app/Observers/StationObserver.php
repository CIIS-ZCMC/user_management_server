<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Station;

class StationObserver
{
    private $CACHE_KEY = 'stations';

    /**
     * Handle the Station "created" event.
     */
    public function created(Station $station): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Station "updated" event.
     */
    public function updated(Station $station): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Station "deleted" event.
     */
    public function deleted(Station $station): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Station "restored" event.
     */
    public function restored(Station $station): void
    {
        //
    }

    /**
     * Handle the Station "force deleted" event.
     */
    public function forceDeleted(Station $station): void
    {
        //
    }
}
