<?php

namespace App\Observers;

use App\Models\DailyTimeRecords;
use Illuminate\Support\Facades\Cache;

class DailyTimeRecordObserver
{

    /**
     * Handle the DailyTimeRecord "created" event.
     */
    public function created(DailyTimeRecords $dailyTimeRecord): void
    {
        //
    }

    /**
     * Handle the DailyTimeRecords "updated" event.
     */
    public function updated(DailyTimeRecords $dailyTimeRecord): void
    {
        //
    }

    public function saved($model)
    {
        // Assuming similar cache key generation logic is used
        $cacheKey = "absences_by_period_*"; // Use a wildcard to clear relevant cache keys
        Cache::forget($cacheKey);
    }

    public function deleted($model)
    {
        $cacheKey = "absences_by_period_*"; // Use a wildcard to clear relevant cache keys
        Cache::forget($cacheKey);
    }

    /**
     * Handle the DailyTimeRecords "restored" event.
     */
    public function restored(DailyTimeRecords $dailyTimeRecord): void
    {
        //
    }

    /**
     * Handle the DailyTimeRecords "force deleted" event.
     */
    public function forceDeleted(DailyTimeRecords $dailyTimeRecord): void
    {
        //
    }
}
