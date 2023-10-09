<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\SpecialAccessRole;

class SpecialAccessRoleObserver
{
    private $CACHE_KEY = 'special_access_roles';

    /**
     * Handle the SpecialAccessRole "created" event.
     */
    public function created(SpecialAccessRole $specialAccessRole): void
    {
        Cache::forget($CACHE_KEY);
    }

    /**
     * Handle the SpecialAccessRole "updated" event.
     */
    public function updated(SpecialAccessRole $specialAccessRole): void
    {   
        Cache::forget($CACHE_KEY);
    }

    /**
     * Handle the SpecialAccessRole "deleted" event.
     */
    public function deleted(SpecialAccessRole $specialAccessRole): void
    {
        Cache::forget($CACHE_KEY);
    }
}