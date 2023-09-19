<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\JobPosition;

class JobPositionObserver
{
    private $CACHE_KEY = 'job_positions';

    /**
     * Handle the JobPosition "created" event.
     */
    public function created(JobPosition $jobPosition): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the JobPosition "updated" event.
     */
    public function updated(JobPosition $jobPosition): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the JobPosition "deleted" event.
     */
    public function deleted(JobPosition $jobPosition): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the JobPosition "restored" event.
     */
    public function restored(JobPosition $jobPosition): void
    {
        //
    }

    /**
     * Handle the JobPosition "force deleted" event.
     */
    public function forceDeleted(JobPosition $jobPosition): void
    {
        //
    }
}
