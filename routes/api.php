<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaveAndOverTime\LeaveApplicationController;

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

Route::get('/initialize-storage', function (Request $request) {
    Artisan::call('storage:link');
});


// In case the env client domain doesn't work
Route::namespace("App\Http\Controllers\UmisAndEmployeeManagement")->group(function () {
    Route::get('update-system', 'SystemController@updateUMISDATA');
});

Route::
        namespace("App\Http\Controllers\UmisAndEmployeeManagement")->group(function () {
            Route::get('update-system', 'SystemController@updateUMISDATA');
            Route::get('employees-sample', 'EmployeeProfileController@employeeListSample');
        });

Route::
        namespace('App\Http\Controllers')->group(function () {
            // VERSION 2
            Route::namespace('Authentication')->group(function(){
                Route::post('sign-in', 'AuthWithCredentialController@store');
            });

            Route::get('transfer-employee-areas', 'TransferEmployeeAreaController@index');
            Route::put('transfer-employee-areas', 'TransferEmployeeAreaController@update');
            Route::delete('transfer-employee-areas', 'TransferEmployeeAreaController@destroy');
        });

Route::post('leave-application-import', [LeaveApplicationController::class, 'import']);


Route::
        namespace('App\Http\Controllers')->group(function () {
            // Route::get('test', 'DashboardController@test');
        
            // Route::get('announcementslist', 'AnnouncementsController@index');
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
            Route::get('notification', 'NotificationController@store');


            Route::put('account-recovery', 'AccountRecoveryController@update');
        });

Route::
        namespace('App\Http\Controllers\PayrollHooks')->group(function () {
            Route::get('testgenerate', 'GenerateReportController@GenerateDataReport');
            Route::get('getUserNightDifferentials', 'GenerateReportController@GenerateDataNightDiffReport');
            Route::post('getUserInformations', 'SessionController@getUserInfo');
        });

Route::
        namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {
            // Route::post('sign-in', 'EmployeeProfileController@signIn');
            Route::post('sign-in-with-otp', 'EmployeeProfileController@signInWithOTP');
            Route::post('skip-for-now', 'EmployeeProfileController@updatePasswordExpiration');
            Route::post('verify-email-and-send-otp', 'EmployeeProfileController@verifyEmailAndSendOTP');
            Route::post('verify-otp', 'EmployeeProfileController@verifyOTP');
            Route::post('new-password', 'EmployeeProfileController@newPassword');
            Route::post('resend-otp', 'EmployeeProfileController@resendOTP');
            Route::get('retrieve-token', 'CsrfTokenController@generateCsrfToken');
            Route::get('validate-token', 'CsrfTokenController@validateToken');
            Route::post('employee-profile/signout-from-other-device', 'EmployeeProfileController@signOutFromOtherDevice');
            Route::get('generate-pds', 'PersonalInformationController@generatePDS');


            Route::get('in-active-employees/force-delete', 'EmployeeProfileController@remove');
        });

