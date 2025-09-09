<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Plantilla;

class PlantillaObserver
{
    private $CACHE_KEY = 'plantillas';

    /**
     * Handle the Plantilla "created" event.
     */
    public function created(Plantilla $plantilla): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Plantilla "updated" event.
     */
    public function updated(Plantilla $plantilla): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Plantilla "deleted" event.
     */
    public function deleted(Plantilla $plantilla): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Plantilla "restored" event.
     */
    public function restored(Plantilla $plantilla): void
    {
        //
    }

    /**
     * Handle the Plantilla "force deleted" event.
     */
    public function forceDeleted(Plantilla $plantilla): void
    {
        //
    }
}
