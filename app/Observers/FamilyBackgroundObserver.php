<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\FamilyBackground;

class FamilyBackgroundObserver
{
    private $CACHE_KEY = 'family_backgrounds';

    /**
     * Handle the FamilyBackground "created" event.
     */
    public function created(FamilyBackground $familyBackground): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the FamilyBackground "updated" event.
     */
    public function updated(FamilyBackground $familyBackground): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the FamilyBackground "deleted" event.
     */
    public function deleted(FamilyBackground $familyBackground): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the FamilyBackground "restored" event.
     */
    public function restored(FamilyBackground $familyBackground): void
    {
        //
    }

    /**
     * Handle the FamilyBackground "force deleted" event.
     */
    public function forceDeleted(FamilyBackground $familyBackground): void
    {
        //
    }
}
