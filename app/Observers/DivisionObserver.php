<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Division;

class DivisionObserver
{
    private $CACHE_KEY = 'divisions';

    /**
     * Handle the Division "created" event.
     */
    public function created(Division $division): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Division "updated" event.
     */
    public function updated(Division $division): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Division "deleted" event.
     */
    public function deleted(Division $division): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Division "restored" event.
     */
    public function restored(Division $division): void
    {
        //
    }

    /**
     * Handle the Division "force deleted" event.
     */
    public function forceDeleted(Division $division): void
    {
        //
    }
}
