<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Unit;

class UnitObserver
{
    private $CACHE_KEY = 'units';

    /**
     * Handle the Unit "created" event.
     */
    public function created(Unit $unit): void
    {
        $code = $unit->code;
        $sector = "U";
        $total_units = Unit::count();

        $area_id = sprintf("%s-%s-%03d", $code, $sector, $total_units);

        $unit->update(['area_id' => $area_id]);
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Unit "updated" event.
     */
    public function updated(Unit $unit): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Unit "deleted" event.
     */
    public function deleted(Unit $unit): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Unit "restored" event.
     */
    public function restored(Unit $unit): void
    {
        //
    }

    /**
     * Handle the Unit "force deleted" event.
     */
    public function forceDeleted(Unit $unit): void
    {
        //
    }
}
