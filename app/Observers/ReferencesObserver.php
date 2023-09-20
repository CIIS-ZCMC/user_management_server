<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\References;

class ReferencesObserver
{
    private $CACHE_KEY = 'references';

    /**
     * Handle the References "created" event.
     */
    public function created(References $references): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the References "updated" event.
     */
    public function updated(References $references): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the References "deleted" event.
     */
    public function deleted(References $references): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the References "restored" event.
     */
    public function restored(References $references): void
    {
        //
    }

    /**
     * Handle the References "force deleted" event.
     */
    public function forceDeleted(References $references): void
    {
        //
    }
}
