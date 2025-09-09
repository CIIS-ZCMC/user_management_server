<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\LegalInformation;

class LegalInformationObserver
{
    private $CACHE_KEY = 'legal_informations';

    /**
     * Handle the LegalInformation "created" event.
     */
    public function created(LegalInformation $legalInformation): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the LegalInformation "updated" event.
     */
    public function updated(LegalInformation $legalInformation): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the LegalInformation "deleted" event.
     */
    public function deleted(LegalInformation $legalInformation): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the LegalInformation "restored" event.
     */
    public function restored(LegalInformation $legalInformation): void
    {
        //
    }

    /**
     * Handle the LegalInformation "force deleted" event.
     */
    public function forceDeleted(LegalInformation $legalInformation): void
    {
        //
    }
}
