<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use App\Models\Address;
use App\Observers\AddressObserver;

use App\Models\CivilServiceEligibility;
use App\Observers\CivilServiceEligibilityObserver;

use App\Models\Contact;
use App\Observers\ContactObserver;

use App\Models\Department;
use App\Observers\DepartmentObserver;

use App\Models\Division;
use App\Observers\DivisionObserver;

use App\Models\EmployeeProfile;
use App\Observers\EmployeeProfileObserver;

use App\Models\FamilyBackground;
use App\Observers\FamilyBackgroundObserver;

use App\Models\JobPosition;
use App\Observers\JobPositionObserver;

use App\Models\IdentificationNumber;
use App\Observers\IdentificationNumberObserver;

use App\Models\LegalInformation;
use App\Observers\LegalInformationObserver;

use App\Models\LegalInformationQuestion;
use App\Observers\LegalInformationQuestionObserver;

use App\Models\OtherInformation;
use App\Observers\OtherInformationObserver;

use App\Models\PersonalInformation;
use App\Observers\PersonalInformationObserver;

use App\Models\Plantilla;
use App\Observers\PlantillaObserver;

use App\Models\PasswordTrail;
use App\Observers\PasswordTrailObserver;

use App\Models\References;
use App\Observers\ReferencesObserver;

use App\Models\SalaryGrade;
use App\Observers\SalaryGradeObserver;

use App\Models\Station;
use App\Observers\StationObserver;

use App\Models\System;
use App\Observers\SystemObserver;

use App\Models\SystemRole;
use App\Observers\SystemRoleObserver;

use App\Models\Training;
use App\Observers\TrainingObserver;

use App\Models\WorkExperience;
use App\Observers\WorkExperienceObserver;

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
        CivilServiceEligibility::observe(CivilServiceEligibilityObserver::class);
        Contact::observe(ContactObserver::class);
        Department::observe(DepartmentObserver::class);
        Division::observe(DivisionObserver::class);
        EmployeeProfile::observe(EmployeeProfileObserver::class);
        FamilyBackground::observe(FamilyBackgroundObserver::class);
        JobPosition::observe(JobPositionObserver::class);
        IdentificationNumber::observe(IdentificationNumberObserver::class);
        LegalInformation::observe(LegalInformationObserver::class);
        LegalInformationQuestion::observe(LegalInformationQuestionObserver::class);
        OtherInformation::observe(OtherInformationObserver::class);
        PersonalInformation::observe(PersonalInformationObserver::class);
        Plantilla::observe(PlantillaObserver::class);
        PasswordTrail::observe(PasswordTrailObserver::class);
        References::observe(ReferencesObserver::class);
        SalaryGrade::observe(SalaryGradeObserver::class);
        Station::observe(StationObserver::class);
        System::observe(SystemObserver::class);
        SystemRole::observe(SystemRoleObserver::class);
        Training::observe(TrainingObserver::class);
        WorkExperience::observe(WorkExperienceObserver::class);
    }
}
