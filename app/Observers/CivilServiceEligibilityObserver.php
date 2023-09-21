<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\CivilServiceEligibility;

class CivilServiceEligibilityObserver
{
    private $CACHE_KEY = 'civil_service_eligibilities';

    /**
     * Handle the CivilServiceEligibility "created" event.
     */
    public function created(CivilServiceEligibility $civilServiceEligibility): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the CivilServiceEligibility "updated" event.
     */
    public function updated(CivilServiceEligibility $civilServiceEligibility): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the CivilServiceEligibility "deleted" event.
     */
    public function deleted(CivilServiceEligibility $civilServiceEligibility): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the CivilServiceEligibility "restored" event.
     */
    public function restored(CivilServiceEligibility $civilServiceEligibility): void
    {
        //
    }

    /**
     * Handle the CivilServiceEligibility "force deleted" event.
     */
    public function forceDeleted(CivilServiceEligibility $civilServiceEligibility): void
    {
        //
    }
}
