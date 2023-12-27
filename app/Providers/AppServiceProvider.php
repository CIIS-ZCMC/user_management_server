<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use App\Services\RequestLogger;
use App\Services\FileValidationAndUpload;

use App\Models\Division;
use App\Observers\DivisionObserver;

use App\Models\Department;
use App\Observers\DepartmentObserver;

use App\Models\Designation;
use App\Observers\DesignationObserver;

use App\Models\ModulePermission;
use App\Observers\ModulePermissionObserver;


use App\Models\EmployeeProfile;
use App\Observers\EmployeeProfileObserver;

use App\Models\Section;
use App\Observers\SectionObserver;

use App\Models\SystemLogs;
use App\Observers\SystemLogsObserver;

use App\Models\Unit;
use App\Observers\UnitObserver;

use App\Models\Plantilla;
use App\Observers\PlantillaObserver;

use App\Models\PositionSystemRole;
use App\Observers\PositionSystemRoleObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RequestLogger::class, function ($app) {
            return new RequestLogger();
        });
        
        $this->app->singleton(FileValidationAndUpload::class, function ($app) {
            return new FileValidationAndUpload();
        });

        Division::observe(DivisionObserver::class);
        Department::observe(DepartmentObserver::class);
        Designation::observe(DesignationObserver::class);
        EmployeeProfile::observe(EmployeeProfileObserver::class);
        ModulePermission::observe(ModulePermissionObserver::class);
        Section::observe(SectionObserver::class);
        SystemLogs::observe(SystemLogsObserver::class);
        Unit::observe(UnitObserver::class);
        Plantilla::observe(PlantillaObserver::class);
        PositionSystemRole::observe(PositionSystemRoleObserver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        $this->app->singleton('Helpers', function () {
            return new Helpers();
        });
    }
}
