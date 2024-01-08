<?php

namespace App\Observers;

use App\Models\FreedomWallMessages;
use Illuminate\Support\Facades\Cache;

class FreedomWallMessagesObserver
{
    private $CACHE_KEY = 'freedom-wall-messages';

    /**
     * Handle the FreedomWallMessages "created" event.
     */
    public function created(FreedomWallMessages $freedomWallMessages): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the FreedomWallMessages "updated" event.
     */
    public function updated(FreedomWallMessages $freedomWallMessages): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the FreedomWallMessages "deleted" event.
     */
    public function deleted(FreedomWallMessages $freedomWallMessages): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the FreedomWallMessages "restored" event.
     */
    public function restored(FreedomWallMessages $freedomWallMessages): void
    {
        //
    }

    /**
     * Handle the FreedomWallMessages "force deleted" event.
     */
    public function forceDeleted(FreedomWallMessages $freedomWallMessages): void
    {
        //
    }
}
