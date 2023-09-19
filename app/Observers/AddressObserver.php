<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Address;

class AddressObserver
{
    private $CACHE_KEY = 'addresses';

    /**
     * Handle the Address "created" event.
     */
    public function created(Address $address): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Address "updated" event.
     */
    public function updated(Address $address): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Address "deleted" event.
     */
    public function deleted(Address $address): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Address "restored" event.
     */
    public function restored(Address $address): void
    {
        //
    }

    /**
     * Handle the Address "force deleted" event.
     */
    public function forceDeleted(Address $address): void
    {
        //
    }
}
