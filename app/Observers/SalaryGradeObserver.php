<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\SalaryGrade;

class SalaryGradeObserver
{
    private $CACHE_KEY = 'salary_grades';

    /**
     * Handle the SalaryGrade "created" event.
     */
    public function created(SalaryGrade $salaryGrade): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the SalaryGrade "updated" event.
     */
    public function updated(SalaryGrade $salaryGrade): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the SalaryGrade "deleted" event.
     */
    public function deleted(SalaryGrade $salaryGrade): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the SalaryGrade "restored" event.
     */
    public function restored(SalaryGrade $salaryGrade): void
    {
        //
    }

    /**
     * Handle the SalaryGrade "force deleted" event.
     */
    public function forceDeleted(SalaryGrade $salaryGrade): void
    {
        //
    }
}
