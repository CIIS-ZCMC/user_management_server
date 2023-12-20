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
| sd,fg;'sdklf;dlsf'ldsflsdfl;'sdl;'sdl;'sdlf'dsl;'sdlf;'sdl'
*/

// Attach CSP in response
// Route::middleware('csp.token')->group(function(){});


Route::get('/initialize-storage', function () {
    Artisan::call('storage:link');
});

Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function () {
    Route::post('sign-in', 'EmployeeProfileController@signIn');
    Route::post('send-otp', 'EmployeeProfileController@sendOTPEmail');
    Route::post('validate-otp', 'EmployeeProfileController@validateOTP');
    Route::post('reset-password', 'EmployeeProfileController@resetPassword');
    Route::get('retrieve-token', 'CsrfTokenController@generateCsrfToken');
    Route::get('validate-token', 'CsrfTokenController@validateToken');
});


Route::namespace('App\Http\Controllers\LeaveAndOverTime')->group(function () {
    Route::get('get_employees', 'OvertimeApplicationController@getEmployees');
    Route::get('get_employees_overtime_total', 'OvertimeApplicationController@getEmployeeOvertimeTotal');
    Route::get('requirements', 'RequirementController@index');
    Route::get('leave-type-all', 'LeaveTypeController@index');
    Route::post('leave-type', 'LeaveTypeController@store');
    Route::post('leave-type-log', 'LeaveTypeController@testLog');
    Route::put('leave-type/{id}', 'LeaveTypeController@update');
    Route::post('leave-type-deactivate-password/{id}', 'LeaveTypeController@deactivateLeaveType');
    Route::post('leave-type-activate-password/{id}', 'LeaveTypeController@reactivateLeaveType');
    Route::get('requirement-all', 'RequirementController@index');
    Route::post('requirement', 'RequirementController@store');
    Route::put('requirement/{id}', 'RequirementController@update');
    Route::get('leave-application-all', 'LeaveApplicationController@index');
    Route::get('division-laot', 'LeaveApplicationController@getDivisionLeaveApplications');
    Route::get('add', 'LeaveCreditController@addMonthlyLeaveCredit');

    Route::get('ob-application-all', 'ObApplicationController@index');
});

