<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\WorkExperience;

class WorkExperienceObserver
{
    private $CACHE_KEY = 'work_experiences';

    /**
     * Handle the WorkExperience "created" event.
     */
    public function created(WorkExperience $workExperience): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the WorkExperience "updated" event.
     */
    public function updated(WorkExperience $workExperience): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the WorkExperience "deleted" event.
     */
    public function deleted(WorkExperience $workExperience): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the WorkExperience "restored" event.
     */
    public function restored(WorkExperience $workExperience): void
    {
        //
    }

    /**
     * Handle the WorkExperience "force deleted" event.
     */
    public function forceDeleted(WorkExperience $workExperience): void
    {
        //
    }
}
