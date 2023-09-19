<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\Contact;

class ContactObserver
{
    private $CACHE_KEY = 'contacts';

    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $contact): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Contact "updated" event.
     */
    public function updated(Contact $contact): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Contact "deleted" event.
     */
    public function deleted(Contact $contact): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the Contact "restored" event.
     */
    public function restored(Contact $contact): void
    {
        //
    }

    /**
     * Handle the Contact "force deleted" event.
     */
    public function forceDeleted(Contact $contact): void
    {
        //
    }
}
