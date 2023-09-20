<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Training;

class TrainingObserver
{
    private $CACHE_KEY = 'trainings';

    /**
     * Handle the Training "created" event.
     */
    public function created(Training $training): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Training "updated" event.
     */
    public function updated(Training $training): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Training "deleted" event.
     */
    public function deleted(Training $training): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Training "restored" event.
     */
    public function restored(Training $training): void
    {
        //
    }

    /**
     * Handle the Training "force deleted" event.
     */
    public function forceDeleted(Training $training): void
    {
        //
    }
}
