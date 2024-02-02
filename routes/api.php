<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
*/

// Attach CSP in response
// Route::middleware('csp.token')->group(function(){});


Route::get('/initialize-storage', function () {
    Artisan::call('storage:link');
});

Route::namespace('App\Http\Controllers')->group(function () {
    Route::get('announcements', 'AnnouncementsController@index');
    Route::get('announcements-search', 'AnnouncementsController@searchAnnouncement');
    Route::get('announcements/{id}', 'AnnouncementsController@show');

    Route::get('events', 'EventsController@index');
    Route::get('events-search', 'EventsController@searchEvents');
    Route::get('events/{id}', 'EventsController@show');

    Route::get('memorandums', 'MemorandumsController@index');
    Route::get('memorandums-search', 'MemorandumsController@searchMemorandum');
    Route::get('memorandums/{id}', 'MemorandumsController@show');

    Route::get('news', 'NewsController@index');
    Route::get('news-search', 'NewsController@searchNews');
    Route::get('news/{id}', 'NewsController@show');
});


Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {
    Route::post('sign-in', 'EmployeeProfileController@signIn');
    Route::post('sign-in-with-otp', 'EmployeeProfileController@signInWithOTP');
    Route::post('verify-email-and-send-otp', 'EmployeeProfileController@verifyEmailAndSendOTP');
    Route::post('verify-otp', 'EmployeeProfileController@verifyOTP');
    Route::post('new-password', 'EmployeeProfileController@newPassword');
    Route::get('retrieve-token', 'CsrfTokenController@generateCsrfToken');
    Route::get('validate-token', 'CsrfTokenController@validateToken');
    Route::post('employee-profile/signout-from-other-device', 'EmployeeProfileController@signOutFromOtherDevice');
});

