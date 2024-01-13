<?php

namespace App\Observers;

use App\Models\Memorandums;
use Illuminate\Support\Facades\Cache;

class MemorandumsObserver
{
    private $CACHE_PATH = 'news';
    /**
     * Handle the Memorandums "created" event.
     */
    public function created(Memorandums $memorandums): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the Memorandums "updated" event.
     */
    public function updated(Memorandums $memorandums): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the Memorandums "deleted" event.
     */
    public function deleted(Memorandums $memorandums): void
    {
        Cache::forget($this->CACHE_PATH);
    }

    /**
     * Handle the Memorandums "restored" event.
     */
    public function restored(Memorandums $memorandums): void
    {
        //
    }

    /**
     * Handle the Memorandums "force deleted" event.
     */
    public function forceDeleted(Memorandums $memorandums): void
    {
        //
    }
}