Route::middleware('auth.cookie')->group(function(){
    
    Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function(){
        Route::post('authenticity-check', 'EmployeeProfileController@isAuthenticated');
        Route::delete('signout', 'EmployeeProfileController@signOut');

        /**
         * Login Trail Module
         */
        Route::middleware('auth.permission:user view')->group(function(){
            Route::get('login-trail/{id}', 'LoginTrailController@show');
        });
    });
    
    /**
     * User Management Information System
     */
    Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function(){
        
        /**
         * Default Password Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('default-password-all', 'DefaultPasswordController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('default-password', 'DefaultPasswordController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('default-password/{id}', 'DefaultPasswordController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('default-password/{id}', 'DefaultPasswordController@update');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function(){
            Route::delete('default-password/{id}', 'DefaultPasswordController@destroy');
        });

        /**
         * System Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('system-all', 'SystemController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('system', 'SystemController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('system-generate-key/{id}', 'SystemController@generateAPIKey');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('system-update-status/{id}', 'SystemController@updateSystemStatus');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('system/{id}', 'SystemController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('system-update/{id}', 'SystemController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function(){
            Route::delete('system/{id}', 'SystemController@destroy');
        });

        /**
         * System Logs Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('system-log-all', 'SystemLogsController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('system-log/{id}', 'SystemLogsController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('system-log-access-rights', 'SystemLogsController@findByAccessRights');
        });
        
        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function(){
            Route::delete('system-log/{id}', 'SystemLogsController@destroy');
        });

        /**
         * System Modules Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('system-module-all', 'SystemModuleController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('system-module/find-by-system/{id}', 'SystemModuleController@systemModulesByID');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('system-module/{id}', 'SystemModuleController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('system-module-add-permission/{id}', 'SystemModuleController@addPermission');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('system-module/{id}', 'SystemModuleController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('system-module/{id}', 'SystemModuleController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function(){
            Route::delete('system-module/{id}', 'SystemModuleController@destroy');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function(){
            Route::delete('system-module/all-permission/{id}', 'SystemModuleController@destroyAllPermission');
        });

        /**
         * System Role Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('system-role-all', 'SystemRoleController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('system-role/{id}', 'SystemRoleController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('system-role-add-permission/{id}', 'SystemRoleController@addRolePermission');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('system-role/assign-special-access/{id}', 'SystemRoleController@addSpecialAccessRole');
        });
        
        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('system-role-new-role-permission/{id}', 'SystemRoleController@registerNewRoleAndItsPermission');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('system-role/find-permissions/{id}', 'SystemRoleController@findSystemRolePermissions');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('system-role/{id}', 'SystemRoleController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('system-role/{id}', 'SystemRoleController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function(){
            Route::delete('system-role/{id}', 'SystemRoleController@destroy');
        });

        /**
         * Role Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('role-all', 'RoleController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('role', 'RoleController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('role/{id}', 'RoleController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('role/{id}', 'RoleController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function(){
            Route::delete('role/{id}', 'RoleController@destroy');
        });
        
        
        /**
         * Permission Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('permission-all', 'PermissionController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('permission', 'PermissionController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('permission/{id}', 'PermissionController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('permission/{id}', 'PermissionController@update');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('permission-activate/{id}', 'PermissionController@activate');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('permission-deactivate/{id}', 'PermissionController@deactivate');
        });
        
        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function(){
            Route::delete('permission/{id}', 'PermissionController@destroy');
        });

        /**
         * Module Permission Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('module-permission-all', 'ModulePermissionController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('module-permission/find-by-system/{id}', 'ModulePermissionController@systemModulePermission');
        });
        
        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('module-permission/{id}', 'ModulePermissionController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::delete('module-permission/{id}', 'ModulePermissionController@destroy');
        });
    });
    
    /**
     * Employee Management
     */
    Route::namespace('App\Http\Controllers\UmisAndEmployeeManagement')->group(function(){
        /**
         * Address Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('address-all-personal-info/{id}', 'AddressController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('address-all-employee/{id}', 'AddressController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('address-all-employee/{id}', 'AddressController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('address/{id}', 'AddressController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('address/{id}', 'AddressController@update');
        });

        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::put('address/{id}', 'AddressController@destroy');
        });

        /**
         * Assign Area Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('assign-area-all', 'AssignAreaController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('assign-area-all-by-employee/{id}', 'AssignAreaController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('assign-area', 'AssignAreaController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('assign-area/{id}', 'AssignAreaController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('assign-area/{id}', 'AssignAreaController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('assign-area/{id}', 'AssignAreaController@destroy');
        });

        /**
         * Assign Area Trail Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('assign-area-trail-all', 'AssignAreaTrailController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('assign-area-trail-all-by-employee/{id}', 'AssignAreaTrailController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('assign-area-trail', 'AssignAreaTrailController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('assign-area-trail/{id}', 'AssignAreaTrailController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('assign-area-trail/{id}', 'AssignAreaTrailController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('assign-area-trail/{id}', 'AssignAreaTrailController@destroy');
        });

        /**
         * Child Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('child-all-by-personal-info/{id}', 'ChildController@findByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('child-all-by-employee/{id}', 'ChildController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('child', 'ChildController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('child/{id}', 'ChildController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('child/{id}', 'ChildController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('child/{id}', 'ChildController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('child-by-personal-info{id}', 'ChildController@destroyByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('child-by-employee{id}', 'ChildController@destroyByEmployeeID');
        });

        /**
         * Contact Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('contact-all-by-personal-info/{id}', 'ContactController@findByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('contact-all-by-employee/{id}', 'ContactController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('contact', 'ContactController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('contact/{id}', 'ContactController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('contact/{id}', 'ContactController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('contact/{id}', 'ContactController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('contact-by-personal-info{id}', 'ContactController@destroyByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('contact-by-employee{id}', 'ContactController@destroyByEmployeeID');
        });

        /**
         * Department Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('department-all', 'DepartmentController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::post('department-assign-head-employee/{id}', 'DepartmentController@assignHeadByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::post('department-assign-to-employee/{id}', 'DepartmentController@assignTrainingOfficerByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::put('department-assign-oic-employee/{id}', 'DepartmentController@assignOICByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('department', 'DepartmentController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('department/{id}', 'DepartmentController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::post('department-update/{id}', 'DepartmentController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('department/{id}', 'DepartmentController@destroy');
        });

        /**
         * Designation Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('designation-all', 'DesignationController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('designation/total-employee-per-designation', 'DesignationController@totalEmployeePerDesignation');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('designation/total-plantilla-per-designation', 'DesignationController@totalPlantillaPerDesignation');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('designation/employee-list/{id}', 'DesignationController@employeeListInDesignation');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('designation', 'DesignationController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('designation-assign-system-role', 'DesignationController@assignSystemRole');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('designation/{id}', 'DesignationController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('designation/{id}', 'DesignationController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('designation/{id}', 'DesignationController@destroy');
        });

        /**
         * Division Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('division-all', 'DivisionController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('division-assign-chief-employee/{id}', 'DivisionController@assignChiefByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('division-assign-oic-employee/{id}', 'DivisionController@assignOICByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('division', 'DivisionController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('division/{id}', 'DivisionController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::post('division-update/{id}', 'DivisionController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('division/{id}', 'DivisionController@destroy');
        });

        /**
         * Educational Background Module
         */
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::put('educational-background-by-personal-info/{id}', 'EducationalBackgroundController@findByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::put('educational-background-by-employee/{id}', 'EducationalBackgroundController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('educational-background', 'EducationalBackgroundController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('educational-background/{id}', 'EducationalBackgroundController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('educational-background/{id}', 'EducationalBackgroundController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('educational-background/{id}', 'EducationalBackgroundController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('educational-background-by-personal-info/{id}', 'EducationalBackgroundController@destroyByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('educational-background-by-employee/{id}', 'EducationalBackgroundController@destroyByEmployeeID');
        });

        /**
         * Educational Background Module
         */
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::put('educational-background-by-personal-info/{id}', 'EducationalBackgroundController@findByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::put('educational-background-by-employee/{id}', 'EducationalBackgroundController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('educational-background', 'EducationalBackgroundController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('educational-background/{id}', 'EducationalBackgroundController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('educational-background/{id}', 'EducationalBackgroundController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('educational-background/{id}', 'EducationalBackgroundController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('educational-background-by-personal-info/{id}', 'EducationalBackgroundController@destroyByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('educational-background-by-employee/{id}', 'EducationalBackgroundController@destroyByEmployeeID');
        });
        
        /**
         * Employee Profile Module
         */
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('employee-profile/signout-from-other-device/{id}', 'EmployeeProfileController@signOutFromOtherDevice');
        });
        
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::get('employee-profile/validate-access-token', 'EmployeeProfileController@revalidateAccessToken');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('employees-dtr-list', 'EmployeeProfileController@employeesDTRList');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('employee-profile-all', 'EmployeeProfileController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('employee-profile', 'EmployeeProfileController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('employee-profile/create-account', 'EmployeeProfileController@createEmployeeAccount');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('employee-profile/{id}', 'EmployeeProfileController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('employee-profile/find-by-employee/{id}', 'EmployeeProfileController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('employee-profile/{id}', 'EmployeeProfileController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('employee-profile/{id}', 'EmployeeProfileController@updateEmployeeProfile');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('employee-profile/{id}', 'EmployeeProfileController@destroy');
        });

        /**
         * Employment Type Module
         */
        
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('employment-type-all', 'EmploymentTypeController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('employment-type', 'EmploymentTypeController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('employment-type/{id}', 'EmploymentTypeController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('employment-type/{id}', 'EmploymentTypeController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('employment-type/{id}', 'EmploymentTypeController@destroy');
        });

        /**
         * Family Background Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('family-background/find-by-employee/{id}', 'FamilyBackgroundController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('family-background/find-by-personal-info/{id}', 'FamilyBackgroundController@findByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('family-background', 'FamilyBackgroundController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('family-background/{id}', 'FamilyBackgroundController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('family-background/{id}', 'FamilyBackgroundController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('family-background/{id}', 'FamilyBackgroundController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('family-background-by-personal-info/{id}', 'FamilyBackgroundController@destroyByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('family-background-by-employee/{id}', 'FamilyBackgroundController@destroyByEmployeeID');
        });
        

        /**
         * HeadToSupervisorTrail Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('head-to-s-trail/{id}', 'HeadToSupervisorTrailController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('head-to-s-trail/find-by-employee/{id}', 'HeadToSupervisorTrailController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('head-to-s-trail/{id}', 'HeadToSupervisorTrailController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('head-to-s-trail/{id}', 'HeadToSupervisorTrailController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('head-to-s-trail/{id}', 'HeadToSupervisorTrailController@destroy');
        });

        /**
         * Identification Number Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('identification-number-by-personal-info/{id}', 'IdentificationNumberController@findByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('identification-number-by-employee/{id}', 'IdentificationNumberController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('identification-number', 'IdentificationNumberController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('identification-number/{id}', 'IdentificationNumberController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('identification-number/{id}', 'IdentificationNumberController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('identification-number/{id}', 'IdentificationNumberController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('identification-number-by-personal-info/{id}', 'IdentificationNumberController@destroyByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('identification-number-by-employee/{id}', 'IdentificationNumberController@destroyByEmployeeID');
        });

        /**
         * Issuance Information Module
         */
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('issuance-information', 'IssuanceInformationController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('issuance-information/{id}', 'IssuanceInformationController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('issuance-information/{id}', 'IssuanceInformationController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('issuance-information/{id}', 'IssuanceInformationController@destroy');
        });

        /**
         * Legal Information Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('legal-information/find-by-employee/{id}', 'LegalInformationController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('legal-information', 'LegalInformationController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('legal-information/{id}', 'LegalInformationController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('legal-information/{id}', 'LegalInformationController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('legal-information/{id}', 'LegalInformationController@destroy');
        });

        /**
         * Legal Information Question Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('legal-information-question-all', 'LegalInformationQuestionController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('legal-information-question', 'LegalInformationQuestionController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('legal-information-question/{id}', 'LegalInformationQuestionController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('legal-information-question/{id}', 'LegalInformationQuestionController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('legal-information-question/{id}', 'LegalInformationQuestionController@destroy');
        });

        /**
         * Login Trail Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('login-trail', 'LoginTrailController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::get('login-trail/find-by-employee/{id}', 'LoginTrailController@findByEmployeeID');
        });

        /**
         * Officer In Charge Trail Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('officer-incharge-trail-all', 'OfficerInChargeTrailController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('officer-incharge-trail/find-by-employee/{id}', 'OfficerInChargeTrailController@findByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('officer-incharge-trail/{id}', 'OfficerInChargeTrailController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::delete('officer-incharge-trail/{id}', 'OfficerInChargeTrailController@destroy');
        });

        /**
         * Other Information Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('other-information/find-by-personal-info/{id}', 'OtherInformationController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('other-information/find-by-employee/{id}', 'OtherInformationController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('other-information', 'OtherInformationController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('other-information/{id}', 'OtherInformationController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('other-information/{id}', 'OtherInformationController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('other-information-personal-info/{id}', 'OtherInformationController@destroyByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('other-information-employee/{id}', 'OtherInformationController@destroyByEmployeeID');
        });

        /**
         * Personal Information Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('personal-information-all', 'PersonalInformationController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('personal-information', 'PersonalInformationController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('personal-information/{id}', 'PersonalInformationController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('personal-information/{id}', 'PersonalInformationController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('personal-information/{id}', 'PersonalInformationController@destroy');
        });

        /**
         * Plantilla Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('plantilla-all', 'PlantillaController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('plantilla/find-by-designation/{id}', 'PlantillaController@findByDesignationID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('plantilla', 'PlantillaController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('plantilla-assign-area/{id}', 'PlantillaController@assignPlantillaToAreas');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('plantilla/areas-for-plantilla-assign', 'PlantillaController@areasForPlantillaAssign');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('plantilla/{id}', 'PlantillaController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('plantilla-number-find/{id}', 'PlantillaController@showPlantillaNumber');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('plantilla/{id}', 'PlantillaController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('plantilla/{id}', 'PlantillaController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('plantilla-number/{id}', 'PlantillaController@destroyPlantillaNumber');
        });

        /**
         * Position System Role Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('position-system-role-all', 'PositionSystemRoleController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('position-system-role/find-by-designation/{id}', 'PositionSystemRoleController@findDesignationAccessRights');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('position-system-role', 'PositionSystemRoleController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('position-system-role/{id}', 'PositionSystemRoleController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('position-system-role/{id}', 'PositionSystemRoleController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('position-system-role/{id}', 'PositionSystemRoleController@destroy');
        });

        /**
         * Profile Update Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('profile-update/find-by-personal-info/{id}', 'ProfileUpdateController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('profile-update/find-by-employee/{id}', 'ProfileUpdateController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('profile-update', 'ProfileUpdateController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('profile-update/{id}', 'ProfileUpdateController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('profile-update/{id}', 'ProfileUpdateController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('profile-update/{id}', 'ProfileUpdateController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('profile-update-personal-info/{id}', 'ProfileUpdateController@destroyByPersonalInformationID');
        });

        /**
         * Reference Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('reference/find-by-personal-info/{id}', 'ReferencesController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('reference/find-by-employee/{id}', 'ReferencesController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('reference', 'ReferencesController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('reference/{id}', 'ReferencesController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('reference/{id}', 'ReferencesController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('reference/{id}', 'ReferencesController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('reference-personal-info/{id}', 'ReferencesController@destroyByPersonaslInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('reference-employee/{id}', 'ReferencesController@destroyByEmployeeID');
        });

        /**
         * Salary Grade Module
         */
        
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::post('salary-grade-import', 'SalaryGradeController@importSalaryGrade');
        });

        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('salary-grade-all', 'SalaryGradeController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('salary-grade', 'SalaryGradeController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('salary-grade/{id}', 'SalaryGradeController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('salary-grade/{id}', 'SalaryGradeController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('salary-grade/{id}', 'SalaryGradeController@destroy');
        });

        /**
         * Section Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('section-all', 'SectionController@index');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('section/assign-supervisor/{id}', 'SectionController@assignSupervisorByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('section/assign-oic/{id}', 'SectionController@assignOICByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('section', 'SectionController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('section/{id}', 'SectionController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::post('section-update/{id}', 'SectionController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('section/{id}', 'SectionController@destroy');
        });

        /**
         * Special Access Role Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('special-access-role-all', 'SpecialAccessRoleController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('special-access-role', 'SpecialAccessRoleController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('special-access-role/{id}', 'SpecialAccessRoleController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('special-access-role/{id}', 'SpecialAccessRoleController@destroy');
        });

        /**
         * Training Module
         */
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('training/find-by-personal-info/{id}', 'TrainingController@assignHeadByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('training/find-by-employee/{id}', 'TrainingController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('training', 'TrainingController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('training/{id}', 'TrainingController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('training/{id}', 'TrainingController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('training/{id}', 'TrainingController@destroy');
        });

        /**
         * Unit Module
         */
        Route::middleware(['auth.permission:UMIS-EM view-all'])->group(function(){
            Route::get('unit-all', 'UnitController@index');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('unit/assign-head-employee/{id}', 'UnitController@assignHeadByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('unit/assign-oic-employee{id}', 'UnitController@assignOICByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('unit', 'UnitController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('unit/{id}', 'UnitController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::post('unit-update/{id}', 'UnitController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('unit/{id}', 'UnitController@destroy');
        });

        /**
         * Voluntary Work Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('voluntary-work/assign-head-employee/{id}', 'VoluntaryWorkController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('voluntary-work/assign-oic-employee{id}', 'VoluntaryWorkController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('voluntary-work', 'VoluntaryWorkController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('voluntary-work/{id}', 'VoluntaryWorkController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('voluntary-work/{id}', 'VoluntaryWorkController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('voluntary-work/{id}', 'VoluntaryWorkController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('voluntary-work-personal-info/{id}', 'VoluntaryWorkController@destroyByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('voluntary-work-employee/{id}', 'VoluntaryWorkController@destroyByEmployeeID');
        });

        /**
         * Voluntary Work Module
         */
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('work-experience/assign-head-employee/{id}', 'WorkExperienceController@findByPersonalInformationID');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('work-experience/assign-oic-employee{id}', 'WorkExperienceController@findByEmployeeID');
        });

        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('work-experience', 'WorkExperienceController@store');
        });

        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('work-experience/{id}', 'WorkExperienceController@show');
        });

        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('work-experience/{id}', 'WorkExperienceController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('work-experience/{id}', 'WorkExperienceController@destroy');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('work-experience-personal-info/{id}', 'WorkExperienceController@destroyByPersonalInformationID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('work-experience-employee/{id}', 'WorkExperienceController@destroyByEmployeeID');
        });
    });

    /**
     * Daily Time Record Management
     */
    Route::namespace('App\Http\Controllers\DTR')->group(function(){
        /** APPLY CODE HERE */
    });

    /**
     * Leave and Overtime Management
     */
    Route::namespace('App\Http\Controllers\LeaveAndOverTime')->group(function(){
      
        //leave types
        // Route::get('leave_types', 'LeaveTypeController@index');
        Route::post('store_leave_types', 'LeaveTypeController@store');
        Route::post('update_leave_types/{id}', 'LeaveTypeController@update');
        Route::post('deactivate_leave_type/{id}', 'LeaveTypeController@deactivateLeaveType');
        Route::post('reactivate_leave_type/{id}', 'LeaveTypeController@reactivateLeaveType');

        //requirements
        // Route::get('requirements', 'RequirementController@index');
        Route::post('store_requirements', 'RequirementController@store');
        Route::post('update_requirements/{id}', 'RequirementController@update');

        //leave applications
        // Route::get('leave_applications', 'LeaveApplicationController@index');
        Route::get('user_leave_applications', 'LeaveApplicationController@getUserLeaveApplication');
        Route::post('store_leave_applications', 'LeaveApplicationController@store');
        Route::post('decline_leave_applications/{id}', 'LeaveApplicationController@declineLeaveApplication');
        Route::post('cancel_leave_applications/{id}', 'LeaveApplicationController@cancelLeaveApplication');
        Route::post('update_leave_applications_status/{id}', 'LeaveApplicationController@updateLeaveApplicationStatus');

        //leave credits 
        Route::get('employee_leave_credit', 'LeaveApplicationController@getEmployeeLeaveCredit');
        Route::get('employee_leave_credit_logs', 'LeaveApplicationController@getEmployeeLeaveCreditLogs');
        Route::get('user_leave_credit_logs', 'LeaveApplicationController@getUserLeaveCreditsLogs');
        Route::get('add_monthly_leave_credit', 'LeaveCreditController@addMonthlyLeaveCredit');
        Route::get('check_user_leave_credit', 'LeaveCreditController@checkUserLeaveCredit');
        Route::get('get_employee_leave_credit', 'LeaveCreditController@getEmployeeLeaveCredit');
        Route::get('get_employee_leave_credit_logs', 'LeaveCreditController@getEmployeeLeaveCreditLogs');


        //official time applications
        Route::get('official_time_applications', 'OfficialTimeApplicationController@index');
        Route::get('user_official_time_applications', 'OfficialTimeApplicationController@getOtApplications');
        Route::post('store_official_time_applications', 'OfficialTimeApplicationController@store');
        Route::post('decline_official_time_applications/{id}', 'OfficialTimeApplicationController@declineOtApplication');
        Route::post('cancel_official_time_applications/{id}', 'OfficialTimeApplicationController@cancelOtApplication');
        Route::post('update_official_time_application_status/{id}', 'OfficialTimeApplicationController@updateStatus');
        Route::post('update_official_time_application/{id}', 'OfficialTimeApplicationController@updateOtApplication');


        //official business applications
        // Route::get('official_business_applications', 'OfficialBusinessApplicationController@index');
        // Route::get('user_official_business_applications', 'OfficialBusinessApplicationController@getObApplications');
        // Route::post('store_official_business_applications', 'OfficialBusinessApplicationController@store');
        // Route::post('decline_official_business_applications/{id}', 'OfficialBusinessApplicationController@declineObApplication');
        // Route::post('cancel_official_business_applications/{id}', 'OfficialBusinessApplicationController@cancelObApplication');
        // Route::post('update_official_business_application_status/{id}', 'OfficialBusinessApplicationController@updateStatus');
        // Route::post('update_official_business_application/{id}', 'OfficialBusinessApplicationController@updateObApplication');

        Route::middleware(['auth.permission:UMIS-LOM view-all'])->group(function(){
            Route::get('time-shift', 'TimeShiftController@index');
        });
    });

    /**
     * Schedule Management
     */
    Route::namespace('App\Http\Controllers\Schedule')->group(function(){
        /**
         * Time Shift Module
         */
        Route::middleware(['auth.permission:UMIS-ScM view-all'])->group(function(){
            Route::get('time-shift', 'TimeShiftController@index');
        });

        Route::middleware(['auth.permission:UMIS-ScM write'])->group(function(){
            Route::post('time-shift', 'TimeShiftController@store');
        });

        Route::middleware(['auth.permission:UMIS-ScM view'])->group(function(){
            Route::get('time-shift/{id}', 'TimeShiftController@show');
        });

        Route::middleware(['auth.permission:UMIS-ScM update'])->group(function(){
            Route::put('time-shift/{id}', 'TimeShiftController@update');
        });

        Route::middleware(['auth.permission:UMIS-ScM delete'])->group(function(){
            Route::delete('time-shift/{id}', 'TimeShiftController@destroy');
        });
    });
});