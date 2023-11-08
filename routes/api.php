<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Attach CSP in response
// Route::middleware('csp.token')->group(function(){
// });

Route::namespace('App\Http\Controllers')->group(function () {
    Route::post('sign-in', 'EmployeeProfileController@signIn');
    Route::post('send-otp', 'EmployeeProfileController@sendOTPEmail');
    Route::post('validate-otp', 'EmployeeProfileController@validateOTP');
    Route::post('reset-password', 'EmployeeProfileController@resetPassword');
    Route::get('retrieve-token', 'CsrfTokenController@generateCsrfToken');
    Route::get('validate-token', 'CsrfTokenController@validateToken');
});

Route::middleware('auth.cookie')->group(function(){
    
    Route::namespace('App\Http\Controllers')->group(function(){
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
    Route::namespace('App\Http\Controllers')->group(function(){
        
        /**
         * Default Password Module
         */
        Route::middleware(['auth.permission:UMIS-SM view-all'])->group(function(){
            Route::get('default-password-all', 'DefaultPasswordController@index');
        });

        Route::middleware(['auth.permission:UMIS-SM write'])->group(function(){
            Route::post('default-password-all-employee/{id}', 'DefaultPasswordController@store');
        });

        Route::middleware(['auth.permission:UMIS-SM view'])->group(function(){
            Route::get('default-password/{id}', 'DefaultPasswordController@show');
        });

        Route::middleware(['auth.permission:UMIS-SM update'])->group(function(){
            Route::put('default-password/{id}', 'DefaultPasswordController@update');
        });

        Route::middleware(['auth.permission:UMIS-SM delete'])->group(function(){
            Route::put('default-password/{id}', 'DefaultPasswordController@destroy');
        });
    });
    
    /**
     * Employee Management
     */
    Route::namespace('App\Http\Controllers')->group(function(){
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
            Route::put('department-assign-head-employee/{id}', 'DepartmentController@assignHeadByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::put('department-assign-to-employee/{id}', 'DepartmentController@assignTrainingOfficerByEmployeeID');
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
            Route::put('department/{id}', 'DepartmentController@update');
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
        
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::put('division-assign-chief-employee/{id}', 'DivisionController@assignChiefByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM put'])->group(function(){
            Route::put('division-assign-oic-employee/{id}', 'DivisionController@assignOICByEmployeeID');
        });
        
        Route::middleware(['auth.permission:UMIS-EM write'])->group(function(){
            Route::post('division', 'DivisionController@store');
        });
        
        Route::middleware(['auth.permission:UMIS-EM view'])->group(function(){
            Route::get('division/{id}', 'DivisionController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('division/{id}', 'DivisionController@update');
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
            Route::get('employee-profile/{id}', 'EmployeeProfileController@show');
        });
        
        Route::middleware(['auth.permission:UMIS-EM update'])->group(function(){
            Route::put('employee-profile/{id}', 'EmployeeProfileController@update');
        });
        
        Route::middleware(['auth.permission:UMIS-EM delete'])->group(function(){
            Route::delete('employee-profile/{id}', 'EmployeeProfileController@destroy');
        });
    });

    Route::namespace('App\Http\Controllers')->group(function(){

    });
});