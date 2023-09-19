<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

use App\Models\PersonalInformation;

class PersonalInformationObserver
{
    private $CACHE_KEY = "personal_informations";

    /**
     * Handle the PersonalInformation "created" event.
     */
    public function created(PersonalInformation $personalInformation): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the PersonalInformation "updated" event.
     */
    public function updated(PersonalInformation $personalInformation): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the PersonalInformation "deleted" event.
     */
    public function deleted(PersonalInformation $personalInformation): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the PersonalInformation "restored" event.
     */
    public function restored(PersonalInformation $personalInformation): void
    {
        //
    }

    /**
     * Handle the PersonalInformation "force deleted" event.
     */
    public function forceDeleted(PersonalInformation $personalInformation): void
    {
        //
    }
}
