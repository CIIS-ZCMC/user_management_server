<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use App\Models\PersonalInformation;
use App\Observers\PersonalInformationObserver;

use App\Models\System;
use App\Observers\SystemObserver;

use App\Models\JobPosition;
use App\Observers\JobPositionObserver;

use App\Models\Address;
use App\Observers\AddressObserver;

use App\Models\Contact;
use App\Observers\ContactObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Address::observe(AddressObserver::class);
        PersonalInformation::observe(PersonalInformationObserver::class);
        JobPosition::observe(JobPositionObserver::class);
        System::observe(SystemObserver::class);
        Contact::observe(ContactObserver::class);
    }
}
