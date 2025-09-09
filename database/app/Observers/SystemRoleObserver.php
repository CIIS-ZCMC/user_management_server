<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\SystemRole;

class SystemRoleObserver
{
    private $CACHE_KEY = 'system_roles';
    /**
     * Handle the SystemRole "created" event.
     */
    public function created(SystemRole $systemRole): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the SystemRole "updated" event.
     */
    public function updated(SystemRole $systemRole): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the SystemRole "deleted" event.
     */
    public function deleted(SystemRole $systemRole): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the SystemRole "restored" event.
     */
    public function restored(SystemRole $systemRole): void
    {
        //
    }

    /**
     * Handle the SystemRole "force deleted" event.
     */
    public function forceDeleted(SystemRole $systemRole): void
    {
        //
    }
}