Route::middleware('auth.cookie')->group(function () {

    Route::namespace('App\Http\Controllers')->group(function () {
        Route::middleware('auth.permission:UMIS-SM write')->group(function () {
            Route::post('announcements', 'AnnouncementsController@store');
        });

        Route::middleware('auth.permission:UMIS-SM update')->group(function () {
            Route::put('announcements/{id}', 'AnnouncementsController@update');
        });

        Route::middleware('auth.permission:UMIS-SM delete')->group(function () {
            Route::delete('announcements/{id}', 'AnnouncementsController@delete');
        });

        /** Events */
        Route::middleware('auth.permission:UMIS-SM write')->group(function () {
            Route::post('events', 'EventsController@store');
        });

        Route::middleware('auth.permission:UMIS-SM update')->group(function () {
            Route::put('events/{id}', 'EventsController@update');
        });

        Route::middleware('auth.permission:UMIS-SM delete')->group(function () {
            Route::delete('events/{id}', 'EventsController@delete');
        });

        /** Memoranda */
        Route::middleware('auth.permission:UMIS-SM write')->group(function () {
            Route::post('memorandums', 'MemorandumsController@store');
        });

        Route::middleware('auth.permission:UMIS-SM update')->group(function () {
            Route::put('memorandums/{id}', 'MemorandumsController@update');
        });

        Route::middleware('auth.permission:UMIS-SM delete')->group(function () {
            Route::delete('memorandums/{id}', 'MemorandumsController@delete');
        });

        /** News */
        Route::middleware('auth.permission:UMIS-SM write')->group(function () {
            Route::post('news', 'NewsController@store');
        });

        Route::middleware('auth.permission:UMIS-SM update')->group(function () {
            Route::put('news/{id}', 'NewsController@update');
        });

        Route::middleware('auth.permission:UMIS-SM delete')->group(function () {
            Route::delete('news/{id}', 'NewsController@delete');
        });

        /**
         * Dashboard Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('birthday-celebrants', 'DashboardController@listOfBirthdayCelebrant');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('human-resources', 'DashboardController@humanResource');
        });
    });

    Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {
        Route::post('re-authenticate', 'EmployeeProfileController@revalidateAccessToken');
        Route::delete('signout', 'EmployeeProfileController@signOut');

        /**
         * Login Trail Module
         */
        Route::middleware('auth.permission:user view')->group(function () {
            Route::get('login-trail/{id}', 'LoginTrailController@show');
        });

        /**
         * Freedomwall
         */
        Route::get('freedom-wall-messages', 'FreedomWallMessagesController@index');
        Route::post('freedom-wall-message', 'FreedomWallMessagesController@store');
        Route::put('freedom-wall-messages/{id}', 'FreedomWallMessagesController@update');
        Route::delete('freedom-wall-messages/{id}', 'FreedomWallMessagesController@destroy');
    });

    /**
     * User Management Information System
     */
    Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {

        /**
         * Default Password Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('default-password-all', 'DefaultPasswordController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('default-password', 'DefaultPasswordController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('default-password/{id}', 'DefaultPasswordController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('default-password/{id}', 'DefaultPasswordController@update');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function () {
            Route::delete('default-password/{id}', 'DefaultPasswordController@destroy');
        });

        /**
         * System Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-all', 'SystemController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('system', 'SystemController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('system-generate-key/{id}', 'SystemController@generateAPIKey');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('system-update-status/{id}', 'SystemController@updateSystemStatus');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('system/{id}', 'SystemController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('system-update/{id}', 'SystemController@update');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function () {
            Route::delete('system/{id}', 'SystemController@destroy');
        });

        /**
         * System Logs Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-log-all', 'SystemLogsController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('system-log/{id}', 'SystemLogsController@show');
        });


        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('system-log-access-rights', 'SystemLogsController@findByAccessRights');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function () {
            Route::delete('system-log/{id}', 'SystemLogsController@destroy');
        });

        /**
         * System Modules Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-module-all', 'SystemModuleController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-module/find-by-system/{id}', 'SystemModuleController@systemModulesByID');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('system-module/{id}', 'SystemModuleController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('system-module-add-permission/{id}', 'SystemModuleController@addPermission');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('system-module/{id}', 'SystemModuleController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('system-module/{id}', 'SystemModuleController@update');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function () {
            Route::delete('system-module/{id}', 'SystemModuleController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function () {
            Route::delete('system-module/all-permission/{id}', 'SystemModuleController@destroyAllPermission');
        });

        /**
         * System Role Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-role-all', 'SystemRoleController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-role/employees-with-special-access', 'SystemRoleController@employeesWithSpecialAccess');
        });

        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-role/designation-with-system-roles', 'SystemRoleController@designationsWithSystemRoles');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('system-role/{id}', 'SystemRoleController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('system-role-add-permission/{id}', 'SystemRoleController@addRolePermission');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('system-role/assign-special-access/{id}', 'SystemRoleController@addSpecialAccessRole');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('system-role-new-role-permission/{id}', 'SystemRoleController@registerNewRoleAndItsPermission');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('system-role/find-permissions/{id}', 'SystemRoleController@findSystemRolePermissions');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('system-role/{id}', 'SystemRoleController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('system-role/{id}', 'SystemRoleController@update');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function () {
            Route::delete('system-role/{id}', 'SystemRoleController@destroy');
        });


        /**
         * Role Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('role-all', 'RoleController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('role', 'RoleController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('role/{id}', 'RoleController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('role/{id}', 'RoleController@update');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function () {
            Route::delete('role/{id}', 'RoleController@destroy');
        });


        /**
         * Permission Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('permission-all', 'PermissionController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('permission', 'PermissionController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('permission/{id}', 'PermissionController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('permission/{id}', 'PermissionController@update');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('permission-activate/{id}', 'PermissionController@activate');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::put('permission-deactivate/{id}', 'PermissionController@deactivate');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function () {
            Route::delete('permission/{id}', 'PermissionController@destroy');
        });

        /**
         * Module Permission Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('module-permission-all', 'ModulePermissionController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('module-permission/find-by-system/{id}', 'ModulePermissionController@systemModulePermission');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('module-permission/{id}', 'ModulePermissionController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::delete('module-permission/{id}', 'ModulePermissionController@destroy');
        });
    });

    /**
     * Employee Management
     */
    Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {
        /**
         * Address Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('address-all-personal-info/{id}', 'AddressController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('address-all-employee/{id}', 'AddressController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('address-all-employee/{id}', 'AddressController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('address/{id}', 'AddressController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('address/{id}', 'AddressController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::put('address/{id}', 'AddressController@destroy');
        });

        /**
         * Assign Area Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('assign-area-all', 'AssignAreaController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('assign-area-all-by-employee/{id}', 'AssignAreaController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('assign-area', 'AssignAreaController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('assign-area/{id}', 'AssignAreaController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('assign-area/{id}', 'AssignAreaController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('assign-area/{id}', 'AssignAreaController@destroy');
        });

        /**
         * Assign Area Trail Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('assign-area-trail-all', 'AssignAreaTrailController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('assign-area-trail-all-by-employee/{id}', 'AssignAreaTrailController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('assign-area-trail', 'AssignAreaTrailController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('assign-area-trail/{id}', 'AssignAreaTrailController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('assign-area-trail/{id}', 'AssignAreaTrailController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('assign-area-trail/{id}', 'AssignAreaTrailController@destroy');
        });

        /**
         * Child Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('child-all-by-personal-info/{id}', 'ChildController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('child-all-by-employee/{id}', 'ChildController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('child', 'ChildController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('child/{id}', 'ChildController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('child/{id}', 'ChildController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('child/{id}', 'ChildController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('child-by-personal-info{id}', 'ChildController@destroyByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('child-by-employee{id}', 'ChildController@destroyByEmployeeID');
        });

        /**
         * Contact Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('contact-all-by-personal-info/{id}', 'ContactController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('contact-all-by-employee/{id}', 'ContactController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('contact', 'ContactController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('contact/{id}', 'ContactController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('contact/{id}', 'ContactController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('contact/{id}', 'ContactController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('contact-by-personal-info{id}', 'ContactController@destroyByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('contact-by-employee{id}', 'ContactController@destroyByEmployeeID');
        });

        /**
         * Civil Service Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('civil-service-eligibility-all-by-personal-info/{id}', 'CivilServiceEligibilityController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('civil-service-eligibility-all-by-employee/{id}', 'CivilServiceEligibilityController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('civil-service-eligibility', 'CivilServiceEligibilityController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('civil-service-eligibility-many', 'CivilServiceEligibilityController@storeMany');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('civil-service-eligibility/{id}', 'CivilServiceEligibilityController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('civil-service-eligibility/{id}', 'CivilServiceEligibilityController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('civil-service-eligibility/{id}', 'CivilServiceEligibilityController@destroy');
        });

        /**
         * Department Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('department-all', 'DepartmentController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('department-assign-head-employee/{id}', 'DepartmentController@assignHeadByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('department-assign-to-employee/{id}', 'DepartmentController@assignTrainingOfficerByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('department-assign-oic-employee/{id}', 'DepartmentController@assignOICByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('department', 'DepartmentController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('department/{id}', 'DepartmentController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('department-update/{id}', 'DepartmentController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('department/{id}', 'DepartmentController@destroy');
        });

        /**
         * Designation Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('designation-all', 'DesignationController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('designation/total-employee-per-designation', 'DesignationController@totalEmployeePerDesignation');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('designation/total-plantilla-per-designation', 'DesignationController@totalPlantillaPerDesignation');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('designation/employee-list/{id}', 'DesignationController@employeeListInDesignation');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('designation', 'DesignationController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('designation/{id}', 'DesignationController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('designation/{id}', 'DesignationController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('designation/{id}', 'DesignationController@destroy');
        });

        // KRIZ
        Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
            Route::post('designation-assign-system-role', 'DesignationController@assignSystemRole');
        });

        /**
         * Division Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('division-all', 'DivisionController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('division-assign-chief-employee/{id}', 'DivisionController@assignChiefByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('division-assign-oic-employee/{id}', 'DivisionController@assignOICByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('division', 'DivisionController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('division/{id}', 'DivisionController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('division-update/{id}', 'DivisionController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('division/{id}', 'DivisionController@destroy');
        });

        /**
         * Educational Background Module
         */
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('educational-background-by-personal-info/{id}', 'EducationalBackgroundController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('educational-background-by-employee/{id}', 'EducationalBackgroundController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('educational-background', 'EducationalBackgroundController@store');
        });

        // Kriz
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('educational-background-many', 'EducationalBackgroundController@storeMany');
        });


        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('educational-background/{id}', 'EducationalBackgroundController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('educational-background/{id}', 'EducationalBackgroundController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('educational-background/{id}', 'EducationalBackgroundController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('educational-background-by-personal-info/{id}', 'EducationalBackgroundController@destroyByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('educational-background-by-employee/{id}', 'EducationalBackgroundController@destroyByEmployeeID');
        });

        /**
         * Educational Background Module
         */
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('educational-background-by-personal-info/{id}', 'EducationalBackgroundController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('educational-background-by-employee/{id}', 'EducationalBackgroundController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('educational-background', 'EducationalBackgroundController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('educational-background/{id}', 'EducationalBackgroundController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('educational-background/{id}', 'EducationalBackgroundController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('educational-background/{id}', 'EducationalBackgroundController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('educational-background-by-personal-info/{id}', 'EducationalBackgroundController@destroyByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('educational-background-by-employee/{id}', 'EducationalBackgroundController@destroyByEmployeeID');
        });

        /**
         * Employee Profile Module
         */
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('employee-reassign-area/{id}', 'EmployeeProfileController@reAssignArea');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::get('employee-profile/validate-access-token', 'EmployeeProfileController@revalidateAccessToken');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-by-area-assigned/{id}/sector/{sector}', 'EmployeeProfileController@employeesByAreaAssigned');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-dtr-list', 'EmployeeProfileController@employeesDTRList');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-profile-all', 'EmployeeProfileController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('employee-profile', 'EmployeeProfileController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('employee-profile/create-account', 'EmployeeProfileController@createEmployeeAccount');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('employee-profile/{id}', 'EmployeeProfileController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('employee-profile/find-by-employee/{id}', 'EmployeeProfileController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('employee-profile/{id}', 'EmployeeProfileController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('employee-profile/{id}', 'EmployeeProfileController@updateEmployeeProfile');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('employee-profile/{id}', 'EmployeeProfileController@destroy');
        });

        /**
         * Employment Type Module
         */

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employment-type-all', 'EmploymentTypeController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employment-type-for-dtr', 'EmploymentTypeController@employmentTypeForDTR');
        });


        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('employment-type', 'EmploymentTypeController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('employment-type/{id}', 'EmploymentTypeController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('employment-type/{id}', 'EmploymentTypeController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('employment-type/{id}', 'EmploymentTypeController@destroy');
        });

        /**
         * Family Background Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('family-background/find-by-employee/{id}', 'FamilyBackgroundController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('family-background/find-by-personal-info/{id}', 'FamilyBackgroundController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('family-background', 'FamilyBackgroundController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('family-background/{id}', 'FamilyBackgroundController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('family-background/{id}', 'FamilyBackgroundController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('family-background/{id}', 'FamilyBackgroundController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('family-background-by-personal-info/{id}', 'FamilyBackgroundController@destroyByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('family-background-by-employee/{id}', 'FamilyBackgroundController@destroyByEmployeeID');
        });


        /**
         * HeadToSupervisorTrail Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('head-to-s-trail/{id}', 'HeadToSupervisorTrailController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('head-to-s-trail/find-by-employee/{id}', 'HeadToSupervisorTrailController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('head-to-s-trail/{id}', 'HeadToSupervisorTrailController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('head-to-s-trail/{id}', 'HeadToSupervisorTrailController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('head-to-s-trail/{id}', 'HeadToSupervisorTrailController@destroy');
        });

        /**
         * Identification Number Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('identification-number-by-personal-info/{id}', 'IdentificationNumberController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('identification-number-by-employee/{id}', 'IdentificationNumberController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('identification-number', 'IdentificationNumberController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('identification-number/{id}', 'IdentificationNumberController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('identification-number/{id}', 'IdentificationNumberController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('identification-number/{id}', 'IdentificationNumberController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('identification-number-by-personal-info/{id}', 'IdentificationNumberController@destroyByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('identification-number-by-employee/{id}', 'IdentificationNumberController@destroyByEmployeeID');
        });

        /**
         * Issuance Information Module
         */
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('issuance-information', 'IssuanceInformationController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('issuance-information/{id}', 'IssuanceInformationController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('issuance-information/{id}', 'IssuanceInformationController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('issuance-information/{id}', 'IssuanceInformationController@destroy');
        });

        /**
         * Legal Information Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('legal-information/find-by-employee/{id}', 'LegalInformationController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('legal-information', 'LegalInformationController@store');
        });

        // Kriz
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('legal-information-many', 'LegalInformationController@storeMany');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('legal-information/{id}', 'LegalInformationController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('legal-information/{id}', 'LegalInformationController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('legal-information/{id}', 'LegalInformationController@destroy');
        });

        /**
         * Legal Information Question Module
         */
        Route::get('legal-information-question-all', 'LegalInformationQuestionController@index');

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('legal-information-question', 'LegalInformationQuestionController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('legal-information-question/{id}', 'LegalInformationQuestionController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('legal-information-question/{id}', 'LegalInformationQuestionController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('legal-information-question/{id}', 'LegalInformationQuestionController@destroy');
        });

        /**
         * Login Trail Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('login-trail', 'LoginTrailController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::get('login-trail/find-by-employee/{id}', 'LoginTrailController@findByEmployeeID');
        });

        /**
         * Officer In Charge Trail Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('officer-incharge-trail-all', 'OfficerInChargeTrailController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('officer-incharge-trail/find-by-employee/{id}', 'OfficerInChargeTrailController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('officer-incharge-trail/{id}', 'OfficerInChargeTrailController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::delete('officer-incharge-trail/{id}', 'OfficerInChargeTrailController@destroy');
        });

        /**
         * Other Information Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('other-information/find-by-personal-info/{id}', 'OtherInformationController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('other-information/find-by-employee/{id}', 'OtherInformationController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('other-information', 'OtherInformationController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('other-information-many', 'OtherInformationController@storeMany');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('other-information/{id}', 'OtherInformationController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('other-information/{id}', 'OtherInformationController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('other-information-personal-info/{id}', 'OtherInformationController@destroyByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('other-information-employee/{id}', 'OtherInformationController@destroyByEmployeeID');
        });

        /**
         * Personal Information Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('personal-information-all', 'PersonalInformationController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('personal-information', 'PersonalInformationController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('personal-information/{id}', 'PersonalInformationController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('personal-information/{id}', 'PersonalInformationController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('personal-information/{id}', 'PersonalInformationController@destroy');
        });

        /**
         * Plantilla Module
         */
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('plantilla-reassign-area', 'PlantillaController@reAssignArea');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('plantilla-all', 'PlantillaController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('plantilla/find-by-designation/{id}', 'PlantillaController@findByDesignationID');
        });


        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('plantilla-with-designation/{id}', 'PlantillaController@plantillaWithDesignation');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('plantilla', 'PlantillaController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('plantilla-assign-area/{id}', 'PlantillaController@assignPlantillaToAreas');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('plantilla-assign-area-random/{id}', 'PlantillaController@assignMultiplePlantillaToArea');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('plantilla/areas-for-plantilla-assign', 'PlantillaController@areasForPlantillaAssign');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('plantilla/{id}', 'PlantillaController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('plantilla-number-find/{id}', 'PlantillaController@showPlantillaNumber');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('plantilla/{id}', 'PlantillaController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('plantilla/{id}', 'PlantillaController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('plantilla-number/{id}', 'PlantillaController@destroyPlantillaNumber');
        });

        /**
         * Position System Role Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('position-system-role-all', 'PositionSystemRoleController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('position-system-role/find-by-designation/{id}', 'PositionSystemRoleController@findDesignationAccessRights');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('position-system-role', 'PositionSystemRoleController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('position-system-role/{id}', 'PositionSystemRoleController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('position-system-role/{id}', 'PositionSystemRoleController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('position-system-role/{id}', 'PositionSystemRoleController@destroy');
        });

        /**
         * Profile Update Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('profile-update-request', 'ProfileUpdateRequestController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('profile-update-pending', 'ProfileUpdateRequestController@pending');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('profile-update-request', 'ProfileUpdateRequestController@request');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('profile-update-approve/{id}', 'ProfileUpdateRequestController@approveRequest');
        });

        /**
         * Reference Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('reference/find-by-personal-info/{id}', 'ReferencesController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('reference/find-by-employee/{id}', 'ReferencesController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('reference', 'ReferencesController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('reference-many', 'ReferencesController@storeMany');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('reference/{id}', 'ReferencesController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('reference/{id}', 'ReferencesController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('reference/{id}', 'ReferencesController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('reference-personal-info/{id}', 'ReferencesController@destroyByPersonaslInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('reference-employee/{id}', 'ReferencesController@destroyByEmployeeID');
        });

        /**
         * Salary Grade Module
         */

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::post('salary-grade-import', 'SalaryGradeController@importSalaryGrade');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('salary-grade-all', 'SalaryGradeController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('salary-grade', 'SalaryGradeController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('salary-grade/{id}', 'SalaryGradeController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('salary-grade/{id}', 'SalaryGradeController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('salary-grade/{id}', 'SalaryGradeController@destroy');
        });

        /**
         * Section Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('section-all', 'SectionController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('section/assign-supervisor/{id}', 'SectionController@assignSupervisorByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('section/assign-oic/{id}', 'SectionController@assignOICByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('section', 'SectionController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('section/{id}', 'SectionController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('section-update/{id}', 'SectionController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('section/{id}', 'SectionController@destroy');
        });

        /**
         * Special Access Role Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('special-access-role-all', 'SpecialAccessRoleController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('special-access-role', 'SpecialAccessRoleController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('special-access-role/{id}', 'SpecialAccessRoleController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('special-access-role/{id}', 'SpecialAccessRoleController@destroy');
        });

        /**
         * Training Module
         */
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('training/find-by-personal-info/{id}', 'TrainingController@assignHeadByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('training/find-by-employee/{id}', 'TrainingController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('training', 'TrainingController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('training-many', 'TrainingController@storeMany');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('training/{id}', 'TrainingController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('training/{id}', 'TrainingController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('training/{id}', 'TrainingController@destroy');
        });

        /**
         * Unit Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('unit-all', 'UnitController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('unit/assign-head-employee/{id}', 'UnitController@assignHeadByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('unit/assign-oic-employee/{id}', 'UnitController@assignOICByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('unit', 'UnitController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('unit/{id}', 'UnitController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('unit-update/{id}', 'UnitController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('unit/{id}', 'UnitController@destroy');
        });

        /**
         * Voluntary Work Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('voluntary-work/assign-head-employee/{id}', 'VoluntaryWorkController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('voluntary-work/assign-oic-employee{id}', 'VoluntaryWorkController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('voluntary-work', 'VoluntaryWorkController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('voluntary-work-many', 'VoluntaryWorkController@storeMany');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('voluntary-work/{id}', 'VoluntaryWorkController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('voluntary-work/{id}', 'VoluntaryWorkController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('voluntary-work/{id}', 'VoluntaryWorkController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('voluntary-work-personal-info/{id}', 'VoluntaryWorkController@destroyByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('voluntary-work-employee/{id}', 'VoluntaryWorkController@destroyByEmployeeID');
        });

        /**
         * Voluntary Work Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('work-experience/assign-head-employee/{id}', 'WorkExperienceController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('work-experience/assign-oic-employee{id}', 'WorkExperienceController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('work-experience', 'WorkExperienceController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('work-experience-many', 'WorkExperienceController@storeMany');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('work-experience/{id}', 'WorkExperienceController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('work-experience/{id}', 'WorkExperienceController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('work-experience/{id}', 'WorkExperienceController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('work-experience-personal-info/{id}', 'WorkExperienceController@destroyByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('work-experience-employee/{id}', 'WorkExperienceController@destroyByEmployeeID');
        });
    });

    /**
     * Daily Time Record Management
     */
    Route::namespace('App\Http\Controllers\DTR')->group(function () {
        /** APPLY CODE HERE */
        Route::middleware(['auth.permission:UMIS-DTRM view-all'])->group(function () {
            Route::get('dtr-self', 'DTRcontroller@pullDTRuser');
            Route::get('dtr-device-devices', 'BioMSController@index');
            Route::post('dtr-pushuser-to-devices', 'BioController@fetchUserToDevice');
            Route::post('dtr-pulluser-from-devices', 'BioController@fetchUserFromDevice');
            Route::post('dtr-pushuser-to-opdevices', 'BioController@fetchUserToOPDevice');
            Route::post('dtr-fetchall-bio', 'BioController@fetchBIOToDevice');
            Route::get('dtr-generate', 'DTRcontroller@generateDTR');
            Route::get('dtr-holidays', 'DTRcontroller@getHolidays');
            Route::get('dtr-fetchuser-Biometrics', 'BioMSController@fetchBiometrics');
            Route::get('dtr-getusers-Logs', 'DTRcontroller@getUsersLogs');
        });

        Route::middleware(['auth.permission:UMIS-DTRM view'])->group(function () {
            Route::get('dtr-device-testdevice', 'BioMSController@testDeviceConnection');
            Route::get('dtr-fetchuser', 'DTRcontroller@fetchUserDTR');
            Route::get('dtr-reports', 'DTRcontroller@dtrUTOTReport');
        });


        Route::middleware(['auth.permission:UMIS-DTRM write'])->group(function () {
            Route::post('dtr-device-registerdevice', 'BioMSController@addDevice');
            Route::post('dtr-registerbio', 'BioController@registerBio');
            Route::post('dtr-synctime', 'BioController@syncTime');
            Route::get('dtr-setholidays', 'DTRcontroller@setHolidays');
        });

        Route::middleware(['auth.permission:UMIS-DTRM update'])->group(function () {
            Route::post('dtr-device-updatedevice', 'BioMSController@updateDevice');
            Route::get('dtr-device-enable-disable', 'BioController@enableORDisable');
            Route::post('dtr-device-setsuper-admin', 'BioController@setUserSuperAdmin');
            Route::post('dtr-device-shutdown', 'BioController@restartORShutdown');
            Route::get('dtr-device-settime', 'BioController@setTime');
            Route::get('dtr-modifyHoliday', 'DTRcontroller@modifyHolidays');
        });

        Route::middleware(['auth.permission:UMIS-DTRM delete'])->group(function () {
            Route::post('dtr-device-delete', 'BioMSController@deleteDevice');
            Route::get('dtr-device-deleteall-bio', 'BioController@deleteAllBIOFromDevice');
            Route::post('dtr-device-deleteuser-bio', 'BioController@deleteSpecificBIOFromDevice');
        });
    });

    /**
     * Leave and Overtime Management
     */
    Route::namespace('App\Http\Controllers\LeaveAndOverTime')->group(function () {

        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('requirement-all', 'RequirementController@index');
        });

        Route::middleware(['auth.permission:UMIS-LM write'])->group(function () {
            Route::post('requirement', 'RequirementController@store');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('requirement/{id}', 'RequirementController@update');
        });


        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('leave-type-all', 'LeaveTypeController@index');
        });


        Route::middleware(['auth.permission:UMIS-LM delete'])->group(function () {
            Route::post('requirement/{id}', 'RequirementController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('leave-type-all', 'LeaveTypeController@index');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('leave-type/{id}', 'LeaveTypeController@show');
        });

        Route::middleware(['auth.permission:UMIS-LM write'])->group(function () {

            Route::post('leave-type', 'LeaveTypeController@store');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('leave-type/{id}', 'LeaveTypeController@update');
        });


        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('leave-type-select', 'LeaveTypeController@leaveTypeOptionWithEmployeeCreditsRecord');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('leave-type-deactivate-password/{id}', 'LeaveTypeController@deactivateLeaveTypes');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('leave-type-activate-password/{id}', 'LeaveTypeController@reactivateLeaveTypes');
        });

        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('leave-application-all', 'LeaveApplicationController@index');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::get('user-leave-application', 'LeaveApplicationController@userLeaveApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('leave-application/{id}', 'LeaveApplicationController@show');
        });

        Route::middleware(['auth.permission:UMIS-LM request'])->group(function () {
            Route::post('leave-application', 'LeaveApplicationController@store');
        });


        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('leave-application-decline/{id}', 'LeaveApplicationController@declineLeaveApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('leave-application-cancel/{id}', 'LeaveApplicationController@cancelLeaveApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('leave-application-update/{id}/{status}', 'LeaveApplicationController@updateLeaveApplicationStatus');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('user-leave-application', 'LeaveApplicationController@getUserLeaveApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('access-level-leave-application', 'LeaveApplicationController@getLeaveApplications');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('leave-application-decline/{id}', 'LeaveApplicationController@declined');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('leave-application-approved/{id}', 'LeaveApplicationController@approved');
        });

        Route::middleware(['auth.permission:UMIS-LM download'])->group(function () {
            Route::post('print-leave-form/{id}', 'LeaveApplicationController@printLeaveForm');
        });



        /**
         * Official Business Module
         */
        Route::middleware(['auth.permission:UMIS-OB view-all'])->group(function () {
            Route::get('ob-application-all', 'OfficialBusinessController@index');
        });

        Route::middleware(['auth.permission:UMIS-OB view'])->group(function () {
            Route::get('user-ob-application', 'OfficialBusinessController@create');
        });

        Route::middleware(['auth.permission:UMIS-OB request'])->group(function () {
            Route::post('ob-application', 'OfficialBusinessController@store');
        });

        Route::middleware(['auth.permission:UMIS-OB approve'])->group(function () {
            Route::post('ob-application/{id}', 'OfficialBusinessController@update');
        });

        /**
         * Official Time Module
         */
        Route::middleware(['auth.permission:UMIS-OT view-all'])->group(function () {
            Route::get('ot-application-all', 'OfficialTimeController@index');
        });

        Route::middleware(['auth.permission:UMIS-OB view'])->group(function () {
            Route::get('user-ot-application', 'OfficialTimeController@create');
        });

        Route::middleware(['auth.permission:UMIS-OT request'])->group(function () {
            Route::post('ot-application', 'OfficialTimeController@store');
        });

        Route::middleware(['auth.permission:UMIS-OB approve'])->group(function () {
            Route::post('ot-application/{id}', 'OfficialTimeController@update');
        });


        Route::middleware(['auth.permission:UMIS-OB approve'])->group(function () {

            Route::post('ob-application-decline/{id}', 'ObApplicationController@declineObApplication');
        });

        Route::middleware(['auth.permission:UMIS-OB approve'])->group(function () {
            Route::post('ob-application-cancel/{id}', 'ObApplicationController@cancelObApplication');
        });

        Route::middleware(['auth.permission:UMIS-OB approve'])->group(function () {
            Route::post('ob-application-update/{id}/{status}', 'ObApplicationController@updateObApplicationStatus');
        });

        Route::middleware(['auth.permission:UMIS-OB view'])->group(function () {

            Route::get('access-level-ob-application', 'ObApplicationController@getObApplications');
        });



        Route::middleware(['auth.permission:UMIS-OT view-all'])->group(function () {
            Route::get('ot-application-all', 'OfficialTimeApplicationController@index');
        });

        Route::middleware(['auth.permission:UMIS-OT request'])->group(function () {
            Route::post('ot-application', 'OfficialTimeApplicationController@store');
        });



        Route::middleware(['auth.permission:UMIS-OT approve'])->group(function () {

            Route::post('ot-application-decline/{id}', 'OfficialTimeApplicationController@declineOtApplication');
        });

        Route::middleware(['auth.permission:UMIS-OT approve'])->group(function () {
            Route::post('ot-application-cancel/{id}', 'OfficialTimeApplicationController@cancelOtApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('ot-application-update/{id}/{status}', 'OfficialTimeApplicationController@updateStatus');
        });

        Route::middleware(['auth.permission:UMIS-OT view'])->group(function () {
            Route::get('user-ot-application', 'OfficialTimeApplicationController@getUserOtApplication');
        });


        Route::middleware(['auth.permission:UMIS-OT view'])->group(function () {
            Route::get('access-level-ot-application', 'OfficialTimeApplicationController@getOtApplications');
        });


        Route::middleware(['auth.permission:UMIS-OM view-all'])->group(function () {
            Route::get('ovt-application-all', 'OvertimeApplicationController@index');
        });

        Route::middleware(['auth.permission:UMIS-OM request'])->group(function () {
            Route::post('ovt-application', 'OvertimeApplicationController@store');
        });

        Route::middleware(['auth.permission:UMIS-OM request'])->group(function () {
            Route::post('ovt-application-past', 'OvertimeApplicationController@storePast');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('ovt-employee-select', 'OvertimeApplicationController@computeEmployees');
        });

        Route::middleware(['auth.permission:UMIS-OM approve'])->group(function () {
            Route::post('ovt-application-decline/{id}', 'OvertimeApplicationController@declineOtApplication');
        });

        Route::middleware(['auth.permission:UMIS-OM approve'])->group(function () {
            Route::post('ovt-application-cancel/{id}', 'OvertimeApplicationController@cancelOtApplication');
        });

        Route::middleware(['auth.permission:UMIS-OM approve'])->group(function () {
            Route::post('ovt-application-update/{id}/{status}', 'OvertimeApplicationController@updateOvertimeApplicationStatus');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('user-ovt-application', 'OvertimeApplicationController@getUserOvertimeApplication');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('access-level-ovt-application', 'OvertimeApplicationController@getOvertimeApplications');
        });


        Route::post('add-monthly-overtime', 'EmployeeOvertimeCreditController@store');


        Route::middleware(['auth.permission:UMIS-CT view-all'])->group(function () {
            Route::get('cto-application-all', 'CtoApplicationController@index');
        });

        Route::middleware(['auth.permission:UMIS-CT request'])->group(function () {
            Route::post('cto-application', 'CtoApplicationController@store');
        });


        Route::middleware(['auth.permission:UMIS-CT approve'])->group(function () {
            Route::post('cto-application-decline/{id}', 'CtoApplicationController@declineCtoApplication');
        });

        Route::middleware(['auth.permission:UMIS-CT approve'])->group(function () {
            Route::post('cto-application-cancel/{id}', 'CtoApplicationController@cancelCtoApplication');
        });

        Route::middleware(['auth.permission:UMIS-CT approve'])->group(function () {
            Route::post('cto-application-update/{id}/{status}', 'CtoApplicationController@updateStatus');
        });

        Route::middleware(['auth.permission:UMIS-CT view'])->group(function () {
            Route::get('user-cto-application', 'CtoApplicationController@getUserCtoApplication');
        });

        Route::middleware(['auth.permission:UMIS-CT view'])->group(function () {
            Route::get('access-level-cto-application', 'CtoApplicationController@getCtoApplications');
        });

        Route::middleware(['auth.permission:UMIS-CT view'])->group(function () {
            Route::get('user-cto-application', 'CtoApplicationController@create');
        });



        Route::middleware(['auth.permission:UMIS-CT approve'])->group(function () {
            Route::post('cto-application-approve/{id}', 'CtoApplicationController@approved');
        });
    });

    /**
     * Schedule Management
     */
    Route::namespace('App\Http\Controllers\Schedule')->group(function () {
        /**
         * Time Shift Module
         */
        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('time-shift', 'TimeShiftController@index');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function () {
            Route::post('time-shift', 'TimeShiftController@store');
        });

        Route::middleware(['auth.permission:UMIS-ScM update'])->group(function () {
            Route::put('time-shift/{id}', 'TimeShiftController@update');
        });

        Route::middleware(['auth.permission:UMIS-ScM delete'])->group(function () {
            Route::delete('time-shift/{id}', 'TimeShiftController@destroy');
        });

        /**
         * Schedule Module
         */
        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('schedules', 'ScheduleController@index');
        });

        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('schedule', 'ScheduleController@create');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function () {
            Route::post('schedule', 'ScheduleController@store');
        });

        Route::middleware(['auth.permission:UMIS-ScM update'])->group(function () {
            Route::put('schedule/{id}', 'ScheduleController@update');
        });

        Route::middleware(['auth.permission:UMIS-ScM delete'])->group(function () {
            Route::delete('schedule/{id}', 'ScheduleController@destroy');
        });

        /**
         * Employee Schedule Module
         */
        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('employee-schedule', 'EmployeeScheduleController@create');
        });

        /**
         * Exchange Schedule Module
         */
        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('exchange-duties', 'ExchangeDutyController@index');
        });

        Route::middleware(['auth.permission:UMIS-ScM view'])->group(function () {
            Route::get('exchange-duty', 'ExchangeDutyController@create');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function () {
            Route::post('exchange-duties', 'ExchangeDutyController@store');
        });

        Route::middleware(['auth.permission:UMIS-ScM update'])->group(function () {
            Route::put('exchange-duties/{id}', 'ExchangeDutyController@update');
        });

        Route::middleware(['auth.permission:UMIS-ScM delete'])->group(function () {
            Route::delete('exchange-duties/{id}', 'ExchangeDutyController@destroy');
        });

        /**
         * Pull Out Module
         */
        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('pull-out', 'PullOutController@index');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function () {
            Route::post('pull-out', 'PullOutController@store');
        });

        Route::middleware(['auth.permission:UMIS-ScM update'])->group(function () {
            Route::put('pull-out/{id}', 'PullOutController@update');
        });

        Route::middleware(['auth.permission:UMIS-ScM delete'])->group(function () {
            Route::delete('pull-out/{id}', 'PullOutController@destroy');
        });

        /**
         * Generate Schedule Module
         */
        Route::middleware(['auth.permission:UMIS-ScM download'])->group(function () {
            Route::get('generate', 'ScheduleController@generate');
        });

        /**
         * Time Adjustment Module
         */
        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('time-adjustment', 'TimeAdjusmentController@index');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function () {
            Route::post('time-adjustment', 'TimeAdjusmentController@store');
        });

        Route::middleware(['auth.permission:UMIS-ScM update'])->group(function () {
            Route::put('time-adjustment/{id}', 'TimeAdjusmentController@update');
        });

        Route::middleware(['auth.permission:UMIS-ScM delete'])->group(function () {
            Route::delete('time-adjustment/{id}', 'TimeAdjusmentController@destroy');
        });

        /**
         * On Call Schedule Module
         */
        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('on-calls', 'OnCallController@index');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function () {
            Route::post('on-call', 'OnCallController@store');
        });

        Route::middleware(['auth.permission:UMIS-ScM delete'])->group(function () {
            Route::delete('on-call/{id}', 'OnCallController@destroy');
        });
    });
});
