<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\PasswordTrail;

class PasswordTrailObserver
{
    private $CACHE_KEY = 'password_trails';

    /**
     * Handle the PasswordTrail "created" event.
     */
    public function created(PasswordTrail $passwordTrail): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the PasswordTrail "updated" event.
     */
    public function updated(PasswordTrail $passwordTrail): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the PasswordTrail "deleted" event.
     */
    public function deleted(PasswordTrail $passwordTrail): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the PasswordTrail "restored" event.
     */
    public function restored(PasswordTrail $passwordTrail): void
    {
        //
    }

    /**
     * Handle the PasswordTrail "force deleted" event.
     */
    public function forceDeleted(PasswordTrail $passwordTrail): void
    {
        //
    }
}
