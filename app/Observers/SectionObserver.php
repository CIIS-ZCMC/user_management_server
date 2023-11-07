<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Section;

class SectionObserver
{
    private $CACHE_KEY = 'sections';

    /**
     * Handle the Section "created" event.
     */
    public function created(Section $section): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Section "updated" event.
     */
    public function updated(Section $section): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Section "deleted" event.
     */
    public function deleted(Section $section): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Section "restored" event.
     */
    public function restored(Section $section): void
    {
        //
    }

    /**
     * Handle the Section "force deleted" event.
     */
    public function forceDeleted(Section $section): void
    {
        //
    }
}
