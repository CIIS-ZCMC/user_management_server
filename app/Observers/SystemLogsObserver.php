<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\SystemLogs;

class SystemLogsObserver
{
    private $CACHE_KEY = 'system_logs';

    /**
     * Handle the SystemLogs "created" event.
     */
    public function created(SystemLogs $systemLogs): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the SystemLogs "updated" event.
     */
    public function updated(SystemLogs $systemLogs): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the SystemLogs "deleted" event.
     */
    public function deleted(SystemLogs $systemLogs): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the SystemLogs "restored" event.
     */
    public function restored(SystemLogs $systemLogs): void
    {
        //
    }

    /**
     * Handle the SystemLogs "force deleted" event.
     */
    public function forceDeleted(SystemLogs $systemLogs): void
    {
        //
    }
}
