<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Models\LegalInformationQuestion;

class LegalInformationQuestionObserver
{
    private $CACHE_KEY = 'legal_information_questions';

    /**
     * Handle the LegalInformationQuestion "created" event.
     */
    public function created(LegalInformationQuestion $legalInformationQuestion): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the LegalInformationQuestion "updated" event.
     */
    public function updated(LegalInformationQuestion $legalInformationQuestion): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the LegalInformationQuestion "deleted" event.
     */
    public function deleted(LegalInformationQuestion $legalInformationQuestion): void
    {
        Cache::forget($this->CACHE_KEY);
    }

    /**
     * Handle the LegalInformationQuestion "restored" event.
     */
    public function restored(LegalInformationQuestion $legalInformationQuestion): void
    {
        //
    }

    /**
     * Handle the LegalInformationQuestion "force deleted" event.
     */
    public function forceDeleted(LegalInformationQuestion $legalInformationQuestion): void
    {
        //
    }
}
