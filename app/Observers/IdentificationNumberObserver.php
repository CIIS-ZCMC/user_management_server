<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\IdentificationNumber;

class IdentificationNumberObserver
{
    private $CACHE_KEY = "identifications";
    /**
     * Handle the Identification "created" event.
     */
    public function created(IdentificationNumber $identification): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Identification "updated" event.
     */
    public function updated(IdentificationNumber $identification): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Identification "deleted" event.
     */
    public function deleted(IdentificationNumber $identification): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Identification "restored" event.
     */
    public function restored(IdentificationNumber $identification): void
    {
        //
    }

    /**
     * Handle the Identification "force deleted" event.
     */
    public function forceDeleted(IdentificationNumber $identification): void
    {
        //
    }
}
