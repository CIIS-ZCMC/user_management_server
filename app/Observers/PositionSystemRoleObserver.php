<?php

namespace App\Observers;
use App\Models\PositionSystemRole;

class PositionSystemRoleObserver
{ 
    private $CACHE_KEY = 'position_system_roles';

    /**
     * Handle the Position System Role "created" event.
     */
    public function created(PositionSystemRole $passwordTrail): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Position System Role "updated" event.
     */
    public function updated(PositionSystemRole $passwordTrail): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Position System Role "deleted" event.
     */
    public function deleted(PositionSystemRole $passwordTrail): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Position System Role "restored" event.
     */
    public function restored(PositionSystemRole $passwordTrail): void
    {
        //
    }

    /**
     * Handle the Position System Role "force deleted" event.
     */
    public function forceDeleted(PositionSystemRole $passwordTrail): void
    {
        //
    }
}
