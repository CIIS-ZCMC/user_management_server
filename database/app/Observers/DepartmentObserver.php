<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Department;

class DepartmentObserver
{
    private $CACHE_KEY = 'departments';

    /**
     * Handle the Department "created" event.
     */
    public function created(Department $department): void
    {
        $code = $department->code;
        $sector = "DE";
        $total_departments = Department::count();

        $area_id = sprintf("%s-%s-%03d", $code, $sector, $total_departments);

        $department->update(['area_id' => $area_id]);
        
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Department "updated" event.
     */
    public function updated(Department $department): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Department "deleted" event.
     */
    public function deleted(Department $department): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Department "restored" event.
     */
    public function restored(Department $department): void
    {
        //
    }

    /**
     * Handle the Department "force deleted" event.
     */
    public function forceDeleted(Department $department): void
    {
        //
    }
}
