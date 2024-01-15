<?php

namespace App\Observers;

use App\Models\News;
use Illuminate\Support\Facades\Cache;

class NewsObserver
{
    private $CACHE_PATH = 'news';

    /**
     * Handle the News "created" event.
     */
    public function created(News $news): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the News "updated" event.
     */
    public function updated(News $news): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the News "deleted" event.
     */
    public function deleted(News $news): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the News "restored" event.
     */
    public function restored(News $news): void
    {
        //
    }

    /**
     * Handle the News "force deleted" event.
     */
    public function forceDeleted(News $news): void
    {
        //
    }
}
