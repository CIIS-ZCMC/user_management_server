<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

use App\Models\System;

class SystemObserver
{
    private $CACHE_KEY = 'systems';

    /**
     * Handle the System "created" event.
     */
    public function created(System $system): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the System "updated" event.
     */
    public function updated(System $system): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the System "deleted" event.
     */
    public function deleted(System $system): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the System "restored" event.
     */
    public function restored(System $system): void
    {
        //
    }

    /**
     * Handle the System "force deleted" event.
     */
    public function forceDeleted(System $system): void
    {
        //
    }
}
