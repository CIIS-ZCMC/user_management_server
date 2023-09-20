<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\OtherInformation;

class OtherInformationObserver
{
    private $CACHE_KEY = 'other_informations';

    /**
     * Handle the OtherInformation "created" event.
     */
    public function created(OtherInformation $otherInformation): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the OtherInformation "updated" event.
     */
    public function updated(OtherInformation $otherInformation): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the OtherInformation "deleted" event.
     */
    public function deleted(OtherInformation $otherInformation): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the OtherInformation "restored" event.
     */
    public function restored(OtherInformation $otherInformation): void
    {
        //
    }

    /**
     * Handle the OtherInformation "force deleted" event.
     */
    public function forceDeleted(OtherInformation $otherInformation): void
    {
        //
    }
}
