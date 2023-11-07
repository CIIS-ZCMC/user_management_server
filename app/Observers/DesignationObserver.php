<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Designation;

class DesignationObserver
{
    private $CACHE_KEY = 'designations';

    /**
     * Handle the Designation "created" event.
     */
    public function created(Designation $jobPosition): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Designation "updated" event.
     */
    public function updated(Designation $jobPosition): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Designation "deleted" event.
     */
    public function deleted(Designation $jobPosition): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Designation "restored" event.
     */
    public function restored(Designation $jobPosition): void
    {
        //
    }

    /**
     * Handle the Designation "force deleted" event.
     */
    public function forceDeleted(Designation $jobPosition): void
    {
        //
    }
}