Route::middleware('auth.cookie')->group(function () {

    Route::namespace('App\Http\Controllers')->group(function () {

        // VERSION 2
        Route::namespace("AccessManagement")->group(callback: function() {
            Route::get('employee-with-special-access-roles', "EmployeeWithSpecialAccessRoleController@index");

            // Systems API Key Management
            Route::post('system-api-keys', "SystemsAPIKeyController@store");
            Route::delete('system-api-keys', "SystemsAPIKeyController@destroy");
        });

        // VERSION 2
        Route::namespace('Authentication')->group(callback: function(){         
            Route::delete('sign-out', 'AuthWithCredentialController@destroy');
        });

        Route::namespace("Migration")->group(function () {
            Route::post('reset-password-get-link', 'ResetPasswordWithCsv@getLinkOfEmployeeToResetPassword');
            Route::post('reset-password-with-employee-ids', 'ResetPasswordWithCsv@resetAndSendNewCredentialToUsers');
        });

        // Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
        //     Route::put('account-recovery', 'AccountRecoveryController@update');
        // });

        Route::post('redcap-module-import', 'RedcapController@import');
        Route::post('redcap-module', 'RedcapController@storeRedCapModule');
        Route::get('redcap-module-employees', 'RedcapController@employessWithRedCapModules');

        Route::get('announcements/{id}', 'AnnouncementsController@showAnnouncement');
        Route::get('announcements', 'AnnouncementsController@index');
        Route::middleware(['auth.permission:UMIS-PAM view'])->group(function () {
            Route::get('notifications', 'NotificationController@getNotificationsById');
        });

        Route::middleware(['auth.permission:UMIS-PAM view'])->group(function () {
            Route::put('notifications/seen-all', 'NotificationController@seenAllNotification');
        });

        Route::middleware(['auth.permission:UMIS-PAM view'])->group(function () {
            Route::put('notifications/{id}/seen', 'NotificationController@seen');
        });

        Route::middleware(['auth.permission:UMIS-PAM view'])->group(function () {
            Route::put('notifications-seen-multiple', 'NotificationController@seenMultipleNotification');
        });

        Route::middleware(['auth.permission:UMIS-PAM view'])->group(function () {
            Route::delete('notifications-delete-multiple', 'NotificationController@destroyMultiple');
        });

        Route::middleware(['auth.permission:UMIS-PAM view'])->group(function () {
            Route::delete('notification/{id}/delete', 'NotificationController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-SM write', 'request.timing'])->group(function () {
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

        /**
         * Digital Signature
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::post('digital-signature', 'DigitalCertificateController@store');
            Route::post('sign-dtr', 'DigitalCertificateController@signDtr');
            Route::apiResource('signed-dtr', 'DigitalSignedDtrController');
            Route::apiResource('signed-leaves', 'DigitalSignedLeaveController');

            // Digital DTR Signature Requests
            Route::apiResource('dtr-sig-requests', 'DigitalDtrSignatureRequestController');
            Route::post('approve-dtr', 'DigitalDtrSignatureRequestController@approveSignatureRequest');
            Route::post('approve-dtr-batch', 'DigitalDtrSignatureRequestController@approveBatchSignatureRequests');
            Route::get('view-dtr/{id}', 'DigitalDtrSignatureRequestController@viewOrDownloadDTR');
            Route::get('view-all-dtr', 'DigitalDtrSignatureRequestController@viewAllOrDownloadDTR');
        });
    });

    Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {
        Route::delete('signout', 'EmployeeProfileController@signOut');
        Route::post('re-authenticate', 'EmployeeProfileController@revalidateAccessToken');
        // Route::delete('signout', 'EmployeeProfileController@signOut');

        /**
         * Login Trail Module
         */
        Route::middleware('auth.permission:user view')->group(function () {
            Route::get('login-trail/{id}', 'LoginTrailController@show');
        });

        /**
         * Freedomwall
         */
        Route::get('freedom-wall-messages', 'FreedomWallMessageController@index');
        Route::post('freedom-wall-message', 'FreedomWallMessageController@store');
        Route::put('freedom-wall-messages/{id}', 'FreedomWallMessageController@update');
        Route::delete('freedom-wall-messages/{id}', 'FreedomWallMessageController@destroy');
        Route::middleware('auth.permission:UMIS-SM view-all')->group(function () {
            Route::post('freedom-wall-messages-filter-year', 'FreedomWallMessageController@filterByYear');
        });
        Route::post('freedom-wall-message-like/{messageId}/like', 'FreedomWallMessageController@like');
        Route::post('freedom-wall-message-unlike/{messageId}/unlike', 'FreedomWallMessageController@unlike');
    });

    /**
     * User Management Information System
     */
    Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {
        /**
         * System Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-all', 'SystemController@index');
        });

        // Route::middleware(['auth.permission:UMIS-SM write'])->group(function () {
        //     Route::post('system', 'SystemController@store');
        // });

        Route::middleware(['auth.permission:UMIS-SM write', 'request.timing'])->group(function () {
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

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function () {
            Route::get('system-roles-rights/{id}', 'SystemRoleController@systemRoleAccessRights');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function () {
            Route::post('system-roles-rights/{id}', 'SystemRoleController@systemRoleAccessRightsUpdate');
        });

        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-role/employees-with-special-access', 'SystemRoleController@employeesWithSpecialAccess');
        });

        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function () {
            Route::get('system-role/employees-with-special-access/{id}', 'SystemRoleController@employeeWithSpecialAccess');
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
        // Route::get('employees-dtr-list', 'EmployeeProfileController@employeesDTRList');
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

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('address-many/{id}', 'AddressController@updateMany');
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

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('child-many', 'ChildController@updateMany');
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
            Route::post('civil-service-new-request', 'CivilServiceEligibilityController@employeeUpdateEligibilities');
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
            Route::put('civil-service-eligibility-single-data/{id}', 'CivilServiceEligibilityController@updateSingleData');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('civil-service-eligibility/{id}', 'CivilServiceEligibilityController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('civil-service-eligibility-many', 'CivilServiceEligibilityController@updateMany');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('civil-service-eligibility/{id}', 'CivilServiceEligibilityController@destroy');
        });

        /**
         * Department Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('department-all', 'DepartmentController@index');
            Route::get('departments/trashbin', 'DepartmentController@trash');
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
            Route::put('department/{id}/restore', 'DepartmentController@restore');
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

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('designation-wplantilla', 'DesignationController@fetchwPlantilla');
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
            Route::get('divisions/trashbin', 'DivisionController@trash');
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
            Route::put('division/{id}/restore', 'DivisionController@restore');
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
            Route::post('educational-new-request', 'EducationalBackgroundController@employeeUpdateEducation');
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

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('educational-background-single-data/{id}', 'EducationalBackgroundController@updateSingleData');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('educational-background-many', 'EducationalBackgroundController@updateMany');
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

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('user-mentions', 'EmployeeProfileController@getUserListMentions');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('inactive-employees', 'InActiveEmployeeController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('inactive-employee/{id}', 'InActiveEmployeeController@showProfile');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('employee-deactivate-account/{id}', 'InActiveEmployeeController@retireAndDeactivateAccount');
        });

        Route::middleware(['auth.permission:UMIS-EM post'])->group(function () {
            Route::post('employee-re-employ/{id}', 'InActiveEmployeeController@reEmploy');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-update-request', 'EmployeeProfileController@profileUpdateRequest');
        });

        Route::middleware(['auth.permission:UMIS-EM approve'])->group(function () {
            Route::put('employee-approve-request', 'EmployeeProfileController@approvedProfileUpdate');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function () {
            Route::post('employees-renew-contract', 'EmployeeProfileController@renewEmployee');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-for-renewal', 'EmployeeProfileController@employeeForRenewal');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::post('employees-assign-oic', 'EmployeeProfileController@assignOICByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function () {
            Route::get('employees-for-oic', 'EmployeeProfileController@employeesForOIC');
        });

        Route::middleware(['auth.permission:UMIS-PAM update'])->group(function () {
            Route::put('employee-profile-update-pin', 'EmployeeProfileController@updatePin');
        });

        Route::middleware(['auth.permission:UMIS-PAM update'])->group(function () {
            Route::put('employee-profile-update-password', 'EmployeeProfileController@updatePassword');
        });

        Route::middleware(['auth.permission:UMIS-PAM post'])->group(function () {
            Route::post('employee-profile-update-shifting', 'EmployeeProfileController@updateEmployeeProfileShifting');
        });

        Route::middleware(['auth.permission:UMIS-PAM post'])->group(function () {
            Route::post('employee-profile-by-area', 'EmployeeProfileController@employeesByArea');
        });

        Route::middleware(['auth.permission:UMIS-PAM post'])->group(function () {
            Route::post('employee-profile-filter', 'EmployeeProfileController@filterEmployeeProfile');
        });


        Route::middleware(['auth.permission:UMIS-PAM update'])->group(function () {
            Route::put('employee-profile-twofa-status', 'EmployeeProfileController@update2fa');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('my-all-employees', 'EmployeeProfileController@myAllEmployees');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('my-employees', 'EmployeeProfileController@myEmployees');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('get-all-my-employees', 'EmployeeProfileController@getAllEmployees');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('my-areas', 'EmployeeProfileController@myAreas');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('sub-areas', 'EmployeeProfileController@getAreas');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('area-employees/{id}/sector/{sector}', 'EmployeeProfileController@areasEmployees');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('employee-reassign-area/{id}', 'EmployeeProfileController@reAssignArea');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::get('employee-profile-cards', 'EmployeeProfileController@employeesCards');
        });


        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('employee-profile-picture/{id}', 'EmployeeProfileController@updateEmployeeProfilePicture');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::post('employee-profile/promote/{id}', 'EmployeeProfileController@promotion');
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

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-profile-all-records', 'EmployeeProfileController@employeeRecords');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-profile-all-dropdown', 'EmployeeProfileController@indexDropdown');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-profile-bytypes', 'EmployeeProfileController@getEmployeeListByEmployementTypes');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::get('employee-account-reset-password/{id}', 'EmployeeProfileController@resetPassword');
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
            Route::delete('employee-profile-deactivate/{id}', 'EmployeeProfileController@deactivateEmployeeAccount');
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


        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('employee-profile/{id}/revoke/{access_right_id}', 'EmployeeProfileController@revokeRights');
        });

        // Reports
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::post('leave-application-filter', 'EmployeeProfileController@Areas');
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
            Route::put('plantilla-reassign-area/{id}', 'PlantillaController@reAssignArea');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('plantilla-reassign-plantilla/{id}', 'PlantillaController@reAssignPlantilla');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('plantilla-all', 'PlantillaController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('plantilla-filter-type-job', 'PlantillaController@plantillaNumberBaseOnJobPositionAndEmploymentType');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('plantilla-referrence-to-assignarea', 'PlantillaController@plantillaReferrenceToAssignArea');
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
            Route::put('salary-grade-set-new', 'SalaryGradeController@updateSalaryGradeForJobPosition');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('salary-grade/{id}', 'SalaryGradeController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('salary-grade/{id}', 'SalaryGradeController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('salary-grade/by-effective-date', 'SalaryGradeController@destroyOnEffectiveDate');
        });

        /**
         * Section Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('section-all', 'SectionController@index');
            Route::get('sections/trashbin', 'SectionController@trash');
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
            Route::put('section/{id}/restore', 'SectionController@restore');
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
            Route::post('training-new-request', 'TrainingController@employeeUpdateTraining');
        });

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

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('training-single-data/{id}', 'TrainingController@updateSingleData');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('training-many', 'TrainingController@updateMany');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function () {
            Route::delete('training/{id}', 'TrainingController@destroy');
        });

        /**
         * Unit Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('unit-all', 'UnitController@index');
            Route::get('units/trashbin', 'UnitController@trash');
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
            Route::put('unit/{id}/restore', 'UnitController@restore');
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

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('voluntary-work-single-data/{id}', 'VoluntaryWorkController@updateSingleData');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('voluntary-work-many', 'VoluntaryWorkController@updateMany');
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

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('work-experience-single-data/{id}', 'WorkExperienceController@updateSingleData');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function () {
            Route::put('work-experience-many', 'WorkExperienceController@updateMany');
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
            Route::get('dtr-device-devices', 'BioMSController@index');
            Route::post('dtr-pushuser-to-devices', 'BioController@fetchUserToDevice');
            Route::post('dtr-pulluser-from-devices', 'BioController@fetchUserFromDevice');
            Route::post('dtr-pushuser-to-opdevices', 'BioController@fetchUserToOPDevice');
            Route::post('dtr-fetchall-bio', 'BioController@fetchBIOToDevice');
            Route::get('dtr-holidays', 'DTRcontroller@getHolidays');
            Route::get('dtr-fetchuser-Biometrics', 'BioMSController@fetchBiometrics');
            Route::get('dtr-getusers-Logs', 'DTRcontroller@getUsersLogs');
            Route::post('dtr-recompute/{biometric_id}/{month}/{year}', 'DTRcontroller@ReComputeDTR');
        });
        // Route::middleware(['auth.permission:UMIS-DTRM download'])->group(function () {

        // });


        Route::middleware(['auth.permission:UMIS-PAM view'])->group(function () {
            Route::get('dtr-self', 'DTRcontroller@pullDTRuser');

            Route::get('print-dtr-logs', 'DTRcontroller@printDtrLogs');
        });

        Route::middleware(['auth.permission:UMIS-DTRM view'])->group(function () {

            Route::get('dtr-md-records-self', 'DTRcontroller@monthDayRecordsSelf');
            Route::get('dtr-device-testdevice', 'BioMSController@testDeviceConnection');
            Route::get('dtr-fetchuser', 'DTRcontroller@fetchUserDTR');
            Route::get('dtr-reports', 'DTRcontroller@dtrUTOTReport');
            Route::get('dtr-generate', 'DTRcontroller@generateDTR');
            Route::get('dtr-getusers-biologs', 'DTRcontroller@getBiometricLog');
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

        /**
         * Monitization Posting Module
         *
         */

        //imports
        // Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
        //     Route::post('leave-application-import', 'LeaveApplicationController@import');
        // });

        //reports
        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::post('leave-application-filter', 'LeaveApplicationController@countapprovedleaveApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('monetization-posts', 'MonitizationPostingController@index');
        });

        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('monetization-posts-candidates', 'MonitizationPostingController@candidates');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('monetization-posts/{id}/check-for-sl-monitization', 'MonitizationPostingController@checkForSLMonitization');
        });

        Route::middleware(['auth.permission:UMIS-LM write'])->group(function () {
            Route::post('monetization-post', 'MonitizationPostingController@store');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::put('monetization-posts/{id}', 'MonitizationPostingController@update');
        });

        Route::middleware(['auth.permission:UMIS-LM delete'])->group(function () {
            Route::delete('monetization-posts/{id}', 'MonitizationPostingController@destroy');
        });

        /**
         * Monitization Posting Module
         */
        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('monetization', 'MonetizationApplicationController@index');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('user-monetization', 'MonetizationApplicationController@userMoneApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('monetization-approve/{id}', 'MonetizationApplicationController@approvedApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('monetization-decline/{id}', 'MonetizationApplicationController@declineMoneApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('monetization-cancel/{id}', 'MonetizationApplicationController@cancelmoneApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM write'])->group(function () {
            Route::post('monetization', 'MonetizationApplicationController@store');
        });

        Route::middleware(['auth.permission:UMIS-LM download'])->group(function () {
            Route::get('monetization-print/{id}', 'MonetizationApplicationController@printLeaveForm');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::put('monetization/{id}', 'MonetizationApplicationController@updateMoneApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM delete'])->group(function () {
            Route::delete('monetization/{id}', 'MonetizationApplicationController@destroy');
        });


        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('requirement-all', 'RequirementController@index');
        });

        Route::middleware(['auth.permission:UMIS-LM write'])->group(function () {
            Route::post('requirement', 'RequirementController@store');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('requirement/{id}', 'RequirementController@update');
        });

        // Route::middleware(['auth.permission:UMIS-LM delete'])->group(function(){
        //     Route::post('requirement/{id}', 'RequirementController@destroy');
        // });


        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('leave-type-all', 'LeaveTypeController@index');
        });

        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('leave-type-hrmo', 'LeaveTypeController@hrmoLeaveTypes');
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

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('leave-type-select-hrmo', 'LeaveTypeController@hrmoLeaveTypeOptionWithEmployeeCreditsRecord');
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

        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('countries', 'LeaveApplicationController@showCountries');
        });

        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('hrmo-leave-applied-all', 'LeaveApplicationController@getAppliedByHrmo');
        });

        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('leave-application-hrmo-all', 'LeaveApplicationController@hrmoApproval');
        });

        //Secretary
        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('leave-application-approved', 'LeaveApplicationController@approvedLeaveRequest');
        });
        //hr
        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('leave-application-approved-hr', 'LeaveApplicationController@approvedLeaveApplication');
        });
        //omcc
        Route::middleware(['auth.permission:UMIS-LM view-all'])->group(function () {
            Route::get('forced-leave-application-mcc', 'LeaveApplicationController@flLeaveApplication');
        });


        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('my-leave-application-approved', 'LeaveApplicationController@myApprovedLeaveApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('my-leave-application', 'LeaveApplicationController@myLeaveApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('my-leave-application-approved/{id}', 'LeaveApplicationController@employeeApprovedLeaveApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('user-leave-application', 'LeaveApplicationController@userLeaveApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('leave-application/{id}', 'LeaveApplicationController@show');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('receive-leave-application/{id}', 'LeaveApplicationController@received');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('cancel-leave-application/{id}', 'LeaveApplicationController@cancelled');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('cancel-leave-application-user/{id}', 'LeaveApplicationController@cancelUser');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('cancel-forced-leave-application/{id}', 'LeaveApplicationController@cancelFL');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('reschedule-leave-application/{id}', 'LeaveApplicationController@reschedule');
        });

        Route::middleware(['auth.permission:UMIS-LM update'])->group(function () {
            Route::post('change-leave-date/{id}', 'LeaveApplicationController@changeDate');
        });

        Route::middleware(['auth.permission:UMIS-LM request'])->group(function () {
            Route::post('hrmo-leave-application', 'LeaveApplicationController@storeHrmo');
        });

        Route::middleware(['auth.permission:UMIS-LM request'])->group(function () {
            Route::post('leave-application', 'LeaveApplicationController@store');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('leave-application-decline/{id}', 'LeaveApplicationController@declined');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('leave-application-approved/{id}', 'LeaveApplicationController@approved');
        });

        Route::middleware(['auth.permission:UMIS-LM download'])->group(function () {
            Route::get('leave-application-print/{id}', 'LeaveApplicationController@printLeaveForm');
        });

        Route::middleware(['auth.permission:UMIS-LM download'])->group(function () {
            Route::post('print-leave-application/{id}', 'LeaveApplicationController@updatePrint');
        });

        Route::middleware(['auth.permission:UMIS-LM download'])->group(function () {
            Route::get('export-csv', 'LeaveApplicationController@exportCsv');
        });


        Route::middleware(['auth.permission:UMIS-LM write'])->group(function () {
            Route::post('leave-credit-add', 'LeaveApplicationController@addCredit');
        });

        Route::middleware(['auth.permission:UMIS-LM write'])->group(function () {
            Route::post('leave-credit-update', 'LeaveApplicationController@updateCredit');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('leave-credit-employees', 'LeaveApplicationController@getEmployees');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('leave-credit-select-employees', 'LeaveApplicationController@getAllEmployees');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('leave-credit-leave-type', 'LeaveApplicationController@getLeaveTypes');
        });

        Route::middleware(['auth.permission:UMIS-LM view'])->group(function () {
            Route::get('employee-credit-logs/{id}', 'LeaveApplicationController@employeeCreditLog');
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

        Route::middleware(['auth.permission:UMIS-OB download'])->group(function () {
            Route::get('export-csv-ob', 'OfficialBusinessController@exportCsv');
        });

        /**
         * Official Time Module
         */
        Route::middleware(['auth.permission:UMIS-OT view-all'])->group(function () {
            Route::get('ot-application-all', 'OfficialTimeController@index');
        });

        Route::middleware(['auth.permission:UMIS-OT view'])->group(function () {
            Route::get('user-ot-application', 'OfficialTimeController@create');
        });

        Route::middleware(['auth.permission:UMIS-OT request'])->group(function () {
            Route::post('ot-application', 'OfficialTimeController@store');
        });

        Route::middleware(['auth.permission:UMIS-OT approve'])->group(function () {
            Route::post('ot-application/{id}', 'OfficialTimeController@update');
        });

        Route::middleware(['auth.permission:UMIS-OT download'])->group(function () {
            Route::get('export-csv-ot', 'OfficialTimeController@exportCsv');
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


        Route::middleware(['auth.permission:UMIS-OT approve'])->group(function () {
            Route::post('ot-application-decline/{id}', 'OfficialTimeApplicationController@declineOtApplication');
        });

        Route::middleware(['auth.permission:UMIS-OT approve'])->group(function () {
            Route::post('ot-application-cancel/{id}', 'OfficialTimeApplicationController@cancelOtApplication');
        });

        Route::middleware(['auth.permission:UMIS-LM approve'])->group(function () {
            Route::post('ot-application-update/{id}/{status}', 'OfficialTimeApplicationController@updateStatus');
        });

        // Route::middleware(['auth.permission:UMIS-OT view'])->group(function(){
        //     Route::get('user-ot-application', 'OfficialTimeApplicationController@getUserOtApplication');
        // });

        Route::middleware(['auth.permission:UMIS-OT view'])->group(function () {
            Route::get('access-level-ot-application', 'OfficialTimeApplicationController@getOtApplications');
        });


        Route::middleware(['auth.permission:UMIS-OM view-all'])->group(function () {
            Route::get('ovt-application-all', 'OvertimeController@index');
        });

        // Route::middleware(['auth.permission:UMIS-OM request'])->group(function () {
        Route::post('ovt-application', 'OvertimeController@store');
        // });

        Route::get('ovt-application-printPast/{id}', 'OvertimeController@printPastOvertimeForm');


        Route::get('ovt-application-print/{id}', 'OvertimeController@printOvertimeForm');

        Route::middleware(['auth.permission:UMIS-OM request'])->group(function () {
            Route::post('ovt-application-past', 'OvertimeController@storePast');
        });

        Route::middleware(['auth.permission:UMIS-OM request'])->group(function () {
            Route::post('ovt-application-bulk', 'OvertimeController@storeBulk');
        });

        Route::middleware(['auth.permission:UMIS-LM download'])->group(function () {
            Route::get('overtime-print/{id}', 'OvertimeController@printOvertimeForm');
        });

        Route::middleware(['auth.permission:UMIS-OM approve'])->group(function () {
            Route::post('ovt-application-approved/{id}', 'OvertimeController@approved');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('ovt-application/{id}', 'OvertimeController@show');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('user-ovt-application', 'OvertimeController@userOvertimeApplication');
        });

        //hr
        Route::middleware(['auth.permission:UMIS-OM view-all'])->group(function () {
            Route::get('ovt-application-approved-hr', 'OvertimeController@approvedOvertimeApplication');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('my-ovt-application-approved', 'OvertimeController@myApprovedOvertimeApplication');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('my-ovt-application', 'OvertimeController@myOvertimeApplication');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('my-ovt-application-approved/{id}', 'OvertimeController@employeeApprovedOvertimeApplication');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('user-ovt-application', 'OvertimeController@getUserOvertime');
        });

        Route::middleware(['auth.permission:UMIS-OM view'])->group(function () {
            Route::get('supervisor-ovt-application', 'OvertimeController@getSupervisor');
        });

        Route::middleware(['auth.permission:UMIS-OM approve'])->group(function () {
            Route::post('ovt-application-decline/{id}', 'OvertimeController@declined');
        });

        Route::post('add-monthly-overtime', 'EmployeeOvertimeCreditController@store');

        Route::middleware(['auth.permission:UMIS-CT view-all'])->group(function () {
            Route::get('cto-application-all', 'CtoApplicationController@index');
        });

        Route::middleware(['auth.permission:UMIS-CT view-all'])->group(function () {
            Route::get('cto-application-same-area', 'CtoApplicationController@CtoApplicationUnderSameArea');
        });


        Route::middleware(['auth.permission:UMIS-CT request'])->group(function () {
            Route::post('cto-application', 'CtoApplicationController@store');
        });

        Route::middleware(['auth.permission:UMIS-CT view'])->group(function () {
            Route::get('user-cto-application', 'CtoApplicationController@create');
        });

        Route::middleware(['auth.permission:UMIS-CT write'])->group(function () {
            Route::post('cto-credit-update', 'CtoApplicationController@updateCredit');
        });

        Route::middleware(['auth.permission:UMIS-CT approve'])->group(function () {
            Route::post('cto-application-decline/{id}', 'CtoApplicationController@declineCtoApplication');
        });


        Route::middleware(['auth.permission:UMIS-CT approve'])->group(function () {
            Route::post('cto-application-approve/{id}', 'CtoApplicationController@approved');
        });

        Route::middleware(['auth.permission:UMIS-CT view'])->group(function () {
            Route::get('employee-cto-credit-logs/{id}', 'CtoApplicationController@employeeCreditLog');
        });

        Route::middleware(['auth.permission:UMIS-CT view'])->group(function () {
            Route::get('cto-credit-employees', 'CtoApplicationController@getEmployees');
        });

        Route::middleware(['auth.permission:UMIS-CT download'])->group(function () {
            Route::get('export-csv-cto', 'CtoApplicationController@exportCsv');
        });
    });

    /**
     * Schedule Management
     */
    Route::namespace('App\Http\Controllers\Schedule')->group(function () {
        /**
         * Time Shift Module
         */
        Route::middleware(['auth.permission:UMIS-TS view-all'])->group(function () {
            Route::get('time-shift', 'TimeShiftController@index');
        });

        Route::middleware(['auth.permission:UMIS-TS write'])->group(function () {
            Route::post('time-shift', 'TimeShiftController@store');
        });

        Route::middleware(['auth.permission:UMIS-TS update'])->group(function () {
            Route::put('time-shift/{id}', 'TimeShiftController@update');
        });

        Route::middleware(['auth.permission:UMIS-TS delete'])->group(function () {
            Route::delete('time-shift/{id}', 'TimeShiftController@destroy');
        });


        /**
         * Schedule Module
         */
        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('schedules', 'EmployeeScheduleController@index');
        });

        Route::middleware(['auth.permission:UMIS-ScM view'])->group(function () {
            Route::get('schedule', 'EmployeeScheduleController@create');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function () {
            Route::post('schedule', 'EmployeeScheduleController@store');
        });

        Route::middleware(['auth.permission:UMIS-ScM view'])->group(function () {
            Route::get('schedule/{id}', 'EmployeeScheduleController@edit');
        });

        Route::middleware(['auth.permission:UMIS-ScM update'])->group(function () {
            Route::put('schedule/{id}', 'EmployeeScheduleController@update');
        });

        Route::middleware(['auth.permission:UMIS-ScM delete'])->group(function () {
            Route::delete('schedule/{id}', 'EmployeeScheduleController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-ScM download'])->group(function () {
            Route::get('schedule-generate', 'ScheduleController@generate');
        });

        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('schedules-my-areas', 'ScheduleController@myAreas');
        });

        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('schedules-filter', 'ScheduleController@FilterByAreaAndDate');
        });

        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('schedules-time-shift', 'TimeShiftController@index');
        });

        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('schedules-employment-type', 'ScheduleController@EmploymentType');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function () {
            Route::post('generate-employee-schedule', 'EmployeeScheduleController@generate');
        });

        Route::middleware(['auth.permission:UMIS-ScM delete'])->group(function () {
            Route::delete('remove-employee-schedule', 'EmployeeScheduleController@remove');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function () {
            Route::post('uploads', 'EmployeeScheduleController@upload');
        });


        /**
         * Exchange Schedule Module
         */
        Route::middleware(['auth.permission:UMIS-ES view-all'])->group(function () {
            Route::get('exchange-duties', 'ExchangeDutyController@index');
        });

        Route::middleware(['auth.permission:UMIS-ES view'])->group(function () {
            Route::get('exchange-duty', 'ExchangeDutyController@create');
        });

        Route::middleware(['auth.permission:UMIS-ES request'])->group(function () {
            Route::post('exchange-duties', 'ExchangeDutyController@store');
        });

        Route::middleware(['auth.permission:UMIS-ES update'])->group(function () {
            Route::put('exchange-duties/{id}', 'ExchangeDutyController@update');
        });

        Route::middleware(['auth.permission:UMIS-ES delete'])->group(function () {
            Route::delete('exchange-duties/{id}', 'ExchangeDutyController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-ES approve'])->group(function () {
            Route::get('exchange-duty-aprroval', 'ExchangeDutyController@edit');
        });

        Route::middleware(['auth.permission:UMIS-ES view'])->group(function () {
            Route::get('exchange-duty-my-schedule', 'ExchangeDutyController@findMySchedule');
        });

        Route::middleware(['auth.permission:UMIS-ES view'])->group(function () {
            Route::get('exchange-duty-reliever-schedule', 'ExchangeDutyController@findRelieverSchedule');
        });

        Route::middleware(['auth.permission:UMIS-ES view'])->group(function () {
            Route::get('exchange-duty-employee', 'ScheduleController@employeeList');
        });


        /**
         * Pull Out Module
         */
        // Route::middleware(['auth.permission:UMIS-POM view-all'])->group(function () {
        //     Route::get('pull-outs', 'PullOutController@index');
        // });

        // Route::middleware(['auth.permission:UMIS-POM view'])->group(function () {
        //     Route::get('pull-out', 'PullOutController@create');
        // });

        // Route::middleware(['auth.permission:UMIS-POM write'])->group(function () {
        //     Route::post('pull-out', 'PullOutController@store');
        // });

        // Route::middleware(['auth.permission:UMIS-POM approve'])->group(function () {
        //     Route::put('pull-out/{id}', 'PullOutController@update');
        // });

        // Route::middleware(['auth.permission:UMIS-POM delete'])->group(function () {
        //     Route::delete('pull-out/{id}', 'PullOutController@destroy');
        // });

        // Route::middleware(['auth.permission:UMIS-POM view'])->group(function () {
        //     Route::get('pull-out-aprroval', 'PullOutController@edit');
        // });

        // Route::middleware(['auth.permission:UMIS-POM view'])->group(function () {
        //     Route::get('pull-out-section', 'PullOutController@sections');
        // });

        // Route::middleware(['auth.permission:UMIS-POM view'])->group(function () {
        //     Route::get('pull-out-section-employee', 'PullOutController@sectionEmployees');
        // });


        /**
         * On Call Schedule Module
         */
        // Route::middleware(['auth.permission:UMIS-OCM view-all'])->group(function () {
        //     Route::get('on-calls', 'OnCallController@index');
        // });

        // Route::middleware(['auth.permission:UMIS-OCM view'])->group(function () {
        //     Route::get('on-call', 'OnCallController@create');
        // });

        // Route::middleware(['auth.permission:UMIS-OCM write'])->group(function () {
        //     Route::post('on-call', 'OnCallController@store');
        // });

        // Route::middleware(['auth.permission:UMIS-OCM delete'])->group(function () {
        //     Route::delete('on-call/{id}', 'OnCallController@destroy');
        // });

        /**
         * Time Adjustment Module
         */
        Route::middleware(['auth.permission:UMIS-TA view-all'])->group(function () {
            Route::get('time-adjustments', 'TimeAdjustmentController@index');
        });

        Route::middleware(['auth.permission:UMIS-TA view'])->group(function () {
            Route::get('time-adjustment', 'TimeAdjustmentController@create');
        });

        Route::middleware(['auth.permission:UMIS-TA request'])->group(function () {
            Route::post('time-adjustment', 'TimeAdjustmentController@store');
        });

        Route::middleware(['auth.permission:UMIS-TA approve'])->group(function () {
            Route::put('time-adjustment/{id}', 'TimeAdjustmentController@update');
        });

        Route::middleware(['auth.permission:UMIS-TA delete'])->group(function () {
            Route::delete('time-adjustment/{id}', 'TimeAdjustmentController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-TA request'])->group(function () {
            Route::post('time-adjustment-request', 'TimeAdjustmentController@request');
        });

        Route::middleware(['auth.permission:UMIS-TA update'])->group(function () {
            Route::put('time-adjustment-update/{id}', 'TimeAdjustmentController@updateRequest');
        });

        Route::middleware(['auth.permission:UMIS-TA view'])->group(function () {
            Route::get('time-adjustment-employee', 'TimeAdjustmentController@employees');
        });



        /**
         * Holiday Module
         */
        Route::middleware(['auth.permission:UMIS-HOL view-all'])->group(function () {
            Route::get('holiday', 'HolidayController@index');
        });

        Route::middleware(['auth.permission:UMIS-HOL write'])->group(function () {
            Route::post('holiday', 'HolidayController@store');
        });

        Route::middleware(['auth.permission:UMIS-HOL update'])->group(function () {
            Route::put('holiday/{id}', 'HolidayController@update');
        });

        Route::middleware(['auth.permission:UMIS-HOL delete'])->group(function () {
            Route::delete('holiday/{id}', 'HolidayController@destroy');
        });

        Route::get('holidays', 'HolidayController@calendar');


        /**
         * MonthlyWorkHours Module
         */
        Route::middleware(['auth.permission:UMIS-MWH view-all'])->group(function () {
            Route::get('monthly-work-hours', 'MonthlyWorkHoursController@index');
        });

        Route::middleware(['auth.permission:UMIS-MWH write'])->group(function () {
            Route::post('monthly-work-hour', 'MonthlyWorkHoursController@store');
        });

        Route::middleware(['auth.permission:UMIS-MWH update'])->group(function () {
            Route::put('monthly-work-hour', 'MonthlyWorkHoursController@update');
        });

        Route::middleware(['auth.permission:UMIS-MWH delete'])->group(function () {
            Route::delete('monthly-work-hour/{id}', 'MonthlyWorkHoursController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-MWH view-all'])->group(function () {
            Route::get('get-employment-type', 'MonthlyWorkHoursController@getEmploymentType');
        });

        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function () {
            Route::get('get-monthly-work-hours', 'MonthlyWorkHoursController@getMonthlyWorkHours');
        });

        Route::middleware(['auth.permission:UMIS-ScM view'])->group(function () {
            Route::get('get-my-total-work-hours', 'MonthlyWorkHoursController@getMyTotalWorkHours');
        });
    });

    /**
     * Employee Reports
     */
    Route::namespace('App\Http\Controllers\Reports')->group(function () {
        // Filter Employees by Blood Type
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-by-blood-type-filter', 'EmployeeReportController@filterEmployeesByBloodType');
        });
        // Filter Employees by Civil Status
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-by-civil-status-filter', 'EmployeeReportController@filterEmployeesByCivilStatus');
        });
        // Filter Employees by Job Status
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-by-job-status-filter', 'EmployeeReportController@filterEmployeesByJobStatus');
        });
        // Filter Employees per Position
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-per-position-filter', 'EmployeeReportController@filterEmployeesPerPosition');
        });
        // Filter Employees by Service Length
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-by-service-length-filter', 'EmployeeReportController@filterEmployeesByServiceLength');
        });
        // Filter Employees by Address
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-by-address', 'EmployeeReportController@filterEmployeesByAddress');
        });
        // Filter Employees by Sex
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-by-sex', 'EmployeeReportController@filterEmployeesBySex');
        });
        // Filter Employees by PWD
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-by-pwd', 'EmployeeReportController@filterEmployeesByPWD');
        });
        // Filter Employees by Solo Parent
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-by-solo-parent', 'EmployeeReportController@filterEmployeesBySoloParent');
        });
        // Filter Employees by Religion
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employee-by-religion', 'EmployeeReportController@filterEmployeesByReligion');
        });


        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-blood-type', 'EmployeeReportController@allEmployeesBloodType');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-by-type/{type}/area/{id}/sector/{sector}', 'EmployeeReportController@employeesByBloodType');
        });

        // CIVIL STATUS
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-civil-status', 'EmployeeReportController@allEmployeesCivilStatus');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-civil-status/{type}/area/{id}/sector/{sector}', 'EmployeeReportController@employeesByCivilStatus');
        });


        // EMPLOYMENT TYPE
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-by-employment-type/{id}/area/{area_id}/sector/{sector}', 'EmployeeReportController@employeesByEmploymentType');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-by-employment-type/{id}', 'EmployeeReportController@employeesEmploymentType');
        });


        // PER JOB POSITION
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-by-job-position/{id}', 'EmployeeReportController@employeesPerJobPosition');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-by-job-position/{id}/area/{area_id}/sector/{sector}', 'EmployeeReportController@employeesPerJobPositionAndArea');
        });

        // SERVICE LENGTH
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('employees-service-length', 'EmployeeReportController@allEmployeesServiceLength');
        });

        // LEAVE REPORT
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('leave-report-filter', 'LeaveReportController@filterLeave');
        });

        // ATTENDANCE REPORTS
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('attendance-report-by-period', 'AttendanceReportController@reportByPeriod');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('attendance-report-by-daterange', 'AttendanceReportController@reportByDateRange');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('attendance-report-summary', 'AttendanceReportController@reportSummary');
        });

        // TEST ROUTE
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::post('leave-application-report-filter', 'LeaveReportController@filterLeave');
        });

        // LOGIN ACTIVITIES REPORT
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('login-activities-report', 'LoginActivitiesReport@generateLoginActivitiesReport');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('login-frequency-report', 'LoginActivitiesReport@generateLoginFrequencyReport');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('login-failed-attempts-report', 'LoginActivitiesReport@generateFailedLoginAttemptsReport');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function () {
            Route::get('login-device-browser-report', 'LoginActivitiesReport@generateDeviceBrowserLoginReport');
        });
    });
});

/**
 * Third party system end points
 *
 * Authentication of server api will be done here
 * While user authorization verification will be done on requester server
 * only if the permission is intended for that server
 *
 * Upon user load on the other client then the server api will request for user permission details from the umis
 * then store the data in the database of the server api
 */

Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {
    Route::middleware("auth.thirdparty")->group(function () {
        Route::get('authenticate-user-session', 'SystemController@authenticateUserFromDifferentSystem');
    });
});

Route::namespace('App\Http\Controllers')->group(function(){
    Route::middleware('auth.thirdparty')->group(function(){
        Route::namespace("Authentication")->group(callback: function() {

            // AUTH WITH SESSION ID
            Route::post('auth-with-session-id', "AuthWithApiKeySessionIDController@store");

            //AUTH WITH CREDENTIAL
            Route::get('auth-with-crential', "AuthWithApiKeyCredentialController@store");
        });
    });
});

// Route::
//         namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {
//             Route::middleware("auth.thirdparty")->group(function () {
//                 Route::get('authenticate-user-session', 'SystemController@authenticateUserFromDifferentSystem');
//             });
//         });

        

