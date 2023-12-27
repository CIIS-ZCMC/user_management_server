<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\ModulePermission;

class ModulePermissionObserver
{
    private $CACHE_KEY = 'legal_information_questions';
    /**
     * Handle the ModulePermission "created" event.
     */
    public function created(ModulePermission $modulePermission): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the ModulePermission "updated" event.
     */
    public function updated(ModulePermission $modulePermission): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the ModulePermission "deleted" event.
     */
    public function deleted(ModulePermission $modulePermission): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the ModulePermission "restored" event.
     */
    public function restored(ModulePermission $modulePermission): void
    {
        //
    }

    /**
     * Handle the ModulePermission "force deleted" event.
     */
    public function forceDeleted(ModulePermission $modulePermission): void
    {
        //
    }
}
