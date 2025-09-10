<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\EmployeeProfile;

class EmployeeProfileObserver
{
    private $CACHE_KEY = 'employee_profiles';

    /**
     * Handle the EmployeeProfile "created" event.
     */
    public function created(EmployeeProfile $employeeProfile): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the EmployeeProfile "updated" event.
     */
    public function updated(EmployeeProfile $employeeProfile): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the EmployeeProfile "deleted" event.
     */
    public function deleted(EmployeeProfile $employeeProfile): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the EmployeeProfile "restored" event.
     */
    public function restored(EmployeeProfile $employeeProfile): void
    {
        //
    }

    /**
     * Handle the EmployeeProfile "force deleted" event.
     */
    public function forceDeleted(EmployeeProfile $employeeProfile): void
    {
        //
    }
}
