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
        $cacheKeyAbsencesByPeriod = "absences_by_period_*"; // Use a wildcard to clear relevant cache keys
        $cacheKeyAbsencesByDateRange = "absences_by_date_range_*";
        $cacheKeyTardinessByPeriod = "tardiness_by_period_*";
        $cacheKeyTardinessByDateRange = "tardiness_by_date_range_*";
        $cacheKeyUndertimeByPeriod = "undertime_by_period_*";
        $cacheKeyUndertimeByDateRange = "undertime_by_date_range_*";
        Cache::forget($cacheKeyAbsencesByDateRange);
        Cache::forget($cacheKeyAbsencesByPeriod);
        Cache::forget($cacheKeyTardinessByPeriod);
        Cache::forget($cacheKeyTardinessByDateRange);
        Cache::forget($cacheKeyUndertimeByPeriod);
        Cache::forget($cacheKeyUndertimeByDateRange);
    }

    public function deleted($model)
    {
        $cacheKeyAbsencesByPeriod = "absences_by_period_*"; // Use a wildcard to clear relevant cache keys
        $cacheKeyAbsencesByDateRange = "absences_by_date_range_";
        $cacheKeyTardinessByPeriod = "tardiness_by_period_*";
        $cacheKeyTardinessByDateRange = "tardiness_by_date_range_*";
        $cacheKeyUndertimeByPeriod = "undertime_by_period_*";
        $cacheKeyUndertimeByDateRange = "undertime_by_date_range_*";
        Cache::forget($cacheKeyAbsencesByDateRange);
        Cache::forget($cacheKeyAbsencesByPeriod);
        Cache::forget($cacheKeyTardinessByPeriod);
        Cache::forget($cacheKeyTardinessByDateRange);
        Cache::forget($cacheKeyUndertimeByPeriod);
        Cache::forget($cacheKeyUndertimeByDateRange);
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
