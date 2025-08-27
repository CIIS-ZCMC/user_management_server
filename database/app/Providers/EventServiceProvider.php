<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

use App\Models\Announcements;
use App\Observers\AnnouncementsObserver;

use App\Models\Division;
use App\Observers\DivisionObserver;

use App\Models\Department;
use App\Observers\DepartmentObserver;

use App\Models\Designation;
use App\Observers\DesignationObserver;

use App\Models\Events;
use App\Observers\EventsObserver;

use App\Models\FreedomWallMessages;
use App\Observers\FreedomWallMessagesObserver;

use App\Models\Memorandums;
use App\Observers\MemorandumsObserver;

use App\Models\ModulePermission;
use App\Observers\ModulePermissionObserver;

use App\Models\News;
use App\Observers\NewsObserver;


use App\Models\EmployeeProfile;
use App\Observers\EmployeeProfileObserver;

use App\Models\Section;
use App\Observers\SectionObserver;

use App\Models\Unit;
use App\Observers\UnitObserver;

use App\Models\Plantilla;
use App\Observers\PlantillaObserver;

use App\Models\PositionSystemRole;
use App\Observers\PositionSystemRoleObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Announcements::observe(AnnouncementsObserver::class);
        Division::observe(DivisionObserver::class);
        Department::observe(DepartmentObserver::class);
        Designation::observe(DesignationObserver::class);
        EmployeeProfile::observe(EmployeeProfileObserver::class);
        Events::observe(EventsObserver::class);
        FreedomWallMessages::observe(FreedomWallMessagesObserver::class);
        Memorandums::observe(MemorandumsObserver::class);
        ModulePermission::observe(ModulePermissionObserver::class);
        News::observe(NewsObserver::class);
        Section::observe(SectionObserver::class);
        Unit::observe(UnitObserver::class);
        Plantilla::observe(PlantillaObserver::class);
        PositionSystemRole::observe(PositionSystemRoleObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
